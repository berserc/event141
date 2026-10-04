<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Anmeldungen (Sportler je Event und Kategorie). */
final class EntryRepo
{
    public const STATUS = [
        'angemeldet' => 'angemeldet',
        'bestaetigt' => 'bestätigt',
        'abgelehnt'  => 'abgelehnt',
        'abgemeldet' => 'abgemeldet',
    ];

    private const SELECT = 'SELECT x.*,
                a.first_name, a.last_name, a.nickname, a.birthdate, a.gender, a.nationality,
                a.weight AS athlete_weight, a.record_wins, a.record_losses, a.record_draws, a.photo_path,
                g.name AS gym_name, g.short_name AS gym_short, g.city AS gym_city,
                c.name AS category_name
           FROM event_entries x
           JOIN athletes a ON a.id = x.athlete_id
           JOIN gyms g ON g.id = x.gym_id
           LEFT JOIN event_categories c ON c.id = x.category_id';

    /**
     * @param array{status?:string,category?:int,gym?:int,q?:string} $filter
     * @return list<array<string,mixed>>
     */
    public static function forEvent(int $eventId, array $filter = []): array
    {
        $where  = ['x.event_id = ?'];
        $params = [$eventId];

        if (($filter['status'] ?? '') !== '' && isset(self::STATUS[$filter['status']])) {
            $where[]  = 'x.status = ?';
            $params[] = $filter['status'];
        }

        if (($filter['category'] ?? 0) > 0) {
            $where[]  = 'x.category_id = ?';
            $params[] = (int) $filter['category'];
        }

        if (($filter['gym'] ?? 0) > 0) {
            $where[]  = 'x.gym_id = ?';
            $params[] = (int) $filter['gym'];
        }

        if (($filter['q'] ?? '') !== '') {
            $where[] = "(fold(a.first_name) LIKE ? OR fold(a.last_name) LIKE ? OR fold(a.first_name || ' ' || a.last_name) LIKE ? OR fold(g.name) LIKE ?)";
            $like    = '%' . fold_text((string) $filter['q']) . '%';
            array_push($params, $like, $like, $like, $like);
        }

        return Database::all(
            self::SELECT . ' WHERE ' . implode(' AND ', $where) . '
              ORDER BY c.sort_order, c.id, a.last_name COLLATE NOCASE, a.first_name COLLATE NOCASE',
            $params
        );
    }

    /** @return list<array<string,mixed>> */
    public static function forGymEvent(int $gymId, int $eventId): array
    {
        return Database::all(
            self::SELECT . ' WHERE x.gym_id = ? AND x.event_id = ?
              ORDER BY a.last_name COLLATE NOCASE, a.first_name COLLATE NOCASE',
            [$gymId, $eventId]
        );
    }

    /** @return list<array<string,mixed>> Alle Anmeldungen eines Gyms ueber alle Events. */
    public static function forGym(int $gymId): array
    {
        return Database::all(
            'SELECT x.*, a.first_name, a.last_name, a.nickname, g.name AS gym_name,
                    c.name AS category_name, e.name AS event_name, e.slug AS event_slug,
                    e.starts_on, e.type AS event_type
               FROM event_entries x
               JOIN athletes a ON a.id = x.athlete_id
               JOIN gyms g ON g.id = x.gym_id
               JOIN events e ON e.id = x.event_id
               LEFT JOIN event_categories c ON c.id = x.category_id
              WHERE x.gym_id = ?
              ORDER BY e.starts_on DESC, a.last_name COLLATE NOCASE',
            [$gymId]
        );
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one(self::SELECT . ' WHERE x.id = ?', [$id]);
    }

    /**
     * Bestaetigte Teilnehmer einer Kategorie – gesetzte zuerst (seed 1, 2, …),
     * danach ungesetzt in zufaelliger Reihenfolge.
     *
     * @return list<array<string,mixed>>
     */
    public static function confirmedForCategory(int $categoryId): array
    {
        $rows = Database::all(
            self::SELECT . " WHERE x.category_id = ? AND x.status = 'bestaetigt'",
            [$categoryId]
        );

        $seeded   = array_values(array_filter($rows, static fn (array $r): bool => (int) $r['seed'] > 0));
        $unseeded = array_values(array_filter($rows, static fn (array $r): bool => (int) $r['seed'] <= 0));

        usort($seeded, static fn (array $a, array $b): int => (int) $a['seed'] <=> (int) $b['seed']);
        shuffle($unseeded);

        return array_merge($seeded, $unseeded);
    }

    /** Anzeigename "Vorname Zuname (Gym)" fuer Auswahllisten. */
    public static function label(array $entry, bool $withGym = true): string
    {
        $name = trim((string) $entry['first_name'] . ' ' . (string) $entry['last_name']);

        if ((string) ($entry['nickname'] ?? '') !== '') {
            $name = t('%s „%s“', $name, (string) $entry['nickname']);
        }

        return $withGym ? $name . ' (' . ($entry['gym_short'] ?: $entry['gym_name']) . ')' : $name;
    }
}
