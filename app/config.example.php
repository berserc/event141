<?php

/**
 * Konfiguration.
 *
 * Diese Datei nach app/config.php kopieren und anpassen.
 * app/config.php wird nicht versioniert (siehe .gitignore).
 *
 * Die Anwendung läuft unter mehreren Hostnamen gleichzeitig:
 *
 *   example.org       -> Produktivbetrieb  (data/event141.sqlite)
 *   dev.example.org   -> Testumgebung      (data/event141-dev.sqlite)
 *   localhost         -> lokale Entwicklung
 */

$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

if (str_contains($host, ':')) {
    $host = (string) strstr($host, ':', true);
}

$isLocal = $host === '' || $host === 'localhost' || $host === '127.0.0.1'
    || str_ends_with($host, '.local') || str_ends_with($host, '.test');

$isDev = $isLocal || str_starts_with($host, 'dev.') || str_starts_with($host, 'test.');

// Auf der Kommandozeile (Installer, Backup, Cronjob) entscheidet die
// Umgebungsvariable EVENT141_ENV; ohne Angabe wird der Produktivbetrieb bedient.
if (PHP_SAPI === 'cli') {
    $cliEnv  = strtolower((string) (getenv('EVENT141_ENV') ?: 'live'));
    $isDev   = in_array($cliEnv, ['dev', 'test', 'local'], true);
    $isLocal = $cliEnv === 'local';
}

return [
    // 'dev' oder 'live' – steuert Datenbank, Fehlerausgabe und Suchmaschinen
    'env' => $isDev ? 'dev' : 'live',

    // Anzeigename in Titel und Kopfzeile
    'app_name' => 'Event141',

    // Basis-Pfad, falls die Anwendung NICHT direkt im Docroot liegt.
    // Docroot zeigt auf public/  -> ''
    // Anwendung liegt in /event141/  -> '/event141'
    'base_path' => '',

    // Getrennte Datenbanken, damit Tests niemals Echtdaten berühren.
    'db_path' => dirname(__DIR__) . '/data/' . ($isDev ? 'event141-dev.sqlite' : 'event141.sqlite'),

    // Uploads (Poster, Logos, Sportlerfotos)
    'upload_dir' => dirname(__DIR__) . '/public/uploads',
    'upload_url' => '/uploads',

    'timezone' => 'Europe/Vienna',

    // Detaillierte Fehlermeldungen nur in der Testumgebung.
    'debug' => $isDev,

    // Testumgebung aus Suchmaschinen heraushalten (robots.txt + Meta-Tag).
    'noindex' => $isDev,

    // Deutlich sichtbarer Hinweis, dass man nicht auf der Echtseite ist.
    'show_env_banner' => $isDev,

    // Eigener Sitzungsname je Umgebung.
    'session_name' => $isDev ? 'event141_dev_sess' : 'event141_sess',

    // Kanonische Adresse (nur Hostname) für Sitemap und robots.txt.
    // Leer lassen = wird automatisch aus der aufgerufenen Adresse übernommen.
    'canonical_host' => '',

    // Nach wie vielen Fehlversuchen pro IP der Login für 15 Minuten sperrt.
    'login_max_attempts' => 10,

    // Ländervorwahl für klickbare Telefonnummern ohne Vorwahl.
    'country_code' => '43',

    // Einmal-Schlüssel für den Web-Installer unter /setup.php.
    // Nach der Einrichtung auf '' setzen, dann ist der Installer gesperrt.
    'setup_key' => '',

    // Optional: DevWorld-Lizenzschlüssel fest hinterlegen (verwaltete Installationen).
    // 'devworld_license_key' => 'DW-XXXX-XXXX-XXXX-XXXX',

    // In der Testumgebung gilt ohne Schlüssel alles als freigeschaltet;
    // false = Gratis-Sperren auch lokal testen.
    // 'license_dev_pro' => false,

    // Zeitlimit (Sekunden) für Anfragen an eine Gym141-/Ticket141-Instanz.
    'gym141_timeout' => 15,

    // KI-Bildassistent (Claude API): Normalerweise trägt der Veranstalter
    // seinen eigenen Anthropic-Schlüssel in den Einstellungen ein.
    // Alternativ kann der BETREIBER einer verwalteten Instanz hier einen
    // Schlüssel hinterlegen ('' = aus) – damit ruft Event141 immer
    // 'ai_shared_model' und zählt höchstens 'ai_quota_monthly'
    // Auswertungen je Monat.
    // 'ai_shared_key'    => '',
    // 'ai_shared_model'  => 'claude-haiku-4-5',
    // 'ai_quota_monthly' => 200,

    // Zeitlimit (Sekunden) für KI-Auswertungen.
    // 'ai_timeout' => 180,

    // CA-Bundle für HTTPS-Aufrufe, falls PHP keinen CA-Store hat (Windows).
    // 'ca_bundle' => 'C:/Program Files/Git/mingw64/etc/ssl/certs/ca-bundle.crt',
];
