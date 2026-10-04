<?php

use App\Core\Auth;
use App\Models\GymRepo;

/**
 * @var list<array<string,mixed>> $gyms
 * @var string $q
 * @var string $status
 */
?>
<div class="page-head">
    <h1><?= e(t('Gyms & Vereine')) ?></h1>
    <?php if (Auth::canWrite()): ?>
        <div class="page-head__actions">
            <a class="btn btn--primary" href="<?= e(url('/admin/gyms/neu')) ?>"><?= e(t('Neues Gym')) ?></a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <form method="get" class="filters">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Name, Ort, Kontakt …')) ?>">
        <select name="status">
            <option value=""><?= e(t('alle Status')) ?></option>
            <?php foreach (GymRepo::STATUS as $k => $l): ?>
                <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn--sm" type="submit"><?= e(t('Filtern')) ?></button>
        <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/gyms')) ?>"><?= e(t('Zurücksetzen')) ?></a>
    </form>

    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr><th><?= e(t('Logo')) ?></th><th><?= e(t('Gym / Verein')) ?></th><th><?= e(t('Ort')) ?></th><th><?= e(t('Kontakt')) ?></th><th><?= e(t('Login')) ?></th><th>Gym141</th><th class="num"><?= e(t('Sportler')) ?></th><th class="num"><?= e(t('Anmeld.')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($gyms as $g): ?>
                <tr>
                    <td class="col-thumb"><?php if ($g['logo_path'] !== ''): ?><img src="<?= e(upload_url($g['logo_path'])) ?>" alt="" width="40" height="40" loading="lazy"><?php endif; ?></td>
                    <td><a class="strong" href="<?= e(url('/admin/gyms/' . $g['id'])) ?>"><?= e($g['name']) ?></a><?= $g['short_name'] !== '' ? ' <small class="muted">(' . e($g['short_name']) . ')</small>' : '' ?></td>
                    <td><?= e(trim($g['zip'] . ' ' . $g['city'])) ?> <?= $g['country'] !== 'AT' ? e(flag_emoji($g['country'])) : '' ?></td>
                    <td><?= e($g['contact_name']) ?><?= $g['email'] !== '' ? '<br><small>' . mail_link($g['email']) . '</small>' : '' ?></td>
                    <td><?= $g['login_email'] !== '' ? '<small>' . e($g['login_email']) . '</small>' : '<span class="muted">–</span>' ?></td>
                    <td><?= $g['gym141_token'] !== '' ? '<span class="badge badge--ok">' . e(t('verbunden')) . '</span>' : '' ?></td>
                    <td class="num"><?= (int) $g['athlete_count'] ?></td>
                    <td class="num"><?= (int) $g['entry_count'] ?></td>
                    <td>
                        <?php if (Auth::canWrite()): ?>
                            <form method="post" action="<?= e(url('/admin/gyms/' . $g['id'] . '/status')) ?>" class="inline">
                                <?= csrf_field() ?>
                                <select name="status" onchange="this.form.submit()" class="select--inline pill pill--gym-<?= e($g['status']) ?>">
                                    <?php foreach (GymRepo::STATUS as $k => $l): ?>
                                        <option value="<?= e($k) ?>" <?= $g['status'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php else: ?>
                            <span class="pill pill--gym-<?= e($g['status']) ?>"><?= e(t(GymRepo::STATUS[$g['status']] ?? $g['status'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="row-actions">
                        <a href="<?= e(url('/admin/gyms/' . $g['id'])) ?>"><?= e(t('Öffnen')) ?></a>
                        <a href="<?= e(url('/admin/sportler', ['gym' => $g['id']])) ?>"><?= e(t('Sportler')) ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($gyms === []): ?>
                <tr><td colspan="10" class="empty"><?= e(t('Keine Gyms gefunden.')) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
