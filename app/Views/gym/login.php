<?php

/**
 * @var array<string,mixed> $old
 * @var bool                $signup
 */
?>
<div class="m-login">
    <h1>Gym-Anmeldung</h1>
    <p class="lead">Für Gyms und Vereine: eigene Sportler verwalten und zu Events anmelden.</p>

    <form method="post" action="<?= e(url('/gym/login')) ?>" class="m-form m-card">
        <?= csrf_field() ?>
        <div class="m-field"><label for="email">E-Mail</label><input id="email" name="email" type="email" required autofocus autocomplete="username" value="<?= e($old['email'] ?? '') ?>"></div>
        <div class="m-field"><label for="password">Passwort</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
        <button class="btn btn--primary btn--block" type="submit">Anmelden</button>
    </form>

    <?php if ($signup): ?>
        <p>Noch kein Zugang? <a href="<?= e(url('/gym/registrieren')) ?>">Gym jetzt registrieren</a></p>
    <?php endif; ?>
    <p><a href="<?= e(url('/')) ?>">Zur Website</a></p>
</div>
