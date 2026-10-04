<?php

use App\Core\Auth;
use App\Models\EventRepo;

/** @var list<array<string,mixed>> $events */
?>
<div class="page-head">
    <h1><?= e(t('Events')) ?></h1>
    <?php if (Auth::canWrite()): ?>
        <div class="page-head__actions">
            <a class="btn btn--ghost" href="<?= e(url('/admin/events/import')) ?>"><?= e(t('Fightcard importieren')) ?></a>
            <a class="btn btn--primary" href="<?= e(url('/admin/events/neu')) ?>"><?= e(t('Neues Event')) ?></a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr>
                <th><?= e(t('Datum')) ?></th>
                <th><?= e(t('Event')) ?></th>
                <th><?= e(t('Typ')) ?></th>
                <th><?= e(t('Ort')) ?></th>
                <th><?= e(t('Status')) ?></th>
                <th><?= e(t('Website')) ?></th>
                <th class="num"><?= e(t('Anmeldungen')) ?></th>
                <th class="num"><?= e(t('Kämpfe')) ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td class="mono"><?= e(format_date_range($ev['starts_on'], $ev['ends_on'])) ?></td>
                    <td>
                        <a class="strong" href="<?= e(url('/admin/events/' . $ev['id'])) ?>"><?= e($ev['name']) ?></a>
                        <?php if ($ev['sport'] !== ''): ?><br><small class="muted"><?= e($ev['sport']) ?></small><?php endif; ?>
                    </td>
                    <td><?= e(t(EventRepo::TYPES[$ev['type']] ?? $ev['type'])) ?></td>
                    <td><?= e(trim($ev['venue_name'] . ' ' . $ev['venue_city'])) ?></td>
                    <td><span class="pill pill--status-<?= e($ev['status']) ?>"><?= e(t(EventRepo::STATUS[$ev['status']] ?? $ev['status'])) ?></span></td>
                    <td>
                        <?php if ((int) $ev['published'] === 1): ?>
                            <a href="<?= e(url('/e/' . $ev['slug'])) ?>" target="_blank" rel="noopener">/e/<?= e($ev['slug']) ?></a>
                        <?php else: ?>
                            <span class="muted"><?= e(t('nicht veröffentlicht')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= (int) $ev['entry_count'] ?></td>
                    <td class="num"><?= (int) $ev['bout_count'] ?></td>
                    <td class="row-actions">
                        <a href="<?= e(url('/admin/events/' . $ev['id'] . '/anmeldungen')) ?>"><?= e(t('Anmeldungen')) ?></a>
                        <a href="<?= e(url('/admin/events/' . $ev['id'] . '/zeitplan')) ?>"><?= e(t('Zeitplan')) ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($events === []): ?>
                <tr><td colspan="9" class="empty"><?= e(t('Noch kein Event angelegt.')) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
