<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
use App\Models\UserRepo;

final class UserController
{
    public function index(): void
    {
        AuthController::requireRole('superuser');

        View::display('admin/users/index', [
            'title' => 'Benutzer',
            'users' => UserRepo::all(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        AuthController::requireRole('superuser');

        View::display('admin/users/form', [
            'title'  => 'Neuer Benutzer',
            'user'   => Flash::oldInput() + ['id' => 0, 'username' => '', 'name' => '', 'email' => '', 'role' => 'orga', 'active' => 1],
            'errors' => Flash::errors(),
            'isNew'  => true,
        ], 'layouts/admin');
    }

    public function edit(array $args): void
    {
        AuthController::requireRole('superuser');

        $user = UserRepo::find((int) ($args['id'] ?? 0));

        if ($user === null) {
            Flash::error('Benutzer nicht gefunden.');
            Url::redirect('/admin/benutzer');
        }

        View::display('admin/users/form', [
            'title'  => (string) $user['username'],
            'user'   => Flash::oldInput() + $user,
            'errors' => Flash::errors(),
            'isNew'  => false,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        [$data, $password, $errors] = $this->validate(null);

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Url::redirect('/admin/benutzer/neu');
        }

        $generated = $password === '' ? UserRepo::generatePassword() : $password;

        $data['password_hash']        = password_hash($generated, PASSWORD_DEFAULT);
        $data['must_change_password'] = 1;

        $id = Database::insert('users', $data);

        Audit::log('user_created', 'user', $id, (string) $data['username'] . ' (' . $data['role'] . ')');
        Flash::success(sprintf(
            'Benutzer "%s" angelegt. Startpasswort: %s – bitte sicher weitergeben, es wird nicht erneut angezeigt.',
            $data['username'],
            $generated
        ));
        Url::redirect('/admin/benutzer');
    }

    public function update(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $id       = (int) ($args['id'] ?? 0);
        $existing = UserRepo::find($id);

        if ($existing === null) {
            Flash::error('Benutzer nicht gefunden.');
            Url::redirect('/admin/benutzer');
        }

        [$data, $password, $errors] = $this->validate($id);

        $losesSuperuser = $existing['role'] === 'superuser'
            && ($data['role'] !== 'superuser' || (int) $data['active'] !== 1);

        if ($losesSuperuser && UserRepo::activeSuperuserCount($id) === 0) {
            $errors['role'] = 'Das ist der letzte aktive Superuser – Rolle und Status können nicht geändert werden.';
        }

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Url::redirect('/admin/benutzer/' . $id);
        }

        if ($password !== '') {
            $data['password_hash']        = password_hash($password, PASSWORD_DEFAULT);
            $data['must_change_password'] = 1;
        }

        $data['updated_at'] = gmdate('Y-m-d H:i:s');

        Database::update('users', $id, $data);

        Audit::log('user_updated', 'user', $id, Audit::diff($existing, $data));
        Flash::success('Benutzer gespeichert.' . ($password !== '' ? ' Neues Passwort: ' . $password : ''));
        Url::redirect('/admin/benutzer/' . $id);
    }

    public function destroy(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $id   = (int) ($args['id'] ?? 0);
        $user = UserRepo::find($id);

        if ($user === null) {
            Flash::error('Benutzer nicht gefunden.');
            Url::redirect('/admin/benutzer');
        }

        if ($id === Auth::id()) {
            Flash::error('Das eigene Konto kann nicht gelöscht werden.');
            Url::redirect('/admin/benutzer/' . $id);
        }

        if ($user['role'] === 'superuser' && UserRepo::activeSuperuserCount($id) === 0) {
            Flash::error('Der letzte aktive Superuser kann nicht gelöscht werden.');
            Url::redirect('/admin/benutzer/' . $id);
        }

        Database::run('DELETE FROM users WHERE id = ?', [$id]);

        Audit::log('user_deleted', 'user', $id, (string) $user['username']);
        Flash::success('Benutzer gelöscht.');
        Url::redirect('/admin/benutzer');
    }

    /** @return array{0:array<string,mixed>,1:string,2:array<string,string>} */
    private function validate(?int $id): array
    {
        $errors   = [];
        $username = post('username');

        if ($username === '') {
            $errors['username'] = 'Benutzername ist erforderlich.';
        } elseif (!preg_match('/^[a-zA-Z0-9._@+-]{3,80}$/', $username)) {
            $errors['username'] = '3–80 Zeichen. Erlaubt sind Buchstaben, Ziffern und . _ - + @';
        } elseif (UserRepo::usernameTaken($username, $id)) {
            $errors['username'] = 'Dieser Benutzername ist bereits vergeben.';
        }

        $role = post('role');

        if (!isset(Auth::ROLES[$role])) {
            $errors['role'] = 'Bitte eine Rolle wählen.';
            $role = 'orga';
        }

        $email = post('email');

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        }

        $password = (string) ($_POST['password'] ?? '');

        if ($password !== '' && mb_strlen($password) < Auth::MIN_PASSWORD_LENGTH) {
            $errors['password'] = 'Das Passwort muss mindestens ' . Auth::MIN_PASSWORD_LENGTH . ' Zeichen haben.';
        }

        $data = [
            'username' => $username,
            'name'     => post('name'),
            'email'    => $email,
            'role'     => $role,
            'active'   => post_bool('active'),
        ];

        return [$data, $password, $errors];
    }
}
