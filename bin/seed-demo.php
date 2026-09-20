<?php

/**
 * Demo-Daten fuer eine frische (Test-)Installation: zwei Events (Turnier +
 * Gala), Gyms mit Login, Sportler, Anmeldungen, Aufbau und Kategorien.
 *
 *   EVENT141_ENV=dev php bin/seed-demo.php
 *
 * Gym-Login danach: demo@novo.example / demo12345 (und weitere, siehe Ausgabe).
 */

declare(strict_types=1);

use App\Core\Database;
use App\Models\EventRepo;
use App\Models\Setting;

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

if ((int) Database::value('SELECT COUNT(*) FROM events') > 0) {
    exit("Es gibt schon Events – Demo-Daten werden nur in eine leere Installation eingespielt.\n");
}

Setting::setMany([
    'org_name'    => 'Novo Athletics',
    'org_tagline' => 'Kampfsport aus Weiz',
    'org_city'    => 'Weiz',
    'org_email'   => 'office@example.org',
]);

$gyms = [
    ['Novo Athletics', 'NOVO', 'Weiz', 'demo@novo.example'],
    ['Fight Club Graz', 'FCG', 'Graz', 'demo@fcg.example'],
    ['Kampfsportschule Hartberg', 'KSH', 'Hartberg', 'demo@ksh.example'],
    ['Team Panther Wien', 'PANTHER', 'Wien', 'demo@panther.example'],
];

$gymIds = [];

foreach ($gyms as $i => [$name, $short, $city, $mail]) {
    $gymIds[] = Database::insert('gyms', [
        'name'                => $name,
        'short_name'          => $short,
        'slug'                => slugify($name),
        'city'                => $city,
        'contact_name'        => 'Trainer ' . $short,
        'email'               => $mail,
        'status'              => $i === 3 ? 'neu' : 'bestaetigt',
        'login_email'         => $mail,
        'login_password_hash' => password_hash('demo12345', PASSWORD_DEFAULT),
    ]);
}

$vornamen  = ['Lukas', 'Jonas', 'David', 'Felix', 'Elias', 'Noah', 'Paul', 'Leon', 'Anna', 'Lena', 'Sarah', 'Laura', 'Marie', 'Julia', 'Emma', 'Lisa'];
$nachnamen = ['Huber', 'Gruber', 'Bauer', 'Wagner', 'Müller', 'Pichler', 'Steiner', 'Moser', 'Mayer', 'Hofer', 'Leitner', 'Berger', 'Fuchs', 'Eder', 'Fischer', 'Schmid'];

$athletes = []; // [gym => list<id>]
$n        = 0;

foreach ($gymIds as $g => $gymId) {
    for ($k = 0; $k < 6; $k++) {
        $weiblich = $k >= 4;
        $athletes[$g][] = Database::insert('athletes', [
            'gym_id'        => $gymId,
            'first_name'    => $vornamen[($weiblich ? 8 : 0) + ($n % 8)],
            'last_name'     => $nachnamen[$n % 16],
            'birthdate'     => sprintf('%d-%02d-%02d', 1994 + ($n % 12), 1 + ($n % 12), 1 + ($n % 27)),
            'gender'        => $weiblich ? 'w' : 'm',
            'nationality'   => $n % 7 === 0 ? 'DE' : 'AT',
            'weight'        => $weiblich ? 56 + ($n % 5) : 70 + ($n % 9),
            'record_wins'   => $n % 9,
            'record_losses' => $n % 4,
            'record_draws'  => $n % 3 === 0 ? 1 : 0,
        ]);
        $n++;
    }
}

// ------------------------------------------------------------ Turnier --
$turnierId = Database::insert('events', [
    'slug'               => 'steirische-meisterschaft-2026',
    'name'               => 'Steirische Meisterschaft 2026',
    'type'               => 'turnier',
    'sport'              => 'Kickboxen',
    'tagline'            => 'Zwei Tage, zwei Ringe, alle Klassen.',
    'description'        => '<p>Die offene steirische Meisterschaft im Kickboxen – Vorrunden am Samstag, Finals am Sonntagabend.</p>',
    'starts_on'          => date('Y-m-d', strtotime('+3 weeks saturday')),
    'ends_on'            => date('Y-m-d', strtotime('+3 weeks sunday')),
    'start_time'         => '09:00',
    'venue_name'         => 'Sporthalle 2',
    'venue_city'         => 'Weiz',
    'venue_zip'          => '8160',
    'status'             => 'anmeldung',
    'registration_until' => date('Y-m-d', strtotime('+2 weeks')),
    'entry_fee'          => 25,
    'published'          => 1,
]);

$turnier = EventRepo::find($turnierId);
EventRepo::ensureDays($turnier);
$days = EventRepo::days($turnierId);

$sess = [];
$sess['sa-vm'] = Database::insert('event_sessions', ['event_id' => $turnierId, 'day_id' => $days[0]['id'], 'name' => 'Vormittag – Vorrunden', 'starts_at' => '09:30', 'ends_at' => '13:00', 'sort_order' => 10]);
$sess['sa-nm'] = Database::insert('event_sessions', ['event_id' => $turnierId, 'day_id' => $days[0]['id'], 'name' => 'Nachmittag – Halbfinale', 'starts_at' => '14:30', 'ends_at' => '18:00', 'sort_order' => 20]);
$sess['so-ab'] = Database::insert('event_sessions', ['event_id' => $turnierId, 'day_id' => $days[1]['id'], 'name' => 'Finals', 'starts_at' => '17:00', 'ends_at' => '21:00', 'sort_order' => 10]);

