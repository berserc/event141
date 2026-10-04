<?php

use App\Models\BoutRepo;
use App\Models\EventRepo;

/**
 * Fightcard (Gala) bzw. Turnierbäume (Turnier).
 *
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $bouts
 * @var list<array{category:array<string,mixed>,rounds:list<array{round:int,label:string,bouts:list<array<string,mixed>>}>}> $brackets
 */
?>
<section class="event-title-bar">
    <div class="wrap">
        <p class="muted"><a href="<?= e(url('/e/' . $event['slug'])) ?>"><?= e($event['name']) ?></a></p>
        <h1><?= e($event['type'] === 'gala' ? t('Fightcard') : t('Turnierplan')) ?></h1>
    </div>
</section>
<?php require __DIR__ . '/_event-nav.php'; ?>

<section class="wrap">
    <?php if ($event['type'] === 'gala'): ?>
        <?php if ($bouts === []): ?>
            <p class="muted"><?= e(t('Die Fightcard wird noch zusammengestellt.')) ?></p>
        <?php else: ?>
            <div data-fightcard="<?= e(url('/e/' . $event['slug'] . '/kaempfe', ['fragment' => 1])) ?>">
                <?php require __DIR__ . '/_fightcard.php'; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($brackets === []): ?>
            <p class="muted"><?= e(t('Die Turnierbäume werden nach Anmeldeschluss erstellt.')) ?></p>
        <?php endif; ?>

        <?php foreach ($brackets as $b): ?>
            <h2 class="section-heading" id="kat-<?= (int) $b['category']['id'] ?>"><?= e($b['category']['name']) ?> <small class="muted"><?= e(EventRepo::categoryInfo($b['category'])) ?></small></h2>
            <div class="bracket-scroll">
                <div class="bracket" style="--rounds:<?= count($b['rounds']) ?>">
                    <?php foreach ($b['rounds'] as $round): ?>
                        <div class="bracket__round">
                            <h3 class="bracket__label"><?= e(t($round['label'])) ?></h3>
                            <?php foreach ($round['bouts'] as $bout): ?>
                                <?php $red = BoutRepo::cornerName($bout, 'red'); $blue = BoutRepo::cornerName($bout, 'blue'); ?>
                                <div class="bracket__match bracket__match--<?= e($bout['status']) ?>" title="<?= e(t('Kampf #%d', (int) $bout['bout_no'])) ?><?= $bout['venue_name'] ? ' · ' . e($bout['venue_name']) : '' ?>">
                                    <div class="bracket__slot bracket__slot--red<?= $bout['winner'] === 'red' ? ' is-winner' : '' ?>">
                                        <span><?= $red !== '' ? e($red) : '<em class="muted">' . ($bout['status'] === 'abgesagt' ? '–' : e(t('offen'))) . '</em>' ?></span>
                                        <?php if ($bout['red_gym'] !== null): ?><small><?= e($bout['red_gym_short'] ?: $bout['red_gym']) ?></small><?php endif; ?>
                                    </div>
                                    <div class="bracket__slot bracket__slot--blue<?= $bout['winner'] === 'blue' ? ' is-winner' : '' ?>">
                                        <span><?= $blue !== '' ? e($blue) : '<em class="muted">' . ($bout['method'] === 'Freilos' ? e(t('Freilos')) : ($bout['status'] === 'abgesagt' ? '–' : e(t('offen')))) . '</em>' ?></span>
                                        <?php if ($bout['blue_gym'] !== null): ?><small><?= e($bout['blue_gym_short'] ?: $bout['blue_gym']) ?></small><?php endif; ?>
                                    </div>
                                    <div class="bracket__meta">#<?= (int) $bout['bout_no'] ?><?= $bout['status'] === 'laufend' ? ' <span class="live-badge">LIVE</span>' : '' ?><?= $bout['status'] === 'beendet' && $bout['method'] !== '' && $bout['method'] !== 'Freilos' ? ' · ' . e(t($bout['method'])) : '' ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php $frei = array_values(array_filter($bouts, static fn (array $x): bool => (int) $x['round_no'] === 0 && (int) $x['is_break'] === 0)); ?>
        <?php if ($frei !== []): ?>
            <h2 class="section-heading"><?= e(t('Weitere Kämpfe')) ?></h2>
            <div class="fightcard">
                <?php foreach ($frei as $bout): ?><?php require __DIR__ . '/_bout.php'; ?><?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
