<?php

use App\Models\EventRepo;

/**
 * Turnierbaeume ("Spinne") zum Ausdrucken: A4 quer, eine Kategorie je Seite.
 *
 * @var array<string,mixed> $event
 * @var list<array{category:array<string,mixed>,rounds:list<array{round:int,label:string,bouts:list<array<string,mixed>>}>}> $brackets
 * @var array<int,array<string,int>>   $feeders
 * @var array<int,array<string,mixed>> $times
 */
$fmt   = static fn ($v): string => rtrim(rtrim(number_format((float) $v, 1, lang() === 'en' ? '.' : ',', ''), '0'), lang() === 'en' ? '.' : ',');
$stand = t('Stand %s', date(lang() === 'en' ? 'j M Y H:i' : 'd.m.Y H:i'));
?>
<?php if ($brackets === []): ?>
    <h1><?= e($event['name']) ?></h1>
    <p class="sub"><?= e(t('Für diese Auswahl gibt es noch keinen Turnierbaum.')) ?></p>
<?php endif; ?>

<?php foreach ($brackets as $b): ?>
    <?php
    $c          = $b['category'];
    $rounds     = $b['rounds'];
    $teilnehmer = 0;

    foreach ($rounds[0]['bouts'] ?? [] as $x) {
        $teilnehmer += ($x['red_entry_id'] !== null ? 1 : 0) + ($x['blue_entry_id'] !== null ? 1 : 0);
    }
    ?>
    <section class="spinne-page">
        <h1><?= e($c['name']) ?></h1>
        <p class="sub">
            <?= e($event['name']) ?> · <?= e(format_date_range($event['starts_on'], $event['ends_on'])) ?>
            <?= EventRepo::categoryInfo($c) !== '' ? ' · ' . e(EventRepo::categoryInfo($c)) : '' ?>
            · <?= e(t('%d Teilnehmer', $teilnehmer)) ?>
            · <?= (int) $c['rounds'] ?> × <?= e($fmt($c['round_minutes'])) ?> min
            · <?= e($stand) ?>
        </p>
        <?php require dirname(__DIR__, 2) . '/partials/_spinne.php'; ?>
    </section>
<?php endforeach; ?>
