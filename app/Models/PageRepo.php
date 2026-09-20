<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PageRepo
{
    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        return Database::all('SELECT * FROM pages ORDER BY sort_order, title');
    }

    /** @return list<array<string,mixed>> */
    public static function footerPages(): array
    {
        return Database::all(
            'SELECT id, slug, title FROM pages WHERE in_footer = 1 AND published = 1 ORDER BY sort_order, title'
        );
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM pages WHERE id = ?', [$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM pages WHERE slug = ?', [$slug]);
    }

    public static function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug !== '' ? $slug : 'seite';
        $try  = $base;
        $n    = 2;

        while (Database::one(
            'SELECT id FROM pages WHERE slug = ? AND (? IS NULL OR id <> ?)',
            [$try, $ignoreId, $ignoreId]
        ) !== null) {
            $try = $base . '-' . $n++;
        }

        return $try;
    }
}
