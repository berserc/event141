<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\GymAuth;
use App\Core\Upload;
use App\Core\Url;
use App\Core\View;
use App\Models\AthleteRepo;
use App\Models\EntryRepo;
use App\Models\EventRepo;
use App\Models\GymRepo;
use App\Models\Setting;
use RuntimeException;

/**
 * Gym-Bereich (/gym): Registrierung, eigene Sportler, Gym141-Kopplung,
 * Anmeldung der Sportler zu Events.
 */
final class GymAreaController
{
    // ------------------------------------------------------ Registrierung --

    public function showRegister(): void
    {
        if (Setting::get('gym_signup', '1') === '0') {
            Flash::info('Die Selbstregistrierung ist deaktiviert – bitte den Veranstalter kontaktieren.');
            Url::redirect('/gym/login');
        }

        View::display('gym/register', [
            'title'  => 'Gym registrieren',
            'gym'    => Flash::oldInput() + GymAdminController::empty(),
            'errors' => Flash::errors(),
        ], 'layouts/gym');
    }

    public function register(): void
    {
        Csrf::verify();

        if (Setting::get('gym_signup', '1') === '0') {
            Url::redirect('/gym/login');
        }

        [$data, $errors] = GymAdminController::validate(null, true);

        $password = (string) ($_POST['login_password'] ?? '');
        $confirm  = (string) ($_POST['login_password_confirm'] ?? '');

        if ($data['login_email'] === '') {
            $errors['login_email'] = 'Bitte eine E-Mail-Adresse für die Anmeldung angeben.';
        }

        if (mb_strlen($password) < Auth::MIN_PASSWORD_LENGTH) {
            $errors['login_password'] = 'Das Passwort muss mindestens ' . Auth::MIN_PASSWORD_LENGTH . ' Zeichen haben.';
        } elseif ($password !== $confirm) {
            $errors['login_password_confirm'] = 'Die Wiederholung stimmt nicht überein.';
        }

        // Einfacher Spam-Schutz: verstecktes Feld muss leer bleiben.
        if (post('website_url') !== '') {
            $errors['name'] = 'Registrierung abgelehnt.';
        }

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Flash::error('Bitte die markierten Felder prüfen.');
            Url::redirect('/gym/registrieren');
        }

        $data['slug']                = GymRepo::uniqueSlug(slugify($data['name']));
        $data['status']              = 'neu';
        $data['login_password_hash'] = password_hash($password, PASSWORD_DEFAULT);

        if ($data['email'] === '') {
            $data['email'] = $data['login_email'];
        }

        $id = Database::insert('gyms', $data);

        Audit::logAs(null, 'gym:' . $data['login_email'], 'gym_registered', 'gym', $id, (string) $data['name']);
        GymAuth::attempt($data['login_email'], $password);

