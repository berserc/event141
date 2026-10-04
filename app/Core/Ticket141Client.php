<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;
use RuntimeException;

/**
 * Anbindung an das Schwesterprodukt Ticket141 (Ticketshop) ueber dessen
 * Plattform-API (/api/v1/*, Bearer-Schluessel "tk_…").
 *
 * Einstellungen: ticket141_url, ticket141_api_key (Schluessel mit Scope
 * "write", damit Events angelegt werden koennen), ticket141_embed.
 * Ein Event141-Event wird ueber external_source='event141' und
 * external_ref=<Event141-Kuerzel> in Ticket141 wiedergefunden (Upsert).
 */
final class Ticket141Client
{
    /** Wie lange die oeffentliche Event-Seite Ticket141-Daten wiederverwendet. */
    public const CACHE_SECONDS = 300;

    private string $base = '';

    private int $lastStatus = 0;

    public function __construct(string $baseUrl = '', private string $apiKey = '')
    {
        if (trim($baseUrl) !== '') {
            $this->base = self::normalizeUrl($baseUrl);
        }
    }

    /** Client aus den gespeicherten Einstellungen (ohne Adresse: nicht konfiguriert). */
    public static function fromSettings(): self
    {
        try {
            return new self(Setting::get('ticket141_url'), Setting::get('ticket141_api_key'));
        } catch (RuntimeException) {
            return new self();
        }
    }

