<?php

use App\Core\Auth;

/**
 * @var array<string,mixed>        $event
 * @var array<string,mixed>        $gallery
 * @var bool                       $isNew
 * @var array<string,string>       $errors
 * @var list<int>                  $selected
 * @var list<array<string,mixed>> $images
 */
$id       = (int) $event['id'];
$gid      = (int) ($gallery['id'] ?? 0);
$subtitle = $isNew ? 'Neue Galerie' : 'Galerie: ' . $gallery['title'];
$canWrite = Auth::canWrite();
$action   = $isNew ? url('/admin/events/' . $id . '/galerie') : url('/admin/events/' . $id . '/galerie/' . $gid);
$err      = static fn (string $f): string => isset($errors[$f]) ? '<p class="field__error">' . e($errors[$f]) . '</p>' : '';
require __DIR__ . '/_head.php';
?>
<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card__head"><h2><?= $isNew ? 'Neue Galerie' : e((string) $gallery['title']) ?></h2>
            <a class="btn btn--ghost" href="<?= e(url('/admin/events/' . $id . '/galerie')) ?>">Zurück</a></div>
        <div class="field-row">
            <div class="field field--grow"><label for="title">Titel *</label><input id="title" name="title" required value="<?= e((string) $gallery['title']) ?>" <?= $canWrite ? '' : 'disabled' ?>><?= $err('title') ?></div>
            <div class="field field--grow"><label for="slug">URL-Kürzel</label><input id="slug" name="slug" value="<?= e((string) $gallery['slug']) ?>" placeholder="wird aus dem Titel gebildet" <?= $canWrite ? '' : 'disabled' ?>></div>
            <div class="field field--xs"><label for="sort_order">Reihung</label><input id="sort_order" name="sort_order" type="number" value="<?= (int) $gallery['sort_order'] ?>" <?= $canWrite ? '' : 'disabled' ?>></div>
        </div>
        <div class="field"><label for="text">Text (optional – Leerzeile = Absatz)</label><textarea id="text" name="text" rows="4" <?= $canWrite ? '' : 'disabled' ?>><?= e((string) $gallery['text']) ?></textarea></div>
        <label class="check"><input type="checkbox" name="published" value="1" <?= (int) $gallery['published'] === 1 ? 'checked' : '' ?> <?= $canWrite ? '' : 'disabled' ?>> Auf der Website sichtbar</label>
        <?php if (!$isNew): ?><p class="muted">Öffentlich: <a href="<?= e(url('/e/' . $event['slug'] . '/galerie/' . $gallery['slug'])) ?>" target="_blank" rel="noopener">/e/<?= e($event['slug']) ?>/galerie/<?= e((string) $gallery['slug']) ?> ↗</a></p><?php endif; ?>
    </div>
    <div class="card">
        <div class="card__head"><h2>Bilder</h2></div>
        <p class="muted">Erst suchen oder nach Tag filtern, dann anhaken; unten die Reihenfolge festlegen. Das erste Bild ist das Titelbild – oder in der Reihenfolge-Leiste ★ klicken.</p>
        <?php $pickerField = 'image_ids'; $pickerSelected = $selected; $pickerImages = $images; $pickerSingle = false;
        require dirname(__DIR__) . '/partials/_image-picker.php'; ?>
    </div>
    <input type="hidden" name="cover_image_id" value="<?= (int) ($gallery['cover_image_id'] ?? 0) ?: '' ?>" data-cover-input>
    <?php if ($canWrite): ?><div class="form-actions"><button class="btn btn--primary" type="submit"><?= $isNew ? 'Galerie anlegen' : 'Speichern' ?></button></div><?php endif; ?>
</form>
