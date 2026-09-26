<?php

use App\Core\Auth;
use App\Models\BoutRepo;
use App\Models\EntryRepo;

/**
 * @var array<string,mixed>       $event
 * @var array<string,mixed>       $bout
 * @var list<array<string,mixed>> $entries
 * @var list<array<string,mixed>> $sessions
 * @var list<array<string,mixed>> $venues
 * @var list<array<string,mixed>> $categories
 * @var list<string>              $methods
 * @var list<array<string,mixed>> $feeder
 */
$id       = (int) $event['id'];
$bid      = (int) $bout['id'];
$isBreak  = (int) $bout['is_break'] === 1;
$subtitle = $isBreak ? 'Pause' : 'Kampf #' . $bout['bout_no'];
$canWrite = Auth::canWrite();
$fromTree = (int) $bout['round_no'] > 1;
require __DIR__ . '/_head.php';

$red  = BoutRepo::cornerName($bout, 'red');
$blue = BoutRepo::cornerName($bout, 'blue');

$entryOptions = static function (?int $selected) use ($entries): string {
    $html = '<option value="">– offen –</option>';
    foreach ($entries as $x) {
        $html .= '<option value="' . (int) $x['id'] . '"' . ($selected === (int) $x['id'] ? ' selected' : '') . '>' . e(EntryRepo::label($x)) . '</option>';
    }
    return $html;
};
?>

