<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Bracket;
use App\Core\Config;
use App\Core\Ticket141Client;
use App\Core\Timetable;
use App\Core\Url;
use App\Core\View;
use App\Models\BoutRepo;
use App\Models\EntryRepo;
use App\Models\EventRepo;
use App\Models\PageRepo;
use App\Models\Setting;

/** Oeffentliche Website: Eventliste, Event-Seiten, redaktionelle Seiten. */
final class PublicController
{
    public function home(): void
    {
        // Ein einzelnes Event kann die komplette Startseite sein (eigene Homepage).
        $homeSlug = Setting::get('home_event');

        if ($homeSlug !== '') {
            $event = EventRepo::findBySlug($homeSlug);

            if ($event !== null && (int) $event['published'] === 1) {
                $this->renderEvent($event, 'home');

                return;
            }
        }

        View::display('public/home', [
            'title'      => '',
            'metaDesc'   => t('%s – Events, Turniere und Fight Nights: Termine, Kämpfe, Ergebnisse.', Setting::get('org_name')),
            'events'     => EventRepo::published(),
            'introTitle' => t(Setting::get('home_title', 'Unsere Events')),
            'introText'  => Setting::get('home_text', ''),
            'activePage' => 'home',
        ]);
    }

    public function event(array $args): void
    {
        $this->renderEvent($this->loadEvent($args), 'event');
    }

    private function renderEvent(array $event, string $active): void
    {
        $eventId = (int) $event['id'];
        $bouts   = self::visible(BoutRepo::forEvent($eventId));

        // Ticket141-Kopplung: Kategorien mit Preis/Verfuegbarkeit (5 Minuten gepuffert).
        $ticket141 = null;
        $tkSlug    = (string) ($event['ticket141_slug'] ?? '');

        if ($tkSlug !== '') {
            $client = Ticket141Client::fromSettings();

            if ($client->configured()) {
                $ticket141 = [
                    'slug'       => $tkSlug,
                    'shop_url'   => $client->shopUrl($tkSlug),
                    'embed'      => Setting::get('ticket141_embed', '0') === '1',
                    'embed_js'   => $client->embedScriptUrl(),
                    'data'       => $client->eventCached($tkSlug),
                ];
            }
        }

        View::display('public/event', [
            'ticket141'  => $ticket141,
            'bouts'      => $bouts,
            'times'      => Timetable::compute($event, $bouts),
            'sponsors'   => EventRepo::sponsors($eventId, true),
            'tickets'    => EventRepo::tickets($event),
            'social'     => EventRepo::social($event),
            'startTs'    => Timetable::eventStart($event),
            'title'      => (string) $event['name'],
            'metaDesc'   => trim($event['name'] . ' – ' . format_date_range($event['starts_on'], $event['ends_on']) . ($event['venue_city'] !== '' ? ', ' . $event['venue_city'] : '') . '. ' . $event['tagline']),
            'event'      => $event,
            'days'       => EventRepo::structure($eventId),
            'venues'     => EventRepo::venues($eventId),
            'categories' => EventRepo::categories($eventId),
            'stats'      => EventRepo::stats($eventId),
            'live'       => array_values(array_filter($bouts, static fn (array $b): bool => $b['status'] === 'laufend')),
            'nextBouts'  => array_slice(array_values(array_filter($bouts, static fn (array $b): bool => $b['status'] === 'geplant' && (int) $b['is_break'] === 0 && $b['session_id'] !== null)), 0, 6),
            'activePage' => $active,
            'eventTab'   => 'uebersicht',
            'galleries'  => \App\Models\GalleryRepo::forEvent($eventId, true),
            'reportCover' => (int) ($event['report_cover_id'] ?? 0) > 0 ? \App\Models\ImageRepo::find((int) $event['report_cover_id']) : null,
        ]);
    }

