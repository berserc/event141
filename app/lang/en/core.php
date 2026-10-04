<?php

/**
 * Englisch – Grundwortschatz: Beschriftungen aus Konstanten (Status, Rollen,
 * Geschlecht …), gespeicherte Standardtexte (Rundennamen, Siegarten,
 * Abschnitte) und die Layouts. Die Bereiche (Website, Gym-Bereich,
 * Verwaltung) haben eigene Dateien im selben Ordner.
 */

return [
    // Event-Arten und -Status
    'Turnier'               => 'Tournament',
    'Gala / Fight Night'    => 'Gala / fight night',
    'Entwurf'               => 'Draft',
    'Anmeldung offen'       => 'Registration open',
    'Anmeldung geschlossen' => 'Registration closed',
    'Läuft gerade'          => 'In progress',
    'Beendet'               => 'Finished',

    // Geschlecht
    'alle'      => 'all',
    'männlich'  => 'male',
    'weiblich'  => 'female',
    'divers'    => 'diverse',
    'unbekannt' => 'unknown',

    // Kampf- und Anmeldestatus
    'geplant'          => 'scheduled',
    'läuft'            => 'live',
    'beendet'          => 'finished',
    'abgesagt'         => 'cancelled',
    'angemeldet'       => 'registered',
    'bestätigt'        => 'confirmed',
    'abgelehnt'        => 'rejected',
    'abgemeldet'       => 'withdrawn',
    'neu (unbestätigt)' => 'new (unconfirmed)',
    'gesperrt'         => 'blocked',

    // Sieger
    'Rot'           => 'Red',
    'Blau'          => 'Blue',
    'Unentschieden' => 'Draw',
    'kein Sieger'   => 'no winner',

    // Rollen und API
    'Admin (Superuser)' => 'Admin (superuser)',
    'Organisation'      => 'Organiser',
    'Ringleitung'       => 'Ring official',
    'Lesen'             => 'Read',
    'Lesen + Schreiben' => 'Read + write',

    // Turnierbaum
    'Finale'        => 'Final',
    'Halbfinale'    => 'Semi-final',
    'Viertelfinale' => 'Quarter-final',
    'Achtelfinale'  => 'Round of 16',
    'Freilos'       => 'Bye',

    // Siegarten (Standardliste)
    'Punkte'           => 'Points',
    'KO'               => 'KO',
    'TKO'              => 'TKO',
    'Aufgabe'          => 'Retirement',
    'Disqualifikation' => 'Disqualification',
    'Verletzung'       => 'Injury',
    'Walkover'         => 'Walkover',

    // Aufbau (Standardbeschriftungen)
    'Vormittag'   => 'Morning',
    'Nachmittag'  => 'Afternoon',
    'Abend'       => 'Evening',
    'Finals'      => 'Finals',
    'Pause'       => 'Break',
    'Fight Night' => 'Fight night',
];
