<?php

/**
 * WAKO Kickboxen – Tatami (Point Fighting, Light Contact, Kick Light, Formen)
 * und Ring (Full Contact, Low Kick, K1 Style).
 *
 * Quelle: WAKO Rules, Ausgabe 2022 (Titel "v.2 04.05.2022", auf wako.sport als
 * "last revision 25.10.2022" gefuehrt), Kap. 1 Art. 2.1.1.2 (Altersklassen),
 * Kap. 2 Art. 3/4 (Tatami: Gewichte, Kampfzeiten), Kap. 6 (Formen), Kap. 7
 * (Ring) sowie die Regelaenderungen des WAKO IF Board vom 20.03.2026.
 */

$tatamiM = [57, 63, 69, 74, 79, 84, 89, 94, '+94'];
$tatamiW = [50, 55, 60, 65, 70, '+70'];

// Eine gemeinsame Tabelle fuer PF, LC und KL (das Regelwerk kennt keine eigenen Listen je Disziplin).
$oc = ['key' => 'oc', 'class' => 'Older Cadets', 'de' => 'Ältere Kadetten', 'age' => [13, 15], 'rounds' => 2, 'minutes' => 2, 'break' => 1,
    'note'    => 'Bei Cups/Opens auch 2 × 1:30 mit 0:30 Pause.',
    'weights' => ['m' => [32, 37, 42, 47, 52, 57, 63, 69, '+69'], 'w' => [32, 37, 42, 46, 50, 55, 60, 65, '+65']]];
$j  = ['key' => 'j', 'class' => 'Juniors', 'de' => 'Junioren', 'age' => [16, 18], 'rounds' => 3, 'minutes' => 2, 'break' => 1,
    'weights' => ['m' => $tatamiM, 'w' => $tatamiW]];
$s  = ['key' => 's', 'class' => 'Seniors', 'de' => 'Senioren', 'age' => [19, 40], 'rounds' => 3, 'minutes' => 2, 'break' => 1,
    'note'    => 'Bei Cups/Opens auch 2 × 2:00.',
    'weights' => ['m' => $tatamiM, 'w' => $tatamiW]];
$v  = ['key' => 'v', 'class' => 'Veterans', 'de' => 'Masters', 'age' => [41, 55], 'rounds' => 2, 'minutes' => 2, 'break' => 1,
    'note'    => 'Bei Cups/Opens auch 2 × 1:30 mit 0:30 Pause.',
    'weights' => ['m' => [63, 74, 84, 94, '+94'], 'w' => [55, 65, '+65']]];

$ringM = [51, 54, 57, 60, 63.5, 67, 71, 75, 81, 86, 91, '+91'];
$ringW = [48, 52, 56, 60, 65, 70, '+70'];
$ring  = [
    ['key' => 'yj', 'class' => 'Younger Juniors', 'de' => 'Jüngere Junioren', 'age' => [15, 16], 'rounds' => 3, 'minutes' => 2, 'break' => 1,
        'note'    => '2-Niederschlag-Regel.',
        'weights' => ['m' => [42, 45, 48, 51, 54, 57, 60, 63.5, 67, 71, 75, 81, '+81'], 'w' => [36, 40, 44, 48, 52, 56, 60, '+60']]],
    ['key' => 'oj', 'class' => 'Older Juniors', 'de' => 'Ältere Junioren', 'age' => [17, 18], 'rounds' => 3, 'minutes' => 2, 'break' => 1,
        'weights' => ['m' => $ringM, 'w' => $ringW]],
    ['key' => 's', 'class' => 'Seniors', 'de' => 'Senioren', 'age' => [19, 40], 'rounds' => 3, 'minutes' => 2, 'break' => 1,
        'weights' => ['m' => $ringM, 'w' => $ringW]],
];

$formen = static fn (array $styles): array => array_map(
    static fn (array $k): array => $k + ['rounds' => 1, 'minutes' => 3, 'break' => 0, 'styles' => $styles, 'genders' => ['m', 'w']],
    [
        ['key' => 'ch', 'class' => 'Children', 'de' => 'Kinder', 'age' => [7, 9]],
        ['key' => 'yc', 'class' => 'Younger Cadets', 'de' => 'Jüngere Kadetten', 'age' => [10, 12]],
        ['key' => 'oc', 'class' => 'Older Cadets', 'de' => 'Ältere Kadetten', 'age' => [13, 15]],
        ['key' => 'j', 'class' => 'Juniors', 'de' => 'Junioren', 'age' => [16, 18]],
        ['key' => 's', 'class' => 'Seniors', 'de' => 'Senioren', 'age' => [19, 40]],
    ]
);

