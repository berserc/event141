<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class GymRepo
{
    public const STATUS = [
        'neu'        => 'neu (unbestätigt)',
        'bestaetigt' => 'bestätigt',
        'gesperrt'   => 'gesperrt',
    ];

    /** @return list<array<string,mixed>> */
    public static function all(string $search = '', string $status = ''): array
    {
        $where  = ['g.deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(g.name LIKE ? OR g.city LIKE ? OR g.contact_name LIKE ? OR g.email LIKE ?)';
            $like     = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }

        if ($status !== '' && isset(self::STATUS[$status])) {
            $where[]  = 'g.status = ?';
            $params[] = $status;
        }

        return Database::all(
            'SELECT g.*,
                    (SELECT COUNT(*) FROM athletes a WHERE a.gym_id = g.id AND a.active = 1) AS athlete_count,
                    (SELECT COUNT(*) FROM event_entries x WHERE x.gym_id = g.id AND x.status IN (\'angemeldet\', \'bestaetigt\')) AS entry_count
               FROM gyms g
              WHERE ' . implode(' AND ', $where) . '
              ORDER BY g.name COLLATE NOCASE',
            $params
        );
    }

    /** @return list<array<string,mixed>> Kurze Liste fuer Auswahlboxen. */
    public static function options(): array
    {
        return Database::all(
            'SELECT id, name, city FROM gyms WHERE deleted_at IS NULL ORDER BY name COLLATE NOCASE'
        );
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM gyms WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug !== '' ? $slug : 'gym';
        $try  = $base;
        $n    = 2;

        while (Database::one(
            'SELECT id FROM gyms WHERE slug = ? AND (? IS NULL OR id <> ?)',
            [$try, $ignoreId, $ignoreId]
        ) !== null) {
            $try = $base . '-' . $n++;
        }

        return $try;
    }

    public static function loginEmailTaken(string $email, ?int $ignoreId = null): bool
    {
        return $email !== '' && Database::one(
            "SELECT id FROM gyms WHERE login_email = ? COLLATE NOCASE AND deleted_at IS NULL AND (? IS NULL OR id <> ?)",
            [$email, $ignoreId, $ignoreId]
        ) !== null;
    }

    public static function pendingCount(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM gyms WHERE status = 'neu' AND deleted_at IS NULL");
    }
}
