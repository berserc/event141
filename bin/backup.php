<?php

/**
 * Sicherung der SQLite-Datenbank nach data/backups/.
 *
 *   php bin/backup.php            (behält die letzten 14 Sicherungen)
 *   php bin/backup.php --keep=30
 */

declare(strict_types=1);

use App\Core\Config;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Nur über die Kommandozeile.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$options = getopt('', ['keep::']);
$keep    = max(1, (int) ($options['keep'] ?? 14));
$dbPath  = (string) Config::get('db_path');

if (!is_file($dbPath)) {
    fwrite(STDERR, "Keine Datenbank unter $dbPath.\n");
    exit(1);
}

$dir = dirname($dbPath) . '/backups';

if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    fwrite(STDERR, "Sicherungsverzeichnis konnte nicht angelegt werden.\n");
    exit(1);
}

$target = $dir . '/' . pathinfo($dbPath, PATHINFO_FILENAME) . '-' . date('Ymd-His') . '.sqlite';

// Konsistente Kopie ueber die SQLite-Backup-API (WAL-sicher).
$src = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
$dst = new SQLite3($target);
$src->backup($dst);
$dst->close();
$src->close();

echo "Sicherung: $target\n";

$files = glob($dir . '/*.sqlite') ?: [];
rsort($files);

foreach (array_slice($files, $keep) as $old) {
    @unlink($old);
    echo 'Entfernt: ' . basename($old) . "\n";
}
