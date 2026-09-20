<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Fightcard;
use App\Core\Timetable;
use App\Models\ApiKeyRepo;
use App\Models\AthleteRepo;
use App\Models\BoutRepo;
use App\Models\EntryRepo;
use App\Models\EventRepo;
use App\Models\GymRepo;

/**
 * Plattform-API (JSON, CORS *).
 *
 * Ohne Schluessel: veroeffentlichte Events lesen (/api/events, /api/event/…).
 * Mit Schluessel (Authorization: Bearer ek_… oder X-Api-Key):
 *   - Plattform-/Event-Schluessel: alles lesen, mit scope=write Kampfstatus und
 *     Ergebnisse schreiben – fuer gekoppelte Event-Websites, Anzeigetafeln, Apps.
 *   - Gym-Schluessel (/api/v1/gym/…): eigene Sportler abgleichen, zu offenen
 *     Events anmelden, Ergebnisse der eigenen Sportler abholen – die
 *     Gegenrichtung der Gym141-Kopplung (Gym141 -> Event141).
 */
final class ApiController
{
    /** @var array<string,mixed>|null */
    private ?array $key = null;

    private bool $keyResolved = false;

    // ------------------------------------------------------------ Lesen --

    public function ping(): void
    {
        $key = $this->requireKey();

        $this->json([
            'ok'      => true,
            'service' => 'event141',
            'version' => trim((string) @file_get_contents(BASE_ROOT . '/VERSION')),
            'key'     => [
                'name'  => $key['name'],
                'scope' => $key['scope'],
                'gym'   => $key['gym_id'] !== null ? (int) $key['gym_id'] : null,
                'event' => $key['event_id'] !== null ? (int) $key['event_id'] : null,
            ],
        ]);
    }

    public function events(): void
    {
        $key  = $this->key();
        $rows = $key !== null && $key['gym_id'] === null ? EventRepo::all() : EventRepo::published();

        if ($key !== null && $key['event_id'] !== null) {
            $rows = array_values(array_filter($rows, static fn (array $e): bool => (int) $e['id'] === (int) $key['event_id']));
        }

        $this->json(['events' => array_map(fn (array $e): array => $this->eventJson($e), $rows)]);
    }

    public function event(array $args): void
    {
        $event   = $this->loadEvent($args);
        $eventId = (int) $event['id'];
        $bouts   = BoutRepo::forEvent($eventId);
        $times   = Timetable::compute($event, $bouts);

        $this->json([
            'event'      => $this->eventJson($event),
            'days'       => array_map(static fn (array $d): array => [
                'date'     => $d['day_date'],
                'label'    => $d['label'],
                'sessions' => array_map(static fn (array $s): array => [
                    'id' => (int) $s['id'], 'name' => $s['name'], 'from' => $s['starts_at'], 'to' => $s['ends_at'],
                ], $d['sessions']),
            ], EventRepo::structure($eventId)),
            'venues'     => array_map(static fn (array $v): array => ['id' => (int) $v['id'], 'name' => $v['name'], 'short' => $v['short_name']], EventRepo::venues($eventId)),
            'categories' => array_map(static fn (array $c): array => ['id' => (int) $c['id'], 'name' => $c['name'], 'info' => EventRepo::categoryInfo($c)], EventRepo::categories($eventId)),
            'sponsors'   => array_map(static fn (array $s): array => [
                'name' => $s['name'], 'url' => $s['url'], 'main' => (bool) $s['is_main'], 'color' => $s['tile_color'],
                'logo' => $s['logo_path'] !== '' ? Fightcard::requestBase() . upload_url((string) $s['logo_path']) : null,
            ], EventRepo::sponsors($eventId, true)),
            'bouts'      => array_map(fn (array $b): array => $this->boutJson($b, $times), $bouts),
        ]);
    }

    /** Fightcard im fights.json-Format der NAFN-Website (Kopplung bestehender Event-Seiten). */
    public function fightcard(array $args): void
    {
        $this->json(Fightcard::export($this->loadEvent($args), Fightcard::requestBase()));
    }

