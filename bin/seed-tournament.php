<?php

/**
 * Beispiel-Turnier nach einem Regelsatz anlegen: Event mit Tagen, Abschnitten
 * und Wettkampfstaetten, alle Kategorien des Regelsatzes, viele erfundene
 * Gyms und Sportler in allen Altersklassen/Geschlechtern/Disziplinen,
 * bestaetigte Anmeldungen, Turnierbaeume und ein verteilter Zeitplan.
 *
 *   php bin/seed-tournament.php --ruleset=wako --name="Austrian Open 2027" \
 *       [--org="Event141 Demo"] [--city=Wien] [--venue="Sporthalle"] [--days=3] \
 *       [--weeks=8] [--fill=75] [--rings=2] [--tatamis=4] [--seed=141] [--force]
 *
 * Laeuft nur in einer Installation ohne Events (ausser mit --force). Alle
 * Namen sind frei erfunden; die Gyms bekommen KEINEN Login.
 */

declare(strict_types=1);

use App\Core\Bracket;
use App\Core\Database;
use App\Core\Ruleset;
use App\Models\BoutRepo;
use App\Models\EventRepo;
use App\Models\Setting;

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$opt = getopt('', ['ruleset:', 'name:', 'org::', 'city::', 'venue::', 'days::', 'weeks::', 'fill::', 'rings::', 'tatamis::', 'seed::', 'force']);

$ruleset = Ruleset::find((string) ($opt['ruleset'] ?? ''));

if ($ruleset === null) {
    exit('Bitte --ruleset angeben: ' . implode(', ', array_keys(Ruleset::all())) . "\n");
}

if (!isset($opt['force']) && (int) Database::value('SELECT COUNT(*) FROM events') > 0) {
    exit("Es gibt schon Events – das Beispiel-Turnier wird nur in eine leere Installation eingespielt (--force erzwingt es).\n");
}

mt_srand((int) ($opt['seed'] ?? 141));

$name    = (string) ($opt['name'] ?? ($ruleset['name'] . ' Open'));
$city    = (string) ($opt['city'] ?? 'Wien');
$tage    = max(1, min(5, (int) ($opt['days'] ?? 2)));
$fill    = max(10, min(100, (int) ($opt['fill'] ?? 75))) / 100;
$start   = new DateTimeImmutable('+' . max(1, (int) ($opt['weeks'] ?? 8)) . ' weeks friday');
$start   = $tage <= 2 ? $start->modify('+1 day') : $start;
$jahr    = (int) $start->format('Y');

$pick = static fn (array $a): mixed => $a[mt_rand(0, count($a) - 1)];

