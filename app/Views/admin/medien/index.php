<?php

/**
 * Bildbibliothek: Upload, Suche/Tag-Filter, Bearbeiten je Bild, Sammelaktionen.
 *
 * @var list<array<string,mixed>> $images
 * @var int                        $total
 * @var array<string,int>          $tagCounts
 * @var string                     $q
 * @var string                     $tag
 */
?>
<div class="page-head">
    <div>
        <h1><?= e(t('Bilder')) ?></h1>
        <p class="page-head__sub">
            <?= e(t('Bildbibliothek: einmal hochladen, mit Tags verschlagworten – dann bei Kämpfen, im Event-Bericht und in Galerien auswählen.')) ?>
            <?= e(t('%d Bilder, %d Tags.', $total, count($tagCounts))) ?>
        </p>
    </div>
    <div class="page-head__actions">
        <a class="btn" href="<?= e(url('/admin/events')) ?>"><?= e(t('Events')) ?></a>
    </div>
</div>

<details class="card" <?= $total === 0 ? 'open' : '' ?>>
    <summary class="linklike">⬆ <?= e(t('Bilder hochladen')) ?></summary>
    <form method="post" action="<?= e(url('/admin/medien/hochladen')) ?>" enctype="multipart/form-data" class="form">
        <?= csrf_field() ?>
        <div class="field">
            <label for="up-files"><?= e(t('Dateien (JPG, PNG, GIF, WebP – Mehrfachauswahl; werden am Server auf max. %d px verkleinert, Handyfotos richtig gedreht)', \App\Core\ImageTool::MAX_EDGE)) ?></label>
            <input id="up-files" type="file" name="files[]" accept="image/*" multiple required>
        </div>
        <div class="field-row">
            <div class="field field--grow">
                <label for="up-tags"><?= e(t('Tags (mit Komma trennen)')) ?></label>
                <input id="up-tags" name="tags" list="taglist" value="<?= e($tag) ?>" placeholder="<?= e(t('z. B. nafn 3, hauptkampf, reiser')) ?>">
            </div>
            <div class="field field--grow">
                <label for="up-caption"><?= e(t('Bildtext (optional, gilt für alle hochgeladenen Bilder)')) ?></label>
                <input id="up-caption" name="caption" maxlength="200" placeholder="<?= e(t('z. B. Titelkampf Aykac vs. Reiser')) ?>">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn--primary" type="submit"><?= e(t('Hochladen')) ?></button>
            <span class="muted"><?= e(t('Tipp: Tags wie Anlass, Jahr oder Gruppe – danach lassen sich Bilder überall gezielt finden.')) ?></span>
        </div>
    </form>
</details>

<datalist id="taglist"><?php foreach ($tagCounts as $t => $n): ?><option value="<?= e((string) $t) ?>"><?php endforeach; ?></datalist>

<div class="card">
    <form method="get" action="<?= e(url('/admin/medien')) ?>" class="filters">
        <div class="filters__row">
            <div class="field field--grow">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Suchen: Tag, Bildtext, Dateiname … (mehrere Wörter = alle müssen passen)')) ?>">
            </div>
            <?php if ($tag !== ''): ?><input type="hidden" name="tag" value="<?= e($tag) ?>"><?php endif; ?>
            <button class="btn" type="submit"><?= e(t('Suchen')) ?></button>
            <?php if ($q !== '' || $tag !== ''): ?><a class="btn btn--ghost" href="<?= e(url('/admin/medien')) ?>"><?= e(t('Zurücksetzen')) ?></a><?php endif; ?>
        </div>
    </form>
    <?php if ($tagCounts !== []): ?>
        <div class="tag-cloud">
            <?php foreach ($tagCounts as $t => $n): ?>
                <a class="tag<?= $tag === (string) $t ? ' is-on' : '' ?>" href="<?= e(url('/admin/medien', ['q' => $q, 'tag' => $tag === (string) $t ? '' : (string) $t])) ?>"><?= e((string) $t) ?> <small><?= $n ?></small></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($images === []): ?>
    <div class="card"><p class="empty"><?= $total === 0 ? e(t('Noch keine Bilder – oben hochladen.')) : e(t('Kein Bild passt zur Suche.')) ?></p></div>
