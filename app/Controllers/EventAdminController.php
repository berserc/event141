<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Upload;
use App\Core\Url;
use App\Core\View;
use App\Models\EventRepo;
use RuntimeException;

/** Events: Liste, Stammdaten, Typ (Gala/Turnier), Bilder, Veroeffentlichung. */
final class EventAdminController
{
    private const IMAGE_FIELDS = [
        'logo_path'   => 600,
        'poster_path' => 1200,
        'hero_path'   => 1900,
        'belt_path'   => 1200,
    ];

    /** Tabs, die jede Event-Seite oben zeigt. */
    public static function tabs(array $event): array
    {
        $id   = (int) $event['id'];
        $tabs = [
            ['Übersicht', '/admin/events/' . $id],
            ['Aufbau', '/admin/events/' . $id . '/aufbau'],
            ['Kategorien', '/admin/events/' . $id . '/kategorien'],
            ['Anmeldungen', '/admin/events/' . $id . '/anmeldungen'],
            [$event['type'] === 'gala' ? 'Fightcard' : 'Kämpfe', '/admin/events/' . $id . '/kaempfe'],
            ['Zeitplan', '/admin/events/' . $id . '/zeitplan'],
            ['Ergebnisse', '/admin/events/' . $id . '/ergebnisse'],
            ['Sponsoren', '/admin/events/' . $id . '/sponsoren'],
            ['Listen & Druck', '/admin/events/' . $id . '/listen'],
        ];

        return $tabs;
    }

    /** Laedt das Event oder bricht mit 404 ab. */
    public static function load(int $id): array
    {
        $event = EventRepo::find($id);

        if ($event === null) {
            http_response_code(404);
            View::display('errors/404-admin', ['title' => 'Nicht gefunden'], 'layouts/admin');
            exit;
        }

        return $event;
    }

