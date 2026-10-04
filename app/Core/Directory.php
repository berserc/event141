<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\EventRepo;
use Throwable;

/**
 * Zentrales Event-Verzeichnis (event141.com).
 *
 * Zwei Rollen in derselben Anwendung:
 *
 * 1. Jede Instanz MELDET Events, die im Formular als "im Event141-Verzeichnis
 *    listen" markiert sind: nach dem Speichern geht ein kurzer Ping
 *    (Instanz-Adresse + Event-Kuerzel) an das Verzeichnis.
 * 2. Die Verzeichnis-Instanz (app/config.php: 'directory' => true) HOLT sich
 *    daraufhin die Daten selbst ueber die oeffentliche API der Instanz
 *    (/api/event/<kuerzel>/info). Gelistet wird also nur, was wirklich unter
 *    der genannten Adresse erreichbar ist und dort freigegeben wurde.
 *
 * Instanzen unter vertrauten Domains (Vorgabe *.event141.com) erscheinen
 * sofort, alle anderen erst nach Freigabe in der Verwaltung des Verzeichnisses.
 */
final class Directory
{
    public const STATES = [
        'sichtbar'  => 'sichtbar',
        'wartet'    => 'wartet auf Freigabe',
        'versteckt' => 'versteckt',
    ];

    /** @var array<int,true> */
    private static array $queued = [];

    private static bool $registered = false;

    // ------------------------------------------------------------ Allgemein --

    /** Ist diese Installation das zentrale Verzeichnis? */
    public static function isDirectory(): bool
    {
        return (bool) Config::get('directory', false);
    }

    /** Adresse des Verzeichnisses, an das diese Instanz meldet ('' = Melden abgeschaltet). */
    public static function url(): string
    {
        return rtrim((string) Config::get('directory_url', 'https://event141.com'), '/');
    }

    /** Oeffentliche Adresse dieser Instanz. */
    public static function ownUrl(): string
    {
        $host = (string) Config::get('canonical_host', '');

        return $host !== '' ? 'https://' . $host : Fightcard::requestBase();
    }

    // ------------------------------------------------------ Instanz: melden --

    /** Event nach dem Absenden der Antwort an das Verzeichnis melden. */
    public static function queue(int $eventId): void
    {
        if ($eventId <= 0 || self::url() === '' || self::isDirectory()) {
            return;
        }

        self::$queued[$eventId] = true;

        if (!self::$registered) {
            self::$registered = true;
            register_shutdown_function([self::class, 'flush']);
        }
    }

    public static function flush(): void
    {
        $ids          = array_keys(self::$queued);
        self::$queued = [];

        if ($ids === []) {
            return;
        }

        // Antwort an den Browser zuerst abschliessen, dann melden.
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        foreach ($ids as $id) {
            try {
                self::announce($id);
            } catch (Throwable $e) {
                error_log('[event141] Verzeichnis-Meldung fehlgeschlagen: ' . $e->getMessage());
            }
        }
    }

    /**
     * Ein Event an das Verzeichnis melden (oder abmelden, wenn es nicht mehr
     * gelistet sein soll). Das Ergebnis steht danach in events.directory_note.
     */
    public static function announce(int $eventId): string
    {
        $event = EventRepo::find($eventId);

        if ($event === null) {
            return '';
        }

        $soll = (int) ($event['directory_listed'] ?? 0) === 1 && (int) $event['published'] === 1;
        $war  = (string) ($event['directory_note'] ?? '') !== '';

        // Nie gelistet und soll es auch nicht sein: nichts zu tun.
        if (!$soll && !$war) {
            return '';
        }

        $antwort = self::post(self::url() . '/api/directory/ping', ['url' => self::ownUrl(), 'slug' => (string) $event['slug']]);
        $zeit    = date('d.m.Y H:i');

        if ($antwort === null) {
            $note = t('Verzeichnis nicht erreichbar (%s).', $zeit);
        } elseif (!$soll) {
            $note = '';
        } else {
            $note = match ((string) ($antwort['state'] ?? '')) {
                'sichtbar' => t('Im Verzeichnis gelistet (%s).', $zeit),
                'wartet'   => t('Gemeldet – wartet auf Freigabe durch das Verzeichnis (%s).', $zeit),
                default    => t('Verzeichnis: %s', (string) ($antwort['error'] ?? $antwort['state'] ?? '?')),
            };
        }

        Database::update('events', $eventId, ['directory_note' => $note]);

        return $note;
    }

