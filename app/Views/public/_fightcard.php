<?php

use App\Models\BoutRepo;

/**
 * Fightcard einer Gala: Hauptkampf als grosse Karte, darunter alle Kaempfe
 * und Pausen (Hauptkampf zuerst = chronologisch rueckwaerts), gruppiert nach
 * Block (Main Card, Prelims …), mit voraussichtlichen Beginnzeiten.
 * Wird auch als Fragment (?fragment=1) fuer die Live-Aktualisierung geladen.
 *
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $bouts  chronologisch, nur sichtbare
 * @var array<int,array<string,mixed>> $times
 */
$base  = '/e/' . $event['slug'];
$list  = array_reverse($bouts);
$main  = null;

foreach ($list as $b) {
    if ((int) $b['is_break'] === 0 && (string) $b['status'] !== 'abgesagt') {
        $main = $b;
        break;
    }
}

$fmtMin = static fn ($v): string => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');

$resultText = static function (array $b): string {
    if ((string) $b['status'] !== 'beendet') {
        return '';
    }

    $w    = (string) $b['winner'];
    $text = $w === 'draw' ? 'Unentschieden' : ($w === 'red' ? 'Sieg ' . BoutRepo::cornerName($b, 'red') : ($w === 'blue' ? 'Sieg ' . BoutRepo::cornerName($b, 'blue') : 'Kein Sieger'));
    $more = trim($b['method'] . ((string) $b['result_round'] !== '' ? ' · Runde ' . $b['result_round'] : '') . ((string) $b['result_note'] !== '' ? ' · ' . $b['result_note'] : ''), ' ·');

    return $text . ($more !== '' ? ' – ' . $more : '');
};
?>
<?php if ($list === []): ?>
    <p class="muted">Die Fightcard wird noch zusammengestellt.</p>
<?php endif; ?>

<?php if ($main !== null): ?>
    <?php $bout = $main; $red = BoutRepo::cornerName($main, 'red') ?: 'TBA'; $blue = BoutRepo::cornerName($main, 'blue') ?: 'TBA'; ?>
    <a class="main-event main-event--<?= e($main['status']) ?>" href="<?= e(url($base . '/kampf/' . $main['id'])) ?>">
        <span class="main-event__badge">
            <?= e($main['title'] !== '' ? $main['title'] : 'Hauptkampf') ?> · Kampf <?= (int) $main['bout_no'] ?>
            <?php if ($main['status'] === 'laufend'): ?><span class="live-badge">LIVE</span><?php endif; ?>
        </span>
        <?php if ((string) $main['belt_label'] !== ''): ?>
            <span class="main-event__belt-label"><?= e($main['belt_label']) ?></span>
            <?php if ((string) ($event['belt_path'] ?? '') !== ''): ?><span class="belt-hero"><img src="<?= e(upload_url($event['belt_path'])) ?>" alt="Titelgürtel – <?= e($main['belt_label']) ?>"></span><?php endif; ?>
        <?php endif; ?>
        <span class="main-event__body">
            <span class="main-event__fighter main-event__fighter--red<?= $main['winner'] === 'red' ? ' is-winner' : '' ?>">
                <span class="main-event__photo"><?php $side = 'red'; $lazy = false; require __DIR__ . '/_media.php'; ?></span>
                <span class="main-event__name"><?= e($red) ?></span>
                <?php if ($main['red_nick'] !== '' && $main['red_nick'] !== null): ?><span class="main-event__nick">„<?= e($main['red_nick']) ?>“</span><?php endif; ?>
                <span class="main-event__gym"><?= e($main['red_gym'] ?? '') ?></span>
                <?php if ((int) $main['show_record'] === 1 && $main['red_entry_id'] !== null): ?><span class="main-event__rec"><?= (int) $main['red_w'] ?>–<?= (int) $main['red_l'] ?>–<?= (int) $main['red_d'] ?></span><?php endif; ?>
            </span>
            <span class="main-event__vs">VS</span>
            <span class="main-event__fighter main-event__fighter--blue<?= $main['winner'] === 'blue' ? ' is-winner' : '' ?>">
                <span class="main-event__photo"><?php $side = 'blue'; $lazy = false; require __DIR__ . '/_media.php'; ?></span>
                <span class="main-event__name"><?= e($blue) ?></span>
                <?php if ($main['blue_nick'] !== '' && $main['blue_nick'] !== null): ?><span class="main-event__nick">„<?= e($main['blue_nick']) ?>“</span><?php endif; ?>
                <span class="main-event__gym"><?= e($main['blue_gym'] ?? '') ?></span>
                <?php if ((int) $main['show_record'] === 1 && $main['blue_entry_id'] !== null): ?><span class="main-event__rec"><?= (int) $main['blue_w'] ?>–<?= (int) $main['blue_l'] ?>–<?= (int) $main['blue_d'] ?></span><?php endif; ?>
            </span>
        </span>
        <span class="main-event__meta">
            <?= e(implode(' · ', array_filter([$main['style'], $main['weight_label'] ?: ($main['category_name'] ?? ''), (int) $main['rounds'] . ' × ' . $fmtMin($main['round_minutes']) . ' Min.']))) ?>
            <?php if (isset($times[(int) $main['id']])): ?> · <span class="start-time" data-sched="<?= (int) $main['id'] ?>"><?= e($times[(int) $main['id']]['label']) ?></span><?php endif; ?>
        </span>
        <?php if ($resultText($main) !== ''): ?><span class="main-event__result"><?= e($resultText($main)) ?></span><?php endif; ?>
    </a>
