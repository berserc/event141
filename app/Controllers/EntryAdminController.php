<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
use App\Models\AthleteRepo;
use App\Models\EntryRepo;
use App\Models\EventRepo;
use App\Models\GymRepo;

/** Anmeldungen eines Events: bestaetigen, wiegen, setzen, manuell ergaenzen. */
final class EntryAdminController
{
    public function index(array $args): void
    {
        AuthController::requireLogin();

        $event  = EventAdminController::load((int) ($args['id'] ?? 0));
        $filter = [
            'status'   => query('status'),
            'category' => (int) query('category', '0'),
            'gym'      => (int) query('gym', '0'),
            'q'        => query('q'),
        ];

        View::display('admin/events/entries', [
            'title'      => $event['name'] . ' – ' . t('Anmeldungen'),
            'event'      => $event,
            'entries'    => EntryRepo::forEvent((int) $event['id'], $filter),
            'categories' => EventRepo::categories((int) $event['id']),
            'gyms'       => GymRepo::options(),
            'athletes'   => AthleteRepo::search('', 0, 2000),
            'filter'     => $filter,
            'stats'      => EventRepo::stats((int) $event['id']),
        ], 'layouts/admin');
    }

    /** Sportler manuell anmelden (Verwaltung). */
    public function store(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event     = EventAdminController::load((int) ($args['id'] ?? 0));
        $athlete   = AthleteRepo::find(post_int('athlete_id'));
        $category  = post_id('category_id');

        if ($athlete === null) {
            Flash::error(t('Bitte einen Sportler auswählen.'));
            Url::redirect('/admin/events/' . $event['id'] . '/anmeldungen');
        }

        if ($category !== null && Database::one(
            'SELECT id FROM event_categories WHERE id = ? AND event_id = ?',
            [$category, (int) $event['id']]
        ) === null) {
            $category = null;
        }

        $dup = Database::one(
            'SELECT id FROM event_entries WHERE event_id = ? AND athlete_id = ? AND category_id IS ?',
            [(int) $event['id'], (int) $athlete['id'], $category]
        );

        if ($dup !== null) {
            Flash::error(t('Dieser Sportler ist in dieser Kategorie bereits angemeldet.'));
            Url::redirect('/admin/events/' . $event['id'] . '/anmeldungen');
        }

        $id = Database::insert('event_entries', [
            'event_id'    => (int) $event['id'],
            'athlete_id'  => (int) $athlete['id'],
            'gym_id'      => (int) $athlete['gym_id'],
            'category_id' => $category,
            'status'      => 'bestaetigt',
            'source'      => 'admin',
        ]);

        Audit::log('entry_created', 'event', (int) $event['id'], person_name($athlete));
        Flash::success(t('%s angemeldet (bestätigt).', person_name($athlete)));
        Url::redirect('/admin/events/' . $event['id'] . '/anmeldungen');
    }

    /** Einzelne Anmeldung aendern (Status, Kategorie, Setzung, Wiegen, Startgeld). */
    public function update(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $entry = $this->own((int) ($args['eid'] ?? 0), (int) $event['id']);

        $data = [];

        if (isset($_POST['status']) && isset(EntryRepo::STATUS[post('status')])) {
            $data['status'] = post('status');
        }

        if (array_key_exists('category_id', $_POST)) {
            $cat = post_id('category_id');

            if ($cat !== null && Database::one(
                'SELECT id FROM event_categories WHERE id = ? AND event_id = ?',
                [$cat, (int) $event['id']]
            ) === null) {
                $cat = null;
            }

            $data['category_id'] = $cat;
        }

        if (array_key_exists('seed', $_POST)) {
            $data['seed'] = max(0, post_int('seed'));
        }

        if (array_key_exists('weighed', $_POST)) {
            $data['weighed']    = post_float_or_null('weighed');
            $data['weighed_at'] = $data['weighed'] !== null ? gmdate('Y-m-d H:i:s') : null;
        }

        if (array_key_exists('paid', $_POST) || array_key_exists('paid_form', $_POST)) {
            $data['paid'] = post_bool('paid');
        }

        if (array_key_exists('admin_note', $_POST)) {
            $data['admin_note'] = post('admin_note');
        }

        if ($data !== []) {
            $data['updated_at'] = gmdate('Y-m-d H:i:s');
            Database::update('event_entries', (int) $entry['id'], $data);
            Audit::log('entry_updated', 'event', (int) $event['id'], person_name($entry) . ': ' . Audit::diff($entry, $data));
        }

        Flash::success(t('Anmeldung gespeichert.'));
        Url::redirectRaw((string) ($_SERVER['HTTP_REFERER'] ?? Url::to('/admin/events/' . $event['id'] . '/anmeldungen')));
    }

