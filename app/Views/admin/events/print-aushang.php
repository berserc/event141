<?php

use App\Models\BoutRepo;

/**
 * Fightcard-Aushang fuer Kabinen und Kampfgericht: chronologisch mit Pausen,
 * ca.-Zeiten, Ecken-Farben, Disziplin/Gewicht/Runden.
 *
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $bouts chronologisch, nur aktive
 * @var array<int,array<string,mixed>> $times
 */
$fmt = static fn ($v): string => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
$ring = count(array_unique(array_filter(array_column($bouts, 'venue_name')))) > 1;
?>
<h1><?= e($event['name']) ?> – Fightcard</h1>
<p class="sub"><?= e(format_date_long($event['starts_on'])) ?><?= $event['venue_name'] !== '' ? ' · ' . e($event['venue_name']) : '' ?>
    <?= $event['start_time'] !== '' ? ' · Beginn ' . e($event['start_time']) . ' Uhr' : '' ?> · Zeiten sind Richtwerte · Stand <?= e(date('d.m.Y H:i')) ?></p>

<table>
    <thead><tr><th class="num">Nr.</th><th class="num">ca.</th><?php if ($ring): ?><th>Ring</th><?php endif; ?><th>Rote Ecke</th><th>Blaue Ecke</th><th>Disziplin / Klasse</th><th class="num">Runden</th></tr></thead>
    <tbody>
    <?php foreach ($bouts as $b): ?>
        <?php $t = $times[(int) $b['id']] ?? null; ?>
        <?php if ((int) $b['is_break'] === 1): ?>
            <tr class="break"><td colspan="<?= $ring ? 7 : 6 ?>"><?= e($b['title'] ?: 'Pause') ?><?= $b['note'] !== '' ? ' – ' . e($b['note']) : '' ?><?= $t !== null ? ' · ca. ' . e(date('H:i', $t['start'])) : '' ?></td></tr>
            <?php continue; ?>
        <?php endif; ?>
        <tr>
            <td class="num"><strong><?= (int) $b['bout_no'] ?></strong></td>
            <td class="num"><?= $t !== null ? e(date('H:i', $t['start'])) : '' ?></td>
            <?php if ($ring): ?><td><?= e($b['venue_short'] ?: ($b['venue_name'] ?? '')) ?></td><?php endif; ?>
            <td class="red"><strong><?= e(BoutRepo::cornerName($b, 'red') ?: 'offen') ?></strong><br><small><?= e($b['red_gym'] ?? '') ?></small></td>
            <td class="blue"><strong><?= e(BoutRepo::cornerName($b, 'blue') ?: 'offen') ?></strong><br><small><?= e($b['blue_gym'] ?? '') ?></small></td>
            <td><?= e(implode(' · ', array_filter([$b['style'], $b['weight_label'] ?: ($b['category_name'] ?? ''), $b['round_label']]))) ?><?= $b['title'] !== '' ? '<br><small>' . e($b['title']) . '</small>' : '' ?></td>
            <td class="num"><?= (int) $b['rounds'] ?> × <?= e($fmt($b['round_minutes'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
