<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Einrichtung der Anwendung.
 *
 * Wird sowohl von bin/install.php (Kommandozeile) als auch von public/setup.php
 * (Browser, wenn nur FTP-Zugang besteht) verwendet – damit beide Wege exakt
 * dasselbe tun. Laeuft idempotent: ein zweiter Lauf ergaenzt nur Fehlendes.
 */
final class Installer
{
    /** @var list<string> */
    private array $log = [];

    /** @return list<string> */
    public function log(): array
    {
        return $this->log;
    }

    private function say(string $message): void
    {
        $this->log[] = $message;
    }

    /**
     * Prueft die Serverumgebung.
     *
     * @return array{ok:bool, checks:list<array{name:string,ok:bool,hint:string}>}
     */
    public static function requirements(): array
    {
        $root   = dirname(__DIR__, 2);
        $checks = [];

        $checks[] = [
            'name' => 'PHP ' . PHP_VERSION,
            'ok'   => PHP_VERSION_ID >= 80100,
            'hint' => 'Benötigt wird PHP 8.1 oder neuer.',
        ];

        foreach (['pdo_sqlite', 'mbstring'] as $extension) {
            $checks[] = [
                'name' => 'Erweiterung ' . $extension,
                'ok'   => extension_loaded($extension),
                'hint' => 'In der PHP-Konfiguration aktivieren.',
            ];
        }

        $checks[] = [
            'name' => 'Erweiterung gd (optional)',
            'ok'   => extension_loaded('gd'),
            'hint' => 'Ohne GD werden hochgeladene Bilder nicht verkleinert.',
        ];

        $checks[] = [
            'name' => 'Erweiterung curl (optional)',
            'ok'   => extension_loaded('curl'),
            'hint' => 'Ohne curl läuft die Gym141-Kopplung über PHP-Streams (allow_url_fopen nötig).',
        ];

        foreach (['data' => $root . '/data', 'public/uploads' => $root . '/public/uploads'] as $label => $dir) {
            $checks[] = [
                'name' => "Verzeichnis $label beschreibbar",
                'ok'   => is_dir($dir) ? is_writable($dir) : is_writable(dirname($dir)),
                'hint' => "Schreibrechte setzen: chmod 775 $label",
            ];
        }

        $required = array_filter($checks, static fn (array $c): bool => !str_contains($c['name'], 'optional'));

        return [
            'ok'     => array_reduce($required, static fn (bool $c, array $x): bool => $c && $x['ok'], true),
            'checks' => $checks,
        ];
    }

    /**
     * Legt Datenbank, Grunddaten und den ersten Superuser an.
     *
     * @return list<string> Protokoll der Schritte
     */
    public function run(string $adminUser, string $adminPassword, bool $force = false, ?string $dbPath = null): array
    {
        $this->log = [];

        $root   = dirname(__DIR__, 2);
        $dbPath = $dbPath ?? (string) Config::get('db_path');

        $this->prepareConfigFile($root);
        $this->prepareDirectories($root, $dbPath);

        if (is_file($dbPath) && $force) {
            $backup = $dbPath . '.' . date('Ymd-His') . '.bak';
            rename($dbPath, $backup);
            $this->say('Bestehende Datenbank gesichert nach ' . basename($backup) . '.');
        }

        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');

        Database::setPdo($pdo);

        $this->applySchema($pdo, $root);
        $this->migrate($pdo);
        @chmod($dbPath, 0664);

        $this->seedPages($root);
        $this->seedSettings();
        $this->createSuperuser($adminUser, $adminPassword);

        $this->say('Einrichtung abgeschlossen. Datenbank: ' . $dbPath);

        return $this->log;
    }

    // ------------------------------------------------------------- Schritte --

    private function prepareConfigFile(string $root): void
    {
        $configFile = $root . '/app/config.php';

        if (!is_file($configFile)) {
            if (@copy($root . '/app/config.example.php', $configFile)) {
                $this->say('app/config.php aus der Vorlage erstellt.');
            } else {
                $this->say('WARNUNG: app/config.php konnte nicht angelegt werden – es gilt die Vorlage.');
            }
        }
    }

