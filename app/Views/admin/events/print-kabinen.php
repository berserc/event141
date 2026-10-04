<?php

/**
 * Kabineneinteilung: Seite 1 Uebersicht (inkl. Konflikt-Warnung), danach je
 * Kabine ein Tuerschild.
 *
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $entries
 * @var list<string>              $conflicts
 */
$cabins = [];
$ohne   = [];

foreach ($entries as $x) {
    if ((string) $x['cabin'] === '') {
        $ohne[] = $x;
        continue;
    }

    $cabins[(string) $x['cabin']][] = $x;
}

ksort($cabins, SORT_NATURAL);
?>
<h1><?= e($event['name']) ?> – <?= e(t('Kabinen')) ?></h1>
<p class="sub"><?= e(format_date_long($event['starts_on'])) ?> · <?= e(t('Stand %s', date(lang() === 'en' ? 'j M Y H:i' : 'd.m.Y H:i'))) ?></p>

<?php foreach ($conflicts as $c): ?><div class="warn">⚠ <?= e($c) ?></div><?php endforeach; ?>
<?php if ($ohne !== []): ?><div class="warn"><?= e(t('Ohne Kabine: %s', implode(', ', array_map(static fn (array $x): string => $x['first_name'] . ' ' . $x['last_name'], $ohne)))) ?></div><?php endif; ?>

<?php if ($cabins === []): ?>
    <p><?= e(t('Noch keine Kabinen vergeben – unter „Listen & Druck“ in der Checkliste je Sportler eine Kabine eintragen.')) ?></p>
<?php endif; ?>

<table>
    <thead><tr><th><?= e(t('Kabine')) ?></th><th><?= e(t('Gyms')) ?></th><th><?= e(t('Sportler')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($cabins as $name => $list): ?>
        <tr>
            <td><strong><?= e($name) ?></strong></td>
            <td><?= e(implode(', ', array_unique(array_column($list, 'gym_name')))) ?></td>
            <td><?= e(implode(', ', array_map(static fn (array $x): string => $x['first_name'] . ' ' . $x['last_name'], $list))) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php foreach ($cabins as $name => $list): ?>
    <div class="page door">
        <p class="sub"><?= e($event['name']) ?></p>
        <h1><?= e(t('Kabine %s', $name)) ?></h1>
        <?php $byGym = []; foreach ($list as $x) { $byGym[(string) $x['gym_name']][] = $x; } ?>
        <?php foreach ($byGym as $gym => $people): ?>
            <h2><?= e($gym) ?></h2>
            <ul><?php foreach ($people as $x): ?><li><?= e($x['first_name'] . ' ' . $x['last_name']) ?></li><?php endforeach; ?></ul>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
