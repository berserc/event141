<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\BoutRepo;
use App\Models\EventRepo;
use App\Models\GymRepo;
use RuntimeException;

/**
 * Fightcard im "fights.json"-Format der NAFN-Website (nafn.at):
 *
 *   export()  – liefert ein Event in genau diesem Format. Eine bestehende
 *               Event-Website kann damit Event141 als Datenquelle nutzen,
 *               ohne ihr Frontend umzubauen (Kopplung per API-Schluessel).
 *   import()  – legt aus einer fights.json (+ optional fighters.json) ein
 *               Gala-Event samt Gyms, Sportlern, Anmeldungen und Kaempfen an.
 *
 * Format: {event:{...}, fights:[...]} – fights in ANZEIGE-Reihenfolge
 * (Hauptkampf zuerst), chronologisch also von hinten nach vorne.
 */
final class Fightcard
{
    private const STATUS_OUT = ['geplant' => 'upcoming', 'laufend' => 'live', 'beendet' => 'done', 'abgesagt' => 'upcoming'];
    private const STATUS_IN  = ['upcoming' => 'geplant', 'live' => 'laufend', 'done' => 'beendet'];

    // ---------------------------------------------------------------- Export --

    /** @return array{event:array<string,mixed>,fights:list<array<string,mixed>>} */
    public static function export(array $event, string $baseUrl = ''): array
    {
        $bouts = BoutRepo::forEvent((int) $event['id']);
        $items = [];

        foreach ($bouts as $b) {
            // Freilose gehoeren nicht auf eine Fightcard.
            if ((string) $b['status'] === 'beendet' && (string) $b['method'] === 'Freilos') {
                continue;
            }

            $items[] = self::item($b, $baseUrl);
        }

        $tz    = new \DateTimeZone((string) Config::get('timezone', 'Europe/Vienna'));
        $start = new \DateTimeImmutable($event['starts_on'] . ' ' . ((string) $event['start_time'] ?: '00:00'), $tz);
        $tage  = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
        $mon   = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

        return [
            'event' => [
                'name'                => (string) $event['name'],
                'shortName'           => (string) ($event['short_name'] ?: $event['name']),
                'date'                => $start->format('c'),
                'dateLabel'           => $tage[(int) $start->format('w')] . ', ' . $start->format('j') . '. ' . $mon[(int) $start->format('n')] . ' ' . $start->format('Y'),
                'venue'               => (string) $event['venue_name'],
                'address'             => implode(', ', array_filter([$event['venue_street'], trim($event['venue_zip'] . ' ' . $event['venue_city'])])),
                'ticketUrl'           => (string) $event['ticket_url'],
                'doorsTime'           => (string) $event['doors_time'],
                'startTime'           => (string) $event['start_time'],
                'liveMode'            => (int) $event['live_mode'] === 1,
                'defaultFightMinutes' => (int) $event['default_bout_minutes'],
                'defaultBreakMinutes' => (int) $event['default_break_minutes'],
                'status'              => (string) $event['status'],
                'source'              => 'event141',
                'slug'                => (string) $event['slug'],
                'updatedAt'           => gmdate('c'),
            ],
            // Anzeige-Reihenfolge: Hauptkampf (chronologisch letzter) zuerst.
            'fights' => array_reverse($items),
        ];
    }

