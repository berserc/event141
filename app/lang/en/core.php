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

    // Layouts: Kopfzeilen, Navigation, Fusszeile
    'Direkt zum Inhalt' => 'Skip to content',
    'Testumgebung – Änderungen hier wirken sich <strong>nicht</strong> auf die Produktivseite aus.' => 'Test environment – changes made here do <strong>not</strong> affect the live site.',
    'Testumgebung – hier gearbeitete Änderungen erscheinen <strong>nicht</strong> auf der Produktivseite.' => 'Test environment – changes made here do <strong>not</strong> appear on the live site.',
    'Testumgebung'          => 'Test environment',
    'Hauptnavigation'       => 'Main navigation',
    'Events'                => 'Events',
    'Gym-Login'             => 'Gym login',
    'Rechtliches'           => 'Legal',
    'Gym registrieren'      => 'Register a gym',
    'Verwaltung'            => 'Admin',
    'Menü'                  => 'Menu',
    'Menü ein-/ausblenden'  => 'Show/hide menu',
    'Website ansehen'       => 'View website',
    'Abmelden'              => 'Log out',
    'Anmelden'              => 'Log in',
    'Verwaltungsnavigation' => 'Admin navigation',
    'Neue, unbestätigte Gyms' => 'New, unconfirmed gyms',
    'Gym-Bereich'           => 'Gym area',
    'Gym-Navigation'        => 'Gym navigation',
    'Website'               => 'Website',
    'Verwaltungsansicht: Sie sind als Gym „%s“ unterwegs.' => 'Admin view: you are acting as gym “%s”.',
    'Zurück zur Verwaltung' => 'Back to admin',
    'Drucken / als PDF speichern' => 'Print / save as PDF',

    // Navigation der Verwaltung
    'Übersicht'        => 'Overview',
    'Regelsätze'       => 'Rule sets',
    'Teilnehmer'       => 'Participants',
    'Gyms & Vereine'   => 'Gyms & clubs',
    'Sportler'         => 'Athletes',
    'Medien (Bilder)'  => 'Media (images)',
    'System'           => 'System',
    'Seiten'           => 'Pages',
    'Benutzer'         => 'Users',
    'API & Kopplungen' => 'API & integrations',
    'Einstellungen'    => 'Settings',
    'Updates'          => 'Updates',
    'Protokoll'        => 'Audit log',
    'Gym-Daten'        => 'Gym details',
    'Gym141'           => 'Gym141',

    // Skripte (window.I18N)
    'Bitte zuerst mindestens einen Eintrag auswählen.' => 'Please select at least one entry first.',
    '„%s“ für %d Eintrag/Einträge ausführen?'          => 'Run “%s” for %d entry/entries?',
    'noch nichts ausgewählt'                           => 'nothing selected yet',
    'Bilder hierher ziehen'                            => 'Drop images here',
    'Bild hierher ziehen'                              => 'Drop an image here',
    'oder klicken zum Auswählen'                       => 'or click to choose',
    'Absatz=p; Überschrift 2=h2; Überschrift 3=h3; Überschrift 4=h4' => 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
    'Schließen'                                        => 'Close',
    'Zurück'                                           => 'Back',
    'Weiter'                                           => 'Next',

    // Gleiches deutsches Wort, andere Bedeutung (Marke hinter ##)
    'Sportler##einzahl'      => 'Athlete',
    'Anmelden##sportler'     => 'Enter',
    'Sieger##mehrzahl'       => 'Winners',
    'Unentschieden##bilanz'  => 'Draws',
    'Benutzer##einzahl'      => 'User',

    // Sprache, Fehlerseiten, Unternavigation
    'Sprache der Oberfläche' => 'Interface language',
    'Vorgabe für Website, Gym-Bereich und Verwaltung. Jeder Besucher kann oben rechts selbst zwischen DE und EN umschalten.'
        => 'Default for the website, gym area and admin. Every visitor can switch between DE and EN at the top right.',
    'Unterseiten'              => 'Sub-pages',
    'Einrichtung erforderlich' => 'Setup required',
    'Fehler'                   => 'Error',
    'Rote Ecke'                => 'Red corner',
    'Blaue Ecke'               => 'Blue corner',
    'ab %d'  => 'from %d',
    'bis %d' => 'up to %d',
];
