<?php

/**
 * Einrichtung über die Kommandozeile.
 *
 * Wenn nur FTP-Zugang besteht, geht dasselbe im Browser über /setup.php.
 *
 *   php bin/install.php --admin=admin@example.org --password=Geheim123
 *   php bin/install.php                                (Zufallspasswort für "admin")
 *   php bin/install.php --force                        (Datenbank neu aufbauen)
 *   php bin/install.php --no-frontend                  (nur Verwaltung, ohne Website)
 *   EVENT141_ENV=dev php bin/install.php               (Testumgebung einrichten)
 */

declare(strict_types=1);

use App\Core\Config;
use App\Core\Installer;
use App\Models\UserRepo;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Dieses Skript darf nur über die Kommandozeile laufen. Im Browser: /setup.php\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$options   = getopt('', ['admin::', 'force', 'password::', 'no-frontend']);
$explicit  = is_string($options['admin'] ?? null) && $options['admin'] !== '';
$adminName = $explicit ? (string) $options['admin'] : 'admin';
$force     = array_key_exists('force', $options);

$generated = !is_string($options['password'] ?? null) || $options['password'] === '';
$password  = $generated ? UserRepo::generatePassword(16) : (string) $options['password'];

echo "Event141 – Einrichtung\n";
echo "=======================\n";
echo 'Umgebung: ' . Config::get('env') . ' (' . Config::get('db_path') . ")\n\n";

$requirements = Installer::requirements();

foreach ($requirements['checks'] as $check) {
    printf("  [%s] %s%s\n", $check['ok'] ? 'OK' : '!!', $check['name'], $check['ok'] ? '' : ' – ' . $check['hint']);
}

echo "\n";

if (!$requirements['ok']) {
    fwrite(STDERR, "Abbruch: Voraussetzungen nicht erfüllt.\n");
    exit(1);
}

$dbPath = (string) Config::get('db_path');

if (is_file($dbPath) && !$force) {
    echo "Hinweis: Es gibt bereits eine Datenbank. Sie bleibt erhalten;\n";
    echo "         fehlende Tabellen werden ergänzt. Für einen Neuaufbau: --force\n\n";
}

$adminUebersprungen = false;

try {
    $installer = new Installer();

    if (!$explicit && $generated && is_file($dbPath)) {
        try {
            $vorhanden = App\Core\Database::one(
                'SELECT id FROM users WHERE username = ? COLLATE NOCASE',
                [$adminName]
            );
        } catch (Throwable) {
            $vorhanden = null;
        }

        if ($vorhanden !== null) {
            $adminName          = '';
            $adminUebersprungen = true;
        }
    }

    foreach ($installer->run($adminName, $password, $force) as $line) {
        echo '  ' . $line . "\n";
    }

    if (array_key_exists('no-frontend', $options)) {
        \App\Models\Setting::set('public_site', '0');
        echo "  Betriebsmodus: Nur Verwaltung (öffentliche Website deaktiviert).\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "\nFEHLER: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\n";
echo "+------------------------------------------------------------+\n";
echo "| Zugangsdaten                                               |\n";
echo "+------------------------------------------------------------+\n";

if ($adminUebersprungen) {
    echo "  Bestehender Superuser unverändert (Passwort setzen: --admin=... --password=...).\n";
} else {
    echo '  Benutzername: ' . $adminName . "\n";
    echo '  Passwort:     ' . ($generated ? $password . '   <- jetzt notieren' : '(wie angegeben)') . "\n";
}

echo "\nVerwaltung: https://<Domain>/admin\n";
