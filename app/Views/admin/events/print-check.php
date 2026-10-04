<?php

/**
 * Kaempfer-Checkliste: Name, Klasse, Gewicht Waage, Musik, aerztliche
 * Untersuchung – chronologisch nach Kampf (wer zuerst kaempft, steht oben).
 *
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $bouts
 * @var list<array<string,mixed>> $entries
 */
$byId = [];
foreach ($entries as $x) {
    $byId[(int) $x['id']] = $x;
}

$rows = [];
$seen = [];

foreach ($bouts as $b) {
    foreach (['red', 'blue'] as $side) {
        $eid = (int) ($b[$side . '_entry_id'] ?? 0);

        if ($eid > 0 && isset($byId[$eid]) && !isset($seen[$eid])) {
            $seen[$eid] = true;
            $rows[]     = $byId[$eid] + ['_bout' => (int) $b['bout_no'], '_class' => $b['weight_label'] ?: ($b['category_name'] ?? '')];
        }
    }
}

// Angemeldete ohne Kampf ans Ende.
foreach ($entries as $x) {
    if (!isset($seen[(int) $x['id']])) {
        $rows[] = $x + ['_bout' => 0, '_class' => $x['category_name'] ?? ''];
    }
}
?>
<h1><?= e($event['name']) ?> – <?= e(t('Kämpfer-Check')) ?></h1>
<p class="sub"><?= e(format_date_long($event['starts_on'])) ?> · <?= e(t('%d Sportler', count($rows))) ?> · <?= e(t('Stand %s', date(lang() === 'en' ? 'j M Y H:i' : 'd.m.Y H:i'))) ?></p>

<table>
    <thead><tr><th class="num"><?= e(t('Kampf')) ?></th><th><?= e(t('Name')) ?></th><th><?= e(t('Gym')) ?></th><th><?= e(t('Klasse')) ?></th><th><?= e(t('Gewicht Waage (kg)')) ?></th><th><?= e(t('Musik')) ?></th><th class="num"><?= e(t('Ärztl. Unters.')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td class="num"><?= $r['_bout'] > 0 ? (int) $r['_bout'] : '–' ?></td>
            <td><strong><?= e($r['last_name']) ?></strong> <?= e($r['first_name']) ?></td>
            <td><small><?= e($r['gym_name']) ?></small></td>
            <td><small><?= e($r['_class']) ?></small></td>
            <td class="fill num"><?= $r['weighed'] !== null ? e(number_format((float) $r['weighed'], 1, ',', '')) : '' ?></td>
            <td><?= $r['music'] !== '' ? '<small>' . e($r['music']) . '</small>' : '<small style="color:#c8102e">' . e(t('fehlt')) . '</small>' ?></td>
            <td class="fill num"><span class="box<?= (int) $r['medical_ok'] === 1 ? ' on' : '' ?>"></span></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
