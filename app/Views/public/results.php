<?php

/**
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $finished
 * @var list<array<string,mixed>> $winners
 */
?>
<section class="event-title-bar">
    <div class="wrap">
        <p class="muted"><a href="<?= e(url('/e/' . $event['slug'])) ?>"><?= e($event['name']) ?></a></p>
        <h1>Ergebnisse</h1>
    </div>
</section>
<?php require __DIR__ . '/_event-nav.php'; ?>

<section class="wrap">
    <?php $entschieden = array_values(array_filter($winners, static fn (array $w): bool => $w['first'] !== '')); ?>
    <?php if ($entschieden !== []): ?>
        <h2 class="section-heading">Sieger</h2>
        <div class="gym-grid">
            <?php foreach ($entschieden as $w): ?>
                <div class="side-card">
                    <h3><?= e($w['category']['name']) ?></h3>
                    <p>🥇 <strong><?= e($w['first']) ?></strong><br>🥈 <?= e($w['second']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="section-heading">Alle Kämpfe</h2>
    <?php if ($finished === []): ?>
        <p class="muted">Noch keine Ergebnisse.</p>
    <?php else: ?>
        <div class="fightcard">
            <?php foreach (array_reverse($finished) as $bout): ?><?php require __DIR__ . '/_bout.php'; ?><?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
