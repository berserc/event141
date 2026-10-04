<?php

use App\Models\BoutRepo;

/**
 * Kampf-Detailseite: beide Kaempfer gross (Foto/Video), Story, Bios, Ergebnis.
 *
 * @var array<string,mixed> $event
 * @var array<string,mixed> $bout
 * @var array<int,array<string,mixed>> $times
 */
$base   = '/e/' . $event['slug'];
$komma  = lang() === 'en' ? '.' : ',';
$fmtMin = rtrim(rtrim(number_format((float) $bout['round_minutes'], 1, $komma, ''), '0'), $komma);
$time   = $times[(int) $bout['id']]['label'] ?? '';
$lblBeginn = t('Beginn');

$corner = static function (string $side) use ($bout): array {
    $birth = $bout[$side . '_birth'] ?? null;
    $age   = athlete_age($birth !== null ? (string) $birth : null, $bout[$side . '_age'] ?? null, $bout[$side . '_age_year'] ?? null);

    return [
        'name'   => BoutRepo::cornerName($bout, $side) ?: 'TBA',
        'nick'   => (string) ($bout[$side . '_nick'] ?? ''),
        'gym'    => (string) ($bout[$side . '_gym'] ?? ''),
        'city'   => (string) ($bout[$side . '_city'] ?? ''),
        'nat'    => (string) ($bout[$side . '_nat'] ?? ''),
        'age'    => $age,
        'record' => $bout[$side . '_entry_id'] !== null ? (int) $bout[$side . '_w'] . '–' . (int) $bout[$side . '_l'] . '–' . (int) $bout[$side . '_d'] : '',
        'bio'    => (string) ($bout[$side . '_bio'] ?? ''),
        'weight' => $bout[$side . '_weight'] ?? null,
    ];
};
?>
<section class="event-title-bar">
    <div class="wrap">
        <p class="muted"><a href="<?= e(url($base)) ?>"><?= e($event['name']) ?></a> · <a href="<?= e(url($base . '/kaempfe')) ?>"><?= e($event['type'] === 'gala' ? t('Fightcard') : t('Turnierplan')) ?></a></p>
        <h1><?= e($bout['title'] !== '' ? $bout['title'] : t('Kampf %d', (int) $bout['bout_no'])) ?>
            <?php if ($bout['status'] === 'laufend'): ?><span class="live-badge">LIVE</span><?php endif; ?></h1>
    </div>
</section>

<?php if ((string) $bout['belt_label'] !== ''): ?>
    <div class="wrap" style="text-align:center">
        <p class="main-event__belt-label"><?= e($bout['belt_label']) ?></p>
        <?php if ((string) ($event['belt_path'] ?? '') !== ''): ?><span class="belt-hero"><img src="<?= e(upload_url($event['belt_path'])) ?>" alt="<?= e(t('Titelgürtel')) ?>"></span><?php endif; ?>
    </div>
<?php endif; ?>

