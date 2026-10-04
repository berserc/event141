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

        <?php if ($brackets !== []): ?>
            <p class="print-links">
                <a class="btn btn--ghost btn--on-dark btn--sm" href="<?= e(url('/e/' . $event['slug'] . '/druck/spinne')) ?>" target="_blank" rel="noopener">🖨 <?= e(t('Alle Turnierbäume drucken / PDF')) ?></a>
                <a class="btn btn--ghost btn--on-dark btn--sm" href="<?= e(url('/e/' . $event['slug'] . '/druck/running-order')) ?>" target="_blank" rel="noopener">🖨 <?= e(t('Running Order drucken / PDF')) ?></a>
            </p>
        <?php endif; ?>

        <?php $boutBase = url('/e/' . $event['slug'] . '/kampf/'); $feeders = \App\Core\PrintDocs::feeders($bouts); ?>
        <?php foreach ($brackets as $b): ?>
            <?php $rounds = $b['rounds']; ?>
            <div class="spinne-head" id="kat-<?= (int) $b['category']['id'] ?>">
                <h2 class="section-heading"><?= e($b['category']['name']) ?> <small class="muted"><?= e(EventRepo::categoryInfo($b['category'])) ?></small></h2>
                <a class="print-link" href="<?= e(url('/e/' . $event['slug'] . '/druck/spinne', ['kat' => $b['category']['id']])) ?>" target="_blank" rel="noopener">🖨 <?= e(t('Drucken / PDF')) ?></a>
            </div>
            <div class="spinne-scroll">
                <?php require dirname(__DIR__) . '/partials/_spinne.php'; ?>
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
