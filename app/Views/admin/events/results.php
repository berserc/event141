<?php

use App\Models\BoutRepo;

/**
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $finished
 * @var list<array<string,mixed>> $open
 * @var list<array<string,mixed>> $winners
 */
$id       = (int) $event['id'];
$subtitle = t('Ergebnisse');
require __DIR__ . '/_head.php';
?>

<?php if ($winners !== []): ?>
    <div class="card">
        <div class="card__head"><h2><?= e(t('Sieger je Kategorie')) ?></h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th><?= e(t('Kategorie')) ?></th><th><?= e(t('1. Platz')) ?></th><th><?= e(t('2. Platz')) ?></th><th><?= e(t('Finale')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($winners as $w): ?>
                    <tr>
                        <td class="strong"><?= e($w['category']['name']) ?></td>
                        <td><?= $w['first'] !== '' ? '🥇 ' . e($w['first']) : '<span class="muted">' . e(t('offen')) . '</span>' ?></td>
                        <td><?= $w['second'] !== '' ? '🥈 ' . e($w['second']) : '' ?></td>
                        <td><?php if ($w['final'] !== null): ?><a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $w['final']['id'])) ?>"><?= e(t('Kampf #%d', (int) $w['final']['bout_no'])) ?></a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="form-grid form-grid--wide">
    <div class="card">
        <div class="card__head"><h2><?= e(t('Beendete Kämpfe')) ?> <span class="badge"><?= count($finished) ?></span></h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th>#</th><th><?= e(t('Kampf')) ?></th><th><?= e(t('Rot')) ?></th><th><?= e(t('Blau')) ?></th><th><?= e(t('Ergebnis')) ?></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($finished as $b): ?>
                    <tr>
                        <td class="num mono">#<?= (int) $b['bout_no'] ?></td>
                        <td><small><?= e($b['category_name'] ?? $b['title']) ?><?= $b['round_label'] !== '' ? ' · ' . e(t($b['round_label'])) : '' ?></small></td>
                        <td class="corner corner--red<?= $b['winner'] === 'red' ? ' is-winner' : '' ?>"><?= e(BoutRepo::cornerName($b, 'red')) ?></td>
                        <td class="corner corner--blue<?= $b['winner'] === 'blue' ? ' is-winner' : '' ?>"><?= e(BoutRepo::cornerName($b, 'blue')) ?></td>
                        <td><?= e(t(BoutRepo::WINNER[$b['winner']] ?? '')) ?><?= $b['method'] !== '' ? ' · ' . e(t($b['method'])) : '' ?><?= $b['result_note'] !== '' ? ' ' . e($b['result_note']) : '' ?></td>
                        <td class="row-actions"><a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $b['id'])) ?>"><?= e(t('Öffnen')) ?></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($finished === []): ?><tr><td colspan="6" class="empty"><?= e(t('Noch kein Ergebnis eingetragen.')) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card__head"><h2><?= e(t('Offene Kämpfe')) ?> <span class="badge"><?= count($open) ?></span></h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th>#</th><th><?= e(t('Kampf')) ?></th><th><?= e(t('Rot')) ?></th><th><?= e(t('Blau')) ?></th><th><?= e(t('Ring')) ?></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($open as $b): ?>
                    <tr class="bout-row bout-row--<?= e($b['status']) ?>">
                        <td class="num mono">#<?= (int) $b['bout_no'] ?></td>
                        <td><small><?= e($b['category_name'] ?? $b['title']) ?><?= $b['round_label'] !== '' ? ' · ' . e(t($b['round_label'])) : '' ?></small></td>
                        <td class="corner corner--red"><?= e(BoutRepo::cornerName($b, 'red')) ?: '<span class="muted">' . e(t('offen')) . '</span>' ?></td>
                        <td class="corner corner--blue"><?= e(BoutRepo::cornerName($b, 'blue')) ?: '<span class="muted">' . e(t('offen')) . '</span>' ?></td>
                        <td><small><?= e($b['venue_name'] ?? '–') ?></small></td>
                        <td class="row-actions"><a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $b['id'])) ?>"><?= e(t('Ergebnis')) ?></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($open === []): ?><tr><td colspan="6" class="empty"><?= e(t('Alle Kämpfe sind beendet.')) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