    /** Nur Status/Ergebnisse – klein genug fuer regelmaessiges Abfragen. */
    public function live(array $args): void
    {
        $event = $this->loadEvent($args);
        $bouts = BoutRepo::forEvent((int) $event['id']);
        $times = Timetable::compute($event, $bouts);

        $this->json([
            'status' => $event['status'],
            'bouts'  => array_map(static fn (array $b): array => [
                'id'     => (int) $b['id'],
                'no'     => (int) $b['bout_no'],
                'status' => $b['status'],
                'winner' => $b['winner'],
                'method' => $b['method'],
                'venue'  => $b['venue_name'],
                'time'   => $times[(int) $b['id']]['label'] ?? '',
            ], $bouts),
            'at' => gmdate('c'),
        ]);
    }

    // --------------------------------------------------------- Schreiben --

    /**
     * Kampf steuern: {action: "live"|"reset"|"cancel"|"result", winner, method, round, note}.
     * Braucht einen Plattform-/Event-Schluessel mit scope=write.
     */
    public function boutAction(array $args): void
    {
        $key = $this->requireKey(true);

        if ($key['gym_id'] !== null) {
            $this->json(['error' => 'Gym-Schlüssel dürfen keine Kämpfe steuern.'], 403);
        }

        $event = $this->loadEvent($args);
        $bout  = BoutRepo::find((int) ($args['id'] ?? 0));

        if ($bout === null || (int) $bout['event_id'] !== (int) $event['id']) {
            $this->json(['error' => 'Kampf nicht gefunden.'], 404);
        }

        $body   = $this->body();
        $action = (string) ($body['action'] ?? '');

        switch ($action) {
            case 'live':
                BoutRepo::setStatus((int) $bout['id'], 'laufend');
                break;
            case 'reset':
                BoutRepo::reopen((int) $bout['id']);
                break;
            case 'cancel':
                BoutRepo::setStatus((int) $bout['id'], 'abgesagt');
                break;
            case 'result':
                $winner = (string) ($body['winner'] ?? '');

                if (!isset(BoutRepo::WINNER[$winner])) {
                    $this->json(['error' => 'winner muss red, blue, draw oder none sein.'], 422);
                }

                BoutRepo::setResult((int) $bout['id'], $winner, (string) ($body['method'] ?? ''), (string) ($body['note'] ?? ''), (string) ($body['round'] ?? ''));
                break;
            default:
                $this->json(['error' => 'Unbekannte action (live, reset, cancel, result).'], 422);
        }

        Audit::logAs(null, 'api:' . $key['name'], 'bout_' . $action, 'event', (int) $event['id'], 'Kampf #' . $bout['bout_no']);

        $fresh = BoutRepo::find((int) $bout['id']) ?? $bout;
        $this->json(['ok' => true, 'bout' => $this->boutJson($fresh, [])]);
    }

    // ---------------------------------------------------- Gym-Schluessel --

    public function gymInfo(): void
    {
        [$key, $gym] = $this->requireGymKey();

        $this->json([
            'gym' => [
                'id' => (int) $gym['id'], 'name' => $gym['name'], 'short_name' => $gym['short_name'],
                'city' => $gym['city'], 'status' => $gym['status'],
            ],
            'scope'    => $key['scope'],
            'athletes' => (int) Database::value('SELECT COUNT(*) FROM athletes WHERE gym_id = ? AND active = 1', [(int) $gym['id']]),
        ]);
    }

    /** Events mit offener Anmeldung samt Kategorien und den eigenen Anmeldungen. */
    public function gymEvents(): void
    {
        [, $gym] = $this->requireGymKey();

        $out = [];

        foreach (EventRepo::openForRegistration() as $e) {
            $out[] = $this->eventJson($e) + [
                'registration_until' => $e['registration_until'],
                'entry_fee'          => (float) $e['entry_fee'],
                'categories'         => array_map(static fn (array $c): array => [
                    'id' => (int) $c['id'], 'name' => $c['name'], 'info' => EventRepo::categoryInfo($c),
                    'gender' => $c['gender'], 'age_min' => $c['age_min'], 'age_max' => $c['age_max'],
                    'weight_min' => $c['weight_min'], 'weight_max' => $c['weight_max'],
                ], EventRepo::categories((int) $e['id'])),
                'entries'            => array_map(fn (array $x): array => $this->entryJson($x), EntryRepo::forGymEvent((int) $gym['id'], (int) $e['id'])),
            ];
        }

        $this->json(['events' => $out]);
    }

