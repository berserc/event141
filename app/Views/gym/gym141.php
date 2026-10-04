<?php

/**
 * @var array<string,mixed>       $gym
 * @var list<array<string,mixed>> $members
 * @var string                    $error
 * @var array<int,int>            $known
 * @var string                    $q
 */
$connected = $gym['gym141_token'] !== '';
?>
<h1><?= e(t('Gym141-Kopplung')) ?></h1>

<?php if (!$connected): ?>
    <div class="m-card">
        <p class="lead"><?= t('Läuft euer Verein auf <strong>Gym141</strong>? Dann holt ihr eure Mitglieder direkt als Sportler – ohne Abtippen. Anmeldung mit einem Verwaltungs-Benutzer eurer Gym141-Instanz (Admin, Verwaltung oder Trainer). Das Passwort wird nicht gespeichert, nur ein Zugangs-Token, das ihr jederzeit trennen könnt.') ?></p>
        <form method="post" action="<?= e(url('/gym/gym141/verbinden')) ?>" class="m-form">
            <?= csrf_field() ?>
            <div class="m-field"><label for="gym141_url"><?= e(t('Adresse eurer Gym141-Instanz')) ?></label><input id="gym141_url" name="gym141_url" required value="<?= e($gym['gym141_url']) ?>" placeholder="<?= e(t('https://euer-verein.gym141.com')) ?>"></div>
            <div class="m-grid">
                <div class="m-field"><label for="gym141_username"><?= e(t('Benutzername')) ?></label><input id="gym141_username" name="gym141_username" required autocomplete="off"></div>
                <div class="m-field"><label for="gym141_password"><?= e(t('Passwort')) ?></label><input id="gym141_password" name="gym141_password" type="password" required autocomplete="off"></div>
            </div>
            <button class="btn btn--primary" type="submit"><?= e(t('Verbinden')) ?></button>
        </form>
    </div>
