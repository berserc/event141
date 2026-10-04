<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Fightcard;
use App\Core\Flash;
use App\Core\Timetable;
use App\Core\Upload;
use App\Core\Url;
use App\Core\View;
use App\Models\BoutRepo;
use App\Models\EntryRepo;
use App\Models\EventRepo;
use RuntimeException;

/**
 * Rund ums Event: Sponsoren, Listen & Druck (Fightcard-Aushang,
 * Kaempfer-Checkliste, Kabineneinteilung), Live-Modus, Fightcard-Import.
 */
final class EventExtrasController
{
    // ------------------------------------------------------------ Sponsoren --

    public function sponsors(array $args): void
    {
        AuthController::requireLogin();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));

        View::display('admin/events/sponsors', [
            'title'    => $event['name'] . ' – ' . t('Sponsoren'),
            'event'    => $event,
            'sponsors' => EventRepo::sponsors((int) $event['id']),
        ], 'layouts/admin');
    }

    public function saveSponsor(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $id    = post_int('sponsor_id');
        $name  = post('name');

        if ($name === '') {
            Flash::error(t('Bitte den Namen des Sponsors angeben.'));
            Url::redirect('/admin/events/' . $event['id'] . '/sponsoren');
        }

        $color = post('tile_color');
        $url   = post('url');

        $data = [
            'name'       => $name,
            'url'        => $url !== '' && !preg_match('#^https?://#i', $url) ? 'https://' . $url : $url,
            'tile_color' => post_bool('use_color') === 1 && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '',
            'is_main'    => post_bool('is_main'),
            'published'  => post_bool('published'),
            'sort_order' => post_int('sort_order'),
        ];

        if ($id > 0) {
            $row = Database::one('SELECT * FROM event_sponsors WHERE id = ? AND event_id = ?', [$id, (int) $event['id']]);

            if ($row === null) {
                Url::redirect('/admin/events/' . $event['id'] . '/sponsoren');
            }

            Database::update('event_sponsors', $id, $data);
        } else {
            if ($data['sort_order'] === 0) {
                $data['sort_order'] = (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM event_sponsors WHERE event_id = ?', [(int) $event['id']]);
            }

            $id = Database::insert('event_sponsors', $data + ['event_id' => (int) $event['id']]);
        }

        try {
            $file = $_FILES['logo'] ?? null;

            if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $path = Upload::image($file, 'sponsoren', 'sponsor-' . $id, 900);

                if ($path !== null) {
                    $alt = (string) Database::value('SELECT logo_path FROM event_sponsors WHERE id = ?', [$id]);

                    if ($alt !== '') {
                        Upload::delete($alt);
                    }

                    Database::update('event_sponsors', $id, ['logo_path' => $path]);
                }
            }
        } catch (RuntimeException $e) {
            Flash::error(t('Logo nicht übernommen: %s', $e->getMessage()));
        }

        Audit::log('sponsor_saved', 'event', (int) $event['id'], $name);
        Flash::success(t('Sponsor gespeichert.'));
        Url::redirect('/admin/events/' . $event['id'] . '/sponsoren');
    }

    public function deleteSponsor(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $row   = Database::one('SELECT * FROM event_sponsors WHERE id = ? AND event_id = ?', [post_int('sponsor_id'), (int) $event['id']]);

        if ($row !== null) {
            if ((string) $row['logo_path'] !== '') {
                Upload::delete((string) $row['logo_path']);
            }

            Database::run('DELETE FROM event_sponsors WHERE id = ?', [(int) $row['id']]);
            Audit::log('sponsor_deleted', 'event', (int) $event['id'], (string) $row['name']);
            Flash::success(t('Sponsor entfernt.'));
        }

        Url::redirect('/admin/events/' . $event['id'] . '/sponsoren');
    }

    // -------------------------------------------------------- Listen & Druck --

    public function lists(array $args): void
    {
        AuthController::requireLogin();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));

        View::display('admin/events/lists', [
            'title'     => $event['name'] . ' – ' . t('Listen & Druck'),
            'event'     => $event,
            'entries'   => EntryRepo::forEvent((int) $event['id'], ['status' => 'bestaetigt']),
            'conflicts' => self::cabinConflicts((int) $event['id']),
        ], 'layouts/admin');
    }

    /** Checkliste speichern: Waage, Musik, Arzt, Kabine – alle Zeilen auf einmal. */
    public function saveChecklist(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $n     = 0;

        foreach ((array) ($_POST['rows'] ?? []) as $entryId => $row) {
            $row   = (array) $row;
            $entry = Database::one('SELECT id FROM event_entries WHERE id = ? AND event_id = ?', [(int) $entryId, (int) $event['id']]);

            if ($entry === null) {
                continue;
            }

            $w = trim((string) ($row['weighed'] ?? ''));
            $w = $w === '' ? null : (float) str_replace(',', '.', $w);

            Database::update('event_entries', (int) $entryId, [
                'weighed'    => $w,
                'music'      => mb_substr(trim((string) ($row['music'] ?? '')), 0, 200),
                'medical_ok' => !empty($row['medical_ok']) ? 1 : 0,
                'cabin'      => mb_substr(trim((string) ($row['cabin'] ?? '')), 0, 40),
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $n++;
        }

        Flash::success(t('Checkliste gespeichert (%d Sportler).', $n));
        Url::redirect('/admin/events/' . $event['id'] . '/listen');
    }

    /** Druckansichten: aushang | check | kabinen (eigenes, helles A4-Layout). */
    public function printView(array $args): void
    {
        AuthController::requireLogin();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $doc   = (string) ($args['doc'] ?? '');

        // Turnierbaeume und Running Order teilen sich die Ansicht mit der Website.
        if (in_array($doc, \App\Core\PrintDocs::DOCS, true)) {
            \App\Core\PrintDocs::display($event, $doc);

            return;
        }

        if (!in_array($doc, ['aushang', 'check', 'kabinen'], true)) {
            Url::redirect('/admin/events/' . $event['id'] . '/listen');
        }

        $bouts = array_values(array_filter(
            BoutRepo::forEvent((int) $event['id']),
            static fn (array $b): bool => (int) $b['active'] === 1 && BoutRepo::needsPlacement($b)
        ));

        View::display('admin/events/print-' . $doc, [
            'title'     => $event['name'],
            'event'     => $event,
            'bouts'     => $bouts,
            'times'     => Timetable::compute($event, $bouts),
            'entries'   => EntryRepo::forEvent((int) $event['id'], ['status' => 'bestaetigt']),
            'conflicts' => self::cabinConflicts((int) $event['id']),
        ], 'layouts/print');
    }

    /**
     * Gegner in derselben Kabine? Liefert lesbare Konflikte.
     *
     * @return list<string>
     */
    public static function cabinConflicts(int $eventId): array
    {
        $rows = Database::all(
            "SELECT b.bout_no, r.cabin AS rc, l.cabin AS lc,
                    ra.first_name || ' ' || ra.last_name AS rn, la.first_name || ' ' || la.last_name AS ln
               FROM event_bouts b
               JOIN event_entries r ON r.id = b.red_entry_id JOIN athletes ra ON ra.id = r.athlete_id
               JOIN event_entries l ON l.id = b.blue_entry_id JOIN athletes la ON la.id = l.athlete_id
              WHERE b.event_id = ? AND b.active = 1 AND b.status <> 'abgesagt'
                AND r.cabin <> '' AND r.cabin = l.cabin",
            [$eventId]
        );

        return array_map(
            static fn (array $r): string => t('Kampf #%s: %s und %s sind beide in Kabine „%s“.', $r['bout_no'], $r['rn'], $r['ln'], $r['rc']),
            $rows
        );
    }

    // ------------------------------------------------------------ Live-Modus --

    public function liveMode(array $args): void
    {
        AuthController::requireRole('superuser', 'orga', 'ring');
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $on    = post_bool('live_mode');

        Database::update('events', (int) $event['id'], ['live_mode' => $on, 'updated_at' => gmdate('Y-m-d H:i:s')]);
        Audit::log('event_live_mode', 'event', (int) $event['id'], $on ? 'an' : 'aus');
        Flash::success($on
            ? t('Live-Modus an: die Beginnzeiten richten sich jetzt nach den echten Start- und Endzeiten der Kämpfe.')
            : t('Live-Modus aus: die Beginnzeiten werden aus Startzeit und geplanter Dauer berechnet.'));
        Url::redirectRaw((string) ($_SERVER['HTTP_REFERER'] ?? Url::to('/admin/events/' . $event['id'] . '/zeitplan')));
    }

    // ---------------------------------------------------------------- Import --

    public function importForm(): void
    {
        AuthController::requireWrite();

        View::display('admin/events/import', ['title' => t('Fightcard importieren')], 'layouts/admin');
    }

    /** Import einer fights.json (Datei-Upload oder Adresse einer bestehenden Event-Website). */
    public function import(): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $site = rtrim(post('site_url'), '/');
        $json = '';
        $pool = '';
        $base = '';

        if ($site !== '') {
            if (!preg_match('#^https?://#i', $site)) {
                $site = 'https://' . $site;
            }

            $ctx  = stream_context_create(['http' => ['timeout' => 20]]);
            $json = (string) @file_get_contents($site . '/data/fights.json', false, $ctx);
            $pool = (string) @file_get_contents($site . '/data/fighters.json', false, $ctx);
            $base = post_bool('with_media') === 1 ? $site : '';
        } elseif (is_uploaded_file((string) ($_FILES['fights']['tmp_name'] ?? ''))) {
            $json = (string) file_get_contents((string) $_FILES['fights']['tmp_name']);

            if (is_uploaded_file((string) ($_FILES['fighters']['tmp_name'] ?? ''))) {
                $pool = (string) file_get_contents((string) $_FILES['fighters']['tmp_name']);
            }

            $base = post_bool('with_media') === 1 ? rtrim(post('media_base'), '/') : '';
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            Flash::error($site !== ''
                ? t('Keine gültige fights.json gefunden unter %s.', $site . '/data/fights.json')
                : t('Keine gültige fights.json gefunden.'));
            Url::redirect('/admin/events/import');
        }

        if (post_bool('replace') !== 1 && ($limit = \App\Core\License::eventLimitError()) !== null) {
            Flash::error($limit);
            Url::redirect('/admin/events/import');
        }

        try {
            $result = Fightcard::import($data, (array) (json_decode($pool, true) ?: []), $base, Auth::id(), post_bool('replace') === 1);
        } catch (\Throwable $e) {
            Flash::error(t('Import fehlgeschlagen: %s', $e->getMessage()));
            Url::redirect('/admin/events/import');
        }

        Audit::log('event_imported', 'event', $result['event_id'], $result['bouts'] . ' Kämpfe');
        Flash::success(t(
            'Fightcard importiert: %d Kämpfe, %d neue Sportler, %d neue Gyms, %d Mediendateien. Das Event ist noch nicht veröffentlicht.',
            $result['bouts'],
            $result['athletes'],
            $result['gyms'],
            $result['media']
        ));
        Url::redirect('/admin/events/' . $result['event_id']);
    }
}
