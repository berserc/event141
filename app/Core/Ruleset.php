<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Regelsaetze der Verbaende (WAKO Kickboxen, olympisches Boxen, IFMA Muay
 * Thai …): Disziplinen, Altersklassen, Gewichtsklassen und Kampfzeiten als
 * Vorlage fuer die Kategorien eines Turniers.
 *
 * Die Daten liegen als PHP-Dateien unter app/Rulesets/<code>.php und kommen
 * mit jedem Update mit. Sie sind eine Arbeitshilfe – massgeblich ist immer das
 * aktuelle Regelwerk des Verbands; erzeugte Kategorien lassen sich danach frei
 * anpassen.
 *
 * Aufbau einer Datei:
 *   code, name, org, sport, version, source, age_rule, note,
 *   disciplines => [ key => [
 *       name, short, area (ring|tatami), mode (ko|liste), equipment,
 *       divisions => [ [
 *           key, class (offizieller englischer Name, steht im Kategorienamen), de (deutscher Name, nur Anzeige), age => [von, bis],
 *           rounds, minutes, break (Minuten), note,
 *           weights => [ 'm' => [57, 63, '+63'], 'w' => [...] ]   // Obergrenzen in kg, '+X' = offene Klasse
 *           min     => [ 'm' => 44, 'w' => 42 ]                   // optionale Untergrenze der leichtesten Klasse
 *           styles  => [ 'Hard Styles', ... ]                     // statt weights: Kategorien ohne Gewicht (Formen)
 *           genders => [ 'm', 'w' ]                               // nur zusammen mit styles
 *       ] ],
 *   ] ]
 */
final class Ruleset
{
    /** @var array<string,array<string,mixed>>|null */
    private static ?array $cache = null;

    /** @return array<string,array<string,mixed>> code => Regelsatz */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [];

        foreach (glob(dirname(__DIR__) . '/Rulesets/*.php') ?: [] as $file) {
            $data = require $file;

            if (is_array($data) && isset($data['code'], $data['disciplines'])) {
                self::$cache[(string) $data['code']] = $data;
            }
        }

