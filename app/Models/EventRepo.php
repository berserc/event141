<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Events samt Aufbau (Tage, Abschnitte, Wettkampfstaetten, Kategorien). */
final class EventRepo
{
    public const TYPES = [
        'turnier' => 'Turnier',
        'gala'    => 'Gala / Fight Night',
    ];

    public const STATUS = [
        'entwurf'     => 'Entwurf',
        'anmeldung'   => 'Anmeldung offen',
        'geschlossen' => 'Anmeldung geschlossen',
        'laufend'     => 'Läuft gerade',
        'beendet'     => 'Beendet',
    ];

    public const GENDERS = [
        'alle' => 'alle',
        'm'    => 'männlich',
        'w'    => 'weiblich',
    ];

    // ---------------------------------------------------------------- Events --

    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        return Database::all(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM event_entries x WHERE x.event_id = e.id AND x.status IN (\'angemeldet\', \'bestaetigt\')) AS entry_count,
                    (SELECT COUNT(*) FROM event_bouts b WHERE b.event_id = e.id AND b.is_break = 0) AS bout_count
               FROM events e
              ORDER BY e.starts_on DESC, e.id DESC'
        );
    }

    /** Veroeffentlichte Events fuer die Website: kommende zuerst, dann vergangene. */
    public static function published(): array
    {
        return Database::all(
            "SELECT * FROM events WHERE published = 1
              ORDER BY CASE WHEN COALESCE(ends_on, starts_on) >= date('now') THEN 0 ELSE 1 END,
                       CASE WHEN COALESCE(ends_on, starts_on) >= date('now') THEN starts_on END ASC,
                       starts_on DESC"
        );
    }

