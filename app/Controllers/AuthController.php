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

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Url::redirect('/admin');
        }

        View::display('admin/login', [
            'title'  => t('Anmeldung'),
            'errors' => Flash::errors(),
            'old'    => Flash::oldInput(),
        ], 'layouts/blank');
    }

    public function login(): void
    {
        Csrf::verify();

        $ip       = client_ip();
        $username = post('username');
        $password = (string) ($_POST['password'] ?? '');

        if (Auth::isThrottled($ip)) {
            Flash::error(t('Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.'));
            Url::redirect('/admin/login');
        }

        if ($username === '' || $password === '') {
            Flash::error(t('Bitte Benutzername und Passwort eingeben.'));
            Flash::withInput(['username' => $username]);
            Url::redirect('/admin/login');
        }

        if (!Auth::attempt($username, $password)) {
            Auth::recordFailedAttempt($ip, $username);
            Flash::error(t('Benutzername oder Passwort ist falsch.'));
            Flash::withInput(['username' => $username]);
            Url::redirect('/admin/login');
        }

        Auth::clearAttempts($ip);
        Audit::log('login', 'user', Auth::id());

        $user = Auth::user();

        if ($user !== null && (int) $user['must_change_password'] === 1) {
            Flash::info(t('Bitte vergeben Sie zuerst ein eigenes Passwort.'));
            Url::redirect('/admin/profil');
        }

        Url::redirect('/admin');
    }

    public function logout(): void
    {
        Csrf::verify();
        Audit::log('logout', 'user', Auth::id());
        Auth::logout();
        Url::redirect('/admin/login');
    }

    public function profile(): void
    {
        self::requireLogin();

        View::display('admin/profile', [
            'title'  => t('Mein Konto'),
            'user'   => Auth::user(),
            'errors' => Flash::errors(),
        ], 'layouts/admin');
    }

    public function updatePassword(): void
    {
        self::requireLogin();
        Csrf::verify();

        $user    = Auth::user();
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');
        $errors  = [];

        if (!password_verify($current, (string) $user['password_hash'])) {
            $errors['current_password'] = t('Das aktuelle Passwort stimmt nicht.');
        }

        if (mb_strlen($new) < Auth::MIN_PASSWORD_LENGTH) {
            $errors['new_password'] = t('Das neue Passwort muss mindestens %d Zeichen haben.', Auth::MIN_PASSWORD_LENGTH);
        }

        if ($new !== $confirm) {
            $errors['new_password_confirm'] = t('Die Wiederholung stimmt nicht überein.');
        }

        if ($errors !== []) {
            Flash::withInput([], $errors);
            Flash::error(t('Das Passwort wurde nicht geändert.'));
            Url::redirect('/admin/profil');
        }

        Database::update('users', (int) $user['id'], [
            'password_hash'        => password_hash($new, PASSWORD_DEFAULT),
            'must_change_password' => 0,
            'updated_at'           => gmdate('Y-m-d H:i:s'),
        ]);

        Audit::log('password_changed', 'user', (int) $user['id']);
        Flash::success(t('Passwort geändert.'));
        Url::redirect('/admin/profil');
    }

    /** Schuetzt alle /admin-Routen. */
    public static function requireLogin(): void
    {
        if (Auth::check()) {
            return;
        }

        Flash::error(t('Bitte melden Sie sich an.'));
        Url::redirect('/admin/login');
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();

        if (Auth::is(...$roles)) {
            return;
        }

        http_response_code(403);
        View::display('errors/403', ['title' => t('Kein Zugriff')], 'layouts/admin');
        exit;
    }

    /** Schreibrechte (Superuser + Organisation). */
    public static function requireWrite(): void
    {
        self::requireRole('superuser', 'orga');
    }
}
