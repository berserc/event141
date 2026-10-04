<?php

use App\Models\BoutRepo;

/**
 * Turnierbaum als "Spinne": Runden als Spalten, rechts der Sieger, die
 * Verbindungslinien zeichnet spinne.css. Wird auf der Website, in der
 * Verwaltung und in der Druckansicht verwendet.
 *
 * @var list<array{round:int,label:string,bouts:list<array<string,mixed>>}> $rounds
 * @var array<int,array<string,int>>   $feeders Kampf-Id => ['red' => Nr., 'blue' => Nr.] (optional)
 * @var array<int,array<string,mixed>> $times   (optional)
 * @var string                         $boutBase Basis-Adresse fuer Links auf die Kampfseite (optional)
 */
$feeders  = $feeders ?? [];
$times    = $times ?? [];
$boutBase = $boutBase ?? '';
$letzte   = $rounds !== [] ? $rounds[count($rounds) - 1] : null;
$final    = $letzte !== null ? ($letzte['bouts'][0] ?? null) : null;
$sieger   = $final !== null && in_array((string) $final['winner'], ['red', 'blue'], true)
    ? BoutRepo::cornerName($final, (string) $final['winner'])
    : '';

// Text einer Ecke: Name, sonst "Sieger #12" (Zubringer), "Freilos" oder Strich.
$ecke = static function (array $bout, string $side) use ($feeders): string {
    $name = BoutRepo::cornerName($bout, $side);

    if ($name !== '') {
        return e($name);
    }

    $nr = $feeders[(int) $bout['id']][$side] ?? null;

    if ($nr !== null) {
        return '<em>' . e(t('Sieger #%d', $nr)) . '</em>';
    }

    if ((string) $bout['method'] === 'Freilos') {
        return '<em>' . e(t('Freilos')) . '</em>';
    }

    return '<em>–</em>';
};
?>
<div class="spinne<?= count($rounds[0]['bouts'] ?? []) >= 16 ? ' spinne--dense' : '' ?>" style="--rounds:<?= count($rounds) + 1 ?>">
    <?php foreach ($rounds as $ri => $round): ?>
        <div class="spinne__round<?= $ri === count($rounds) - 1 ? ' spinne__round--final' : '' ?>">
            <div class="spinne__label"><?= e(t($round['label'])) ?></div>
            <div class="spinne__cells">
                <?php foreach ($round['bouts'] as $bout): ?>
                    <?php
                    $leer  = $bout['red_entry_id'] === null && $bout['blue_entry_id'] === null && (string) $bout['status'] === 'abgesagt';
                    $zeit  = $times[(int) $bout['id']]['label'] ?? '';
                    $platz = BoutRepo::needsPlacement($bout);
                    $meta  = array_filter([
                        $platz && (int) $bout['bout_no'] > 0 ? '#' . (int) $bout['bout_no'] : '',
                        $platz ? (string) ($bout['venue_short'] ?: ($bout['venue_name'] ?? '')) : '',
                        $platz && (string) $bout['status'] !== 'beendet' ? (string) $zeit : '',
                        (string) $bout['status'] === 'beendet' && !in_array((string) $bout['method'], ['', 'Freilos'], true) ? t((string) $bout['method']) : '',
                        (string) $bout['status'] === 'laufend' ? 'LIVE' : '',
                    ]);
                    ?>
                    <div class="spinne__cell<?= $leer ? ' is-empty' : '' ?>">
                        <div class="spinne__match spinne__match--<?= e($bout['status']) ?>">
                            <?php foreach (['red', 'blue'] as $side): ?>
                                <div class="spinne__slot spinne__slot--<?= $side ?><?= $bout['winner'] === $side ? ' is-winner' : '' ?>">
                                    <span><?= $ecke($bout, $side) ?></span>
                                    <?php if ($bout[$side . '_gym'] !== null): ?><small><?= e($bout[$side . '_gym_short'] ?: $bout[$side . '_gym']) ?></small><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($meta !== []): ?>
                                <div class="spinne__meta">
                                    <?php if ($boutBase !== '' && $platz): ?><a href="<?= e($boutBase . $bout['id']) ?>"><?= e(implode(' · ', $meta)) ?></a><?php else: ?><?= e(implode(' · ', $meta)) ?><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="spinne__round spinne__round--winner">
        <div class="spinne__label"><?= e(t('Sieger')) ?></div>
        <div class="spinne__cells">
            <div class="spinne__cell"><div class="spinne__winner"><?= $sieger !== '' ? e($sieger) : '&nbsp;' ?></div></div>
        </div>
    </div>
</div>