    /** Event-Bericht (/e/{slug}/bericht) */
    public function report(array $args): void
    {
        $event = $this->loadEvent($args);
        if ((int) ($event['report_published'] ?? 0) !== 1) {
            $this->notFound();
            return;
        }
        View::display('public/report', [
            'title'      => (string) ($event['report_title'] ?: t('Bericht')) . ' – ' . $event['name'],
            'metaDesc'   => mb_substr(trim(preg_replace('/\s+/', ' ', (string) $event['report_text']) ?? ''), 0, 160),
            'event'      => $event,
            'cover'      => (int) ($event['report_cover_id'] ?? 0) > 0 ? \App\Models\ImageRepo::find((int) $event['report_cover_id']) : null,
            'images'     => \App\Models\GalleryRepo::reportImages((int) $event['id']),
            'activePage' => 'event',
            'eventTab'   => 'bericht',
        ]);
    }

    /** Galerien eines Events (/e/{slug}/galerie) */
    public function galleries(array $args): void
    {
        $event = $this->loadEvent($args);
        View::display('public/galleries', [
            'title'      => t('Galerie') . ' – ' . $event['name'],
            'event'      => $event,
            'galleries'  => \App\Models\GalleryRepo::forEvent((int) $event['id'], true),
            'activePage' => 'event',
            'eventTab'   => 'galerie',
        ]);
    }

    /** Eine Galerie (/e/{slug}/galerie/{gslug}) */
    public function gallery(array $args): void
    {
        $event   = $this->loadEvent($args);
        $gallery = \App\Models\GalleryRepo::findBySlug((int) $event['id'], (string) ($args['gslug'] ?? ''));
        if ($gallery === null) {
            $this->notFound();
            return;
        }
        View::display('public/galleries', [
            'title'      => $gallery['title'] . ' – ' . $event['name'],
            'event'      => $event,
            'gallery'    => $gallery,
            'images'     => \App\Models\GalleryRepo::images((int) $gallery['id']),
            'galleries'  => \App\Models\GalleryRepo::forEvent((int) $event['id'], true),
            'activePage' => 'event',
            'eventTab'   => 'galerie',
        ]);
    }

    /** Fightcard (Gala) bzw. Turnierbaeume (Turnier). */
    public function bouts(array $args): void
    {
        $event   = $this->loadEvent($args);
        $eventId = (int) $event['id'];

        $brackets = [];
        $filter   = \App\Core\CategoryFilter::fromQuery();
        $mitBaum  = [];

        if ($event['type'] === 'turnier') {
            // Nur Kategorien mit Kaempfen; der Filter (Disziplin, Altersklasse, Geschlecht, Suche) grenzt weiter ein.
            $mitBaum = array_values(array_filter(EventRepo::categories($eventId), static fn (array $c): bool => (int) $c['bout_count'] > 0));

            foreach (\App\Core\CategoryFilter::apply($eventId, $mitBaum, $filter) as $c) {
                $rounds = Bracket::rounds((int) $c['id']);

                if ($rounds !== []) {
                    $brackets[] = ['category' => $c, 'rounds' => $rounds];
                }
            }
        }

        $bouts = self::visible(BoutRepo::forEvent($eventId));
        $data  = [
            'title'      => $event['name'] . ' – ' . ($event['type'] === 'gala' ? t('Fightcard') : t('Turnierplan')),
            'event'      => $event,
            'bouts'      => $bouts,
            'times'      => Timetable::compute($event, $bouts),
            'brackets'   => $brackets,
            'filter'        => $filter,
            'filterOptions' => \App\Core\CategoryFilter::options($mitBaum, $event),
            'filterTotal'   => count($mitBaum),
            'activePage' => 'event',
            'eventTab'   => 'kaempfe',
        ];

        // Nur die Fightcard (fuer die automatische Aktualisierung der Seite).
        if (query('fragment') === '1') {
            header('Cache-Control: no-store');
            View::display('public/_fightcard', $data, null);

            return;
        }

        View::display('public/bouts', $data);
    }

    /** Druckansichten fuer Besucher: Turnierbaeume ("Spinne") und Running Order. */
    public function printDoc(array $args): void
    {
        $event = $this->loadEvent($args);
        $doc   = (string) ($args['doc'] ?? '');

        if (!in_array($doc, \App\Core\PrintDocs::DOCS, true)) {
            $this->notFound();

            return;
        }

        \App\Core\PrintDocs::display($event, $doc);
    }