<?php else: ?>
    <div class="m-card">
        <p><span class="badge-dark badge-dark--bestaetigt"><?= e(t('verbunden')) ?></span> <strong><?= e($gym['gym141_club'] ?: $gym['gym141_url']) ?></strong>
            <small class="muted">· <?= e($gym['gym141_url']) ?> · <?= e(t('Benutzer %s', $gym['gym141_user'])) ?></small></p>
        <form method="post" action="<?= e(url('/gym/gym141/trennen')) ?>" data-confirm="<?= e(t('Verbindung trennen? Übernommene Sportler bleiben erhalten.')) ?>">
            <?= csrf_field() ?><button class="linklike" type="submit"><?= e(t('Verbindung trennen')) ?></button>
        </form>
    </div>

    <?php if ($error !== ''): ?>
        <div class="m-card"><p class="m-warn"><?= e(t('Gym141 antwortet nicht: %s', $error)) ?></p>
            <p class="muted"><?= e(t('Ist das Token abgelaufen, die Verbindung trennen und neu herstellen.')) ?></p></div>
    <?php else: ?>
        <div class="m-card">
            <h2><?= e(t('Mitglieder auswählen')) ?></h2>
            <form method="get" class="m-inline">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Name oder Mitgliedsnummer')) ?>">
                <button class="btn btn--sm btn--ghost btn--on-dark" type="submit"><?= e(t('Suchen')) ?></button>
                <span class="muted"><?= e(t('%d Mitglieder', count($members))) ?></span>
            </form>

            <form method="post" action="<?= e(url('/gym/gym141/uebernehmen')) ?>" id="import-form">
                <?= csrf_field() ?>
                <table class="m-table">
                    <thead><tr><th><input type="checkbox" data-check-all="import-form" aria-label="<?= e(t('alle')) ?>"></th><th><?= e(t('Name')) ?></th><th><?= e(t('Geb.')) ?></th><th><?= e(t('Gruppen')) ?></th><th><?= e(t('Hier')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($members as $m): ?>
                        <?php $mid = (int) ($m['id'] ?? 0); $bekannt = isset($known[$mid]); ?>
                        <tr class="<?= $bekannt ? 'is-muted' : '' ?>">
                            <td><input type="checkbox" name="member_ids[]" value="<?= $mid ?>"></td>
                            <td><strong><?= e(($m['last_name'] ?? '') . ' ' . ($m['first_name'] ?? '')) ?></strong> <small class="muted"><?= e($m['member_no'] ?? '') ?></small></td>
                            <td><?= e(format_date($m['birthdate'] ?? null)) ?></td>
                            <td><small><?= e($m['sections'] ?? '') ?></small></td>
                            <td><?= $bekannt ? '<small class="m-ok">' . e(t('übernommen')) . '</small>' : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($members === []): ?><tr><td colspan="5" class="muted"><?= e(t('Keine Mitglieder gefunden.')) ?></td></tr><?php endif; ?>
                    </tbody>
                </table>
                <p><button class="btn btn--primary" type="submit"><?= e(t('Ausgewählte als Sportler übernehmen')) ?></button>
                    <small class="muted"><?= e(t('Bereits übernommene werden aktualisiert, nicht doppelt angelegt.')) ?></small></p>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="m-card" id="api">
    <h2><?= e(t('Von Gym141 aus anmelden (API-Schlüssel)')) ?></h2>
    <p class="lead"><?= e(t('Die Gegenrichtung: mit einem API-Schlüssel kann eure Gym141-Instanz (oder ein anderes System) Sportler direkt hierher übertragen, zu offenen Events anmelden und die Ergebnisse eurer Kämpfer abholen.')) ?></p>
    <?php if (!empty($newKey)): ?>
        <p class="m-ok"><strong><?= e(t('Neuer Schlüssel – jetzt kopieren, er wird nur einmal angezeigt:')) ?></strong></p>
        <p style="font-family:ui-monospace,Consolas,monospace;background:#000;color:#7dffb0;padding:.7rem 1rem;border-radius:8px;word-break:break-all;user-select:all"><?= e($newKey) ?></p>
        <p class="muted"><?= t('In Gym141 eintragen: Adresse <code>%s</code> + dieser Schlüssel.', e(\App\Core\Fightcard::requestBase() . url('/'))) ?></p>
    <?php endif; ?>
    <?php if (($apiKeys ?? []) !== []): ?>
        <table class="m-table">
            <thead><tr><th><?= e(t('Name')) ?></th><th><?= e(t('Schlüssel')) ?></th><th><?= e(t('Zuletzt benutzt')) ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($apiKeys as $k): ?>
                <tr>
                    <td><?= e($k['name']) ?></td>
                    <td><code><?= e($k['key_prefix']) ?>…</code></td>
                    <td><?= e(format_datetime($k['last_used_at'])) ?: e(t('nie')) ?></td>
                    <td><form method="post" action="<?= e(url('/gym/api-schluessel/' . $k['id'] . '/loeschen')) ?>" data-confirm="<?= e(t('Schlüssel löschen? Das gekoppelte System verliert den Zugriff.')) ?>"><?= csrf_field() ?><button class="linklike linklike--danger" type="submit"><?= e(t('löschen')) ?></button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/gym/api-schluessel')) ?>" class="m-inline">
        <?= csrf_field() ?>
        <input name="name" placeholder="<?= e(t('Name, z. B. Gym141')) ?>" value="Gym141">
        <button class="btn btn--sm btn--ghost btn--on-dark" type="submit"><?= e(t('API-Schlüssel erzeugen')) ?></button>
    </form>
</div>

<script>
document.querySelectorAll('[data-check-all]').forEach(function (cb) {
    cb.addEventListener('change', function () {
        document.querySelectorAll('#' + cb.dataset.checkAll + ' input[type=checkbox][name]').forEach(function (x) { x.checked = cb.checked; });
    });
});
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) { if (!confirm(f.dataset.confirm)) ev.preventDefault(); });
});
</script>