    /** Geloeschtes Event im Verzeichnis abmelden (das Verzeichnis findet es nicht mehr und entfernt den Eintrag). */
    public static function forget(string $slug): void
    {
        if (self::url() === '' || self::isDirectory()) {
            return;
        }

        try {
            self::post(self::url() . '/api/directory/ping', ['url' => self::ownUrl(), 'slug' => $slug]);
        } catch (Throwable) {
            // Verzeichnis nicht erreichbar: der Eintrag faellt beim naechsten Abgleich heraus.
        }
    }

    // -------------------------------------------- Verzeichnis: aufnehmen --

    /**
     * Meldung einer Instanz verarbeiten: Daten dort abholen und den Eintrag
     * anlegen, aktualisieren oder entfernen.
     *
     * @return array{ok:bool,state?:string,error?:string}
     */
    public static function ping(string $url, string $slug): array
    {
        $url  = rtrim(trim($url), '/');
        $slug = trim($slug);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '' || $slug === '' || preg_match('/^[a-z0-9][a-z0-9\-]{0,120}$/', $slug) !== 1) {
            return ['ok' => false, 'error' => 'Adresse oder Event-Kürzel fehlt.'];
        }

        if (!self::allowedSource($url, $host)) {
            return ['ok' => false, 'error' => 'Diese Adresse kann nicht gelistet werden (nur öffentliche https-Adressen).'];
        }

        $info = self::fetch($url, $slug);

        // Nicht (mehr) vorhanden, nicht veroeffentlicht oder nicht fuers Verzeichnis freigegeben: Eintrag entfernen.
        if ($info === null || empty($info['directory']) || empty($info['published'])) {
            Database::run('DELETE FROM directory_events WHERE source_url = ? AND slug = ?', [$url, $slug]);

            return ['ok' => true, 'state' => 'entfernt'];
        }

