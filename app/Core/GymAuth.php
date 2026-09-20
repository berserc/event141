<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Anmeldung eines Gyms/Vereins im Gym-Bereich (/gym).
 *
 * Getrennt von der Verwaltung: Gyms melden sich mit E-Mail und Passwort an,
 * die sie bei der Registrierung selbst vergeben haben. Beide Anmeldungen
 * koennen gleichzeitig in einer Sitzung bestehen.
 */
final class GymAuth
{
    /** @var array<string,mixed>|null */
    private static ?array $gym = null;

    private static bool $resolved = false;

    /** @return array<string,mixed>|null */
    public static function gym(): ?array
    {
        if (self::$resolved) {
            return self::$gym;
        }

        self::$resolved = true;
        $id             = $_SESSION['gym_id'] ?? null;

        if (!is_int($id)) {
            return self::$gym = null;
        }

        $gym = Database::one(
            "SELECT * FROM gyms WHERE id = ? AND status <> 'gesperrt' AND deleted_at IS NULL",
            [$id]
        );

        if ($gym === null) {
            unset($_SESSION['gym_id']);

            return self::$gym = null;
        }

        return self::$gym = $gym;
    }

    public static function check(): bool
    {
        return self::gym() !== null;
    }

    public static function id(): ?int
    {
        $gym = self::gym();

        return $gym === null ? null : (int) $gym['id'];
    }

    public static function attempt(string $email, string $password): bool
    {
        $gym = Database::one(
            "SELECT * FROM gyms
              WHERE login_email = ? COLLATE NOCASE AND login_email <> '' AND deleted_at IS NULL",
            [trim($email)]
        );

        $hash = (string) ($gym['login_password_hash'] ?? '');

        if ($gym === null || $hash === '' || !password_verify($password, $hash)) {
            return false;
        }

        if ((string) $gym['status'] === 'gesperrt') {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['gym_id'] = (int) $gym['id'];
        unset($_SESSION['gym_as_admin']);
        self::$gym      = null;
        self::$resolved = false;

        Database::update('gyms', (int) $gym['id'], ['login_last_at' => gmdate('Y-m-d H:i:s')]);

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['gym_id'], $_SESSION['gym_as_admin']);

        self::$gym      = null;
        self::$resolved = true;
    }

    /** Verwaltung darf "als Gym" in den Gym-Bereich wechseln (Support). */
    public static function loginAs(int $gymId): void
    {
        $_SESSION['gym_id']       = $gymId;
        $_SESSION['gym_as_admin'] = true;
        self::$gym                = null;
        self::$resolved           = false;
    }

    public static function isAdminView(): bool
    {
        return !empty($_SESSION['gym_as_admin']);
    }
}
