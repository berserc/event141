<?php

/**
 * @var array<string,mixed>                          $event
 * @var array<string,list<array<string,mixed>>>      $byGym
 * @var int                                          $total
 */
?>
<section class="event-title-bar">
    <div class="wrap">
        <p class="muted"><a href="<?= e(url('/e/' . $event['slug'])) ?>"><?= e($event['name']) ?></a></p>
        <h1><?= e(t('Teilnehmer')) ?> <small class="muted"><?= (int) $total ?></small></h1>
    </div>
</section>
<?php require __DIR__ . '/_event-nav.php'; ?>

<section class="wrap">
    <?php if ($byGym === []): ?>
        <p class="muted"><?= e(t('Noch keine bestätigten Teilnehmer.')) ?></p>
    <?php endif; ?>
    <div class="gym-grid">
        <?php foreach ($byGym as $gymName => $rows): ?>
            <div class="side-card">
                <h3><?= e($gymName) ?> <small class="muted"><?= e($rows[0]['gym_city']) ?></small></h3>
                <ul class="plain-list">
                    <?php foreach ($rows as $r): ?>
                        <li><?= e($r['first_name'] . ' ' . $r['last_name']) ?><?= $r['category_name'] !== null ? ' <small class="muted">' . e($r['category_name']) . '</small>' : '' ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</section>
