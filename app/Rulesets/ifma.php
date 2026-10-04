<?php

/**
 * IFMA Muaythai – Altersklassen U8 bis U18, U24, Elite und Masters.
 *
 * Quelle: IFMA Rules & Regulations for International Competition, Version 3.0
 * (revidiert 11.05.2026, veroeffentlicht 13.05.2026): Rule 4 (Gewichte),
 * Rule 5 (Alter), Rule 7 (Kampfzeiten), Rule 15 (Schutzausruestung),
 * Rule 31.3 (eingeschraenkte Techniken), Rule 35 (Cultural).
 * v3.0 ersetzt die frueheren Klassen 10-11 / 12-13 / 14-15 / 16-17 / U23 / Senior.
 */

$m14 = [45, 48, 51, 54, 57, 60, 63.5, 67, 71, 75, 81, 86, 91, '+91'];
$w11 = [45, 48, 51, 54, 57, 60, 63.5, 67, 71, 75, '+75'];

$kultur = static fn (): array => array_map(
    static fn (array $k): array => ['key' => $k[0], 'class' => $k[1], 'de' => $k[1], 'age' => [$k[2], $k[3]],
        'rounds' => 1, 'minutes' => 5, 'break' => 0, 'styles' => ['Wai Kru'], 'genders' => ['m', 'w']],
    [['u8', 'U8', 6, 7], ['u10', 'U10', 8, 9], ['u12', 'U12', 10, 11], ['u14', 'U14', 12, 13], ['u16', 'U16', 14, 15],
        ['u18', 'U18', 16, 17], ['u24', 'U24', 18, 23], ['elite', 'Elite', 18, 40]]
);

