<?php

use App\Core\Auth;

/**
 * @var array<string,mixed>  $user
 * @var array<string,string> $errors
 * @var bool                 $isNew
 */
$id     = (int) ($user['id'] ?? 0);
$action = $isNew ? url('/admin/benutzer') : url('/admin/benutzer/' . $id);
$err    = static fn (string $f): string => isset($errors[$f]) ? '<p class="field__error">' . e($errors[$f]) . '</p>' : '';
?>
<div class="page-head">
    <h1><?= $isNew ? e(t('Neuer Benutzer')) : e($user['username']) ?></h1>
    <div class="page-head__actions"><a class="btn btn--ghost" href="<?= e(url('/admin/benutzer')) ?>"><?= e(t('Zur Liste')) ?></a></div>
</div>

<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>
    <div class="form-grid">
        <fieldset class="card">
            <legend><?= e(t('Konto')) ?></legend>
            <div class="field"><label for="username"><?= e(t('Benutzername *')) ?></label><input id="username" name="username" required value="<?= e($user['username']) ?>" autocomplete="off"><?= $err('username') ?></div>
            <div class="field"><label for="name"><?= e(t('Name')) ?></label><input id="name" name="name" value="<?= e($user['name']) ?>"></div>
            <div class="field"><label for="email"><?= e(t('E-Mail')) ?></label><input id="email" name="email" type="email" value="<?= e($user['email']) ?>"><?= $err('email') ?></div>
            <div class="field">
                <label for="password"><?= $isNew ? t('Startpasswort <small>(leer = wird erzeugt)</small>') : t('Neues Passwort <small>(leer = unverändert)</small>') ?></label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>"><?= $err('password') ?>
            </div>
        </fieldset>
        <fieldset class="card">
            <legend><?= e(t('Rolle')) ?></legend>
            <?php foreach (Auth::ROLES as $key => $label): ?>
                <label class="check"><input type="radio" name="role" value="<?= e($key) ?>" <?= $user['role'] === $key ? 'checked' : '' ?>> <?= e(t($label)) ?></label>
            <?php endforeach; ?>
            <?= $err('role') ?>
            <label class="check" style="margin-top:1rem"><input type="checkbox" name="active" value="1" <?= (int) $user['active'] === 1 ? 'checked' : '' ?>> <?= e(t('Konto aktiv')) ?></label>
        </fieldset>
    </div>
    <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= $isNew ? e(t('Benutzer anlegen')) : e(t('Speichern')) ?></button>
    </div>
</form>

<?php if (!$isNew): ?>
    <form method="post" action="<?= e(url('/admin/benutzer/' . $id . '/loeschen')) ?>" class="inline" data-confirm="<?= e(t('Benutzer „%s“ löschen?', $user['username'])) ?>">
        <?= csrf_field() ?>
        <button class="linklike linklike--danger" type="submit"><?= e(t('Benutzer löschen')) ?></button>
    </form>
<?php endif; ?>
