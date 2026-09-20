<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AthleteRepo
{
    public const GENDERS = [
        'm'         => 'männlich',
        'w'         => 'weiblich',
        'd'         => 'divers',
        'unbekannt' => 'unbekannt',
    ];

    /** @return list<array<string,mixed>> */
    public static function forGym(int $gymId, bool $activeOnly = false): array
    {
        return Database::all(
            'SELECT a.*,
                    (SELECT COUNT(*) FROM event_entries x WHERE x.athlete_id = a.id AND x.status IN (\'angemeldet\', \'bestaetigt\')) AS entry_count
               FROM athletes a
              WHERE a.gym_id = ?' . ($activeOnly ? ' AND a.active = 1' : '') . '
              ORDER BY a.last_name COLLATE NOCASE, a.first_name COLLATE NOCASE',
            [$gymId]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function search(string $q = '', int $gymId = 0, int $limit = 300): array
    {
        $where  = ['g.deleted_at IS NULL'];
        $params = [];

        if ($gymId > 0) {
            $where[]  = 'a.gym_id = ?';
            $params[] = $gymId;
        }

        if ($q !== '') {
            $where[] = "(a.first_name LIKE ? OR a.last_name LIKE ? OR a.nickname LIKE ? OR (a.first_name || ' ' || a.last_name) LIKE ? OR g.name LIKE ?)";
            $like    = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $params[] = $limit;

        return Database::all(
            'SELECT a.*, g.name AS gym_name, g.city AS gym_city
               FROM athletes a JOIN gyms g ON g.id = a.gym_id
              WHERE ' . implode(' AND ', $where) . '
              ORDER BY a.last_name COLLATE NOCASE, a.first_name COLLATE NOCASE
              LIMIT ?',
            $params
        );
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT a.*, g.name AS gym_name, g.city AS gym_city
               FROM athletes a JOIN gyms g ON g.id = a.gym_id
              WHERE a.id = ?',
            [$id]
        );
    }

    /**
     * Uebernimmt Mitglieder aus Gym141 als Sportler (neu anlegen oder – wenn
     * schon per gym141_member_id bekannt – Stammdaten aktualisieren).
     *
     * @param list<array<string,mixed>> $members Antwort von Gym141Client::members()
     * @param list<int>                 $onlyIds nur diese Gym141-IDs (leer = alle)
     * @return array{created:int,updated:int}
     */
    public static function importFromGym141(int $gymId, array $members, array $onlyIds = []): array
    {
        $created = 0;
        $updated = 0;

        foreach ($members as $m) {
            $mid = (int) ($m['id'] ?? 0);

            if ($mid <= 0 || ($onlyIds !== [] && !in_array($mid, $onlyIds, true))) {
                continue;
            }

            $first = trim((string) ($m['first_name'] ?? ''));
            $last  = trim((string) ($m['last_name'] ?? ''));

            if ($first === '' && $last === '') {
                continue;
            }

            $data = [
                'first_name'       => $first,
                'last_name'        => $last,
                'birthdate'        => ($m['birthdate'] ?? null) ?: null,
                'email'            => mb_substr(trim((string) ($m['email'] ?? '')), 0, 200),
                'phone'            => mb_substr(trim((string) ($m['phone'] ?? '')), 0, 60),
                'gym141_member_no' => (string) ($m['member_no'] ?? ''),
                'updated_at'       => gmdate('Y-m-d H:i:s'),
            ];

            $existing = Database::one(
                'SELECT id FROM athletes WHERE gym_id = ? AND gym141_member_id = ?',
                [$gymId, $mid]
            );

            if ($existing !== null) {
                Database::update('athletes', (int) $existing['id'], $data);
                $updated++;
                continue;
            }

            // Namensgleiche, manuell erfasste Sportler nicht doppeln – verknuepfen.
            $sameName = Database::one(
                'SELECT id FROM athletes WHERE gym_id = ? AND gym141_member_id IS NULL
                    AND first_name = ? COLLATE NOCASE AND last_name = ? COLLATE NOCASE',
                [$gymId, $first, $last]
            );

            if ($sameName !== null) {
                Database::update('athletes', (int) $sameName['id'], $data + ['gym141_member_id' => $mid]);
                $updated++;
                continue;
            }

            unset($data['updated_at']);
            Database::insert('athletes', $data + ['gym_id' => $gymId, 'gym141_member_id' => $mid]);
            $created++;
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /** Kampfbilanz "12-3-1". */
    public static function record(array $a): string
    {
        return (int) $a['record_wins'] . '-' . (int) $a['record_losses'] . '-' . (int) $a['record_draws'];
    }
}
