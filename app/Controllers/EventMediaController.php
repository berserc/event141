<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
use App\Models\GalleryRepo;
use App\Models\ImageRepo;

/**
 * Bilder rund um ein Event: Bericht (Titel, Text, Titelbild, Bilder), Galerien mit
 * eigener Seite (/e/{slug}/galerie/{kuerzel}) und Bilder + Nachwort je Kampf.
 * Alle Bilder kommen aus der Bildbibliothek (/admin/medien).
 */
final class EventMediaController
{
    /** Tab „Galerie & Bericht“ */
    public function index(array $args): void
    {
        AuthController::requireLogin();
        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $id    = (int) $event['id'];

        View::display('admin/events/media', [
            'title'        => $event['name'] . ' – ' . t('Galerie & Bericht'),
            'event'        => $event,
            'galleries'    => GalleryRepo::forEvent($id),
            'reportImages' => GalleryRepo::mediaIds('report', $id),
            'images'       => ImageRepo::search(),
            'imageCount'   => ImageRepo::count(),
            'boutCounts'   => GalleryRepo::boutImageCounts($id),
        ], 'layouts/admin');
    }

    public function saveReport(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();
        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $id    = (int) $event['id'];
        $cover = self::pickedIds('report_cover');

        Database::update('events', $id, [
            'report_title'     => mb_substr(trim(post('report_title')), 0, 200),
            'report_text'      => trim((string) ($_POST['report_text'] ?? '')),
            'report_cover_id'  => $cover[0] ?? null,
            'report_published' => post_bool('report_published'),
        ]);
        GalleryRepo::setMedia('report', $id, $id, self::pickedIds('report_images'));
        Audit::log('report_saved', 'event', $id, 'Bericht gespeichert');
        Flash::success(t('Bericht gespeichert.'));
        Url::redirect('/admin/events/' . $id . '/galerie');
    }

    public function createGallery(array $args): void
    {
        AuthController::requireWrite();
        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $this->galleryForm($event, null);
    }

    public function editGallery(array $args): void
    {
        AuthController::requireLogin();
        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $gallery = GalleryRepo::find((int) ($args['gid'] ?? 0), (int) $event['id']);
        if ($gallery === null) {
            Flash::error(t('Galerie nicht gefunden.'));
            Url::redirect('/admin/events/' . $event['id'] . '/galerie');
        }
        $this->galleryForm($event, $gallery);
    }

    /** @param array<string,mixed> $event @param array<string,mixed>|null $gallery */
    private function galleryForm(array $event, ?array $gallery): void
    {
        $old  = Flash::oldInput();
        $data = $gallery ?? ['id' => 0, 'title' => '', 'slug' => '', 'text' => '', 'published' => 1, 'sort_order' => 0, 'cover_image_id' => null];
        foreach (['title', 'slug', 'text'] as $k) {
            if (isset($old[$k])) {
                $data[$k] = (string) $old[$k];
            }
        }
        View::display('admin/events/gallery-form', [
            'title'    => $event['name'] . ' – ' . ($gallery === null ? t('Neue Galerie') : $gallery['title']),
            'event'    => $event,
            'gallery'  => $data,
            'isNew'    => $gallery === null,
            'errors'   => Flash::errors(),
            'selected' => $gallery === null ? [] : GalleryRepo::imageIds((int) $gallery['id']),
            'images'   => ImageRepo::search(),
        ], 'layouts/admin');
    }