        Flash::success('Willkommen! Das Gym ist registriert. Als Nächstes Sportler anlegen – oder direkt aus Gym141 holen.');
        Url::redirect('/gym');
    }

    // -------------------------------------------------------------- Login --

    public function showLogin(): void
    {
        if (GymAuth::check()) {
            Url::redirect('/gym');
        }

        View::display('gym/login', [
            'title'  => 'Gym-Anmeldung',
            'old'    => Flash::oldInput(),
            'signup' => Setting::get('gym_signup', '1') !== '0',
        ], 'layouts/gym');
    }

    public function login(): void
    {
        Csrf::verify();

        $ip       = client_ip();
        $email    = post('email');
        $password = (string) ($_POST['password'] ?? '');

        if (Auth::isThrottled($ip)) {
            Flash::error('Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.');
            Url::redirect('/gym/login');
        }

        if (!GymAuth::attempt($email, $password)) {
            Auth::recordFailedAttempt($ip, 'gym:' . $email);
            Flash::error('E-Mail oder Passwort ist falsch – oder das Gym ist gesperrt.');
            Flash::withInput(['email' => $email]);
            Url::redirect('/gym/login');
        }

        Auth::clearAttempts($ip);
        Url::redirect('/gym');
    }

    public function logout(): void
    {
        Csrf::verify();
        $wasAdmin = GymAuth::isAdminView();
        GymAuth::logout();
        Url::redirect($wasAdmin ? '/admin/gyms' : '/gym/login');
    }

    // --------------------------------------------------------------- Seiten --

    public function home(): void
    {
        $gym = self::requireGym();

        View::display('gym/home', [
            'title'    => $gym['name'],
            'gym'      => $gym,
            'open'     => EventRepo::openForRegistration(),
            'entries'  => EntryRepo::forGym((int) $gym['id']),
            'athletes' => AthleteRepo::forGym((int) $gym['id'], true),
        ], 'layouts/gym');
    }

    public function athletes(): void
    {
        $gym = self::requireGym();

        View::display('gym/athletes', [
            'title'    => 'Sportler',
            'gym'      => $gym,
            'athletes' => AthleteRepo::forGym((int) $gym['id']),
        ], 'layouts/gym');
    }

    public function athleteCreate(): void
    {
        $gym = self::requireGym();

        View::display('gym/athlete-form', [
            'title'   => 'Neuer Sportler',
            'gym'     => $gym,
            'athlete' => Flash::oldInput() + AthleteAdminController::empty(),
            'errors'  => Flash::errors(),
            'isNew'   => true,
        ], 'layouts/gym');
    }

    public function athleteEdit(array $args): void
    {
        $gym     = self::requireGym();
        $athlete = $this->ownAthlete($gym, (int) ($args['id'] ?? 0));

        View::display('gym/athlete-form', [
            'title'   => person_name($athlete),
            'gym'     => $gym,
            'athlete' => Flash::oldInput() + $athlete,
            'errors'  => Flash::errors(),
            'isNew'   => false,
        ], 'layouts/gym');
    }

    public function athleteStore(): void
    {
        $gym = self::requireGym();
        Csrf::verify();

        [$data, $errors] = AthleteAdminController::validate();

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Flash::error('Bitte die markierten Felder prüfen.');
            Url::redirect('/gym/sportler/neu');
        }

        $data['gym_id'] = (int) $gym['id'];
        $id             = Database::insert('athletes', $data);

        try {
            AthleteAdminController::handlePhoto($id);
        } catch (RuntimeException $e) {
            Flash::error('Foto nicht übernommen: ' . $e->getMessage());
        }

        Flash::success('Sportler angelegt.');
        Url::redirect('/gym/sportler');
    }

    public function athleteUpdate(array $args): void
    {
        $gym     = self::requireGym();
        $athlete = $this->ownAthlete($gym, (int) ($args['id'] ?? 0));
        Csrf::verify();

        [$data, $errors] = AthleteAdminController::validate();

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Flash::error('Bitte die markierten Felder prüfen.');
            Url::redirect('/gym/sportler/' . $athlete['id']);
        }

        $data['updated_at'] = gmdate('Y-m-d H:i:s');
        Database::update('athletes', (int) $athlete['id'], $data);

        try {
            AthleteAdminController::handlePhoto((int) $athlete['id']);
        } catch (RuntimeException $e) {
            Flash::error('Foto nicht übernommen: ' . $e->getMessage());
        }

        Flash::success('Sportler gespeichert.');
        Url::redirect('/gym/sportler');
    }

    public function athleteDelete(array $args): void
    {
        $gym     = self::requireGym();
        $athlete = $this->ownAthlete($gym, (int) ($args['id'] ?? 0));
        Csrf::verify();

        $entries = (int) Database::value('SELECT COUNT(*) FROM event_entries WHERE athlete_id = ?', [(int) $athlete['id']]);

        if ($entries > 0) {
            Flash::error('Der Sportler hat Anmeldungen – bitte zuerst abmelden oder den Sportler deaktivieren.');
            Url::redirect('/gym/sportler/' . $athlete['id']);
        }

        if ((string) $athlete['photo_path'] !== '') {
            Upload::delete((string) $athlete['photo_path']);
        }

        Database::run('DELETE FROM athletes WHERE id = ?', [(int) $athlete['id']]);
        Flash::success('Sportler gelöscht.');
        Url::redirect('/gym/sportler');
    }

    // -------------------------------------------------------------- Gym141 --

    public function gym141(): void
    {
        $gym = self::requireGym();

        [$members, $error] = GymAdminController::fetchMembers($gym, query('q'));

        View::display('gym/gym141', [
            'title'   => 'Gym141-Kopplung',
            'gym'     => $gym,
            'members' => $members,
            'error'   => $error,
            'known'   => GymAdminController::knownGym141Ids((int) $gym['id']),
            'q'       => query('q'),
            'apiKeys' => \App\Models\ApiKeyRepo::all((int) $gym['id']),
            'newKey'  => (static function () { $k = $_SESSION['new_gym_api_key'] ?? null; unset($_SESSION['new_gym_api_key']); return is_string($k) ? $k : null; })(),
        ], 'layouts/gym');
    }

    /** Eigener API-Schluessel: damit meldet Gym141 (oder ein anderes System) Sportler von dort aus an. */
    public function createApiKey(): void
    {
        $gym = self::requireGym();
        Csrf::verify();

        if (count(\App\Models\ApiKeyRepo::all((int) $gym['id'])) >= 5) {
            Flash::error('Maximal fünf Schlüssel je Gym – bitte zuerst einen alten löschen.');
            Url::redirect('/gym/gym141');
        }

        $created = \App\Models\ApiKeyRepo::create(post('name') ?: 'Gym141', 'write', (int) $gym['id'], null, null);
        $_SESSION['new_gym_api_key'] = $created['key'];

        Audit::logAs(null, 'gym:' . $gym['login_email'], 'api_key_created', 'gym', (int) $gym['id']);
        Url::redirect('/gym/gym141');
    }

    public function deleteApiKey(array $args): void
    {
        $gym = self::requireGym();
        Csrf::verify();

        Database::run('DELETE FROM api_keys WHERE id = ? AND gym_id = ?', [(int) ($args['id'] ?? 0), (int) $gym['id']]);
        Flash::success('Schlüssel gelöscht.');
        Url::redirect('/gym/gym141');
    }

    public function gym141Connect(): void
    {
        $gym = self::requireGym();
        Csrf::verify();

        GymAdminController::connect($gym, post('gym141_url'), post('gym141_username'), (string) ($_POST['gym141_password'] ?? ''));
        Url::redirect('/gym/gym141');
    }

    public function gym141Disconnect(): void
    {
        $gym = self::requireGym();
        Csrf::verify();

        GymAdminController::disconnect($gym);
        Url::redirect('/gym/gym141');
    }

    public function gym141Import(): void
    {
        $gym = self::requireGym();
        Csrf::verify();

        $ok = GymAdminController::import($gym);
        Url::redirect($ok ? '/gym/sportler' : '/gym/gym141');
    }

    // ----------------------------------------------------- Event-Anmeldung --

    public function event(array $args): void
    {
        $gym   = self::requireGym();
        $event = EventRepo::find((int) ($args['id'] ?? 0));

        if ($event === null || ((int) $event['published'] !== 1 && !EventRepo::registrationOpen($event) && !GymAuth::isAdminView())) {
            Flash::error('Event nicht gefunden.');
            Url::redirect('/gym');
        }

        View::display('gym/event', [
            'title'      => $event['name'],
            'gym'        => $gym,
            'event'      => $event,
            'open'       => EventRepo::registrationOpen($event),
            'categories' => EventRepo::categories((int) $event['id']),
            'athletes'   => AthleteRepo::forGym((int) $gym['id'], true),
            'entries'    => EntryRepo::forGymEvent((int) $gym['id'], (int) $event['id']),
        ], 'layouts/gym');
    }

    /** Sportler (mehrere) in einer Kategorie anmelden. */
    public function enter(array $args): void
    {
        $gym   = self::requireGym();
        $event = EventRepo::find((int) ($args['id'] ?? 0));
        Csrf::verify();

        if ($event === null || !EventRepo::registrationOpen($event)) {
            Flash::error('Die Anmeldung für dieses Event ist nicht (mehr) möglich.');
            Url::redirect('/gym');
        }

        $categoryId = post_id('category_id');
        $categories = EventRepo::categories((int) $event['id']);
        $category   = null;

        foreach ($categories as $c) {
            if ((int) $c['id'] === $categoryId) {
                $category = $c;
            }
        }

        if ($categories !== [] && $category === null) {
            Flash::error('Bitte eine Kategorie wählen.');
            Url::redirect('/gym/event/' . $event['id']);
        }

        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['athlete_ids'] ?? []))));

        if ($ids === []) {
            Flash::error('Bitte mindestens einen Sportler auswählen.');
            Url::redirect('/gym/event/' . $event['id']);
        }

        $max     = (int) $event['max_entries'];
        $aktuell = (int) Database::value(
            "SELECT COUNT(*) FROM event_entries WHERE event_id = ? AND status IN ('angemeldet', 'bestaetigt')",
            [(int) $event['id']]
        );

        $neu = 0;
        $dup = 0;
        $warn = [];

        foreach ($ids as $athleteId) {
            $athlete = Database::one('SELECT * FROM athletes WHERE id = ? AND gym_id = ? AND active = 1', [$athleteId, (int) $gym['id']]);

            if ($athlete === null) {
                continue;
            }

            if ($max > 0 && $aktuell + $neu >= $max) {
                $warn[] = 'Teilnehmerlimit erreicht – ' . person_name($athlete) . ' nicht angemeldet.';
                continue;
            }

            $exists = Database::one(
                'SELECT id, status FROM event_entries WHERE event_id = ? AND athlete_id = ? AND category_id IS ?',
                [(int) $event['id'], $athleteId, $category !== null ? (int) $category['id'] : null]
            );

            if ($exists !== null) {
                if ($exists['status'] === 'abgemeldet') {
                    Database::update('event_entries', (int) $exists['id'], ['status' => 'angemeldet', 'updated_at' => gmdate('Y-m-d H:i:s')]);
                    $neu++;
                } else {
                    $dup++;
                }

                continue;
            }

            // Hinweise (kein Ausschluss): Alter/Gewicht passen nicht zur Kategorie.
            if ($category !== null) {
                $alter = age_from($athlete['birthdate'], (string) $event['starts_on']);

                if ($alter !== null && (($category['age_min'] !== null && $alter < (int) $category['age_min']) || ($category['age_max'] !== null && $alter > (int) $category['age_max']))) {
                    $warn[] = person_name($athlete) . ' (' . $alter . ' J.) liegt außerhalb des Alters der Kategorie.';
                }

                if ($athlete['weight'] !== null && $category['weight_max'] !== null && (float) $athlete['weight'] > (float) $category['weight_max']) {
                    $warn[] = person_name($athlete) . ' (' . format_weight($athlete['weight']) . ') ist schwerer als das Limit der Kategorie.';
                }
            }

            Database::insert('event_entries', [
                'event_id'    => (int) $event['id'],
                'athlete_id'  => $athleteId,
                'gym_id'      => (int) $gym['id'],
                'category_id' => $category !== null ? (int) $category['id'] : null,
                'status'      => 'angemeldet',
                'source'      => 'gym',
                'note'        => post('note'),
            ]);
            $neu++;
        }

        Audit::logAs(null, 'gym:' . $gym['login_email'], 'gym_entries', 'event', (int) $event['id'], $gym['name'] . ': ' . $neu . ' angemeldet');

        if ($neu > 0) {
            Flash::success($neu . ' Sportler angemeldet' . ($category !== null ? ' (' . $category['name'] . ')' : '') . '. Der Veranstalter bestätigt die Anmeldung.');
        }

        if ($dup > 0) {
            Flash::info($dup . ' Sportler waren in dieser Kategorie schon angemeldet.');
        }

        foreach ($warn as $w) {
            Flash::error($w);
        }

        Url::redirect('/gym/event/' . $event['id']);
    }

    public function withdraw(array $args): void
    {
        $gym   = self::requireGym();
        $entry = Database::one('SELECT * FROM event_entries WHERE id = ? AND gym_id = ?', [(int) ($args['eid'] ?? 0), (int) $gym['id']]);
        Csrf::verify();

        if ($entry === null) {
            Url::redirect('/gym');
        }

        $event = EventRepo::find((int) $entry['event_id']);

        if ($event === null || !EventRepo::registrationOpen($event)) {
            Flash::error('Abmeldung nur, solange die Anmeldung offen ist – bitte den Veranstalter kontaktieren.');
            Url::redirect('/gym/event/' . $entry['event_id']);
        }

        if ((string) $entry['status'] === 'angemeldet' && (string) $entry['source'] === 'gym') {
            Database::run('DELETE FROM event_entries WHERE id = ?', [(int) $entry['id']]);
        } else {
            Database::update('event_entries', (int) $entry['id'], ['status' => 'abgemeldet', 'updated_at' => gmdate('Y-m-d H:i:s')]);
        }

        Flash::success('Abgemeldet.');
        Url::redirect('/gym/event/' . $entry['event_id']);
    }

    // -------------------------------------------------------------- Profil --

    public function profile(): void
    {
        $gym = self::requireGym();

        View::display('gym/profile', [
            'title'  => 'Gym-Daten',
            'gym'    => Flash::oldInput() + $gym,
            'errors' => Flash::errors(),
        ], 'layouts/gym');
    }

    public function updateProfile(): void
    {
        $gym = self::requireGym();
        Csrf::verify();

        [$data, $errors] = GymAdminController::validate((int) $gym['id'], true);

        if ($data['login_email'] === '') {
            $errors['login_email'] = 'Die Anmelde-E-Mail darf nicht leer sein.';
        }

        $password = (string) ($_POST['login_password'] ?? '');

        if ($password !== '') {
            if (!password_verify((string) ($_POST['current_password'] ?? ''), (string) $gym['login_password_hash'])) {
                $errors['current_password'] = 'Das aktuelle Passwort stimmt nicht.';
            } elseif (mb_strlen($password) < Auth::MIN_PASSWORD_LENGTH) {
                $errors['login_password'] = 'Mindestens ' . Auth::MIN_PASSWORD_LENGTH . ' Zeichen.';
            } else {
                $data['login_password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Flash::error('Bitte die markierten Felder prüfen.');
            Url::redirect('/gym/profil');
        }

        $data['updated_at'] = gmdate('Y-m-d H:i:s');
        Database::update('gyms', (int) $gym['id'], $data);

        try {
            GymAdminController::handleLogo((int) $gym['id'], (string) $gym['slug']);
        } catch (RuntimeException $e) {
            Flash::error('Logo nicht übernommen: ' . $e->getMessage());
        }

        Flash::success('Gym-Daten gespeichert.');
        Url::redirect('/gym/profil');
    }

    // ---------------------------------------------------------------- intern --

    public static function requireGym(): array
    {
        $gym = GymAuth::gym();

        if ($gym === null) {
            Flash::error('Bitte als Gym anmelden.');
            Url::redirect('/gym/login');
        }

        return $gym;
    }

    private function ownAthlete(array $gym, int $id): array
    {
        $athlete = Database::one('SELECT * FROM athletes WHERE id = ? AND gym_id = ?', [$id, (int) $gym['id']]);

        if ($athlete === null) {
            Flash::error('Sportler nicht gefunden.');
            Url::redirect('/gym/sportler');
        }

        return $athlete;
    }
}