// ------------------------------------------------------------- Namenslisten --
$namen = [
    'de' => [
        'm' => ['Lukas', 'Jonas', 'David', 'Felix', 'Elias', 'Noah', 'Paul', 'Leon', 'Maximilian', 'Tobias', 'Simon', 'Fabian', 'Jakob', 'Moritz', 'Florian', 'Daniel', 'Julian', 'Niklas', 'Raphael', 'Matthias', 'Stefan', 'Philipp', 'Dominik', 'Sebastian', 'Valentin', 'Emil', 'Anton', 'Luis', 'Ben', 'Theo'],
        'w' => ['Anna', 'Lena', 'Sarah', 'Laura', 'Marie', 'Julia', 'Emma', 'Lisa', 'Hannah', 'Sophie', 'Mia', 'Leonie', 'Johanna', 'Katharina', 'Nina', 'Valentina', 'Elena', 'Magdalena', 'Theresa', 'Clara', 'Lea', 'Emilia', 'Paula', 'Jana', 'Vanessa', 'Carina', 'Selina', 'Amelie', 'Ida', 'Frieda'],
        'n' => ['Huber', 'Gruber', 'Bauer', 'Wagner', 'Müller', 'Pichler', 'Steiner', 'Moser', 'Mayer', 'Hofer', 'Leitner', 'Berger', 'Fuchs', 'Eder', 'Fischer', 'Schmid', 'Winkler', 'Weber', 'Schwarz', 'Maier', 'Reiter', 'Brunner', 'Lang', 'Baumgartner', 'Auer', 'Wolf', 'Binder', 'Lechner', 'Egger', 'Haas', 'Koller', 'Wimmer', 'Aigner', 'Hartmann', 'Krüger', 'Schulz', 'Zimmermann', 'Vogel', 'Kaiser', 'Graf'],
    ],
    'it' => [
        'm' => ['Luca', 'Matteo', 'Alessandro', 'Lorenzo', 'Marco', 'Davide', 'Andrea', 'Federico', 'Simone', 'Gabriele', 'Riccardo', 'Tommaso', 'Nicolò', 'Pietro', 'Giovanni'],
        'w' => ['Giulia', 'Sofia', 'Martina', 'Chiara', 'Francesca', 'Alessia', 'Sara', 'Elisa', 'Federica', 'Aurora', 'Greta', 'Beatrice', 'Camilla', 'Arianna', 'Noemi'],
        'n' => ['Rossi', 'Russo', 'Ferrari', 'Esposito', 'Bianchi', 'Romano', 'Colombo', 'Ricci', 'Marino', 'Greco', 'Bruno', 'Gallo', 'Conti', 'De Luca', 'Costa', 'Fontana', 'Moretti', 'Barbieri', 'Lombardi', 'Rinaldi'],
    ],
    'sl' => [
        'm' => ['Luka', 'Marko', 'Jan', 'Žan', 'Nik', 'Matej', 'Ivan', 'Filip', 'Tomislav', 'Ante', 'Josip', 'Domagoj', 'Miha', 'Rok', 'Tilen', 'Dario', 'Karlo', 'Nejc'],
        'w' => ['Ana', 'Maja', 'Nika', 'Eva', 'Sara', 'Petra', 'Ivana', 'Lucija', 'Tina', 'Katja', 'Lara', 'Marta', 'Mia', 'Dora', 'Iva', 'Nina', 'Zala', 'Tjaša'],
        'n' => ['Novak', 'Horvat', 'Kovačić', 'Babić', 'Marić', 'Jurić', 'Kranjc', 'Zupančič', 'Potočnik', 'Kovač', 'Vidmar', 'Golob', 'Knežević', 'Perić', 'Tomić', 'Pavlović', 'Božić', 'Matić', 'Petek', 'Hribar'],
    ],
    'hu' => [
        'm' => ['Bence', 'Máté', 'Levente', 'Dávid', 'Ádám', 'Balázs', 'Gergő', 'Péter', 'Zoltán', 'Tamás', 'Márk', 'Dominik', 'Jakub', 'Tomáš', 'Martin', 'Ondřej', 'Michal', 'Patrik'],
        'w' => ['Anna', 'Hanna', 'Zsófia', 'Réka', 'Dóra', 'Lilla', 'Eszter', 'Viktória', 'Petra', 'Fanni', 'Tereza', 'Adéla', 'Karolína', 'Natálie', 'Kristýna', 'Barbora', 'Veronika', 'Lucie'],
        'n' => ['Nagy', 'Kovács', 'Tóth', 'Szabó', 'Horváth', 'Varga', 'Kiss', 'Molnár', 'Németh', 'Farkas', 'Novák', 'Svoboda', 'Dvořák', 'Černý', 'Procházka', 'Kučera', 'Veselý', 'Krejčí', 'Balogh', 'Papp'],
    ],
];
$laender = [
    'AT' => 'de', 'DE' => 'de', 'CH' => 'de', 'IT' => 'it', 'SI' => 'sl', 'HR' => 'sl', 'HU' => 'hu', 'CZ' => 'hu', 'SK' => 'hu',
];

