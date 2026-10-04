<?php

use App\Models\ApiKeyRepo;

/**
 * @var list<array<string,mixed>> $keys
 * @var list<array<string,mixed>> $webhooks
 * @var list<array<string,mixed>> $events
 * @var list<array<string,mixed>> $gyms
 * @var array{name:string,key:string}|null $newKey
 */
$base = \App\Core\Fightcard::requestBase() . url('/api/v1');
?>
<div class="page-head">
    <div>
        <h1><?= e(t('API & Kopplungen')) ?></h1>
        <p class="page-head__sub"><?= e(t('Event141 als zentrale Plattform: Event-Websites, Anzeigetafeln, Apps und Gym141 greifen per API-Schlüssel zu.')) ?></p>
    </div>
</div>

<?php if ($newKey !== null): ?>
    <div class="notice notice--ok">
        <strong><?= e(t('Neuer Schlüssel „%s“ – jetzt kopieren, er wird nur dieses eine Mal angezeigt:', $newKey['name'])) ?></strong>
        <p class="key-once"><?= e($newKey['key']) ?></p>
    </div>
<?php endif; ?>

<div class="form-grid form-grid--wide">
    <div class="card">
        <div class="card__head"><h2><?= e(t('API-Schlüssel')) ?></h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th><?= e(t('Name')) ?></th><th><?= e(t('Schlüssel')) ?></th><th><?= e(t('Rechte')) ?></th><th><?= e(t('Beschränkt auf')) ?></th><th><?= e(t('Zuletzt benutzt')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($keys as $k): ?>
                    <tr class="<?= (int) $k['active'] === 1 ? '' : 'is-muted' ?>">
                        <td class="strong"><?= e($k['name']) ?></td>
                        <td class="mono"><?= e($k['key_prefix']) ?>…</td>
                        <td><?= e(t(ApiKeyRepo::SCOPES[$k['scope']] ?? $k['scope'])) ?></td>
                        <td>
                            <?php if ($k['gym_id'] !== null): ?>Gym: <a href="<?= e(url('/admin/gyms/' . $k['gym_id'])) ?>"><?= e($k['gym_name']) ?></a>
                            <?php elseif ($k['event_id'] !== null): ?>Event: <?= e($k['event_name']) ?>
                            <?php else: ?><span class="muted"><?= e(t('ganze Plattform')) ?></span><?php endif; ?>
                        </td>
                        <td><?= e(format_datetime($k['last_used_at'])) ?: '<span class="muted">' . e(t('nie')) . '</span>' ?></td>
                        <td><?= (int) $k['active'] === 1 ? '<span class="pill pill--aktiv">' . e(t('aktiv')) . '</span>' : '<span class="pill pill--offen">' . e(t('aus')) . '</span>' ?></td>
                        <td class="row-actions">
                            <form method="post" action="<?= e(url('/admin/api/schluessel/' . $k['id'] . '/umschalten')) ?>" class="inline"><?= csrf_field() ?>
                                <button class="linklike" type="submit"><?= (int) $k['active'] === 1 ? e(t('deaktivieren')) : e(t('aktivieren')) ?></button></form>
                            <form method="post" action="<?= e(url('/admin/api/schluessel/' . $k['id'] . '/loeschen')) ?>" class="inline" data-confirm="<?= e(t('Schlüssel „%s“ löschen? Gekoppelte Systeme verlieren sofort den Zugriff.', $k['name'])) ?>"><?= csrf_field() ?>
                                <button class="linklike linklike--danger" type="submit"><?= e(t('löschen')) ?></button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($keys === []): ?><tr><td colspan="7" class="empty"><?= e(t('Noch kein Schlüssel.')) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <form method="post" action="<?= e(url('/admin/api/schluessel')) ?>" class="inline-form inline-form--new">
            <?= csrf_field() ?>
            <div class="field field--grow"><label><?= e(t('Neuer Schlüssel – wofür?')) ?></label><input name="name" required placeholder="<?= e(t('z. B. nafn.at Website')) ?>"></div>
            <div class="field field--sm"><label><?= e(t('Rechte')) ?></label>
                <select name="scope"><?php foreach (ApiKeyRepo::SCOPES as $s => $l): ?><option value="<?= e($s) ?>"><?= e(t($l)) ?></option><?php endforeach; ?></select></div>
            <div class="field field--sm"><label><?= e(t('nur Event')) ?></label>
                <select name="event_id"><option value=""><?= e(t('alle')) ?></option><?php foreach ($events as $ev): ?><option value="<?= (int) $ev['id'] ?>"><?= e($ev['name']) ?></option><?php endforeach; ?></select></div>
            <div class="field field--sm"><label><?= e(t('Gym-Schlüssel für')) ?></label>
                <select name="gym_id"><option value=""><?= e(t('– kein Gym –')) ?></option><?php foreach ($gyms as $g): ?><option value="<?= (int) $g['id'] ?>"><?= e($g['name']) ?></option><?php endforeach; ?></select></div>
            <button class="btn btn--primary" type="submit"><?= e(t('Schlüssel erzeugen')) ?></button>
        </form>
        <p class="field__hint"><?= e(t('Gym-Schlüssel können Gyms auch selbst im Gym-Bereich unter „Gym141“ erzeugen.')) ?></p>
    </div>

    <div class="card">
        <div class="card__head"><h2><?= e(t('Endpunkte')) ?></h2></div>
        <p class="muted"><?= t('Basis: <code>%s</code> · Header <code>Authorization: Bearer ek_…</code> (oder <code>X-Api-Key</code>)', e($base)) ?></p>
        <code class="endpoint">GET  /ping</code>
        <code class="endpoint">GET  /events · /event/{slug} · /event/{slug}/live</code>
        <code class="endpoint">GET  /event/{slug}/fightcard &nbsp;← <?= e(t('NAFN-Format')) ?> (fights.json)</code>
        <code class="endpoint">POST /event/{slug}/bout/{id} &nbsp;{action: live|result|reset|cancel, winner, method, round}</code>
        <p class="muted" style="margin-top:.8rem"><?= e(t('Mit Gym-Schlüssel (Gegenrichtung zu Gym141):')) ?></p>
        <code class="endpoint">GET  /gym · /gym/events · /gym/athletes · /gym/results</code>
        <code class="endpoint">POST /gym/athletes &nbsp;{athletes:[{external_ref, first_name, last_name, birthdate, gender, weight}]}</code>
        <code class="endpoint">POST /gym/event/{slug}/entries &nbsp;{entries:[{external_ref|athlete_id, category_id}]}</code>
        <code class="endpoint">POST /gym/entry/{id}/withdraw</code>
        <p class="muted" style="margin-top:.8rem"><?= t('Ohne Schlüssel lesbar (nur veröffentlichte Events): <code>/api/events</code>, <code>/api/event/{slug}</code>, <code>…/live</code>, <code>…/fightcard</code>.') ?></p>
    </div>
</div>

<div class="card">
    <div class="card__head"><h2><?= e(t('Webhooks')) ?></h2></div>
    <p class="muted"><?= t('Bei jeder Änderung an einem Event (Kämpfe, Status, Ergebnisse, Anmeldungen …) bekommt die Adresse einen POST <code>{"event":"slug","type":"event.changed"}</code> mit Header <code>X-Event141-Signature: sha256=HMAC(body, secret)</code> – die gekoppelte Website holt sich dann sofort die frische Fightcard.') ?></p>
    <div class="table-scroll">
        <table class="table table--compact">
            <thead><tr><th><?= e(t('Name')) ?></th><th><?= e(t('Adresse')) ?></th><th><?= e(t('Event')) ?></th><th>Secret</th><th><?= e(t('Letzte Zustellung')) ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($webhooks as $w): ?>
                <tr>
                    <td><?= e($w['name']) ?></td>
                    <td class="wrap-anywhere"><?= e($w['url']) ?></td>
                    <td><?= e($w['event_name'] ?? t('alle')) ?></td>
                    <td class="mono wrap-anywhere"><?= e($w['secret']) ?></td>
                    <td><?= e($w['last_status']) ?> <small class="muted"><?= e(format_datetime($w['last_at'])) ?></small></td>
                    <td class="row-actions">
                        <form method="post" action="<?= e(url('/admin/api/webhook/' . $w['id'] . '/test')) ?>" class="inline"><?= csrf_field() ?><button class="linklike" type="submit"><?= e(t('testen')) ?></button></form>
                        <form method="post" action="<?= e(url('/admin/api/webhook/' . $w['id'] . '/loeschen')) ?>" class="inline" data-confirm="<?= e(t('Webhook löschen?')) ?>"><?= csrf_field() ?><button class="linklike linklike--danger" type="submit"><?= e(t('löschen')) ?></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($webhooks === []): ?><tr><td colspan="6" class="empty"><?= e(t('Noch kein Webhook.')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= e(url('/admin/api/webhook')) ?>" class="inline-form inline-form--new">
        <?= csrf_field() ?>
        <div class="field field--sm"><label><?= e(t('Name')) ?></label><input name="name" placeholder="nafn.at"></div>
        <div class="field field--grow"><label><?= e(t('Adresse')) ?></label><input name="url" required placeholder="https://nafn.at/admin/event141-hook.php"></div>
        <div class="field field--sm"><label><?= e(t('Event')) ?></label>
            <select name="event_id"><option value=""><?= e(t('alle')) ?></option><?php foreach ($events as $ev): ?><option value="<?= (int) $ev['id'] ?>"><?= e($ev['name']) ?></option><?php endforeach; ?></select></div>
        <button class="btn btn--primary" type="submit"><?= e(t('Webhook anlegen')) ?></button>
    </form>
</div>
