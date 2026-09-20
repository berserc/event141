<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** API-Schluessel der Plattform ("ek_..."), gespeichert nur als SHA-256-Hash. */
final class ApiKeyRepo
{
    public const SCOPES = [
        'read'  => 'Lesen',
        'write' => 'Lesen + Schreiben',
    ];

    /** @return list<array<string,mixed>> */
    public static function all(?int $gymId = null): array
    {
        $where  = $gymId === null ? '1 = 1' : 'k.gym_id = ?';
        $params = $gymId === null ? [] : [$gymId];

        return Database::all(
            "SELECT k.*, g.name AS gym_name, e.name AS event_name
               FROM api_keys k
               LEFT JOIN gyms g ON g.id = k.gym_id
               LEFT JOIN events e ON e.id = k.event_id
              WHERE $where
              ORDER BY k.active DESC, k.created_at DESC",
            $params
        );
    }

    /**
     * Legt einen Schluessel an und liefert ihn EINMALIG im Klartext.
     *
     * @return array{id:int,key:string}
     */
    public static function create(string $name, string $scope, ?int $gymId, ?int $eventId, ?int $userId): array
    {
        $key = 'ek_' . bin2hex(random_bytes(24));

        $id = Database::insert('api_keys', [
            'name'       => mb_substr($name, 0, 120),
            'key_prefix' => substr($key, 0, 11),
            'key_hash'   => hash('sha256', $key),
            'scope'      => isset(self::SCOPES[$scope]) ? $scope : 'read',
            'gym_id'     => $gymId,
            'event_id'   => $eventId,
            'created_by' => $userId,
        ]);

        return ['id' => $id, 'key' => $key];
    }

    /** @return array<string,mixed>|null aktiver Schluessel zum Klartext-Key */
    public static function resolve(string $key): ?array
    {
        if (!str_starts_with($key, 'ek_') || strlen($key) < 20) {
            return null;
        }

        $row = Database::one('SELECT * FROM api_keys WHERE key_hash = ? AND active = 1', [hash('sha256', $key)]);

        if ($row !== null) {
            Database::run('UPDATE api_keys SET last_used_at = ? WHERE id = ?', [gmdate('Y-m-d H:i:s'), (int) $row['id']]);
        }

        return $row;
    }
}