return [
    'code'     => 'wako',
    'name'     => 'WAKO Kickboxen',
    'org'      => 'World Association of Kickboxing Organizations (WAKO)',
    'sport'    => 'Kickboxen',
    'version'  => 'WAKO Rules, Ausgabe 2022 (letzte Revision 25.10.2022) mit den Regeländerungen vom 20.03.2026',
    'source'   => 'https://www.wako.sport/rules-overview',
    'age_rule' => 'Maßgeblich ist das Geburtsjahr (WAKO Rules Art. 2.1.1.2): Alter = Wettkampfjahr minus Geburtsjahr, nicht der Geburtstag. Einzige Ausnahme: Ältere Junioren dürfen im Ring erst dann bei den Senioren starten, wenn sie tatsächlich schon 18 Jahre alt sind.',
    'note'     => 'Kinder und Kadetten starten nur in ihrer eigenen Altersklasse. Tatami-Sportler dürfen PF, LC und KL kombinieren; '
        . 'Ring-Sportler starten je Meisterschaft in genau einer Ring-Disziplin und nicht auf der Tatami. Gemeldet werden darf höchstens '
        . 'eine Gewichtsklasse über dem tatsächlichen Gewicht. Veteranen dürfen auf der Tatami bis 45 Jahre bei den Senioren starten. '
        . 'WAKO hat eine überarbeitete Fassung des Regelwerks angekündigt – Tabellen vor einer Meisterschaft mit dem Verband abgleichen.',

    'disciplines' => [
        'pf' => [
            'name' => 'Point Fighting', 'short' => 'PF', 'area' => 'tatami', 'mode' => 'ko',
            'equipment' => 'Unentschieden: 1 Minute Verlängerung ohne Pause, danach Sudden Death. Teamwettbewerb (3 Sportler, offenes Gewicht, je 1 × 2:00) und Grand Champion sind nicht als Kategorien hinterlegt.',
            'divisions' => [
                ['key' => 'ch', 'class' => 'Children', 'de' => 'Kinder', 'age' => [7, 9], 'rounds' => 2, 'minutes' => 1, 'break' => 0.5,
                    'weights' => ['m' => [18, 21, 24, 27, 30, 33, 36, '+36'], 'w' => [18, 21, 24, 27, 30, 33, 36, '+36']]],
                ['key' => 'yc', 'class' => 'Younger Cadets', 'de' => 'Jüngere Kadetten', 'age' => [10, 12], 'rounds' => 2, 'minutes' => 1.5, 'break' => 1,
                    'note'    => 'Bei Cups/Opens auch 2 × 1:00 mit 0:30 Pause.',
                    'weights' => ['m' => [28, 32, 37, 42, 47, '+47'], 'w' => [28, 32, 37, 42, 47, '+47']]],
                $oc, $j, $s, $v,
            ],
        ],
        'lc' => [
            'name' => 'Light Contact', 'short' => 'LC', 'area' => 'tatami', 'mode' => 'ko',
            'equipment' => 'Unentschieden: keine Verlängerung – es gewinnt, wer in der letzten Runde mehr Punkte erzielt hat, sonst Kampfrichterentscheid.',
            'divisions' => [$oc, $j, $s, $v],
        ],
        'kl' => [
            'name' => 'Kick Light', 'short' => 'KL', 'area' => 'tatami', 'mode' => 'ko',
            'equipment' => 'Wie Light Contact, zusätzlich Lowkicks auf den Oberschenkel.',
            'divisions' => [$oc, $j, $s, $v],
        ],
        'mf' => [
            'name' => 'Musical Forms', 'short' => 'MF', 'area' => 'tatami', 'mode' => 'liste',
            'equipment' => 'Formen mit Musik, Dauer 1:00–3:00 inkl. Präsentation, Fläche 10 × 10 m, Wertung 7,0–10,0. Soft Styles sieht das aktuelle Regelwerk nicht mehr vor.',
            'divisions' => $formen(['Hard Styles', 'Hard Styles Weapons']),
        ],
        'cf' => [
            'name' => 'Creative Forms', 'short' => 'CF', 'area' => 'tatami', 'mode' => 'liste',
            'equipment' => 'Formen ohne Musik, Dauer 1:00–3:00. Die Aufteilung nach Altersklassen gibt das Regelwerk nicht exakt vor – hier wie bei Musical Forms angelegt.',
            'divisions' => $formen(['Open Hand', 'Weapons']),
        ],
        'fc' => [
            'name' => 'Full Contact', 'short' => 'FC', 'area' => 'ring', 'mode' => 'ko',
            'equipment' => '3 × 2:00 mit 1:00 Pause in allen Altersklassen. Tägliches Wiegen am Kampftag.',
            'divisions' => $ring,
        ],
        'lk' => [
            'name' => 'Low Kick', 'short' => 'LK', 'area' => 'ring', 'mode' => 'ko',
            'equipment' => '3 × 2:00 mit 1:00 Pause in allen Altersklassen. Tägliches Wiegen am Kampftag.',
            'divisions' => $ring,
        ],
        'k1' => [
            'name' => 'K1 Style', 'short' => 'K1', 'area' => 'ring', 'mode' => 'ko',
            'equipment' => '3 × 2:00 mit 1:00 Pause in allen Altersklassen. Ab 01.01.2027: kein Klammern/Halten mehr, Knietechniken unbegrenzt.',
            'divisions' => $ring,
        ],
    ],
];
