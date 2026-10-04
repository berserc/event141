<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Filter fuer die Kategorien/Turnierbaeume eines Turniers: Disziplin,
 * Altersklasse, Geschlecht und freie Suche (Kategorie, Sportler, Gym).
 * Gilt fuer den Turnierplan der Website, die Kaempfe in der Verwaltung und
 * den Druck der Turnierbaeume – die Filter stehen in der Adresse
 * (?disziplin=…&alter=13-15&geschlecht=w&suche=…).
 */
final class CategoryFilter
{
    /** @return array{disziplin:string,alter:string,geschlecht:string,suche:string} */
    public static function fromQuery(): array
    {
        $geschlecht = query('geschlecht');

        return [
            'disziplin'  => mb_substr(trim(query('disziplin')), 0, 80),
            'alter'      => preg_match('/^\d*-\d*$/', query('alter')) === 1 ? query('alter') : '',
            'geschlecht' => in_array($geschlecht, ['m', 'w'], true) ? $geschlecht : '',
            'suche'      => mb_substr(trim(query('suche')), 0, 80),
        ];
    }

    public static function isActive(array $filter): bool
    {
        return array_filter($filter, static fn ($v): bool => (string) $v !== '') !== [];
    }

    /** Nur die gesetzten Filter – fuer Links (Druck der Auswahl). */
    public static function query(array $filter): array
    {
        return array_filter($filter, static fn ($v): bool => (string) $v !== '');
    }

    /**
     * Auswahlmoeglichkeiten aus den vorhandenen Kategorien.
     *
     * @param list<array<string,mixed>> $categories
     * @return array{disciplines:list<string>,ages:array<string,string>,genders:list<string>}
     */
    public static function options(array $categories, ?array $event = null): array
    {
        $jahr = $event !== null && \App\Models\EventRepo::ageMode($event) === 'jahrgang' ? \App\Models\EventRepo::year($event) : 0;

        $disziplinen = [];
        $alter       = [];
        $klassen     = [];
        $geschlecht  = [];

        foreach ($categories as $c) {
            if ((string) $c['discipline'] !== '') {
                $disziplinen[(string) $c['discipline']] = true;
            }

            if (in_array((string) $c['gender'], ['m', 'w'], true)) {
                $geschlecht[(string) $c['gender']] = true;
            }

            if ($c['age_min'] === null && $c['age_max'] === null) {
                continue;
            }

            $key         = self::ageKey($c);
            $alter[$key] = [$c['age_min'] !== null ? (int) $c['age_min'] : -1, $c['age_max'] !== null ? (int) $c['age_max'] : 999];

            // Klassenname aus dem Hinweis eines Regelsatzes ("WAKO Kickboxen · Older Cadets")
            $pos = mb_strrpos((string) $c['note'], ' · ');
            $klassen[$key][$pos !== false ? mb_substr((string) $c['note'], $pos + 3) : ''] = true;
        }

        uasort($alter, static fn (array $a, array $b): int => $a <=> $b);
        $labels = [];

        foreach ($alter as $key => [$von, $bis]) {
            $spanne = $von >= 0 && $bis < 999 ? $von . '–' . $bis : ($von >= 0 ? t('ab %d', $von) : t('bis %d', $bis));
            $namen  = array_keys($klassen[$key] ?? []);

            // Nur wenn alle Kategorien dieser Altersspanne dieselbe Klasse nennen, den Namen zeigen.
            // Jahrgaenge dazu, wenn das Event nach Geburtsjahr rechnet
            $jg = $jahr > 0 ? birth_years($von >= 0 ? $von : null, $bis < 999 ? $bis : null, $jahr) : '';

            if ($jg !== '') {
                $spanne .= ' · ' . $jg;
            }

            $labels[$key] = count($namen) === 1 && $namen[0] !== '' ? $namen[0] . ' (' . $spanne . ')' : $spanne;
        }

        return ['disciplines' => array_keys($disziplinen), 'ages' => $labels, 'genders' => array_keys($geschlecht)];
    }

    /**
     * Kategorien nach dem Filter auswaehlen.
     *
     * @param list<array<string,mixed>> $categories
     * @return list<array<string,mixed>>
     */
    public static function apply(int $eventId, array $categories, array $filter): array
    {
        if (!self::isActive($filter)) {
            return $categories;
        }

        $treffer = null;   // Kategorie-Ids, in denen Sportler/Gym zur Suche passen

        if ($filter['suche'] !== '') {
            $like    = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], fold_text($filter['suche'])) . '%';
            $treffer = array_flip(array_map('intval', array_column(Database::all(
                "SELECT DISTINCT e.category_id FROM event_entries e
                   JOIN athletes a ON a.id = e.athlete_id
                   JOIN gyms g ON g.id = e.gym_id
                  WHERE e.event_id = ? AND e.category_id IS NOT NULL AND e.status IN ('angemeldet', 'bestaetigt')
                    AND (fold(a.first_name || ' ' || a.last_name) LIKE ? ESCAPE '\\' OR fold(a.last_name || ' ' || a.first_name) LIKE ? ESCAPE '\\'
                         OR fold(a.nickname) LIKE ? ESCAPE '\\' OR fold(g.name) LIKE ? ESCAPE '\\' OR fold(g.short_name) LIKE ? ESCAPE '\\')",
                [$eventId, $like, $like, $like, $like, $like]
            ), 'category_id')));
        }

        return array_values(array_filter($categories, static function (array $c) use ($filter, $treffer): bool {
            if ($filter['disziplin'] !== '' && (string) $c['discipline'] !== $filter['disziplin']) {
                return false;
            }

            if ($filter['geschlecht'] !== '' && (string) $c['gender'] !== $filter['geschlecht']) {
                return false;
            }

            if ($filter['alter'] !== '' && self::ageKey($c) !== $filter['alter']) {
                return false;
            }

            if ($filter['suche'] !== '') {
                return str_contains(fold_text((string) $c['name']), fold_text($filter['suche'])) || isset($treffer[(int) $c['id']]);
            }

            return true;
        }));
    }

    private static function ageKey(array $c): string
    {
        return ($c['age_min'] !== null ? (int) $c['age_min'] : '') . '-' . ($c['age_max'] !== null ? (int) $c['age_max'] : '');
    }
}