    public function index(): void
    {
        AuthController::requireLogin();

        View::display('admin/events/index', [
            'title'  => 'Events',
            'events' => EventRepo::all(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        AuthController::requireWrite();

        View::display('admin/events/form', [
            'title'  => 'Neues Event',
            'event'  => Flash::oldInput() + $this->empty(),
            'errors' => Flash::errors(),
            'isNew'  => true,
        ], 'layouts/admin');
    }

    /** Event-Startseite in der Verwaltung: Kennzahlen + Stammdaten-Formular. */
    public function show(array $args): void
    {
        AuthController::requireLogin();

        $event = self::load((int) ($args['id'] ?? 0));

        View::display('admin/events/form', [
            'title'  => (string) $event['name'],
            'event'  => Flash::oldInput() + $event,
            'stats'  => EventRepo::stats((int) $event['id']),
            'errors' => Flash::errors(),
            'isNew'  => false,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        [$data, $errors] = $this->validate();

        if ($errors === [] && $data['status'] !== 'beendet' && ($limit = \App\Core\License::eventLimitError()) !== null) {
            Flash::withInput($_POST, []);
            Flash::error($limit);
            Url::redirect('/admin/events/neu');
        }

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Flash::error('Bitte die markierten Felder prüfen.');
            Url::redirect('/admin/events/neu');
        }

        $data['slug']       = EventRepo::uniqueSlug($data['slug'] !== '' ? $data['slug'] : slugify($data['name']));
        $data['created_by'] = Auth::id();

        $id = Database::insert('events', $data);

        // Tage des Zeitraums gleich anlegen, damit der Aufbau nicht leer startet.
        EventRepo::ensureDays(EventRepo::find($id) ?? []);

        try {
            $this->handleImages($id, $data['slug']);
        } catch (RuntimeException $e) {
            Flash::error('Event angelegt, aber ein Bild wurde nicht übernommen: ' . $e->getMessage());
        }

        Audit::log('event_created', 'event', $id, (string) $data['name']);
        Flash::success('Event angelegt. Als Nächstes: Aufbau (Tage, Abschnitte, Wettkampfstätten) und Kategorien.');
        Url::redirect('/admin/events/' . $id . '/aufbau');
    }

    public function update(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $id    = (int) ($args['id'] ?? 0);
        $event = self::load($id);

        [$data, $errors] = $this->validate($id);

        // Ein beendetes Event wieder zu oeffnen zaehlt als weiteres aktives Event.
        if ($event['status'] === 'beendet' && $data['status'] !== 'beendet' && ($limit = \App\Core\License::eventLimitError($id)) !== null) {
            Flash::error($limit);
            Url::redirect('/admin/events/' . $id);
        }

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Flash::error('Bitte die markierten Felder prüfen.');
            Url::redirect('/admin/events/' . $id);
        }

        $data['slug']       = EventRepo::uniqueSlug($data['slug'] !== '' ? $data['slug'] : slugify($data['name']), $id);
        $data['updated_at'] = gmdate('Y-m-d H:i:s');

        Database::update('events', $id, $data);
        EventRepo::ensureDays($data + ['id' => $id]);

        try {
            $this->handleImages($id, $data['slug']);
        } catch (RuntimeException $e) {
            Flash::error('Ein Bild wurde nicht übernommen: ' . $e->getMessage());
        }

        if ($data['type'] !== $event['type']) {
            Flash::info(
                $data['type'] === 'gala'
                    ? 'Typ auf Gala umgestellt: Kämpfe werden jetzt als Fightcard manuell zusammengestellt. Bestehende Turnierkämpfe bleiben erhalten.'
                    : 'Typ auf Turnier umgestellt: je Kategorie lässt sich jetzt ein Turnierbaum erzeugen. Bestehende Fightcard-Kämpfe bleiben erhalten.'
            );
        }

        Audit::log('event_updated', 'event', $id, Audit::diff($event, $data));
        Flash::success('Event gespeichert.');
        Url::redirect('/admin/events/' . $id);
    }

    public function setStatus(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $id     = (int) ($args['id'] ?? 0);
        $event  = self::load($id);
        $status = post('status');

        if (!isset(EventRepo::STATUS[$status])) {
            Flash::error('Unbekannter Status.');
            Url::redirect('/admin/events/' . $id);
        }

        if ($event['status'] === 'beendet' && $status !== 'beendet' && ($limit = \App\Core\License::eventLimitError($id)) !== null) {
            Flash::error($limit);
            Url::redirect('/admin/events/' . $id);
        }

        Database::update('events', $id, ['status' => $status, 'updated_at' => gmdate('Y-m-d H:i:s')]);
        Audit::log('event_status', 'event', $id, $event['status'] . ' → ' . $status);
        Flash::success('Status: ' . EventRepo::STATUS[$status]);
        Url::redirectRaw((string) ($_SERVER['HTTP_REFERER'] ?? Url::to('/admin/events/' . $id)));
    }

    public function removeImage(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $id    = (int) ($args['id'] ?? 0);
        $event = self::load($id);
        $field = post('field');

        if (isset(self::IMAGE_FIELDS[$field]) && (string) $event[$field] !== '') {
            Upload::delete((string) $event[$field]);
            Database::update('events', $id, [$field => '']);
            Flash::success('Bild entfernt.');
        }

        Url::redirect('/admin/events/' . $id);
    }

    public function destroy(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $id    = (int) ($args['id'] ?? 0);
        $event = self::load($id);

        foreach (array_keys(self::IMAGE_FIELDS) as $field) {
            if ((string) $event[$field] !== '') {
                Upload::delete((string) $event[$field]);
            }
        }

        Database::run('DELETE FROM events WHERE id = ?', [$id]);
        Audit::log('event_deleted', 'event', $id, (string) $event['name']);
        Flash::success('Event „' . $event['name'] . '“ samt Anmeldungen und Kämpfen gelöscht.');
        Url::redirect('/admin/events');
    }

    // --------------------------------------------------------------- intern --

    private function empty(): array
    {
        return [
            'id' => 0, 'slug' => '', 'name' => '', 'type' => 'turnier', 'sport' => '', 'tagline' => '',
            'description' => '', 'starts_on' => '', 'ends_on' => '', 'doors_time' => '', 'start_time' => '',
            'venue_name' => '', 'venue_street' => '', 'venue_zip' => '', 'venue_city' => '',
            'status' => 'entwurf', 'registration_from' => '', 'registration_until' => '',
            'max_entries' => 0, 'entry_fee' => 0, 'ticket_url' => '', 'contact_email' => '',
            'contact_phone' => '', 'logo_path' => '', 'poster_path' => '', 'hero_path' => '', 'belt_path' => '',
            'published' => 0, 'show_entries' => 1, 'show_results' => 1, 'gym_registration' => 1,
            'short_name' => '', 'live_mode' => 0, 'default_bout_minutes' => 12, 'default_break_minutes' => 15,
            'show_countdown' => 1, 'show_map' => 1, 'location_note' => '', 'min_age_note' => '', 'ticket_note' => '',
            'tickets_json' => '[]', 'social_json' => '{}',
        ];
    }

    /** @return array{0:array<string,mixed>,1:array<string,string>} */
    private function validate(?int $id = null): array
    {
        $errors = [];
        $name   = post('name');

        if ($name === '') {
            $errors['name'] = 'Bitte einen Namen angeben.';
        }

        $type = post('type');

        if (!isset(EventRepo::TYPES[$type])) {
            $type = 'turnier';
        }

        $startsOn = parse_date(post('starts_on'));
        $endsOn   = parse_date(post('ends_on'));

        if ($startsOn === null) {
            $errors['starts_on'] = 'Bitte ein gültiges Datum angeben.';
        }

        if ($endsOn !== null && $startsOn !== null && $endsOn < $startsOn) {
            $errors['ends_on'] = 'Das Ende liegt vor dem Beginn.';
        }

        if ($endsOn === $startsOn) {
            $endsOn = null;
        }

        $email = post('contact_email');

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Keine gültige E-Mail-Adresse.';
        }

        $status = post('status');

        if (!isset(EventRepo::STATUS[$status])) {
            $status = 'entwurf';
        }

        $slug = slugify(post('slug'));

        $data = [
            'name'               => $name,
            'slug'               => $slug,
            'type'               => $type,
            'sport'              => post('sport'),
            'tagline'            => post('tagline'),
            'description'        => safe_html((string) ($_POST['description'] ?? '')),
            'starts_on'          => $startsOn ?? date('Y-m-d'),
            'ends_on'            => $endsOn,
            'doors_time'         => parse_time(post('doors_time')),
            'start_time'         => parse_time(post('start_time')),
            'venue_name'         => post('venue_name'),
            'venue_street'       => post('venue_street'),
            'venue_zip'          => post('venue_zip'),
            'venue_city'         => post('venue_city'),
            'status'             => $status,
            'registration_from'  => parse_date(post('registration_from')),
            'registration_until' => parse_date(post('registration_until')),
            'max_entries'        => max(0, post_int('max_entries')),
            'entry_fee'          => max(0, post_float('entry_fee')),
            'ticket_url'         => post('ticket_url'),
            'contact_email'      => $email,
            'contact_phone'      => post('contact_phone'),
            'published'          => post_bool('published'),
            'show_entries'       => post_bool('show_entries'),
            'show_results'       => post_bool('show_results'),
            'gym_registration'   => post_bool('gym_registration'),
            'short_name'            => mb_substr(post('short_name'), 0, 40),
            'live_mode'             => post_bool('live_mode'),
            'default_bout_minutes'  => max(1, post_int('default_bout_minutes', 12)),
            'default_break_minutes' => max(1, post_int('default_break_minutes', 15)),
            'show_countdown'        => post_bool('show_countdown'),
            'show_map'              => post_bool('show_map'),
            'location_note'         => post('location_note'),
            'min_age_note'          => post('min_age_note'),
            'ticket_note'           => post('ticket_note'),
            'tickets_json'          => self::ticketsFromPost(),
            'social_json'           => self::socialFromPost(),
        ];

        return [$data, $errors];
    }

    public const SOCIAL = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'];

    /** Ticketkategorien aus den Formularzeilen tickets[i][label|price|note|highlight]. */
    private static function ticketsFromPost(): string
    {
        $out = [];

        foreach ((array) ($_POST['tickets'] ?? []) as $row) {
            $row = (array) $row;

            if (trim((string) ($row['label'] ?? '')) === '') {
                continue;
            }

            $out[] = [
                'label'     => mb_substr(trim((string) $row['label']), 0, 60),
                'price'     => mb_substr(trim((string) ($row['price'] ?? '')), 0, 30),
                'note'      => mb_substr(trim((string) ($row['note'] ?? '')), 0, 200),
                'highlight' => !empty($row['highlight']),
            ];
        }

        return (string) json_encode($out, JSON_UNESCAPED_UNICODE);
    }

    private static function socialFromPost(): string
    {
        $out = [];

        foreach (array_keys(self::SOCIAL) as $net) {
            $url = trim((string) ($_POST['social'][$net] ?? ''));

            if ($url !== '') {
                $out[$net] = preg_match('#^https?://#i', $url) ? $url : 'https://' . $url;
            }
        }

        return (string) json_encode((object) $out, JSON_UNESCAPED_SLASHES);
    }

    private function handleImages(int $id, string $slug): void
    {
        foreach (self::IMAGE_FIELDS as $field => $maxWidth) {
            $key  = substr($field, 0, -5); // logo, poster, hero
            $file = $_FILES[$key] ?? null;

            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $path = Upload::image($file, 'events', $slug . '-' . $key, $maxWidth);

            if ($path === null) {
                continue;
            }

            $alt = (string) (Database::value("SELECT $field FROM events WHERE id = ?", [$id]) ?? '');

            if ($alt !== '') {
                Upload::delete($alt);
            }

            Database::update('events', $id, [$field => $path]);
        }
    }
}