        return ['ok' => true, 'state' => self::ingest($url, $host, $info)];
    }

    /**
     * Eintrag anlegen/aktualisieren. Liefert den Sichtbarkeits-Status.
     *
     * @param array<string,mixed> $info Antwort von /api/event/<slug>/info
     */
    public static function ingest(string $url, string $host, array $info): string
    {
        $slug  = (string) $info['slug'];
        $alt   = Database::one('SELECT id, state FROM directory_events WHERE source_url = ? AND slug = ?', [$url, $slug]);
        $state = $alt !== null ? (string) $alt['state'] : (self::trusted($host) ? 'sichtbar' : 'wartet');
        $kurz  = static fn (mixed $v, int $n): string => mb_substr(trim(strip_tags((string) $v)), 0, $n);

        $daten = [
            'source_url'         => $url,
            'host'               => $host,
            'slug'               => $slug,
            'name'               => $kurz($info['name'] ?? '', 200),
            'type'               => in_array($info['type'] ?? '', ['turnier', 'gala'], true) ? (string) $info['type'] : 'turnier',
            'sport'              => $kurz($info['sport'] ?? '', 80),
            'federation'         => $kurz($info['federation'] ?? '', 80),
            'country'            => preg_match('/^[A-Za-z]{2}$/', (string) ($info['country'] ?? '')) === 1 ? strtoupper((string) $info['country']) : '',
            'region'             => $kurz($info['region'] ?? '', 80),
            'city'               => $kurz($info['city'] ?? '', 120),
            'venue'              => $kurz($info['venue_name'] ?? '', 160),
            'starts_on'          => self::date($info['starts_on'] ?? ''),
            'ends_on'            => self::date($info['ends_on'] ?? '') ?: self::date($info['starts_on'] ?? ''),
            'status'             => $kurz($info['status'] ?? '', 20),
            'registration_open'  => !empty($info['registration_open']) ? 1 : 0,
            'registration_until' => self::date($info['registration_until'] ?? ''),
            'url'                => self::sameHost((string) ($info['url'] ?? ''), $host) ?: $url . '/' . $slug,
            'image_url'          => self::sameHost((string) ($info['poster_url'] ?? ''), $host),
            'org_name'           => $kurz($info['org'] ?? '', 160),
            'state'              => $state,
            'fetched_at'         => gmdate('Y-m-d H:i:s'),
        ];

        if ($daten['name'] === '' || $daten['starts_on'] === '') {
            return 'ungültig';
        }

        if ($alt !== null) {
            Database::update('directory_events', (int) $alt['id'], $daten);
        } else {
            Database::insert('directory_events', $daten);
        }

        return $state;
    }

    /** Alle Eintraege neu abholen (Cron: bin/directory-refresh.php). @return array{ok:int,entfernt:int,fehler:int} */
    public static function refreshAll(): array
    {
        $out = ['ok' => 0, 'entfernt' => 0, 'fehler' => 0];

        foreach (Database::all('SELECT id, source_url, host, slug FROM directory_events ORDER BY fetched_at') as $row) {
            $info = self::fetch((string) $row['source_url'], (string) $row['slug'], $erreichbar);

            if ($info === null && !$erreichbar) {
                $out['fehler']++;   // Instanz gerade nicht erreichbar: Eintrag behalten
                continue;
            }

            if ($info === null || empty($info['directory']) || empty($info['published'])) {
                Database::run('DELETE FROM directory_events WHERE id = ?', [(int) $row['id']]);
                $out['entfernt']++;
                continue;
            }

            self::ingest((string) $row['source_url'], (string) $row['host'], $info);
            $out['ok']++;
        }

        return $out;
    }

    /** @return list<array<string,mixed>> sichtbare Eintraege */
    public static function visible(): array
    {
        try {
            return Database::all("SELECT * FROM directory_events WHERE state = 'sichtbar' ORDER BY starts_on, name");
        } catch (Throwable) {
            return [];   // Tabelle fehlt noch (Migration ausstehend)
        }
    }

    public static function trusted(string $host): bool
    {
        foreach ((array) Config::get('directory_trusted', ['.event141.com']) as $suffix) {
            $suffix = strtolower((string) $suffix);

            if ($suffix !== '' && ($host === ltrim($suffix, '.') || str_ends_with($host, $suffix[0] === '.' ? $suffix : '.' . $suffix))) {
                return true;
            }
        }

        return false;
    }

    // --------------------------------------------------------------- Intern --

    /**
     * Daten eines Events von einer Instanz holen.
     *
     * @param-out bool $erreichbar ob die Instanz ueberhaupt geantwortet hat
     * @return array<string,mixed>|null
     */
    private static function fetch(string $url, string $slug, ?bool &$erreichbar = null): ?array
    {
        $erreichbar = false;
        $host       = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (!self::allowedSource($url, $host)) {
            return null;
        }

        [$status, $body] = self::http('GET', $url . '/api/event/' . rawurlencode($slug) . '/info');

        if ($status === 0) {
            return null;
        }

        $erreichbar = true;
        $data       = $status === 200 ? json_decode($body, true) : null;

        return is_array($data) && isset($data['event']['slug']) && (string) $data['event']['slug'] === $slug ? (array) $data['event'] : null;
    }

    /**
     * Nur oeffentliche https-Adressen abholen – keine internen Netze, kein
     * Port, keine Zugangsdaten (Schutz davor, das Verzeichnis als Sprungbrett
     * ins eigene Netz zu missbrauchen).
     */
    private static function allowedSource(string $url, string $host): bool
    {
        $teile = parse_url($url);

        if ($teile === false || $host === '' || isset($teile['user']) || isset($teile['port']) || (string) ($teile['path'] ?? '') !== '') {
            return false;
        }

        // Entwicklung: lokale http-Adressen zulassen
        if ((bool) Config::get('directory_allow_local', false)) {
            return true;
        }

        if (($teile['scheme'] ?? '') !== 'https' || filter_var($host, FILTER_VALIDATE_IP) !== false || !str_contains($host, '.')) {
            return false;
        }

        $ips = gethostbynamel($host) ?: [];

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    /** Adresse nur uebernehmen, wenn sie zur meldenden Instanz gehoert. */
    private static function sameHost(string $adresse, string $host): string
    {
        return $adresse !== '' && strtolower((string) parse_url($adresse, PHP_URL_HOST)) === $host ? mb_substr($adresse, 0, 400) : '';
    }

    private static function date(mixed $v): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) === 1 ? (string) $v : '';
    }

    /** @return array<string,mixed>|null */
    private static function post(string $url, array $payload): ?array
    {
        [$status, $body] = self::http('POST', $url, (string) json_encode($payload));
        $data            = json_decode($body, true);

        return $status === 0 || !is_array($data) ? null : $data;
    }

    /** @return array{0:int,1:string} Status (0 = nicht erreichbar) und Antwort */
    private static function http(string $method, string $url, ?string $body = null): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/json', 'User-Agent: Event141-Directory'],
            ]);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $antwort = curl_exec($ch);
            $status  = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            return $antwort === false ? [0, ''] : [$status, (string) $antwort];
        }

        $kontext = stream_context_create(['http' => [
            'method' => $method, 'timeout' => 10, 'ignore_errors' => true, 'follow_location' => 0,
            'header' => "Accept: application/json\r\nContent-Type: application/json\r\nUser-Agent: Event141-Directory\r\n",
            'content' => $body ?? '',
        ]]);
        $antwort = @file_get_contents($url, false, $kontext);
        $status  = 0;

        foreach ($http_response_header ?? [] as $zeile) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $zeile, $m) === 1) {
                $status = (int) $m[1];
            }
        }

        return $antwort === false ? [0, ''] : [$status, $antwort];
    }
}