    /** @return array<string,mixed> */
    private static function item(array $b, string $baseUrl): array
    {
        $aktiv = (int) $b['active'] === 1 && (string) $b['status'] !== 'abgesagt';

        if ((int) $b['is_break'] === 1) {
            return [
                'id'       => (string) ($b['external_id'] ?? '') !== '' ? (string) $b['external_id'] : 'p' . (int) $b['id'],
                'type'     => 'break',
                'label'    => (string) ($b['title'] ?: 'Pause'),
                'duration' => (string) $b['note'],
                'minutes'  => (int) $b['minutes'],
                'status'   => self::STATUS_OUT[(string) $b['status']] ?? 'upcoming',
                'result'   => null,
                'active'   => $aktiv,
            ] + self::liveTimes($b);
        }

        $result = null;

        if ((string) $b['status'] === 'beendet' && in_array((string) $b['winner'], ['red', 'blue', 'draw'], true)) {
            $result = [
                'winner' => (string) $b['winner'],
                'method' => trim($b['method'] . ((string) $b['result_note'] !== '' ? ' ' . $b['result_note'] : '')),
                'round'  => (string) $b['result_round'],
            ];
        }

        $min = rtrim(rtrim(number_format((float) $b['round_minutes'], 1, ',', ''), '0'), ',');

        return [
            'id'          => (string) ($b['external_id'] ?? '') !== '' ? (string) $b['external_id'] : 'k' . (int) $b['id'],
            'boutId'      => (int) $b['id'],
            'no'          => (int) $b['bout_no'],
            'type'        => 'fight',
            'block'       => (string) $b['block'],
            'label'       => (string) $b['title'],
            'title'       => (string) ($b['belt_label'] ?: ($b['category_name'] ?? '')),
            'style'       => (string) $b['style'],
            'rounds'      => (int) $b['rounds'] . ' × ' . $min . ' Min.',
            'weight'      => (string) $b['weight_label'],
            'showRecord'  => (int) $b['show_record'] === 1,
            'description' => (string) $b['description'],
            'minutes'     => (int) $b['minutes'],
            'ring'        => (string) ($b['venue_name'] ?? ''),
            'status'      => self::STATUS_OUT[(string) $b['status']] ?? 'upcoming',
            'result'      => $result,
            'active'      => $aktiv,
            'red'         => self::corner($b, 'red', $baseUrl),
            'blue'        => self::corner($b, 'blue', $baseUrl),
        ] + self::liveTimes($b);
    }

    /** @return array<string,int> */
    private static function liveTimes(array $b): array
    {
        $out = [];

        if (!empty($b['started_at']) && in_array((string) $b['status'], ['laufend', 'beendet'], true)) {
            $out['liveSince'] = (int) strtotime($b['started_at'] . ' UTC');
        }

        if (!empty($b['finished_at']) && (string) $b['status'] === 'beendet') {
            $out['finishedAt'] = (int) strtotime($b['finished_at'] . ' UTC');
        }

        return $out;
    }

    /** @return array<string,mixed> */
    private static function corner(array $b, string $s, string $baseUrl): array
    {
        if ($b[$s . '_entry_id'] === null) {
            return ['name' => 'TBA', 'gym' => '', 'age' => '', 'record' => '', 'bio' => '', 'img' => '', 'video' => '', 'media' => 'photo', 'photoSec' => 3];
        }

        $nick = (string) $b[$s . '_nick'];
        $name = trim($b[$s . '_first'] . ($nick !== '' ? ' "' . $nick . '" ' : ' ') . $b[$s . '_last']);
        $age  = $b[$s . '_birth'] ? age_from((string) $b[$s . '_birth']) : ($b[$s . '_age'] !== null ? (int) $b[$s . '_age'] : null);

        return [
            'athleteId' => (int) $b[$s . '_athlete_id'],
            'name'      => $name,
            'gym'       => (string) $b[$s . '_gym'],
            'age'       => $age === null ? '' : (string) $age,
            'record'    => (int) $b[$s . '_w'] . '–' . (int) $b[$s . '_l'] . '–' . (int) $b[$s . '_d'],
            'bio'       => (string) $b[$s . '_bio'],
            'img'       => (string) $b[$s . '_photo'] !== '' ? $baseUrl . upload_url((string) $b[$s . '_photo']) : '',
            'video'     => (string) $b[$s . '_video'] !== '' ? $baseUrl . upload_url((string) $b[$s . '_video']) : '',
            'media'     => (string) $b[$s . '_media'],
            'photoSec'  => (int) $b[$s . '_photo_sec'],
        ];
    }

