<?php

/**
 * Importiert eine Fightcard im NAFN-Format (fights.json) als Gala-Event.
 *
 *   php bin/import-nafn.php --from=https://nafn.at                 (Website, inkl. Medien)
 *   php bin/import-nafn.php --from=../nafn-website                 (lokaler Ordner)
 *   php bin/import-nafn.php --from=../nafn-website --no-media
 *   php bin/import-nafn.php --from=https://nafn.at --replace       (früheren Import auffrischen)
 *   EVENT141_ENV=dev php bin/import-nafn.php --from=…              (Testumgebung)
 */

declare(strict_types=1);

use App\Core\Database;
use App\Core\Fightcard;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Nur über die Kommandozeile.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$opt  = getopt('', ['from:', 'no-media', 'replace']);
$from = rtrim((string) ($opt['from'] ?? ''), '/\\');

if ($from === '') {
    exit("Aufruf: php bin/import-nafn.php --from=<URL oder Ordner> [--no-media]\n");
}

$read = static function (string $rel) use ($from): string {
    $src = $from . '/' . $rel;

    return (string) (preg_match('#^https?://#i', $from) ? @file_get_contents($src) : (is_file($src) ? file_get_contents($src) : ''));
};

$data = json_decode($read('data/fights.json'), true);

if (!is_array($data)) {
    fwrite(STDERR, "Keine gültige data/fights.json unter $from\n");
    exit(1);
}

$pool = json_decode($read('data/fighters.json'), true);

Database::pdo();

try {
    $r = Fightcard::import($data, is_array($pool) ? $pool : [], array_key_exists('no-media', $opt) ? '' : $from, null, array_key_exists('replace', $opt));
} catch (Throwable $e) {
    fwrite(STDERR, 'FEHLER: ' . $e->getMessage() . "\n");
    exit(1);
}

printf(
    "Importiert: Event #%d – %d Kämpfe, %d neue Sportler, %d neue Gyms, %d Mediendateien.\n",
    $r['event_id'],
    $r['bouts'],
    $r['athletes'],
    $r['gyms'],
    $r['media']
);
echo "Das Event ist unveröffentlicht – in der Verwaltung prüfen und veröffentlichen.\n";