    /** @return list<array<string,mixed>> Events mit offener Anmeldung (fuer Gyms). */
    public static function openForRegistration(): array
    {
        $rows = Database::all(
            "SELECT * FROM events WHERE status = 'anmeldung' AND gym_registration = 1
              ORDER BY starts_on"
        );

        return array_values(array_filter($rows, static fn (array $e): bool => self::registrationOpen($e)));
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM events WHERE id = ?', [$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM events WHERE slug = ?', [$slug]);
    }

    public static function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug !== '' ? $slug : 'event';
        $try  = $base;
        $n    = 2;

        while (Database::one(
            'SELECT id FROM events WHERE slug = ? AND (? IS NULL OR id <> ?)',
            [$try, $ignoreId, $ignoreId]
        ) !== null) {
            $try = $base . '-' . $n++;
        }

        return $try;
    }

    /** Ist die Anmeldung fuer Gyms gerade moeglich? */
    public static function registrationOpen(array $event): bool
    {
        if ((string) $event['status'] !== 'anmeldung' || (int) ($event['gym_registration'] ?? 1) !== 1) {
            return false;
        }

        $heute = date('Y-m-d');
        $von   = (string) ($event['registration_from'] ?? '');
        $bis   = (string) ($event['registration_until'] ?? '');

        if ($von !== '' && $heute < $von) {
            return false;
        }

        if ($bis !== '' && $heute > $bis) {
            return false;
        }

        return true;
    }

    /** Kennzahlen fuer Uebersicht und Dashboard. */
    public static function stats(int $eventId): array
    {
        $entries = Database::all(
            'SELECT status, COUNT(*) AS n FROM event_entries WHERE event_id = ? GROUP BY status',
            [$eventId]
        );

        $bouts = Database::all(
            'SELECT status, COUNT(*) AS n FROM event_bouts WHERE event_id = ? AND is_break = 0 GROUP BY status',
            [$eventId]
        );

        $out = [
            'entries'  => array_fill_keys(array_keys(EntryRepo::STATUS), 0),
            'bouts'    => array_fill_keys(array_keys(BoutRepo::STATUS), 0),
            'gyms'     => (int) Database::value(
                "SELECT COUNT(DISTINCT gym_id) FROM event_entries WHERE event_id = ? AND status IN ('angemeldet', 'bestaetigt')",
                [$eventId]
            ),
            'unplaced' => (int) Database::value(
                "SELECT COUNT(*) FROM event_bouts
                  WHERE event_id = ? AND is_break = 0 AND (session_id IS NULL OR venue_id IS NULL)
                    AND status <> 'abgesagt' AND NOT (status = 'beendet' AND method = 'Freilos')",
                [$eventId]
            ),
        ];

        foreach ($entries as $row) {
            $out['entries'][(string) $row['status']] = (int) $row['n'];
        }

        foreach ($bouts as $row) {
            $out['bouts'][(string) $row['status']] = (int) $row['n'];
        }

        $out['entries']['gesamt'] = array_sum($out['entries']);
        $out['bouts']['gesamt']   = array_sum($out['bouts']);

        return $out;
    }

    // ------------------------------------------------------------------ Tage --

    /** @return list<array<string,mixed>> */
    public static function days(int $eventId): array
    {
        return Database::all(
            'SELECT * FROM event_days WHERE event_id = ? ORDER BY day_date, sort_order, id',
            [$eventId]
        );
    }

    /**
     * Abschnitte mit Tagesdatum – chronologisch.
     *
     * @return list<array<string,mixed>>
     */
    public static function sessions(int $eventId): array
    {
        return Database::all(
            'SELECT s.*, d.day_date, d.label AS day_label
               FROM event_sessions s JOIN event_days d ON d.id = s.day_id
              WHERE s.event_id = ?
              ORDER BY d.day_date, d.sort_order, s.sort_order, s.starts_at, s.id',
            [$eventId]
        );
    }

    /**
     * Tage mit verschachtelten Abschnitten (fuer Aufbau-Seite und Zeitplan).
     *
     * @return list<array<string,mixed>>
     */
    public static function structure(int $eventId): array
    {
        $days = self::days($eventId);
        $map  = [];

        foreach ($days as $i => $day) {
            $days[$i]['sessions']  = [];
            $map[(int) $day['id']] = $i;
        }

        foreach (self::sessions($eventId) as $session) {
            $i = $map[(int) $session['day_id']] ?? null;

            if ($i !== null) {
                $days[$i]['sessions'][] = $session;
            }
        }

        return $days;
    }

    /** Legt fuer jeden Tag des Zeitraums einen Eintrag an, falls er fehlt. */
    public static function ensureDays(array $event): int
    {
        $von = (string) $event['starts_on'];
        $bis = (string) ($event['ends_on'] ?: $event['starts_on']);

        if ($von === '' || $bis < $von) {
            return 0;
        }

        $neu   = 0;
        $datum = new \DateTimeImmutable($von);
        $ende  = new \DateTimeImmutable($bis);
        $n     = 1;

        while ($datum <= $ende) {
            $affected = Database::run(
                'INSERT OR IGNORE INTO event_days (event_id, day_date, label, sort_order) VALUES (?, ?, ?, ?)',
                [(int) $event['id'], $datum->format('Y-m-d'), 'Tag ' . $n, $n * 10]
            )->rowCount();

            $neu  += $affected;
            $datum = $datum->modify('+1 day');
            $n++;

            if ($n > 60) {
                break; // Schutz vor Tippfehlern im Datum
            }
        }

        return $neu;
    }

    // ------------------------------------------------ Wettkampfstaetten / Kategorien --

    /** @return list<array<string,mixed>> */
    public static function venues(int $eventId): array
    {
        return Database::all(
            'SELECT * FROM event_venues WHERE event_id = ? ORDER BY sort_order, id',
            [$eventId]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function categories(int $eventId): array
    {
        return Database::all(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM event_entries x WHERE x.category_id = c.id AND x.status IN (\'angemeldet\', \'bestaetigt\')) AS entry_count,
                    (SELECT COUNT(*) FROM event_entries x WHERE x.category_id = c.id AND x.status = \'bestaetigt\') AS confirmed_count,
                    (SELECT COUNT(*) FROM event_bouts b WHERE b.category_id = c.id) AS bout_count
               FROM event_categories c
              WHERE c.event_id = ?
              ORDER BY c.sort_order, c.id',
            [$eventId]
        );
    }

    /** @return list<array<string,mixed>> Hauptsponsoren zuerst. */
    public static function sponsors(int $eventId, bool $publishedOnly = false): array
    {
        return Database::all(
            'SELECT * FROM event_sponsors WHERE event_id = ?' . ($publishedOnly ? ' AND published = 1' : '') . '
              ORDER BY is_main DESC, sort_order, id',
            [$eventId]
        );
    }

    /** @return list<array{label:string,price:string,note:string,highlight:bool}> */
    public static function tickets(array $event): array
    {
        $rows = json_decode((string) ($event['tickets_json'] ?? '[]'), true);
        $out  = [];

        foreach (is_array($rows) ? $rows : [] as $r) {
            if (is_array($r) && trim((string) ($r['label'] ?? '')) !== '') {
                $out[] = [
                    'label'     => (string) $r['label'],
                    'price'     => (string) ($r['price'] ?? ''),
                    'note'      => (string) ($r['note'] ?? ''),
                    'highlight' => !empty($r['highlight']),
                ];
            }
        }

        return $out;
    }

    /** @return array<string,string> Netzwerk => URL */
    public static function social(array $event): array
    {
        $rows = json_decode((string) ($event['social_json'] ?? '{}'), true);

        return array_filter(array_map('strval', is_array($rows) ? $rows : []), static fn (string $v): bool => $v !== '');
    }

    /** Lesbare Kategorie-Beschreibung, z. B. "K1 · männlich · 18–40 J. · bis 75 kg". */
    public static function categoryInfo(array $c): string
    {
        $teile = [];

        if ((string) $c['discipline'] !== '') {
            $teile[] = (string) $c['discipline'];
        }

        if ((string) $c['gender'] !== 'alle') {
            $teile[] = t(self::GENDERS[(string) $c['gender']] ?? (string) $c['gender']);
        }

        if ($c['age_min'] !== null || $c['age_max'] !== null) {
            $teile[] = t(
                '%s–%s J.',
                $c['age_min'] !== null ? (string) (int) $c['age_min'] : '',
                $c['age_max'] !== null ? (string) (int) $c['age_max'] : ''
            );
        }

        if ($c['weight_min'] !== null || $c['weight_max'] !== null) {
            // Dezimaltrennzeichen wie format_weight(): Komma (de) bzw. Punkt (en).
            $komma = lang() === 'en' ? '.' : ',';
            $kg    = static fn ($v): string => rtrim(rtrim(number_format((float) $v, 1, $komma, ''), '0'), $komma);

            if ($c['weight_min'] !== null && $c['weight_max'] !== null) {
                $teile[] = t('>%s bis %s kg', $kg($c['weight_min']), $kg($c['weight_max']));
            } elseif ($c['weight_max'] !== null) {
                $teile[] = t('bis %s kg', $kg($c['weight_max']));
            } else {
                $teile[] = '>' . $kg($c['weight_min']) . ' kg';
            }
        }

        return implode(' · ', $teile);
    }
}
