<?php

use App\Core\Auth;
use App\Models\BoutRepo;

/**
 * Zeitplan: Tag → Abschnitt → Wettkampfstätten nebeneinander.
 *
 * @var array<string,mixed>       $event
 * @var array{days:list<array<string,mixed>>,open:list<array<string,mixed>>} $schedule
 * @var list<array<string,mixed>> $sessions
 * @var list<array<string,mixed>> $venues
 * @var list<array<string,mixed>> $categories
 */
$id       = (int) $event['id'];
$subtitle = 'Zeitplan';
$canWrite = Auth::canWrite();
require __DIR__ . '/_head.php';

$placeForm = static function (array $bout) use ($id, $sessions, $venues, $csrf): string {
    $html = '<form method="post" action="' . e(url('/admin/events/' . $id . '/zeitplan/einplanen')) . '" class="place-form">'
        . '<input type="hidden" name="csrf_token" value="' . e($csrf) . '">'
        . '<input type="hidden" name="bout_id" value="' . (int) $bout['id'] . '">'
        . '<select name="session_id" aria-label="Abschnitt"><option value="">– Abschnitt –</option>';
    foreach ($sessions as $s) {
        $html .= '<option value="' . (int) $s['id'] . '"' . ((int) $bout['session_id'] === (int) $s['id'] ? ' selected' : '') . '>'
            . e(format_date($s['day_date']) . ' · ' . $s['name']) . '</option>';
    }
    $html .= '</select><select name="venue_id" aria-label="Ring"><option value="">– Ring –</option>';
    foreach ($venues as $v) {
        $html .= '<option value="' . (int) $v['id'] . '"' . ((int) $bout['venue_id'] === (int) $v['id'] ? ' selected' : '') . '>' . e($v['name']) . '</option>';
    }
    $html .= '</select><input type="time" name="scheduled_time" value="' . e($bout['scheduled_time']) . '" aria-label="Uhrzeit">'
        . '<button class="btn btn--sm" type="submit">OK</button></form>';
    return $html;
};
?>

<div class="card">
    <form method="post" action="<?= e(url('/admin/events/' . $id . '/live-modus')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <strong>Zeitplan &amp; Live-Modus</strong>
        <span class="muted">Beginn <?= e($event['start_time'] ?: '–') ?> · Standard <?= (int) $event['default_bout_minutes'] ?> Min. je Kampf, <?= (int) $event['default_break_minutes'] ?> Min. je Pause (<a href="<?= e(url('/admin/events/' . $id)) ?>">ändern</a>)</span>
        <?php if ((int) $event['live_mode'] === 1): ?>
            <span class="pill pill--status-laufend">Live-Modus an</span>
            <button class="btn btn--sm" type="submit" name="live_mode" value="0">Live-Modus ausschalten</button>
        <?php else: ?>
            <button class="btn btn--sm btn--primary" type="submit" name="live_mode" value="1">Live-Modus einschalten</button>
        <?php endif; ?>
    </form>
    <p class="field__hint">🕒 Zeiten = Beginn des Abschnitts + Dauer der Kämpfe davor. Im Live-Modus zählen die echten Start-/Endzeiten – überzieht ein Kampf, rücken alle folgenden automatisch nach hinten (auch auf der Website).</p>
</div>

<?php if ($venues === [] || $sessions === []): ?>
    <div class="notice notice--warn">
        Für den Zeitplan braucht es mindestens einen <strong>Abschnitt</strong> und eine <strong>Wettkampfstätte</strong> –
        beides unter <a href="<?= e(url('/admin/events/' . $id . '/aufbau')) ?>">Aufbau</a> anlegen.
    </div>
<?php endif; ?>