<div class="form-grid">
    <?php if (!$isBreak): ?>
        <div class="card bout-card">
            <div class="card__head">
                <h2>
                    <?= $bout['title'] !== '' ? e($bout['title']) : 'Kampf #' . (int) $bout['bout_no'] ?>
                    <span class="pill pill--bout-<?= e($bout['status']) ?>"><?= e(BoutRepo::STATUS[$bout['status']] ?? $bout['status']) ?></span>
                </h2>
                <small class="muted"><?= e($bout['category_name'] ?? '') ?><?= $bout['round_label'] !== '' ? ' · ' . e($bout['round_label']) : '' ?></small>
            </div>

            <div class="versus">
                <div class="versus__corner versus__corner--red<?= $bout['winner'] === 'red' ? ' is-winner' : '' ?>">
                    <span class="versus__label">Rot</span>
                    <strong><?= $red !== '' ? e($red) : '<span class="muted">offen</span>' ?></strong>
                    <?php if ($bout['red_gym'] !== null): ?><small><?= e($bout['red_gym']) ?><?= $bout['red_nat'] ? ' ' . flag_emoji($bout['red_nat']) : '' ?></small><?php endif; ?>
                    <?php if ($bout['red_entry_id'] !== null): ?><small class="muted"><?= (int) $bout['red_w'] ?>-<?= (int) $bout['red_l'] ?>-<?= (int) $bout['red_d'] ?><?= $bout['red_weight'] ? ' · ' . e(format_weight($bout['red_weight'])) : '' ?></small><?php endif; ?>
                </div>
                <div class="versus__vs">vs</div>
                <div class="versus__corner versus__corner--blue<?= $bout['winner'] === 'blue' ? ' is-winner' : '' ?>">
                    <span class="versus__label">Blau</span>
                    <strong><?= $blue !== '' ? e($blue) : '<span class="muted">offen</span>' ?></strong>
                    <?php if ($bout['blue_gym'] !== null): ?><small><?= e($bout['blue_gym']) ?><?= $bout['blue_nat'] ? ' ' . flag_emoji($bout['blue_nat']) : '' ?></small><?php endif; ?>
                    <?php if ($bout['blue_entry_id'] !== null): ?><small class="muted"><?= (int) $bout['blue_w'] ?>-<?= (int) $bout['blue_l'] ?>-<?= (int) $bout['blue_d'] ?><?= $bout['blue_weight'] ? ' · ' . e(format_weight($bout['blue_weight'])) : '' ?></small><?php endif; ?>
                </div>
            </div>

            <?php if ($bout['status'] === 'beendet'): ?>
                <p class="result-line">
                    <strong>Ergebnis:</strong> <?= e(BoutRepo::WINNER[$bout['winner']] ?? '') ?>
                    <?= $bout['winner'] === 'red' ? '– ' . e($red) : ($bout['winner'] === 'blue' ? '– ' . e($blue) : '') ?>
                    <?= $bout['method'] !== '' ? '· ' . e($bout['method']) : '' ?>
                    <?= $bout['result_note'] !== '' ? '· ' . e($bout['result_note']) : '' ?>
                </p>
                <?php if (Auth::canScore()): ?>
                    <form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $bid . '/status')) ?>" data-confirm="Ergebnis zurücknehmen? Der Sieger wird aus dem Folgekampf entfernt (sofern dieser noch offen ist).">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="reopen">
                        <button class="btn btn--sm" type="submit">Ergebnis zurücknehmen</button>
                    </form>
                <?php endif; ?>
            <?php elseif (Auth::canScore() && $bout['status'] !== 'abgesagt'): ?>
                <form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $bid . '/ergebnis')) ?>" class="form result-form">
                    <?= csrf_field() ?>
                    <h3>Ergebnis eintragen</h3>
                    <div class="winner-pick">
                        <label class="winner-pick__opt winner-pick__opt--red"><input type="radio" name="winner" value="red" <?= $bout['red_entry_id'] === null ? 'disabled' : '' ?>> <span>Rot<?= $red !== '' ? '<br><small>' . e($red) . '</small>' : '' ?></span></label>
                        <label class="winner-pick__opt winner-pick__opt--blue"><input type="radio" name="winner" value="blue" <?= $bout['blue_entry_id'] === null ? 'disabled' : '' ?>> <span>Blau<?= $blue !== '' ? '<br><small>' . e($blue) . '</small>' : '' ?></span></label>
                        <label class="winner-pick__opt"><input type="radio" name="winner" value="draw"> <span>Unentschieden</span></label>
                        <label class="winner-pick__opt"><input type="radio" name="winner" value="none"> <span>kein Sieger</span></label>
                    </div>
                    <div class="field-row">
                        <div class="field field--grow">
                            <label for="method">Siegart</label>
                            <input id="method" name="method" list="methods" placeholder="Punkte, KO, TKO …">
                            <datalist id="methods"><?php foreach ($methods as $m): ?><option value="<?= e($m) ?>"></option><?php endforeach; ?></datalist>
                        </div>
                        <div class="field field--xs">
                            <label for="result_round">Runde</label>
                            <input id="result_round" name="result_round" placeholder="2">
                        </div>
                        <div class="field field--grow">
                            <label for="result_note">Details</label>
                            <input id="result_note" name="result_note" placeholder="3:0, Runde 2 1:12 …">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn--primary" type="submit">Ergebnis speichern</button>
                        <?php if ($bout['status'] !== 'laufend'): ?>
                            <button class="btn" type="submit" formaction="<?= e(url('/admin/events/' . $id . '/kampf/' . $bid . '/status')) ?>" name="status" value="laufend" formnovalidate>▶ Kampf läuft</button>
                        <?php else: ?>
                            <button class="btn" type="submit" formaction="<?= e(url('/admin/events/' . $id . '/kampf/' . $bid . '/status')) ?>" name="status" value="geplant" formnovalidate>zurück auf geplant</button>
                        <?php endif; ?>
                        <button class="btn btn--ghost" type="submit" formaction="<?= e(url('/admin/events/' . $id . '/kampf/' . $bid . '/status')) ?>" name="status" value="abgesagt" formnovalidate>absagen</button>
                    </div>
                </form>
            <?php elseif ($bout['status'] === 'abgesagt' && Auth::canScore()): ?>
                <form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $bid . '/status')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--sm" name="status" value="geplant" type="submit">Absage aufheben</button>
                </form>
            <?php endif; ?>

            <?php if ($feeder !== [] || $bout['next_bout_id'] !== null): ?>
                <p class="muted" style="margin-top:1rem">
                    <?php foreach ($feeder as $f): ?>
                        <?= e(ucfirst($f['next_slot'])) ?>e Ecke ← Sieger aus <a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $f['id'])) ?>">#<?= (int) $f['bout_no'] ?> (<?= e($f['round_label']) ?>)</a>.
                    <?php endforeach; ?>
                    <?php if ($bout['next_bout_id'] !== null): ?>
                        Sieger geht in <a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $bout['next_bout_id'])) ?>">Kampf #<?= (int) ($bout['next_bout_id']) ?></a> (<?= e($bout['next_slot']) ?>).
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $bid)) ?>" class="card form">
        <?= csrf_field() ?>
        <div class="card__head"><h2><?= $isBreak ? 'Pause' : 'Kampfdaten' ?> &amp; Einplanung</h2></div>

        <?php if (!$isBreak && !$fromTree): ?>
            <div class="field-row">
                <div class="field field--grow">
                    <label for="red_entry_id">Rote Ecke</label>
                    <select id="red_entry_id" name="red_entry_id" <?= $canWrite ? '' : 'disabled' ?>><?= $entryOptions($bout['red_entry_id'] !== null ? (int) $bout['red_entry_id'] : null) ?></select>
                </div>
                <div class="field field--grow">
                    <label for="blue_entry_id">Blaue Ecke</label>
                    <select id="blue_entry_id" name="blue_entry_id" <?= $canWrite ? '' : 'disabled' ?>><?= $entryOptions($bout['blue_entry_id'] !== null ? (int) $bout['blue_entry_id'] : null) ?></select>
                </div>
            </div>
        <?php elseif ($fromTree): ?>
            <p class="field__hint">Die Ecken dieses Turnierkampfs werden automatisch mit den Siegern der Vorrunde besetzt.</p>
        <?php endif; ?>

        <div class="field-row">
            <div class="field field--grow"><label for="title">Bezeichnung</label><input id="title" name="title" value="<?= e($bout['title']) ?>" <?= $canWrite ? '' : 'disabled' ?>></div>
            <div class="field field--xs"><label for="bout_no">Nr.</label><input id="bout_no" name="bout_no" type="number" min="0" value="<?= (int) $bout['bout_no'] ?>" <?= $canWrite ? '' : 'disabled' ?>></div>
            <?php if (!$isBreak): ?>
                <div class="field field--xs"><label for="rounds">Runden</label><input id="rounds" name="rounds" type="number" min="1" value="<?= (int) $bout['rounds'] ?>" <?= $canWrite ? '' : 'disabled' ?>></div>
                <div class="field field--xs"><label for="round_minutes">Minuten</label><input id="round_minutes" name="round_minutes" inputmode="decimal" value="<?= e(rtrim(rtrim(number_format((float) $bout['round_minutes'], 1, ',', ''), '0'), ',')) ?>" <?= $canWrite ? '' : 'disabled' ?>></div>
            <?php else: ?>
                <input type="hidden" name="rounds" value="1"><input type="hidden" name="round_minutes" value="1">
            <?php endif; ?>
        </div>
        <div class="field-row">
            <div class="field field--grow">
                <label for="session_id">Abschnitt</label>
                <select id="session_id" name="session_id" <?= $canWrite ? '' : 'disabled' ?>>
                    <option value="">– nicht eingeplant –</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) $bout['session_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e(format_date($s['day_date'])) ?> · <?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field field--sm">
                <label for="venue_id">Ring</label>
                <select id="venue_id" name="venue_id" <?= $canWrite ? '' : 'disabled' ?>>
                    <option value="">–</option>
                    <?php foreach ($venues as $v): ?>
                        <option value="<?= (int) $v['id'] ?>" <?= (int) $bout['venue_id'] === (int) $v['id'] ? 'selected' : '' ?>><?= e($v['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field field--xs"><label for="order_no">Reihung</label><input id="order_no" name="order_no" type="number" value="<?= (int) $bout['order_no'] ?>" <?= $canWrite ? '' : 'disabled' ?>></div>
            <div class="field field--xs"><label for="scheduled_time">Uhrzeit</label><input id="scheduled_time" name="scheduled_time" type="time" value="<?= e($bout['scheduled_time']) ?>" <?= $canWrite ? '' : 'disabled' ?>></div>
        </div>
        <div class="field"><label for="note"><?= $isBreak ? 'Dauer / Hinweis' : 'Notiz' ?></label><input id="note" name="note" value="<?= e($bout['note']) ?>" <?= $isBreak ? 'placeholder="z. B. 20 Min."' : '' ?> <?= $canWrite ? '' : 'disabled' ?>></div>

        <input type="hidden" name="card_form" value="1">
        <div class="field-row">
            <?php if (!$isBreak): ?>
                <div class="field field--sm"><label for="block">Block</label><input id="block" name="block" list="blocks" value="<?= e($bout['block']) ?>" placeholder="Main Card" <?= $canWrite ? '' : 'disabled' ?>>
                    <datalist id="blocks"><option value="Main Fight"></option><option value="Main Card"></option><option value="Prelims"></option></datalist></div>
                <div class="field field--grow"><label for="belt_label">Titelkampf um <small>(leer = kein Titelkampf)</small></label><input id="belt_label" name="belt_label" value="<?= e($bout['belt_label']) ?>" placeholder="Österreichischer Muay Thai Titel" <?= $canWrite ? '' : 'disabled' ?>></div>
                <div class="field field--sm"><label for="style">Disziplin</label><input id="style" name="style" value="<?= e($bout['style']) ?>" placeholder="Muay Thai, K-1, Boxen" <?= $canWrite ? '' : 'disabled' ?>></div>
                <div class="field field--grow"><label for="weight_label">Gewichtsklasse</label><input id="weight_label" name="weight_label" value="<?= e($bout['weight_label']) ?>" placeholder="-66,6 kg" <?= $canWrite ? '' : 'disabled' ?>></div>
            <?php endif; ?>
            <div class="field field--xs"><label for="minutes">Dauer im Zeitplan (Min.)</label><input id="minutes" name="minutes" type="number" min="0" value="<?= (int) $bout['minutes'] ?>" title="0 = Standard des Events" <?= $canWrite ? '' : 'disabled' ?>></div>
        </div>
        <?php if (!$isBreak): ?>
            <div class="field"><label for="description">Story zum Kampf <small>(Kampf-Detailseite; erster Absatz = Überschrift)</small></label>
                <textarea id="description" name="description" rows="7" <?= $canWrite ? '' : 'disabled' ?>><?= e($bout['description']) ?></textarea></div>
            <div class="field"><label for="epilog">Nachwort – „Nach dem Kampf“ <small>(erscheint nach dem Kampf unter dem Ergebnis; Leerzeile = Absatz)</small></label>
                <textarea id="epilog" name="epilog" rows="4" <?= $canWrite ? '' : 'disabled' ?>><?= e((string) ($bout['epilog'] ?? '')) ?></textarea></div>
            <label class="check"><input type="checkbox" name="show_record" value="1" <?= (int) $bout['show_record'] === 1 ? 'checked' : '' ?> <?= $canWrite ? '' : 'disabled' ?>> Kampfbilanz öffentlich zeigen</label>
        <?php else: ?>
            <input type="hidden" name="show_record" value="1">
        <?php endif; ?>
        <label class="check"><input type="checkbox" name="active" value="1" <?= (int) $bout['active'] === 1 ? 'checked' : '' ?> <?= $canWrite ? '' : 'disabled' ?>> aktiv (auf Website und im Zeitplan sichtbar)</label>

        <?php if ($canWrite): ?>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit">Speichern</button>
                <a class="btn btn--ghost" href="<?= e(url('/admin/events/' . $id . '/kaempfe')) ?>">Zur Liste</a>
                <a class="btn btn--ghost" href="<?= e(url('/admin/events/' . $id . '/zeitplan')) ?>">Zeitplan</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if (!$isBreak): ?>
<form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $bid . '/bilder')) ?>" class="card form" id="bilder">
    <?= csrf_field() ?>
    <div class="card__head"><h2>Bilder zum Kampf (<?= count($boutImages ?? []) ?>)</h2></div>
    <p class="muted">Aus der <a href="<?= e(url('/admin/medien')) ?>">Bildbibliothek</a> – erscheinen auf der Kampf-Detailseite unter Story und Nachwort. Tipp: Bilder beim Hochladen mit dem Kämpfernamen taggen, dann hier danach suchen.</p>
    <?php $pickerField = 'bout_images'; $pickerSelected = $boutImages ?? []; $pickerImages = $libImages ?? []; $pickerSingle = false;
    require dirname(__DIR__) . '/partials/_image-picker.php'; ?>
    <?php if ($canWrite): ?><div class="form-actions"><button class="btn btn--primary" type="submit">Bilder speichern</button></div><?php endif; ?>
</form>
<?php endif; ?>

<?php if ($canWrite): ?>
    <form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $bid . '/loeschen')) ?>" class="inline" data-confirm="<?= $isBreak ? 'Pause' : 'Kampf' ?> löschen?">
        <?= csrf_field() ?>
        <?php if ((int) $bout['round_no'] > 0): ?><input type="hidden" name="force" value="1"><?php endif; ?>
        <button class="linklike linklike--danger" type="submit"><?= $isBreak ? 'Pause' : 'Kampf' ?> löschen<?= (int) $bout['round_no'] > 0 ? ' (Achtung: Teil eines Turnierbaums)' : '' ?></button>
    </form>
<?php endif; ?>