    public function gymAthletes(): void
    {
        [, $gym] = $this->requireGymKey();

        $this->json(['athletes' => array_map(
            fn (array $a): array => $this->athleteJson($a),
            AthleteRepo::forGym((int) $gym['id'])
        )]);
    }

    /**
     * Sportler abgleichen: {athletes:[{external_ref, first_name, last_name, birthdate,
     * gender, weight, nickname, nationality, email, phone}]} – Zuordnung ueber
     * external_ref (z. B. die Mitglieds-ID aus Gym141), sonst ueber den Namen.
     */
    public function gymAthletesUpsert(): void
    {
        [$key, $gym] = $this->requireGymKey(true);

        $list = (array) ($this->body()['athletes'] ?? []);

        if ($list === [] || count($list) > 500) {
            $this->json(['error' => 'athletes fehlt (1–500 Einträge).'], 422);
        }

        $created = 0;
        $updated = 0;
        $result  = [];

        foreach ($list as $a) {
            $a     = (array) $a;
            $first = trim((string) ($a['first_name'] ?? ''));
            $last  = trim((string) ($a['last_name'] ?? ''));
            $ref   = mb_substr(trim((string) ($a['external_ref'] ?? '')), 0, 80);

            if ($first === '' || $last === '') {
                continue;
            }

            $gender = in_array($a['gender'] ?? '', ['m', 'w', 'd'], true) ? (string) $a['gender'] : 'unbekannt';

            $data = array_filter([
                'first_name'  => $first,
                'last_name'   => $last,
                'nickname'    => isset($a['nickname']) ? (string) $a['nickname'] : null,
                'birthdate'   => isset($a['birthdate']) ? parse_date((string) $a['birthdate']) : null,
                'gender'      => isset($a['gender']) ? $gender : null,
                'nationality' => isset($a['nationality']) ? strtoupper(mb_substr((string) $a['nationality'], 0, 2)) : null,
                'weight'      => isset($a['weight']) && is_numeric($a['weight']) ? (float) $a['weight'] : null,
                'email'       => isset($a['email']) ? mb_substr((string) $a['email'], 0, 200) : null,
                'phone'       => isset($a['phone']) ? mb_substr((string) $a['phone'], 0, 60) : null,
            ], static fn ($v): bool => $v !== null);

            $existing = $ref !== ''
                ? Database::one('SELECT id FROM athletes WHERE gym_id = ? AND external_ref = ?', [(int) $gym['id'], $ref])
                : null;

            $existing ??= Database::one(
                'SELECT id FROM athletes WHERE gym_id = ? AND first_name = ? COLLATE NOCASE AND last_name = ? COLLATE NOCASE',
                [(int) $gym['id'], $first, $last]
            );

            if ($existing !== null) {
                Database::update('athletes', (int) $existing['id'], $data + ['external_ref' => $ref, 'updated_at' => gmdate('Y-m-d H:i:s')]);
                $id = (int) $existing['id'];
                $updated++;
            } else {
                $id = Database::insert('athletes', $data + ['gym_id' => (int) $gym['id'], 'external_ref' => $ref]);
                $created++;
            }

            $result[] = ['external_ref' => $ref, 'athlete_id' => $id];
        }

        Audit::logAs(null, 'api:' . $key['name'], 'api_athletes', 'gym', (int) $gym['id'], "$created neu, $updated aktualisiert");
        $this->json(['ok' => true, 'created' => $created, 'updated' => $updated, 'athletes' => $result]);
    }

