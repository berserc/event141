<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Upload;
use App\Core\Url;
use App\Core\View;
use App\Models\AthleteRepo;
use App\Models\EntryRepo;
use App\Models\GymRepo;
use RuntimeException;

/** Sportler in der Verwaltung (alle Gyms). */
final class AthleteAdminController
{
    public function index(): void
    {
        AuthController::requireLogin();

        View::display('admin/athletes/index', [
            'title'    => t('Sportler'),
            'athletes' => AthleteRepo::search(query('q'), (int) query('gym', '0'), 500),
            'gyms'     => GymRepo::options(),
            'q'        => query('q'),
            'gymId'    => (int) query('gym', '0'),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        AuthController::requireWrite();

        View::display('admin/athletes/form', [
            'title'   => t('Neuer Sportler'),
            'athlete' => Flash::oldInput() + self::empty() + ['gym_id' => (int) query('gym', '0')],
            'gyms'    => GymRepo::options(),
            'entries' => [],
            'errors'  => Flash::errors(),
            'isNew'   => true,
            'backUrl' => '/admin/sportler',
            'formUrl' => '/admin/sportler',
        ], 'layouts/admin');
    }

    public function edit(array $args): void
    {
        AuthController::requireLogin();

        $athlete = AthleteRepo::find((int) ($args['id'] ?? 0));

        if ($athlete === null) {
            Flash::error(t('Sportler nicht gefunden.'));
            Url::redirect('/admin/sportler');
        }

        View::display('admin/athletes/form', [
            'title'   => person_name($athlete),
            'athlete' => Flash::oldInput() + $athlete,
            'gyms'    => GymRepo::options(),
            'entries' => Database::all(
                'SELECT x.*, e.name AS event_name, e.id AS event_id, e.starts_on, c.name AS category_name
                   FROM event_entries x JOIN events e ON e.id = x.event_id
                   LEFT JOIN event_categories c ON c.id = x.category_id
                  WHERE x.athlete_id = ? ORDER BY e.starts_on DESC',
                [(int) $athlete['id']]
            ),
            'errors'  => Flash::errors(),
            'isNew'   => false,
            'backUrl' => '/admin/sportler',
            'formUrl' => '/admin/sportler/' . $athlete['id'],
        ], 'layouts/admin');
    }

    public function store(): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        [$data, $errors] = self::validate();

        $gym = GymRepo::find(post_int('gym_id'));

        if ($gym === null) {
            $errors['gym_id'] = t('Bitte ein Gym wählen.');
        }

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Flash::error(t('Bitte die markierten Felder prüfen.'));
            Url::redirect('/admin/sportler/neu');
        }

        $data['gym_id'] = (int) $gym['id'];
        $id             = Database::insert('athletes', $data);

        try {
            self::handlePhoto($id);
        } catch (RuntimeException $e) {
            Flash::error(t('Foto nicht übernommen: %s', $e->getMessage()));
        }

        Audit::log('athlete_created', 'athlete', $id, person_name($data));
        Flash::success(t('Sportler angelegt.'));
        Url::redirect('/admin/sportler/' . $id);
    }

    public function update(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $id      = (int) ($args['id'] ?? 0);
        $athlete = AthleteRepo::find($id);

        if ($athlete === null) {
            Flash::error(t('Sportler nicht gefunden.'));
            Url::redirect('/admin/sportler');
        }

        [$data, $errors] = self::validate();

        $gym = GymRepo::find(post_int('gym_id'));

        if ($gym !== null) {
            $data['gym_id'] = (int) $gym['id'];
        }

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Flash::error(t('Bitte die markierten Felder prüfen.'));
            Url::redirect('/admin/sportler/' . $id);
        }

        $data['updated_at'] = gmdate('Y-m-d H:i:s');
        Database::update('athletes', $id, $data);

        // Gym-Wechsel: bestehende Anmeldungen mitziehen.
        if (isset($data['gym_id']) && $data['gym_id'] !== (int) $athlete['gym_id']) {
            Database::run('UPDATE event_entries SET gym_id = ? WHERE athlete_id = ?', [$data['gym_id'], $id]);
        }

        try {
            self::handlePhoto($id);
        } catch (RuntimeException $e) {
            Flash::error(t('Foto nicht übernommen: %s', $e->getMessage()));
        }