    private function prepareDirectories(string $root, string $dbPath): void
    {
        foreach ([
            dirname($dbPath),
            $root . '/data/backups',
            $root . '/public/uploads/events',
            $root . '/public/uploads/gyms',
            $root . '/public/uploads/sportler',
        ] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Verzeichnis konnte nicht angelegt werden: ' . $dir);
            }
        }
    }

    /**
     * Passt Datenbanken frueherer Versionen an. Neue Tabellen/Spalten gehoeren
     * IMMER hierher (zusaetzlich zu data/schema.sql), weil der Updater das
     * Datenverzeichnis schuetzt.
     */
    private function migrate(PDO $pdo): void
    {
        // Seit 0.2.0: Fightcard-Funktionen (Story, Block, Dauer, inaktiv),
        // Kaempfer-Medien, Live-Zeitplan, Tickets, Checklisten.
        $this->addColumns($pdo, 'athletes', [
            'age'          => 'INTEGER',
            'bio'          => "TEXT NOT NULL DEFAULT ''",
            'video_path'   => "TEXT NOT NULL DEFAULT ''",
            'media_mode'   => "TEXT NOT NULL DEFAULT 'photo'",
            'photo_sec'    => 'INTEGER NOT NULL DEFAULT 3',
            'external_ref' => "TEXT NOT NULL DEFAULT ''",
        ]);
        $this->addColumns($pdo, 'events', [
            'short_name'            => "TEXT NOT NULL DEFAULT ''",
            'live_mode'             => 'INTEGER NOT NULL DEFAULT 0',
            'default_bout_minutes'  => 'INTEGER NOT NULL DEFAULT 12',
            'default_break_minutes' => 'INTEGER NOT NULL DEFAULT 15',
            'show_countdown'        => 'INTEGER NOT NULL DEFAULT 1',
            'show_map'              => 'INTEGER NOT NULL DEFAULT 1',
            'location_note'         => "TEXT NOT NULL DEFAULT ''",
            'min_age_note'          => "TEXT NOT NULL DEFAULT ''",
            'ticket_note'           => "TEXT NOT NULL DEFAULT ''",
            'tickets_json'          => "TEXT NOT NULL DEFAULT '[]'",
            'social_json'           => "TEXT NOT NULL DEFAULT '{}'",
            'belt_path'             => "TEXT NOT NULL DEFAULT ''",
            // Seit 0.5.0: Ticket141-Kopplung.
            'ticket141_slug'        => "TEXT NOT NULL DEFAULT ''",
        ]);
        $this->addColumns($pdo, 'event_entries', [
            'medical_ok' => 'INTEGER NOT NULL DEFAULT 0',
            'music'      => "TEXT NOT NULL DEFAULT ''",
            'cabin'      => "TEXT NOT NULL DEFAULT ''",
        ]);
        $this->addColumns($pdo, 'event_bouts', [
            'block'        => "TEXT NOT NULL DEFAULT ''",
            'style'        => "TEXT NOT NULL DEFAULT ''",
            'weight_label' => "TEXT NOT NULL DEFAULT ''",
            'description'  => "TEXT NOT NULL DEFAULT ''",
            'minutes'      => 'INTEGER NOT NULL DEFAULT 0',
            'show_record'  => 'INTEGER NOT NULL DEFAULT 1',
            'active'       => 'INTEGER NOT NULL DEFAULT 1',
            'result_round' => "TEXT NOT NULL DEFAULT ''",
            'belt_label'   => "TEXT NOT NULL DEFAULT ''",
            'external_id'  => "TEXT NOT NULL DEFAULT ''",
        ]);

        // Seit 0.6.0: Bildbibliothek mit Tags, Galerien, Event-Bericht, Bilder + Nachwort je Kampf.
        $this->addColumns($pdo, 'event_bouts', ['epilog' => "TEXT NOT NULL DEFAULT ''"]);
        $this->addColumns($pdo, 'events', [
            'report_title'     => "TEXT NOT NULL DEFAULT ''",
            'report_text'      => "TEXT NOT NULL DEFAULT ''",
            'report_cover_id'  => 'INTEGER',
            'report_published' => 'INTEGER NOT NULL DEFAULT 0',
        ]);
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS library_images (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    file        TEXT    NOT NULL,
    thumb       TEXT    NOT NULL DEFAULT '',
    width       INTEGER NOT NULL DEFAULT 0,
    height      INTEGER NOT NULL DEFAULT 0,
    caption     TEXT    NOT NULL DEFAULT '',
    tags        TEXT    NOT NULL DEFAULT '',
    orig_name   TEXT    NOT NULL DEFAULT '',
    uploaded_by INTEGER,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_library_images_created ON library_images(created_at DESC);
CREATE TABLE IF NOT EXISTS galleries (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id       INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    slug           TEXT    NOT NULL,
    title          TEXT    NOT NULL,
    text           TEXT    NOT NULL DEFAULT '',
    cover_image_id INTEGER REFERENCES library_images(id) ON DELETE SET NULL,
    published      INTEGER NOT NULL DEFAULT 1,
    sort_order     INTEGER NOT NULL DEFAULT 0,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (event_id, slug)
);
CREATE TABLE IF NOT EXISTS gallery_images (
    gallery_id INTEGER NOT NULL REFERENCES galleries(id) ON DELETE CASCADE,
    image_id   INTEGER NOT NULL REFERENCES library_images(id) ON DELETE CASCADE,
    sort_order INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (gallery_id, image_id)
);
CREATE TABLE IF NOT EXISTS event_media (
    event_id   INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    kind       TEXT    NOT NULL CHECK (kind IN ('bout', 'report')),
    ref_id     INTEGER NOT NULL,
    image_id   INTEGER NOT NULL REFERENCES library_images(id) ON DELETE CASCADE,
    sort_order INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (kind, ref_id, image_id)
);
CREATE INDEX IF NOT EXISTS idx_event_media_event ON event_media(event_id, kind);
SQL);

        // Seit 0.2.0: Sponsoren, Plattform-API (Schluessel), Webhooks.
        $pdo->exec(<<<'SQL'

CREATE TABLE IF NOT EXISTS event_sponsors (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id   INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    name       TEXT    NOT NULL,
    url        TEXT    NOT NULL DEFAULT '',
    logo_path  TEXT    NOT NULL DEFAULT '',
    tile_color TEXT    NOT NULL DEFAULT '',
    is_main    INTEGER NOT NULL DEFAULT 0,
    published  INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_sponsors_event ON event_sponsors(event_id, sort_order);


CREATE TABLE IF NOT EXISTS api_keys (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT    NOT NULL,
    key_prefix   TEXT    NOT NULL,
    key_hash     TEXT    NOT NULL UNIQUE,
    scope        TEXT    NOT NULL DEFAULT 'read' CHECK (scope IN ('read', 'write')),
    gym_id       INTEGER REFERENCES gyms(id)   ON DELETE CASCADE,
    event_id     INTEGER REFERENCES events(id) ON DELETE CASCADE,
    active       INTEGER NOT NULL DEFAULT 1,
    last_used_at TEXT,
    created_by   INTEGER REFERENCES users(id),
    created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS webhooks (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id    INTEGER REFERENCES events(id) ON DELETE CASCADE,
    name        TEXT    NOT NULL DEFAULT '',
    url         TEXT    NOT NULL,
    secret      TEXT    NOT NULL,
    active      INTEGER NOT NULL DEFAULT 1,
    last_status TEXT    NOT NULL DEFAULT '',
    last_at     TEXT,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

SQL);
    }

    /**
     * Ergaenzt fehlende Spalten einer Tabelle.
     *
     * @param array<string,string> $spalten Name => SQL-Definition
     */
    private function addColumns(PDO $pdo, string $table, array $spalten): void
    {
        if ($spalten === []) {
            return;
        }

        $vorhanden = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($table))->fetchColumn();

        if ($vorhanden === false) {
            return;
        }

        $columns = array_column($pdo->query("PRAGMA table_info($table)")->fetchAll(), 'name');

        foreach ($spalten as $name => $definition) {
            if (in_array($name, $columns, true)) {
                continue;
            }

            $pdo->exec("ALTER TABLE $table ADD COLUMN $name $definition");
            $this->say("Spalte $table.$name ergänzt.");
        }
    }

    private function applySchema(PDO $pdo, string $root): void
    {
        $schema = file_get_contents($root . '/data/schema.sql');

        if ($schema === false) {
            throw new RuntimeException('data/schema.sql konnte nicht gelesen werden.');
        }

        $pdo->exec($schema);
        $this->say('Datenbankschema eingespielt.');
    }

    private function seedPages(string $root): void
    {
        foreach ([['impressum', 'Impressum', 10], ['datenschutz', 'Datenschutz', 20]] as [$slug, $title, $sort]) {
            if (Database::one('SELECT id FROM pages WHERE slug = ?', [$slug]) !== null) {
                continue;
            }

            $file = $root . '/data/seed/' . $slug . '.html';
            $body = is_file($file) ? (string) file_get_contents($file) : '';

            Database::insert('pages', [
                'slug'       => $slug,
                'title'      => $title,
                'body'       => $body,
                'in_footer'  => 1,
                'sort_order' => $sort,
                'published'  => 1,
            ]);

            $this->say("Seite \"$title\" angelegt" . ($body === '' ? ' (ohne Inhalt).' : '.'));
        }
    }

    private function seedSettings(): void
    {
        foreach (SeedData::settings() as $key => $value) {
            Database::run('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)', [$key, $value]);
        }

        $this->say('Veranstalterdaten vorbelegt.');
    }

    private function createSuperuser(string $username, string $password): void
    {
        $username = trim($username);

        if ($username === '') {
            $this->say('Kein Superuser angelegt (kein Benutzername angegeben).');

            return;
        }

        $existing = Database::one('SELECT id FROM users WHERE username = ? COLLATE NOCASE', [$username]);

        if ($existing !== null) {
            Database::update('users', (int) $existing['id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role'          => 'superuser',
                'active'        => 1,
                'updated_at'    => gmdate('Y-m-d H:i:s'),
            ]);

            $this->say("Superuser \"$username\" war vorhanden – Passwort und Rolle aktualisiert.");

            return;
        }

        Database::insert('users', [
            'username'             => $username,
            'name'                 => 'Administrator',
            'email'                => str_contains($username, '@') ? $username : '',
            'password_hash'        => password_hash($password, PASSWORD_DEFAULT),
            'role'                 => 'superuser',
            'active'               => 1,
            'must_change_password' => 0,
        ]);

        $this->say("Superuser \"$username\" angelegt.");
    }
}