<?php endif; ?>

<div class="fight-list">
    <?php $lastBlock = null; ?>
    <?php foreach ($list as $bout): ?>
        <?php if ($main !== null && (int) $bout['id'] === (int) $main['id']) { $lastBlock = (string) $bout['block']; continue; } ?>

        <?php if ((int) $bout['is_break'] === 1): ?>
            <div class="break-row break-row--<?= e($bout['status']) ?>">
                <span><?= e($bout['title'] ?: 'Pause') ?><?= $bout['note'] !== '' ? ' · ' . e($bout['note']) : '' ?></span>
                <?php if (isset($times[(int) $bout['id']])): ?><span class="start-time" data-sched="<?= (int) $bout['id'] ?>"><?= e($times[(int) $bout['id']]['label']) ?></span><?php endif; ?>
            </div>
            <?php continue; ?>
        <?php endif; ?>

        <?php if ((string) $bout['block'] !== '' && (string) $bout['block'] !== $lastBlock): ?>
            <h3 class="block-heading"><?= e($bout['block']) ?></h3>
        <?php endif; ?>
        <?php $lastBlock = (string) $bout['block']; ?>

        <?php $red = BoutRepo::cornerName($bout, 'red') ?: 'TBA'; $blue = BoutRepo::cornerName($bout, 'blue') ?: 'TBA'; ?>
        <a class="fight-row fight-row--<?= e($bout['status']) ?>" href="<?= e(url($base . '/kampf/' . $bout['id'])) ?>">
            <span class="fight-row__no"><?= (int) $bout['bout_no'] ?></span>
            <span class="fight-row__corner fight-row__corner--red<?= $bout['winner'] === 'red' ? ' is-winner' : '' ?>">
                <span class="row-thumb"><?php $side = 'red'; $lazy = true; require __DIR__ . '/_media.php'; ?></span>
                <span class="fight-row__who"><strong><?= e($red) ?></strong><small><?= e($bout['red_gym'] ?? '') ?></small></span>
            </span>
            <span class="fight-row__mid">
                <?php if ($bout['status'] === 'laufend'): ?><span class="live-badge">LIVE</span>
                <?php elseif ($bout['status'] === 'abgesagt'): ?><span class="badge-dark">abgesagt</span>
                <?php else: ?><span class="fight-row__vs">VS</span><?php endif; ?>
                <small><?= e(implode(' · ', array_filter([$bout['title'], $bout['style'], $bout['weight_label'] ?: ($bout['category_name'] ?? '')]))) ?></small>
                <small><?= (int) $bout['rounds'] ?> × <?= e($fmtMin($bout['round_minutes'])) ?> Min.<?php if (isset($times[(int) $bout['id']])): ?> · <span class="start-time" data-sched="<?= (int) $bout['id'] ?>"><?= e($times[(int) $bout['id']]['label']) ?></span><?php endif; ?></small>
                <?php if ($resultText($bout) !== ''): ?><small class="fight-row__result"><?= e($resultText($bout)) ?></small><?php endif; ?>
            </span>
            <span class="fight-row__corner fight-row__corner--blue<?= $bout['winner'] === 'blue' ? ' is-winner' : '' ?>">
                <span class="fight-row__who"><strong><?= e($blue) ?></strong><small><?= e($bout['blue_gym'] ?? '') ?></small></span>
                <span class="row-thumb"><?php $side = 'blue'; $lazy = true; require __DIR__ . '/_media.php'; ?></span>
            </span>
        </a>
    <?php endforeach; ?>
</div>
