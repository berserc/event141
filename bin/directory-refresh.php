<?php

/**
 * Zentrales Verzeichnis: alle gemeldeten Events bei ihren Instanzen neu
 * abholen (Datum, Status, Anmeldung offen …) und verschwundene entfernen.
 * Fuer einen Cronjob auf der Verzeichnis-Instanz, z. B. stuendlich:
 *
 *   php bin/directory-refresh.php
 *
 * Auf einer normalen Instanz (ohne 'directory' => true) tut das Skript nichts.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Directory;

if (!Directory::isDirectory()) {
    exit("Diese Installation ist kein Verzeichnis (app/config.php: 'directory' => true).\n");
}

$r = Directory::refreshAll();

printf("Verzeichnis aktualisiert: %d abgeholt, %d entfernt, %d nicht erreichbar.\n", $r['ok'], $r['entfernt'], $r['fehler']);
