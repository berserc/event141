<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Anbindung an eine Gym141-Instanz ueber deren Verwaltungs-App-API
 * (/api/app/verwaltung/*, Bearer-Token).
 *
 * Ablauf: Einmal mit einem Verwaltungs-Benutzer des Vereins anmelden – das
 * Token wird beim Gym gespeichert, damit Mitglieder jederzeit erneut geholt
 * werden koennen, ohne das Passwort nochmals einzugeben. "Verbindung trennen"
 * meldet das Token bei Gym141 ab und loescht es hier.
 */
final class Gym141Client
{
    private string $base;

    public function __construct(string $baseUrl, private string $token = '')
    {
        $this->base = self::normalizeUrl($baseUrl);
    }

    /** "gym.example.org/" -> "https://gym.example.org" */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            throw new RuntimeException(t('Bitte die Adresse der Gym141-Instanz angeben.'));
        }

        if (!preg_match('#^https?://#i', $url)) {
            // Lokale Entwicklungsadressen laufen ohne TLS, alles andere per HTTPS.
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
            throw new RuntimeException(t('Die Gym141-Adresse ist keine gültige URL.'));
        }

        return $url;
    }

    public function baseUrl(): string
    {
        return $this->base;
    }

    /**
     * Anmeldung: liefert Token, Vereinsname und Benutzerdaten.
     *
     * @return array{token:string,club:array<string,mixed>,user:array<string,mixed>}
     */
    public function login(string $username, string $password): array
    {
        $data = $this->request('POST', '/api/app/verwaltung/login', [
            'username' => $username,
            'password' => $password,
            'device'   => 'Event141',
        ]);

        if (!isset($data['token']) || !is_string($data['token'])) {
            throw new RuntimeException(t('Gym141 hat kein Token geliefert.'));
        }

        $this->token = $data['token'];

        return [
            'token' => $data['token'],
            'club'  => (array) ($data['club'] ?? []),
            'user'  => (array) ($data['user'] ?? []),
        ];
    }

    public function logout(): void
    {
        try {
            $this->request('POST', '/api/app/verwaltung/logout');
        } catch (RuntimeException) {
            // Token war schon ungueltig – egal, es wird ohnehin verworfen.
        }
    }

    /** @return array<string,mixed> Kennzahlen + Vereinsname (Verbindungstest). */
    public function overview(): array
    {
        return $this->request('GET', '/api/app/verwaltung/uebersicht');
    }

    /**
     * Mitgliederliste (optional gefiltert).
     *
     * @return list<array<string,mixed>>
     */
    public function members(string $search = ''): array
    {
        $path = '/api/app/verwaltung/mitglieder' . ($search !== '' ? '?suche=' . rawurlencode($search) : '');
        $data = $this->request('GET', $path);

        /** @var list<array<string,mixed>> */
        return array_values((array) ($data['members'] ?? []));
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

        if ($this->token !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $payload = null;

        if ($body !== null) {
            $payload   = json_encode($body, JSON_UNESCAPED_UNICODE);
            $headers[] = 'Content-Type: application/json';
        }

        [$status, $raw] = function_exists('curl_init')
            ? $this->viaCurl($method, $url, $headers, $payload, $timeout)
            : $this->viaStream($method, $url, $headers, $payload, $timeout);

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            throw new RuntimeException(
                t('Gym141 hat keine gültige Antwort geliefert (HTTP %d). Stimmt die Adresse?', $status)
            );
        }

        if ($status >= 400) {
            throw new RuntimeException((string) ($data['error'] ?? t('Gym141-Fehler (HTTP %d)', $status)));
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
            // curl_close() ist seit PHP 8.0 wirkungslos (und ab 8.5 deprecated).
            throw new RuntimeException(t('Gym141 nicht erreichbar: %s', curl_error($ch)));
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
            throw new RuntimeException(t('Gym141 nicht erreichbar: %s', $url));
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
