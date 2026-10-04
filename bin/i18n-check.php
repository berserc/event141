<?php

/**
 * Entwicklerwerkzeug: prueft, ob alle t('…')-Texte eine Uebersetzung haben.
 *
 *   php bin/i18n-check.php                 # ganzes Projekt, Sprache en
 *   php bin/i18n-check.php app/Views/gym   # nur diese Dateien/Ordner
 *   php bin/i18n-check.php --lang=en --unused
 *
 * Meldet fehlende Schluessel je Datei (Exit-Code 1) und mit --unused auch
 * Woerterbuch-Eintraege, die nirgends mehr verwendet werden.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile.\n");
}

$root   = dirname(__DIR__);
$lang   = 'en';
$unused = false;
$ziele  = [];

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--lang=')) {
        $lang = substr($arg, 7);
    } elseif ($arg === '--unused') {
        $unused = true;
    } else {
        $ziele[] = $arg;
    }
}

if ($ziele === []) {
    $ziele = ['app', 'public/index.php'];
}

$dict = [];

foreach (glob($root . '/app/lang/' . $lang . '/*.php') ?: [] as $file) {
    $teil = require $file;

    if (is_array($teil)) {
        $dict += $teil;
    }
}

$dateien = [];

foreach ($ziele as $ziel) {
    $pfad = is_file($ziel) || is_dir($ziel) ? $ziel : $root . '/' . $ziel;

    if (is_file($pfad)) {
        $dateien[] = $pfad;
    } elseif (is_dir($pfad)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pfad, FilesystemIterator::SKIP_DOTS));

        foreach ($it as $f) {
            if ($f->isFile() && $f->getExtension() === 'php' && !str_contains(str_replace('\\', '/', $f->getPathname()), '/lang/')) {
                $dateien[] = $f->getPathname();
            }
        }
    }
}

$benutzt = [];
$fehlt   = 0;

foreach ($dateien as $datei) {
    $tokens = token_get_all((string) file_get_contents($datei));
    $n      = count($tokens);

    for ($i = 0; $i < $n; $i++) {
        $tok = $tokens[$i];

        if (!is_array($tok) || $tok[0] !== T_STRING || $tok[1] !== 't') {
            continue;
        }

        // t( '…'  – nur Aufrufe der globalen Funktion mit String-Literal als erstem Argument
        $vor = $tokens[$i - 1] ?? null;

        if (is_array($vor) && in_array($vor[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true)) {
            continue;
        }

        $j = $i + 1;

        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }

        if (($tokens[$j] ?? null) !== '(') {
            continue;
        }

        $j++;

        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }

        $arg = $tokens[$j] ?? null;

        if (!is_array($arg) || $arg[0] !== T_CONSTANT_ENCAPSED_STRING) {
            continue;   // t($variable) – Beschriftung aus Konstante/Datenbank
        }

        $text           = eval('return ' . $arg[1] . ';');
        $benutzt[$text] = true;

        if (!isset($dict[$text])) {
            $rel = str_replace('\\', '/', substr($datei, strlen($root) + 1));
            printf("FEHLT  %s:%d  %s\n", $rel, $arg[2], $text);
            $fehlt++;
        }
    }
}

printf("%d Dateien, %d verschiedene Texte, %d ohne Übersetzung (%s).\n", count($dateien), count($benutzt), $fehlt, $lang);

if ($unused) {
    foreach (array_diff_key($dict, $benutzt) as $text => $_) {
        echo 'UNBENUTZT  ', $text, "\n";
    }
}

exit($fehlt > 0 ? 1 : 0);
