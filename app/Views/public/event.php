<?php

use App\Models\BoutRepo;
use App\Models\EventRepo;

/**
 * Event-Startseite: Hero mit Countdown und Live-Leiste, Fightcard (Gala) bzw.
 * naechste Kaempfe (Turnier), Beschreibung, Location, Tickets, Sponsoren.
 *
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $bouts
 * @var array<int,array<string,mixed>> $times
 * @var list<array<string,mixed>> $days
 * @var list<array<string,mixed>> $venues
 * @var list<array<string,mixed>> $categories
 * @var array<string,mixed>       $stats
 * @var list<array<string,mixed>> $live
 * @var list<array<string,mixed>> $nextBouts
 * @var list<array<string,mixed>> $sponsors
 * @var list<array<string,mixed>> $tickets
 * @var array<string,string>      $social
 * @var int                       $startTs
 * @var array<string,mixed>|null  $ticket141 (slug, shop_url, embed, embed_js, data|null)
 */
$ticket141 = $ticket141 ?? null;
$tkData    = $ticket141['data'] ?? null;
$tkCats    = is_array($tkData) ? array_values(array_filter((array) ($tkData['categories'] ?? []), 'is_array')) : [];
$tkSale    = is_array($tkData) ? (array) ($tkData['sale'] ?? []) : [];
$tkOpen    = $tkData === null || !empty($tkSale['open']);
$tkUrl     = $ticket141 !== null ? (string) ($tkData['event']['shop_url'] ?? $ticket141['shop_url']) : '';
$hero    = $event['hero_path'] !== '' ? upload_url($event['hero_path']) : ($event['poster_path'] !== '' ? upload_url($event['poster_path']) : '');
$base    = '/e/' . $event['slug'];
$isGala  = $event['type'] === 'gala';
$address = implode(', ', array_filter([$event['venue_name'], $event['venue_street'], trim($event['venue_zip'] . ' ' . $event['venue_city'])]));
$vorbei  = in_array($event['status'], ['beendet'], true) || (string) ($event['ends_on'] ?: $event['starts_on']) < date('Y-m-d');
$liveNow = array_values(array_filter($bouts, static fn (array $b): bool => $b['status'] === 'laufend' && (int) $b['is_break'] === 0));
?>
<section class="event-hero<?= $hero !== '' ? ' event-hero--image' : '' ?>"<?= $hero !== '' ? ' style="--hero:url(\'' . e($hero) . '\')"' : '' ?>>
    <div class="event-hero__shade"></div>
    <div class="wrap event-hero__content">
        <?php if ($event['logo_path'] !== ''): ?><img class="event-hero__logo" src="<?= e(upload_url($event['logo_path'])) ?>" alt="<?= e($event['name']) ?>"><?php endif; ?>
        <p class="event-hero__kicker"><?= e(EventRepo::TYPES[$event['type']] ?? '') ?><?= $event['sport'] !== '' ? ' · ' . e($event['sport']) : '' ?></p>
        <h1><?= e($event['name']) ?></h1>
        <?php if ($event['tagline'] !== ''): ?><p class="event-hero__tagline"><?= e($event['tagline']) ?></p><?php endif; ?>
        <p class="event-hero__facts">
            <strong><?= e(format_date_long($event['starts_on'])) ?><?= $event['ends_on'] ? ' – ' . e(format_date_long($event['ends_on'])) : '' ?></strong>
            <?= $event['doors_time'] !== '' ? '· Einlass ' . e($event['doors_time']) : '' ?>
            <?= $event['start_time'] !== '' ? '· Beginn ' . e($event['start_time']) . ' Uhr' : '' ?>
            <?php if ($address !== ''): ?><br><?= e($address) ?><?php endif; ?>
        </p>

        <?php if ($liveNow !== []): ?>
            <a class="live-bar" href="<?= e(url($base . '/kampf/' . $liveNow[0]['id'])) ?>">
                <span class="live-badge">LIVE</span>
                <span>Jetzt im Ring: <strong><?= e(BoutRepo::cornerName($liveNow[0], 'red') ?: 'TBA') ?></strong> vs. <strong><?= e(BoutRepo::cornerName($liveNow[0], 'blue') ?: 'TBA') ?></strong><?= count($venues) > 1 && $liveNow[0]['venue_name'] ? ' · ' . e($liveNow[0]['venue_name']) : '' ?></span>
            </a>
        <?php elseif (!$vorbei && (int) $event['show_countdown'] === 1 && $startTs > time()): ?>
            <div class="countdown" data-countdown="<?= (int) $startTs ?>" aria-label="Countdown bis zum Beginn">
                <span><b data-cd="d">–</b><small>Tage</small></span>
                <span><b data-cd="h">–</b><small>Std.</small></span>
                <span><b data-cd="m">–</b><small>Min.</small></span>
                <span><b data-cd="s">–</b><small>Sek.</small></span>
            </div>
        <?php endif; ?>

        <p class="event-hero__actions">
            <?php if ($ticket141 !== null && !$vorbei): ?><a class="btn btn--primary" href="#tickets">Tickets sichern</a>
            <?php elseif ($event['ticket_url'] !== '' && !$vorbei): ?><a class="btn btn--primary" href="<?= e($event['ticket_url']) ?>" target="_blank" rel="noopener">Tickets sichern</a><?php endif; ?>
            <a class="btn btn--ghost btn--on-dark" href="<?= $isGala ? '#fightcard' : e(url($base . '/kaempfe')) ?>"><?= $isGala ? 'Fightcard' : 'Turnierplan' ?></a>
            <?php if (EventRepo::registrationOpen($event) && ($gymArea ?? true)): ?>
                <a class="btn btn--ghost btn--on-dark" href="<?= e(url('/gym/event/' . $event['id'])) ?>">Sportler anmelden (Gyms)</a>
            <?php endif; ?>
        </p>
    </div>
