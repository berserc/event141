<?php

/**
 * Update-Seite: Version prüfen und Ein-Klick-Update einspielen.
 *
 * @var string                    $version
 * @var array<string,mixed>|null  $manifest
 * @var bool                      $geprueft
 * @var list<string>              $log
 */
$updateDa = $manifest !== null && version_compare((string) $manifest['version'], $version, '>');
?>
<div class="page-head">
    <div>
        <h1><?= e(t('Updates')) ?></h1>
        <p class="page-head__sub"><?= t('Installierte Version: <strong>%s</strong>', e($version)) ?></p>
    </div>

    <div class="page-head__actions">
        <a class="btn btn--primary" href="<?= e(url('/admin/updates', ['pruefen' => '1'])) ?>"><?= e(t('Nach Updates suchen')) ?></a>
    </div>
</div>

<?php if ($log !== []): ?>
    <div class="card">
        <div class="card__head"><h2><?= e(t('Update-Protokoll')) ?></h2></div>
        <ul class="remind-list">
            <?php foreach ($log as $zeile): ?>
                <li><?= e($zeile) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($geprueft && $manifest !== null): ?>
    <?php if ($updateDa): ?>
        <div class="card">
            <div class="card__head">
                <h2><?= e(t('Version %s verfügbar', $manifest['version'])) ?></h2>
                <span class="badge badge--ok"><?= e(t('neu')) ?></span>
            </div>

            <?php if ((string) $manifest['changelog'] !== ''): ?>
                <p style="white-space: pre-line"><?= e($manifest['changelog']) ?></p>
            <?php endif; ?>

            <div class="notice">
                <?= t('Vor dem Einspielen wird automatisch eine Datenbanksicherung angelegt. Eigene Daten (Datenbank, hochgeladene Fotos/Videos, Konfiguration) werden vom Update <strong>nicht</strong> verändert.') ?>
            </div>

            <form method="post" action="<?= e(url('/admin/updates/installieren')) ?>"
                  data-confirm="<?= e(t('Jetzt auf Version %s aktualisieren?', $manifest['version'])) ?>">
                <?= csrf_field() ?>
                <button class="btn btn--primary" type="submit"><?= e(t('Update jetzt installieren')) ?></button>
            </form>
        </div>
    <?php else: ?>
        <div class="card">
            <p class="empty"><?= e(t('Event141 ist auf dem neuesten Stand (Version %s).', $version)) ?></p>
        </div>
    <?php endif; ?>
<?php elseif (!$geprueft): ?>
    <div class="card">
        <p class="muted">
            <?= t('„Nach Updates suchen“ fragt den Update-Server (<code class="mono">%s</code>) ab und zeigt an, ob eine neue Version bereitsteht. Installiert wird erst nach ausdrücklicher Bestätigung.', e(\App\Core\Updater::manifestUrl())) ?>
        </p>
    </div>
<?php endif; ?>
