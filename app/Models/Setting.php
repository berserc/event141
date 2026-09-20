<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Key/Value-Einstellungen (Veranstalterdaten, Betriebsmodus …). */
final class Setting
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    /** @return array<string,string> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $values = [];
        foreach (Database::all('SELECT key, value FROM settings') as $row) {
            $values[(string) $row['key']] = (string) $row['value'];
        }

        return self::$cache = $values;
    }

    public static function get(string $key, string $default = ''): string
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        Database::run(
            'INSERT INTO settings (key, value) VALUES (?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value',
            [$key, $value]
        );

        self::$cache = null;
    }

    /** @param array<string,string> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }
    }
}
