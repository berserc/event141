<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Anmeldung und Rollen der Verwaltung.
 *
 * Rollen:
 *   superuser – darf alles (Benutzer, Einstellungen, Gyms, alle Events)
 *   orga      – Events anlegen und vollstaendig verwalten (Anmeldungen, Kaempfe,
 *               Zeitplan), Gyms und Sportler pflegen; keine System-/Benutzerverwaltung
 *   ring      – Ringleitung / Kampfrichtertisch: nur Kampfstatus und Ergebnisse
 *               eintragen (Ringansicht), sonst lesend
 */
final class Auth
{
    public const MIN_PASSWORD_LENGTH = 8;

    private const DUMMY_HASH = '$2y$12$0000000000000000000000u1yWEyxYPZlSWDPjXmSSAsBnyBnZjJi';

    public const ROLES = [
        'superuser' => 'Admin (Superuser)',
        'orga'      => 'Organisation',
        'ring'      => 'Ringleitung',
    ];

    /** @var array<string,mixed>|null */
    private static ?array $user = null;

    private static bool $resolved = false;

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name((string) Config::get('session_name', 'event141_sess'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => (string) Config::get('base_path', '') . '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }

        self::$resolved = true;
        $id             = $_SESSION['user_id'] ?? null;

        if (!is_int($id)) {
            return self::$user = null;
        }

        $user = Database::one('SELECT * FROM users WHERE id = ? AND active = 1', [$id]);

        if ($user === null) {
            self::logout();

            return self::$user = null;
        }

        return self::$user = $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function role(): ?string
    {
        $user = self::user();

        return $user === null ? null : (string) $user['role'];
    }

    /** Hat der Benutzer MINDESTENS EINE der genannten Rollen? */
    public static function is(string ...$roles): bool
    {
        $role = self::role();

        return $role !== null && in_array($role, $roles, true);
    }

    public static function isSuperuser(): bool
    {
        return self::is('superuser');
    }

    /** Darf Events, Gyms und Sportler anlegen und aendern? */
    public static function canWrite(): bool
    {
        return self::is('superuser', 'orga');
    }

    /** Darf Kampfstatus und Ergebnisse eintragen? */
    public static function canScore(): bool
    {
        return self::is('superuser', 'orga', 'ring');
    }

    // ------------------------------------------------------------- Anmeldung --

    public static function attempt(string $username, string $password): bool
    {
        $user = Database::one(
            'SELECT * FROM users WHERE username = ? COLLATE NOCASE',
            [trim($username)]
        );

        $hash    = (string) ($user['password_hash'] ?? self::DUMMY_HASH);
        $matches = password_verify($password, $hash);

        if ($user === null || (int) $user['active'] !== 1 || !$matches) {
            return false;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('users', (int) $user['id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        self::$user          = null;
        self::$resolved      = false;

        Database::update('users', (int) $user['id'], [
            'last_login_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);

        self::$user     = null;
        self::$resolved = true;
    }

    /** Beendet die gesamte Sitzung (Verwaltung UND Gym-Bereich). */
    public static function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
            ]);
        }

        session_destroy();

        self::$user     = null;
        self::$resolved = true;
    }

    // ------------------------------------------------------ Brute-Force-Bremse --

    public static function isThrottled(string $ip): bool
    {
        $max = (int) Config::get('login_max_attempts', 10);

        $count = (int) Database::value(
            "SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND at > datetime('now', '-15 minutes')",
            [$ip]
        );

        return $count >= $max;
    }

    public static function recordFailedAttempt(string $ip, string $username): void
    {
        Database::insert('login_attempts', ['ip' => $ip, 'username' => $username]);
        Database::run("DELETE FROM login_attempts WHERE at < datetime('now', '-1 day')");
    }

    public static function clearAttempts(string $ip): void
    {
        Database::run('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
    }
}