    public function storeGallery(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();
        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $id    = (int) $event['id'];
        [$data, $errors] = $this->validateGallery($id, null);
        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Url::redirect('/admin/events/' . $id . '/galerie/neu');
        }
        $data['event_id'] = $id;
        $gid = GalleryRepo::create($data);
        GalleryRepo::setImages($gid, self::pickedIds('image_ids'));
        Audit::log('gallery_created', 'event', $id, (string) $data['title']);
        Flash::success(t('Galerie angelegt.'));
        Url::redirect('/admin/events/' . $id . '/galerie/' . $gid);
    }

    public function updateGallery(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();
        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $id      = (int) $event['id'];
        $gallery = GalleryRepo::find((int) ($args['gid'] ?? 0), $id);
        if ($gallery === null) {
            Flash::error(t('Galerie nicht gefunden.'));
            Url::redirect('/admin/events/' . $id . '/galerie');
        }
        $gid = (int) $gallery['id'];
        [$data, $errors] = $this->validateGallery($id, $gallery);
        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Url::redirect('/admin/events/' . $id . '/galerie/' . $gid);
        }
        GalleryRepo::update($gid, $data);
        GalleryRepo::setImages($gid, self::pickedIds('image_ids'));
        Audit::log('gallery_updated', 'event', $id, (string) $data['title']);
        Flash::success(t('Galerie gespeichert.'));
        Url::redirect('/admin/events/' . $id . '/galerie/' . $gid);
    }

    public function toggleGallery(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();
        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $gallery = GalleryRepo::find((int) ($args['gid'] ?? 0), (int) $event['id']);
        if ($gallery !== null) {
            GalleryRepo::update((int) $gallery['id'], ['published' => (int) $gallery['published'] === 1 ? 0 : 1]);
            Audit::log('gallery_toggled', 'event', (int) $event['id'], (string) $gallery['title']);
        }
        Url::redirect('/admin/events/' . $event['id'] . '/galerie');
    }

    public function destroyGallery(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();
        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $gallery = GalleryRepo::find((int) ($args['gid'] ?? 0), (int) $event['id']);
        if ($gallery !== null) {
            GalleryRepo::delete((int) $gallery['id']);
            Audit::log('gallery_deleted', 'event', (int) $event['id'], (string) $gallery['title']);
            Flash::success(t('Galerie gelöscht – die Bilder bleiben in der Bildbibliothek.'));
        }
        Url::redirect('/admin/events/' . $event['id'] . '/galerie');
    }

    /** Bilder zu einem Kampf (eigenes Formular auf der Kampfseite) */
    public function saveBoutImages(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();
        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $id    = (int) $event['id'];
        $bid   = (int) ($args['bid'] ?? 0);
        if (Database::value('SELECT id FROM event_bouts WHERE id = ? AND event_id = ?', [$bid, $id]) === null) {
            Flash::error(t('Kampf nicht gefunden.'));
            Url::redirect('/admin/events/' . $id . '/kaempfe');
        }
        GalleryRepo::setMedia('bout', $bid, $id, self::pickedIds('bout_images'));
        Audit::log('bout_images', 'event', $id, 'Kampf #' . $bid . ': Bilder gespeichert');
        Flash::success(t('Bilder zum Kampf gespeichert.'));
        Url::redirect('/admin/events/' . $id . '/kampf/' . $bid . '#bilder');
    }

    /** Ausgewaehlte IDs aus dem Bild-Picker (Reihenfolge aus <feld>_order). @return list<int> */
    public static function pickedIds(string $field): array
    {
        $checked = array_values(array_filter(array_map('intval', (array) ($_POST[$field] ?? []))));
        $order   = array_values(array_filter(array_map('intval', explode(',', (string) ($_POST[$field . '_order'] ?? '')))));
        $out     = array_values(array_filter($order, static fn (int $i): bool => in_array($i, $checked, true)));
        foreach ($checked as $i) {
            if (!in_array($i, $out, true)) {
                $out[] = $i;
            }
        }

        return $out;
    }

    /** @param array<string,mixed>|null $gallery @return array{0: array<string,mixed>, 1: array<string,string>} */
    private function validateGallery(int $eventId, ?array $gallery): array
    {
        $errors = [];
        $title  = trim(post('title'));
        if ($title === '') {
            $errors['title'] = t('Bitte einen Titel eingeben.');
        }
        $cover = post_int('cover_image_id');

        return [[
            'title'          => $title,
            'slug'           => GalleryRepo::uniqueSlug($eventId, trim(post('slug')) !== '' ? post('slug') : $title, $gallery !== null ? (int) $gallery['id'] : null),
            'text'           => trim((string) ($_POST['text'] ?? '')),
            'published'      => post_bool('published'),
            'sort_order'     => post_int('sort_order'),
            'cover_image_id' => $cover > 0 ? $cover : null,
        ], $errors];
    }
}
