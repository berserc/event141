<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Ai;
use App\Core\AiImage;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\ImageTool;
use App\Core\Url;
use App\Core\View;
use App\Models\ImageRepo;
use RuntimeException;

/**
 * Bildbibliothek (/admin/medien): Mehrfach-Upload mit Verkleinerung, Tags, Bildtext,
 * Suche und Sammelaktionen. Die Bilder werden von Kampf-Galerien, Event-Galerien und dem Bericht
 * und (spaeter) weiteren Stellen nur referenziert – einmal hochladen, ueberall waehlen.
 */
final class ImageLibraryController
{
    private const ROLES = ['superuser', 'orga'];

    public function index(): void
    {
        AuthController::requireRole(...self::ROLES);

        $q   = trim(query('q'));
        $tag = trim(query('tag'));

        View::display('admin/medien/index', [
            'title'     => t('Bilder'),
            'images'    => ImageRepo::search($q, $tag),
            'total'     => ImageRepo::count(),
            'tagCounts' => ImageRepo::tagCounts(),
            'q'         => $q,
            'tag'       => $tag,
        ], 'layouts/admin');
    }

    public function upload(): void
    {
        AuthController::requireRole(...self::ROLES);
        Csrf::verify();

        $tags     = ImageRepo::parseTags(post('tags'));
        $caption  = trim(post('caption'));
        $mitKi    = post_bool('ki_beschreiben') === 1 && Ai::configured();
        $files    = $_FILES['files'] ?? null;
        $ok       = 0;
        $kiOk     = 0;
        $errors   = [];

        if (is_array($files) && is_array($files['name'] ?? null)) {
            foreach ($files['name'] as $i => $name) {
                $einzel = [
                    'name'     => (string) $name,
                    'tmp_name' => (string) ($files['tmp_name'][$i] ?? ''),
                    'error'    => (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                    'size'     => (int) ($files['size'][$i] ?? 0),
                ];
                if ($einzel['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                try {
                    $img = ImageTool::ingest($einzel, 'bilder');
                    $id  = ImageRepo::create($img, $tags, $caption, (string) $name, Auth::id());
                    $ok++;

                    // Auf Wunsch beschreibt die KI jedes Bild einzeln; ein
                    // Fehler dabei laesst den Upload selbst unberuehrt.
                    if ($mitKi && ($zeile = ImageRepo::find($id)) !== null) {
                        try {
                            $analyse = AiImage::analyze($zeile);
                            ImageRepo::update(
                                $id,
                                $caption !== '' ? $caption : $analyse['caption'],
                                array_values(array_unique(array_merge($tags, $analyse['tags'])))
                            );
                            $kiOk++;
                        } catch (RuntimeException $e) {
                            $errors[] = $name . ' (KI): ' . $e->getMessage();
                            $mitKi    = str_contains($e->getMessage(), 'API') ? false : $mitKi;
                        }
                    }
                } catch (RuntimeException $e) {
                    $errors[] = $name . ': ' . $e->getMessage();
                }
            }
        }

        if ($ok > 0) {
            Audit::log('images_uploaded', 'image', null, $ok . ' Bild(er), Tags: ' . implode(', ', $tags));
            Flash::success(t('%d Bild(er) übernommen.', $ok)
                . ($kiOk > 0 ? ' ' . t('%d davon von der KI beschrieben und verschlagwortet.', $kiOk) : ''));
        }
        if ($errors !== []) {
            Flash::error(implode(' · ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? ' …' : ''));
        }
        if ($ok === 0 && $errors === []) {
            Flash::error(t('Keine Datei ausgewählt.'));
        }

        Url::redirect('/admin/medien', ['tag' => $tags[0] ?? '']);
    }

    /** @param array<string,string> $args */
    public function update(array $args): void
    {
        AuthController::requireRole(...self::ROLES);
        Csrf::verify();

        $id = (int) ($args['id'] ?? 0);
        if (ImageRepo::find($id) === null) {
            Flash::error(t('Bild nicht gefunden.'));
            Url::redirect('/admin/medien');
        }
        ImageRepo::update($id, trim(post('caption')), ImageRepo::parseTags(post('tags')));
        Flash::success(t('Bild gespeichert.'));
        $this->back();
    }

    /**
     * KI-Bildtext: Claude beschreibt und verschlagwortet EIN Bild. Der
     * vorhandene Bildtext wird ueberschrieben (der Knopf wird bewusst
     * gedrueckt), Tags werden ergaenzt.
     *
     * @param array<string,string> $args
     */
    public function aiDescribe(array $args): void
    {
        AuthController::requireRole(...self::ROLES);
        Csrf::verify();

        $img = ImageRepo::find((int) ($args['id'] ?? 0));

        if ($img === null) {
            Flash::error(t('Bild nicht gefunden.'));
            Url::redirect('/admin/medien');
        }

        try {
            $analyse = AiImage::analyze($img);
        } catch (RuntimeException $e) {
            Flash::error(t('KI-Bildtext fehlgeschlagen: %s', $e->getMessage()));
            $this->back();
        }

        ImageRepo::update(
            (int) $img['id'],
            $analyse['caption'] !== '' ? $analyse['caption'] : (string) $img['caption'],
            array_values(array_unique(array_merge($img['tags'], $analyse['tags'])))
        );

        Flash::success(t('KI-Bildtext übernommen: „%s“ – bitte kurz gegenlesen.', $analyse['caption']));
        $this->back();
    }

    /**
     * KI-Zuschnitt: neues Bibliotheksbild im Zielformat rund ums erkannte
     * Motiv, optional mit sanfter Helligkeits-/Kontrastkorrektur. Das
     * Original bleibt unveraendert.
     *
     * @param array<string,string> $args
     */
    public function aiCrop(array $args): void
    {
        AuthController::requireRole(...self::ROLES);
        Csrf::verify();

        $img = ImageRepo::find((int) ($args['id'] ?? 0));

        if ($img === null) {
            Flash::error(t('Bild nicht gefunden.'));
            Url::redirect('/admin/medien');
        }

        $format = post('format');

        if (!isset(AiImage::FORMATS[$format])) {
            Flash::error(t('Unbekanntes Zielformat.'));
            $this->back();
        }

        try {
            $ergebnis = AiImage::crop($img, $format, post_bool('verbessern') === 1);
        } catch (RuntimeException $e) {
            Flash::error(t('KI-Zuschnitt fehlgeschlagen: %s', $e->getMessage()));
            $this->back();
        }

        $caption = (string) $img['caption'] !== '' ? (string) $img['caption'] : $ergebnis['analyse']['caption'];

        $neuId = ImageRepo::create(
            $ergebnis['img'],
            array_values(array_unique(array_merge($img['tags'], ['zuschnitt']))),
            $caption,
            (string) $img['orig_name'],
            Auth::id()
        );

        Audit::log('image_ai_crop', 'image', $neuId, $format . ' aus Bild #' . (int) $img['id']);
        Flash::success(t('KI-Zuschnitt gespeichert (%d × %d) – als neues Bild in der Bibliothek, das Original bleibt erhalten.', $ergebnis['img']['width'], $ergebnis['img']['height']));
        $this->back();
    }

    /** Sammelaktion fuer die angehakten Bilder: Tags ergaenzen/entfernen oder loeschen. */
    public function bulk(): void
    {
        AuthController::requireRole(...self::ROLES);
        Csrf::verify();

        $ids    = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
        $action = post('action');
        $tags   = ImageRepo::parseTags(post('bulk_tags'));

        if ($ids === []) {
            Flash::error(t('Keine Bilder ausgewählt.'));
            $this->back();
        }

        switch ($action) {
            case 'addtags':
                if ($tags === []) {
                    Flash::error(t('Bitte Tags eingeben.'));
                    $this->back();
                }
                ImageRepo::addTags($ids, $tags);
                Flash::success(t('Tags bei %d Bild(ern) ergänzt.', count($ids)));
                break;
            case 'deltags':
                if ($tags === []) {
                    Flash::error(t('Bitte Tags eingeben.'));
                    $this->back();
                }
                ImageRepo::removeTags($ids, $tags);
                Flash::success(t('Tags bei %d Bild(ern) entfernt.', count($ids)));
                break;
            case 'delete':
                foreach ($ids as $id) {
                    ImageRepo::delete($id);
                }
                Audit::log('images_deleted', 'image', null, count($ids) . ' Bild(er)');
                Flash::success(t('%d Bild(er) gelöscht.', count($ids)));
                break;
            default:
                Flash::error(t('Unbekannte Aktion.'));
        }

        $this->back();
    }

    private function back(): never
    {
        Url::redirect('/admin/medien', ['q' => post('q'), 'tag' => post('tag')]);
    }
}
