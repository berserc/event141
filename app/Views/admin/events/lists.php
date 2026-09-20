<?php

use App\Core\Auth;

/**
 * Listen & Druck: Checkliste (Waage, Musik, Arzt, Kabine) + Druckansichten.
 *
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $entries  bestaetigte Anmeldungen
 * @var list<string>              $conflicts
 */
$id       = (int) $event['id'];
$subtitle = 'Listen & Druck';
$canWrite = Auth::canWrite();
require __DIR__ . '/_head.php';

$kabinen = array_values(array_unique(array_filter(array_map(static fn (array $x): string => (string) $x['cabin'], $entries))));
sort($kabinen);
?>
<div class="stat-grid stat-grid--compact">
    <a class="stat" href="<?= e(url('/admin/events/' . $id . '/druck/aushang')) ?>" target="_blank" rel="noopener">
        <span class="stat__value">🖨</span><span class="stat__label">Fightcard-Aushang (Kabinen, Kampfgericht)</span>
    </a>
    <a class="stat" href="<?= e(url('/admin/events/' . $id . '/druck/check')) ?>" target="_blank" rel="noopener">
        <span class="stat__value">☑</span><span class="stat__label">Kämpfer-Checkliste (Waage, Musik, Arzt)</span>
    </a>
    <a class="stat <?= $conflicts !== [] ? 'stat--danger' : '' ?>" href="<?= e(url('/admin/events/' . $id . '/druck/kabinen')) ?>" target="_blank" rel="noopener">
        <span class="stat__value">🚪</span><span class="stat__label">Kabineneinteilung + Türschilder</span>
    </a>
    <a class="stat" href="<?= e(url('/admin/events/' . $id . '/anmeldungen.csv')) ?>">
        <span class="stat__value">⬇</span><span class="stat__label">Teilnehmer als CSV (Excel)</span>
    </a>
</div>
<p class="muted">Die Druckansichten öffnen in einem neuen Tab – dort mit Strg+P drucken oder als PDF speichern.</p>

<?php foreach ($conflicts as $c): ?>
    <div class="notice notice--danger"><strong>Kabinen-Konflikt:</strong> <?= e($c) ?></div>
<?php endforeach; ?>

<form method="post" action="<?= e(url('/admin/events/' . $id . '/listen')) ?>" class="card">
    <?= csrf_field() ?>
    <div class="card__head">
        <h2>Checkliste</h2>
        <?php if ($canWrite): ?><button class="btn btn--primary btn--sm" type="submit">Checkliste speichern</button><?php endif; ?>
    </div>
    <datalist id="kabinen"><?php foreach ($kabinen as $k): ?><option value="<?= e($k) ?>"></option><?php endforeach; ?></datalist>

    <div class="table-scroll">
        <table class="table table--compact">
            <thead><tr><th>Sportler</th><th>Gym</th><th>Klasse</th><th>Gewicht Waage (kg)</th><th>Einlaufmusik</th><th>Ärztl. Unters.</th><th>Kabine</th></tr></thead>
            <tbody>
            <?php foreach ($entries as $x): ?>
                <tr>
                    <td class="strong"><?= e($x['last_name']) ?> <?= e($x['first_name']) ?></td>
                    <td><?= e($x['gym_name']) ?></td>
                    <td><small><?= e($x['category_name'] ?? '') ?></small></td>
                    <td><input name="rows[<?= (int) $x['id'] ?>][weighed]" class="input--xs" inputmode="decimal" value="<?= $x['weighed'] !== null ? e(number_format((float) $x['weighed'], 1, ',', '')) : '' ?>" <?= $canWrite ? '' : 'disabled' ?>></td>
                    <td><input name="rows[<?= (int) $x['id'] ?>][music]" value="<?= e($x['music']) ?>" placeholder="Titel / Datei" <?= $canWrite ? '' : 'disabled' ?>></td>
                    <td><input type="checkbox" name="rows[<?= (int) $x['id'] ?>][medical_ok]" value="1" <?= (int) $x['medical_ok'] === 1 ? 'checked' : '' ?> <?= $canWrite ? '' : 'disabled' ?>></td>
                    <td><input name="rows[<?= (int) $x['id'] ?>][cabin]" list="kabinen" class="input--time" value="<?= e($x['cabin']) ?>" placeholder="z. B. 1" <?= $canWrite ? '' : 'disabled' ?>></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($entries === []): ?><tr><td colspan="7" class="empty">Noch keine bestätigten Anmeldungen.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="field__hint">Kabinen-Regel: Gegner dürfen nicht in derselben Kabine sein – Konflikte werden oben rot gemeldet. Gyms am besten zusammenlassen.</p>
</form>
