<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\EventRepo;
use App\Models\Setting;

/**
 * Event-Kalender mit Filtern (Sportart, Land, Region, Verband, Art, Zeitraum,
 * Suche) als Liste oder Monatskalender. Dieselbe Darstellung dient der
 * Startseite einer Instanz (alle eigenen Events – der Kalender eines Verbands)
 * und dem zentralen Verzeichnis auf event141.com (Events aller Instanzen).
 *
 * Ein Eintrag ("Item") ist ein Array mit: name, url, type, sport, federation,
 * country, region, city, venue, starts_on, ends_on, status, registration_open,
 * registration_until, org, image, extern (bool: fuehrt auf eine andere Instanz).
 */
final class EventCalendar
{
    /** Laendernamen fuer die Auswahl (ISO-2 => deutscher Name; t() uebersetzt). */
    public const COUNTRIES = [
        'AT' => 'Österreich', 'DE' => 'Deutschland', 'CH' => 'Schweiz', 'IT' => 'Italien', 'SI' => 'Slowenien',
        'HR' => 'Kroatien', 'HU' => 'Ungarn', 'CZ' => 'Tschechien', 'SK' => 'Slowakei', 'PL' => 'Polen',
        'FR' => 'Frankreich', 'ES' => 'Spanien', 'PT' => 'Portugal', 'NL' => 'Niederlande', 'BE' => 'Belgien',
        'LU' => 'Luxemburg', 'LI' => 'Liechtenstein', 'GB' => 'Vereinigtes Königreich', 'IE' => 'Irland',
        'DK' => 'Dänemark', 'SE' => 'Schweden', 'NO' => 'Norwegen', 'FI' => 'Finnland', 'RS' => 'Serbien',
        'BA' => 'Bosnien und Herzegowina', 'ME' => 'Montenegro', 'MK' => 'Nordmazedonien', 'AL' => 'Albanien',
        'GR' => 'Griechenland', 'BG' => 'Bulgarien', 'RO' => 'Rumänien', 'TR' => 'Türkei', 'UA' => 'Ukraine',
        'US' => 'USA', 'TH' => 'Thailand',
    ];

    /** Vorschlaege fuer Sportart und Verband im Event-Formular. */
    public const SPORTS      = ['Kickboxen', 'Boxen', 'Muay Thai', 'MMA', 'Karate', 'Taekwondo', 'Judo', 'Ringen', 'Brazilian Jiu-Jitsu', 'Grappling'];
    public const FEDERATIONS = ['WAKO', 'World Boxing', 'IFMA', 'ÖBFK', 'ÖBV', 'AMTF', 'ISKA', 'WKU', 'WMC'];