    /** "https://host" der aktuellen Anfrage (fuer absolute Medien-URLs). */
    public static function requestBase(): string
    {
        $host = (string) Config::get('canonical_host', '') ?: (string) ($_SERVER['HTTP_HOST'] ?? '');

        if ($host === '') {
            return '';
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        return ($https ? 'https://' : 'http://') . $host;
    }

    // ---------------------------------------------------------------- Import --

    /**
     * @param array<string,mixed> $data       Inhalt der fights.json
     * @param array<int|string,mixed> $pool   Inhalt der fighters.json (optional, liefert Bios/Fotos nach)
     * @param string $assetBase               Ordner oder URL, relativ zu dem img/video-Pfade liegen ('' = keine Medien)
     * @return array{event_id:int,bouts:int,athletes:int,gyms:int,media:int,log:list<string>}
     */
    public static function import(array $data, array $pool = [], string $assetBase = '', ?int $userId = null, bool $replace = false): array
    {
        // Ersetzen: ein frueher importiertes Event mit demselben Kuerzel behaelt seine ID
        // (API-Schluessel, Webhooks, Sponsoren, Bilder, Website-Einstellungen bleiben),
        // nur Aufbau, Anmeldungen und Kaempfe werden neu aufgebaut.
        $replaceId = null;

        if ($replace) {
            $slug = slugify((string) ($data['event']['shortName'] ?? '') ?: (string) ($data['event']['name'] ?? ''));
            $alt  = $slug !== '' ? EventRepo::findBySlug($slug) : null;
            $replaceId = $alt !== null ? (int) $alt['id'] : null;
        }

        $ev     = (array) ($data['event'] ?? []);
        $fights = array_values((array) ($data['fights'] ?? []));

        if (($ev['name'] ?? '') === '' || $fights === []) {
            throw new RuntimeException('Keine gültige Fightcard: event.name oder fights fehlen.');
        }

        $tz    = new \DateTimeZone((string) Config::get('timezone', 'Europe/Vienna'));
        $start = new \DateTimeImmutable((string) ($ev['date'] ?? 'today'));
        $start = $start->setTimezone($tz);
        $time  = preg_match('/^\d{1,2}:\d{2}$/', (string) ($ev['startTime'] ?? '')) ? parse_time((string) $ev['startTime']) : $start->format('H:i');

        // "Offenburger Gasse 17, 8160 Weiz" -> Strasse / PLZ / Ort
        $street = (string) ($ev['address'] ?? '');
        $zip    = '';
        $city   = '';

        if (preg_match('/^(.*?),\s*(\d{4,5})\s+(.+)$/u', $street, $m)) {
            [$street, $zip, $city] = [trim($m[1]), $m[2], trim($m[3])];
        }

        $stats = ['bouts' => 0, 'athletes' => 0, 'gyms' => 0, 'media' => 0, 'log' => []];

        $eventId = Database::transaction(static function () use ($ev, $fights, $pool, $assetBase, $userId, $start, $time, $street, $zip, $city, $replaceId, &$stats): int {
            if ($replaceId !== null) {
                foreach (['event_bouts', 'event_entries', 'event_sessions', 'event_days', 'event_venues'] as $table) {
                    Database::run("DELETE FROM $table WHERE event_id = ?", [$replaceId]);
                }

                Database::update('events', $replaceId, [
                    'name'                  => (string) $ev['name'],
                    'short_name'            => (string) ($ev['shortName'] ?? ''),
                    'starts_on'             => $start->format('Y-m-d'),
                    'start_time'            => $time,
                    'venue_name'            => (string) ($ev['venue'] ?? ''),
                    'venue_street'          => $street,
                    'venue_zip'             => $zip,
                    'venue_city'            => $city,
                    'ticket_url'            => (string) ($ev['ticketUrl'] ?? ''),
                    'updated_at'            => gmdate('Y-m-d H:i:s'),
                ]);
            }

            $eventId = $replaceId ?? Database::insert('events', [
                'slug'                  => EventRepo::uniqueSlug(slugify((string) ($ev['shortName'] ?? '') ?: (string) $ev['name'])),
                'name'                  => (string) $ev['name'],
                'short_name'            => (string) ($ev['shortName'] ?? ''),
                'type'                  => 'gala',
                'starts_on'             => $start->format('Y-m-d'),
                'start_time'            => $time,
                'doors_time'            => parse_time((string) ($ev['doorsTime'] ?? '')),
                'venue_name'            => (string) ($ev['venue'] ?? ''),
                'venue_street'          => $street,
                'venue_zip'             => $zip,
                'venue_city'            => $city,
                'ticket_url'            => (string) ($ev['ticketUrl'] ?? ''),
                'live_mode'             => !empty($ev['liveMode']) ? 1 : 0,
                'default_bout_minutes'  => max(1, (int) ($ev['defaultFightMinutes'] ?? 12)),
                'default_break_minutes' => max(1, (int) ($ev['defaultBreakMinutes'] ?? 15)),
                'status'                => 'geschlossen',
                'gym_registration'      => 0,
                'published'             => 0,
                'created_by'            => $userId,
            ]);

            $belt = self::fetchAsset($assetBase, 'img/guertel-cutout.webp', 'events', 'guertel-' . $eventId);

            if ($belt !== '') {
                Database::update('events', $eventId, ['belt_path' => $belt]);
            }

            $dayId     = Database::insert('event_days', ['event_id' => $eventId, 'day_date' => $start->format('Y-m-d'), 'label' => '', 'sort_order' => 10]);
            $sessionId = Database::insert('event_sessions', ['event_id' => $eventId, 'day_id' => $dayId, 'name' => 'Fightcard', 'starts_at' => $time, 'sort_order' => 10]);
            $venueId   = Database::insert('event_venues', ['event_id' => $eventId, 'name' => 'Ring', 'short_name' => 'R', 'color' => '#e63946', 'sort_order' => 10]);

            // Pool nach Namen indizieren (liefert Bio/Foto/Video nach, falls im Kampf leer).
            $poolByName = [];

            foreach ($pool as $p) {
                if (is_array($p) && isset($p['name'])) {
                    $poolByName[self::nameKey((string) $p['name'])] = $p;
                }
            }

            $gymIds     = [];
            $athleteIds = [];
            $order      = 10;
            $no         = 1;

            // Chronologisch = Liste von hinten nach vorne.
            foreach (array_reverse($fights) as $f) {
                $f      = (array) $f;
                $status = self::STATUS_IN[(string) ($f['status'] ?? 'upcoming')] ?? 'geplant';
                $live   = [
                    'started_at'  => isset($f['liveSince']) ? gmdate('Y-m-d H:i:s', (int) $f['liveSince']) : null,
                    'finished_at' => isset($f['finishedAt']) ? gmdate('Y-m-d H:i:s', (int) $f['finishedAt']) : null,
                ];

                if (($f['type'] ?? 'fight') === 'break') {
                    Database::insert('event_bouts', [
                        'event_id' => $eventId, 'session_id' => $sessionId, 'venue_id' => $venueId, 'order_no' => $order,
                        'is_break' => 1, 'external_id' => mb_substr((string) ($f['id'] ?? ''), 0, 120), 'title' => (string) ($f['label'] ?? 'Pause'), 'note' => (string) ($f['duration'] ?? ''),
                        'minutes' => (int) ($f['minutes'] ?? 0), 'status' => $status, 'rounds' => 1, 'round_minutes' => 1,
                        'active' => ($f['active'] ?? true) === false ? 0 : 1,
                    ] + $live);
                    $order += 10;
                    continue;
                }

                $entry = [];

                foreach (['red', 'blue'] as $side) {
                    $c = (array) ($f[$side] ?? []);

                    if (trim((string) ($c['name'] ?? '')) === '' || strtoupper(trim((string) $c['name'])) === 'TBA') {
                        $entry[$side] = null;
                        continue;
                    }

                    $c  += $poolByName[self::nameKey((string) $c['name'])] ?? [];
                    $key = self::nameKey((string) $c['name']);

                    if (!isset($athleteIds[$key])) {
                        $gymName = trim((string) ($c['gym'] ?? '')) ?: 'Ohne Gym';
                        $gKey    = mb_strtolower($gymName);

                        if (!isset($gymIds[$gKey])) {
                            $existing = Database::one('SELECT id FROM gyms WHERE name = ? COLLATE NOCASE AND deleted_at IS NULL', [$gymName]);

                            if ($existing !== null) {
                                $gymIds[$gKey] = (int) $existing['id'];
                            } else {
                                $gymIds[$gKey] = Database::insert('gyms', ['name' => $gymName, 'slug' => GymRepo::uniqueSlug(slugify($gymName)), 'status' => 'bestaetigt']);
                                $stats['gyms']++;
                            }
                        }

                        [$first, $nick, $last] = self::splitName((string) $c['name']);
                        [$w, $l, $d]           = self::splitRecord((string) ($c['record'] ?? ''));

                        $known = Database::one(
                            'SELECT id FROM athletes WHERE gym_id = ? AND first_name = ? COLLATE NOCASE AND last_name = ? COLLATE NOCASE',
                            [$gymIds[$gKey], $first, $last]
                        );

                        $row = [
                            'nickname' => $nick, 'age' => is_numeric($c['age'] ?? null) ? (int) $c['age'] : null,
                            'record_wins' => $w, 'record_losses' => $l, 'record_draws' => $d,
                            'bio' => (string) ($c['bio'] ?? ''),
                            'media_mode' => in_array($c['media'] ?? '', ['photo', 'video', 'both'], true) ? (string) $c['media'] : 'photo',
                            'photo_sec' => max(1, min(5, (int) ($c['photoSec'] ?? 3))),
                        ];

                        if ($known !== null) {
                            $athleteIds[$key] = (int) $known['id'];
                            Database::update('athletes', $athleteIds[$key], $row);
                        } else {
                            $athleteIds[$key] = Database::insert('athletes', $row + ['gym_id' => $gymIds[$gKey], 'first_name' => $first, 'last_name' => $last]);
                            $stats['athletes']++;
                        }

                        foreach (['img' => ['photo_path', 'sportler'], 'video' => ['video_path', 'sportler/video']] as $field => [$col, $dir]) {
                            $path = self::fetchAsset($assetBase, (string) ($c[$field] ?? ''), $dir, 'sportler-' . $athleteIds[$key]);

                            if ($path !== '') {
                                Database::update('athletes', $athleteIds[$key], [$col => $path]);
                                $stats['media']++;
                            }
                        }
                    }

                    $gymId = (int) Database::value('SELECT gym_id FROM athletes WHERE id = ?', [$athleteIds[$key]]);

                    $vorhanden = Database::one('SELECT id FROM event_entries WHERE event_id = ? AND athlete_id = ?', [$eventId, $athleteIds[$key]]);

                    $entry[$side] = $vorhanden !== null ? (int) $vorhanden['id'] : Database::insert('event_entries', [
                        'event_id' => $eventId, 'athlete_id' => $athleteIds[$key], 'gym_id' => $gymId,
                        'status' => 'bestaetigt', 'source' => 'admin', 'note' => 'Kampf ' . $no,
                    ]);
                }

                [$rounds, $minutes] = self::splitRounds((string) ($f['rounds'] ?? ''));
                $res                = (array) ($f['result'] ?? []);

                Database::insert('event_bouts', [
                    'event_id' => $eventId, 'session_id' => $sessionId, 'venue_id' => $venueId, 'order_no' => $order, 'bout_no' => $no,
                    'external_id' => mb_substr((string) ($f['id'] ?? ''), 0, 120),
                    'block' => (string) ($f['block'] ?? ''), 'title' => (string) ($f['label'] ?? ''), 'belt_label' => (string) ($f['title'] ?? ''),
                    'style' => (string) ($f['style'] ?? ''), 'weight_label' => (string) ($f['weight'] ?? ''),
                    'description' => (string) ($f['description'] ?? ''), 'minutes' => (int) ($f['minutes'] ?? 0),
                    'show_record' => ($f['showRecord'] ?? true) ? 1 : 0, 'active' => ($f['active'] ?? true) === false ? 0 : 1,
                    'rounds' => $rounds, 'round_minutes' => $minutes,
                    'red_entry_id' => $entry['red'], 'blue_entry_id' => $entry['blue'],
                    'status' => $status,
                    'winner' => $status === 'beendet' && in_array($res['winner'] ?? '', ['red', 'blue', 'draw'], true) ? (string) $res['winner'] : '',
                    'method' => $status === 'beendet' ? mb_substr((string) ($res['method'] ?? ''), 0, 60) : '',
                    'result_round' => $status === 'beendet' ? mb_substr((string) ($res['round'] ?? ''), 0, 20) : '',
                ] + $live);

                $stats['bouts']++;
                $order += 10;
                $no++;
            }

            return $eventId;
        });

        return ['event_id' => $eventId] + $stats;
    }

    /** Vergleichsschluessel eines Namens (ohne Spitznamen/Anfuehrungszeichen). */
    private static function nameKey(string $name): string
    {
        [$first, , $last] = self::splitName($name);

        return mb_strtolower($first . '|' . $last);
    }

    /** 'Fabienne "Lioness" Reiser' -> [Fabienne, Lioness, Reiser] @return array{0:string,1:string,2:string} */
    public static function splitName(string $name): array
    {
        $nick = '';

        if (preg_match('/["„“”\'‚‘’]\s*([^"„“”\'‚‘’]+?)\s*["„“”\'‚‘’]/u', $name, $m)) {
            $nick = trim($m[1]);
            $name = str_replace($m[0], ' ', $name);
        }

        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $last  = (string) array_pop($parts);

        return [implode(' ', $parts) ?: $last, $nick, $parts === [] ? '' : $last];
    }

    /** "4–1–0" -> [4,1,0] @return array{0:int,1:int,2:int} */
    private static function splitRecord(string $record): array
    {
        preg_match_all('/\d+/', $record, $m);

        return [(int) ($m[0][0] ?? 0), (int) ($m[0][1] ?? 0), (int) ($m[0][2] ?? 0)];
    }

    /** "3 × 2 Min." -> [3, 2.0] @return array{0:int,1:float} */
    private static function splitRounds(string $rounds): array
    {
        preg_match_all('/\d+(?:[.,]\d+)?/', $rounds, $m);

        return [max(1, (int) ($m[0][0] ?? 3)), max(0.5, (float) str_replace(',', '.', (string) ($m[0][1] ?? '2')))];
    }

    /** Kopiert/laedt eine Mediendatei in die Uploads; '' wenn nichts uebernommen wurde. */
    private static function fetchAsset(string $base, string $rel, string $subDir, string $basename): string
    {
        $rel = trim($rel);

        if ($rel === '' || str_ends_with(strtolower($rel), '.svg') || str_contains($rel, '..')) {
            return ''; // SVG-Silhouetten sind Platzhalter
        }

        $ext = strtolower(pathinfo((string) parse_url($rel, PHP_URL_PATH), PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm'], true)) {
            return '';
        }

        $source = preg_match('#^https?://#i', $rel) ? $rel : ($base === '' ? '' : rtrim($base, '/\\') . '/' . ltrim($rel, '/'));

        if ($source === '') {
            return '';
        }

        $bytes = preg_match('#^https?://#i', $source)
            ? @file_get_contents($source, false, stream_context_create(['http' => ['timeout' => 20]]))
            : (is_file($source) ? @file_get_contents($source) : false);

        if ($bytes === false || $bytes === '') {
            return '';
        }

        $dir = rtrim((string) Config::get('upload_dir'), '/\\') . '/' . $subDir;

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return '';
        }

        $file = $basename . '-' . substr(sha1($rel), 0, 6) . '.' . $ext;

        return file_put_contents($dir . '/' . $file, $bytes) === false ? '' : $subDir . '/' . $file;
    }
}
