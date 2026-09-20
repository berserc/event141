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
    <h1>Übersicht</h1>
    <p class="page-head__sub">
        Angemeldet als <strong><?= e($authUser['name'] !== '' ? $authUser['name'] : $authUser['username']) ?></strong>
        (<?= e(Auth::ROLES[$authUser['role']] ?? $authUser['role']) ?>)
    </p>
    <?php if (Auth::canWrite()): ?>
        <div class="page-head__actions">
            <a class="btn btn--primary" href="<?= e(url('/admin/events/neu')) ?>">Neues Event</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($stats['gymsNew'] > 0): ?>
    <div class="notice notice--warn">
        <strong><?= (int) $stats['gymsNew'] ?></strong> neue Gym-Registrierung(en) warten auf Bestätigung.
        <a href="<?= e(url('/admin/gyms', ['status' => 'neu'])) ?>">Jetzt prüfen</a>
    </div>
<?php endif; ?>

<div class="stat-grid">
    <a class="stat stat--info" href="<?= e(url('/admin/events')) ?>">
        <span class="stat__value"><?= (int) $stats['events'] ?></span>
        <span class="stat__label">Events</span>
    </a>
    <a class="stat" href="<?= e(url('/admin/gyms')) ?>">
        <span class="stat__value"><?= (int) $stats['gyms'] ?></span>
        <span class="stat__label">Gyms &amp; Vereine</span>
    </a>
    <a class="stat" href="<?= e(url('/admin/sportler')) ?>">
        <span class="stat__value"><?= (int) $stats['athletes'] ?></span>
        <span class="stat__label">Sportler</span>
    </a>
    <div class="stat <?= $stats['entries'] > 0 ? 'stat--warn' : '' ?>">
        <span class="stat__value"><?= (int) $stats['entries'] ?></span>
        <span class="stat__label">unbestätigte Anmeldungen</span>
    </div>
</div>

<?php foreach ($running as $ev): ?>
    <div class="notice notice--ok">
        <strong>Läuft gerade:</strong> <?= e($ev['name']) ?> –
        <a href="<?= e(url('/admin/events/' . $ev['id'] . '/zeitplan')) ?>">Zeitplan</a> ·
        <a href="<?= e(url('/admin/events/' . $ev['id'] . '/ergebnisse')) ?>">Ergebnisse</a>
    </div>
<?php endforeach; ?>

<div class="form-grid">
    <div class="card">
        <div class="card__head"><h2>Nächste Events</h2></div>
        <?php if ($upcoming === []): ?>
            <p class="muted">Noch kein Event geplant. <a href="<?= e(url('/admin/events/neu')) ?>">Jetzt anlegen</a>.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table class="table table--compact">
                    <thead><tr><th>Datum</th><th>Event</th><th>Typ</th><th>Status</th><th class="num">Anmeld.</th><th class="num">Kämpfe</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming as $ev): ?>
                        <tr>
                            <td class="mono"><?= e(format_date_range($ev['starts_on'], $ev['ends_on'])) ?></td>
                            <td><a class="strong" href="<?= e(url('/admin/events/' . $ev['id'])) ?>"><?= e($ev['name']) ?></a></td>
                            <td><?= e(EventRepo::TYPES[$ev['type']] ?? $ev['type']) ?></td>
                            <td><span class="pill pill--status-<?= e($ev['status']) ?>"><?= e(EventRepo::STATUS[$ev['status']] ?? $ev['status']) ?></span></td>
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
        <div class="card__head"><h2>Letzte Anmeldungen</h2></div>
        <?php if ($recentEntries === []): ?>
            <p class="muted">Noch keine Anmeldungen.</p>
        <?php else: ?>
            <ul class="log-list">
                <?php foreach ($recentEntries as $r): ?>
                    <li>
                        <time><?= e(format_datetime((string) $r['created_at'])) ?></time>
                        <strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong> (<?= e($r['gym_name']) ?>)
                        → <a href="<?= e(url('/admin/events/' . $r['event_id'] . '/anmeldungen')) ?>"><?= e($r['event_name']) ?></a>
                        <?php if ($r['category_name'] !== null): ?><em><?= e($r['category_name']) ?></em><?php endif; ?>
                        <span class="pill pill--entry-<?= e($r['status']) ?>"><?= e(EntryRepo::STATUS[$r['status']] ?? $r['status']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card__head"><h2>Neue Gyms</h2></div>
        <?php if ($recentGyms === []): ?>
            <p class="muted">Noch keine Gyms registriert.</p>
        <?php else: ?>
            <ul class="log-list">
                <?php foreach ($recentGyms as $g): ?>
                    <li>
                        <time><?= e(format_datetime((string) $g['created_at'])) ?></time>
                        <a href="<?= e(url('/admin/gyms/' . $g['id'])) ?>"><strong><?= e($g['name']) ?></strong></a>
                        <?= $g['city'] !== '' ? '· ' . e($g['city']) : '' ?>
                        <?php if ($g['status'] === 'neu'): ?><span class="badge badge--warn">neu</span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
