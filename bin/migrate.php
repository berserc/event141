<?php

/**
 * Schema-Aktualisierung ohne Benutzeranlage (nach Updates).
 *
 *   php bin/migrate.php
 *   EVENT141_ENV=dev php bin/migrate.php
 */

declare(strict_types=1);

use App\Core\Config;
use App\Core\Installer;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Nur über die Kommandozeile.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$dbPath = (string) Config::get('db_path');

if (!is_file($dbPath)) {
    fwrite(STDERR, "Keine Datenbank unter $dbPath – bitte zuerst bin/install.php ausführen.\n");
    exit(1);
}

echo "Event141 – Migration (" . Config::get('env') . ")\n";

try {
    foreach ((new Installer())->run('', '', false) as $line) {
        echo '  ' . $line . "\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'FEHLER: ' . $e->getMessage() . "\n");
    exit(1);
}
