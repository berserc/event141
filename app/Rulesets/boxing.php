<?php

/**
 * Olympisches Boxen nach World Boxing – Elite, U19 und U17 laut World Boxing
 * Competition Rules (in Kraft November 2024, Gewichtsklassen ab 01.01.2025).
 * U15 und U13 regelt World Boxing nicht selbst; hinterlegt sind die Klassen
 * der OeBV-Wettkampfbestimmungen 2026 (EUBC-Limits).
 */

$eubc = [34, 35.5, 37, 38.5, 40, 41.5, 43, 44.5, 46, 48, 50, 52, 54, 56, 59, 62, 65, 68, 72, 76, '+76'];
$u17  = [46, 48, 50, 52, 54, 57, 60, 63, 66, 70, 75, 80, '+80'];
$men  = [50, 55, 60, 65, 70, 75, 80, 85, 90, '+90'];
$wom  = [48, 51, 54, 57, 60, 65, 70, 75, 80, '+80'];

return [
    'code'     => 'boxing',
    'name'     => 'Olympisches Boxen (World Boxing)',
    'org'      => 'World Boxing (WB)',
    'sport'    => 'Boxen',
    'version'  => 'World Boxing Competition Rules (November 2024, Gewichtsklassen ab 01.01.2025); U15/U13 nach ÖBV-Wettkampfbestimmungen 2026',
    'source'   => 'https://worldboxing.org/',
    'age_rule' => 'Maßgeblich ist das Geburtsjahr: Alter = Wettkampfjahr minus Geburtsjahr.',
    'note'     => 'Handschuhe: 10 oz für alle Frauen, alle U17/U15/U13 sowie Männer Elite/U19 bis 65 kg; 12 oz für Männer Elite/U19 ab der 70-kg-Klasse. '
        . 'Kopfschutz: Pflicht für Frauen, U19, U17, U15 und U13; Elite-Männer boxen ohne (World Boxing berät über die Wiedereinführung, Abstimmung im November 2026). '
        . 'Pause zwischen den Runden: 1 Minute. U15 und U13 sowie Masters regelt World Boxing nicht selbst – für U15/U13 sind die Klassen des ÖBV (EUBC-Limits) hinterlegt.',

    'disciplines' => [
        'box' => [
            'name' => 'Boxing', 'short' => 'WB', 'area' => 'ring', 'mode' => 'ko',
            'equipment' => 'Die leichteste Klasse hat eine Untergrenze (Männer 47 kg, Frauen 45 kg, U17 44 kg).',
            'divisions' => [
                ['key' => 'u13', 'class' => 'U13', 'de' => 'Schüler U13', 'age' => [11, 12], 'rounds' => 3, 'minutes' => 1.5, 'break' => 1,
                    'note'    => 'ÖBV/EUBC; unter 34 kg weiter in 1,5-kg-Schritten.',
                    'weights' => ['m' => $eubc, 'w' => $eubc]],
                ['key' => 'u15', 'class' => 'U15', 'de' => 'Schüler U15', 'age' => [13, 14], 'rounds' => 3, 'minutes' => 1.5, 'break' => 1,
                    'note'    => 'ÖBV/EUBC; unter 34 kg weiter in 1,5-kg-Schritten.',
                    'weights' => ['m' => $eubc, 'w' => $eubc]],
                ['key' => 'u17', 'class' => 'U17', 'de' => 'Junioren U17', 'age' => [15, 16], 'rounds' => 3, 'minutes' => 2, 'break' => 1,
                    'weights' => ['m' => $u17, 'w' => $u17], 'min' => ['m' => 44, 'w' => 44]],
                ['key' => 'u19', 'class' => 'U19', 'de' => 'Jugend U19', 'age' => [17, 18], 'rounds' => 3, 'minutes' => 3, 'break' => 1,
                    'weights' => ['m' => $men, 'w' => $wom], 'min' => ['m' => 47, 'w' => 45]],
                ['key' => 'elite', 'class' => 'Elite', 'de' => 'Elite', 'age' => [19, 40], 'rounds' => 3, 'minutes' => 3, 'break' => 1,
                    'weights' => ['m' => $men, 'w' => $wom], 'min' => ['m' => 47, 'w' => 45]],
            ],
        ],
    ],
];
