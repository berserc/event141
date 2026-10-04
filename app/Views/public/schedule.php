<?php

use App\Models\BoutRepo;

/**
 * Zeitplan: Tag → Abschnitt → Wettkampfstätten.
 *
 * @var array<string,mixed> $event
 * @var array{days:list<array<string,mixed>>,open:list<array<string,mixed>>} $schedule
 */
?>
<section class="event-title-bar">
    <div class="wrap">
        <p class="muted"><a href="<?= e(url('/e/' . $event['slug'])) ?>"><?= e($event['name']) ?></a></p>
        <h1><?= e(t('Zeitplan')) ?></h1>
    </div>
</section>
<?php require __DIR__ . '/_event-nav.php'; ?>

<section class="wrap">
    <?php $hatKaempfe = false; ?>
    <?php foreach ($schedule['days'] as $day): ?>
        <h2 class="section-heading"><?= e(format_date_long($day['day_date'])) ?><?= $day['label'] !== '' ? ' – ' . e(t($day['label'])) : '' ?></h2>
        <?php foreach ($day['sessions'] as $s): ?>
            <div class="pub-session">
                <h3 class="pub-session__title"><?= e(t($s['name'])) ?> <small><?= e($s['starts_at']) ?><?= $s['ends_at'] !== '' ? '–' . e($s['ends_at']) : '' ?></small></h3>
                <?php if ($s['note'] !== ''): ?><p class="muted"><?= e($s['note']) ?></p><?php endif; ?>
                <div class="pub-venues" style="--venues:<?= max(1, count($s['venues'])) ?>">
                    <?php foreach ($s['venues'] as $v): ?>
                        <?php if ($v['bouts'] === []) { continue; } $hatKaempfe = true; ?>
                        <div class="pub-venue">
                            <h4 class="pub-venue__title" style="border-color:<?= e($v['color'] ?: '#e63946') ?>"><?= e($v['name']) ?></h4>
                            <ol class="pub-venue__list">
                                <?php foreach ($v['bouts'] as $bout): ?>
                                    <?php if ((int) $bout['active'] !== 1) { continue; } ?>
                                    <li class="pub-bout pub-bout--<?= e($bout['status']) ?><?= (int) $bout['is_break'] === 1 ? ' pub-bout--break' : '' ?>">
                                        <?php if ((int) $bout['is_break'] === 1): ?>
                                            <em><?= e(t($bout['title'] ?: 'Pause')) ?></em>
                                        <?php else: ?>
                                            <span class="pub-bout__no">#<?= (int) $bout['bout_no'] ?></span>
                                            <?php if (isset($times[(int) $bout['id']])): ?><span class="pub-bout__time"><?= e($times[(int) $bout['id']]['label']) ?></span><?php endif; ?>
                                            <span class="pub-bout__names">
                                                <span class="corner corner--red<?= $bout['winner'] === 'red' ? ' is-winner' : '' ?>"><?= e(BoutRepo::cornerName($bout, 'red')) ?: '<em>' . e(t('offen')) . '</em>' ?></span>
                                                <span class="muted">vs</span>
                                                <span class="corner corner--blue<?= $bout['winner'] === 'blue' ? ' is-winner' : '' ?>"><?= e(BoutRepo::cornerName($bout, 'blue')) ?: '<em>' . e(t('offen')) . '</em>' ?></span>
                                            </span>
                                            <small class="muted"><?= e($bout['category_name'] ?? $bout['title']) ?><?= $bout['round_label'] !== '' ? ' · ' . e(t($bout['round_label'])) : '' ?></small>
                                            <?php if ($bout['status'] === 'laufend'): ?><span class="live-badge">LIVE</span><?php endif; ?>
                                            <?php if ($bout['status'] === 'beendet'): ?><small class="pub-bout__res"><?= e(t($bout['method'])) ?></small><?php endif; ?>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <?php if (!$hatKaempfe): ?>
        <p class="muted"><?= e(t('Der Zeitplan wird noch erstellt.')) ?></p>
    <?php endif; ?>
</section>
