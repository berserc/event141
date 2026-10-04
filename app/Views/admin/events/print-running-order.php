<?php

use App\Models\BoutRepo;

/**
 * Running Order zum Ausdrucken: je Tag, Abschnitt und Wettkampfstaette eine
 * Seite mit den Kaempfen in Reihenfolge – fuer Kampfgericht, Aufrufer, Trainer.
 *
 * @var array<string,mixed>            $event
 * @var list<array<string,mixed>>      $days    Tage -> sessions -> venues -> bouts
 * @var array<int,array<string,int>>   $feeders
 * @var array<int,array<string,mixed>> $times
 */
$stand = t('Stand %s', date(lang() === 'en' ? 'j M Y H:i' : 'd.m.Y H:i'));
$erste = true;

// Ecke: Name + Gym, sonst "Sieger #12"
$ecke = static function (array $b, string $side) use ($feeders): string {
    $name = BoutRepo::cornerName($b, $side);

    if ($name !== '') {
        return '<strong>' . e($name) . '</strong><br><small>' . e((string) ($b[$side . '_gym'] ?? '')) . '</small>';
    }

    $nr = $feeders[(int) $b['id']][$side] ?? null;

    return '<em>' . e($nr !== null ? t('Sieger #%d', $nr) : t('offen')) . '</em>';
};
?>
<?php if ($days === []): ?>
    <h1><?= e($event['name']) ?> – Running Order</h1>
    <p class="sub"><?= e(t('Es sind noch keine Kämpfe eingeplant.')) ?></p>
<?php endif; ?>

<?php foreach ($days as $day): ?>
    <?php foreach ($day['sessions'] as $session): ?>
        <?php foreach ($session['venues'] as $venue): ?>
            <?php $anzahl = count(array_filter($venue['bouts'], static fn (array $b): bool => (int) $b['is_break'] === 0)); ?>
            <section<?= $erste ? '' : ' class="page"' ?>>
                <?php $erste = false; ?>
                <h1>Running Order – <?= e($venue['name']) ?></h1>
                <p class="sub">
                    <?= e($event['name']) ?> · <?= e(format_date_long($day['day_date'])) ?><?= (string) $day['label'] !== '' ? ' (' . e(t($day['label'])) . ')' : '' ?>
                    · <?= e(t($session['name'])) ?><?= (string) $session['starts_at'] !== '' ? ' ' . e(t('ab %s', format_time($session['starts_at']))) : '' ?>
                    · <?= e(t('%d Kämpfe', $anzahl)) ?> · <?= e(t('Zeiten sind Richtwerte')) ?> · <?= e($stand) ?>
                </p>

                <table>
                    <thead><tr>
                        <th class="num"><?= e(t('Nr.')) ?></th><th class="num"><?= e(t('ca.')) ?></th><th><?= e(t('Kategorie / Runde')) ?></th>
                        <th><?= e(t('Rote Ecke')) ?></th><th><?= e(t('Blaue Ecke')) ?></th><th><?= e(t('Sieger')) ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($venue['bouts'] as $b): ?>
                        <?php $zeit = $times[(int) $b['id']] ?? null; ?>
                        <?php if ((int) $b['is_break'] === 1): ?>
                            <tr class="break"><td colspan="6"><?= e(t($b['title'] ?: 'Pause')) ?><?= (string) $b['note'] !== '' ? ' – ' . e($b['note']) : '' ?><?= $zeit !== null ? ' · ' . e(t('ca. %s', date('H:i', $zeit['start']))) : '' ?></td></tr>
                            <?php continue; ?>
                        <?php endif; ?>
                        <tr>
                            <td class="num"><strong><?= (int) $b['bout_no'] ?></strong></td>
                            <td class="num"><?= $zeit !== null ? e(date('H:i', $zeit['start'])) : '' ?></td>
                            <td><?= e((string) ($b['category_name'] ?? '') !== '' ? $b['category_name'] : implode(' · ', array_filter([$b['style'], $b['weight_label']]))) ?>
                                <?php if ((string) $b['round_label'] !== '' || (string) $b['title'] !== ''): ?><br><small><?= e(implode(' · ', array_filter([t($b['round_label']), $b['title']]))) ?></small><?php endif; ?></td>
                            <td class="red"><?= $ecke($b, 'red') ?></td>
                            <td class="blue"><?= $ecke($b, 'blue') ?></td>
                            <td class="fill">
                                <?php if ((string) $b['status'] === 'beendet'): ?>
                                    <?= in_array((string) $b['winner'], ['red', 'blue'], true)
                                        ? e(BoutRepo::cornerName($b, (string) $b['winner']))
                                        : e(t(BoutRepo::WINNER[(string) $b['winner']] ?? '')) ?><?= (string) $b['method'] !== '' ? '<br><small>' . e(t($b['method'])) . '</small>' : '' ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endforeach; ?>
