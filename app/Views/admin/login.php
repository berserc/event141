<?php

/**
 * @var array<string,mixed>  $old
 * @var list<array{type:string,message:string}> $flash
 * @var string $appName
 * @var array<string,string> $settings
 */
?>
<main class="login">
    <div class="login__box">
        <h1 class="login__title"><?= e($settings['org_name'] ?? $appName) ?></h1>
        <p class="login__sub"><?= e(t('Event141 Verwaltung – bitte anmelden')) ?></p>

        <?php foreach ($flash as $message): ?>
            <div class="flash flash--<?= e($message['type']) ?>" role="status"><?= e($message['message']) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= e(url('/admin/login')) ?>" class="form">
            <?= csrf_field() ?>

            <div class="field">
                <label for="username"><?= e(t('Benutzername')) ?></label>
                <input id="username" name="username" type="text" autocomplete="username" required
                       autofocus value="<?= e($old['username'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="password"><?= e(t('Passwort')) ?></label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>

            <button class="btn btn--primary btn--block" type="submit"><?= e(t('Anmelden')) ?></button>
        </form>

        <p class="login__back">
            <a href="<?= e(url('/')) ?>"><?= e(t('Zur Website')) ?></a> ·
            <a href="<?= e(url('/gym/login')) ?>"><?= e(t('Gym-Anmeldung')) ?></a>
        </p>
    </div>
</main>
