<?php

use App\Core\Auth;

/**
 * @var array<string,mixed>  $gym
 * @var array<string,string> $errors
 */
$err = static fn (string $f): string => isset($errors[$f]) ? '<p class="m-error">' . e($errors[$f]) . '</p>' : '';
?>
<h1><?= e(t('Gym-Daten')) ?></h1>

<form method="post" action="<?= e(url('/gym/profil')) ?>" enctype="multipart/form-data" class="m-form">
    <?= csrf_field() ?>
    <div class="m-card">
        <h2><?= e(t('Gym / Verein')) ?></h2>
        <div class="m-grid">
            <div class="m-field"><label for="name"><?= e(t('Name *')) ?></label><input id="name" name="name" required value="<?= e($gym['name']) ?>"><?= $err('name') ?></div>
            <div class="m-field"><label for="short_name"><?= e(t('Kurzname')) ?></label><input id="short_name" name="short_name" maxlength="12" value="<?= e($gym['short_name']) ?>"></div>
        </div>
        <div class="m-field"><label for="street"><?= e(t('Straße')) ?></label><input id="street" name="street" value="<?= e($gym['street']) ?>"></div>
        <div class="m-grid m-grid--3">
            <div class="m-field"><label for="zip"><?= e(t('PLZ')) ?></label><input id="zip" name="zip" value="<?= e($gym['zip']) ?>"></div>
            <div class="m-field"><label for="city"><?= e(t('Ort')) ?></label><input id="city" name="city" value="<?= e($gym['city']) ?>"></div>
            <div class="m-field"><label for="country"><?= e(t('Land')) ?></label><input id="country" name="country" maxlength="2" value="<?= e($gym['country']) ?>"></div>
        </div>
        <div class="m-field"><label for="website"><?= e(t('Website')) ?></label><input id="website" name="website" value="<?= e($gym['website']) ?>"></div>
        <div class="m-field"><label for="logo"><?= e(t('Logo')) ?></label>
            <?php if ($gym['logo_path'] !== ''): ?><img src="<?= e(upload_url($gym['logo_path'])) ?>" alt="" class="m-avatar m-avatar--big"><?php endif; ?>
            <input id="logo" type="file" name="logo" accept="image/*"></div>
    </div>
    <div class="m-card">
        <h2><?= e(t('Ansprechpartner & Zugang')) ?></h2>
        <div class="m-field"><label for="contact_name"><?= e(t('Name')) ?></label><input id="contact_name" name="contact_name" value="<?= e($gym['contact_name']) ?>"></div>
        <div class="m-grid">
            <div class="m-field"><label for="email"><?= e(t('Kontakt-E-Mail')) ?></label><input id="email" name="email" type="email" value="<?= e($gym['email']) ?>"><?= $err('email') ?></div>
            <div class="m-field"><label for="phone"><?= e(t('Telefon')) ?></label><input id="phone" name="phone" value="<?= e($gym['phone']) ?>"></div>
        </div>
        <div class="m-field"><label for="login_email"><?= e(t('Anmelde-E-Mail *')) ?></label><input id="login_email" name="login_email" type="email" required value="<?= e($gym['login_email']) ?>"><?= $err('login_email') ?></div>
        <div class="m-grid">
            <div class="m-field"><label for="current_password"><?= t('Aktuelles Passwort <small>(nur bei Änderung)</small>') ?></label><input id="current_password" name="current_password" type="password" autocomplete="current-password"><?= $err('current_password') ?></div>
            <div class="m-field"><label for="login_password"><?= e(t('Neues Passwort')) ?></label><input id="login_password" name="login_password" type="password" autocomplete="new-password" minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>"><?= $err('login_password') ?></div>
        </div>
    </div>
    <p><button class="btn btn--primary" type="submit"><?= e(t('Speichern')) ?></button></p>
</form>
