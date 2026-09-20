<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * Verbindung zum DevWorld-Kundenkonto (account.devworld-llc.com).
 *
 * Event141 ist Open Source; ein aktives Event ist gratis (FREE_EVENT_LIMIT).
 * Ein Lizenzschlüssel (Produkt "event141-pro") hebt das Limit auf und schaltet Turnierbaum und Gym141-Kopplung frei; gebuchte
 * Zusatzmodule kommen als Feature-Codes in der Prüfantwort mit.
 *
 * Die Prüfung läuft gegen POST {license_api}/api/v1/licenses/validate und wird
 * gecacht (Setting license_state). Ist der Lizenzserver vorübergehend nicht
 * erreichbar, bleibt eine zuletzt gültige Lizenz GRACE_DAYS Tage lang gültig.
 */
final class License
{
    public const PRODUCT_CODE = 'event141-pro';

    /** Aktive Events, die ohne Lizenz möglich sind. */
    public const FREE_EVENT_LIMIT = 1;

    /** Karenz in Tagen, wenn der Lizenzserver nicht erreichbar ist. */
    private const GRACE_DAYS = 14;

    /** Frühestens alle X Stunden automatisch neu prüfen. */
    private const CHECK_HOURS = 24;

    // ------------------------------------------------------------- Zustand --

    /** @return array<string,mixed> Gecachter Prüfstand (leeres Array = nie geprüft). */
    public static function state(): array
    {
        $raw = Setting::get('license_state');

        /** @var array<string,mixed> */
        return $raw === '' ? [] : ((array) json_decode($raw, true));
    }

    public static function key(): string
    {
        $key = trim(Setting::get('devworld_license_key'));

        // Alternativ kann der Schlüssel fest in app/config.php hinterlegt
        // werden ('devworld_license_key') – praktisch für verwaltete
        // Installationen, deren Konfiguration versioniert ausgerollt wird.
        if ($key === '') {
            $key = trim((string) Config::get('devworld_license_key', ''));
        }

        return $key;
    }

    /** Basis-URL des Lizenzservers (ohne Slash am Ende). */
    public static function apiBase(): string
    {
        return rtrim((string) Config::get('license_api', 'https://api.devworld-llc.com'), '/');
    }

    /**
     * Stabile Kennung dieser Installation für die Geräteaktivierung –
     * ein Hash, es verlassen keine Klardaten den Server.
     */
    public static function deviceId(): string
    {
        $secret = Setting::get('install_secret');

        if ($secret === '') {
            $secret = bin2hex(random_bytes(16));
            Setting::set('install_secret', $secret);
        }

        return hash('sha256', $secret . '|' . (string) Config::get('db_path', ''));
    }

    // ------------------------------------------------------------ Prüfung --

    /** Prüft bei Bedarf (Cache abgelaufen) neu; liefert den aktuellen Stand. */
    public static function check(bool $force = false): array
    {
        $state = self::state();

        if (self::key() === '') {
            return $state;
        }

        $alter = time() - (int) ($state['checked_at'] ?? 0);

        if (!$force && $alter < self::CHECK_HOURS * 3600) {
            return $state;
        }

        return self::refresh();
    }

    /** Fragt den Lizenzserver an und aktualisiert den gecachten Stand. */
    public static function refresh(): array
    {
        $key = self::key();

        if ($key === '') {
            Setting::set('license_state', '');

            return [];
        }

        // Auf der Kommandozeile (Cron) gibt es keinen HTTP_HOST – dann zaehlt
        // der kanonische Host aus der Konfiguration. Der Host geht explizit
        // mit: der Lizenzserver bindet Pro-Lizenzen an EINE Domain.
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

        if ($host === '') {
            $host = (string) Config::get('canonical_host', '') ?: php_uname('n');
        }

        $antwort = self::request('/api/v1/licenses/validate', [
            'licenseKey'  => $key,
            'deviceId'    => self::deviceId(),
            'productCode' => self::PRODUCT_CODE,
            'deviceName'  => 'Event141 @ ' . $host,
            'host'        => $host,
        ]);

        $state = self::state();
        $jetzt = time();

        if ($antwort === null) {
            // Server nicht erreichbar: Karenz ab letzter erfolgreicher Prüfung.
            $state['checked_at'] = $jetzt;
            $state['reason']     = 'unreachable';

            $letzterErfolg = (int) ($state['ok_at'] ?? 0);
            if ($letzterErfolg < $jetzt - self::GRACE_DAYS * 86400) {
                $state['valid'] = false;
            }
        } else {
            $state = [
                'valid'      => (bool) ($antwort['isValid'] ?? false),
                'reason'     => $antwort['reason'] ?? null,
                'warning'    => $antwort['warning'] ?? null,
                'features'   => array_values((array) ($antwort['features'] ?? [])),
                'expires_at' => $antwort['expiresAtUtc'] ?? null,
                'trial'      => (bool) ($antwort['isTrial'] ?? false),
                'checked_at' => $jetzt,
                'ok_at'      => $jetzt,
            ];
        }

        Setting::set('license_state', (string) json_encode($state));

        return $state;
    }

    // ------------------------------------------------------------- Abfragen --

