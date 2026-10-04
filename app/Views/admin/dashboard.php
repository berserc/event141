<?php

use App\Core\Auth;
use App\Models\EntryRepo;
use App\Models\EventRepo;

/**
 * @var array<string,int>         $stats
 * @var list<array<string,mixed>> $upcoming
 * @var list<array<string,mixed>> $running
 * @var list<array<string,mixed>> $recentGyms
 * @var list<array<string,mixed>> $recentEntries
 * @var array<string,mixed>|null  $authUser
 */
?>
<div class="page-head">
    <h1><?= e(t('Übersicht')) ?></h1>
    <p class="page-head__sub">
        <?= t(
            'Angemeldet als <strong>%s</strong> (%s)',
            e($authUser['name'] !== '' ? $authUser['name'] : $authUser['username']),
            e(t(Auth::ROLES[$authUser['role']] ?? $authUser['role']))
        ) ?>
    </p>
    <?php if (Auth::canWrite()): ?>
        <div class="page-head__actions">
            <a class="btn btn--primary" href="<?= e(url('/admin/events/neu')) ?>"><?= e(t('Neues Event')) ?></a>
        </div>
    <?php endif; ?>
</div>

<?php if ($stats['gymsNew'] > 0): ?>
    <div class="notice notice--warn">
        <?= t('<strong>%d</strong> neue Gym-Registrierung(en) warten auf Bestätigung.', (int) $stats['gymsNew']) ?>
        <a href="<?= e(url('/admin/gyms', ['status' => 'neu'])) ?>"><?= e(t('Jetzt prüfen')) ?></a>
    </div>
<?php endif; ?>

<div class="stat-grid">
    <a class="stat stat--info" href="<?= e(url('/admin/events')) ?>">
        <span class="stat__value"><?= (int) $stats['events'] ?></span>
        <span class="stat__label"><?= e(t('Events')) ?></span>
    </a>
    <a class="stat" href="<?= e(url('/admin/gyms')) ?>">
        <span class="stat__value"><?= (int) $stats['gyms'] ?></span>
        <span class="stat__label"><?= e(t('Gyms & Vereine')) ?></span>
    </a>
    <a class="stat" href="<?= e(url('/admin/sportler')) ?>">
        <span class="stat__value"><?= (int) $stats['athletes'] ?></span>
        <span class="stat__label"><?= e(t('Sportler')) ?></span>
    </a>
    <div class="stat <?= $stats['entries'] > 0 ? 'stat--warn' : '' ?>">
        <span class="stat__value"><?= (int) $stats['entries'] ?></span>
        <span class="stat__label"><?= e(t('unbestätigte Anmeldungen')) ?></span>
    </div>
</div>

<?php foreach ($running as $ev): ?>
    <div class="notice notice--ok">
        <strong><?= e(t('Läuft gerade:')) ?></strong> <?= e($ev['name']) ?> –
        <a href="<?= e(url('/admin/events/' . $ev['id'] . '/zeitplan')) ?>"><?= e(t('Zeitplan')) ?></a> ·
        <a href="<?= e(url('/admin/events/' . $ev['id'] . '/ergebnisse')) ?>"><?= e(t('Ergebnisse')) ?></a>
    </div>
<?php endforeach; ?>

<div class="form-grid">
    <div class="card">
        <div class="card__head"><h2><?= e(t('Nächste Events')) ?></h2></div>
        <?php if ($upcoming === []): ?>
            <p class="muted"><?= t('Noch kein Event geplant. <a href="%s">Jetzt anlegen</a>.', e(url('/admin/events/neu'))) ?></p>
        <?php else: ?>
            <div class="table-scroll">
                <table class="table table--compact">
                    <thead><tr><th><?= e(t('Datum')) ?></th><th><?= e(t('Event')) ?></th><th><?= e(t('Typ')) ?></th><th><?= e(t('Status')) ?></th><th class="num"><?= e(t('Anmeld.')) ?></th><th class="num"><?= e(t('Kämpfe')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming as $ev): ?>
                        <tr>
                            <td class="mono"><?= e(format_date_range($ev['starts_on'], $ev['ends_on'])) ?></td>
                            <td><a class="strong" href="<?= e(url('/admin/events/' . $ev['id'])) ?>"><?= e($ev['name']) ?></a></td>
                            <td><?= e(t(EventRepo::TYPES[$ev['type']] ?? $ev['type'])) ?></td>
                            <td><span class="pill pill--status-<?= e($ev['status']) ?>"><?= e(t(EventRepo::STATUS[$ev['status']] ?? $ev['status'])) ?></span></td>
                            <td class="num"><?= (int) $ev['entry_count'] ?></td>
                            <td class="num"><?= (int) $ev['bout_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card__head"><h2><?= e(t('Letzte Anmeldungen')) ?></h2></div>
        <?php if ($recentEntries === []): ?>
            <p class="muted"><?= e(t('Noch keine Anmeldungen.')) ?></p>
        <?php else: ?>
            <ul class="log-list">
                <?php foreach ($recentEntries as $r): ?>
                    <li>
                        <time><?= e(format_datetime((string) $r['created_at'])) ?></time>
                        <strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong> (<?= e($r['gym_name']) ?>)
                        → <a href="<?= e(url('/admin/events/' . $r['event_id'] . '/anmeldungen')) ?>"><?= e($r['event_name']) ?></a>
                        <?php if ($r['category_name'] !== null): ?><em><?= e($r['category_name']) ?></em><?php endif; ?>
                        <span class="pill pill--entry-<?= e($r['status']) ?>"><?= e(t(EntryRepo::STATUS[$r['status']] ?? $r['status'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card__head"><h2><?= e(t('Neue Gyms')) ?></h2></div>
        <?php if ($recentGyms === []): ?>
            <p class="muted"><?= e(t('Noch keine Gyms registriert.')) ?></p>
        <?php else: ?>
            <ul class="log-list">
                <?php foreach ($recentGyms as $g): ?>
                    <li>
                        <time><?= e(format_datetime((string) $g['created_at'])) ?></time>
                        <a href="<?= e(url('/admin/gyms/' . $g['id'])) ?>"><strong><?= e($g['name']) ?></strong></a>
                        <?= $g['city'] !== '' ? '· ' . e($g['city']) : '' ?>
                        <?php if ($g['status'] === 'neu'): ?><span class="badge badge--warn"><?= e(t('neu')) ?></span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
