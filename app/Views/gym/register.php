<?php

use App\Core\Auth;

/**
 * @var array<string,mixed>  $gym
 * @var array<string,string> $errors
 */
$err = static fn (string $f): string => isset($errors[$f]) ? '<p class="m-error">' . e($errors[$f]) . '</p>' : '';
?>
<h1>Gym registrieren</h1>
<p class="lead">Als Gym oder Verein meldet ihr eure Sportler selbst zu Events an. Registriert euch einmal – danach
    Sportler anlegen (oder direkt aus <strong>Gym141</strong> holen) und zu offenen Events anmelden.</p>

<form method="post" action="<?= e(url('/gym/registrieren')) ?>" class="m-form">
    <?= csrf_field() ?>
    <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">

    <div class="m-card">
        <h2>Gym / Verein</h2>
        <div class="m-field"><label for="name">Name *</label><input id="name" name="name" required value="<?= e($gym['name']) ?>"><?= $err('name') ?></div>
        <div class="m-grid">
            <div class="m-field"><label for="short_name">Kurzname</label><input id="short_name" name="short_name" maxlength="12" value="<?= e($gym['short_name']) ?>" placeholder="für Anzeigetafeln"></div>
            <div class="m-field"><label for="country">Land</label><input id="country" name="country" maxlength="2" value="<?= e($gym['country']) ?>"></div>
        </div>
        <div class="m-field"><label for="street">Straße</label><input id="street" name="street" value="<?= e($gym['street']) ?>"></div>
        <div class="m-grid">
            <div class="m-field"><label for="zip">PLZ</label><input id="zip" name="zip" value="<?= e($gym['zip']) ?>"></div>
            <div class="m-field"><label for="city">Ort</label><input id="city" name="city" value="<?= e($gym['city']) ?>"></div>
        </div>
        <div class="m-field"><label for="website">Website</label><input id="website" name="website" value="<?= e($gym['website']) ?>"></div>
    </div>

    <div class="m-card">
        <h2>Ansprechpartner</h2>
        <div class="m-field"><label for="contact_name">Name (Trainer / Obmann)</label><input id="contact_name" name="contact_name" value="<?= e($gym['contact_name']) ?>"></div>
        <div class="m-grid">
            <div class="m-field"><label for="email">Kontakt-E-Mail</label><input id="email" name="email" type="email" value="<?= e($gym['email']) ?>"><?= $err('email') ?></div>
            <div class="m-field"><label for="phone">Telefon</label><input id="phone" name="phone" value="<?= e($gym['phone']) ?>"></div>
        </div>
    </div>

    <div class="m-card">
        <h2>Zugang</h2>
        <div class="m-field"><label for="login_email">Anmelde-E-Mail *</label><input id="login_email" name="login_email" type="email" required value="<?= e($gym['login_email']) ?>"><?= $err('login_email') ?></div>
        <div class="m-grid">
            <div class="m-field"><label for="login_password">Passwort * <small>(mind. <?= Auth::MIN_PASSWORD_LENGTH ?> Zeichen)</small></label><input id="login_password" name="login_password" type="password" required minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>" autocomplete="new-password"><?= $err('login_password') ?></div>
            <div class="m-field"><label for="login_password_confirm">Passwort wiederholen *</label><input id="login_password_confirm" name="login_password_confirm" type="password" required autocomplete="new-password"><?= $err('login_password_confirm') ?></div>
        </div>
    </div>

    <p><button class="btn btn--primary" type="submit">Registrieren</button>
        <a class="btn btn--ghost btn--on-dark" href="<?= e(url('/gym/login')) ?>">Schon registriert? Anmelden</a></p>
</form>
