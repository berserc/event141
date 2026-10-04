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
$subtitle = $isGala ? t('Fightcard') : t('Kämpfe');
$canWrite = Auth::canWrite();
require __DIR__ . '/_head.php';

$entryOptions = static function (?int $selected = null) use ($entries): string {
    $html = '<option value="">' . e(t('– offen –')) . '</option>';
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
            <h2><?= e(t('Fightcard')) ?></h2>
            <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/events/' . $id . '/zeitplan')) ?>"><?= e(t('Zeitplan / Ringe')) ?></a>
        </div>
        <p class="muted"><?= t('Kämpfe in Reihenfolge des Abends. Pfeile verschieben innerhalb desselben Abschnitts/Rings. Sportler müssen unter <a href="%s">Anmeldungen</a> bestätigt sein.', e(url('/admin/events/' . $id . '/anmeldungen'))) ?></p>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>#</th><th><?= e(t('Kampf')) ?></th><th><?= e(t('Rot')) ?></th><th><?= e(t('Blau')) ?></th><th><?= e(t('Einplanung')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($bouts as $bout): ?>
                    <?php $showMove = true; require __DIR__ . '/_bout-row.php'; ?>
                <?php endforeach; ?>
                <?php if ($bouts === []): ?>
                    <tr><td colspan="7" class="empty"><?= e(t('Noch kein Kampf. Unten den ersten Kampf zusammenstellen.')) ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <?php if ($categories === []): ?>
        <div class="notice notice--warn"><?= t('Noch keine Kategorien – bitte zuerst unter <a href="%s">Kategorien</a> anlegen. Turnierbäume werden je Kategorie erzeugt.', e(url('/admin/events/' . $id . '/kategorien'))) ?></div>
    <?php endif; ?>

    <?php foreach ($categories as $c): ?>
        <?php $list = $byCategory[(int) $c['id']] ?? []; ?>
        <div class="card" id="kat-<?= (int) $c['id'] ?>">
            <div class="card__head">
                <h2><?= e($c['name']) ?> <small class="muted"><?= e(EventRepo::categoryInfo($c)) ?></small></h2>
                <div class="card__actions">
                    <span class="badge"><?= e(t('%d bestätigt', (int) $c['confirmed_count'])) ?></span>
                    <?php if ($canWrite && $c['mode'] === 'ko'): ?>
                        <form method="post" action="<?= e(url('/admin/events/' . $id . '/turnierbaum')) ?>" class="inline"
                              <?= $list !== [] ? 'data-confirm="' . e(t('Turnierbaum neu erzeugen? Bestehende Kämpfe und Ergebnisse dieser Kategorie werden ersetzt.')) . '"' : '' ?>>
                            <?= csrf_field() ?>
                            <input type="hidden" name="category_id" value="<?= (int) $c['id'] ?>">
                            <button class="btn btn--sm <?= $list === [] ? 'btn--primary' : '' ?>" type="submit" <?= (int) $c['confirmed_count'] < 2 ? 'disabled title="' . e(t('mindestens 2 bestätigte Anmeldungen')) . '"' : '' ?>>
                                <?= e($list === [] ? t('Turnierbaum erzeugen') : t('Turnierbaum neu erzeugen')) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($list === []): ?>
                <p class="muted"><?= e(t('Noch keine Kämpfe.')) ?> <?= e($c['mode'] === 'ko' ? t('Sobald die Anmeldungen bestätigt sind, den Turnierbaum erzeugen.') : t('Kämpfe unten manuell anlegen.')) ?></p>
            <?php else: ?>
                <div class="table-scroll">
                    <table class="table table--compact">
                        <thead><tr><th>#</th><th><?= e(t('Runde')) ?></th><th><?= e(t('Rot')) ?></th><th><?= e(t('Blau')) ?></th><th><?= e(t('Einplanung')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead>
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
            <div class="card__head"><h2><?= e(t('Kämpfe ohne Kategorie')) ?></h2></div>
            <div class="table-scroll">
                <table class="table table--compact">
                    <thead><tr><th>#</th><th><?= e(t('Kampf')) ?></th><th><?= e(t('Rot')) ?></th><th><?= e(t('Blau')) ?></th><th><?= e(t('Einplanung')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead>
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
            <div class="card__head"><h2><?= e($isGala ? t('Kampf hinzufügen') : t('Zusatzkampf manuell anlegen')) ?></h2></div>

            <div class="field-row">
                <div class="field field--grow">
                    <label for="red_entry_id"><?= e(t('Rote Ecke')) ?></label>
                    <select id="red_entry_id" name="red_entry_id"><?= $entryOptions() ?></select>
                </div>
                <div class="field field--grow">
                    <label for="blue_entry_id"><?= e(t('Blaue Ecke')) ?></label>
                    <select id="blue_entry_id" name="blue_entry_id"><?= $entryOptions() ?></select>
                </div>
            </div>
            <div class="field-row">
                <div class="field field--grow">
                    <label for="b-title"><?= e(t('Bezeichnung')) ?></label>
                    <input id="b-title" name="title" placeholder="<?= e(t('Hauptkampf, Titelkampf, Superfight …')) ?>">
                </div>
                <?php if ($categories !== []): ?>
                    <div class="field field--grow">
                        <label for="b-cat"><?= e(t('Kategorie')) ?></label>
                        <select id="b-cat" name="category_id">
                            <option value=""><?= e(t('– keine –')) ?></option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <div class="field-row">
                <div class="field field--xs"><label for="b-rounds"><?= e(t('Runden')) ?></label><input id="b-rounds" name="rounds" type="number" min="1" value="3"></div>
                <div class="field field--xs"><label for="b-min"><?= e(t('Minuten')) ?></label><input id="b-min" name="round_minutes" inputmode="decimal" value="2"></div>
                <div class="field field--xs"><label for="b-time"><?= e(t('Uhrzeit')) ?></label><input id="b-time" name="scheduled_time" type="time"></div>
                <div class="field field--grow">
                    <label for="b-session"><?= e(t('Abschnitt')) ?></label>
                    <select id="b-session" name="session_id">
                        <option value=""><?= e(t('– später –')) ?></option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e(format_date($s['day_date'])) ?> · <?= e(t($s['name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field field--sm">
                    <label for="b-venue"><?= e(t('Ring')) ?></label>
                    <select id="b-venue" name="venue_id">
                        <option value=""><?= e(t('– später –')) ?></option>
                        <?php foreach ($venues as $v): ?>
                            <option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="field field--sm"><label for="b-block"><?= e(t('Block')) ?></label><input id="b-block" name="block" list="blocks" placeholder="Main Card">
                    <datalist id="blocks"><option value="Main Fight"></option><option value="Main Card"></option><option value="Prelims"></option></datalist></div>
                <div class="field field--sm"><label for="b-style"><?= e(t('Disziplin')) ?></label><input id="b-style" name="style" placeholder="K-1"></div>
                <div class="field field--grow"><label for="b-weight"><?= e(t('Gewichtsklasse')) ?></label><input id="b-weight" name="weight_label" placeholder="-75 kg"></div>
                <div class="field field--xs"><label for="b-minutes"><?= e(t('Dauer (Min.)')) ?></label><input id="b-minutes" name="minutes" type="number" min="0" value="0"></div>
            </div>
            <div class="field"><label for="b-note"><?= e(t('Notiz')) ?></label><input id="b-note" name="note"></div>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit"><?= e(t('Kampf anlegen')) ?></button>
            </div>
        </form>

        <form method="post" action="<?= e(url('/admin/events/' . $id . '/kaempfe')) ?>" class="card form">
            <?= csrf_field() ?>
            <input type="hidden" name="is_break" value="1">
            <div class="card__head"><h2><?= e(t('Pause einfügen')) ?></h2></div>
            <div class="field"><label for="p-title"><?= e(t('Bezeichnung')) ?></label><input id="p-title" name="title" value="Pause" placeholder="<?= e(t('Pause, Showeinlage, Siegerehrung …')) ?>"></div>
            <div class="field-row">
                <div class="field field--xs"><label for="p-time"><?= e(t('Uhrzeit')) ?></label><input id="p-time" name="scheduled_time" type="time"></div>
                <div class="field field--grow">
                    <label for="p-session"><?= e(t('Abschnitt')) ?></label>
                    <select id="p-session" name="session_id">
                        <option value=""><?= e(t('– später –')) ?></option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e(format_date($s['day_date'])) ?> · <?= e(t($s['name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field field--sm">
                    <label for="p-venue"><?= e(t('Ring')) ?></label>
                    <select id="p-venue" name="venue_id">
                        <option value=""><?= e(t('– alle –')) ?></option>
                        <?php foreach ($venues as $v): ?>
                            <option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="field field--grow"><label for="p-note"><?= e(t('Dauer / Hinweis')) ?></label><input id="p-note" name="note" placeholder="<?= e(t('z. B. 20 Min.')) ?>"></div>
                <div class="field field--xs"><label for="p-minutes"><?= e(t('Minuten')) ?></label><input id="p-minutes" name="minutes" type="number" min="0" value="0"></div>
            </div>
            <div class="form-actions"><button class="btn" type="submit"><?= e(t('Pause einfügen')) ?></button></div>
        </form>
    </div>
<?php endif; ?>