    /** Kampf-Detailseite: Story, beide Kaempfer mit Bio, Bilanz, Medien. */
    public function fight(array $args): void
    {
        $event = $this->loadEvent($args);
        $bouts = self::visible(BoutRepo::forEvent((int) $event['id']));
        $bout  = null;

        foreach ($bouts as $b) {
            if ((int) $b['id'] === (int) ($args['id'] ?? 0) && (int) $b['is_break'] === 0) {
                $bout = $b;
            }
        }

        if ($bout === null) {
            $this->notFound();

            return;
        }

        $red  = BoutRepo::cornerName($bout, 'red') ?: 'TBA';
        $blue = BoutRepo::cornerName($bout, 'blue') ?: 'TBA';

        View::display('public/fight', [
            'title'      => $red . ' vs. ' . $blue . ' – ' . $event['name'],
            'metaDesc'   => mb_substr(trim(preg_replace('/\s+/', ' ', (string) $bout['description']) ?? ''), 0, 160),
            'event'      => $event,
            'bout'       => $bout,
            'times'      => Timetable::compute($event, $bouts),
            'images'     => \App\Models\GalleryRepo::boutImages((int) $bout['id']),
            'activePage' => 'event',
            'eventTab'   => 'kaempfe',
        ]);
    }

    /** Inaktive Kaempfe und Freilose gehoeren nicht auf die Website. @param list<array<string,mixed>> $bouts */
    private static function visible(array $bouts): array
    {
        return array_values(array_filter($bouts, static fn (array $b): bool => (int) $b['active'] === 1
            && !((string) $b['status'] === 'beendet' && (string) $b['method'] === 'Freilos' && (int) $b['round_no'] === 0)));
    }

    public function schedule(array $args): void
    {
        $event = $this->loadEvent($args);

        View::display('public/schedule', [
            'title'      => $event['name'] . ' – ' . t('Zeitplan'),
            'event'      => $event,
            'schedule'   => BoutRepo::schedule((int) $event['id']),
            'times'      => Timetable::compute($event, BoutRepo::forEvent((int) $event['id'])),
            'activePage' => 'event',
            'eventTab'   => 'zeitplan',
        ]);
    }

    public function entries(array $args): void
    {
        $event = $this->loadEvent($args);

        if ((int) $event['show_entries'] !== 1) {
            $this->notFound();

            return;
        }

        $rows  = EntryRepo::forEvent((int) $event['id'], ['status' => 'bestaetigt']);
        $byGym = [];

        foreach ($rows as $r) {
            $byGym[(string) $r['gym_name']][] = $r;
        }

        ksort($byGym, SORT_NATURAL | SORT_FLAG_CASE);

        View::display('public/entries', [
            'title'      => $event['name'] . ' – ' . t('Teilnehmer'),
            'event'      => $event,
            'byGym'      => $byGym,
            'total'      => count($rows),
            'activePage' => 'event',
            'eventTab'   => 'teilnehmer',
        ]);
    }

    public function results(array $args): void
    {
        $event = $this->loadEvent($args);

        if ((int) $event['show_results'] !== 1) {
            $this->notFound();

            return;
        }

        $bouts = self::visible(BoutRepo::forEvent((int) $event['id']));

        View::display('public/results', [
            'title'      => $event['name'] . ' – ' . t('Ergebnisse'),
            'event'      => $event,
            'finished'   => array_values(array_filter($bouts, static fn (array $b): bool => $b['status'] === 'beendet' && (int) $b['is_break'] === 0 && $b['method'] !== 'Freilos')),
            'winners'    => BoutController::categoryWinners((int) $event['id']),
            'activePage' => 'event',
            'eventTab'   => 'ergebnisse',
        ]);
    }

    public function page(array $args): void
    {
        $page = PageRepo::findBySlug($args['slug'] ?? '');

        if ($page === null || (int) $page['published'] !== 1) {
            $this->notFound();

            return;
        }

        View::display('public/page', [
            'title'      => (string) $page['title'],
            'page'       => $page,
            'activePage' => 'page',
        ]);
    }