// Frei erfundene Gyms – bewusst ohne Bezug zu echten Vereinen.
$gymListe = [
    ['Roter Drache Kampfsport', 'DRACHE', 'Graz', 'AT'], ['Team Nordwind', 'NORDW', 'Linz', 'AT'], ['Eisenfaust Gym', 'EISEN', 'Wien', 'AT'],
    ['Alpen Warriors', 'ALPEN', 'Innsbruck', 'AT'], ['Donau Fighters', 'DONAU', 'Krems', 'AT'], ['Phönix Kampfsportzentrum', 'PHÖNIX', 'Salzburg', 'AT'],
    ['Kampfkunst Adlerhorst', 'ADLER', 'Klagenfurt', 'AT'], ['Tigerclaw Academy', 'TIGER', 'Wels', 'AT'], ['Fightwerk Süd', 'FWSÜD', 'Villach', 'AT'],
    ['Bergland Sportschule', 'BERGL', 'Leoben', 'AT'], ['Löwenherz Gym', 'LÖWE', 'St. Pölten', 'AT'], ['Sturmfalken Team', 'FALKE', 'Dornbirn', 'AT'],
    ['Stahlwerk Fight Team', 'STAHL', 'München', 'DE'], ['Nordlicht Kampfsport', 'NORDL', 'Hamburg', 'DE'], ['Rheinland Strikers', 'RHEIN', 'Köln', 'DE'],
    ['Schwarzwald Dojo', 'SWDOJO', 'Freiburg', 'DE'], ['Team Isarkraft', 'ISAR', 'Landshut', 'DE'], ['Hansa Fight Academy', 'HANSA', 'Bremen', 'DE'],
    ['Gotthard Fighters', 'GOTTH', 'Luzern', 'CH'], ['Helvetia Sportkampf', 'HELV', 'Zürich', 'CH'],
    ['Leoni Rossi Team', 'LEONI', 'Verona', 'IT'], ['Aquila Fight Club', 'AQUILA', 'Udine', 'IT'], ['Dolomiti Kombat', 'DOLOM', 'Bozen', 'IT'],
    ['Zmaj Borilni Klub', 'ZMAJ', 'Maribor', 'SI'], ['Triglav Fight Team', 'TRIGL', 'Ljubljana', 'SI'],
    ['Jadran Borci', 'JADRAN', 'Split', 'HR'], ['Sokol Zagreb Gym', 'SOKOL', 'Zagreb', 'HR'],
    ['Puszta Harcosok', 'PUSZTA', 'Győr', 'HU'], ['Duna Küzdősport', 'DUNA', 'Budapest', 'HU'],
    ['Moldau Gladiators', 'MOLDAU', 'Brno', 'CZ'], ['Bílý Tygr Team', 'TYGR', 'Praha', 'CZ'], ['Tatra Fight Klub', 'TATRA', 'Bratislava', 'SK'],
];

// ------------------------------------------------------------------- Event --
Setting::setMany([
    'org_name'    => (string) ($opt['org'] ?? 'Event141 Demo'),
    'org_tagline' => 'Beispiel-Turnier – alle Daten sind frei erfunden',
    'org_city'    => $city,
]);

$eventId = Database::insert('events', [
    'slug'               => EventRepo::uniqueSlug(slugify($name)),
    'name'               => $name,
    'type'               => 'turnier',
    'sport'              => (string) $ruleset['sport'],
    'tagline'            => $tage . ' Tage · alle Altersklassen · nach ' . $ruleset['name'],
    'description'        => '<p>Beispiel-Turnier nach dem Regelsatz <strong>' . htmlspecialchars((string) $ruleset['name']) . '</strong>: '
        . 'alle Disziplinen, Alters- und Gewichtsklassen, Turnierbäume und Zeitplan. Sportler, Gyms und Ergebnisse sind frei erfunden.</p>',
    'starts_on'          => $start->format('Y-m-d'),
    'ends_on'            => $start->modify('+' . ($tage - 1) . ' days')->format('Y-m-d'),
    'start_time'         => '09:00',
    'venue_name'         => (string) ($opt['venue'] ?? 'Sport- und Messehalle'),
    'venue_city'         => $city,
    'status'             => 'geschlossen',
    'registration_until' => $start->modify('-10 days')->format('Y-m-d'),
    'entry_fee'          => 30,
    'published'          => 1,
    'show_entries'       => 1,
    'show_results'       => 1,
]);

$event = EventRepo::find($eventId);
EventRepo::ensureDays($event);
$days = EventRepo::days($eventId);

// Abschnitte in zeitlicher Reihenfolge
$sessions = [];