</section>

<?php require __DIR__ . '/_event-nav.php'; ?>

<?php if ($isGala): ?>
    <section class="wrap page-section" id="fightcard">
        <h2 class="section-heading">Die Fightcard</h2>
        <p class="section-sub">Klick auf einen Kampf für alle Details</p>
        <div data-fightcard="<?= e(url($base . '/kaempfe', ['fragment' => 1])) ?>">
            <?php require __DIR__ . '/_fightcard.php'; ?>
        </div>
    </section>
<?php elseif ($live !== [] || $nextBouts !== []): ?>
    <section class="wrap page-section">
        <?php if ($live !== []): ?>
            <h2 class="section-heading"><span class="live-badge">LIVE</span> Gerade im Ring</h2>
            <div class="fightcard">
                <?php foreach ($live as $bout): ?><?php require __DIR__ . '/_bout.php'; ?><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($nextBouts !== []): ?>
            <h2 class="section-heading">Nächste Kämpfe</h2>
            <div class="fightcard">
                <?php foreach ($nextBouts as $bout): ?><?php require __DIR__ . '/_bout.php'; ?><?php endforeach; ?>
            </div>
            <p><a class="btn btn--ghost btn--on-dark" href="<?= e(url($base . '/zeitplan')) ?>">Ganzer Zeitplan</a></p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<div class="wrap event-body">
    <div class="event-body__main">
        <?php if (trim($event['description']) !== ''): ?>
            <div class="rich-text"><?= $event['description'] ?></div>
        <?php endif; ?>
    </div>

    <aside class="event-body__side">
        <?php if ($event['poster_path'] !== '' && $event['hero_path'] !== ''): ?>
            <img class="event-poster" src="<?= e(upload_url($event['poster_path'])) ?>" alt="Poster" loading="lazy">
        <?php endif; ?>

        <?php if ($days !== [] && (!$isGala || count($days) > 1)): ?>
            <div class="side-card">
                <h3>Ablauf</h3>
                <?php foreach ($days as $d): ?>
                    <p><strong><?= e(format_date_long($d['day_date'])) ?></strong><?= $d['label'] !== '' ? ' – ' . e($d['label']) : '' ?></p>
                    <?php if ($d['sessions'] !== []): ?>
                        <ul>
                            <?php foreach ($d['sessions'] as $s): ?>
                                <li><?= $s['starts_at'] !== '' ? e($s['starts_at']) . ' ' : '' ?><?= e($s['name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (count($venues) > 1): ?>
                    <p class="muted"><?= count($venues) ?> Wettkampfstätten: <?= e(implode(', ', array_column($venues, 'name'))) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($categories !== [] && !$isGala): ?>
            <div class="side-card">
                <h3>Kategorien</h3>
                <ul>
                    <?php foreach ($categories as $c): ?>
                        <li><?= e($c['name']) ?> <small class="muted"><?= e(EventRepo::categoryInfo($c)) ?></small></li>
                    <?php endforeach; ?>
                </ul>
                <p class="muted"><?= (int) $stats['entries']['bestaetigt'] ?> Teilnehmer aus <?= (int) $stats['gyms'] ?> Gyms</p>
            </div>
        <?php endif; ?>

        <?php if ($event['contact_email'] !== '' || $event['contact_phone'] !== ''): ?>
            <div class="side-card">
                <h3>Kontakt</h3>
                <?php if ($event['contact_email'] !== ''): ?><p><?= mail_link($event['contact_email']) ?></p><?php endif; ?>
                <?php if ($event['contact_phone'] !== ''): ?><p><?= tel_link($event['contact_phone']) ?></p><?php endif; ?>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?php if ($address !== ''): ?>
    <section class="wrap page-section" id="location">
        <h2 class="section-heading">Die Location</h2>
        <?php if ($event['location_note'] !== ''): ?><p class="section-sub"><?= e($event['location_note']) ?></p><?php endif; ?>
        <div class="location-grid">
            <?php if ((int) $event['show_map'] === 1): ?>
                <div class="map-wrap" data-map="https://www.google.com/maps?q=<?= e(rawurlencode($address)) ?>&amp;output=embed">
                    <button class="btn btn--ghost btn--on-dark" type="button" data-map-load>Karte laden (Google Maps)</button>
                    <small class="muted">Erst beim Laden werden Daten an Google übertragen.</small>
                </div>
            <?php endif; ?>
            <div class="location-info">
                <h3><?= e($event['venue_name'] ?: $event['venue_city']) ?></h3>
                <p>📍 <strong><?= e($event['venue_street']) ?></strong><?= $event['venue_street'] !== '' ? ', ' : '' ?><?= e(trim($event['venue_zip'] . ' ' . $event['venue_city'])) ?></p>
                <p>📅 <strong><?= e(format_date_long($event['starts_on'])) ?></strong></p>
                <?php if ($event['doors_time'] !== ''): ?><p>🚪 Einlass ab <strong><?= e($event['doors_time']) ?> Uhr</strong></p><?php endif; ?>
                <?php if ($event['start_time'] !== ''): ?><p>🥊 Beginn ca. <strong><?= e($event['start_time']) ?> Uhr</strong></p><?php endif; ?>
                <?php if ($event['min_age_note'] !== ''): ?><p>🔞 <?= e($event['min_age_note']) ?></p><?php endif; ?>
                <p><a class="btn btn--ghost btn--on-dark" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= e(rawurlencode($address)) ?>" target="_blank" rel="noopener">Route planen</a></p>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($ticket141 !== null && !$vorbei): ?>
    <section class="wrap page-section" id="tickets">
        <h2 class="section-heading">Tickets sichern</h2>
        <?php if ($event['ticket_note'] !== ''): ?><p class="section-sub"><?= e($event['ticket_note']) ?></p><?php endif; ?>
        <?php if ($tkCats !== []): ?>
            <div class="ticket-grid">
                <?php foreach ($tkCats as $c): ?>
                    <?php $avail = \App\Core\Ticket141Client::availability($c); ?>
                    <div class="ticket-card<?= !empty($c['sold_out']) ? ' ticket-card--soldout' : '' ?>">
                        <h3><?= e((string) $c['name']) ?></h3>
                        <div class="ticket-card__price"><?= e(\App\Core\Ticket141Client::price((int) ($c['price_cents'] ?? 0))) ?></div>
                        <?php if ((string) ($c['description'] ?? '') !== ''): ?><p><?= e((string) $c['description']) ?></p><?php endif; ?>
                        <?php if ($avail !== ''): ?><p class="ticket-card__avail"><?= e($avail) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($tkData === null && $tickets !== []): ?>
            <div class="ticket-grid">
                <?php foreach ($tickets as $t): ?>
                    <div class="ticket-card<?= $t['highlight'] ? ' ticket-card--highlight' : '' ?>">
                        <h3><?= e($t['label']) ?></h3>
                        <div class="ticket-card__price"><?= e($t['price']) ?></div>
                        <?php if ($t['note'] !== ''): ?><p><?= e($t['note']) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($tkOpen && $ticket141['embed']): ?>
            <div data-ticket141="<?= e($ticket141['slug']) ?>" data-return="<?= e(\App\Core\Fightcard::requestBase() . url($base)) ?>"></div>
            <script src="<?= e($ticket141['embed_js']) ?>" defer></script>
            <p class="ticket-actions"><a class="btn btn--ghost btn--on-dark" href="<?= e($tkUrl) ?>" target="_blank" rel="noopener">Shop in neuem Fenster öffnen</a></p>
        <?php elseif ($tkOpen): ?>
            <p class="ticket-actions"><a class="btn btn--primary btn--lg" href="<?= e($tkUrl) ?>" target="_blank" rel="noopener">Tickets kaufen</a></p>
        <?php else: ?>
            <p class="ticket-actions muted"><?= e((string) ($tkSale['reason'] ?: 'Der Ticketverkauf ist derzeit nicht geöffnet.')) ?></p>
        <?php endif; ?>
    </section>
<?php elseif (($tickets !== [] || $event['ticket_url'] !== '') && !$vorbei): ?>
    <section class="wrap page-section" id="tickets">
        <h2 class="section-heading">Tickets sichern</h2>
        <?php if ($event['ticket_note'] !== ''): ?><p class="section-sub"><?= e($event['ticket_note']) ?></p><?php endif; ?>
        <?php if ($tickets !== []): ?>
            <div class="ticket-grid">
                <?php foreach ($tickets as $t): ?>
                    <div class="ticket-card<?= $t['highlight'] ? ' ticket-card--highlight' : '' ?>">
                        <h3><?= e($t['label']) ?></h3>
                        <div class="ticket-card__price"><?= e($t['price']) ?></div>
                        <?php if ($t['note'] !== ''): ?><p><?= e($t['note']) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($event['ticket_url'] !== ''): ?>
            <p class="ticket-actions"><a class="btn btn--primary" href="<?= e($event['ticket_url']) ?>" target="_blank" rel="noopener">Jetzt Tickets kaufen</a></p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($sponsors !== []): ?>
    <?php
    $mainSponsors = array_values(array_filter($sponsors, static fn (array $s): bool => (int) $s['is_main'] === 1));
    $others       = array_values(array_filter($sponsors, static fn (array $s): bool => (int) $s['is_main'] !== 1));
    $tile         = static function (array $s, bool $big): string {
        $inner = $s['logo_path'] !== ''
            ? '<img src="' . e(upload_url($s['logo_path'])) . '" alt="' . e($s['name']) . '" loading="lazy">'
            : '<span>' . e($s['name']) . '</span>';
        $style = $s['tile_color'] !== '' ? ' style="background:' . e($s['tile_color']) . '"' : '';
        $class = 'sponsor-tile' . ($big ? ' sponsor-tile--main' : '');

        return $s['url'] !== ''
            ? '<a class="' . $class . '"' . $style . ' href="' . e($s['url']) . '" target="_blank" rel="noopener sponsored" title="' . e($s['name']) . '">' . $inner . '</a>'
            : '<div class="' . $class . '"' . $style . ' title="' . e($s['name']) . '">' . $inner . '</div>';
    };
    ?>
    <section class="wrap page-section" id="sponsoren">
        <h2 class="section-heading">Unsere Sponsoren</h2>
        <?php if ($mainSponsors !== []): ?>
            <div class="sponsor-grid sponsor-grid--main"><?php foreach ($mainSponsors as $s) { echo $tile($s, true); } ?></div>
        <?php endif; ?>
        <?php if ($others !== []): ?>
            <div class="sponsor-grid"><?php foreach ($others as $s) { echo $tile($s, false); } ?></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($social !== []): ?>
    <p class="wrap social-row">
        <?php foreach ($social as $net => $link): ?>
            <a href="<?= e($link) ?>" target="_blank" rel="noopener"><?= e(ucfirst($net)) ?></a>
        <?php endforeach; ?>
    </p>
<?php endif; ?>
