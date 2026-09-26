-- Event141 – Datenbankschema (SQLite)
-- Wird von bin/install.php bzw. public/setup.php eingespielt.
-- NEUE TABELLEN IMMER AUCH in Installer::migrate() nachruesten (Updater
-- schuetzt data/ und tauscht schema.sql nicht bei jeder Version).

PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------------- Benutzer --
CREATE TABLE IF NOT EXISTS users (
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    username             TEXT    NOT NULL UNIQUE,
    name                 TEXT    NOT NULL DEFAULT '',
    email                TEXT    NOT NULL DEFAULT '',
    password_hash        TEXT    NOT NULL,
    role                 TEXT    NOT NULL DEFAULT 'orga'
                                 CHECK (role IN ('superuser', 'orga', 'ring')),
    active               INTEGER NOT NULL DEFAULT 1,
    must_change_password INTEGER NOT NULL DEFAULT 0,
    last_login_at        TEXT,
    created_at           TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at           TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    ip       TEXT NOT NULL DEFAULT '',
    username TEXT NOT NULL DEFAULT '',
    at       TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_login_attempts ON login_attempts(ip, at);

CREATE TABLE IF NOT EXISTS settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS audit_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER,
    username   TEXT NOT NULL DEFAULT '',
    action     TEXT NOT NULL,
    entity     TEXT NOT NULL DEFAULT '',
    entity_id  INTEGER,
    detail     TEXT NOT NULL DEFAULT '',
    ip         TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_log(created_at DESC);

-- Redaktionelle Seiten (Impressum, Datenschutz, freie Seiten)
CREATE TABLE IF NOT EXISTS pages (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    slug       TEXT    NOT NULL UNIQUE,
    title      TEXT    NOT NULL,
    body       TEXT    NOT NULL DEFAULT '',
    in_footer  INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    published  INTEGER NOT NULL DEFAULT 1,
    updated_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- --------------------------------------------------------------- Gyms/Vereine --

-- Ein Gym meldet seine Sportler fuer Events an. Es registriert sich selbst
-- (/gym/registrieren) oder wird von der Verwaltung angelegt. Optional ist
-- eine Gym141-Instanz gekoppelt, aus der Mitglieder geholt werden.
CREATE TABLE IF NOT EXISTS gyms (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    name                TEXT    NOT NULL,
    short_name          TEXT    NOT NULL DEFAULT '',   -- z. B. "NOVO" fuer Anzeigen
    slug                TEXT    NOT NULL UNIQUE,
    street              TEXT    NOT NULL DEFAULT '',
    zip                 TEXT    NOT NULL DEFAULT '',
    city                TEXT    NOT NULL DEFAULT '',
    country             TEXT    NOT NULL DEFAULT 'AT',
    contact_name        TEXT    NOT NULL DEFAULT '',   -- Trainer / Ansprechpartner
    email               TEXT    NOT NULL DEFAULT '',
    phone               TEXT    NOT NULL DEFAULT '',
    website             TEXT    NOT NULL DEFAULT '',
    logo_path           TEXT    NOT NULL DEFAULT '',
    status              TEXT    NOT NULL DEFAULT 'neu'
                                CHECK (status IN ('neu', 'bestaetigt', 'gesperrt')),
    -- Gym-Login (E-Mail + Passwort, vom Gym selbst gewaehlt)
    login_email         TEXT    NOT NULL DEFAULT '',
    login_password_hash TEXT    NOT NULL DEFAULT '',
    login_last_at       TEXT,
    -- Kopplung mit Gym141 (Verwaltungs-API, Bearer-Token)
    gym141_url          TEXT    NOT NULL DEFAULT '',
    gym141_token        TEXT    NOT NULL DEFAULT '',
    gym141_user         TEXT    NOT NULL DEFAULT '',
    gym141_club         TEXT    NOT NULL DEFAULT '',
    gym141_synced_at    TEXT,
    note                TEXT    NOT NULL DEFAULT '',   -- intern (Verwaltung)
    deleted_at          TEXT,
    created_at          TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at          TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_gyms_name   ON gyms(name);
CREATE INDEX IF NOT EXISTS idx_gyms_status ON gyms(status);

-- ------------------------------------------------------------- Sportler --
CREATE TABLE IF NOT EXISTS athletes (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    gym_id           INTEGER NOT NULL REFERENCES gyms(id) ON DELETE CASCADE,
    first_name       TEXT    NOT NULL,
    last_name        TEXT    NOT NULL,
    nickname         TEXT    NOT NULL DEFAULT '',     -- Kampfname
    birthdate        TEXT,                            -- YYYY-MM-DD
    gender           TEXT    NOT NULL DEFAULT 'unbekannt'
                             CHECK (gender IN ('m', 'w', 'd', 'unbekannt')),
    nationality      TEXT    NOT NULL DEFAULT 'AT',   -- ISO-2
    weight           REAL,                            -- kg (aktuell/gemeldet)
    height           INTEGER,                         -- cm
    record_wins      INTEGER NOT NULL DEFAULT 0,
    record_losses    INTEGER NOT NULL DEFAULT 0,
    record_draws     INTEGER NOT NULL DEFAULT 0,
    photo_path       TEXT    NOT NULL DEFAULT '',
    gym141_member_id INTEGER,                         -- Herkunft aus Gym141
    gym141_member_no TEXT    NOT NULL DEFAULT '',
    email            TEXT    NOT NULL DEFAULT '',
    phone            TEXT    NOT NULL DEFAULT '',
    note             TEXT    NOT NULL DEFAULT '',
    active           INTEGER NOT NULL DEFAULT 1,
    age              INTEGER,                         -- Alter, falls kein Geburtsdatum bekannt
    bio              TEXT    NOT NULL DEFAULT '',
    video_path       TEXT    NOT NULL DEFAULT '',     -- MP4/WebM fuer die animierte Fightcard
    media_mode       TEXT    NOT NULL DEFAULT 'photo', -- photo | video | both
    photo_sec        INTEGER NOT NULL DEFAULT 3,      -- Foto-Dauer im Wechselmodus (1-5 s)
    external_ref     TEXT    NOT NULL DEFAULT '',     -- Fremdschluessel aus API-Clients
    created_at       TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at       TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_athletes_gym    ON athletes(gym_id, last_name, first_name);
CREATE INDEX IF NOT EXISTS idx_athletes_gym141 ON athletes(gym_id, gym141_member_id);

-- --------------------------------------------------------------- Events --

-- Ein Event ist entweder eine Gala (Fightcard, manuell zusammengestellt) oder
-- ein Turnier (Kategorien, Anmeldungen, generierter Turnierbaum). Der Typ
-- laesst sich jederzeit umstellen – vorhandene Daten bleiben erhalten.
CREATE TABLE IF NOT EXISTS events (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    slug               TEXT    NOT NULL UNIQUE,
    name               TEXT    NOT NULL,
    type               TEXT    NOT NULL DEFAULT 'turnier' CHECK (type IN ('gala', 'turnier')),
    sport              TEXT    NOT NULL DEFAULT '',       -- z. B. "Kickboxen", "Judo"
    tagline            TEXT    NOT NULL DEFAULT '',
    description        TEXT    NOT NULL DEFAULT '',       -- HTML (eingeschraenkt)
    starts_on          TEXT    NOT NULL,                  -- YYYY-MM-DD
    ends_on            TEXT,                              -- YYYY-MM-DD (mehrtaegig)
    doors_time         TEXT    NOT NULL DEFAULT '',       -- Einlass HH:MM
    start_time         TEXT    NOT NULL DEFAULT '',       -- Beginn HH:MM
    venue_name         TEXT    NOT NULL DEFAULT '',       -- Halle
    venue_street       TEXT    NOT NULL DEFAULT '',
    venue_zip          TEXT    NOT NULL DEFAULT '',
    venue_city         TEXT    NOT NULL DEFAULT '',
    status             TEXT    NOT NULL DEFAULT 'entwurf'
                               CHECK (status IN ('entwurf', 'anmeldung', 'geschlossen', 'laufend', 'beendet')),
    registration_from  TEXT,                              -- YYYY-MM-DD
    registration_until TEXT,                              -- YYYY-MM-DD
    max_entries        INTEGER NOT NULL DEFAULT 0,        -- 0 = unbegrenzt
    entry_fee          REAL    NOT NULL DEFAULT 0,        -- Startgeld je Sportler
    ticket_url         TEXT    NOT NULL DEFAULT '',
    contact_email      TEXT    NOT NULL DEFAULT '',
    contact_phone      TEXT    NOT NULL DEFAULT '',
    logo_path          TEXT    NOT NULL DEFAULT '',
    poster_path        TEXT    NOT NULL DEFAULT '',
    hero_path          TEXT    NOT NULL DEFAULT '',
    published          INTEGER NOT NULL DEFAULT 0,        -- auf der Website sichtbar
    show_entries       INTEGER NOT NULL DEFAULT 1,        -- Teilnehmerliste oeffentlich
    show_results       INTEGER NOT NULL DEFAULT 1,
    gym_registration   INTEGER NOT NULL DEFAULT 1,        -- Gyms duerfen selbst anmelden
    short_name         TEXT    NOT NULL DEFAULT '',       -- "NAFN 3"
    live_mode          INTEGER NOT NULL DEFAULT 0,        -- Zeiten aus echten Start-/Endzeiten
    default_bout_minutes  INTEGER NOT NULL DEFAULT 12,
    default_break_minutes INTEGER NOT NULL DEFAULT 15,
    show_countdown     INTEGER NOT NULL DEFAULT 1,
    show_map           INTEGER NOT NULL DEFAULT 1,
    location_note      TEXT    NOT NULL DEFAULT '',       -- "Mitten in Weiz, genug Parkplaetze"
    min_age_note       TEXT    NOT NULL DEFAULT '',       -- "Einlass ab 10 Jahren"
    ticket_note        TEXT    NOT NULL DEFAULT '',
    tickets_json       TEXT    NOT NULL DEFAULT '[]',     -- [{label,price,note,highlight}]
    social_json        TEXT    NOT NULL DEFAULT '{}',     -- {facebook,instagram,...}
    belt_path          TEXT    NOT NULL DEFAULT '',       -- Bild des Titelguertels (Titelkaempfe)
    ticket141_slug     TEXT    NOT NULL DEFAULT '',       -- Kuerzel des gekoppelten Ticket141-Events
    created_by         INTEGER REFERENCES users(id),
    created_at         TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at         TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_events_date ON events(starts_on DESC);

-- Tage eines Events (mehrtaegige Turniere)
CREATE TABLE IF NOT EXISTS event_days (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id   INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    day_date   TEXT    NOT NULL,                          -- YYYY-MM-DD
    label      TEXT    NOT NULL DEFAULT '',               -- "Tag 1 – Vorrunden"
    note       TEXT    NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    UNIQUE (event_id, day_date)
);

-- Abschnitte eines Tages (Vormittag, Nachmittag, Abend-Gala, "Block 1" ...)
CREATE TABLE IF NOT EXISTS event_sessions (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id   INTEGER NOT NULL REFERENCES events(id)     ON DELETE CASCADE,
    day_id     INTEGER NOT NULL REFERENCES event_days(id) ON DELETE CASCADE,
    name       TEXT    NOT NULL,
    starts_at  TEXT    NOT NULL DEFAULT '',               -- HH:MM
    ends_at    TEXT    NOT NULL DEFAULT '',               -- HH:MM
    note       TEXT    NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_sessions_day ON event_sessions(day_id, sort_order);

-- Wettkampfstaetten (Ring 1, Ring 2, Matte A, Kaefig ...)
CREATE TABLE IF NOT EXISTS event_venues (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id   INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    name       TEXT    NOT NULL,
    short_name TEXT    NOT NULL DEFAULT '',               -- "R1"
    color      TEXT    NOT NULL DEFAULT '',               -- #rrggbb fuer den Zeitplan
    note       TEXT    NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_venues_event ON event_venues(event_id, sort_order);

-- Kategorien (Gewichts-/Altersklassen, Disziplinen). Bei Turnieren wird je
-- Kategorie ein Turnierbaum erzeugt; bei Galas dienen sie nur zur Anzeige.
CREATE TABLE IF NOT EXISTS event_categories (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id      INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    name          TEXT    NOT NULL,                       -- "Herren -75 kg"
    discipline    TEXT    NOT NULL DEFAULT '',            -- "K1", "Leichtkontakt"
    gender        TEXT    NOT NULL DEFAULT 'alle'
                          CHECK (gender IN ('alle', 'm', 'w')),
    age_min       INTEGER,
    age_max       INTEGER,
    weight_min    REAL,
    weight_max    REAL,
    rounds        INTEGER NOT NULL DEFAULT 3,
    round_minutes REAL    NOT NULL DEFAULT 2,
    mode          TEXT    NOT NULL DEFAULT 'ko' CHECK (mode IN ('ko', 'liste')),
    max_entries   INTEGER NOT NULL DEFAULT 0,
    note          TEXT    NOT NULL DEFAULT '',
    sort_order    INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_categories_event ON event_categories(event_id, sort_order);

-- Anmeldungen: ein Sportler je Event und Kategorie
CREATE TABLE IF NOT EXISTS event_entries (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id    INTEGER NOT NULL REFERENCES events(id)   ON DELETE CASCADE,
    athlete_id  INTEGER NOT NULL REFERENCES athletes(id) ON DELETE CASCADE,
    gym_id      INTEGER NOT NULL REFERENCES gyms(id)     ON DELETE CASCADE,
    category_id INTEGER REFERENCES event_categories(id)  ON DELETE SET NULL,
    status      TEXT    NOT NULL DEFAULT 'angemeldet'
                        CHECK (status IN ('angemeldet', 'bestaetigt', 'abgelehnt', 'abgemeldet')),
    seed        INTEGER NOT NULL DEFAULT 0,               -- Setzposition (0 = ungesetzt)
    medical_ok  INTEGER NOT NULL DEFAULT 0,               -- aerztliche Untersuchung
    music       TEXT    NOT NULL DEFAULT '',              -- Einlaufmusik (Titel/Datei)
    cabin       TEXT    NOT NULL DEFAULT '',              -- Kabine
    weighed     REAL,                                     -- Wiegegewicht
    weighed_at  TEXT,
    paid        INTEGER NOT NULL DEFAULT 0,               -- Startgeld bezahlt
    note        TEXT    NOT NULL DEFAULT '',               -- vom Gym
    admin_note  TEXT    NOT NULL DEFAULT '',               -- intern
    source      TEXT    NOT NULL DEFAULT 'gym'             -- gym | admin
                        CHECK (source IN ('gym', 'admin')),
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (event_id, athlete_id, category_id)
);

CREATE INDEX IF NOT EXISTS idx_entries_event    ON event_entries(event_id, status);
CREATE INDEX IF NOT EXISTS idx_entries_category ON event_entries(category_id, status);
CREATE INDEX IF NOT EXISTS idx_entries_gym      ON event_entries(gym_id);

-- Kaempfe: Gala-Fightcard (manuell) oder Turnierbaum (generiert).
-- red/blue = Ecken; next_bout_id/next_slot = wohin der Sieger aufsteigt.
CREATE TABLE IF NOT EXISTS event_bouts (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id       INTEGER NOT NULL REFERENCES events(id)        ON DELETE CASCADE,
    category_id    INTEGER REFERENCES event_categories(id)       ON DELETE SET NULL,
    session_id     INTEGER REFERENCES event_sessions(id)         ON DELETE SET NULL,
    venue_id       INTEGER REFERENCES event_venues(id)           ON DELETE SET NULL,
    order_no       INTEGER NOT NULL DEFAULT 0,                   -- Reihenfolge im Abschnitt/Ring
    bout_no        INTEGER NOT NULL DEFAULT 0,                   -- Kampfnummer (Anzeige)
    title          TEXT    NOT NULL DEFAULT '',                  -- "Hauptkampf", "Titelkampf"
    round_no       INTEGER NOT NULL DEFAULT 0,                   -- Turnierrunde (1 = erste)
    round_label    TEXT    NOT NULL DEFAULT '',                  -- "Viertelfinale"
    bracket_pos    INTEGER NOT NULL DEFAULT 0,                   -- Position in der Runde
    red_entry_id   INTEGER REFERENCES event_entries(id)          ON DELETE SET NULL,
    blue_entry_id  INTEGER REFERENCES event_entries(id)          ON DELETE SET NULL,
    next_bout_id   INTEGER REFERENCES event_bouts(id)            ON DELETE SET NULL,
    next_slot      TEXT    NOT NULL DEFAULT '' CHECK (next_slot IN ('', 'red', 'blue')),
    rounds         INTEGER NOT NULL DEFAULT 3,
    round_minutes  REAL    NOT NULL DEFAULT 2,
    is_break       INTEGER NOT NULL DEFAULT 0,                   -- Pause in der Fightcard
    block          TEXT    NOT NULL DEFAULT '',                  -- Main Fight / Main Card / Prelims
    style          TEXT    NOT NULL DEFAULT '',                  -- Muay Thai, K-1, Boxen
    weight_label   TEXT    NOT NULL DEFAULT '',                  -- "-66,6 kg"
    description    TEXT    NOT NULL DEFAULT '',                  -- Story zum Kampf
    minutes        INTEGER NOT NULL DEFAULT 0,                   -- Dauer im Zeitplan (0 = Standard)
    show_record    INTEGER NOT NULL DEFAULT 1,
    active         INTEGER NOT NULL DEFAULT 1,                   -- 0 = inaktiv (nicht oeffentlich)
    result_round   TEXT    NOT NULL DEFAULT '',
    external_id    TEXT    NOT NULL DEFAULT '',                  -- ID aus einer importierten Fightcard (haelt geteilte Links stabil)
    belt_label     TEXT    NOT NULL DEFAULT '',                  -- Titelkampf: "Oesterr. Muay Thai Titel"
    scheduled_time TEXT    NOT NULL DEFAULT '',                  -- HH:MM (geplant)
    status         TEXT    NOT NULL DEFAULT 'geplant'
                           CHECK (status IN ('geplant', 'laufend', 'beendet', 'abgesagt')),
    winner         TEXT    NOT NULL DEFAULT ''
                           CHECK (winner IN ('', 'red', 'blue', 'draw', 'none')),
    method         TEXT    NOT NULL DEFAULT '',                  -- KO, TKO, Punkte, Aufgabe ...
    result_note    TEXT    NOT NULL DEFAULT '',                  -- "3:0", "Runde 2 1:12"
    note           TEXT    NOT NULL DEFAULT '',
    started_at     TEXT,
    finished_at    TEXT,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_bouts_event    ON event_bouts(event_id, session_id, venue_id, order_no);
CREATE INDEX IF NOT EXISTS idx_bouts_category ON event_bouts(category_id, round_no, bracket_pos);
CREATE INDEX IF NOT EXISTS idx_bouts_status   ON event_bouts(event_id, status);

-- ------------------------------------------------------------- Sponsoren --
CREATE TABLE IF NOT EXISTS event_sponsors (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id   INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    name       TEXT    NOT NULL,
    url        TEXT    NOT NULL DEFAULT '',
    logo_path  TEXT    NOT NULL DEFAULT '',
    tile_color TEXT    NOT NULL DEFAULT '',              -- #rrggbb Kachelhintergrund
    is_main    INTEGER NOT NULL DEFAULT 0,               -- Hauptsponsor (grosse Kachel)
    published  INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_sponsors_event ON event_sponsors(event_id, sort_order);

-- ------------------------------------------------- Plattform-API (Schluessel) --

-- API-Schluessel "ek_...": gespeichert wird nur der SHA-256-Hash. scope:
--   read  – alles lesen (auch unveroeffentlichte Events)
--   write – zusaetzlich Kampfstatus/Ergebnisse, Anmeldungen, Sportler schreiben
-- gym_id gesetzt   = Schluessel eines Gyms (nur eigene Sportler/Anmeldungen) –
--                    die Gegenrichtung der Gym141-Kopplung.
-- event_id gesetzt = auf ein Event beschraenkt (z. B. eine gekoppelte Event-Website).
CREATE TABLE IF NOT EXISTS api_keys (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT    NOT NULL,
    key_prefix   TEXT    NOT NULL,                        -- erste Zeichen zur Anzeige
    key_hash     TEXT    NOT NULL UNIQUE,
    scope        TEXT    NOT NULL DEFAULT 'read' CHECK (scope IN ('read', 'write')),
    gym_id       INTEGER REFERENCES gyms(id)   ON DELETE CASCADE,
    event_id     INTEGER REFERENCES events(id) ON DELETE CASCADE,
    active       INTEGER NOT NULL DEFAULT 1,
    last_used_at TEXT,
    created_by   INTEGER REFERENCES users(id),
    created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- Ausgehende Webhooks: bei Aenderungen an einem Event wird die URL per POST
-- benachrichtigt (HMAC-SHA256 ueber den Body im Header X-Event141-Signature).
CREATE TABLE IF NOT EXISTS webhooks (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id    INTEGER REFERENCES events(id) ON DELETE CASCADE,  -- NULL = alle Events
    name        TEXT    NOT NULL DEFAULT '',
    url         TEXT    NOT NULL,
    secret      TEXT    NOT NULL,
    active      INTEGER NOT NULL DEFAULT 1,
    last_status TEXT    NOT NULL DEFAULT '',
    last_at     TEXT,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);


-- ---- Bildbibliothek, Galerien, Bericht, Kampf-Bilder (seit 0.6.0) --
-- tags: kleingeschrieben als ",tag1,tag2," – Suche per LIKE. event_media: kind 'bout' (ref_id = Kampf) | 'report' (ref_id = Event).
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