    /** Gültige Pro-Lizenz vorhanden (inkl. Offline-Karenz)? */
    public static function isPro(): bool
    {
        // Lokale Entwicklung/Testumgebung ohne Schluessel: alles frei (abschaltbar
        // ueber 'license_dev_pro' => false, um die Sperren zu testen).
        if (self::key() === '' && (string) Config::get('env', 'live') === 'dev' && (bool) Config::get('license_dev_pro', true)) {
            return true;
        }

        $state = self::check();

        return (bool) ($state['valid'] ?? false)
            && in_array(self::PRODUCT_CODE, (array) ($state['features'] ?? [self::PRODUCT_CODE]), true);
    }

    /** Läuft die aktuelle Lizenz als kostenlose 30-Tage-Testphase? */
    public static function isTrial(): bool
    {
        $state = self::check();

        return (bool) ($state['valid'] ?? false) && (bool) ($state['trial'] ?? false);
    }

    /**
     * Lizenz-Warnung oder Sperr-Hinweis fuer den Admin-Bereich (null = alles ok).
     *
     * Der Lizenzserver bindet Pro-Lizenzen an eine Domain: Laeuft derselbe
     * Schluessel auf mehreren Domains, kommt zuerst eine Warnung mit Frist
     * (im Feld "warning"), danach meldet er die Lizenz als gesperrt
     * (reason "multi_domain_blocked").
     */
    public static function warning(): ?string
    {
        $state = self::check();

        if ($state === []) {
            return null;
        }

        if (($state['reason'] ?? '') === 'multi_domain_blocked') {
            return 'Lizenz gesperrt: Dieser Lizenzschlüssel wird auf mehreren Domains verwendet. '
                . 'Event141 Pro gilt für ein System – bitte die überzähligen Installationen stilllegen '
                . 'oder eine weitere Lizenz erwerben (account.devworld-llc.com). '
                . 'Sobald nur noch eine Domain aktiv ist, wird die Lizenz automatisch wieder gültig.';
        }

        $warnung = trim((string) ($state['warning'] ?? ''));

        return $warnung !== '' ? $warnung : null;
    }

    /** Ist ein Zusatzmodul (Produktcode, z. B. "gym141-support") gebucht? */
    public static function hasModule(string $code): bool
    {
        $state = self::check();

        return (bool) ($state['valid'] ?? false)
            && in_array($code, (array) ($state['features'] ?? []), true);
    }

    /** Wie viele aktive (nicht beendete) Events sind erlaubt? */
    public static function eventLimit(): int
    {
        if (self::isPro()) {
            return PHP_INT_MAX;
        }

        return max(1, (int) Config::get('free_event_limit', self::FREE_EVENT_LIMIT));
    }

    /** Aktive Events = alles, was nicht "beendet" ist (optional ohne ein bestimmtes Event). */
    public static function activeEventCount(?int $exceptId = null): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM events WHERE status <> 'beendet' AND (? IS NULL OR id <> ?)",
            [$exceptId, $exceptId]
        );
    }

    /**
     * Darf ein (weiteres) aktives Event entstehen? null = ja, sonst die Meldung.
     * $exceptId = das Event, das gerade gespeichert wird (zaehlt nicht doppelt).
     */
    public static function eventLimitError(?int $exceptId = null): ?string
    {
        $limit = self::eventLimit();

        if (self::activeEventCount($exceptId) + 1 <= $limit) {
            return null;
        }

        return sprintf(
            'Die Gratis-Version erlaubt %d aktives Event. Für unbegrenzte Events, Turnierbaum und '
            . 'Gym141-Kopplung gibt es Event141 Pro auf account.devworld-llc.com – den Lizenzschlüssel '
            . 'dann unter Einstellungen eintragen. (Ein Event auf „Beendet“ zu stellen schafft ebenfalls Platz.)',
            $limit
        );
    }

    /** Pro-Funktion (Turnierbaum, Gym141-Kopplung)? null = erlaubt, sonst die Meldung. */
    public static function proFeatureError(string $feature): ?string
    {
        if (self::isPro()) {
            return null;
        }

        return $feature . ' gehört zu Event141 Pro. Lizenz auf account.devworld-llc.com, '
            . 'den Schlüssel dann unter Einstellungen eintragen.';
    }

    // --------------------------------------------------------------- Intern --

    /**
     * POST mit JSON-Antwort; null bei Netz-/Serverfehler.
     *
     * @param array<string,mixed> $daten
     * @return array<string,mixed>|null
     */
    private static function request(string $pfad, array $daten): ?array
    {
        $url  = self::apiBase() . $pfad;
        $body = (string) json_encode($daten);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            ]);

            $antwort = curl_exec($ch);
            $status  = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        } else {
            $kontext = stream_context_create(['http' => [
                'method'        => 'POST',
                'header'        => 'Content-Type: application/json',
                'content'       => $body,
                'timeout'       => 15,
                'ignore_errors' => true,
            ]]);

            $antwort = @file_get_contents($url, false, $kontext);
            $status  = 0;

            foreach ($http_response_header ?? [] as $zeile) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $zeile, $m) === 1) {
                    $status = (int) $m[1];
                }
            }
        }

        if ($antwort === false || $status !== 200) {
            return null;
        }

        $json = json_decode((string) $antwort, true);

        return is_array($json) ? $json : null;
    }
}
