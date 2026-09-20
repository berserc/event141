<?php

use App\Controllers\EventAdminController;
use App\Core\Auth;
use App\Models\EventRepo;

/**
 * Kopf jeder Event-Seite: Titel, Status-Umschalter, Tabs.
 *
 * @var array<string,mixed> $event
 * @var string              $subtitle (optional)
 */
$subtitle = $subtitle ?? '';
?>
<div class="page-head">
    <div>
        <h1><?= e($event['name']) ?><?= $subtitle !== '' ? ' <small class="muted">– ' . e($subtitle) . '</small>' : '' ?></h1>
        <p class="page-head__sub">
            <?= e(EventRepo::TYPES[$event['type']] ?? $event['type']) ?> ·
            <?= e(format_date_range($event['starts_on'], $event['ends_on'])) ?>
            <?= $event['venue_city'] !== '' ? '· ' . e($event['venue_city']) : '' ?>
            <?php if ((int) $event['published'] === 1): ?>
                · <a href="<?= e(url('/e/' . $event['slug'])) ?>" target="_blank" rel="noopener">Website ansehen ↗</a>
            <?php else: ?>
                · <span class="muted">nicht veröffentlicht</span>
            <?php endif; ?>
        </p>
    </div>

    <div class="page-head__actions">
        <?php if (Auth::canWrite()): ?>
            <form method="post" action="<?= e(url('/admin/events/' . $event['id'] . '/status')) ?>" class="inline status-switch">
                <?= csrf_field() ?>
                <label class="sr-only" for="ev-status">Status</label>
                <select id="ev-status" name="status" onchange="this.form.submit()">
                    <?php foreach (EventRepo::STATUS as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $event['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php else: ?>
            <span class="pill pill--status-<?= e($event['status']) ?>"><?= e(EventRepo::STATUS[$event['status']] ?? $event['status']) ?></span>
        <?php endif; ?>
    </div>
</div>

<?= admin_tabs(EventAdminController::tabs($event)) ?>
