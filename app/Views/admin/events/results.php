<?php

use App\Models\BoutRepo;

/**
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $finished
 * @var list<array<string,mixed>> $open
 * @var list<array<string,mixed>> $winners
 */
$id       = (int) $event['id'];
$subtitle = 'Ergebnisse';
require __DIR__ . '/_head.php';
?>

<?php if ($winners !== []): ?>
    <div class="card">
        <div class="card__head"><h2>Sieger je Kategorie</h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th>Kategorie</th><th>1. Platz</th><th>2. Platz</th><th>Finale</th></tr></thead>
                <tbody>
                <?php foreach ($winners as $w): ?>
                    <tr>
                        <td class="strong"><?= e($w['category']['name']) ?></td>
                        <td><?= $w['first'] !== '' ? '🥇 ' . e($w['first']) : '<span class="muted">offen</span>' ?></td>
                        <td><?= $w['second'] !== '' ? '🥈 ' . e($w['second']) : '' ?></td>
                        <td><?php if ($w['final'] !== null): ?><a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $w['final']['id'])) ?>">Kampf #<?= (int) $w['final']['bout_no'] ?></a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="form-grid form-grid--wide">
    <div class="card">
        <div class="card__head"><h2>Beendete Kämpfe <span class="badge"><?= count($finished) ?></span></h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th>#</th><th>Kampf</th><th>Rot</th><th>Blau</th><th>Ergebnis</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($finished as $b): ?>
                    <tr>
                        <td class="num mono">#<?= (int) $b['bout_no'] ?></td>
                        <td><small><?= e($b['category_name'] ?? $b['title']) ?><?= $b['round_label'] !== '' ? ' · ' . e($b['round_label']) : '' ?></small></td>
                        <td class="corner corner--red<?= $b['winner'] === 'red' ? ' is-winner' : '' ?>"><?= e(BoutRepo::cornerName($b, 'red')) ?></td>
                        <td class="corner corner--blue<?= $b['winner'] === 'blue' ? ' is-winner' : '' ?>"><?= e(BoutRepo::cornerName($b, 'blue')) ?></td>
                        <td><?= e(BoutRepo::WINNER[$b['winner']] ?? '') ?><?= $b['method'] !== '' ? ' · ' . e($b['method']) : '' ?><?= $b['result_note'] !== '' ? ' ' . e($b['result_note']) : '' ?></td>
                        <td class="row-actions"><a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $b['id'])) ?>">Öffnen</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($finished === []): ?><tr><td colspan="6" class="empty">Noch kein Ergebnis eingetragen.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card__head"><h2>Offene Kämpfe <span class="badge"><?= count($open) ?></span></h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th>#</th><th>Kampf</th><th>Rot</th><th>Blau</th><th>Ring</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($open as $b): ?>
                    <tr class="bout-row bout-row--<?= e($b['status']) ?>">
                        <td class="num mono">#<?= (int) $b['bout_no'] ?></td>
                        <td><small><?= e($b['category_name'] ?? $b['title']) ?><?= $b['round_label'] !== '' ? ' · ' . e($b['round_label']) : '' ?></small></td>
                        <td class="corner corner--red"><?= e(BoutRepo::cornerName($b, 'red')) ?: '<span class="muted">offen</span>' ?></td>
                        <td class="corner corner--blue"><?= e(BoutRepo::cornerName($b, 'blue')) ?: '<span class="muted">offen</span>' ?></td>
                        <td><small><?= e($b['venue_name'] ?? '–') ?></small></td>
                        <td class="row-actions"><a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $b['id'])) ?>">Ergebnis</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($open === []): ?><tr><td colspan="6" class="empty">Alle Kämpfe sind beendet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
