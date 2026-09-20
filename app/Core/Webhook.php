<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Ausgehende Webhooks: gekoppelte Systeme (z. B. eine eigene Event-Website)
 * erfahren sofort von Aenderungen und holen sich dann den frischen Stand
 * ueber die API.
 *
 * Aenderungen werden waehrend der Anfrage nur vorgemerkt (queue) und nach dem
 * Absenden der Antwort zugestellt (flush, per register_shutdown_function) –
 * ein langsamer Empfaenger bremst die Verwaltung dadurch nicht.
 *
 * Body:   {"event":"<slug>","event_id":1,"type":"event.changed","at":"..."}
 * Header: X-Event141-Signature: sha256=<HMAC-SHA256 des Bodys mit dem Secret>
 */
final class Webhook
{
    /** @var array<int,true> */
    private static array $queued = [];

    private static bool $registered = false;

    public static function queue(int $eventId): void
    {
        if ($eventId <= 0) {
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
        if (self::$queued === []) {
            return;
        }

        $ids          = array_keys(self::$queued);
        self::$queued = [];

        try {
            $hooks = Database::all('SELECT * FROM webhooks WHERE active = 1');
        } catch (Throwable) {
            return; // Tabelle fehlt noch (Migration ausstehend)
        }

        if ($hooks === []) {
            return;
        }

        // Antwort an den Browser abschliessen, bevor zugestellt wird.
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }

        foreach ($ids as $eventId) {
            $event = Database::one('SELECT id, slug FROM events WHERE id = ?', [$eventId]);

            if ($event === null) {
                continue;
            }

            foreach ($hooks as $hook) {
                if ($hook['event_id'] !== null && (int) $hook['event_id'] !== $eventId) {
                    continue;
                }

                self::deliver($hook, [
                    'event'    => (string) $event['slug'],
                    'event_id' => $eventId,
                    'type'     => 'event.changed',
                    'at'       => gmdate('c'),
                ]);
            }
        }
    }

    /** Stellt einen Hook zu und merkt das Ergebnis. @param array<string,mixed> $payload */
    public static function deliver(array $hook, array $payload): string
    {
        $body   = (string) json_encode($payload, JSON_UNESCAPED_SLASHES);
        $sig    = 'sha256=' . hash_hmac('sha256', $body, (string) $hook['secret']);
        $status = 'Fehler';

        try {
            if (function_exists('curl_init')) {
                $ch = curl_init((string) $hook['url']);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $body,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 5,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-Event141-Signature: ' . $sig],
                ]);
                $ok     = curl_exec($ch);
                $status = $ok === false ? 'Fehler: ' . curl_error($ch) : 'HTTP ' . (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            } else {
                $ctx = stream_context_create(['http' => [
                    'method'        => 'POST',
                    'header'        => "Content-Type: application/json\r\nX-Event141-Signature: $sig",
                    'content'       => $body,
                    'timeout'       => 5,
                    'ignore_errors' => true,
                ]]);
                $ok     = @file_get_contents((string) $hook['url'], false, $ctx);
                $status = $ok === false ? 'Fehler: nicht erreichbar' : (string) ($http_response_header[0] ?? 'gesendet');
            }
        } catch (Throwable $e) {
            $status = 'Fehler: ' . $e->getMessage();
        }

        Database::run(
            'UPDATE webhooks SET last_status = ?, last_at = ? WHERE id = ?',
            [mb_substr($status, 0, 200), gmdate('Y-m-d H:i:s'), (int) $hook['id']]
        );

        return $status;
    }
}