    /** Anmelden: {entries:[{athlete_id | external_ref, category_id, note}]} */
    public function gymEnter(array $args): void
    {
        [$key, $gym] = $this->requireGymKey(true);

        $event = EventRepo::findBySlug((string) ($args['slug'] ?? ''));

        if ($event === null || !EventRepo::registrationOpen($event)) {
            $this->json(['error' => 'Die Anmeldung für dieses Event ist nicht (mehr) möglich.'], 409);
        }

        $catIds  = array_map(static fn (array $c): int => (int) $c['id'], EventRepo::categories((int) $event['id']));
        $created = [];
        $errors  = [];

        foreach ((array) ($this->body()['entries'] ?? []) as $i => $x) {
            $x = (array) $x;

            $athlete = isset($x['athlete_id'])
                ? Database::one('SELECT * FROM athletes WHERE id = ? AND gym_id = ? AND active = 1', [(int) $x['athlete_id'], (int) $gym['id']])
                : Database::one('SELECT * FROM athletes WHERE external_ref = ? AND external_ref <> \'\' AND gym_id = ? AND active = 1', [(string) ($x['external_ref'] ?? ''), (int) $gym['id']]);

            if ($athlete === null) {
                $errors[] = ['index' => $i, 'error' => 'Sportler nicht gefunden.'];
                continue;
            }

            $cat = isset($x['category_id']) ? (int) $x['category_id'] : null;

            if ($catIds !== [] && ($cat === null || !in_array($cat, $catIds, true))) {
                $errors[] = ['index' => $i, 'error' => 'category_id fehlt oder gehört nicht zum Event.'];
                continue;
            }

            if ($catIds === []) {
                $cat = null;
            }

            $dup = Database::one(
                'SELECT id, status FROM event_entries WHERE event_id = ? AND athlete_id = ? AND category_id IS ?',
                [(int) $event['id'], (int) $athlete['id'], $cat]
            );

            if ($dup !== null) {
                if ($dup['status'] === 'abgemeldet') {
                    Database::update('event_entries', (int) $dup['id'], ['status' => 'angemeldet', 'updated_at' => gmdate('Y-m-d H:i:s')]);
                }

                $created[] = ['entry_id' => (int) $dup['id'], 'athlete_id' => (int) $athlete['id'], 'existing' => true];
                continue;
            }

            $id = Database::insert('event_entries', [
                'event_id' => (int) $event['id'], 'athlete_id' => (int) $athlete['id'], 'gym_id' => (int) $gym['id'],
                'category_id' => $cat, 'status' => 'angemeldet', 'source' => 'gym',
                'note' => mb_substr((string) ($x['note'] ?? ''), 0, 200),
            ]);

            $created[] = ['entry_id' => $id, 'athlete_id' => (int) $athlete['id'], 'existing' => false];
        }

        Audit::logAs(null, 'api:' . $key['name'], 'gym_entries', 'event', (int) $event['id'], $gym['name'] . ': ' . count($created) . ' per API');
        $this->json(['ok' => $errors === [], 'entries' => $created, 'errors' => $errors], $created === [] && $errors !== [] ? 422 : 200);
    }

    public function gymWithdraw(array $args): void
    {
        [, $gym] = $this->requireGymKey(true);

        $entry = Database::one('SELECT * FROM event_entries WHERE id = ? AND gym_id = ?', [(int) ($args['id'] ?? 0), (int) $gym['id']]);
        $event = $entry === null ? null : EventRepo::find((int) $entry['event_id']);

        if ($entry === null || $event === null) {
            $this->json(['error' => 'Anmeldung nicht gefunden.'], 404);
        }

        if (!EventRepo::registrationOpen($event)) {
            $this->json(['error' => 'Abmeldung nur, solange die Anmeldung offen ist.'], 409);
        }

        Database::update('event_entries', (int) $entry['id'], ['status' => 'abgemeldet', 'updated_at' => gmdate('Y-m-d H:i:s')]);
        Audit::logAs(null, 'api:gym', 'gym_withdraw', 'event', (int) $event['id'], $gym['name']);
        $this->json(['ok' => true]);
    }