<section class="wrap fight-detail" data-refresh="<?= e(url('/api/event/' . $event['slug'] . '/live')) ?>" data-bout="<?= (int) $bout['id'] ?>" data-status="<?= e($bout['status']) ?>">
    <div class="fight-detail__versus">
        <?php foreach (['red', 'blue'] as $side): ?>
            <?php $c = $corner($side); ?>
            <div class="fight-detail__fighter fight-detail__fighter--<?= $side ?><?= $bout['winner'] === $side ? ' is-winner' : '' ?>">
                <div class="fight-detail__photo"><?php $lazy = false; require __DIR__ . '/_media.php'; ?><?= $bout['winner'] === $side ? '<span class="winner-ribbon">Winner</span>' : '' ?></div>
                <p class="fight-detail__corner"><?= e($side === 'red' ? t('Rote Ecke') : t('Blaue Ecke')) ?></p>
                <h2><?= e($c['name']) ?></h2>
                <?php if ($c['nick'] !== ''): ?><p class="fight-detail__nick"><?= e(t('„%s“', $c['nick'])) ?></p><?php endif; ?>
                <p class="fight-detail__gym"><?= e(flag_emoji($c['nat'])) ?> <?= e($c['gym']) ?><?= $c['city'] !== '' ? ', ' . e($c['city']) : '' ?></p>
                <p class="chips chips--lg">
                    <?php if ((int) $bout['show_record'] === 1 && $c['record'] !== ''): ?><span class="chip chip--gold"><?= e(t('Bilanz %s', $c['record'])) ?></span><?php endif; ?>
                    <?php if ($c['age'] !== null): ?><span class="chip chip--gold"><?= e(t('Alter %d', (int) $c['age'])) ?></span><?php endif; ?>
                    <?php if ($c['weight']): ?><span class="chip"><?= e(t('Gewicht %s', format_weight($c['weight']))) ?></span><?php endif; ?>
                </p>
                <?php if ($bout['winner'] === $side): ?><p class="fight-detail__win"><?= e(t('Sieger')) ?> <span class="laurel" aria-hidden="true"></span></p><?php endif; ?>
            </div>
            <?php if ($side === 'red'): ?><div class="fight-detail__vs">VS</div><?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="fight-detail__meta">
        <?php foreach (array_filter([
            t('Disziplin') => $bout['style'],
            t('Klasse')    => $bout['weight_label'] ?: ($bout['category_name'] ?? ''),
            t('Runden')    => t('%d × %s Min.', (int) $bout['rounds'], $fmtMin),
            $lblBeginn     => $time,
            t('Ring')      => $bout['venue_name'] ?? '',
            t('Block')     => $bout['block'],
        ]) as $label => $value): ?>
            <div><small><?= e($label) ?></small><strong<?= $label === $lblBeginn ? ' data-sched="' . (int) $bout['id'] . '"' : '' ?>><?= e($value) ?></strong></div>
        <?php endforeach; ?>
    </div>

    <?php if ($bout['status'] === 'beendet'): ?>
        <?php $w = (string) $bout['winner']; ?>
        <p class="fight-detail__result">
            <strong><?= e($w === 'draw' ? t('Unentschieden') : ($w === 'red' || $w === 'blue' ? t('Sieg für %s', BoutRepo::cornerName($bout, $w)) : t('Kein Sieger'))) ?></strong>
            <?= e(trim(' ' . t((string) $bout['method']) . ((string) $bout['result_round'] !== '' ? ' · ' . t('Runde %s', $bout['result_round']) : '') . ((string) $bout['result_note'] !== '' ? ' · ' . $bout['result_note'] : ''))) ?>
        </p>
    <?php elseif ($bout['status'] === 'abgesagt'): ?>
        <p class="fight-detail__result"><?= e(t('Dieser Kampf wurde abgesagt.')) ?></p>
    <?php endif; ?>

    <?php if (trim((string) $bout['description']) !== ''): ?>
        <div class="fight-detail__story"><?= nl2p((string) $bout['description']) ?></div>
    <?php endif; ?>

    <?php if (trim((string) ($bout['epilog'] ?? '')) !== ''): ?>
        <div class="fight-detail__story fight-detail__epilog"><h3><?= e(t('Nach dem Kampf')) ?></h3><?= nl2p((string) $bout['epilog']) ?></div>
    <?php endif; ?>

    <?php $images = $images ?? []; if ($images !== []): ?>
        <div class="fight-detail__gallery">
            <h3><?= e(t('Bilder')) ?></h3>
            <div class="gallery-grid" style="--gallery-spalten: 3">
                <?php foreach ($images as $img): ?>
                    <figure class="gallery-item">
                        <a href="<?= e(upload_url((string) $img['file'])) ?>" class="js-lightbox" data-caption="<?= e((string) $img['caption']) ?>">
                            <img src="<?= e(upload_url((string) $img['thumb'])) ?>" alt="<?= e((string) $img['caption']) ?>" loading="lazy">
                        </a>
                    </figure>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="fight-detail__bios">
        <?php foreach (['red', 'blue'] as $side): ?>
            <?php $c = $corner($side); ?>
            <?php if (trim($c['bio']) !== ''): ?>
                <div class="side-card side-card--<?= $side ?>">
                    <h3><?= e($c['name']) ?></h3>
                    <?= nl2p($c['bio']) ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <p><a class="btn btn--ghost btn--on-dark" href="<?= e(url($base . ($event['type'] === 'gala' ? '#fightcard' : '/kaempfe'))) ?>">← <?= e($event['type'] === 'gala' ? t('Zur Fightcard') : t('Zur Übersicht')) ?></a></p>
</section>
