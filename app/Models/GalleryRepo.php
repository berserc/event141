<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Galerien je Event (galleries + gallery_images) sowie Bildzuordnungen fuer
 * Kaempfe und den Event-Bericht (event_media: kind 'bout' | 'report').
 * Alle Bilder kommen aus der Bildbibliothek (library_images).
 */
final class GalleryRepo
{
    /** @return list<array<string,mixed>> */
    public static function forEvent(int $eventId, bool $publishedOnly = false): array
    {
        return Database::all(
            'SELECT g.*, (SELECT COUNT(*) FROM gallery_images gi WHERE gi.gallery_id = g.id) AS image_count,'
            . ' (SELECT thumb FROM library_images li WHERE li.id = COALESCE(g.cover_image_id,'
            . ' (SELECT image_id FROM gallery_images gi2 WHERE gi2.gallery_id = g.id ORDER BY gi2.sort_order, gi2.image_id LIMIT 1))) AS cover_thumb'
            . ' FROM galleries g WHERE g.event_id = ?' . ($publishedOnly ? ' AND g.published = 1' : '')
            . ' ORDER BY g.sort_order, g.id DESC',
            [$eventId]
        );
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id, ?int $eventId = null): ?array
    {
        return Database::one(
            'SELECT * FROM galleries WHERE id = ?' . ($eventId !== null ? ' AND event_id = ?' : ''),
            $eventId !== null ? [$id, $eventId] : [$id]
        );
    }

    /** @return array<string,mixed>|null */
    public static function findBySlug(int $eventId, string $slug, bool $publishedOnly = true): ?array
    {
        return Database::one(
            'SELECT * FROM galleries WHERE event_id = ? AND slug = ?' . ($publishedOnly ? ' AND published = 1' : ''),
            [$eventId, $slug]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function images(int $galleryId): array
    {
        $ids = array_map(
            static fn (array $r): int => (int) $r['image_id'],
            Database::all('SELECT image_id FROM gallery_images WHERE gallery_id = ? ORDER BY sort_order, image_id', [$galleryId])
        );

        return ImageRepo::byIds($ids);
    }

    /** @return list<int> */
    public static function imageIds(int $galleryId): array
    {
        return array_map(static fn (array $i): int => (int) $i['id'], self::images($galleryId));
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');

        return Database::insert('galleries', $data);
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('galleries', $id, $data);
    }

    /** @param list<int> $imageIds */
    public static function setImages(int $galleryId, array $imageIds): void
    {
        Database::transaction(static function () use ($galleryId, $imageIds): void {
            Database::run('DELETE FROM gallery_images WHERE gallery_id = ?', [$galleryId]);
            $pos = 0;
            foreach (array_values(array_unique(array_filter(array_map('intval', $imageIds)))) as $imageId) {
                Database::run('INSERT OR IGNORE INTO gallery_images (gallery_id, image_id, sort_order) VALUES (?, ?, ?)', [$galleryId, $imageId, $pos++]);
            }
        });
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM galleries WHERE id = ?', [$id]);
    }

    public static function uniqueSlug(int $eventId, string $wunsch, ?int $ignoreId = null): string
    {
        $base = slugify($wunsch) ?: 'galerie';
        $slug = $base;
        $n    = 2;
        while (Database::value('SELECT id FROM galleries WHERE event_id = ? AND slug = ? AND id <> ?', [$eventId, $slug, (int) $ignoreId]) !== null) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    /* ---------- Bilder zu Kaempfen / zum Bericht (event_media) ---------- */

    /** @return list<array<string,mixed>> */
    public static function boutImages(int $boutId): array
    {
        return ImageRepo::byIds(self::mediaIds('bout', $boutId));
    }

    /** @return list<array<string,mixed>> */
    public static function reportImages(int $eventId): array
    {
        return ImageRepo::byIds(self::mediaIds('report', $eventId));
    }

    /** @return list<int> */
    public static function mediaIds(string $kind, int $refId): array
    {
        return array_map(
            static fn (array $r): int => (int) $r['image_id'],
            Database::all('SELECT image_id FROM event_media WHERE kind = ? AND ref_id = ? ORDER BY sort_order, image_id', [$kind, $refId])
        );
    }

    /** @param list<int> $imageIds */
    public static function setMedia(string $kind, int $refId, int $eventId, array $imageIds): void
    {
        Database::transaction(static function () use ($kind, $refId, $eventId, $imageIds): void {
            Database::run('DELETE FROM event_media WHERE kind = ? AND ref_id = ?', [$kind, $refId]);
            $pos = 0;
            foreach (array_values(array_unique(array_filter(array_map('intval', $imageIds)))) as $imageId) {
                Database::run(
                    'INSERT OR IGNORE INTO event_media (event_id, kind, ref_id, image_id, sort_order) VALUES (?, ?, ?, ?, ?)',
                    [$eventId, $kind, $refId, $imageId, $pos++]
                );
            }
        });
    }

    /** Bildanzahl je Kampf eines Events (fuer Listen/Export). @return array<int,int> */
    public static function boutImageCounts(int $eventId): array
    {
        $out = [];
        foreach (Database::all("SELECT ref_id, COUNT(*) AS n FROM event_media WHERE event_id = ? AND kind = 'bout' GROUP BY ref_id", [$eventId]) as $r) {
            $out[(int) $r['ref_id']] = (int) $r['n'];
        }

        return $out;
    }

    /** Hat das Event sichtbare Galerien oder einen veroeffentlichten Bericht? (fuer die Event-Navigation) */
    public static function hasGalleries(int $eventId): bool
    {
        try {
            return (int) Database::value('SELECT COUNT(*) FROM galleries WHERE event_id = ? AND published = 1', [$eventId]) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