foreach ($days as $d => $day) {
    $teile = [['Vormittag', '09:00', '13:00'], ['Nachmittag', '14:00', '19:00']];

    foreach ($teile as $i => [$titel, $von, $bis]) {
        $sessions[] = Database::insert('event_sessions', [
            'event_id' => $eventId, 'day_id' => (int) $day['id'], 'name' => $titel, 'starts_at' => $von, 'ends_at' => $bis, 'sort_order' => ($i + 1) * 10,
        ]);
    }
}

// Wettkampfstaetten je Flaechenart
$areas  = array_values(array_unique(array_map(static fn (array $d): string => (string) ($d['area'] ?? 'ring'), $ruleset['disciplines'])));
$venues = ['ring' => [], 'tatami' => []];
$farben = ['#e63946', '#1a5fb4', '#2a9d8f', '#f4a261', '#8e44ad', '#457b9d', '#d62828', '#6a994e'];
$sort   = 0;

foreach (['tatami' => max(1, (int) ($opt['tatamis'] ?? 4)), 'ring' => max(1, (int) ($opt['rings'] ?? 2))] as $area => $anzahl) {
    if (!in_array($area, $areas, true)) {
        continue;
    }

    for ($i = 1; $i <= $anzahl; $i++) {
        $venues[$area][] = Database::insert('event_venues', [
            'event_id' => $eventId, 'name' => ($area === 'ring' ? 'Ring ' : 'Tatami ') . $i, 'short_name' => ($area === 'ring' ? 'R' : 'T') . $i,
            'color' => $farben[$sort % count($farben)], 'sort_order' => ++$sort * 10,
        ]);
    }
}

// --------------------------------------------------------------- Kategorien --
$applied = Ruleset::apply($eventId, Ruleset::expand($ruleset));
$katIds  = $applied['ids'];    // Kategorie-Id => Zeile mit _disc/_class/_gender/_weight/_area

// --------------------------------------------------------------------- Gyms --
$gyms = [];

Database::transaction(static function () use ($gymListe, &$gyms): void {
    foreach ($gymListe as [$gName, $short, $ort, $land]) {
        $gyms[] = [
            'id'   => Database::insert('gyms', [
                'name' => $gName, 'short_name' => $short, 'slug' => slugify($gName), 'city' => $ort, 'country' => $land,
                'contact_name' => 'Trainerteam ' . $short, 'status' => 'bestaetigt',
            ]),
            'land' => $land,
        ];
    }
});

// ----------------------------------------------------- Sportler + Anmeldungen --
// Tatami-Sportler starten oft doppelt (PF + LC, LC + KL): Partner-Disziplin je Disziplin.
$partner = ['lc' => 'pf', 'kl' => 'lc'];
$pool    = [];   // disziplin => "klasse|geschlecht|gewicht" => list<athlete id>
$stats   = ['athletes' => 0, 'entries' => 0, 'reused' => 0];
$heute   = gmdate('Y-m-d H:i:s');

$neuerSportler = static function (array $row, array $kat) use (&$gyms, $namen, $laender, $pick, $jahr, $start): array {
    $g     = (string) $row['_gender'];
    $gym   = $pick($gyms);
    // Gyms schicken ueberwiegend eigene Landsleute, manchmal Zugezogene.
    $land  = mt_rand(1, 100) <= 85 ? $gym['land'] : $pick(array_keys($laender));
    $liste = $namen[$laender[$land]];
    $alter = mt_rand((int) ($kat['age_min'] ?? 18), (int) ($kat['age_max'] ?? 35));
    $wmin  = $kat['weight_min'] !== null ? (float) $kat['weight_min'] : null;
    $wmax  = $kat['weight_max'] !== null ? (float) $kat['weight_max'] : null;

    if ($wmin === null && $wmax === null) {
        $gewicht = null;   // Formen
    } elseif ($wmax === null) {
        $gewicht = $wmin + mt_rand(5, 140) / 10;
    } else {
        $spanne  = $wmin !== null ? ($wmax - $wmin) : 3.0;
        $gewicht = $wmax - mt_rand(0, (int) round(max(0.4, $spanne - 0.2) * 10)) / 10;
    }

    $kaempfe = $alter < 13 ? mt_rand(0, 6) : ($alter < 19 ? mt_rand(0, 18) : mt_rand(0, 40));
    $siege   = (int) round($kaempfe * mt_rand(35, 85) / 100);

    return [
        'gym_id'        => $gym['id'],
        'first_name'    => $pick($liste[$g]),
        'last_name'     => $pick($liste['n']),
        'birthdate'     => sprintf('%04d-%02d-%02d', $jahr - $alter, mt_rand(1, 12), mt_rand(1, 28)),
        'gender'        => $g,
        'nationality'   => $land,
        'weight'        => $gewicht !== null ? round($gewicht, 1) : null,
        'height'        => $alter < 13 ? mt_rand(118, 158) : ($g === 'w' ? mt_rand(152, 180) : mt_rand(160, 198)),
        'record_wins'   => $siege,
        'record_losses' => $kaempfe - $siege,
    ];
};