return [
    'code'     => 'ifma',
    'name'     => 'IFMA Muay Thai',
    'org'      => 'International Federation of Muaythai Associations (IFMA)',
    'sport'    => 'Muay Thai',
    'version'  => 'IFMA Rules & Regulations, Version 3.0 (revidiert 11.05.2026)',
    'source'   => 'https://muaythai.sport/about-muaythai/muaythai-rules/',
    'age_rule' => 'Maßgeblich ist das Geburtsjahr (Stichtag 31.12.): Alter = Wettkampfjahr minus Geburtsjahr. Je Wettkampf nur ein Weg: Elite, U-Klasse oder Masters.',
    'note'     => 'Für alle Klassen Pflicht: 10-oz-Handschuhe, Kopfschutz, Schienbein- und Ellbogenschützer, Zahn- und Tiefschutz. '
        . 'Körperschutz (Weste) in allen U-Klassen, U24 und Masters 40+/45+; Elite und Masters 35+ ohne Weste. '
        . 'U14: keine Ellbogen- und Kniestöße zum Kopf; U12, U10 und U8: keine Treffer zum Kopf. Welche Altersklassen als Semi-Kontakt '
        . '(Muaythai Technical) ausgetragen werden, legt die Ausschreibung fest – international meist U12/U14, bei Schulmeisterschaften U8/U10. '
        . 'Standard ist ein Kampf pro Tag; Auslosung als K.-o.-System. Die Gewichte für U8/U10 weichen in manchen Ausschreibungen ab.',

    'disciplines' => [
        'mt' => [
            'name' => 'Muay Thai', 'short' => 'MT', 'area' => 'ring', 'mode' => 'ko',
            'equipment' => 'Immer 3 Runden, keine Verlängerung. Wiegen: das Klassenlimit darf nicht überschritten werden.',
            'divisions' => [
                ['key' => 'u8', 'class' => 'U8', 'de' => 'U8', 'age' => [6, 7], 'rounds' => 3, 'minutes' => 1, 'break' => 1,
                    'note'    => 'Keine offene Klasse nach oben.',
                    'weights' => ['m' => [16, 18, 20, 22, 24, 26, 28], 'w' => [16, 18, 20, 22, 24, 26]]],
                ['key' => 'u10', 'class' => 'U10', 'de' => 'U10', 'age' => [8, 9], 'rounds' => 3, 'minutes' => 1, 'break' => 1,
                    'note'    => 'Keine offene Klasse nach oben.',
                    'weights' => ['m' => [20, 22, 24, 26, 28, 30, 32, 34, 36], 'w' => [18, 20, 22, 24, 26, 28, 30, 32]]],
                ['key' => 'u12', 'class' => 'U12', 'de' => 'U12', 'age' => [10, 11], 'rounds' => 3, 'minutes' => 1, 'break' => 1,
                    'weights' => ['m' => [30, 32, 34, 36, 38, 40, 42, 44, 46, 48, 50, 52, 54, 56, 58, 60, 63.5, 67, '+67'],
                        'w' => [30, 32, 34, 36, 38, 40, 42, 44, 46, 48, 50, 52, 54, 56, 58, 60, '+60']]],
                ['key' => 'u14', 'class' => 'U14', 'de' => 'U14', 'age' => [12, 13], 'rounds' => 3, 'minutes' => 1.5, 'break' => 1,
                    'weights' => ['m' => [32, 34, 36, 38, 40, 42, 44, 46, 48, 50, 52, 54, 56, 58, 60, 63.5, 67, 71, '+71'],
                        'w' => [32, 34, 36, 38, 40, 42, 44, 46, 48, 50, 52, 54, 56, 58, 60, 63.5, '+63.5']]],
                ['key' => 'u16', 'class' => 'U16', 'de' => 'U16', 'age' => [14, 15], 'rounds' => 3, 'minutes' => 2, 'break' => 1,
                    'weights' => ['m' => [38, 40, 42, 45, 48, 51, 54, 57, 60, 63.5, 67, 71, 75, 81, '+81'],
                        'w' => [36, 38, 40, 42, 45, 48, 51, 54, 57, 60, 63.5, 67, 71, '+71']]],
                ['key' => 'u18', 'class' => 'U18', 'de' => 'U18', 'age' => [16, 17], 'rounds' => 3, 'minutes' => 2, 'break' => 1,
                    'weights' => ['m' => $m14, 'w' => [42, 45, 48, 51, 54, 57, 60, 63.5, 67, 71, 75, '+75']]],
                ['key' => 'u24', 'class' => 'U24', 'de' => 'U24', 'age' => [18, 23], 'rounds' => 3, 'minutes' => 3, 'break' => 1,
                    'weights' => ['m' => $m14, 'w' => $w11]],
                ['key' => 'elite', 'class' => 'Elite', 'de' => 'Elite', 'age' => [18, 40], 'rounds' => 3, 'minutes' => 3, 'break' => 1,
                    'note'    => 'Herren -45 kg nur bei ausgewählten Multisport-Events.',
                    'weights' => ['m' => $m14, 'w' => $w11]],
                ['key' => 'm35', 'class' => 'Masters 35+', 'de' => 'Masters 35+', 'age' => [35, 39], 'rounds' => 3, 'minutes' => 3, 'break' => 1.5,
                    'weights' => ['m' => $m14, 'w' => $w11]],
                ['key' => 'm40', 'class' => 'Masters 40+', 'de' => 'Masters 40+', 'age' => [40, 44], 'rounds' => 3, 'minutes' => 2, 'break' => 1.5,
                    'note'    => 'Keine Klasse +91 kg.',
                    'weights' => ['m' => array_slice($m14, 0, -1), 'w' => $w11]],
                ['key' => 'm45', 'class' => 'Masters 45+', 'de' => 'Masters 45+', 'age' => [45, 50], 'rounds' => 3, 'minutes' => 2, 'break' => 1.5,
                    'note'    => 'Keine Klasse +91 kg.',
                    'weights' => ['m' => array_slice($m14, 0, -1), 'w' => $w11]],
            ],
        ],
        'wk' => [
            'name' => 'Wai Kru', 'short' => 'WK', 'area' => 'ring', 'mode' => 'liste',
            'equipment' => 'Muaythai Cultural (ohne Kontakt): Wai Kru einzeln, Vorführung 4–5 Minuten. Mai Muay (Duo, auch gemischt, 5–6 Minuten) und die Masters-Klassen der Cultural-Wettbewerbe (35+, 40+, 50+, 60+) bei Bedarf von Hand anlegen.',
            'divisions' => $kultur(),
        ],
    ],
];
