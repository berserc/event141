<?php

use App\Core\Auth;

/**
 * @var array<string,mixed>  $user
 * @var array<string,string> $errors
 */
$err = static fn (string $f): string => isset($errors[$f]) ? '<p class="field__error">' . e($errors[$f]) . '</p>' : '';
?>
<div class="page-head">
    <h1>Mein Konto</h1>
    <p class="page-head__sub"><?= e($user['username']) ?> · <?= e(Auth::ROLES[$user['role']] ?? $user['role']) ?></p>
</div>

<div class="form-grid">
    <form method="post" action="<?= e(url('/admin/profil')) ?>" class="card form">
        <?= csrf_field() ?>
        <h2>Passwort ändern</h2>

        <div class="field">
            <label for="current_password">Aktuelles Passwort</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
            <?= $err('current_password') ?>
        </div>

        <div class="field">
            <label for="new_password">Neues Passwort</label>
            <input id="new_password" name="new_password" type="password" autocomplete="new-password" required minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>">
            <?= $err('new_password') ?>
        </div>

        <div class="field">
            <label for="new_password_confirm">Neues Passwort wiederholen</label>
            <input id="new_password_confirm" name="new_password_confirm" type="password" autocomplete="new-password" required>
            <?= $err('new_password_confirm') ?>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">Passwort speichern</button>
        </div>
    </form>
</div>
