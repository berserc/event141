<?php

/**
 * Mitglieder aus Gym141 auswaehlen und uebernehmen (Verwaltung UND Gym-Bereich).
 *
 * @var array<string,mixed>       $gym
 * @var list<array<string,mixed>> $members
 * @var string                    $error
 * @var array<int,int>            $known   gym141_member_id => athlete_id
 * @var string                    $q
 * @var string                    $backUrl
 * @var string                    $formUrl  Basis fuer /uebernehmen
 */
$connected = $gym['gym141_token'] !== '';
?>
<div class="page-head">
    <div>
        <h1>Mitglieder aus Gym141</h1>
        <p class="page-head__sub"><?= e($gym['name']) ?><?= $connected ? ' · verbunden mit ' . e($gym['gym141_club'] ?: $gym['gym141_url']) : '' ?></p>
    </div>
    <div class="page-head__actions"><a class="btn btn--ghost" href="<?= e(url($backUrl)) ?>">Zurück</a></div>
</div>

<?php if (!$connected): ?>
    <div class="notice notice--warn">Noch keine Verbindung zu Gym141 – bitte zuerst <a href="<?= e(url($backUrl)) ?>">verbinden</a>.</div>
<?php elseif ($error !== ''): ?>
    <div class="notice notice--danger">Gym141 antwortet nicht: <?= e($error) ?><br>
        <small>Ist das Token abgelaufen (z. B. nach Abmeldung in der Gym141-App), die Verbindung trennen und neu herstellen.</small></div>
<?php else: ?>
    <div class="card">
        <form method="get" class="filters">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="In Gym141 suchen (Name, Mitgliedsnummer)">
            <button class="btn btn--sm" type="submit">Suchen</button>
            <span class="muted"><?= count($members) ?> Mitglieder</span>
        </form>

        <form method="post" action="<?= e(url($formUrl . '/uebernehmen')) ?>" id="import-form">
            <?= csrf_field() ?>
            <div class="table-scroll">
                <table class="table table--compact">
                    <thead><tr><th class="col-check"><input type="checkbox" data-check-all="import-form" aria-label="alle"></th><th>Nr.</th><th>Name</th><th>Geburtsdatum</th><th>Gruppen</th><th>Status</th><th>Hier</th></tr></thead>
                    <tbody>
                    <?php foreach ($members as $m): ?>
                        <?php $mid = (int) ($m['id'] ?? 0); $bekannt = isset($known[$mid]); ?>
                        <tr class="<?= $bekannt ? 'is-muted' : '' ?>">
                            <td class="col-check"><input type="checkbox" name="member_ids[]" value="<?= $mid ?>" <?= $bekannt ? '' : '' ?>></td>
                            <td class="mono"><?= e($m['member_no'] ?? '') ?></td>
                            <td><strong><?= e(($m['last_name'] ?? '') . ' ' . ($m['first_name'] ?? '')) ?></strong></td>
                            <td><?= e(format_date($m['birthdate'] ?? null)) ?></td>
                            <td><small><?= e($m['sections'] ?? '') ?></small></td>
                            <td><small><?= e($m['status'] ?? '') ?></small></td>
                            <td><?= $bekannt ? '<span class="badge badge--ok">übernommen</span>' : '<span class="muted">–</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($members === []): ?><tr><td colspan="7" class="empty">Keine Mitglieder gefunden.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="bulkbar">
                <button class="btn btn--primary" type="submit">Ausgewählte als Sportler übernehmen</button>
                <span class="muted">Bereits übernommene werden aktualisiert (Name, Geburtsdatum, Kontakt), nicht doppelt angelegt.</span>
            </div>
        </form>
    </div>
<?php endif; ?>