$kategorien = Database::all('SELECT * FROM event_categories WHERE event_id = ? ORDER BY sort_order', [$eventId]);

Database::transaction(static function () use ($kategorien, $katIds, $partner, &$pool, &$stats, $neuerSportler, $eventId, $fill, $heute, $pick): void {
    foreach ($kategorien as $kat) {
        $row = $katIds[(int) $kat['id']] ?? null;

        if ($row === null) {
            continue;
        }

        // Wie voll ist die Klasse? Randklassen und die ganz Jungen/Alten sind duenner besetzt.
        $wurf = mt_rand(1, 100);

        if ($wurf > $fill * 100) {
            $anzahl = $wurf > 96 ? 1 : 0;
        } else {
            $anzahl = $pick([2, 2, 3, 3, 3, 4, 4, 4, 4, 5, 5, 6, 6, 7, 8]);

            if ((string) $kat['mode'] === 'liste') {
                $anzahl = mt_rand(3, 9);
            }
        }

        $disc   = (string) $row['_disc'];
        $key    = $row['_class'] . '|' . $row['_gender'] . '|' . $row['_weight'];
        $quelle = isset($partner[$disc]) ? ($pool[$partner[$disc]][$key] ?? []) : [];
        shuffle($quelle);

        for ($i = 0; $i < $anzahl; $i++) {
            // Doppelstarter aus der Partner-Disziplin oder neuer Sportler
            if ($quelle !== [] && mt_rand(1, 100) <= 45) {
                $athleteId = (int) array_pop($quelle);
                $gymId     = (int) Database::value('SELECT gym_id FROM athletes WHERE id = ?', [$athleteId]);
                $gewicht   = Database::value('SELECT weight FROM athletes WHERE id = ?', [$athleteId]);
                $stats['reused']++;
            } else {
                $daten     = $neuerSportler($row, $kat);
                $athleteId = Database::insert('athletes', $daten);
                $gymId     = (int) $daten['gym_id'];
                $gewicht   = $daten['weight'];
                $stats['athletes']++;
            }

            $pool[$disc][$key][] = $athleteId;
            $bestaetigt          = mt_rand(1, 100) <= 93;

            Database::insert('event_entries', [
                'event_id'    => $eventId,
                'athlete_id'  => $athleteId,
                'gym_id'      => $gymId,
                'category_id' => (int) $kat['id'],
                'status'      => $bestaetigt ? 'bestaetigt' : 'angemeldet',
                'seed'        => $bestaetigt && $i < 2 && $anzahl >= 5 ? $i + 1 : 0,
                'medical_ok'  => mt_rand(1, 100) <= 85 ? 1 : 0,
                'weighed'     => $bestaetigt && $gewicht !== null ? round((float) $gewicht - mt_rand(0, 4) / 10, 1) : null,
                'weighed_at'  => $bestaetigt && $gewicht !== null ? $heute : null,
                'paid'        => mt_rand(1, 100) <= 88 ? 1 : 0,
                'source'      => 'gym',
            ]);
            $stats['entries']++;
        }
    }
});

// -------------------------------------------------------------- Turnierbaeume --
$baeume = 0;
$kaempfe = 0;

foreach ($kategorien as $kat) {
    if ((string) $kat['mode'] !== 'ko') {
        continue;
    }

    $r = Bracket::generate($event, $kat);

    if ($r['bouts'] > 0) {
        $baeume++;
        $kaempfe += $r['bouts'];
    }
}