<?php if ($schedule['open'] !== []): ?>
    <div class="card">
        <div class="card__head">
            <h2>Nicht eingeplant <span class="badge badge--danger"><?= count($schedule['open']) ?></span></h2>
        </div>

        <?php if ($canWrite && $venues !== [] && $sessions !== []): ?>
            <form method="post" action="<?= e(url('/admin/events/' . $id . '/zeitplan/verteilen')) ?>" class="inline-form distribute-form">
                <?= csrf_field() ?>
                <input type="hidden" name="only_open" value="1">
                <div class="field field--grow">
                    <label>Automatisch verteilen auf</label>
                    <select name="session_id" required>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e(format_date($s['day_date'])) ?> · <?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Wettkampfstätten (reihum)</label>
                    <div class="checkbox-grid checkbox-grid--inline">
                        <?php foreach ($venues as $v): ?>
                            <label class="check"><input type="checkbox" name="venue_ids[]" value="<?= (int) $v['id'] ?>" checked> <?= e($v['name']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($categories !== []): ?>
                    <div class="field field--sm">
                        <label>nur Kategorie</label>
                        <select name="category_id">
                            <option value="">alle</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field field--xs">
                        <label>nur Runde</label>
                        <input name="round_no" type="number" min="1" placeholder="alle">
                    </div>
                <?php endif; ?>
                <button class="btn btn--primary" type="submit">Verteilen</button>
            </form>
            <p class="field__hint">Tipp bei Turnieren: Runde 1 auf „Vormittag“, Halbfinale/Finale auf „Abend“ verteilen – jede Runde einzeln.</p>
        <?php endif; ?>

        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th>#</th><th>Kampf</th><th>Rot</th><th>Blau</th><th>Einplanen</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($schedule['open'] as $bout): ?>
                    <?php if ($canWrite): ?>
                        <tr class="bout-row">
                            <td class="num mono"><a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $bout['id'])) ?>">#<?= (int) $bout['bout_no'] ?></a></td>
                            <td><small><?= e($bout['category_name'] ?? ($bout['title'] ?: '')) ?><?= $bout['round_label'] !== '' ? ' · ' . e($bout['round_label']) : '' ?></small></td>
                            <td class="corner corner--red"><?= e(BoutRepo::cornerName($bout, 'red')) ?: '<span class="muted">offen</span>' ?></td>
                            <td class="corner corner--blue"><?= e(BoutRepo::cornerName($bout, 'blue')) ?: '<span class="muted">offen</span>' ?></td>
                            <td><?= $placeForm($bout) ?></td>
                            <td><span class="pill pill--bout-<?= e($bout['status']) ?>"><?= e(BoutRepo::STATUS[$bout['status']] ?? $bout['status']) ?></span></td>
                            <td class="row-actions"><a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $bout['id'])) ?>">Öffnen</a></td>
                        </tr>
                    <?php else: ?>
                        <?php $showPlace = false; require __DIR__ . '/_bout-row.php'; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($schedule['days'] as $day): ?>
    <h2 class="schedule-day"><?= e(format_date_long($day['day_date'])) ?><?= $day['label'] !== '' ? ' – ' . e($day['label']) : '' ?></h2>

    <?php if ($day['sessions'] === []): ?>
        <p class="muted">Keine Abschnitte an diesem Tag.</p>
    <?php endif; ?>

    <?php foreach ($day['sessions'] as $s): ?>
        <div class="card session-card">
            <div class="card__head">
                <h3><?= e($s['name']) ?> <small class="muted"><?= e($s['starts_at']) ?><?= $s['ends_at'] !== '' ? '–' . e($s['ends_at']) : '' ?></small></h3>
            </div>

            <div class="venue-grid" style="--venues:<?= max(1, count($s['venues'])) ?>">
                <?php foreach ($s['venues'] as $v): ?>
                    <div class="venue-col">
                        <h4 class="venue-col__head" style="border-color:<?= e($v['color'] ?: '#e63946') ?>">
                            <?= e($v['name']) ?>
                            <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/events/' . $id . '/ring/' . $v['id'], ['session' => $s['id']])) ?>" title="Ringansicht">Ring ↗</a>
                        </h4>
                        <?php if ($v['bouts'] === []): ?>
                            <p class="muted"><small>keine Kämpfe</small></p>
                        <?php endif; ?>
                        <ol class="venue-list">
                            <?php foreach ($v['bouts'] as $bout): ?>
                                <li class="venue-bout venue-bout--<?= e($bout['status']) ?><?= (int) $bout['is_break'] === 1 ? ' venue-bout--break' : '' ?>">
                                    <?php if ((int) $bout['is_break'] === 1): ?>
                                        <em>⏸ <?= e($bout['title'] ?: 'Pause') ?></em>
                                    <?php else: ?>
                                        <a class="venue-bout__no" href="<?= e(url('/admin/events/' . $id . '/kampf/' . $bout['id'])) ?>">#<?= (int) $bout['bout_no'] ?></a>
                                        <?php if (isset($times[(int) $bout['id']])): ?><small class="mono" title="voraussichtlicher Beginn">🕒 <?= e($times[(int) $bout['id']]['label']) ?></small><?php endif; ?>
                                        <span class="venue-bout__names">
                                            <span class="corner corner--red<?= $bout['winner'] === 'red' ? ' is-winner' : '' ?>"><?= e(BoutRepo::cornerName($bout, 'red')) ?: '<span class="muted">offen</span>' ?></span>
                                            <span class="muted">vs</span>
                                            <span class="corner corner--blue<?= $bout['winner'] === 'blue' ? ' is-winner' : '' ?>"><?= e(BoutRepo::cornerName($bout, 'blue')) ?: '<span class="muted">offen</span>' ?></span>
                                        </span>
                                        <small class="muted"><?= e($bout['category_name'] ?? $bout['title']) ?><?= $bout['round_label'] !== '' ? ' · ' . e($bout['round_label']) : '' ?></small>
                                    <?php endif; ?>
                                    <?php if ($bout['status'] !== 'geplant'): ?>
                                        <span class="pill pill--bout-<?= e($bout['status']) ?>"><?= e(BoutRepo::STATUS[$bout['status']] ?? $bout['status']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($canWrite): ?>
                                        <form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $bout['id'] . '/verschieben')) ?>" class="inline venue-bout__move">
                                            <?= csrf_field() ?>
                                            <button class="linklike" name="dir" value="up" type="submit" title="nach oben">▲</button>
                                            <button class="linklike" name="dir" value="down" type="submit" title="nach unten">▼</button>
                                        </form>
                                        <details class="venue-bout__place">
                                            <summary title="umplanen">⇄</summary>
                                            <?= $placeForm($bout) ?>
                                        </details>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>

<?php if ($canWrite && $schedule['days'] !== []): ?>
    <form method="post" action="<?= e(url('/admin/events/' . $id . '/zeitplan/nummerieren')) ?>" data-confirm="Alle Kampfnummern nach der Zeitplan-Reihenfolge neu vergeben?">
        <?= csrf_field() ?>
        <button class="btn btn--sm btn--ghost" type="submit">Kampfnummern nach Zeitplan neu vergeben</button>
    </form>
<?php endif; ?>