        uasort(self::$cache, static fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));

        return self::$cache;
    }

    /** @return array<string,mixed>|null */
    public static function find(string $code): ?array
    {
        return self::all()[$code] ?? null;
    }

    /** Altersklassen eines Regelsatzes in der Reihenfolge ihres Auftretens: key => Anzeige. */
    public static function classes(array $ruleset): array
    {
        $out = [];

        foreach ($ruleset['disciplines'] as $disc) {
            foreach ($disc['divisions'] as $div) {
                $out[(string) $div['key']] ??= self::classLabel($div);
            }
        }

        return $out;
    }

    public static function classLabel(array $div): string
    {
        [$von, $bis] = $div['age'] + [null, null];
        $alter       = $von !== null && $bis !== null ? ' (' . $von . '–' . $bis . ')' : ($von !== null ? ' (' . t('ab %d', $von) . ')' : ($bis !== null ? ' (' . t('bis %d', $bis) . ')' : ''));

        return (string) $div['class'] . $alter;
    }

    /** "-63 kg", "+94 kg", "63,5 kg" */
    public static function weightLabel(int|float|string $limit): string
    {
        if (is_string($limit) && str_starts_with($limit, '+')) {
            return '+' . self::num((float) substr($limit, 1)) . ' kg';
        }

        return '-' . self::num((float) $limit) . ' kg';
    }

    /**
     * Kategorien (Zeilen fuer event_categories) aus einem Regelsatz erzeugen.
     * Leere Filter bedeuten "alle".
     *
     * @param list<string> $disciplines Schluessel der Disziplinen
     * @param list<string> $classes     Schluessel der Altersklassen
     * @param list<string> $genders     'm' / 'w'
     * @return list<array<string,mixed>> zusaetzlich mit _disc, _class, _gender, _area, _weight
     */
    public static function expand(array $ruleset, array $disciplines = [], array $classes = [], array $genders = []): array
    {
        $rows   = [];

        foreach ($ruleset['disciplines'] as $dKey => $disc) {
            if ($disciplines !== [] && !in_array((string) $dKey, $disciplines, true)) {
                continue;
            }

            foreach ($disc['divisions'] as $div) {
                if ($classes !== [] && !in_array((string) $div['key'], $classes, true)) {
                    continue;
                }

                // Kategorienamen international: offizieller Klassenname + Male/Female
                $label = ['m' => 'Male', 'w' => 'Female'];
                $basis = [
                    'discipline'    => (string) $disc['name'],
                    'age_min'       => $div['age'][0] ?? null,
                    'age_max'       => $div['age'][1] ?? null,
                    'rounds'        => (int) ($div['rounds'] ?? 3),
                    'round_minutes' => (float) ($div['minutes'] ?? 2),
                    'mode'          => (string) ($disc['mode'] ?? 'ko'),
                    'note'          => trim((string) $ruleset['name'] . ' · ' . (string) $div['class']),
                    '_disc'         => (string) $dKey,
                    '_class'        => (string) $div['key'],
                    '_area'         => (string) ($disc['area'] ?? 'ring'),
                ];
                $kopf  = (string) $disc['short'] . ' ' . (string) $div['class'];

                // Formen & Co.: Kategorien ohne Gewicht
                if (isset($div['styles'])) {
                    foreach ((array) ($div['genders'] ?? ['m', 'w']) as $g) {
                        if ($genders !== [] && !in_array($g, $genders, true)) {
                            continue;
                        }

                        foreach ((array) $div['styles'] as $style) {
                            $rows[] = $basis + [
                                'name'       => $kopf . ' ' . $label[$g] . ' – ' . $style,
                                'gender'     => $g,
                                'weight_min' => null,
                                'weight_max' => null,
                                '_gender'    => $g,
                                '_weight'    => (string) $style,
                            ];
                        }
                    }

                    continue;
                }

                foreach ((array) ($div['weights'] ?? []) as $g => $limits) {
                    if ($genders !== [] && !in_array($g, $genders, true)) {
                        continue;
                    }

                    $unten = isset($div['min'][$g]) ? (float) $div['min'][$g] : null;

                    foreach ((array) $limits as $limit) {
                        $offen = is_string($limit) && str_starts_with($limit, '+');
                        $wert  = $offen ? (float) substr($limit, 1) : (float) $limit;

                        $rows[] = $basis + [
                            'name'       => $kopf . ' ' . $label[$g] . ' ' . self::weightLabel($limit),
                            'gender'     => (string) $g,
                            'weight_min' => $offen ? $wert : $unten,
                            'weight_max' => $offen ? null : $wert,
                            '_gender'    => (string) $g,
                            '_weight'    => self::weightLabel($limit),
                        ];

                        $unten = $wert;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Kategorien in ein Event schreiben; gleichnamige bleiben unangetastet.
     *
     * @param list<array<string,mixed>> $rows aus expand()
     * @return array{created:int,skipped:int,ids:array<int,array<string,mixed>>} ids: Kategorie-Id => Zeile
     */
    public static function apply(int $eventId, array $rows): array
    {
        $vorhanden = [];

        foreach (Database::all('SELECT id, name FROM event_categories WHERE event_id = ?', [$eventId]) as $c) {
            $vorhanden[(string) $c['name']] = (int) $c['id'];
        }

        $sort    = (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) FROM event_categories WHERE event_id = ?', [$eventId]);
        $created = 0;
        $skipped = 0;
        $ids     = [];

        Database::transaction(static function () use ($eventId, $rows, &$vorhanden, &$sort, &$created, &$skipped, &$ids): void {
            foreach ($rows as $row) {
                $name = (string) $row['name'];

                if (isset($vorhanden[$name])) {
                    $ids[$vorhanden[$name]] = $row;
                    $skipped++;

                    continue;
                }

                $sort += 10;
                $id    = Database::insert('event_categories', [
                    'event_id'      => $eventId,
                    'name'          => $name,
                    'discipline'    => $row['discipline'],
                    'gender'        => $row['gender'],
                    'age_min'       => $row['age_min'],
                    'age_max'       => $row['age_max'],
                    'weight_min'    => $row['weight_min'],
                    'weight_max'    => $row['weight_max'],
                    'rounds'        => $row['rounds'],
                    'round_minutes' => $row['round_minutes'],
                    'mode'          => $row['mode'],
                    'note'          => $row['note'],
                    'sort_order'    => $sort,
                ]);

                $vorhanden[$name] = $id;
                $ids[$id]         = $row;
                $created++;
            }
        });

        return ['created' => $created, 'skipped' => $skipped, 'ids' => $ids];
    }

    private static function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',');
    }
}