        Audit::log('athlete_updated', 'athlete', $id, Audit::diff($athlete, $data));
        Flash::success(t('Sportler gespeichert.'));
        Url::redirect('/admin/sportler/' . $id);
    }

    public function destroy(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $id      = (int) ($args['id'] ?? 0);
        $athlete = AthleteRepo::find($id);

        if ($athlete === null) {
            Url::redirect('/admin/sportler');
        }

        $entries = (int) Database::value('SELECT COUNT(*) FROM event_entries WHERE athlete_id = ?', [$id]);

        if ($entries > 0) {
            Flash::error(t('Der Sportler hat Anmeldungen – bitte stattdessen deaktivieren.'));
            Url::redirect('/admin/sportler/' . $id);
        }

        if ((string) $athlete['photo_path'] !== '') {
            Upload::delete((string) $athlete['photo_path']);
        }

        Database::run('DELETE FROM athletes WHERE id = ?', [$id]);
        Audit::log('athlete_deleted', 'athlete', $id, person_name($athlete));
        Flash::success(t('Sportler gelöscht.'));
        Url::redirect('/admin/sportler');
    }

    // ---------------------------------------- gemeinsam mit dem Gym-Bereich --

    public static function empty(): array
    {
        return [
            'id' => 0, 'gym_id' => 0, 'first_name' => '', 'last_name' => '', 'nickname' => '', 'birthdate' => '',
            'gender' => 'unbekannt', 'nationality' => 'AT', 'weight' => null, 'height' => null,
            'record_wins' => 0, 'record_losses' => 0, 'record_draws' => 0, 'photo_path' => '',
            'gym141_member_id' => null, 'gym141_member_no' => '', 'email' => '', 'phone' => '', 'note' => '', 'active' => 1,
            'age' => null, 'bio' => '', 'video_path' => '', 'media_mode' => 'photo', 'photo_sec' => 3,
        ];
    }

    /** @return array{0:array<string,mixed>,1:array<string,string>} */
    public static function validate(): array
    {
        $errors = [];
        $first  = post('first_name');
        $last   = post('last_name');

        if ($first === '' || $last === '') {
            $errors['first_name'] = t('Vor- und Zuname sind Pflicht.');
        }

        $birth = parse_date(post('birthdate'));

        if (post('birthdate') !== '' && $birth === null) {
            $errors['birthdate'] = t('Ungültiges Datum.');
        }

        $gender = post('gender');

        if (!isset(AthleteRepo::GENDERS[$gender])) {
            $gender = 'unbekannt';
        }

        $email = post('email');

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = t('Keine gültige E-Mail-Adresse.');
        }

        return [[
            'first_name'    => $first,
            'last_name'     => $last,
            'nickname'      => post('nickname'),
            'birthdate'     => $birth,
            'gender'        => $gender,
            'nationality'   => strtoupper(mb_substr(post('nationality', 'AT'), 0, 2)) ?: 'AT',
            'weight'        => post_float_or_null('weight'),
            'height'        => post('height') !== '' ? post_int('height') : null,
            'record_wins'   => max(0, post_int('record_wins')),
            'record_losses' => max(0, post_int('record_losses')),
            'record_draws'  => max(0, post_int('record_draws')),
            'email'         => $email,
            'phone'         => post('phone'),
            'note'          => post('note'),
            'active'        => isset($_POST['active_form']) ? post_bool('active') : 1,
            'age'           => post('age') !== '' ? max(0, post_int('age')) : null,
            'bio'           => trim((string) ($_POST['bio'] ?? '')),
            'media_mode'    => in_array(post('media_mode'), ['photo', 'video', 'both'], true) ? post('media_mode') : 'photo',
            'photo_sec'     => max(1, min(5, post_int('photo_sec', 3))),
        ], $errors];
    }

    public static function handlePhoto(int $id): void
    {
        self::handleVideo($id);

        if (post_bool('photo_remove') === 1) {
            $alt = (string) (Database::value('SELECT photo_path FROM athletes WHERE id = ?', [$id]) ?? '');

            if ($alt !== '') {
                Upload::delete($alt);
                Database::update('athletes', $id, ['photo_path' => '']);
            }
        }

        $file = $_FILES['photo'] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $path = Upload::image($file, 'sportler', 'sportler-' . $id, 800);

        if ($path === null) {
            return;
        }

        $alt = (string) (Database::value('SELECT photo_path FROM athletes WHERE id = ?', [$id]) ?? '');

        if ($alt !== '') {
            Upload::delete($alt);
        }

        Database::update('athletes', $id, ['photo_path' => $path]);
    }

    /** Kaempfer-Video fuer die animierte Fightcard (MP4/WebM, max. 100 MB). */
    private static function handleVideo(int $id): void
    {
        $alt = (string) (Database::value('SELECT video_path FROM athletes WHERE id = ?', [$id]) ?? '');

        if (post_bool('video_remove') === 1 && $alt !== '') {
            Upload::delete($alt);
            Database::update('athletes', $id, ['video_path' => '', 'media_mode' => 'photo']);
            $alt = '';
        }

        $file = $_FILES['video'] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $path = Upload::video($file, 'sportler/video', 'sportler-' . $id);

        if ($path === null) {
            return;
        }

        if ($alt !== '') {
            Upload::delete($alt);
        }

        Database::update('athletes', $id, ['video_path' => $path]);
    }
}