    /** Sammelaktion: bestaetigen / ablehnen / abmelden / loeschen. */
    public function bulk(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event  = EventAdminController::load((int) ($args['id'] ?? 0));
        $ids    = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
        $action = post('action');

        if ($ids === []) {
            Flash::error(t('Keine Anmeldungen ausgewählt.'));
            Url::redirect('/admin/events/' . $event['id'] . '/anmeldungen');
        }

        $marks  = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([(int) $event['id']], $ids);

        if ($action === 'loeschen') {
            $n = Database::run("DELETE FROM event_entries WHERE event_id = ? AND id IN ($marks)", $params)->rowCount();
            Flash::success(t('%d Anmeldung(en) gelöscht.', $n));
        } elseif (isset(EntryRepo::STATUS[$action])) {
            $n = Database::run(
                "UPDATE event_entries SET status = ?, updated_at = ? WHERE event_id = ? AND id IN ($marks)",
                array_merge([$action, gmdate('Y-m-d H:i:s')], $params)
            )->rowCount();
            Flash::success(t('%d Anmeldung(en) auf „%s“ gesetzt.', $n, t(EntryRepo::STATUS[$action])));
        } elseif ($action === 'bezahlt') {
            Database::run("UPDATE event_entries SET paid = 1 WHERE event_id = ? AND id IN ($marks)", $params);
            Flash::success(t('Startgeld als bezahlt markiert.'));
        } else {
            Flash::error(t('Unbekannte Aktion.'));
        }

        Audit::log('entry_bulk', 'event', (int) $event['id'], $action . ': ' . count($ids));
        Url::redirect('/admin/events/' . $event['id'] . '/anmeldungen');
    }

    public function destroy(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $entry = $this->own((int) ($args['eid'] ?? 0), (int) $event['id']);

        Database::run('DELETE FROM event_entries WHERE id = ?', [(int) $entry['id']]);
        Audit::log('entry_deleted', 'event', (int) $event['id'], person_name($entry));
        Flash::success(t('Anmeldung gelöscht.'));
        Url::redirect('/admin/events/' . $event['id'] . '/anmeldungen');
    }

    /** Teilnehmerliste als CSV (fuer Wiegen, Aushang, Sanitaeter …). */
    public function exportCsv(array $args): void
    {
        AuthController::requireLogin();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $rows  = EntryRepo::forEvent((int) $event['id']);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="anmeldungen-' . $event['slug'] . '.csv"');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            t('Kategorie'), t('Zuname'), t('Vorname'), t('Kampfname'), t('Geburtsdatum'), t('Alter'), t('Geschlecht'), t('Nation'), t('Gym'),
            t('Ort'), t('Gewicht gemeldet'), t('Wiegegewicht'), t('Status'), t('Setzung'), t('Startgeld'), t('Bilanz'), t('Anmerkung'),
        ], ';', '"', '\\');

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['category_name'] ?? '', $r['last_name'], $r['first_name'], $r['nickname'],
                format_date($r['birthdate']), (string) (age_from($r['birthdate'], (string) $event['starts_on']) ?? ''),
                t(AthleteRepo::GENDERS[$r['gender']] ?? $r['gender']), $r['nationality'], $r['gym_name'], $r['gym_city'],
                $r['athlete_weight'] !== null ? number_format((float) $r['athlete_weight'], 1, ',', '') : '',
                $r['weighed'] !== null ? number_format((float) $r['weighed'], 1, ',', '') : '',
                t(EntryRepo::STATUS[$r['status']] ?? $r['status']), (int) $r['seed'] > 0 ? (int) $r['seed'] : '',
                (int) $r['paid'] === 1 ? t('ja') : t('nein'),
                (int) $r['record_wins'] . '-' . (int) $r['record_losses'] . '-' . (int) $r['record_draws'],
                $r['note'],
            ], ';', '"', '\\');
        }

        fclose($out);
        exit;
    }

    private function own(int $entryId, int $eventId): array
    {
        $entry = EntryRepo::find($entryId);

        if ($entry === null || (int) $entry['event_id'] !== $eventId) {
            Flash::error(t('Anmeldung nicht gefunden.'));
            Url::redirect('/admin/events/' . $eventId . '/anmeldungen');
        }

        return $entry;
    }
}
