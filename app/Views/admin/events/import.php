<div class="page-head">
    <div>
        <h1><?= e(t('Fightcard importieren')) ?></h1>
        <p class="page-head__sub"><?= t('Übernimmt ein Event aus einer bestehenden Event-Website im NAFN-Format (<code>data/fights.json</code>).') ?></p>
    </div>
    <div class="page-head__actions"><a class="btn btn--ghost" href="<?= e(url('/admin/events')) ?>"><?= e(t('Zur Liste')) ?></a></div>
</div>

<div class="notice">
    <?= t('Angelegt wird ein <strong>Gala-Event</strong> mit allen Kämpfen und Pausen (Reihenfolge, Blöcke, Storys, Status, Ergebnisse), dazu die <strong>Gyms</strong> und <strong>Kämpfer</strong> (Kampfname, Alter, Bilanz, Bio, Foto/Video-Modus). Schon vorhandene Gyms/Kämpfer mit gleichem Namen werden wiederverwendet. Das Event bleibt zunächst unveröffentlicht.') ?>
</div>

<div class="form-grid">
    <form method="post" action="<?= e(url('/admin/events/import')) ?>" class="card form">
        <?= csrf_field() ?>
        <div class="card__head"><h2><?= e(t('Von einer Website holen')) ?></h2></div>
        <div class="field">
            <label for="site_url"><?= e(t('Adresse der Event-Website')) ?></label>
            <input id="site_url" name="site_url" placeholder="https://nafn.at" required>
            <p class="field__hint"><?= t('Gelesen werden <code>/data/fights.json</code> und (falls vorhanden) <code>/data/fighters.json</code>.') ?></p>
        </div>
        <label class="check"><input type="checkbox" name="with_media" value="1" checked> <?= e(t('Kämpferfotos und -videos mit übernehmen (kann dauern)')) ?></label>
        <label class="check"><input type="checkbox" name="replace" value="1"> <?= e(t('Früheren Import dieses Events auffrischen (gleiche Event-ID: Schlüssel, Webhooks, Sponsoren, Bilder und Website-Einstellungen bleiben)')) ?></label>
        <div class="form-actions"><button class="btn btn--primary" type="submit"><?= e(t('Importieren')) ?></button></div>
    </form>

    <form method="post" action="<?= e(url('/admin/events/import')) ?>" enctype="multipart/form-data" class="card form">
        <?= csrf_field() ?>
        <div class="card__head"><h2><?= e(t('Dateien hochladen')) ?></h2></div>
        <div class="field"><label for="fights">fights.json *</label><input id="fights" type="file" name="fights" accept=".json,application/json" required></div>
        <div class="field"><label for="fighters">fighters.json <small><?= e(t('(Kämpfer-Pool, optional)')) ?></small></label><input id="fighters" type="file" name="fighters" accept=".json,application/json"></div>
        <label class="check"><input type="checkbox" name="with_media" value="1"> <?= e(t('Medien von dieser Adresse nachladen:')) ?></label>
        <div class="field"><input name="media_base" placeholder="https://nafn.at"></div>
        <label class="check"><input type="checkbox" name="replace" value="1"> <?= e(t('Früheren Import dieses Events auffrischen (gleiche Event-ID: Schlüssel, Webhooks, Sponsoren, Bilder und Website-Einstellungen bleiben)')) ?></label>
        <div class="form-actions"><button class="btn btn--primary" type="submit"><?= e(t('Importieren')) ?></button></div>
    </form>
</div>
