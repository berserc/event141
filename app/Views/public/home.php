<?php

use App\Models\EventRepo;

/**
 * Startseite: Liste aller veröffentlichten Events.
 *
 * @var list<array<string,mixed>> $events
 * @var string                    $introTitle
 * @var string                    $introText
 */
$heute     = date('Y-m-d');
$kommend   = array_values(array_filter($events, static fn (array $e): bool => (string) ($e['ends_on'] ?: $e['starts_on']) >= $heute));
$vergangen = array_values(array_filter($events, static fn (array $e): bool => (string) ($e['ends_on'] ?: $e['starts_on']) < $heute));
?>
<section class="hero-intro">
    <div class="wrap">
        <h1><?= e($introTitle) ?></h1>
        <?php if (trim($introText) !== ''): ?><div class="hero-intro__text"><?= $introText ?></div><?php endif; ?>
    </div>
</section>

<section class="wrap">
    <?php if ($kommend === []): ?>
        <p class="muted">Derzeit ist kein Event angekündigt.</p>
    <?php endif; ?>

    <div class="event-grid">
        <?php foreach ($kommend as $ev): ?>
            <a class="event-tile" href="<?= e(url('/e/' . $ev['slug'])) ?>">
                <?php if ($ev['poster_path'] !== ''): ?>
                    <img class="event-tile__img" src="<?= e(upload_url($ev['poster_path'])) ?>" alt="" loading="lazy">
                <?php elseif ($ev['hero_path'] !== ''): ?>
                    <img class="event-tile__img" src="<?= e(upload_url($ev['hero_path'])) ?>" alt="" loading="lazy">
                <?php endif; ?>
                <span class="event-tile__body">
                    <span class="event-tile__date"><?= e(format_date_range($ev['starts_on'], $ev['ends_on'])) ?></span>
                    <span class="event-tile__title"><?= e($ev['name']) ?></span>
                    <span class="event-tile__meta"><?= e(EventRepo::TYPES[$ev['type']] ?? '') ?><?= $ev['venue_city'] !== '' ? ' · ' . e($ev['venue_city']) : '' ?></span>
                    <?php if ($ev['status'] === 'laufend'): ?><span class="live-badge">LIVE</span><?php endif; ?>
                    <?php if (EventRepo::registrationOpen($ev)): ?><span class="badge-dark badge-dark--bestaetigt">Anmeldung offen</span><?php endif; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($vergangen !== []): ?>
        <h2 class="section-heading">Vergangene Events</h2>
        <ul class="event-list">
            <?php foreach ($vergangen as $ev): ?>
                <li><a href="<?= e(url('/e/' . $ev['slug'])) ?>"><?= e($ev['name']) ?></a> <small class="muted"><?= e(format_date_range($ev['starts_on'], $ev['ends_on'])) ?></small></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
