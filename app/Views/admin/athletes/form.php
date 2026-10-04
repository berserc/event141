<?php

use App\Core\Auth;
use App\Models\EntryRepo;

/**
 * @var array<string,mixed>       $athlete
 * @var list<array<string,mixed>> $gyms
 * @var list<array<string,mixed>> $entries
 * @var array<string,string>      $errors
 * @var bool                      $isNew
 * @var string                    $backUrl
 * @var string                    $formUrl
 */
$id       = (int) ($athlete['id'] ?? 0);
$canWrite = Auth::canWrite();
?>
<div class="page-head">
    <div>
        <h1><?= $isNew ? e(t('Neuer Sportler')) : e(person_name($athlete)) ?></h1>
        <?php if (!$isNew): ?><p class="page-head__sub"><a href="<?= e(url('/admin/gyms/' . $athlete['gym_id'])) ?>"><?= e($athlete['gym_name']) ?></a></p><?php endif; ?>
    </div>
    <div class="page-head__actions"><a class="btn btn--ghost" href="<?= e(url($backUrl)) ?>"><?= e(t('Zur Liste')) ?></a></div>
</div>

<form method="post" action="<?= e(url($formUrl)) ?>" enctype="multipart/form-data" class="form">
    <?= csrf_field() ?>
    <fieldset class="card">
        <legend><?= e(t('Stammdaten')) ?></legend>
        <div class="field">
            <label for="gym_id"><?= e(t('Gym / Verein')) ?> *</label>
            <select id="gym_id" name="gym_id" required <?= $canWrite ? '' : 'disabled' ?>>
                <option value=""><?= e(t('– wählen –')) ?></option>
                <?php foreach ($gyms as $g): ?>
                    <option value="<?= (int) $g['id'] ?>" <?= (int) $athlete['gym_id'] === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?><?= $g['city'] !== '' ? ' (' . e($g['city']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['gym_id'])): ?><p class="field__error"><?= e($errors['gym_id']) ?></p><?php endif; ?>
        </div>
        <?php $ro = !$canWrite; require __DIR__ . '/_fields.php'; ?>
    </fieldset>

    <?php if ($canWrite): ?>
        <div class="form-actions">
            <button class="btn btn--primary" type="submit"><?= e($isNew ? t('Sportler anlegen') : t('Speichern')) ?></button>
        </div>
    <?php endif; ?>
</form>

<?php if (!$isNew): ?>
    <div class="card">
        <div class="card__head"><h2><?= e(t('Anmeldungen')) ?></h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th><?= e(t('Event')) ?></th><th><?= e(t('Datum')) ?></th><th><?= e(t('Kategorie')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Wiegen')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($entries as $x): ?>
                    <tr>
                        <td><a href="<?= e(url('/admin/events/' . $x['event_id'] . '/anmeldungen')) ?>"><?= e($x['event_name']) ?></a></td>
                        <td><?= e(format_date($x['starts_on'])) ?></td>
                        <td><?= e($x['category_name'] ?? '–') ?></td>
                        <td><span class="pill pill--entry-<?= e($x['status']) ?>"><?= e(t(EntryRepo::STATUS[$x['status']] ?? $x['status'])) ?></span></td>
                        <td><?= e(format_weight($x['weighed'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($entries === []): ?><tr><td colspan="5" class="empty"><?= e(t('Noch keine Anmeldungen.')) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($canWrite): ?>
        <form method="post" action="<?= e(url('/admin/sportler/' . $id . '/loeschen')) ?>" class="inline" data-confirm="<?= e(t('Sportler löschen?')) ?>">
            <?= csrf_field() ?>
            <button class="linklike linklike--danger" type="submit"><?= e(t('Sportler löschen')) ?></button>
        </form>
    <?php endif; ?>
<?php endif; ?>