    /**
     * Alte Adressen einer importierten Event-Website im NAFN-Format
     * (index.html, fight.html?fight=<id>&event=<kuerzel>, bericht.html …)
     * dauerhaft auf die neuen Seiten leiten – geteilte Links und QR-Codes
     * bleiben so gueltig, wenn die Domain auf Event141 umzieht.
     */
    public function legacy(): void
    {
        $seite = basename((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '.html');
        $ziel  = '/';

        if ($seite === 'impressum') {
            $ziel = '/seite/impressum';
        } else {
            // Event: ausdruecklich genannt (?event=…), sonst das Startseiten-Event.
            $event = EventRepo::findBySlug(query('event')) ?? EventRepo::findBySlug(Setting::get('home_event'));

            if ($event !== null && (int) $event['published'] === 1) {
                $base = '/e/' . $event['slug'];
                $ziel = Setting::get('home_event') === (string) $event['slug'] ? '/' : $base;

                if ($seite === 'fight') {
                    $bout = \App\Core\Database::one(
                        "SELECT id FROM event_bouts WHERE event_id = ? AND external_id = ? AND external_id <> '' AND is_break = 0",
                        [(int) $event['id'], query('fight')]
                    );
                    $ziel = $bout !== null ? $base . '/kampf/' . (int) $bout['id'] : $base . '/kaempfe';
                } elseif ($seite === 'bericht') {
                    $ziel = $base . '/bericht';
                } elseif ($seite === 'galerie') {
                    $ziel = $base . '/galerie';
                }
            }
        }

        header('Location: ' . Url::to($ziel), true, 301);
        exit;
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');

        if (Setting::get('public_site', '1') === '0' || (bool) Config::get('noindex', false)) {
            echo "User-agent: *\nDisallow: /\n";

            return;
        }

        $host    = (string) Config::get('canonical_host', '') ?: (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $sitemap = 'https://' . $host . Url::to('/sitemap.xml');

        echo "User-agent: *\nDisallow: /admin\nDisallow: /gym\nAllow: /\n\nSitemap: $sitemap\n";
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');

        $host = (string) Config::get('canonical_host', '') ?: (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $base = 'https://' . $host;
        $urls = [['loc' => $base . Url::to('/'), 'prio' => '1.0']];

        foreach (EventRepo::published() as $event) {
            $urls[] = ['loc' => $base . Url::to('/e/' . $event['slug']), 'prio' => '0.9', 'mod' => substr((string) $event['updated_at'], 0, 10)];
            $urls[] = ['loc' => $base . Url::to('/e/' . $event['slug'] . '/kaempfe'), 'prio' => '0.7'];
            $urls[] = ['loc' => $base . Url::to('/e/' . $event['slug'] . '/zeitplan'), 'prio' => '0.6'];
        }

        foreach (PageRepo::footerPages() as $page) {
            $urls[] = ['loc' => $base . Url::to('/seite/' . $page['slug']), 'prio' => '0.3'];
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            echo "  <url>\n    <loc>" . e($url['loc']) . "</loc>\n";

            if (($url['mod'] ?? '') !== '') {
                echo '    <lastmod>' . e($url['mod']) . "</lastmod>\n";
            }

            echo '    <priority>' . $url['prio'] . "</priority>\n  </url>\n";
        }

        echo "</urlset>\n";
    }

    public function notFound(): void
    {
        http_response_code(404);

        // API-Clients bekommen JSON statt der HTML-Fehlerseite.
        if (str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), '/api/')) {
            header('Content-Type: application/json; charset=UTF-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode(['error' => t('Unbekannter Endpunkt.')]);

            return;
        }

        View::display('errors/404', ['title' => t('Seite nicht gefunden'), 'activePage' => '']);
    }

    private function loadEvent(array $args): array
    {
        $event = EventRepo::findBySlug($args['slug'] ?? '');

        if ($event === null || ((int) $event['published'] !== 1 && !\App\Core\Auth::check())) {
            $this->notFound();
            exit;
        }

        return $event;
    }
}
