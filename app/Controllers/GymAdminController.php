<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Gym141Client;
use App\Core\GymAuth;
use App\Core\Upload;
use App\Core\Url;
use App\Core\View;
use App\Models\AthleteRepo;
use App\Models\EntryRepo;
use App\Models\GymRepo;
use RuntimeException;

/** Gyms/Vereine in der Verwaltung inkl. Gym141-Kopplung. */
final class GymAdminController
{
    public function index(): void
    {
        AuthController::requireLogin();

        View::display('admin/gyms/index', [
            'title'  => 'Gyms & Vereine',
            'gyms'   => GymRepo::all(query('q'), query('status')),
            'q'      => query('q'),
            'status' => query('status'),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        AuthController::requireWrite();

        View::display('admin/gyms/form', [
            'title'    => 'Neues Gym',
            'gym'      => Flash::oldInput() + self::empty(),
            'athletes' => [],
            'entries'  => [],
            'errors'   => Flash::errors(),
            'isNew'    => true,
        ], 'layouts/admin');
    }

    public function edit(array $args): void
    {
        AuthController::requireLogin();

        $gym = self::load((int) ($args['id'] ?? 0));

        View::display('admin/gyms/form', [
            'title'    => (string) $gym['name'],
            'gym'      => Flash::oldInput() + $gym,
            'athletes' => AthleteRepo::forGym((int) $gym['id']),
            'entries'  => EntryRepo::forGym((int) $gym['id']),
            'errors'   => Flash::errors(),
            'isNew'    => false,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        [$data, $errors] = self::validate(null);

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Flash::error('Bitte die markierten Felder prüfen.');
            Url::redirect('/admin/gyms/neu');
        }

        $data['slug']   = GymRepo::uniqueSlug(slugify($data['name']));
        $data['status'] = 'bestaetigt';

        $password = (string) ($_POST['login_password'] ?? '');

        if ($password !== '') {
            $data['login_password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $id = Database::insert('gyms', $data);

        try {
            self::handleLogo($id, $data['slug']);
        } catch (RuntimeException $e) {
            Flash::error('Logo nicht übernommen: ' . $e->getMessage());
        }

        Audit::log('gym_created', 'gym', $id, (string) $data['name']);
        Flash::success('Gym angelegt.');
        Url::redirect('/admin/gyms/' . $id);
    }

    public function update(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $id  = (int) ($args['id'] ?? 0);
        $gym = self::load($id);

        [$data, $errors] = self::validate($id);

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Flash::error('Bitte die markierten Felder prüfen.');
            Url::redirect('/admin/gyms/' . $id);
        }

        $status = post('status');

        if (isset(GymRepo::STATUS[$status])) {
            $data['status'] = $status;
        }

        $password = (string) ($_POST['login_password'] ?? '');

        if ($password !== '') {
            if (mb_strlen($password) < Auth::MIN_PASSWORD_LENGTH) {
                Flash::error('Das Passwort muss mindestens ' . Auth::MIN_PASSWORD_LENGTH . ' Zeichen haben.');
                Url::redirect('/admin/gyms/' . $id);
            }

            $data['login_password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $data['updated_at'] = gmdate('Y-m-d H:i:s');
        Database::update('gyms', $id, $data);

        try {
            self::handleLogo($id, (string) $gym['slug']);
        } catch (RuntimeException $e) {
            Flash::error('Logo nicht übernommen: ' . $e->getMessage());
        }

        Audit::log('gym_updated', 'gym', $id, Audit::diff($gym, $data));
        Flash::success('Gym gespeichert.' . ($password !== '' ? ' Neues Login-Passwort gesetzt.' : ''));
        Url::redirect('/admin/gyms/' . $id);
    }

    public function setStatus(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $id     = (int) ($args['id'] ?? 0);
        $gym    = self::load($id);
        $status = post('status');

        if (isset(GymRepo::STATUS[$status])) {
            Database::update('gyms', $id, ['status' => $status, 'updated_at' => gmdate('Y-m-d H:i:s')]);
            Audit::log('gym_status', 'gym', $id, $gym['status'] . ' → ' . $status);
            Flash::success($gym['name'] . ': ' . GymRepo::STATUS[$status]);
        }

        Url::redirectRaw((string) ($_SERVER['HTTP_REFERER'] ?? Url::to('/admin/gyms')));
    }

    /** Support: als dieses Gym in den Gym-Bereich wechseln. */
    public function loginAs(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $gym = self::load((int) ($args['id'] ?? 0));
        GymAuth::loginAs((int) $gym['id']);
        Audit::log('gym_login_as', 'gym', (int) $gym['id']);
        Url::redirect('/gym');
    }

    public function destroy(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $id  = (int) ($args['id'] ?? 0);
        $gym = self::load($id);

        $entries = (int) Database::value('SELECT COUNT(*) FROM event_entries WHERE gym_id = ?', [$id]);

        if ($entries > 0) {
            Flash::error('Das Gym hat noch Anmeldungen – bitte sperren statt löschen.');
            Url::redirect('/admin/gyms/' . $id);
        }

        Database::update('gyms', $id, ['deleted_at' => gmdate('Y-m-d H:i:s'), 'login_email' => '', 'status' => 'gesperrt']);
        Audit::log('gym_deleted', 'gym', $id, (string) $gym['name']);
        Flash::success('Gym gelöscht.');
        Url::redirect('/admin/gyms');
    }

    // ------------------------------------------------------------- Gym141 --

    public function gym141Connect(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $gym = self::load((int) ($args['id'] ?? 0));
        self::connect($gym, post('gym141_url'), post('gym141_username'), (string) ($_POST['gym141_password'] ?? ''));
        Url::redirect('/admin/gyms/' . $gym['id'] . '/gym141');
    }

    public function gym141Disconnect(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $gym = self::load((int) ($args['id'] ?? 0));
        self::disconnect($gym);
        Url::redirect('/admin/gyms/' . $gym['id']);
    }

    /** Mitgliederliste aus Gym141 anzeigen (zum Auswaehlen). */
    public function gym141Members(array $args): void
    {
        AuthController::requireLogin();

        $gym = self::load((int) ($args['id'] ?? 0));

        [$members, $error] = self::fetchMembers($gym, query('q'));

        View::display('admin/gyms/gym141', [
            'title'    => $gym['name'] . ' – Gym141',
            'gym'      => $gym,
            'members'  => $members,
            'error'    => $error,
            'known'    => self::knownGym141Ids((int) $gym['id']),
            'q'        => query('q'),
            'backUrl'  => '/admin/gyms/' . $gym['id'],
            'formUrl'  => '/admin/gyms/' . $gym['id'] . '/gym141',
            'layout'   => 'admin',
        ], 'layouts/admin');
    }

    public function gym141Import(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $gym    = self::load((int) ($args['id'] ?? 0));
        $result = self::import($gym);
        Url::redirect($result ? '/admin/gyms/' . $gym['id'] : '/admin/gyms/' . $gym['id'] . '/gym141');
    }

    // ---------------------------------------- gemeinsam mit dem Gym-Bereich --

    /** Verbindung herstellen: Login bei Gym141, Token speichern. */
    public static function connect(array $gym, string $url, string $username, string $password): bool
    {
        try {
            $client = new Gym141Client($url);
            $login  = $client->login($username, $password);
        } catch (RuntimeException $e) {
            Flash::error('Verbindung fehlgeschlagen: ' . $e->getMessage());

            return false;
        }

        Database::update('gyms', (int) $gym['id'], [
            'gym141_url'   => $client->baseUrl(),
            'gym141_token' => $login['token'],
            'gym141_user'  => (string) ($login['user']['username'] ?? $username),
            'gym141_club'  => (string) ($login['club']['name'] ?? ''),
            'updated_at'   => gmdate('Y-m-d H:i:s'),
        ]);

        Audit::log('gym141_connected', 'gym', (int) $gym['id'], $client->baseUrl());
        Flash::success('Mit Gym141 verbunden: ' . ($login['club']['name'] ?? $client->baseUrl()) . '. Jetzt Mitglieder auswählen.');

        return true;
    }

    public static function disconnect(array $gym): void
    {
        if ((string) $gym['gym141_token'] !== '' && (string) $gym['gym141_url'] !== '') {
            try {
                (new Gym141Client((string) $gym['gym141_url'], (string) $gym['gym141_token']))->logout();
            } catch (RuntimeException) {
                // egal – Token wird lokal verworfen
            }
        }

        Database::update('gyms', (int) $gym['id'], [
            'gym141_token' => '',
            'gym141_user'  => '',
            'updated_at'   => gmdate('Y-m-d H:i:s'),
        ]);

        Audit::log('gym141_disconnected', 'gym', (int) $gym['id']);
        Flash::success('Verbindung zu Gym141 getrennt. Bereits übernommene Sportler bleiben erhalten.');
    }

    /** @return array{0:list<array<string,mixed>>,1:string} Mitglieder + Fehlertext */
    public static function fetchMembers(array $gym, string $search = ''): array
    {
        if ((string) $gym['gym141_token'] === '' || (string) $gym['gym141_url'] === '') {
            return [[], ''];
        }

        try {
            $client  = new Gym141Client((string) $gym['gym141_url'], (string) $gym['gym141_token']);
            $members = $client->members($search);

            usort($members, static fn (array $a, array $b): int => strcasecmp(
                $a['last_name'] . ' ' . $a['first_name'],
                $b['last_name'] . ' ' . $b['first_name']
            ));

            return [$members, ''];
        } catch (RuntimeException $e) {
            return [[], $e->getMessage()];
        }
    }

    /** Ausgewaehlte Gym141-Mitglieder als Sportler uebernehmen. */
    public static function import(array $gym): bool
    {
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['member_ids'] ?? []))));

        if ($ids === []) {
            Flash::error('Bitte mindestens ein Mitglied auswählen.');

            return false;
        }

        [$members, $error] = self::fetchMembers($gym);

        if ($error !== '') {
            Flash::error('Gym141: ' . $error);

            return false;
        }

        $result = AthleteRepo::importFromGym141((int) $gym['id'], $members, $ids);

        Database::update('gyms', (int) $gym['id'], ['gym141_synced_at' => gmdate('Y-m-d H:i:s')]);
        Audit::log('gym141_import', 'gym', (int) $gym['id'], $result['created'] . ' neu, ' . $result['updated'] . ' aktualisiert');
        Flash::success(sprintf('%d Sportler neu übernommen, %d aktualisiert.', $result['created'], $result['updated']));

        return true;
    }

    /** @return array<int,int> gym141_member_id => athlete_id */
    public static function knownGym141Ids(int $gymId): array
    {
        $out = [];

        foreach (Database::all('SELECT id, gym141_member_id FROM athletes WHERE gym_id = ? AND gym141_member_id IS NOT NULL', [$gymId]) as $r) {
            $out[(int) $r['gym141_member_id']] = (int) $r['id'];
        }

        return $out;
    }

    // ---------------------------------------------------------------- intern --

    public static function load(int $id): array
    {
        $gym = GymRepo::find($id);

        if ($gym === null) {
            http_response_code(404);
            View::display('errors/404-admin', ['title' => 'Nicht gefunden'], 'layouts/admin');
            exit;
        }

        return $gym;
    }

    public static function empty(): array
    {
        return [
            'id' => 0, 'name' => '', 'short_name' => '', 'slug' => '', 'street' => '', 'zip' => '', 'city' => '',
            'country' => 'AT', 'contact_name' => '', 'email' => '', 'phone' => '', 'website' => '', 'logo_path' => '',
            'status' => 'neu', 'login_email' => '', 'gym141_url' => '', 'gym141_token' => '', 'gym141_user' => '',
            'gym141_club' => '', 'gym141_synced_at' => null, 'note' => '', 'login_last_at' => null, 'created_at' => '',
        ];
    }

    /**
     * Gemeinsame Validierung fuer Verwaltung und Gym-Bereich.
     *
     * @return array{0:array<string,mixed>,1:array<string,string>}
     */
    public static function validate(?int $id, bool $selfService = false): array
    {
        $errors = [];
        $name   = post('name');

        if ($name === '') {
            $errors['name'] = 'Bitte den Namen des Gyms/Vereins angeben.';
        }

        $email = post('email');

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Keine gültige E-Mail-Adresse.';
        }

        $loginEmail = post('login_email');

        if ($loginEmail !== '' && !filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['login_email'] = 'Keine gültige E-Mail-Adresse.';
        } elseif (GymRepo::loginEmailTaken($loginEmail, $id)) {
            $errors['login_email'] = 'Mit dieser E-Mail-Adresse gibt es schon ein Gym-Konto.';
        }

        $data = [
            'name'         => $name,
            'short_name'   => mb_substr(post('short_name'), 0, 12),
            'street'       => post('street'),
            'zip'          => post('zip'),
            'city'         => post('city'),
            'country'      => strtoupper(mb_substr(post('country', 'AT'), 0, 2)) ?: 'AT',
            'contact_name' => post('contact_name'),
            'email'        => $email,
            'phone'        => post('phone'),
            'website'      => post('website'),
            'login_email'  => $loginEmail,
        ];

        if (!$selfService) {
            $data['note'] = post('note');
        }

        return [$data, $errors];
    }

    public static function handleLogo(int $id, string $slug): void
    {
        $file = $_FILES['logo'] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $path = Upload::image($file, 'gyms', $slug . '-logo', 500);

        if ($path === null) {
            return;
        }

        $alt = (string) (Database::value('SELECT logo_path FROM gyms WHERE id = ?', [$id]) ?? '');

        if ($alt !== '') {
            Upload::delete($alt);
        }

        Database::update('gyms', $id, ['logo_path' => $path]);
    }
}
