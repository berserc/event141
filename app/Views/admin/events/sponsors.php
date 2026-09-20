<?php

use App\Core\Auth;

/**
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $sponsors
 */
$id       = (int) $event['id'];
$subtitle = 'Sponsoren';
$canWrite = Auth::canWrite();
require __DIR__ . '/_head.php';
?>
<div class="card">
    <div class="card__head"><h2>Sponsoren</h2></div>
    <p class="muted">Erscheinen unten auf der Event-Seite. Hauptsponsoren bekommen eine große Kachel. Die Kachelfarbe passt den Hintergrund ans Logo an (weißes Logo → dunkle Kachel).</p>

    <?php foreach ($sponsors as $s): ?>
        <form method="post" action="<?= e(url('/admin/events/' . $id . '/sponsor')) ?>" enctype="multipart/form-data" class="inline-form venue-row">
            <?= csrf_field() ?>
            <input type="hidden" name="sponsor_id" value="<?= (int) $s['id'] ?>">
            <div class="sponsor-tile" style="width:130px<?= $s['tile_color'] !== '' ? ';background:' . e($s['tile_color']) : '' ?>">
                <?php if ($s['logo_path'] !== ''): ?><img src="<?= e(upload_url($s['logo_path'])) ?>" alt=""><?php else: ?><small><?= e($s['name']) ?></small><?php endif; ?>
            </div>
            <div class="field field--grow"><label>Name</label><input name="name" value="<?= e($s['name']) ?>" required <?= $canWrite ? '' : 'disabled' ?>></div>
            <div class="field field--grow"><label>Link</label><input name="url" value="<?= e($s['url']) ?>" placeholder="https://…" <?= $canWrite ? '' : 'disabled' ?>></div>
            <div class="field field--xs"><label>Kachel</label><input type="color" name="tile_color" value="<?= e($s['tile_color'] ?: '#ffffff') ?>"></div>
            <label class="check"><input type="checkbox" name="use_color" value="1" <?= $s['tile_color'] !== '' ? 'checked' : '' ?>> Farbe</label>
            <label class="check"><input type="checkbox" name="is_main" value="1" <?= (int) $s['is_main'] === 1 ? 'checked' : '' ?>> Haupt</label>
            <label class="check"><input type="checkbox" name="published" value="1" <?= (int) $s['published'] === 1 ? 'checked' : '' ?>> sichtbar</label>
            <div class="field field--xs"><label>Reihung</label><input type="number" name="sort_order" value="<?= (int) $s['sort_order'] ?>"></div>
            <?php if ($canWrite): ?>
                <div class="field"><label>Logo ersetzen</label><input type="file" name="logo" accept="image/*"></div>
                <div class="contact-row__actions">
                    <button class="btn btn--sm" type="submit">Speichern</button>
                    <button class="linklike linklike--danger" type="submit" formaction="<?= e(url('/admin/events/' . $id . '/sponsor-loeschen')) ?>" data-confirm-click="Sponsor „<?= e($s['name']) ?>“ entfernen?">Entfernen</button>
                </div>
            <?php endif; ?>
        </form>
    <?php endforeach; ?>

    <?php if ($sponsors === []): ?><p class="muted">Noch keine Sponsoren.</p><?php endif; ?>

    <?php if ($canWrite): ?>
        <form method="post" action="<?= e(url('/admin/events/' . $id . '/sponsor')) ?>" enctype="multipart/form-data" class="inline-form inline-form--new">
            <?= csrf_field() ?>
            <input type="hidden" name="sponsor_id" value="0">
            <input type="hidden" name="published" value="1">
            <div class="field field--grow"><label>Neuer Sponsor</label><input name="name" required placeholder="Name"></div>
            <div class="field field--grow"><label>Link</label><input name="url" placeholder="https://…"></div>
            <div class="field"><label>Logo</label><input type="file" name="logo" accept="image/*"></div>
            <label class="check"><input type="checkbox" name="is_main" value="1"> Hauptsponsor</label>
            <button class="btn btn--sm btn--primary" type="submit">Hinzufügen</button>
        </form>
    <?php endif; ?>
</div>
