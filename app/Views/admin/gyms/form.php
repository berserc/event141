<?php

use App\Core\Auth;
use App\Models\AthleteRepo;
use App\Models\EntryRepo;
use App\Models\GymRepo;

/**
 * @var array<string,mixed>       $gym
 * @var list<array<string,mixed>> $athletes
 * @var list<array<string,mixed>> $entries
 * @var array<string,string>      $errors
 * @var bool                      $isNew
 */
$id       = (int) ($gym['id'] ?? 0);
$action   = $isNew ? url('/admin/gyms') : url('/admin/gyms/' . $id);
$err      = static fn (string $f): string => isset($errors[$f]) ? '<p class="field__error">' . e($errors[$f]) . '</p>' : '';
$canWrite = Auth::canWrite();
$ro       = $canWrite ? '' : ' disabled';
?>
<div class="page-head">
    <div>
        <h1><?= $isNew ? e(t('Neues Gym')) : e($gym['name']) ?></h1>
        <?php if (!$isNew): ?>
            <p class="page-head__sub">
                <span class="pill pill--gym-<?= e($gym['status']) ?>"><?= e(t(GymRepo::STATUS[$gym['status']] ?? $gym['status'])) ?></span>
                · <?= e(t('registriert %s', format_datetime((string) $gym['created_at']))) ?>
                <?= $gym['login_last_at'] ? '· ' . e(t('letzter Login %s', format_datetime((string) $gym['login_last_at']))) : '' ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="page-head__actions">
        <?php if (!$isNew && $canWrite): ?>
            <form method="post" action="<?= e(url('/admin/gyms/' . $id . '/anmelden-als')) ?>" class="inline">
                <?= csrf_field() ?>
                <button class="btn btn--ghost" type="submit"><?= e(t('Als Gym anmelden ↗')) ?></button>
            </form>
        <?php endif; ?>
        <a class="btn btn--ghost" href="<?= e(url('/admin/gyms')) ?>"><?= e(t('Zur Liste')) ?></a>
    </div>
