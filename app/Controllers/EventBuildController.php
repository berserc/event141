<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
use App\Models\EventRepo;

/** Aufbau eines Events: Tage, Abschnitte, Wettkampfstaetten, Kategorien. */
final class EventBuildController
{
    // ------------------------------------------------------------- Aufbau --

    public function build(array $args): void
    {
        AuthController::requireLogin();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));

        View::display('admin/events/build', [
            'title'  => $event['name'] . ' – Aufbau',
            'event'  => $event,
            'days'   => EventRepo::structure((int) $event['id']),
            'venues' => EventRepo::venues((int) $event['id']),
        ], 'layouts/admin');
    }

    public function saveDay(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $dayId = post_int('day_id');
        $date  = parse_date(post('day_date'));

        if ($date === null) {
            Flash::error('Bitte ein gültiges Datum angeben.');
            Url::redirect('/admin/events/' . $event['id'] . '/aufbau');
        }

        $data = [
            'day_date'   => $date,
            'label'      => post('label'),
            'note'       => post('note'),
            'sort_order' => post_int('sort_order'),
        ];

        if ($dayId > 0) {
            $this->own('event_days', $dayId, (int) $event['id']);
            Database::update('event_days', $dayId, $data);
        } else {
            $dup = Database::one('SELECT id FROM event_days WHERE event_id = ? AND day_date = ?', [(int) $event['id'], $date]);

            if ($dup !== null) {
                Flash::error('Diesen Tag gibt es schon.');
                Url::redirect('/admin/events/' . $event['id'] . '/aufbau');
            }

            $data['event_id'] = (int) $event['id'];
            Database::insert('event_days', $data);
        }

        Flash::success('Tag gespeichert.');
        Url::redirect('/admin/events/' . $event['id'] . '/aufbau');
    }

    public function deleteDay(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $dayId = post_int('day_id');
        $this->own('event_days', $dayId, (int) $event['id']);

        Database::run('DELETE FROM event_days WHERE id = ?', [$dayId]);
        Flash::success('Tag samt Abschnitten entfernt (Kämpfe bleiben erhalten und sind jetzt „nicht eingeplant“).');
        Url::redirect('/admin/events/' . $event['id'] . '/aufbau');
    }

    public function saveSession(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event     = EventAdminController::load((int) ($args['id'] ?? 0));
        $sessionId = post_int('session_id');
        $dayId     = post_int('day_id');
        $name      = post('name');

        $this->own('event_days', $dayId, (int) $event['id']);

        if ($name === '') {
            Flash::error('Bitte einen Namen für den Abschnitt angeben.');
            Url::redirect('/admin/events/' . $event['id'] . '/aufbau');
        }

        $data = [
            'day_id'     => $dayId,
            'name'       => $name,
            'starts_at'  => parse_time(post('starts_at')),
            'ends_at'    => parse_time(post('ends_at')),
            'note'       => post('note'),
            'sort_order' => post_int('sort_order'),
        ];

        if ($sessionId > 0) {
            $this->own('event_sessions', $sessionId, (int) $event['id']);
            Database::update('event_sessions', $sessionId, $data);
        } else {
            if ($data['sort_order'] === 0) {
                $data['sort_order'] = (int) Database::value(
                    'SELECT COALESCE(MAX(sort_order), 0) + 10 FROM event_sessions WHERE day_id = ?',
                    [$dayId]
                );
            }

            $data['event_id'] = (int) $event['id'];
            Database::insert('event_sessions', $data);
        }

        Flash::success('Abschnitt gespeichert.');
        Url::redirect('/admin/events/' . $event['id'] . '/aufbau');
    }

    public function deleteSession(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event     = EventAdminController::load((int) ($args['id'] ?? 0));
        $sessionId = post_int('session_id');
        $this->own('event_sessions', $sessionId, (int) $event['id']);

        Database::run('DELETE FROM event_sessions WHERE id = ?', [$sessionId]);
        Flash::success('Abschnitt entfernt.');
        Url::redirect('/admin/events/' . $event['id'] . '/aufbau');
    }

    public function saveVenue(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $venueId = post_int('venue_id');
        $name    = post('name');

        if ($name === '') {
            Flash::error('Bitte einen Namen für die Wettkampfstätte angeben (z. B. Ring 1).');
            Url::redirect('/admin/events/' . $event['id'] . '/aufbau');
        }

        $color = post('color');

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '';
        }

        $data = [
            'name'       => $name,
            'short_name' => mb_substr(post('short_name'), 0, 8),
            'color'      => $color,
            'note'       => post('note'),
            'sort_order' => post_int('sort_order'),
        ];

        if ($venueId > 0) {
            $this->own('event_venues', $venueId, (int) $event['id']);
            Database::update('event_venues', $venueId, $data);
        } else {
            if ($data['sort_order'] === 0) {
                $data['sort_order'] = (int) Database::value(
                    'SELECT COALESCE(MAX(sort_order), 0) + 10 FROM event_venues WHERE event_id = ?',
                    [(int) $event['id']]
                );
            }

            $data['event_id'] = (int) $event['id'];
            Database::insert('event_venues', $data);
        }

        Flash::success('Wettkampfstätte gespeichert.');
        Url::redirect('/admin/events/' . $event['id'] . '/aufbau');
    }

    public function deleteVenue(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $venueId = post_int('venue_id');
        $this->own('event_venues', $venueId, (int) $event['id']);

        Database::run('DELETE FROM event_venues WHERE id = ?', [$venueId]);
        Flash::success('Wettkampfstätte entfernt.');
        Url::redirect('/admin/events/' . $event['id'] . '/aufbau');
    }

    // --------------------------------------------------------- Kategorien --

    public function categories(array $args): void
    {
        AuthController::requireLogin();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));

        View::display('admin/events/categories', [
            'title'      => $event['name'] . ' – Kategorien',
            'event'      => $event,
            'categories' => EventRepo::categories((int) $event['id']),
            'errors'     => Flash::errors(),
            'old'        => Flash::oldInput(),
        ], 'layouts/admin');
    }

    public function saveCategory(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event      = EventAdminController::load((int) ($args['id'] ?? 0));
        $categoryId = post_int('category_id');
        $name       = post('name');

        if ($name === '') {
            Flash::withInput($_POST, ['name' => 'Bitte einen Namen angeben.']);
            Flash::error('Bitte einen Namen für die Kategorie angeben.');
            Url::redirect('/admin/events/' . $event['id'] . '/kategorien');
        }

        $gender = post('gender');

        if (!isset(EventRepo::GENDERS[$gender])) {
            $gender = 'alle';
        }

        $ageMin = post('age_min') !== '' ? post_int('age_min') : null;
        $ageMax = post('age_max') !== '' ? post_int('age_max') : null;

        $data = [
            'name'          => $name,
            'discipline'    => post('discipline'),
            'gender'        => $gender,
            'age_min'       => $ageMin,
            'age_max'       => $ageMax,
            'weight_min'    => post_float_or_null('weight_min'),
            'weight_max'    => post_float_or_null('weight_max'),
            'rounds'        => max(1, post_int('rounds', 3)),
            'round_minutes' => max(0.5, post_float('round_minutes', 2)),
            'mode'          => post('mode') === 'liste' ? 'liste' : 'ko',
            'max_entries'   => max(0, post_int('max_entries')),
            'note'          => post('note'),
            'sort_order'    => post_int('sort_order'),
        ];

        if ($categoryId > 0) {
            $this->own('event_categories', $categoryId, (int) $event['id']);
            Database::update('event_categories', $categoryId, $data);
        } else {
            if ($data['sort_order'] === 0) {
                $data['sort_order'] = (int) Database::value(
                    'SELECT COALESCE(MAX(sort_order), 0) + 10 FROM event_categories WHERE event_id = ?',
                    [(int) $event['id']]
                );
            }

            $data['event_id'] = (int) $event['id'];
            $categoryId       = Database::insert('event_categories', $data);
        }

        Audit::log('category_saved', 'event', (int) $event['id'], $name);
        Flash::success('Kategorie gespeichert.');
        Url::redirect('/admin/events/' . $event['id'] . '/kategorien');
    }

    public function deleteCategory(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event      = EventAdminController::load((int) ($args['id'] ?? 0));
        $categoryId = post_int('category_id');
        $this->own('event_categories', $categoryId, (int) $event['id']);

        $entries = (int) Database::value('SELECT COUNT(*) FROM event_entries WHERE category_id = ?', [$categoryId]);

        if ($entries > 0) {
            Flash::error('Die Kategorie hat noch Anmeldungen – bitte zuerst umbuchen oder abmelden.');
            Url::redirect('/admin/events/' . $event['id'] . '/kategorien');
        }

        Database::run('DELETE FROM event_bouts WHERE category_id = ?', [$categoryId]);
        Database::run('DELETE FROM event_categories WHERE id = ?', [$categoryId]);
        Flash::success('Kategorie entfernt.');
        Url::redirect('/admin/events/' . $event['id'] . '/kategorien');
    }

    /** Kategorien aus einem anderen Event uebernehmen (Vorlage). */
    public function copyCategories(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event  = EventAdminController::load((int) ($args['id'] ?? 0));
        $source = EventRepo::find(post_int('source_event_id'));

        if ($source === null || (int) $source['id'] === (int) $event['id']) {
            Flash::error('Bitte ein anderes Event als Vorlage wählen.');
            Url::redirect('/admin/events/' . $event['id'] . '/kategorien');
        }

        $n = 0;

        foreach (EventRepo::categories((int) $source['id']) as $c) {
            Database::insert('event_categories', [
                'event_id'      => (int) $event['id'],
                'name'          => $c['name'],
                'discipline'    => $c['discipline'],
                'gender'        => $c['gender'],
                'age_min'       => $c['age_min'],
                'age_max'       => $c['age_max'],
                'weight_min'    => $c['weight_min'],
                'weight_max'    => $c['weight_max'],
                'rounds'        => $c['rounds'],
                'round_minutes' => $c['round_minutes'],
                'mode'          => $c['mode'],
                'max_entries'   => $c['max_entries'],
                'note'          => $c['note'],
                'sort_order'    => $c['sort_order'],
            ]);
            $n++;
        }

        Flash::success($n . ' Kategorien aus „' . $source['name'] . '“ übernommen.');
        Url::redirect('/admin/events/' . $event['id'] . '/kategorien');
    }

    /** Kategorien aus einem Regelsatz (WAKO, olympisches Boxen, IFMA …) erzeugen. */
    public function applyRuleset(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $back    = '/admin/events/' . $event['id'] . '/kategorien';
        $ruleset = \App\Core\Ruleset::find(post('ruleset'));

        if ($ruleset === null) {
            Flash::error('Regelsatz nicht gefunden.');
            Url::redirect($back);
        }

        $pick = static fn (string $key): array => array_values(array_filter(
            array_map('strval', (array) ($_POST[$key] ?? [])),
            static fn (string $v): bool => $v !== ''
        ));

        $disciplines = $pick('disc');
        $classes     = $pick('class');
        $genders     = array_values(array_intersect($pick('gender'), ['m', 'w']));

        if ($disciplines === [] || $classes === [] || $genders === []) {
            Flash::error('Bitte mindestens eine Disziplin, eine Altersklasse und ein Geschlecht wählen.');
            Url::redirect($back);
        }

        $rows = \App\Core\Ruleset::expand($ruleset, $disciplines, $classes, $genders);

        if ($rows === []) {
            Flash::error('Diese Auswahl ergibt keine Kategorien (die gewählten Altersklassen gibt es in den gewählten Disziplinen nicht).');
            Url::redirect($back);
        }

        $result = \App\Core\Ruleset::apply((int) $event['id'], $rows);

        Audit::log('ruleset_applied', 'event', (int) $event['id'], $ruleset['name'] . ': ' . $result['created'] . ' Kategorien');
        Flash::success($result['created'] . ' Kategorien aus „' . $ruleset['name'] . '“ angelegt'
            . ($result['skipped'] > 0 ? ' (' . $result['skipped'] . ' gab es schon)' : '') . '.');
        Url::redirect($back);
    }

    /** Stellt sicher, dass ein Datensatz zum Event gehoert. */
    private function own(string $table, int $id, int $eventId): void
    {
        $row = Database::one("SELECT id FROM $table WHERE id = ? AND event_id = ?", [$id, $eventId]);

        if ($row === null) {
            Flash::error('Datensatz nicht gefunden.');
            Url::redirect('/admin/events/' . $eventId . '/aufbau');
        }
    }
}
