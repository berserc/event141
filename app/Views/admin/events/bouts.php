<?php

use App\Core\Auth;
use App\Models\EntryRepo;
use App\Models\EventRepo;

/**
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $categories
 * @var list<array<string,mixed>> $bouts
 * @var array<int,list<array<string,mixed>>> $byCategory
 * @var list<array<string,mixed>> $entries    bestaetigte Anmeldungen
 * @var list<array<string,mixed>> $sessions
 * @var list<array<string,mixed>> $venues
 */
$id       = (int) $event['id'];
$isGala   = $event['type'] === 'gala';
$subtitle = $isGala ? 'Fightcard' : 'Kämpfe';
$canWrite = Auth::canWrite();
require __DIR__ . '/_head.php';

$entryOptions = static function (?int $selected = null) use ($entries): string {
    $html = '<option value="">– offen –</option>';
    foreach ($entries as $x) {
        $html .= '<option value="' . (int) $x['id'] . '"' . ($selected === (int) $x['id'] ? ' selected' : '') . '>'
            . e(EntryRepo::label($x)) . ($x['category_name'] !== null ? ' · ' . e($x['category_name']) : '') . '</option>';
    }
    return $html;
};
?>

<?php if ($isGala): ?>
    <div class="card">
        <div class="card__head">
            <h2>Fightcard</h2>
            <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/events/' . $id . '/zeitplan')) ?>">Zeitplan / Ringe</a>
        </div>
        <p class="muted">Kämpfe in Reihenfolge des Abends. Pfeile verschieben innerhalb desselben Abschnitts/Rings.
            Sportler müssen unter <a href="<?= e(url('/admin/events/' . $id . '/anmeldungen')) ?>">Anmeldungen</a> bestätigt sein.</p>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>#</th><th>Kampf</th><th>Rot</th><th>Blau</th><th>Einplanung</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($bouts as $bout): ?>
                    <?php $showMove = true; require __DIR__ . '/_bout-row.php'; ?>
                <?php endforeach; ?>
                <?php if ($bouts === []): ?>
                    <tr><td colspan="7" class="empty">Noch kein Kampf. Unten den ersten Kampf zusammenstellen.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <?php if ($categories === []): ?>
        <div class="notice notice--warn">Noch keine Kategorien – bitte zuerst unter <a href="<?= e(url('/admin/events/' . $id . '/kategorien')) ?>">Kategorien</a> anlegen. Turnierbäume werden je Kategorie erzeugt.</div>
    <?php endif; ?>

    <?php foreach ($categories as $c): ?>
        <?php $list = $byCategory[(int) $c['id']] ?? []; ?>
        <div class="card" id="kat-<?= (int) $c['id'] ?>">
            <div class="card__head">
                <h2><?= e($c['name']) ?> <small class="muted"><?= e(EventRepo::categoryInfo($c)) ?></small></h2>
                <div class="card__actions">
                    <span class="badge"><?= (int) $c['confirmed_count'] ?> bestätigt</span>
                    <?php if ($canWrite && $c['mode'] === 'ko'): ?>
                        <form method="post" action="<?= e(url('/admin/events/' . $id . '/turnierbaum')) ?>" class="inline"
                              <?= $list !== [] ? 'data-confirm="Turnierbaum neu erzeugen? Bestehende Kämpfe und Ergebnisse dieser Kategorie werden ersetzt."' : '' ?>>
                            <?= csrf_field() ?>
                            <input type="hidden" name="category_id" value="<?= (int) $c['id'] ?>">
                            <button class="btn btn--sm <?= $list === [] ? 'btn--primary' : '' ?>" type="submit" <?= (int) $c['confirmed_count'] < 2 ? 'disabled title="mindestens 2 bestätigte Anmeldungen"' : '' ?>>
                                Turnierbaum <?= $list === [] ? 'erzeugen' : 'neu erzeugen' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($list === []): ?>
                <p class="muted">Noch keine Kämpfe. <?= $c['mode'] === 'ko' ? 'Sobald die Anmeldungen bestätigt sind, den Turnierbaum erzeugen.' : 'Kämpfe unten manuell anlegen.' ?></p>
            <?php else: ?>
                <div class="table-scroll">
                    <table class="table table--compact">
                        <thead><tr><th>#</th><th>Runde</th><th>Rot</th><th>Blau</th><th>Einplanung</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($list as $bout): ?>
                            <?php $showMove = false; require __DIR__ . '/_bout-row.php'; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php $frei = $byCategory[0] ?? []; ?>
    <?php if ($frei !== []): ?>
        <div class="card">
            <div class="card__head"><h2>Kämpfe ohne Kategorie</h2></div>
            <div class="table-scroll">
                <table class="table table--compact">
                    <thead><tr><th>#</th><th>Kampf</th><th>Rot</th><th>Blau</th><th>Einplanung</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($frei as $bout): ?>
                        <?php $showMove = true; require __DIR__ . '/_bout-row.php'; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($canWrite): ?>
    <div class="form-grid">
        <form method="post" action="<?= e(url('/admin/events/' . $id . '/kaempfe')) ?>" class="card form">
            <?= csrf_field() ?>
            <div class="card__head"><h2><?= $isGala ? 'Kampf hinzufügen' : 'Zusatzkampf manuell anlegen' ?></h2></div>

            <div class="field-row">
                <div class="field field--grow">
                    <label for="red_entry_id">Rote Ecke</label>
                    <select id="red_entry_id" name="red_entry_id"><?= $entryOptions() ?></select>
                </div>
                <div class="field field--grow">
                    <label for="blue_entry_id">Blaue Ecke</label>
                    <select id="blue_entry_id" name="blue_entry_id"><?= $entryOptions() ?></select>
                </div>
            </div>
            <div class="field-row">
                <div class="field field--grow">
                    <label for="b-title">Bezeichnung</label>
                    <input id="b-title" name="title" placeholder="Hauptkampf, Titelkampf, Superfight …">
                </div>
                <?php if ($categories !== []): ?>
                    <div class="field field--grow">
                        <label for="b-cat">Kategorie</label>
                        <select id="b-cat" name="category_id">
                            <option value="">– keine –</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <div class="field-row">
                <div class="field field--xs"><label for="b-rounds">Runden</label><input id="b-rounds" name="rounds" type="number" min="1" value="3"></div>
                <div class="field field--xs"><label for="b-min">Minuten</label><input id="b-min" name="round_minutes" inputmode="decimal" value="2"></div>
                <div class="field field--xs"><label for="b-time">Uhrzeit</label><input id="b-time" name="scheduled_time" type="time"></div>
                <div class="field field--grow">
                    <label for="b-session">Abschnitt</label>
                    <select id="b-session" name="session_id">
                        <option value="">– später –</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e(format_date($s['day_date'])) ?> · <?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field field--sm">
                    <label for="b-venue">Ring</label>
                    <select id="b-venue" name="venue_id">
                        <option value="">– später –</option>
                        <?php foreach ($venues as $v): ?>
                            <option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="field field--sm"><label for="b-block">Block</label><input id="b-block" name="block" list="blocks" placeholder="Main Card">
                    <datalist id="blocks"><option value="Main Fight"></option><option value="Main Card"></option><option value="Prelims"></option></datalist></div>
                <div class="field field--sm"><label for="b-style">Disziplin</label><input id="b-style" name="style" placeholder="K-1"></div>
                <div class="field field--grow"><label for="b-weight">Gewichtsklasse</label><input id="b-weight" name="weight_label" placeholder="-75 kg"></div>
                <div class="field field--xs"><label for="b-minutes">Dauer (Min.)</label><input id="b-minutes" name="minutes" type="number" min="0" value="0"></div>
            </div>
            <div class="field"><label for="b-note">Notiz</label><input id="b-note" name="note"></div>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit">Kampf anlegen</button>
            </div>
        </form>

        <form method="post" action="<?= e(url('/admin/events/' . $id . '/kaempfe')) ?>" class="card form">
            <?= csrf_field() ?>
            <input type="hidden" name="is_break" value="1">
            <div class="card__head"><h2>Pause einfügen</h2></div>
            <div class="field"><label for="p-title">Bezeichnung</label><input id="p-title" name="title" value="Pause" placeholder="Pause, Showeinlage, Siegerehrung …"></div>
            <div class="field-row">
                <div class="field field--xs"><label for="p-time">Uhrzeit</label><input id="p-time" name="scheduled_time" type="time"></div>
                <div class="field field--grow">
                    <label for="p-session">Abschnitt</label>
                    <select id="p-session" name="session_id">
                        <option value="">– später –</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e(format_date($s['day_date'])) ?> · <?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field field--sm">
                    <label for="p-venue">Ring</label>
                    <select id="p-venue" name="venue_id">
                        <option value="">– alle –</option>
                        <?php foreach ($venues as $v): ?>
                            <option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="field field--grow"><label for="p-note">Dauer / Hinweis</label><input id="p-note" name="note" placeholder="z. B. 20 Min."></div>
                <div class="field field--xs"><label for="p-minutes">Minuten</label><input id="p-minutes" name="minutes" type="number" min="0" value="0"></div>
            </div>
            <div class="form-actions"><button class="btn" type="submit">Pause einfügen</button></div>
        </form>
    </div>
<?php endif; ?>
