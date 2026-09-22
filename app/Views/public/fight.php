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
$fmtMin = rtrim(rtrim(number_format((float) $bout['round_minutes'], 1, ',', ''), '0'), ',');
$time   = $times[(int) $bout['id']]['label'] ?? '';

$corner = static function (string $side) use ($bout): array {
    $birth = $bout[$side . '_birth'] ?? null;
    $age   = $birth ? age_from((string) $birth) : ($bout[$side . '_age'] !== null ? (int) $bout[$side . '_age'] : null);

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
        <p class="muted"><a href="<?= e(url($base)) ?>"><?= e($event['name']) ?></a> · <a href="<?= e(url($base . '/kaempfe')) ?>"><?= $event['type'] === 'gala' ? 'Fightcard' : 'Turnierplan' ?></a></p>
        <h1><?= e($bout['title'] !== '' ? $bout['title'] : 'Kampf ' . (int) $bout['bout_no']) ?>
            <?php if ($bout['status'] === 'laufend'): ?><span class="live-badge">LIVE</span><?php endif; ?></h1>
    </div>
</section>

<?php if ((string) $bout['belt_label'] !== ''): ?>
    <div class="wrap" style="text-align:center">
        <p class="main-event__belt-label"><?= e($bout['belt_label']) ?></p>
        <?php if ((string) ($event['belt_path'] ?? '') !== ''): ?><span class="belt-hero"><img src="<?= e(upload_url($event['belt_path'])) ?>" alt="Titelgürtel"></span><?php endif; ?>
    </div>
<?php endif; ?>

<section class="wrap fight-detail" data-refresh="<?= e(url('/api/event/' . $event['slug'] . '/live')) ?>" data-bout="<?= (int) $bout['id'] ?>" data-status="<?= e($bout['status']) ?>">
    <div class="fight-detail__versus">
        <?php foreach (['red', 'blue'] as $side): ?>
            <?php $c = $corner($side); ?>
            <div class="fight-detail__fighter fight-detail__fighter--<?= $side ?><?= $bout['winner'] === $side ? ' is-winner' : '' ?>">
                <div class="fight-detail__photo"><?php $lazy = false; require __DIR__ . '/_media.php'; ?><?= $bout['winner'] === $side ? '<span class="winner-ribbon">Winner</span>' : '' ?></div>
                <p class="fight-detail__corner"><?= $side === 'red' ? 'Rote Ecke' : 'Blaue Ecke' ?></p>
                <h2><?= e($c['name']) ?></h2>
                <?php if ($c['nick'] !== ''): ?><p class="fight-detail__nick">„<?= e($c['nick']) ?>“</p><?php endif; ?>
                <p class="fight-detail__gym"><?= e(flag_emoji($c['nat'])) ?> <?= e($c['gym']) ?><?= $c['city'] !== '' ? ', ' . e($c['city']) : '' ?></p>
                <p class="chips chips--lg">
                    <?php if ((int) $bout['show_record'] === 1 && $c['record'] !== ''): ?><span class="chip chip--gold">Bilanz <?= e($c['record']) ?></span><?php endif; ?>
                    <?php if ($c['age'] !== null): ?><span class="chip chip--gold">Alter <?= (int) $c['age'] ?></span><?php endif; ?>
                    <?php if ($c['weight']): ?><span class="chip">Gewicht <?= e(format_weight($c['weight'])) ?></span><?php endif; ?>
                </p>
                <?php if ($bout['winner'] === $side): ?><p class="fight-detail__win">Sieger <span class="laurel" aria-hidden="true"></span></p><?php endif; ?>
            </div>
            <?php if ($side === 'red'): ?><div class="fight-detail__vs">VS</div><?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="fight-detail__meta">
        <?php foreach (array_filter([
            'Disziplin'  => $bout['style'],
            'Klasse'     => $bout['weight_label'] ?: ($bout['category_name'] ?? ''),
            'Runden'     => (int) $bout['rounds'] . ' × ' . $fmtMin . ' Min.',
            'Beginn'     => $time,
            'Ring'       => $bout['venue_name'] ?? '',
            'Block'      => $bout['block'],
        ]) as $label => $value): ?>
            <div><small><?= e($label) ?></small><strong<?= $label === 'Beginn' ? ' data-sched="' . (int) $bout['id'] . '"' : '' ?>><?= e($value) ?></strong></div>
        <?php endforeach; ?>
    </div>

    <?php if ($bout['status'] === 'beendet'): ?>
        <?php $w = (string) $bout['winner']; ?>
        <p class="fight-detail__result">
            <strong><?= $w === 'draw' ? 'Unentschieden' : ($w === 'red' || $w === 'blue' ? 'Sieg für ' . e(BoutRepo::cornerName($bout, $w)) : 'Kein Sieger') ?></strong>
            <?= e(trim(' ' . $bout['method'] . ((string) $bout['result_round'] !== '' ? ' · Runde ' . $bout['result_round'] : '') . ((string) $bout['result_note'] !== '' ? ' · ' . $bout['result_note'] : ''))) ?>
        </p>
    <?php elseif ($bout['status'] === 'abgesagt'): ?>
        <p class="fight-detail__result">Dieser Kampf wurde abgesagt.</p>
    <?php endif; ?>

    <?php if (trim((string) $bout['description']) !== ''): ?>
        <div class="fight-detail__story"><?= nl2p((string) $bout['description']) ?></div>
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

    <p><a class="btn btn--ghost btn--on-dark" href="<?= e(url($base . ($event['type'] === 'gala' ? '#fightcard' : '/kaempfe'))) ?>">← Zur <?= $event['type'] === 'gala' ? 'Fightcard' : 'Übersicht' ?></a></p>
</section>