    /** "tickets.example.org/" -> "https://tickets.example.org" (lokale Adressen http). */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            throw new RuntimeException(t('Bitte die Adresse der Ticket141-Instanz angeben.'));
        }

        if (!preg_match('#^https?://#i', $url)) {
            $host  = strtolower((string) strtok($url, '/:'));
            $lokal = in_array($host, ['localhost', '127.0.0.1'], true)
                || str_ends_with($host, '.local') || str_ends_with($host, '.test');

            $url = ($lokal ? 'http://' : 'https://') . $url;
        }

        $url = rtrim($url, '/');

        if (str_ends_with($url, '/admin')) {
            $url = substr($url, 0, -6);
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException(t('Die Ticket141-Adresse ist keine gültige URL.'));
        }

        return $url;
    }

    public function baseUrl(): string
    {
        return $this->base;
    }

    /** Adresse hinterlegt? (Der Schluessel ist nur fuers Anlegen/Kennzahlen noetig.) */
    public function configured(): bool
    {
        return $this->base !== '';
    }

    public function hasKey(): bool
    {
        return $this->apiKey !== '';
    }

    /** Oeffentliche Shop-Seite eines Ticket141-Events. */
    public function shopUrl(string $slug): string
    {
        return $this->base . '/t/' . rawurlencode($slug);
    }

    /** Skript fuer das Einbett-Widget (<div data-ticket141="slug"></div>). */
    public function embedScriptUrl(): string
    {
        return $this->base . '/embed.js';
    }

    // ------------------------------------------------------------- API --

    /**
     * Verbindungstest (GET /api/v1/ping, braucht einen Schluessel).
     *
     * @return array<string,mixed> service, version, key{name,scope,event}
     */
    public function ping(): array
    {
        $this->requireConfigured();

        if ($this->apiKey === '') {
            throw new RuntimeException(t('Bitte einen Ticket141-API-Schlüssel (tk_…) hinterlegen.'));
        }

        $data = $this->request('GET', '/api/v1/ping');

        if (($data['service'] ?? '') !== 'ticket141') {
            throw new RuntimeException(t('Unter dieser Adresse antwortet kein Ticket141.'));
        }

        return $data;
    }

    /**
     * Event + Kategorien (Preis, Verfuegbarkeit) + Verkaufsstatus, live.
     *
     * @return array{event:array<string,mixed>,categories:list<array<string,mixed>>,sale:array<string,mixed>}
     */
    public function event(string $slug): array
    {
        $this->requireConfigured();

        $data = $this->request('GET', '/api/v1/event/' . rawurlencode($slug));

        return [
            'event'      => (array) ($data['event'] ?? []),
            'categories' => array_values((array) ($data['categories'] ?? [])),
            'sale'       => (array) ($data['sale'] ?? []),
        ];
    }

    /**
     * Wie event(), aber fuer die oeffentliche Seite gepuffert (5 Minuten in
     * den Einstellungen). Bei Fehlern gilt der letzte Stand weiter; gab es
     * noch keinen, kommt null zurueck.
     *
     * @return array<string,mixed>|null
     */
    public function eventCached(string $slug): ?array
    {
        if (!$this->configured() || $slug === '') {
            return null;
        }

        $key   = 'ticket141_cache_' . $slug;
        $cache = json_decode(Setting::get($key, ''), true);
        $cache = is_array($cache) ? $cache : null;

        if ($cache !== null && (int) ($cache['at'] ?? 0) + self::CACHE_SECONDS > time() && is_array($cache['data'] ?? null)) {
            return $cache['data'];
        }

        try {
            $data = $this->event($slug);
        } catch (RuntimeException) {
            // Letzter Stand (auch wenn abgelaufen) ist besser als nichts.
            return is_array($cache['data'] ?? null) ? $cache['data'] : null;
        }

        Setting::set($key, (string) json_encode(['at' => time(), 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $data;
    }

    /** Puffer eines Events verwerfen (z. B. nach dem Anlegen/Aktualisieren). */
    public static function forgetCache(string $slug): void
    {
        if ($slug !== '') {
            Setting::set('ticket141_cache_' . $slug, '');
        }
    }

    /**
     * Kennzahlen (Tickets, Umsatz, Einlass) + Verkauf je Kategorie.
     *
     * @return array{stats:array<string,int>,categories:list<array<string,mixed>>}
     */
    public function stats(string $slug): array
    {
        $this->requireConfigured();

        $data = $this->request('GET', '/api/v1/event/' . rawurlencode($slug) . '/stats');

        return [
            'stats'      => array_map('intval', (array) ($data['stats'] ?? [])),
            'categories' => array_values((array) ($data['categories'] ?? [])),
        ];
    }

    /**
     * Event in Ticket141 anlegen bzw. aktualisieren (Upsert ueber
     * external_source + external_ref).
     *
     * @param array<string,mixed> $data name, starts_at, doors_at, venue_*, external_*, categories …
     * @return array{event:array<string,mixed>,categories:list<array<string,mixed>>,admin_url:string,created:bool}
     */
    public function createEvent(array $data): array
    {
        $this->requireConfigured();

        $data += ['external_source' => 'event141'];
        $res   = $this->request('POST', '/api/v1/events', $data);
        $event = (array) ($res['event'] ?? []);

        if (($event['slug'] ?? '') === '') {
            throw new RuntimeException(t('Ticket141 hat kein Event-Kürzel geliefert.'));
        }

        return [
            'event'      => $event,
            'categories' => array_values((array) ($res['categories'] ?? [])),
            'admin_url'  => (string) ($res['admin_url'] ?? ($event['admin_url'] ?? '')),
            'created'    => $this->lastStatus === 201,
        ];
    }

    // ------------------------------------------------------------ Hilfen --

    /** "2500" -> "25,00 €" */
    public static function price(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.') . ' €';
    }

    /**
     * Kurztext zur Verfuegbarkeit einer Kategorie aus GET /event/{slug}.
     *
     * @param array<string,mixed> $category
     */
    public static function availability(array $category): string
    {
        if (!empty($category['sold_out'])) {
            return t('ausverkauft');
        }

        $available = $category['available'] ?? null;

        if ($available !== null && (int) $available <= 20) {
            return t('nur noch %d', (int) $available);
        }

        return '';
    }

    private function requireConfigured(): void
    {
        if (!$this->configured()) {
            throw new RuntimeException(t('Ticket141 ist nicht konfiguriert (Einstellungen → Ticket141).'));
        }
    }

    // ---------------------------------------------------------------- HTTP --

    /**
     * @param array<string,mixed>|null $body
     * @return array<string,mixed>
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $url     = $this->base . $path;
        $timeout = (int) Config::get('gym141_timeout', 15);
        $headers = ['Accept: application/json'];

        if ($this->apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $payload = null;

        if ($body !== null) {
            $payload   = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json';
        }

        [$status, $raw] = function_exists('curl_init')
            ? $this->viaCurl($method, $url, $headers, $payload, $timeout)
            : $this->viaStream($method, $url, $headers, $payload, $timeout);

        $this->lastStatus = $status;
        $data             = json_decode($raw, true);

        if (!is_array($data)) {
            throw new RuntimeException(
                t('Ticket141 hat keine gültige Antwort geliefert (HTTP %d). Stimmt die Adresse?', $status)
            );
        }

        if ($status >= 400) {
            throw new RuntimeException((string) ($data['error'] ?? t('Ticket141-Fehler (HTTP %d)', $status)));
        }

        return $data;
    }

    /**
     * @param list<string> $headers
     * @return array{0:int,1:string}
     */
    private function viaCurl(string $method, string $url, array $headers, ?string $payload, int $timeout): array
    {
        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException(t('Verbindung konnte nicht aufgebaut werden.'));
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT      => 'Event141/' . trim((string) @file_get_contents(BASE_ROOT . '/VERSION')),
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $raw = curl_exec($ch);

        if ($raw === false) {
            throw new RuntimeException(t('Ticket141 nicht erreichbar: %s', curl_error($ch)));
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        return [$status, (string) $raw];
    }

    /**
     * @param list<string> $headers
     * @return array{0:int,1:string}
     */
    private function viaStream(string $method, string $url, array $headers, ?string $payload, int $timeout): array
    {
        $ctx = stream_context_create(['http' => [
            'method'        => $method,
            'header'        => implode("\r\n", $headers),
            'content'       => $payload ?? '',
            'timeout'       => $timeout,
            'ignore_errors' => true,
        ]]);

        $raw = @file_get_contents($url, false, $ctx);

        if ($raw === false) {
            throw new RuntimeException(t('Ticket141 nicht erreichbar: %s', $url));
        }

        $status = 0;

        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int) $m[1];
            }
        }

        return [$status, $raw];
    }
}