    /** Kaempfe der eigenen Sportler (alle Events) – z. B. fuer "Erfolge" in Gym141. */
    public function gymResults(): void
    {
        [, $gym] = $this->requireGymKey();

        $rows = Database::all(
            "SELECT b.id FROM event_bouts b
               LEFT JOIN event_entries r ON r.id = b.red_entry_id
               LEFT JOIN event_entries l ON l.id = b.blue_entry_id
              WHERE b.is_break = 0 AND b.method <> 'Freilos' AND (r.gym_id = ? OR l.gym_id = ?)
              ORDER BY b.id DESC LIMIT 500",
            [(int) $gym['id'], (int) $gym['id']]
        );

        $out = [];

        foreach ($rows as $r) {
            $b = BoutRepo::find((int) $r['id']);

            if ($b === null) {
                continue;
            }

            $event = EventRepo::find((int) $b['event_id']);

            foreach (['red' => 'blue', 'blue' => 'red'] as $own => $other) {
                $ref = Database::one(
                    'SELECT a.id, a.external_ref, a.gym141_member_id, x.gym_id FROM event_entries x JOIN athletes a ON a.id = x.athlete_id WHERE x.id = ?',
                    [(int) ($b[$own . '_entry_id'] ?? 0)]
                );

                if ($ref === null || (int) $ref['gym_id'] !== (int) $gym['id']) {
                    continue;
                }

                $w = (string) $b['winner'];

                $out[] = [
                    'bout_id'          => (int) $b['id'],
                    'event'            => $event['name'] ?? '',
                    'event_slug'       => $event['slug'] ?? '',
                    'date'             => $b['day_date'] ?: ($event['starts_on'] ?? null),
                    'athlete_id'       => (int) $ref['id'],
                    'external_ref'     => (string) $ref['external_ref'],
                    'gym141_member_id' => $ref['gym141_member_id'] !== null ? (int) $ref['gym141_member_id'] : null,
                    'athlete'          => BoutRepo::cornerName($b, $own),
                    'opponent'         => BoutRepo::cornerName($b, $other),
                    'opponent_gym'     => (string) ($b[$other . '_gym'] ?? ''),
                    'category'         => (string) ($b['category_name'] ?? $b['weight_label']),
                    'style'            => (string) $b['style'],
                    'status'           => (string) $b['status'],
                    'outcome'          => $b['status'] !== 'beendet' ? null : ($w === $own ? 'win' : ($w === $other ? 'loss' : ($w === 'draw' ? 'draw' : 'nc'))),
                    'method'           => (string) $b['method'],
                    'round'            => (string) $b['result_round'],
                ];
            }
        }

        $this->json(['results' => $out]);
    }

    // ------------------------------------------------------------- intern --

    /** @return array<string,mixed>|null der Schluessel dieser Anfrage (null = keiner/ungueltig) */
    private function key(): ?array
    {
        if ($this->keyResolved) {
            return $this->key;
        }

        $this->keyResolved = true;

        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $raw    = str_starts_with($header, 'Bearer ') ? substr($header, 7) : (string) ($_SERVER['HTTP_X_API_KEY'] ?? '');
        $raw    = trim($raw);

        if ($raw === '') {
            return null;
        }

        $this->key = ApiKeyRepo::resolve($raw);

        if ($this->key === null) {
            $this->json(['error' => 'Ungültiger oder deaktivierter API-Schlüssel.'], 401);
        }

        return $this->key;
    }

    /** @return array<string,mixed> */
    private function requireKey(bool $write = false): array
    {
        $key = $this->key();

        if ($key === null) {
            $this->json(['error' => 'API-Schlüssel erforderlich (Authorization: Bearer ek_…).'], 401);
        }

        if ($write && $key['scope'] !== 'write') {
            $this->json(['error' => 'Dieser Schlüssel darf nur lesen.'], 403);
        }

        return $key;
    }

    /** @return array{0:array<string,mixed>,1:array<string,mixed>} [Schluessel, Gym] */
    private function requireGymKey(bool $write = false): array
    {
        $key = $this->requireKey($write);
        $gym = $key['gym_id'] === null ? null : GymRepo::find((int) $key['gym_id']);

        if ($gym === null || $gym['status'] === 'gesperrt') {
            $this->json(['error' => 'Dieser Endpunkt braucht den Schlüssel eines (nicht gesperrten) Gyms.'], 403);
        }

        return [$key, $gym];
    }

    /** Veroeffentlichte Events fuer alle; mit Plattform-/Event-Schluessel auch Entwuerfe. */
    private function loadEvent(array $args): array
    {
        $event = EventRepo::findBySlug((string) ($args['slug'] ?? ''));
        $key   = $this->key();

        $darf = $event !== null && (
            (int) $event['published'] === 1
            || ($key !== null && $key['gym_id'] === null && ($key['event_id'] === null || (int) $key['event_id'] === (int) $event['id']))
        );

        if ($event !== null && $key !== null && $key['event_id'] !== null && (int) $key['event_id'] !== (int) $event['id']) {
            $darf = false;
        }

        if (!$darf) {
            $this->json(['error' => 'Event nicht gefunden.'], 404);
        }

        return $event;
    }

