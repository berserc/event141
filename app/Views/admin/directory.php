<?php

use App\Core\Directory;

/**
 * Verwaltung des zentralen Verzeichnisses: gemeldete Events aller Instanzen.
 *
 * @var list<array<string,mixed>> $rows
 */
$wartend = count(array_filter($rows, static fn (array $r): bool => (string) $r['state'] === 'wartet'));
$knopf   = static function (int $id, string $aktion, string $label, string $klasse = '', string $frage = ''): string {
    return '<form method="post" action="' . e(url('/admin/verzeichnis/' . $id)) . '" class="inline"' . ($frage !== '' ? ' data-confirm="' . e($frage) . '"' : '') . '>'
        . csrf_field() . '<input type="hidden" name="aktion" value="' . e($aktion) . '">'
        . '<button class="linklike ' . e($klasse) . '" type="submit">' . e($label) . '</button></form>';
};
?>
<div class="page-head">
    <div>
        <h1><?= e(t('Verzeichnis')) ?></h1>
        <p class="page-head__sub"><?= e(t('Events, die Instanzen an dieses Verzeichnis gemeldet haben. Die Daten werden bei der Instanz abgeholt – hier wird nur freigegeben oder versteckt.')) ?></p>
    </div>
    <div class="page-head__actions">
        <form method="post" action="<?= e(url('/admin/verzeichnis/aktualisieren')) ?>" class="inline">
            <?= csrf_field() ?>
            <button class="btn btn--primary" type="submit"><?= e(t('Alle neu abholen')) ?></button>
        </form>
    </div>
</div>

<?php if ($wartend > 0): ?>
    <div class="notice notice--warn"><?= e(t('%d Einträge warten auf Freigabe (Instanzen außerhalb der vertrauten Domains).', $wartend)) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-scroll">
        <table class="table">
            <thead><tr>
                <th><?= e(t('Datum')) ?></th><th><?= e(t('Event')) ?></th><th><?= e(t('Sportart')) ?> / <?= e(t('Verband')) ?></th>
                <th><?= e(t('Ort')) ?></th><th><?= e(t('Instanz')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Abgeholt')) ?></th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td style="white-space:nowrap"><?= e(format_date_range($r['starts_on'], $r['ends_on'])) ?></td>
                    <td><a class="strong" href="<?= e($r['url']) ?>" target="_blank" rel="noopener"><?= e($r['name']) ?></a><br><small class="muted"><?= e($r['org_name']) ?></small></td>
                    <td><?= e($r['sport']) ?><?= (string) $r['federation'] !== '' ? '<br><small class="muted">' . e($r['federation']) . '</small>' : '' ?></td>
                    <td><?= e(flag_emoji((string) $r['country'])) ?> <?= e(implode(', ', array_filter([$r['city'], $r['region']]))) ?></td>
                    <td><small><?= e($r['host']) ?></small><?= Directory::trusted((string) $r['host']) ? '' : '<br><small class="muted">' . e(t('fremde Domain')) . '</small>' ?></td>
                    <td><span class="pill pill--<?= (string) $r['state'] === 'sichtbar' ? 'aktiv' : ((string) $r['state'] === 'wartet' ? 'offen' : 'inaktiv') ?>"><?= e(t(Directory::STATES[(string) $r['state']] ?? (string) $r['state'])) ?></span></td>
                    <td style="white-space:nowrap"><small><?= e(format_datetime($r['fetched_at'])) ?></small></td>
                    <td class="row-actions">
                        <?php if ((string) $r['state'] !== 'sichtbar'): ?><?= $knopf((int) $r['id'], 'freigeben', t('Freigeben')) ?><?php endif; ?>
                        <?php if ((string) $r['state'] === 'wartet'): ?><?= $knopf((int) $r['id'], 'host-freigeben', t('Alle dieser Instanz')) ?><?php endif; ?>
                        <?php if ((string) $r['state'] === 'sichtbar'): ?><?= $knopf((int) $r['id'], 'verstecken', t('Verstecken')) ?><?php endif; ?>
                        <?= $knopf((int) $r['id'], 'abholen', t('Neu abholen')) ?>
                        <?= $knopf((int) $r['id'], 'entfernen', t('Entfernen'), 'linklike--danger', t('„%s“ aus dem Verzeichnis entfernen?', (string) $r['name'])) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr><td colspan="8" class="empty"><?= e(t('Noch kein Event gemeldet. Instanzen melden ein Event, sobald dort im Event-Formular „Im Event141-Verzeichnis listen“ angehakt ist.')) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
