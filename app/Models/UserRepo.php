<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class UserRepo
{
    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        return Database::all('SELECT * FROM users ORDER BY role, username COLLATE NOCASE');
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function usernameTaken(string $username, ?int $ignoreId = null): bool
    {
        return Database::one(
            'SELECT id FROM users WHERE username = ? COLLATE NOCASE AND (? IS NULL OR id <> ?)',
            [$username, $ignoreId, $ignoreId]
        ) !== null;
    }

    public static function activeSuperuserCount(?int $excludingId = null): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM users WHERE active = 1 AND role = 'superuser' AND (? IS NULL OR id <> ?)",
            [$excludingId, $excludingId]
        );
    }

    /** Gut lesbares Startpasswort (ohne leicht verwechselbare Zeichen). */
    public static function generatePassword(int $length = 14): string
    {
        $alphabet = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }
}
