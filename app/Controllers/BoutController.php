<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Bracket;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
use App\Models\BoutRepo;
use App\Models\EntryRepo;
use App\Models\EventRepo;

/**
 * Kaempfe: Fightcard (Gala) bzw. Turnierbaeume (Turnier), Zeitplan mit
 * Tagen/Abschnitten/Wettkampfstaetten, Ergebnisse und Ringansicht.
 */
final class BoutController
{
    // --------------------------------------------------------------- Liste --

    public function index(array $args): void
    {
        AuthController::requireLogin();

        $event      = EventAdminController::load((int) ($args['id'] ?? 0));
        $categories = EventRepo::categories((int) $event['id']);
        $bouts      = BoutRepo::forEvent((int) $event['id']);

        // Turnier: Kaempfe je Kategorie nach Runden gruppieren.
        $byCategory = [];

        foreach ($bouts as $bout) {
            $byCategory[(int) ($bout['category_id'] ?? 0)][] = $bout;
        }

        View::display('admin/events/bouts', [
            'title'      => $event['name'] . ' – ' . ($event['type'] === 'gala' ? t('Fightcard') : t('Kämpfe')),
            'event'      => $event,
            'categories' => $categories,
            'bouts'      => $bouts,
            'byCategory' => $byCategory,
            'entries'    => EntryRepo::forEvent((int) $event['id'], ['status' => 'bestaetigt']),
            'sessions'   => EventRepo::sessions((int) $event['id']),
            'venues'     => EventRepo::venues((int) $event['id']),
        ], 'layouts/admin');
    }

    /** Kampf manuell anlegen (Fightcard oder Zusatzkampf) bzw. Pause einfuegen. */
    public function store(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $eventId = (int) $event['id'];
        $isBreak = post_bool('is_break') === 1;

        $red  = $this->entryOf($eventId, post_id('red_entry_id'));
        $blue = $this->entryOf($eventId, post_id('blue_entry_id'));

        if (!$isBreak && $red === null && $blue === null) {
            Flash::error(t('Bitte mindestens eine Ecke besetzen.'));
            Url::redirect('/admin/events/' . $eventId . '/kaempfe');
        }

        if ($red !== null && $blue !== null && (int) $red['id'] === (int) $blue['id']) {
            Flash::error(t('Rot und Blau können nicht derselbe Sportler sein.'));
            Url::redirect('/admin/events/' . $eventId . '/kaempfe');
        }

        $sessionId = $this->sessionOf($eventId, post_id('session_id'));
        $venueId   = $this->venueOf($eventId, post_id('venue_id'));
        $category  = post_id('category_id');

        if ($category !== null && Database::one('SELECT id FROM event_categories WHERE id = ? AND event_id = ?', [$category, $eventId]) === null) {
            $category = null;
        }

        $id = Database::insert('event_bouts', [
            'event_id'       => $eventId,
            'category_id'    => $category,
            'session_id'     => $sessionId,
            'venue_id'       => $venueId,
            'order_no'       => BoutRepo::nextOrderNo($eventId, $sessionId, $venueId),
            'bout_no'        => $isBreak ? 0 : BoutRepo::nextBoutNo($eventId),
            'title'          => post('title'),
            'red_entry_id'   => $red !== null ? (int) $red['id'] : null,
            'blue_entry_id'  => $blue !== null ? (int) $blue['id'] : null,
            'rounds'         => max(1, post_int('rounds', 3)),
            'round_minutes'  => max(0.5, post_float('round_minutes', 2)),
            'is_break'       => $isBreak ? 1 : 0,
            'scheduled_time' => parse_time(post('scheduled_time')),
            'note'           => post('note'),
        ] + self::cardFields());

        Audit::log('bout_created', 'event', $eventId, $isBreak ? 'Pause' : 'Kampf #' . $id);
        Flash::success($isBreak ? t('Pause eingefügt.') : t('Kampf angelegt.'));
        Url::redirect('/admin/events/' . $eventId . '/kaempfe');
    }

