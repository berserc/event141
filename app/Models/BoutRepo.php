<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Kaempfe: Fightcard (Gala) und Turnierbaum (Turnier), Zeitplan, Ergebnisse. */
final class BoutRepo
{
    public const STATUS = [
        'geplant'  => 'geplant',
        'laufend'  => 'läuft',
        'beendet'  => 'beendet',
        'abgesagt' => 'abgesagt',
    ];

    public const WINNER = [
        'red'  => 'Rot',
        'blue' => 'Blau',
        'draw' => 'Unentschieden',
        'none' => 'kein Sieger',
    ];

    private const SELECT = 'SELECT b.*,
                c.name AS category_name,
                s.name AS session_name, s.starts_at AS session_start, s.day_id,
                d.day_date, d.label AS day_label,
                v.name AS venue_name, v.short_name AS venue_short, v.color AS venue_color,
                ra.first_name AS red_first, ra.last_name AS red_last, ra.nickname AS red_nick,
                ra.nationality AS red_nat, ra.photo_path AS red_photo, ra.weight AS red_weight,
                ra.id AS red_athlete_id, ra.bio AS red_bio, ra.video_path AS red_video,
                ra.media_mode AS red_media, ra.photo_sec AS red_photo_sec, ra.age AS red_age,
                ra.birthdate AS red_birth, ra.gender AS red_gender,
                ra.record_wins AS red_w, ra.record_losses AS red_l, ra.record_draws AS red_d,
                rg.name AS red_gym, rg.short_name AS red_gym_short, rg.city AS red_city,
                ba.first_name AS blue_first, ba.last_name AS blue_last, ba.nickname AS blue_nick,
                ba.nationality AS blue_nat, ba.photo_path AS blue_photo, ba.weight AS blue_weight,
                ba.id AS blue_athlete_id, ba.bio AS blue_bio, ba.video_path AS blue_video,
                ba.media_mode AS blue_media, ba.photo_sec AS blue_photo_sec, ba.age AS blue_age,
                ba.birthdate AS blue_birth, ba.gender AS blue_gender,
                ba.record_wins AS blue_w, ba.record_losses AS blue_l, ba.record_draws AS blue_d,
                bg.name AS blue_gym, bg.short_name AS blue_gym_short, bg.city AS blue_city
           FROM event_bouts b
           LEFT JOIN event_categories c ON c.id = b.category_id
           LEFT JOIN event_sessions s ON s.id = b.session_id
           LEFT JOIN event_days d ON d.id = s.day_id
           LEFT JOIN event_venues v ON v.id = b.venue_id
           LEFT JOIN event_entries re ON re.id = b.red_entry_id
           LEFT JOIN athletes ra ON ra.id = re.athlete_id
           LEFT JOIN gyms rg ON rg.id = re.gym_id
           LEFT JOIN event_entries be ON be.id = b.blue_entry_id
           LEFT JOIN athletes ba ON ba.id = be.athlete_id
           LEFT JOIN gyms bg ON bg.id = be.gym_id';

    /** Reihenfolge fuer Fightcard/Zeitplan: Tag, Abschnitt, Ring, Nummer. */
    private const ORDER = ' ORDER BY d.day_date, s.sort_order, s.starts_at, v.sort_order, b.order_no, b.round_no, b.bracket_pos, b.id';

    /** @return list<array<string,mixed>> */
    public static function forEvent(int $eventId): array
    {
        return Database::all(self::SELECT . ' WHERE b.event_id = ?' . self::ORDER, [$eventId]);
    }

    /** @return list<array<string,mixed>> Runden-Reihenfolge fuer den Turnierbaum. */
    public static function forCategory(int $categoryId): array
    {
        return Database::all(
            self::SELECT . ' WHERE b.category_id = ? ORDER BY b.round_no, b.bracket_pos, b.id',
            [$categoryId]
        );
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one(self::SELECT . ' WHERE b.id = ?', [$id]);
    }

    /**
     * Kaempfe nach Tag -> Abschnitt -> Wettkampfstaette gruppiert. Nicht
     * eingeplante Kaempfe landen unter dem Schluessel 'offen'.
     *
     * @return array{days:list<array<string,mixed>>,open:list<array<string,mixed>>}
     */
    public static function schedule(int $eventId): array
    {
        $days   = EventRepo::structure($eventId);
        $venues = EventRepo::venues($eventId);
        $index  = [];

        foreach ($days as $di => $day) {
            foreach ($day['sessions'] as $si => $session) {
                $days[$di]['sessions'][$si]['venues'] = [];

                foreach ($venues as $venue) {
                    $days[$di]['sessions'][$si]['venues'][(int) $venue['id']] = $venue + ['bouts' => []];
                }

                $index[(int) $session['id']] = [$di, $si];
            }
        }

        $open = [];

        foreach (self::forEvent($eventId) as $bout) {
            $sid = (int) ($bout['session_id'] ?? 0);
            $vid = (int) ($bout['venue_id'] ?? 0);

            if (!isset($index[$sid]) || !isset($days[$index[$sid][0]]['sessions'][$index[$sid][1]]['venues'][$vid])) {
                // Freilose und abgesagte Kaempfe brauchen keinen Ring.
                if (!self::needsPlacement($bout)) {
                    continue;
                }

                $open[] = $bout;
                continue;
            }

            [$di, $si] = $index[$sid];
            $days[$di]['sessions'][$si]['venues'][$vid]['bouts'][] = $bout;
        }

        return ['days' => $days, 'open' => $open];
    }