</div>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form">
    <?= csrf_field() ?>
    <div class="form-grid">
        <fieldset class="card">
            <legend><?= e(t('Gym / Verein')) ?></legend>
            <div class="field-row">
                <div class="field field--grow"><label for="name"><?= e(t('Name')) ?> *</label><input id="name" name="name" required value="<?= e($gym['name']) ?>"<?= $ro ?>><?= $err('name') ?></div>
                <div class="field field--xs"><label for="short_name"><?= e(t('Kurz')) ?></label><input id="short_name" name="short_name" maxlength="12" value="<?= e($gym['short_name']) ?>" placeholder="NOVO"<?= $ro ?>></div>
            </div>
            <div class="field"><label for="street"><?= e(t('Straße')) ?></label><input id="street" name="street" value="<?= e($gym['street']) ?>"<?= $ro ?>></div>
            <div class="field-row">
                <div class="field field--xs"><label for="zip"><?= e(t('PLZ')) ?></label><input id="zip" name="zip" value="<?= e($gym['zip']) ?>"<?= $ro ?>></div>
                <div class="field field--grow"><label for="city"><?= e(t('Ort')) ?></label><input id="city" name="city" value="<?= e($gym['city']) ?>"<?= $ro ?>></div>
                <div class="field field--xs"><label for="country"><?= e(t('Land')) ?></label><input id="country" name="country" maxlength="2" value="<?= e($gym['country']) ?>"<?= $ro ?>></div>
            </div>
            <div class="field"><label for="website"><?= e(t('Website')) ?></label><input id="website" name="website" value="<?= e($gym['website']) ?>"<?= $ro ?>></div>
            <?php if (!$isNew): ?>
                <div class="field">
                    <label for="status"><?= e(t('Status')) ?></label>
                    <select id="status" name="status"<?= $ro ?>>
                        <?php foreach (GymRepo::STATUS as $k => $l): ?>
                            <option value="<?= e($k) ?>" <?= $gym['status'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="field"><label for="note"><?= e(t('Interne Notiz')) ?></label><textarea id="note" name="note" rows="2"<?= $ro ?>><?= e($gym['note']) ?></textarea></div>
        </fieldset>

        <fieldset class="card">
            <legend><?= e(t('Kontakt & Login')) ?></legend>
            <div class="field"><label for="contact_name"><?= e(t('Ansprechpartner / Trainer')) ?></label><input id="contact_name" name="contact_name" value="<?= e($gym['contact_name']) ?>"<?= $ro ?>></div>
            <div class="field-row">
                <div class="field field--grow"><label for="email"><?= e(t('E-Mail')) ?></label><input id="email" name="email" type="email" value="<?= e($gym['email']) ?>"<?= $ro ?>><?= $err('email') ?></div>
                <div class="field field--sm"><label for="phone"><?= e(t('Telefon')) ?></label><input id="phone" name="phone" value="<?= e($gym['phone']) ?>"<?= $ro ?>></div>
            </div>
            <div class="field">
                <label for="login_email"><?= e(t('Login-E-Mail')) ?> <small><?= e(t('(Gym-Bereich)')) ?></small></label>
                <input id="login_email" name="login_email" type="email" value="<?= e($gym['login_email']) ?>"<?= $ro ?>>
                <?= $err('login_email') ?>
            </div>
            <div class="field">
                <label for="login_password"><?= e($isNew ? t('Login-Passwort') : t('Neues Login-Passwort')) ?> <small><?= e(t('(leer = unverändert)')) ?></small></label>
                <input id="login_password" name="login_password" type="password" autocomplete="new-password" minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>"<?= $ro ?>>
            </div>
            <div class="field">
                <label for="logo"><?= e(t('Logo')) ?></label>
                <?php if ($gym['logo_path'] !== ''): ?><img src="<?= e(upload_url($gym['logo_path'])) ?>" alt="" width="80" height="80" style="object-fit:contain"><?php endif; ?>
                <?php if ($canWrite): ?><input id="logo" type="file" name="logo" accept="image/*"><?php endif; ?>
            </div>
        </fieldset>
    </div>

    <?php if ($canWrite): ?>
        <div class="form-actions">
            <button class="btn btn--primary" type="submit"><?= e($isNew ? t('Gym anlegen') : t('Speichern')) ?></button>
        </div>
    <?php endif; ?>
</form>

<?php if (!$isNew): ?>
    <div class="form-grid form-grid--wide">
        <div class="card">
            <div class="card__head">
                <h2><?= e(t('Sportler')) ?> <span class="badge"><?= count($athletes) ?></span></h2>
                <?php if ($canWrite): ?><a class="btn btn--sm btn--primary" href="<?= e(url('/admin/sportler/neu', ['gym' => $id])) ?>"><?= e(t('Neuer Sportler')) ?></a><?php endif; ?>
            </div>
            <div class="table-scroll">
                <table class="table table--compact">
                    <thead><tr><th><?= e(t('Name')) ?></th><th><?= e(t('Jg.')) ?></th><th><?= e(t('Gewicht')) ?></th><th><?= e(t('Bilanz')) ?></th><th><?= e(t('Quelle')) ?></th><th class="num"><?= e(t('Anmeld.')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($athletes as $a): ?>
                        <tr class="<?= (int) $a['active'] === 0 ? 'is-muted' : '' ?>">
                            <td><a href="<?= e(url('/admin/sportler/' . $a['id'])) ?>"><?= e($a['last_name']) ?> <?= e($a['first_name']) ?></a><?= (int) $a['active'] === 0 ? ' <span class="badge badge--muted">' . e(t('inaktiv')) . '</span>' : '' ?></td>
                            <td><?= e($a['birthdate'] ? substr((string) $a['birthdate'], 0, 4) : '') ?></td>
                            <td><?= e(format_weight($a['weight'])) ?></td>
                            <td class="mono"><?= e(AthleteRepo::record($a)) ?></td>
                            <td><?= $a['gym141_member_id'] !== null ? '<span class="badge badge--info">Gym141</span>' : '' ?></td>
                            <td class="num"><?= (int) $a['entry_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($athletes === []): ?><tr><td colspan="6" class="empty"><?= e(t('Noch keine Sportler.')) ?></td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card__head"><h2><?= e(t('Gym141-Kopplung')) ?></h2></div>
            <?php if ($gym['gym141_token'] !== ''): ?>
                <p><span class="badge badge--ok"><?= e(t('verbunden')) ?></span> <strong><?= e($gym['gym141_club'] ?: $gym['gym141_url']) ?></strong><br>
                    <small class="muted"><?= e($gym['gym141_url']) ?> · <?= e(t('Benutzer %s', $gym['gym141_user'])) ?><?= $gym['gym141_synced_at'] ? ' · ' . e(t('zuletzt geholt %s', format_datetime((string) $gym['gym141_synced_at']))) : '' ?></small></p>
                <p>
                    <a class="btn btn--primary btn--sm" href="<?= e(url('/admin/gyms/' . $id . '/gym141')) ?>"><?= e(t('Mitglieder holen')) ?></a>
                    <?php if ($canWrite): ?>
                        <form method="post" action="<?= e(url('/admin/gyms/' . $id . '/gym141/trennen')) ?>" class="inline" data-confirm="<?= e(t('Verbindung zu Gym141 trennen?')) ?>">
                            <?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= e(t('Verbindung trennen')) ?></button>
                        </form>
                    <?php endif; ?>
                </p>
            <?php elseif ($canWrite): ?>
                <p class="muted"><?= t('Läuft das Gym auf <strong>Gym141</strong>, lassen sich die Mitglieder direkt als Sportler übernehmen. Anmeldung mit einem Verwaltungs-Benutzer der Gym141-Instanz (das Passwort wird nicht gespeichert, nur ein Zugangs-Token).') ?></p>
                <form method="post" action="<?= e(url('/admin/gyms/' . $id . '/gym141/verbinden')) ?>" class="form">
                    <?= csrf_field() ?>
                    <div class="field"><label for="gym141_url"><?= e(t('Adresse der Gym141-Instanz')) ?></label><input id="gym141_url" name="gym141_url" value="<?= e($gym['gym141_url']) ?>" placeholder="https://verein.gym141.com" required></div>
                    <div class="field-row">
                        <div class="field field--grow"><label for="gym141_username"><?= e(t('Benutzername')) ?></label><input id="gym141_username" name="gym141_username" required autocomplete="off"></div>
                        <div class="field field--grow"><label for="gym141_password"><?= e(t('Passwort')) ?></label><input id="gym141_password" name="gym141_password" type="password" required autocomplete="off"></div>
                    </div>
                    <button class="btn btn--primary btn--sm" type="submit"><?= e(t('Verbinden')) ?></button>
                </form>
            <?php else: ?>
                <p class="muted"><?= e(t('Nicht verbunden.')) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card__head"><h2><?= e(t('Anmeldungen')) ?> <span class="badge"><?= count($entries) ?></span></h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th><?= e(t('Event')) ?></th><th><?= e(t('Sportler##einzahl')) ?></th><th><?= e(t('Kategorie')) ?></th><th><?= e(t('Status')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($entries as $x): ?>
                    <tr>
                        <td><a href="<?= e(url('/admin/events/' . $x['event_id'] . '/anmeldungen', ['gym' => $id])) ?>"><?= e($x['event_name']) ?></a> <small class="muted"><?= e(format_date($x['starts_on'])) ?></small></td>
                        <td><?= e($x['last_name']) ?> <?= e($x['first_name']) ?></td>
                        <td><?= e($x['category_name'] ?? '–') ?></td>
                        <td><span class="pill pill--entry-<?= e($x['status']) ?>"><?= e(t(EntryRepo::STATUS[$x['status']] ?? $x['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($entries === []): ?><tr><td colspan="4" class="empty"><?= e(t('Noch keine Anmeldungen.')) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (Auth::isSuperuser()): ?>
        <div class="card card--danger">
            <div class="card__head"><h2><?= e(t('Gym löschen')) ?></h2></div>
            <p class="muted"><?= e(t('Nur möglich ohne Anmeldungen. Sportler werden mitgelöscht.')) ?></p>
            <form method="post" action="<?= e(url('/admin/gyms/' . $id . '/loeschen')) ?>" data-confirm="<?= e(t('Gym „%s“ löschen?', $gym['name'])) ?>">
                <?= csrf_field() ?><button class="btn btn--danger" type="submit"><?= e(t('Gym löschen')) ?></button>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>