    /** Turnierbaum einer Kategorie (neu) erzeugen. */
    public function generate(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event    = EventAdminController::load((int) ($args['id'] ?? 0));
        $category = Database::one(
            'SELECT * FROM event_categories WHERE id = ? AND event_id = ?',
            [post_int('category_id'), (int) $event['id']]
        );

        if ($category === null) {
            Flash::error(t('Kategorie nicht gefunden.'));
            Url::redirect('/admin/events/' . $event['id'] . '/kaempfe');
        }

        if (($pro = \App\Core\License::proFeatureError(t('Der automatische Turnierbaum'))) !== null) {
            Flash::error($pro);
            Url::redirect('/admin/events/' . $event['id'] . '/kaempfe');
        }

        $result = Bracket::generate($event, $category);

        if ($result['bouts'] === 0) {
            Flash::error(t('„%s“: mindestens zwei bestätigte Anmeldungen nötig (%d vorhanden).', $category['name'], $result['entries']));
        } else {
            Flash::success(t(
                '„%s“: Turnierbaum mit %d Kämpfen in %d Runden erzeugt (%d Teilnehmer, %d Freilose).',
                $category['name'],
                $result['bouts'],
                $result['rounds'],
                $result['entries'],
                $result['byes']
            ));
        }

        Audit::log('bracket_generated', 'event', (int) $event['id'], (string) $category['name']);
        Url::redirect('/admin/events/' . $event['id'] . '/kaempfe#kat-' . $category['id']);
    }

    // ------------------------------------------------------------- Einzeln --

    public function edit(array $args): void
    {
        AuthController::requireLogin();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $bout  = $this->own((int) ($args['bid'] ?? 0), (int) $event['id']);

        View::display('admin/events/bout', [
            'title'      => $event['name'] . ' – ' . ((int) $bout['bout_no'] > 0 ? t('Kampf #%d', (int) $bout['bout_no']) : t('Kampf') . ' '),
            'event'      => $event,
            'bout'       => $bout,
            'entries'    => EntryRepo::forEvent((int) $event['id'], ['status' => 'bestaetigt']),
            'sessions'   => EventRepo::sessions((int) $event['id']),
            'venues'     => EventRepo::venues((int) $event['id']),
            'categories' => EventRepo::categories((int) $event['id']),
            'methods'    => SettingsController::winMethods(),
            'feeder'     => Database::all(
                'SELECT id, bout_no, round_label, next_slot, status FROM event_bouts WHERE next_bout_id = ? ORDER BY bracket_pos',
                [(int) $bout['id']]
            ),
            'boutImages' => \App\Models\GalleryRepo::mediaIds('bout', (int) $bout['id']),
            'libImages'  => \App\Models\ImageRepo::search(),
        ], 'layouts/admin');
    }

    /** Stammdaten eines Kampfs: Ecken, Titel, Runden, Einplanung. */
    public function update(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $eventId = (int) $event['id'];
        $bout    = $this->own((int) ($args['bid'] ?? 0), $eventId);

        $data = [
            'title'          => post('title'),
            'rounds'         => max(1, post_int('rounds', 3)),
            'round_minutes'  => max(0.5, post_float('round_minutes', 2)),
            'scheduled_time' => parse_time(post('scheduled_time')),
            'note'           => post('note'),
            'session_id'     => $this->sessionOf($eventId, post_id('session_id')),
            'venue_id'       => $this->venueOf($eventId, post_id('venue_id')),
            'order_no'       => post_int('order_no'),
            'bout_no'        => max(0, post_int('bout_no')),
            'updated_at'     => gmdate('Y-m-d H:i:s'),
        ] + self::cardFields();

        // Ecken nur aendern, wenn der Kampf nicht aus einem Baum gespeist wird
        // (Turnierkaempfe ab Runde 2 bekommen ihre Sportler automatisch).
        if ((int) $bout['round_no'] <= 1 || (int) $bout['is_break'] === 1) {
            $red  = $this->entryOf($eventId, post_id('red_entry_id'));
            $blue = $this->entryOf($eventId, post_id('blue_entry_id'));

            if ($red !== null && $blue !== null && (int) $red['id'] === (int) $blue['id']) {
                Flash::error(t('Rot und Blau können nicht derselbe Sportler sein.'));
                Url::redirect('/admin/events/' . $eventId . '/kampf/' . $bout['id']);
            }

            $data['red_entry_id']  = $red !== null ? (int) $red['id'] : null;
            $data['blue_entry_id'] = $blue !== null ? (int) $blue['id'] : null;
        }

        Database::update('event_bouts', (int) $bout['id'], $data);
        Audit::log('bout_updated', 'event', $eventId, 'Kampf #' . $bout['bout_no'] . ': ' . Audit::diff($bout, $data));
        Flash::success(t('Kampf gespeichert.'));
        Url::redirect('/admin/events/' . $eventId . '/kampf/' . $bout['id']);
    }