    private function eventJson(array $e): array
    {
        return [
            'id'         => (int) $e['id'],
            'slug'       => $e['slug'],
            'name'       => $e['name'],
            'short_name' => $e['short_name'] ?? '',
            'type'       => $e['type'],
            'sport'      => $e['sport'],
            'tagline'    => $e['tagline'],
            'starts_on'  => $e['starts_on'],
            'ends_on'    => $e['ends_on'],
            'doors_time' => $e['doors_time'],
            'start_time' => $e['start_time'],
            'venue'      => trim($e['venue_name'] . ', ' . $e['venue_zip'] . ' ' . $e['venue_city'], ', '),
            'status'     => $e['status'],
            'published'  => (int) $e['published'] === 1,
            'live_mode'  => (int) ($e['live_mode'] ?? 0) === 1,
            'registration_open' => EventRepo::registrationOpen($e),
            'ticket_url' => $e['ticket_url'],
            'poster_url' => $e['poster_path'] !== '' ? Fightcard::requestBase() . upload_url((string) $e['poster_path']) : null,
            'url'        => Fightcard::requestBase() . url('/e/' . $e['slug']),
        ];
    }

    /** @param array<int,array<string,mixed>> $times */
    private function boutJson(array $b, array $times): array
    {
        $corner = static fn (string $s): ?array => $b[$s . '_entry_id'] === null ? null : [
            'athlete_id' => (int) $b[$s . '_athlete_id'],
            'name'       => BoutRepo::cornerName($b, $s),
            'nickname'   => $b[$s . '_nick'],
            'gym'        => $b[$s . '_gym'],
            'nat'        => $b[$s . '_nat'],
            'record'     => (int) $b[$s . '_w'] . '-' . (int) $b[$s . '_l'] . '-' . (int) $b[$s . '_d'],
            'photo'      => (string) $b[$s . '_photo'] !== '' ? Fightcard::requestBase() . upload_url((string) $b[$s . '_photo']) : null,
        ];

        return [
            'id'       => (int) $b['id'],
            'no'       => (int) $b['bout_no'],
            'title'    => $b['title'],
            'block'    => $b['block'],
            'style'    => $b['style'],
            'weight'   => $b['weight_label'],
            'category' => $b['category_name'],
            'round'    => $b['round_label'],
            'rounds'   => (int) $b['rounds'],
            'round_minutes' => (float) $b['round_minutes'],
            'day'      => $b['day_date'],
            'session'  => $b['session_name'],
            'venue'    => $b['venue_name'],
            'order'    => (int) $b['order_no'],
            'time'     => $times[(int) $b['id']]['label'] ?? $b['scheduled_time'],
            'is_break' => (bool) $b['is_break'],
            'active'   => (int) $b['active'] === 1,
            'status'   => $b['status'],
            'red'      => $corner('red'),
            'blue'     => $corner('blue'),
            'winner'   => $b['winner'],
            'method'   => $b['method'],
            'result_round' => $b['result_round'],
            'result'   => $b['result_note'],
        ];
    }

    private function athleteJson(array $a): array
    {
        return [
            'id' => (int) $a['id'], 'external_ref' => $a['external_ref'], 'gym141_member_id' => $a['gym141_member_id'],
            'first_name' => $a['first_name'], 'last_name' => $a['last_name'], 'nickname' => $a['nickname'],
            'birthdate' => $a['birthdate'], 'gender' => $a['gender'], 'weight' => $a['weight'],
            'record' => AthleteRepo::record($a), 'active' => (int) $a['active'] === 1,
        ];
    }

    private function entryJson(array $x): array
    {
        return [
            'entry_id' => (int) $x['id'], 'athlete_id' => (int) $x['athlete_id'],
            'athlete' => trim($x['first_name'] . ' ' . $x['last_name']),
            'category_id' => $x['category_id'] !== null ? (int) $x['category_id'] : null,
            'category' => $x['category_name'], 'status' => $x['status'],
        ];
    }

    /** @return array<string,mixed> */
    private function body(): array
    {
        $raw  = (string) file_get_contents('php://input');
        $data = $raw !== '' ? json_decode($raw, true) : null;

        return is_array($data) ? $data : $_POST;
    }

    private function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, X-Api-Key, Content-Type');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