    /** @return array<string,string> */
    public static function filterFromQuery(): array
    {
        $zeit    = query('zeit');
        $ansicht = query('ansicht');
        $monat   = query('monat');

        return [
            'sportart' => mb_substr(trim(query('sportart')), 0, 60),
            'land'     => preg_match('/^[A-Za-z]{2}$/', query('land')) === 1 ? strtoupper(query('land')) : '',
            'region'   => mb_substr(trim(query('region')), 0, 60),
            'verband'  => mb_substr(trim(query('verband')), 0, 60),
            'art'      => in_array(query('art'), ['turnier', 'gala'], true) ? query('art') : '',
            'suche'    => mb_substr(trim(query('suche')), 0, 80),
            'zeit'     => in_array($zeit, ['alle', 'vergangen'], true) ? $zeit : 'kommend',
            'ansicht'  => $ansicht === 'kalender' ? 'kalender' : 'liste',
            'monat'    => preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monat) === 1 ? $monat : '',
        ];
    }

    /** Sind inhaltliche Filter gesetzt (ohne Ansicht/Monat/Standard-Zeitraum)? */
    public static function isActive(array $f): bool
    {
        foreach (['sportart', 'land', 'region', 'verband', 'art', 'suche'] as $k) {
            if ((string) ($f[$k] ?? '') !== '') {
                return true;
            }
        }

        return ($f['zeit'] ?? 'kommend') !== 'kommend';
    }

    /** Gesetzte Filter fuer Links; $override ersetzt einzelne Werte. */
    public static function query(array $f, array $override = []): array
    {
        $q = array_merge($f, $override);

        if (($q['zeit'] ?? '') === 'kommend') {
            unset($q['zeit']);
        }

        if (($q['ansicht'] ?? '') === 'liste') {
            unset($q['ansicht'], $q['monat']);
        }

        return array_filter($q, static fn ($v): bool => (string) $v !== '');
    }

    /**
     * Eigene Events als Kalender-Eintraege.
     *
     * @param list<array<string,mixed>> $events
     * @return list<array<string,mixed>>
     */
    public static function fromLocal(array $events): array
    {
        $org = Setting::get('org_name');

        return array_map(static function (array $e) use ($org): array {
            $bild = (string) ($e['poster_path'] ?: $e['hero_path']);

            return [
                'name'               => (string) $e['name'],
                'url'                => url('/e/' . $e['slug']),
                'type'               => (string) $e['type'],
                'sport'              => (string) $e['sport'],
                'federation'         => (string) ($e['federation'] ?? ''),
                'country'            => strtoupper((string) ($e['country'] ?? '')),
                'region'             => (string) ($e['region'] ?? ''),
                'city'               => (string) $e['venue_city'],
                'venue'              => (string) $e['venue_name'],
                'starts_on'          => (string) $e['starts_on'],
                'ends_on'            => (string) ($e['ends_on'] ?: $e['starts_on']),
                'status'             => (string) $e['status'],
                'registration_open'  => EventRepo::registrationOpen($e),
                'registration_until' => (string) ($e['registration_until'] ?? ''),
                'org'                => $org,
                'image'              => $bild !== '' ? upload_url($bild) : '',
                'extern'             => false,
            ];
        }, $events);
    }

    /**
     * Eintraege des zentralen Verzeichnisses als Kalender-Eintraege.
     *
     * @param list<array<string,mixed>> $rows Zeilen aus directory_events
     * @return list<array<string,mixed>>
     */
    public static function fromDirectory(array $rows): array
    {
        return array_map(static fn (array $r): array => [
            'name'               => (string) $r['name'],
            'url'                => (string) $r['url'],
            'type'               => (string) $r['type'],
            'sport'              => (string) $r['sport'],
            'federation'         => (string) $r['federation'],
            'country'            => strtoupper((string) $r['country']),
            'region'             => (string) $r['region'],
            'city'               => (string) $r['city'],
            'venue'              => (string) $r['venue'],
            'starts_on'          => (string) $r['starts_on'],
            'ends_on'            => (string) ($r['ends_on'] ?: $r['starts_on']),
            'status'             => (string) $r['status'],
            'registration_open'  => (int) $r['registration_open'] === 1,
            'registration_until' => (string) $r['registration_until'],
            'org'                => (string) $r['org_name'],
            'image'              => (string) $r['image_url'],
            'extern'             => true,
        ], $rows);
    }

    /**
     * Auswahlmoeglichkeiten der Filter aus den vorhandenen Eintraegen.
     * Schreibweisen werden zusammengefasst ("kickboxen" = "Kickboxen").
     *
     * @param list<array<string,mixed>> $items
     * @return array{sports:array<string,string>,federations:array<string,string>,regions:array<string,string>,countries:array<string,string>}
     */
    public static function options(array $items): array
    {
        $out = ['sports' => [], 'federations' => [], 'regions' => [], 'countries' => []];

        foreach ($items as $i) {
            foreach (['sports' => 'sport', 'federations' => 'federation', 'regions' => 'region'] as $ziel => $feld) {
                $wert = trim((string) $i[$feld]);

                if ($wert !== '') {
                    $out[$ziel][fold_text($wert)] ??= $wert;
                }
            }

            if ((string) $i['country'] !== '') {
                $out['countries'][(string) $i['country']] = self::countryName((string) $i['country']);
            }
        }

        foreach ($out as &$liste) {
            asort($liste, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $out;
    }

    public static function countryName(string $iso): string
    {
        $iso = strtoupper($iso);

        return isset(self::COUNTRIES[$iso]) ? t(self::COUNTRIES[$iso]) : $iso;
    }

    /**
     * Filter anwenden und nach Datum sortieren (kommende aufsteigend,
     * vergangene absteigend).
     *
     * @param list<array<string,mixed>> $items
     * @return list<array<string,mixed>>
     */
    public static function apply(array $items, array $f, bool $mitZeitraum = true): array
    {
        $heute = date('Y-m-d');
        $suche = fold_text((string) ($f['suche'] ?? ''));

        $items = array_values(array_filter($items, static function (array $i) use ($f, $heute, $suche, $mitZeitraum): bool {
            if ($mitZeitraum) {
                $vorbei = (string) $i['ends_on'] < $heute;

                if (($f['zeit'] ?? 'kommend') === 'kommend' && $vorbei) {
                    return false;
                }

                if (($f['zeit'] ?? '') === 'vergangen' && !$vorbei) {
                    return false;
                }
            }

            foreach (['sportart' => 'sport', 'region' => 'region', 'verband' => 'federation'] as $filter => $feld) {
                if ((string) ($f[$filter] ?? '') !== '' && fold_text((string) $i[$feld]) !== fold_text((string) $f[$filter])) {
                    return false;
                }
            }

            if ((string) ($f['land'] ?? '') !== '' && (string) $i['country'] !== (string) $f['land']) {
                return false;
            }

            if ((string) ($f['art'] ?? '') !== '' && (string) $i['type'] !== (string) $f['art']) {
                return false;
            }

            if ($suche !== '') {
                $text = fold_text(implode(' ', [$i['name'], $i['city'], $i['venue'], $i['org'], $i['sport'], $i['federation'], $i['region']]));

                if (!str_contains($text, $suche)) {
                    return false;
                }
            }

            return true;
        }));

        $absteigend = ($f['zeit'] ?? 'kommend') === 'vergangen';
        usort($items, static fn (array $a, array $b): int => $absteigend
            ? [$b['starts_on'], $b['name']] <=> [$a['starts_on'], $a['name']]
            : [$a['starts_on'], $a['name']] <=> [$b['starts_on'], $b['name']]);

        return $items;
    }

    /**
     * Monatsblatt: Wochen (Mo–So) mit den Events je Tag.
     *
     * @param list<array<string,mixed>> $items bereits gefiltert (ohne Zeitraum)
     * @return array{monat:string,titel:string,prev:string,next:string,wochen:list<list<array{datum:string,tag:int,imMonat:bool,heute:bool,items:list<array<string,mixed>>}>>}
     */
    public static function month(array $items, string $monat): array
    {
        if (preg_match('/^\d{4}-\d{2}$/', $monat) !== 1) {
            $monat = date('Y-m');
        }

        $erster = new \DateTimeImmutable($monat . '-01');
        $start  = $erster->modify('-' . ((int) $erster->format('N') - 1) . ' days');
        $letzter = $erster->modify('last day of this month');
        $ende   = $letzter->modify('+' . (7 - (int) $letzter->format('N')) . ' days');
        $heute  = date('Y-m-d');
        $wochen = [];
        $woche  = [];

        for ($tag = $start; $tag <= $ende; $tag = $tag->modify('+1 day')) {
            $datum = $tag->format('Y-m-d');

            $woche[] = [
                'datum'   => $datum,
                'tag'     => (int) $tag->format('j'),
                'imMonat' => $tag->format('Y-m') === $monat,
                'heute'   => $datum === $heute,
                'items'   => array_values(array_filter($items, static fn (array $i): bool => (string) $i['starts_on'] <= $datum && (string) $i['ends_on'] >= $datum)),
            ];

            if (count($woche) === 7) {
                $wochen[] = $woche;
                $woche    = [];
            }
        }

        $monate = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

        return [
            'monat'  => $monat,
            'titel'  => t($monate[(int) $erster->format('n') - 1]) . ' ' . $erster->format('Y'),
            'prev'   => $erster->modify('-1 month')->format('Y-m'),
            'next'   => $erster->modify('+1 month')->format('Y-m'),
            'wochen' => $wochen,
        ];
    }

    /** Monat, in dem der Kalender starten soll: gewaehlter Monat, sonst der des naechsten Events, sonst heute. */
    public static function startMonth(array $items, string $gewaehlt): string
    {
        if ($gewaehlt !== '') {
            return $gewaehlt;
        }

        $heute = date('Y-m-d');

        foreach ($items as $i) {
            if ((string) $i['ends_on'] >= $heute) {
                return substr(max((string) $i['starts_on'], $heute), 0, 7);
            }
        }

        return date('Y-m');
    }
}