    /** Ergebnis eintragen (auch Ringleitung). */
    public function result(array $args): void
    {
        AuthController::requireRole('superuser', 'orga', 'ring');
        Csrf::verify();

        $event  = EventAdminController::load((int) ($args['id'] ?? 0));
        $bout   = $this->own((int) ($args['bid'] ?? 0), (int) $event['id']);
        $winner = post('winner');

        if (!isset(BoutRepo::WINNER[$winner])) {
            Flash::error(t('Bitte den Sieger wählen.'));
            Url::redirectRaw($this->back($event, $bout));
        }

        if (($winner === 'red' && $bout['red_entry_id'] === null) || ($winner === 'blue' && $bout['blue_entry_id'] === null)) {
            Flash::error(t('Diese Ecke ist nicht besetzt.'));
            Url::redirectRaw($this->back($event, $bout));
        }

        BoutRepo::setResult((int) $bout['id'], $winner, post('method'), post('result_note'), post('result_round'));

        Audit::log('bout_result', 'event', (int) $event['id'], 'Kampf #' . $bout['bout_no'] . ': ' . BoutRepo::WINNER[$winner] . ' ' . post('method'));
        Flash::success(in_array($winner, ['red', 'blue'], true)
            ? t('Ergebnis eingetragen: %s – %s', t(BoutRepo::WINNER[$winner]), BoutRepo::cornerName($bout, $winner))
            : t('Ergebnis eingetragen: %s', t(BoutRepo::WINNER[$winner])));
        Url::redirectRaw($this->back($event, $bout));
    }

    /** Status setzen: geplant / laufend / abgesagt – oder Ergebnis zuruecknehmen. */
    public function status(array $args): void
    {
        AuthController::requireRole('superuser', 'orga', 'ring');
        Csrf::verify();

        $event  = EventAdminController::load((int) ($args['id'] ?? 0));
        $bout   = $this->own((int) ($args['bid'] ?? 0), (int) $event['id']);
        $status = post('status');

        if ($status === 'reopen') {
            BoutRepo::reopen((int) $bout['id']);
            Flash::success(t('Ergebnis zurückgenommen – der Kampf ist wieder offen.'));
        } elseif (in_array($status, ['geplant', 'laufend', 'abgesagt'], true)) {
            BoutRepo::setStatus((int) $bout['id'], $status);
            Flash::success(t('Kampf: %s', t(BoutRepo::STATUS[$status])));
        }

        Audit::log('bout_status', 'event', (int) $event['id'], 'Kampf #' . $bout['bout_no'] . ' → ' . $status);
        Url::redirectRaw($this->back($event, $bout));
    }

    public function destroy(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $bout  = $this->own((int) ($args['bid'] ?? 0), (int) $event['id']);

        if ((int) $bout['round_no'] > 0 && post_bool('force') !== 1) {
            Flash::error(t('Turnierkämpfe werden über „Turnierbaum neu erzeugen“ ersetzt – einzelnes Löschen würde den Baum zerreißen.'));
            Url::redirect('/admin/events/' . $event['id'] . '/kampf/' . $bout['id']);
        }

        Database::run('DELETE FROM event_bouts WHERE id = ?', [(int) $bout['id']]);
        Audit::log('bout_deleted', 'event', (int) $event['id'], 'Kampf #' . $bout['bout_no']);
        Flash::success(t('Kampf gelöscht.'));
        Url::redirect('/admin/events/' . $event['id'] . '/kaempfe');
    }

    /** Fightcard-Angaben eines Kampfs (Block, Stil, Gewicht, Story, Dauer, Sichtbarkeit). */
    private static function cardFields(): array
    {
        return [
            'block'        => mb_substr(post('block'), 0, 60),
            'belt_label'   => mb_substr(post('belt_label'), 0, 120),
            'style'        => mb_substr(post('style'), 0, 60),
            'weight_label' => mb_substr(post('weight_label'), 0, 80),
            'description'  => trim((string) ($_POST['description'] ?? '')),
            'epilog'       => trim((string) ($_POST['epilog'] ?? '')),
            'minutes'      => max(0, post_int('minutes')),
            'show_record'  => isset($_POST['card_form']) ? post_bool('show_record') : 1,
            'active'       => isset($_POST['card_form']) ? post_bool('active') : 1,
        ];
    }