$ring1 = Database::insert('event_venues', ['event_id' => $turnierId, 'name' => 'Ring 1', 'short_name' => 'R1', 'color' => '#e63946', 'sort_order' => 10]);
$ring2 = Database::insert('event_venues', ['event_id' => $turnierId, 'name' => 'Ring 2', 'short_name' => 'R2', 'color' => '#1a5fb4', 'sort_order' => 20]);

$kat = [];
$kat['m75'] = Database::insert('event_categories', ['event_id' => $turnierId, 'name' => 'Herren -75 kg', 'discipline' => 'K1', 'gender' => 'm', 'age_min' => 18, 'weight_max' => 75, 'rounds' => 3, 'round_minutes' => 2, 'sort_order' => 10]);
$kat['m81'] = Database::insert('event_categories', ['event_id' => $turnierId, 'name' => 'Herren -81 kg', 'discipline' => 'K1', 'gender' => 'm', 'age_min' => 18, 'weight_min' => 75, 'weight_max' => 81, 'rounds' => 3, 'round_minutes' => 2, 'sort_order' => 20]);
$kat['w60'] = Database::insert('event_categories', ['event_id' => $turnierId, 'name' => 'Damen -60 kg', 'discipline' => 'K1', 'gender' => 'w', 'age_min' => 18, 'weight_max' => 60, 'rounds' => 3, 'round_minutes' => 2, 'sort_order' => 30]);

foreach ($athletes as $g => $ids) {
    foreach ($ids as $k => $athleteId) {
        $a   = Database::one('SELECT * FROM athletes WHERE id = ?', [$athleteId]);
        $cat = $a['gender'] === 'w' ? $kat['w60'] : ((float) $a['weight'] <= 75 ? $kat['m75'] : $kat['m81']);

        Database::insert('event_entries', [
            'event_id'    => $turnierId,
            'athlete_id'  => $athleteId,
            'gym_id'      => (int) $a['gym_id'],
            'category_id' => $cat,
            'status'      => $g === 3 && $k > 3 ? 'angemeldet' : 'bestaetigt',
            'seed'        => $g === 0 && $k === 0 ? 1 : 0,
            'source'      => 'gym',
        ]);
    }
}

// ---------------------------------------------------------------- Gala --
$galaId = Database::insert('events', [
    'slug'         => 'fight-night-4',
    'name'         => 'Novo Fight Night 4',
    'type'         => 'gala',
    'sport'        => 'Kickboxen / K1',
    'tagline'      => 'Die Nacht der Champions.',
    'description'  => '<p>Zehn Kämpfe, ein Titelkampf – Einlass 18:00, erster Kampf 19:00.</p>',
    'starts_on'    => date('Y-m-d', strtotime('+7 weeks saturday')),
    'doors_time'   => '18:00',
    'start_time'   => '19:00',
    'venue_name'   => 'Sporthalle 2',
    'venue_city'   => 'Weiz',
    'venue_zip'    => '8160',
    'status'       => 'geschlossen',
    'ticket_url'   => 'https://example.org/tickets',
    'published'    => 1,
    'gym_registration' => 0,
]);

$gala = EventRepo::find($galaId);
EventRepo::ensureDays($gala);
$gDay  = EventRepo::days($galaId)[0];
$gSess = Database::insert('event_sessions', ['event_id' => $galaId, 'day_id' => $gDay['id'], 'name' => 'Fight Night', 'starts_at' => '19:00', 'ends_at' => '23:00', 'sort_order' => 10]);
$gRing = Database::insert('event_venues', ['event_id' => $galaId, 'name' => 'Ring', 'short_name' => 'R', 'color' => '#e63946', 'sort_order' => 10]);

$galaEntries = [];

foreach ([[0, 0], [1, 0], [0, 1], [2, 1], [1, 2], [3, 2], [0, 4], [1, 4]] as [$g, $k]) {
    $a = Database::one('SELECT * FROM athletes WHERE id = ?', [$athletes[$g][$k]]);
    $galaEntries[] = Database::insert('event_entries', [
        'event_id'   => $galaId,
        'athlete_id' => (int) $a['id'],
        'gym_id'     => (int) $a['gym_id'],
        'status'     => 'bestaetigt',
        'source'     => 'admin',
    ]);
}

$no = 1;
foreach ([[0, 1, 'Eröffnungskampf'], [2, 3, ''], [4, 5, 'Co-Main-Event'], [6, 7, 'Hauptkampf – Damen']] as [$r, $b, $title]) {
    if ($no === 3) {
        Database::insert('event_bouts', ['event_id' => $galaId, 'session_id' => $gSess, 'venue_id' => $gRing, 'order_no' => $no * 10 - 5, 'is_break' => 1, 'title' => 'Pause', 'note' => '20 Minuten', 'rounds' => 1, 'round_minutes' => 1]);
    }

    Database::insert('event_bouts', [
        'event_id'      => $galaId,
        'session_id'    => $gSess,
        'venue_id'      => $gRing,
        'order_no'      => $no * 10,
        'bout_no'       => $no,
        'title'         => $title,
        'red_entry_id'  => $galaEntries[$r],
        'blue_entry_id' => $galaEntries[$b],
        'rounds'        => $no === 4 ? 5 : 3,
        'round_minutes' => 2,
    ]);
    $no++;
}

echo "Demo-Daten eingespielt.\n";
echo "  Events: Steirische Meisterschaft 2026 (Turnier, Anmeldung offen), Novo Fight Night 4 (Gala)\n";
echo "  Gyms (Login im Gym-Bereich, Passwort demo12345):\n";

foreach ($gyms as [$name, , , $mail]) {
    echo "    $mail  ($name)\n";
}