// ------------------------------------------------------------------ Zeitplan --
// Jede Kategorie bekommt einen Abschnitt fuer ihr Finale; die Runden davor
// liegen je einen Abschnitt frueher. Gewaehlt wird der Abschnitt, in dem die
// Flaechenart (Ring/Tatami) am wenigsten zu tun hat – bei Gleichstand der
// spaetere, damit die grossen Finals am Schluss stehen.
$k     = count($sessions);
$plan  = [];   // Kategorie-Id => [Runde => list<Kampf>]
$bouts = Database::all(
    'SELECT b.* FROM event_bouts b JOIN event_categories c ON c.id = b.category_id
      WHERE b.event_id = ? ORDER BY c.sort_order, b.round_no, b.bracket_pos',
    [$eventId]
);

foreach ($bouts as $b) {
    $plan[(int) $b['category_id']][(int) $b['round_no']][] = $b;
}

// Grosse Baeume zuerst einplanen, sie haben die wenigste Auswahl.
uasort($plan, static fn (array $x, array $y): int => count($y) <=> count($x));

$last    = [];   // Flaechenart => Abschnitt => Anzahl Kaempfe
$orderNo = [];   // "abschnitt|staette" => Reihung
$reihum  = [];   // "abschnitt|flaeche" => Zaehler

Database::transaction(static function () use ($plan, $katIds, $sessions, $venues, $k, &$last, &$orderNo, &$reihum): void {
    foreach ($plan as $catId => $runden) {
        $area   = (string) ($katIds[$catId]['_area'] ?? 'ring');
        $liste  = $venues[$area] !== [] ? $venues[$area] : ($venues['ring'] !== [] ? $venues['ring'] : $venues['tatami']);
        $anzahl = max(array_keys($runden));
        $echte  = [];   // Runde => Kaempfe, die einen Platz brauchen

        foreach ($runden as $r => $kaempfe) {
            $echte[$r] = array_values(array_filter($kaempfe, static fn (array $b): bool => BoutRepo::needsPlacement($b)));
        }

        // Abschnitt der Runde r, wenn das Finale in Abschnitt f liegt
        $abschnitt = static fn (int $f, int $r): int => max(0, $f - ($anzahl - $r));
        $best      = $k - 1;
        $bestLast  = PHP_INT_MAX;

        for ($f = min($anzahl, $k) - 1; $f < $k; $f++) {
            $summe = 0;

            foreach ($echte as $r => $kaempfe) {
                $summe += ($last[$area][$abschnitt($f, $r)] ?? 0) * count($kaempfe);
            }

            if ($summe <= $bestLast) {
                $bestLast = $summe;
                $best     = $f;
            }
        }

        foreach ($echte as $r => $kaempfe) {
            $s = $abschnitt($best, $r);

            foreach ($kaempfe as $b) {
                $n     = $reihum[$s . '|' . $area] = ($reihum[$s . '|' . $area] ?? -1) + 1;
                $venue = $liste[$n % count($liste)];
                $key   = $s . '|' . $venue;
                $order = $orderNo[$key] = ($orderNo[$key] ?? 0) + 10;

                Database::update('event_bouts', (int) $b['id'], ['session_id' => $sessions[$s], 'venue_id' => $venue, 'order_no' => $order]);
                $last[$area][$s] = ($last[$area][$s] ?? 0) + 1;
            }
        }
    }
});

BoutRepo::renumber($eventId);
Setting::set('home_event', (string) $event['slug']);

printf(
    "Beispiel-Turnier \"%s\" (%s, %s) angelegt:\n  %d Kategorien, %d Gyms, %d Sportler, %d Anmeldungen (%d Doppelstarter), %d Turnierbäume mit %d Kämpfen,\n  %d Tage, %d Abschnitte, %d Wettkampfstätten. Startseite zeigt das Turnier.\n",
    $name, $ruleset['name'], $start->format('d.m.Y'),
    count($kategorien), count($gyms), $stats['athletes'], $stats['entries'], $stats['reused'], $baeume, $kaempfe,
    count($days), $k, count($venues['ring']) + count($venues['tatami'])
);