    /** Kampf inaktiv setzen (Absage: verschwindet von Website und Zeitplan, bleibt in der Verwaltung). */
    public function toggleActive(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $bout  = $this->own((int) ($args['bid'] ?? 0), (int) $event['id']);
        $neu   = (int) $bout['active'] === 1 ? 0 : 1;

        Database::update('event_bouts', (int) $bout['id'], ['active' => $neu, 'updated_at' => gmdate('Y-m-d H:i:s')]);
        Audit::log('bout_active', 'event', (int) $event['id'], 'Kampf #' . $bout['bout_no'] . ($neu ? ' aktiviert' : ' inaktiv'));
        Flash::success($neu ? t('Kampf wieder aktiv.') : t('Kampf inaktiv – er erscheint nicht mehr auf Website und Zeitplan.'));
        Url::redirectRaw($this->back($event, $bout));
    }

    // ------------------------------------------------------------ Zeitplan --

    public function schedule(array $args): void
    {
        AuthController::requireLogin();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));

        View::display('admin/events/schedule', [
            'title'      => $event['name'] . ' – ' . t('Zeitplan'),
            'event'      => $event,
            'schedule'   => BoutRepo::schedule((int) $event['id']),
            'times'      => \App\Core\Timetable::compute($event, BoutRepo::forEvent((int) $event['id'])),
            'sessions'   => EventRepo::sessions((int) $event['id']),
            'venues'     => EventRepo::venues((int) $event['id']),
            'categories' => EventRepo::categories((int) $event['id']),
        ], 'layouts/admin');
    }

    /** Einzelnen Kampf einplanen (Abschnitt + Ring + Reihenfolge). */
    public function place(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $eventId = (int) $event['id'];
        $bout    = $this->own(post_int('bout_id'), $eventId);

        $sessionId = $this->sessionOf($eventId, post_id('session_id'));
        $venueId   = $this->venueOf($eventId, post_id('venue_id'));
        $order     = post('order_no') !== '' ? post_int('order_no') : BoutRepo::nextOrderNo($eventId, $sessionId, $venueId);

        Database::update('event_bouts', (int) $bout['id'], [
            'session_id'     => $sessionId,
            'venue_id'       => $venueId,
            'order_no'       => $order,
            'scheduled_time' => parse_time(post('scheduled_time', (string) $bout['scheduled_time'])),
            'updated_at'     => gmdate('Y-m-d H:i:s'),
        ]);

        Flash::success(t('Kampf eingeplant.'));
        Url::redirectRaw((string) ($_SERVER['HTTP_REFERER'] ?? Url::to('/admin/events/' . $eventId . '/zeitplan')));
    }

    /**
     * Automatisch verteilen: alle noch nicht eingeplanten Kaempfe (optional
     * einer Kategorie/Runde) auf einen Abschnitt legen und reihum auf die
     * gewaehlten Wettkampfstaetten aufteilen.
     */
    public function distribute(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event     = EventAdminController::load((int) ($args['id'] ?? 0));
        $eventId   = (int) $event['id'];
        $sessionId = $this->sessionOf($eventId, post_id('session_id'));
        $venueIds  = array_values(array_filter(array_map('intval', (array) ($_POST['venue_ids'] ?? []))));
        $venueIds  = array_values(array_filter($venueIds, fn (int $v): bool => $this->venueOf($eventId, $v) !== null));

        if ($sessionId === null || $venueIds === []) {
            Flash::error(t('Bitte Abschnitt und mindestens eine Wettkampfstätte wählen.'));
            Url::redirect('/admin/events/' . $eventId . '/zeitplan');
        }

        $where  = ['event_id = ?', 'is_break = 0', "status <> 'abgesagt'"];
        $params = [$eventId];

        if (post_bool('only_open') === 1) {
            $where[] = '(session_id IS NULL OR venue_id IS NULL)';
        }

        if (post_id('category_id') !== null) {
            $where[]  = 'category_id = ?';
            $params[] = post_id('category_id');
        }

        if (post('round_no') !== '') {
            $where[]  = 'round_no = ?';
            $params[] = post_int('round_no');
        }

        // Freilos-Kaempfe brauchen keinen Ring.
        $where[] = "NOT (status = 'beendet' AND method = 'Freilos')";

        $bouts = Database::all(
            'SELECT id FROM event_bouts WHERE ' . implode(' AND ', $where) . ' ORDER BY round_no, category_id, bracket_pos, order_no, id',
            $params
        );

        $orders = [];

        foreach ($venueIds as $v) {
            $orders[$v] = BoutRepo::nextOrderNo($eventId, $sessionId, $v);
        }

        $i = 0;

        foreach ($bouts as $row) {
            $venue = $venueIds[$i % count($venueIds)];

            Database::update('event_bouts', (int) $row['id'], [
                'session_id' => $sessionId,
                'venue_id'   => $venue,
                'order_no'   => $orders[$venue],
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $orders[$venue] += 10;
            $i++;
        }

        Audit::log('bouts_distributed', 'event', $eventId, count($bouts) . ' Kämpfe');
        Flash::success(t('%d Kämpfe auf %d Wettkampfstätte(n) verteilt.', count($bouts), count($venueIds)));
        Url::redirect('/admin/events/' . $eventId . '/zeitplan');
    }

    /** Kampfnummern nach Zeitplan-Reihenfolge neu vergeben. */
    public function renumber(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        BoutRepo::renumber((int) $event['id']);
        Flash::success(t('Kampfnummern neu vergeben.'));
        Url::redirect('/admin/events/' . $event['id'] . '/zeitplan');
    }

    /** Reihenfolge innerhalb Abschnitt/Ring: Kampf um eine Position verschieben. */
    public function move(array $args): void
    {
        AuthController::requireWrite();
        Csrf::verify();

        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $eventId = (int) $event['id'];
        $bout    = $this->own((int) ($args['bid'] ?? 0), $eventId);
        $dir     = post('dir') === 'up' ? -1 : 1;

        $siblings = Database::all(
            'SELECT id, order_no FROM event_bouts WHERE event_id = ? AND session_id IS ? AND venue_id IS ?
              ORDER BY order_no, round_no, bracket_pos, id',
            [$eventId, $bout['session_id'], $bout['venue_id']]
        );

        $ids = array_map(static fn (array $r): int => (int) $r['id'], $siblings);
        $pos = array_search((int) $bout['id'], $ids, true);

        if ($pos !== false && isset($ids[$pos + $dir])) {
            [$ids[$pos], $ids[$pos + $dir]] = [$ids[$pos + $dir], $ids[$pos]];

            foreach ($ids as $i => $id) {
                Database::update('event_bouts', $id, ['order_no' => ($i + 1) * 10]);
            }
        }

        Url::redirectRaw((string) ($_SERVER['HTTP_REFERER'] ?? Url::to('/admin/events/' . $eventId . '/kaempfe')));
    }

    // ---------------------------------------------------------- Ergebnisse --

    public function results(array $args): void
    {
        AuthController::requireLogin();

        $event = EventAdminController::load((int) ($args['id'] ?? 0));
        $bouts = BoutRepo::forEvent((int) $event['id']);

        View::display('admin/events/results', [
            'title'      => $event['name'] . ' – ' . t('Ergebnisse'),
            'event'      => $event,
            'finished'   => array_values(array_filter($bouts, static fn (array $b): bool => $b['status'] === 'beendet' && (int) $b['is_break'] === 0 && $b['method'] !== 'Freilos')),
            'open'       => array_values(array_filter($bouts, static fn (array $b): bool => in_array($b['status'], ['geplant', 'laufend'], true) && (int) $b['is_break'] === 0)),
            'winners'    => self::categoryWinners((int) $event['id']),
        ], 'layouts/admin');
    }

    /**
     * Sieger je Kategorie (Finalsieger) + Zweiter.
     *
     * @return list<array<string,mixed>>
     */
    public static function categoryWinners(int $eventId): array
    {
        $out = [];

        foreach (EventRepo::categories($eventId) as $c) {
            $final = Database::one(
                'SELECT MAX(round_no) AS r FROM event_bouts WHERE category_id = ?',
                [(int) $c['id']]
            );

            if ($final === null || (int) $final['r'] === 0) {
                continue;
            }

            $bout = null;

            foreach (BoutRepo::forCategory((int) $c['id']) as $b) {
                if ((int) $b['round_no'] === (int) $final['r']) {
                    $bout = $b;
                    break;
                }
            }

            if ($bout === null || $bout['status'] !== 'beendet') {
                $out[] = ['category' => $c, 'final' => $bout, 'first' => '', 'second' => ''];
                continue;
            }

            $w = (string) $bout['winner'];
            $out[] = [
                'category' => $c,
                'final'    => $bout,
                'first'    => $w === 'red' ? BoutRepo::cornerName($bout, 'red') . ' (' . $bout['red_gym'] . ')' : ($w === 'blue' ? BoutRepo::cornerName($bout, 'blue') . ' (' . $bout['blue_gym'] . ')' : '–'),
                'second'   => $w === 'red' ? BoutRepo::cornerName($bout, 'blue') . ' (' . $bout['blue_gym'] . ')' : ($w === 'blue' ? BoutRepo::cornerName($bout, 'red') . ' (' . $bout['red_gym'] . ')' : '–'),
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------ Ringansicht --

    /** Grosse Ansicht fuer den Kampfrichtertisch einer Wettkampfstaette. */
    public function ring(array $args): void
    {
        AuthController::requireRole('superuser', 'orga', 'ring');

        $event   = EventAdminController::load((int) ($args['id'] ?? 0));
        $eventId = (int) $event['id'];
        $venues  = EventRepo::venues($eventId);
        $venueId = (int) ($args['vid'] ?? 0);
        $venue   = null;

        foreach ($venues as $v) {
            if ((int) $v['id'] === $venueId) {
                $venue = $v;
            }
        }

        $sessions  = EventRepo::sessions($eventId);
        $sessionId = (int) query('session', '0');

        // Standard: der Abschnitt von heute mit offenen Kaempfen, sonst der erste.
        if ($sessionId === 0) {
            foreach ($sessions as $s) {
                $offen = (int) Database::value(
                    "SELECT COUNT(*) FROM event_bouts WHERE session_id = ? AND venue_id = ? AND status IN ('geplant', 'laufend')",
                    [(int) $s['id'], $venueId]
                );

                if ($offen > 0) {
                    $sessionId = (int) $s['id'];
                    break;
                }
            }

            if ($sessionId === 0 && $sessions !== []) {
                $sessionId = (int) $sessions[0]['id'];
            }
        }

        $bouts = array_values(array_filter(
            BoutRepo::forEvent($eventId),
            static fn (array $b): bool => (int) ($b['venue_id'] ?? 0) === $venueId && (int) ($b['session_id'] ?? 0) === $sessionId
        ));

        View::display('admin/events/ring', [
            'title'     => ($venue['name'] ?? t('Ring')) . ' – ' . $event['name'],
            'event'     => $event,
            'venue'     => $venue,
            'venues'    => $venues,
            'sessions'  => $sessions,
            'sessionId' => $sessionId,
            'bouts'     => $bouts,
            'methods'   => SettingsController::winMethods(),
        ], 'layouts/admin');
    }

    // ---------------------------------------------------------------- intern --

    private function own(int $boutId, int $eventId): array
    {
        $bout = BoutRepo::find($boutId);

        if ($bout === null || (int) $bout['event_id'] !== $eventId) {
            Flash::error(t('Kampf nicht gefunden.'));
            Url::redirect('/admin/events/' . $eventId . '/kaempfe');
        }

        return $bout;
    }

    private function entryOf(int $eventId, ?int $entryId): ?array
    {
        if ($entryId === null) {
            return null;
        }

        return Database::one('SELECT * FROM event_entries WHERE id = ? AND event_id = ?', [$entryId, $eventId]);
    }

    private function sessionOf(int $eventId, ?int $sessionId): ?int
    {
        if ($sessionId === null) {
            return null;
        }

        $row = Database::one('SELECT id FROM event_sessions WHERE id = ? AND event_id = ?', [$sessionId, $eventId]);

        return $row === null ? null : (int) $row['id'];
    }

    private function venueOf(int $eventId, ?int $venueId): ?int
    {
        if ($venueId === null) {
            return null;
        }

        $row = Database::one('SELECT id FROM event_venues WHERE id = ? AND event_id = ?', [$venueId, $eventId]);

        return $row === null ? null : (int) $row['id'];
    }

    /** Zurueck zur Herkunftsseite (Ringansicht, Zeitplan, Liste) oder zum Kampf. */
    private function back(array $event, array $bout): string
    {
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $own = Url::to('/admin/events/' . $event['id']);

        if ($ref !== '' && str_contains($ref, $own)) {
            $path = (string) parse_url($ref, PHP_URL_PATH);
            $qs   = (string) parse_url($ref, PHP_URL_QUERY);

            return $path . ($qs !== '' ? '?' . $qs : '');
        }

        return Url::to('/admin/events/' . $event['id'] . '/kampf/' . $bout['id']);
    }
}
