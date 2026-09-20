<?php

/**
 * @var array<string,mixed>  $page
 * @var array<string,string> $errors
 * @var bool                 $isNew
 */
$id     = (int) ($page['id'] ?? 0);
$action = $isNew ? url('/admin/seiten') : url('/admin/seiten/' . $id);
?>
<div class="page-head">
    <h1><?= $isNew ? 'Neue Seite' : e($page['title']) ?></h1>
    <div class="page-head__actions"><a class="btn btn--ghost" href="<?= e(url('/admin/seiten')) ?>">Zur Liste</a></div>
</div>

<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>
    <div class="card">
        <div class="field-row">
            <div class="field field--grow"><label for="title">Titel *</label><input id="title" name="title" required value="<?= e($page['title']) ?>">
                <?php if (isset($errors['title'])): ?><p class="field__error"><?= e($errors['title']) ?></p><?php endif; ?></div>
            <div class="field field--sm"><label for="slug">URL-Kürzel</label><input id="slug" name="slug" value="<?= e($page['slug']) ?>"></div>
            <div class="field field--xs"><label for="sort_order">Reihung</label><input id="sort_order" name="sort_order" type="number" value="<?= (int) $page['sort_order'] ?>"></div>
        </div>
        <div class="field"><label for="body">Inhalt</label><textarea id="body" name="body" rows="14" class="js-richtext"><?= e($page['body']) ?></textarea></div>
        <label class="check"><input type="checkbox" name="in_footer" value="1" <?= (int) $page['in_footer'] === 1 ? 'checked' : '' ?>> in der Fußzeile verlinken</label>
        <label class="check"><input type="checkbox" name="published" value="1" <?= (int) $page['published'] === 1 ? 'checked' : '' ?>> veröffentlicht</label>
    </div>
    <div class="form-actions"><button class="btn btn--primary" type="submit"><?= $isNew ? 'Seite anlegen' : 'Speichern' ?></button></div>
</form>

<?php if (!$isNew): ?>
    <form method="post" action="<?= e(url('/admin/seiten/' . $id . '/loeschen')) ?>" class="inline" data-confirm="Seite löschen?">
        <?= csrf_field() ?><button class="linklike linklike--danger" type="submit">Seite löschen</button>
    </form>
<?php endif; ?>
