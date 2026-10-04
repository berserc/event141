<?php

use App\Core\Auth;

/**
 * Tab „Galerie & Bericht“: Event-Bericht + Galerien des Events.
 *
 * @var array<string,mixed>        $event
 * @var list<array<string,mixed>> $galleries
 * @var list<int>                  $reportImages
 * @var list<array<string,mixed>> $images
 * @var int                        $imageCount
 * @var array<int,int>             $boutCounts
 */
$id       = (int) $event['id'];
$subtitle = t('Galerie & Bericht');
$canWrite = Auth::canWrite();
require __DIR__ . '/_head.php';
?>
<?php if ($imageCount === 0): ?>
    <div class="notice notice--warn"><?= t('Noch keine Bilder in der Bildbibliothek – zuerst unter <a href="%s">Medien</a> hochladen (mit Tags, z. B. „%s“).', e(url('/admin/medien')), e(mb_strtolower((string) ($event['short_name'] ?: $event['name']), 'UTF-8'))) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/events/' . $id . '/bericht')) ?>" class="card form" id="bericht">
    <?= csrf_field() ?>
    <div class="card__head"><h2><?= e(t('Event-Bericht')) ?></h2></div>
    <p class="muted"><?= t('Erscheint nach dem Event auf der Event-Seite (Teaser) und unter <a href="%s" target="_blank" rel="noopener">%s ↗</a>. Leerzeile = Absatz, „## “ am Zeilenanfang = Zwischenüberschrift.', e(url('/e/' . $event['slug'] . '/bericht')), e('/e/' . $event['slug'] . '/bericht')) ?></p>
    <div class="field"><label for="report_title"><?= e(t('Überschrift')) ?></label><input id="report_title" name="report_title" value="<?= e((string) ($event['report_title'] ?? '')) ?>" placeholder="<?= e(t('z. B. Das war die Fight Night 3')) ?>" <?= $canWrite ? '' : 'disabled' ?>></div>
    <div class="field"><label for="report_text"><?= e(t('Text')) ?></label><textarea id="report_text" name="report_text" rows="12" <?= $canWrite ? '' : 'disabled' ?>><?= e((string) ($event['report_text'] ?? '')) ?></textarea></div>
    <label class="check"><input type="checkbox" name="report_published" value="1" <?= (int) ($event['report_published'] ?? 0) === 1 ? 'checked' : '' ?> <?= $canWrite ? '' : 'disabled' ?>> <?= e(t('Bericht auf der Website anzeigen')) ?></label>
    <details <?= (int) ($event['report_cover_id'] ?? 0) > 0 ? '' : 'open' ?>>
        <summary class="linklike"><?= e(t('Titelbild (ein Bild – das gewählte gilt)')) ?></summary>
        <?php $pickerField = 'report_cover'; $pickerSelected = (int) ($event['report_cover_id'] ?? 0) > 0 ? [(int) $event['report_cover_id']] : []; $pickerImages = $images; $pickerSingle = true;
        require dirname(__DIR__) . '/partials/_image-picker.php'; ?>
    </details>
    <details open>
        <summary class="linklike"><?= e(t('Bilder zum Bericht (%d)', count($reportImages))) ?></summary>
        <?php $pickerField = 'report_images'; $pickerSelected = $reportImages; $pickerImages = $images; $pickerSingle = false;
        require dirname(__DIR__) . '/partials/_image-picker.php'; ?>
    </details>
    <?php if ($canWrite): ?><div class="form-actions"><button class="btn btn--primary" type="submit"><?= e(t('Bericht speichern')) ?></button></div><?php endif; ?>
</form>

<div class="card" id="galerien">
    <div class="card__head">
        <h2><?= e(t('Galerien (%d)', count($galleries))) ?></h2>
        <?php if ($canWrite): ?><a class="btn btn--primary" href="<?= e(url('/admin/events/' . $id . '/galerie/neu')) ?>"><?= e(t('Neue Galerie')) ?></a><?php endif; ?>
    </div>
    <p class="muted"><?= t('Bildersammlungen mit eigener Seite, z. B. „Impressionen“, „Backstage“, „Publikum“. Übersicht unter <a href="%s" target="_blank" rel="noopener">%s ↗</a>; der Reiter „Galerie“ erscheint auf der Event-Seite, sobald eine Galerie sichtbar ist.', e(url('/e/' . $event['slug'] . '/galerie')), e('/e/' . $event['slug'] . '/galerie')) ?></p>
    <?php if ($galleries === []): ?>
        <p class="empty"><?= e(t('Noch keine Galerie.')) ?></p>
    <?php else: ?>
        <div class="table-scroll"><table class="table">
            <thead><tr><th class="col-thumb"></th><th><?= e(t('Titel')) ?></th><th class="num"><?= e(t('Bilder')) ?></th><th><?= e(t('Sichtbar')) ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($galleries as $g): ?>
                <tr>
                    <td class="col-thumb"><?php if (!empty($g['cover_thumb'])): ?><img src="<?= e(upload_url((string) $g['cover_thumb'])) ?>" alt="" style="width:56px;height:42px;object-fit:cover;border-radius:4px;"><?php endif; ?></td>
                    <td><a class="strong" href="<?= e(url('/admin/events/' . $id . '/galerie/' . (int) $g['id'])) ?>"><?= e((string) $g['title']) ?></a><br><a class="muted" href="<?= e(url('/e/' . $event['slug'] . '/galerie/' . $g['slug'])) ?>" target="_blank" rel="noopener">/galerie/<?= e((string) $g['slug']) ?> ↗</a></td>
                    <td class="num"><?= (int) $g['image_count'] ?></td>
                    <td><?= (int) $g['published'] === 1 ? '<span class="pill pill--aktiv">' . e(t('ja')) . '</span>' : '<span class="pill pill--offen">' . e(t('nein')) . '</span>' ?></td>
                    <td class="row-actions">
                        <?php if ($canWrite): ?>
                            <form method="post" action="<?= e(url('/admin/events/' . $id . '/galerie/' . (int) $g['id'] . '/umschalten')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--sm" type="submit"><?= (int) $g['published'] === 1 ? e(t('Verstecken')) : e(t('Sichtbar machen')) ?></button></form>
                            <form method="post" action="<?= e(url('/admin/events/' . $id . '/galerie/' . (int) $g['id'] . '/loeschen')) ?>" class="inline" data-confirm="<?= e(t('Galerie „%s“ löschen? Die Bilder bleiben in der Bildbibliothek.', (string) $g['title'])) ?>"><?= csrf_field() ?><button class="btn btn--sm btn--danger" type="submit"><?= e(t('Löschen')) ?></button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card__head"><h2><?= e(t('Bilder je Kampf')) ?></h2></div>
    <p class="muted"><?= t('Bilder und ein Nachwort („Nach dem Kampf“) trägst du direkt beim jeweiligen Kampf ein (<a href="%s">Fightcard</a> → Kampf öffnen).', e(url('/admin/events/' . $id . '/kaempfe'))) ?>
        <?php if ($boutCounts !== []): ?><?= e(t('Bisher mit Bildern: %d Kämpfe, %d Bilder.', count($boutCounts), array_sum($boutCounts))) ?><?php else: ?><?= e(t('Noch kein Kampf mit Bildern.')) ?><?php endif; ?></p>
</div>