<?php else: ?>
<form method="post" action="<?= e(url('/admin/medien/sammel')) ?>" data-confirm-bulk="<?= e(t('Aktion für die ausgewählten Bilder ausführen?')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="q" value="<?= e($q) ?>"><input type="hidden" name="tag" value="<?= e($tag) ?>">
    <p class="muted"><?= e(t('%d Bild(er)', count($images))) ?> · <label class="check"><input type="checkbox" data-check-all> <?= e(t('alle auswählen')) ?></label></p>
    <div class="image-lib">
        <?php foreach ($images as $img): ?>
            <div class="image-lib__item card">
                <label class="image-lib__pic">
                    <input type="checkbox" name="ids[]" value="<?= (int) $img['id'] ?>">
                    <img src="<?= e(upload_url((string) $img['thumb'])) ?>" alt="" loading="lazy">
                    <a class="image-lib__size" href="<?= e(upload_url((string) $img['file'])) ?>" target="_blank" rel="noopener" title="<?= e(t('Bild öffnen')) ?>"><?= (int) $img['width'] ?> × <?= (int) $img['height'] ?></a>
                </label>
                <div class="image-lib__body">
                    <div class="tag-cloud tag-cloud--sm"><?php foreach ($img['tags'] as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?></div>
                    <?php if ((string) $img['caption'] !== ''): ?><div class="image-lib__cap"><?= e((string) $img['caption']) ?></div><?php endif; ?>
                    <div class="muted image-lib__meta"><?= e(substr((string) $img['created_at'], 0, 10)) ?> · <?= e((string) $img['orig_name']) ?></div>
                    <details>
                        <summary class="linklike">✏️ <?= e(t('Tags / Bildtext')) ?></summary>
                        <div class="image-lib__edit" data-id="<?= (int) $img['id'] ?>" data-url="<?= e(url('/admin/medien/' . (int) $img['id'])) ?>">
                            <input class="js-edit-tags" list="taglist" value="<?= e(implode(', ', $img['tags'])) ?>" placeholder="<?= e(t('Tags, mit Komma')) ?>">
                            <input class="js-edit-caption" maxlength="200" value="<?= e((string) $img['caption']) ?>" placeholder="<?= e(t('Bildtext')) ?>">
                            <button type="button" class="btn btn--sm js-edit-save"><?= e(t('Speichern')) ?></button>
                        </div>
                    </details>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="bulkbar">
        <strong><?= e(t('Ausgewählte Bilder:')) ?></strong>
        <select name="action">
            <option value="addtags"><?= e(t('Tags hinzufügen')) ?></option>
            <option value="deltags"><?= e(t('Tags entfernen')) ?></option>
            <option value="delete"><?= e(t('Löschen')) ?></option>
        </select>
        <input name="bulk_tags" list="taglist" placeholder="<?= e(t('Tags (Komma)')) ?>">
        <button class="btn btn--primary" type="submit"><?= e(t('Ausführen')) ?></button>
        <span class="muted"><?= e(t('Löschen entfernt die Bilder auch aus Kämpfen, Galerien und dem Bericht.')) ?></span>
    </div>
</form>
<?php endif; ?>

<script>
/* Bild einzeln speichern: POST wie ein Formular, dann Seite neu laden */
document.querySelectorAll('.js-edit-save').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var box = btn.closest('.image-lib__edit');
        var fd  = new FormData();
        fd.append('csrf_token', <?= json_encode(\App\Core\Csrf::token()) ?>);
        fd.append('tags', box.querySelector('.js-edit-tags').value);
        fd.append('caption', box.querySelector('.js-edit-caption').value);
        fd.append('q', <?= json_encode($q) ?>);
        fd.append('tag', <?= json_encode($tag) ?>);
        btn.disabled = true; btn.textContent = '…';
        fetch(box.getAttribute('data-url'), { method: 'POST', body: fd, credentials: 'same-origin' }).then(function () { location.reload(); });
    });
});
</script>
