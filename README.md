# Event141

Event- und Turnierverwaltung für Kampfsport und Vereinssport auf Shared
Webspace – Galas (Fight Nights) **und** Turniere in **einer** kleinen
PHP-Anwendung. Kein Composer, kein MySQL, kein Build-Schritt: Dateien
hochladen, `setup.php` aufrufen, fertig. Schwesterprodukt von
[Gym141](https://github.com/berserc/gym141) und mit diesem gekoppelt.

## Funktionen

**Events (Verwaltung `/admin`)**
- Zwei Arten, jederzeit umstellbar: **Gala** (Fightcard, Kämpfe einzeln
  zusammengestellt, Pausen/Showblöcke) und **Turnier** (Kategorien,
  Anmeldungen, generierter K.-o.-Turnierbaum mit Setzliste und Freilosen)
- **Aufbau**: mehrtägig, jeder Tag in **Abschnitte** unterteilt (Vormittag –
  Vorrunden, Abend – Finals …), beliebig viele **Wettkampfstätten** (Ring 1,
  Ring 2, Matte A …)
- **Zeitplan**: Kämpfe je Abschnitt auf die Wettkampfstätten verteilen
  (automatisch reihum oder einzeln), Reihenfolge per Pfeil, Kampfnummern
- **Ringansicht** für den Kampfrichtertisch: Kampf starten, Sieger + Siegart
  mit großen Schaltflächen; Sieger rücken automatisch im Turnierbaum auf
- **Anmeldungen**: bestätigen/ablehnen, Sammelaktionen, Wiegen, Setzposition,
  Startgeld, CSV-Export
- Rollen: Admin, Organisation, Ringleitung

**Fightcard wie auf einer Event-Homepage (seit 0.2)**
- Hauptkampf als große Karte, Kämpfe nach Blöcken (Main Card, Prelims …), Pausen,
  **Story und Bios** auf der Kampf-Detailseite, Kampfbilanz
- **Animierte Fightcard**: je Kämpfer Foto, Video in Schleife oder Foto ↔ Video im
  Wechsel – beide Ecken eines Kampfs laufen synchron
- **Voraussichtliche Beginnzeiten** aus Startzeit und Dauer; im **Live-Modus**
  zählen die echten Start-/Endzeiten, ein überziehender Kampf schiebt alles Folgende
- Countdown, Live-Leiste („Jetzt im Ring“), automatische Aktualisierung,
  Location mit Karte (erst nach Klick geladen), Ticketpreise, Sponsoren-Kacheln
- Kämpfe **inaktiv** setzen (Absage: weg von Website und Zeitplan, Daten bleiben)
- **Listen & Druck**: Fightcard-Aushang, Kämpfer-Checkliste (Waage, Musik, ärztliche
  Untersuchung), Kabineneinteilung mit Türschildern und Gegner-Konfliktprüfung
- **Import** einer bestehenden Fightcard im NAFN-Format (`fights.json`) samt
  Kämpfer-Pool, Fotos und Videos – per Adresse, Upload oder `bin/import-nafn.php`

**Zentrale Plattform mit API (seit 0.2)** – siehe [docs/api.md](docs/api.md)
- API-Schlüssel (Plattform, je Event, je Gym; Lesen oder Schreiben), Webhooks mit HMAC-Signatur
- Bestehende Event-Websites koppeln: `/api/v1/event/{slug}/fightcard` liefert das
  NAFN-Format – die Website behält ihr Frontend, die Daten kommen aus Event141
- Kampfstatus und Ergebnisse per API setzen (Anzeigetafel, App, Kampfrichter-Tool)
- **Gym141 in beide Richtungen**: Mitglieder aus Gym141 holen (Gym-Bereich) *und*
  per Gym-Schlüssel von Gym141 aus Sportler abgleichen, anmelden, Ergebnisse abholen
- **Bilder, Galerien, Bericht (seit 0.6)**: Bildbibliothek mit Tags (Upload wird
  verkleinert und EXIF-gedreht), Suche und Auswahl im Backend; je Event ein Bericht
  (Titelbild, Text, Bilder) und Galerien mit eigener Seite; je Kampf Nachwort und
  Bilder – auch in der Fightcard-API (`epilog`, `gallery`)
- **Ticket141 (seit 0.5)**: Event per Knopf im Ticketshop anlegen, Kategorien mit
  Preis und Verfügbarkeit plus „Tickets kaufen“ (oder Shop-Widget) auf der
  Event-Seite, Verkaufszahlen in der Verwaltung

**Gym-Bereich (`/gym`)**
- Gyms/Vereine registrieren sich selbst, legen ihre Sportler an und melden sie
  zu offenen Events in der passenden Kategorie an (mit Hinweisen bei
  Alter/Gewicht außerhalb der Kategorie)
- **Gym141-Kopplung**: einmal mit der eigenen Gym141-Instanz verbinden
  (Verwaltungs-Benutzer → Bearer-Token) und Mitglieder als Sportler
  übernehmen – ohne Abtippen, jederzeit aktualisierbar

**Event-Website**
- Startseite mit allen Events – oder **ein Event als komplette Homepage**
  (Einstellungen → Startseite), damit eine Installation z. B. die Seite einer
  Fight Night ist
- Je Event: Übersicht mit Poster/Titelbild, Fightcard bzw. Turnierbäume,
  Zeitplan nach Tag/Abschnitt/Ring, Teilnehmer je Gym, Ergebnisse, LIVE-Kennzeichnung
- Lese-API (`/api/events`, `/api/event/{slug}`, `/api/event/{slug}/live`)
  für Anzeigetafeln und fremde Websites

## Anforderungen

- PHP 8.1 oder neuer mit `pdo_sqlite`, `sqlite3`, `mbstring`, `gd`
- Optional: `curl` (Gym141-Kopplung; sonst PHP-Streams mit `allow_url_fopen`)
- Kein MySQL nötig – die Daten liegen in einer SQLite-Datei unter `data/`

## Installation auf Shared Webspace

1. Alle Dateien per FTP/SFTP hochladen.
2. Den Docroot der Domain auf den Ordner **`public/`** zeigen lassen (sonst in
   `app/config.php` den `base_path` setzen).
3. Im Browser **`https://deine-domain.tld/setup.php`** aufrufen und dem
   Assistenten folgen (Veranstaltername, Admin-Zugang).
4. Anmelden unter `/admin`, unter **Events → Neues Event** starten: Grunddaten →
   Aufbau (Tage, Abschnitte, Wettkampfstätten) → Kategorien → Anmeldung öffnen.
5. `setup.php` löschen oder in `app/config.php` den `setup_key` leer lassen.

Ein eigenes Logo? Als `public/assets/img/logo.png` (oder `.svg`) hochladen.

## Updates

Unter **Updates** in der Verwaltung (Superuser) nach neuen Versionen suchen und
per Klick einspielen: das Release-ZIP wird vom Update-Server geladen
(SHA-256-geprüft), vorher die Datenbank gesichert, dann werden nur die
Anwendungsdateien ersetzt – `data/`, `public/uploads/` und `app/config.php`
bleiben unangetastet, Migrationen laufen automatisch. Eigene Dateien, die ein
Update überleben sollen, in `app/config.php` unter `update_protected` eintragen.

Manuell: neue Version hochladen (`data/` und `public/uploads/` **nicht**
überschreiben) und `php bin/migrate.php` bzw. `setup.php?key=…` aufrufen.
Release bauen: `php bin/release.php --version=X.Y.Z --changelog="…"`.

### Kommandozeile (alternativ)

```bash
php bin/install.php --admin=admin --password=Geheim123
php bin/migrate.php          # nach Updates
php bin/backup.php --keep=14 # Sicherung nach data/backups/
```

### Lokal entwickeln

```bash
php -S localhost:8124 -t public public/router.php
```

## Ablauf eines Turniers

1. Event anlegen (Typ Turnier), Tage/Abschnitte/Ringe im **Aufbau**, **Kategorien**.
2. Status auf **Anmeldung offen** – Gyms melden ihre Sportler im Gym-Bereich an
   (oder die Verwaltung trägt sie ein).
3. Anmeldungen **bestätigen**, wiegen, ggf. Setzposition vergeben.
4. Unter **Kämpfe** je Kategorie den **Turnierbaum erzeugen**.
5. Im **Zeitplan** die Runden auf Abschnitte und Ringe **verteilen**.
6. Am Wettkampftag: **Ringansicht** je Ring – Kämpfe starten, Ergebnisse eintragen.
   Sieger rücken automatisch auf; die Website zeigt Live-Status und Ergebnisse.

## Sprachen

Die Oberfläche (Website, Gym-Bereich, Verwaltung) ist zwischen **Deutsch und
Englisch** umschaltbar – Umschalter in der Kopfzeile, Vorgabe unter
*Einstellungen*. Quellsprache ist Deutsch: `t('Deutscher Text')` schlägt die
Übersetzung in `app/lang/<sprache>/*.php` nach und fällt auf den deutschen Text
zurück. Eine weitere Sprache ist ein weiterer Ordner plus ein Eintrag in
`App\Core\I18n::LANGS`. `php bin/i18n-check.php` meldet Texte ohne Übersetzung.

## Regelsätze

Unter `app/Rulesets/` liegen die Wettkampfstrukturen der Verbände als
PHP-Dateien: **WAKO Kickboxen** (alle Tatami- und Ring-Disziplinen),
**Olympisches Boxen** (World Boxing, U13/U15 nach ÖBV) und **IFMA Muay Thai**
(Regelwerk v3.0). Beim Event unter *Kategorien → Aus Regelsatz anlegen* werden
daraus alle Alters- und Gewichtsklassen mit Rundenzahl und Rundenzeit erzeugt;
*Regelsätze* in der Verwaltung zeigt die Tabellen zum Nachschlagen. Ein eigener
Regelsatz ist eine weitere Datei im selben Format (siehe `app/Core/Ruleset.php`).

Beispiel-Turnier für eine leere Installation – alle Kategorien eines
Regelsatzes, erfundene Gyms und Sportler, Turnierbäume und Zeitplan:

```bash
php bin/seed-tournament.php --ruleset=wako --name="Kickboxing Open" --days=3 --rings=4 --tatamis=6
```

## Kopplung mit Gym141

Im Gym-Bereich (oder in der Verwaltung beim Gym) unter **Gym141**: Adresse der
Instanz plus Benutzername/Passwort eines Verwaltungs-Benutzers eingeben.
Event141 meldet sich an der Gym141-Verwaltungs-API (`/api/app/verwaltung/*`)
an und speichert nur das Token. Danach: Mitglieder auswählen → als Sportler
übernehmen (Name, Geburtsdatum, Kontakt; bereits übernommene werden
aktualisiert, nicht doppelt angelegt).

## Kopplung mit Ticket141

Ticketverkauf über das Schwesterprodukt [Ticket141](https://devworld-llc.com)
(Pro-Funktion): unter **Einstellungen → Ticket141** Adresse der Instanz und
einen API-Schlüssel (`tk_…`, Rechte „write“) eintragen. In der Event-Übersicht
legt **In Ticket141 anlegen** das Event dort per `POST /api/v1/events` an
(Upsert über `external_source=event141` + `external_ref=<kürzel>`) und merkt
sich das Ticket141-Kürzel (`events.ticket141_slug`). Die öffentliche
Event-Seite zeigt dann die Kategorien mit Preis und Verfügbarkeit (5 Minuten
gepuffert) und einen „Tickets kaufen“-Knopf – optional den Shop als
eingebettetes Widget (`<base>/embed.js`). Die Verwaltung zeigt verkaufte
Tickets, Umsatz und Einlass; die Lese-API liefert die Shop-Adresse als
`tickets_url`.

## Verwaltetes Hosting

`product.json` beschreibt die Anwendung für die DevWorld Cloud (Provisioner):
Docroot, Install-/Migrate-/Backup-Befehle, geschützte Pfade, Betriebsmodi.

## Lizenz

MIT – siehe `LICENSE`.