    /** Muss dieser Kampf im Zeitplan einen Platz bekommen? (Freilos/abgesagt: nein) */
    public static function needsPlacement(array $bout): bool
    {
        return (string) $bout['status'] !== 'abgesagt'
            && !((string) $bout['status'] === 'beendet' && (string) $bout['method'] === 'Freilos');
    }

    public static function nextBoutNo(int $eventId): int
    {
        return (int) Database::value(
            'SELECT COALESCE(MAX(bout_no), 0) + 1 FROM event_bouts WHERE event_id = ? AND is_break = 0',
            [$eventId]
        );
    }

    public static function nextOrderNo(int $eventId, ?int $sessionId, ?int $venueId): int
    {
        return (int) Database::value(
            'SELECT COALESCE(MAX(order_no), 0) + 10 FROM event_bouts
              WHERE event_id = ? AND session_id IS ? AND venue_id IS ?',
            [$eventId, $sessionId, $venueId]
        );
    }

    /** Kampfnummern fortlaufend nach Zeitplan-Reihenfolge neu vergeben. */
    public static function renumber(int $eventId): void
    {
        $n = 1;

        foreach (self::forEvent($eventId) as $bout) {
            if ((int) $bout['is_break'] === 1) {
                continue;
            }

            Database::update('event_bouts', (int) $bout['id'], ['bout_no' => $n++]);
        }
    }

    // ------------------------------------------------------------ Ergebnisse --

    /**
     * Ergebnis eintragen und den Sieger in den Folgekampf uebernehmen.
     * Bei Aenderung eines bereits beendeten Kampfs wird der alte Sieger aus
     * dem Folgekampf entfernt (sofern dieser noch nicht beendet ist).
     */
    public static function setResult(int $boutId, string $winner, string $method, string $note, string $round = ''): void
    {
        $bout = self::find($boutId);

        if ($bout === null) {
            return;
        }

        $alterSieger = self::winnerEntryId($bout);

        Database::update('event_bouts', $boutId, [
            'status'      => 'beendet',
            'winner'      => $winner,
            'method'      => mb_substr($method, 0, 60),
            'result_note' => mb_substr($note, 0, 200),
            'result_round' => mb_substr($round, 0, 20),
            'finished_at' => gmdate('Y-m-d H:i:s'),
            'updated_at'  => gmdate('Y-m-d H:i:s'),
        ]);

        $bout['winner'] = $winner;
        $neuerSieger    = self::winnerEntryId($bout);

        if ($alterSieger !== $neuerSieger) {
            self::propagate($bout, $neuerSieger);
        }
    }

    /** Kampf wieder oeffnen: Ergebnis loeschen, Folgekampf-Slot leeren. */
    public static function reopen(int $boutId): void
    {
        $bout = self::find($boutId);

        if ($bout === null) {
            return;
        }

        Database::update('event_bouts', $boutId, [
            'status'      => 'geplant',
            'winner'      => '',
            'method'      => '',
            'result_note' => '',
            'result_round' => '',
            'finished_at' => null,
            'updated_at'  => gmdate('Y-m-d H:i:s'),
        ]);

        self::propagate($bout, null);
    }

    public static function setStatus(int $boutId, string $status): void
    {
        $data = ['status' => $status, 'updated_at' => gmdate('Y-m-d H:i:s')];

        if ($status === 'laufend') {
            $data['started_at'] = gmdate('Y-m-d H:i:s');
        }

        if ($status !== 'beendet') {
            $data['winner']      = '';
            $data['method']      = '';
            $data['result_note'] = '';
            $data['result_round'] = '';
            $data['finished_at'] = null;
        }

        Database::update('event_bouts', $boutId, $data);

        $bout = self::find($boutId);

        if ($bout !== null && $status !== 'beendet') {
            self::propagate($bout, null);
        }
    }

    private static function winnerEntryId(array $bout): ?int
    {
        return match ((string) $bout['winner']) {
            'red'   => $bout['red_entry_id'] !== null ? (int) $bout['red_entry_id'] : null,
            'blue'  => $bout['blue_entry_id'] !== null ? (int) $bout['blue_entry_id'] : null,
            default => null,
        };
    }

    /** Traegt den Sieger (oder NULL) in den Slot des Folgekampfs ein. */
    private static function propagate(array $bout, ?int $entryId): void
    {
        $nextId = $bout['next_bout_id'] !== null ? (int) $bout['next_bout_id'] : 0;
        $slot   = (string) $bout['next_slot'];

        if ($nextId <= 0 || !in_array($slot, ['red', 'blue'], true)) {
            return;
        }

        $next = Database::one('SELECT * FROM event_bouts WHERE id = ?', [$nextId]);

        if ($next === null || (string) $next['status'] === 'beendet') {
            return; // Folgekampf schon entschieden – nicht mehr anfassen
        }

        Database::update('event_bouts', $nextId, [
            $slot . '_entry_id' => $entryId,
            'updated_at'        => gmdate('Y-m-d H:i:s'),
        ]);
    }

    // --------------------------------------------------------------- Anzeige --

    /** Name einer Ecke ('' wenn noch offen). */
    public static function cornerName(array $bout, string $side): string
    {
        $first = (string) ($bout[$side . '_first'] ?? '');
        $last  = (string) ($bout[$side . '_last'] ?? '');

        return trim($first . ' ' . $last);
    }

    /** Runden-Bezeichnung: Finale, Halbfinale, Viertelfinale, Achtelfinale, Runde n. */
    public static function roundLabel(int $roundNo, int $totalRounds): string
    {
        $vomEnde = $totalRounds - $roundNo;

        return match ($vomEnde) {
            0       => 'Finale',
            1       => 'Halbfinale',
            2       => 'Viertelfinale',
            3       => 'Achtelfinale',
            default => 'Runde ' . $roundNo,
        };
    }
}
