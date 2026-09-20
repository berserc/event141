<?php

use App\Core\Auth;
use App\Models\EventRepo;

/**
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $categories
 * @var array<string,string>      $errors
 * @var array<string,mixed>       $old
 */
$id       = (int) $event['id'];
$subtitle = 'Kategorien';
$canWrite = Auth::canWrite();
require __DIR__ . '/_head.php';

$editId = (int) query('edit', '0');
$edit   = null;

foreach ($categories as $c) {
    if ((int) $c['id'] === $editId) {
        $edit = $c;
    }
}

$form = $old + ($edit ?? [
    'id' => 0, 'name' => '', 'discipline' => '', 'gender' => 'alle', 'age_min' => null, 'age_max' => null,
    'weight_min' => null, 'weight_max' => null, 'rounds' => 3, 'round_minutes' => 2, 'mode' => 'ko', 'max_entries' => 0, 'note' => '', 'sort_order' => 0,
]);
$fmt = static fn ($v): string => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
?>

<?php if ($event['type'] === 'gala'): ?>
    <div class="notice">Bei einer <strong>Gala</strong> sind Kategorien optional – sie dienen nur zur Beschriftung der Kämpfe (z. B. „K1 -75 kg“). Turnierbäume werden nur bei Turnieren erzeugt.</div>
<?php endif; ?>

<div class="form-grid form-grid--wide">
    <div class="card">
        <div class="card__head"><h2>Kategorien</h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th>Kategorie</th><th>Details</th><th>Runden</th><th class="num">Anmeld.</th><th class="num">bestätigt</th><th class="num">Kämpfe</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr<?= $editId === (int) $c['id'] ? ' class="is-selected"' : '' ?>>
                        <td><a class="strong" href="<?= e(url('/admin/events/' . $id . '/kategorien', ['edit' => $c['id']])) ?>"><?= e($c['name']) ?></a></td>
                        <td><small><?= e(EventRepo::categoryInfo($c)) ?></small></td>
                        <td><?= (int) $c['rounds'] ?> × <?= e($fmt($c['round_minutes'])) ?> min</td>
                        <td class="num"><?= (int) $c['entry_count'] ?></td>
                        <td class="num"><?= (int) $c['confirmed_count'] ?></td>
                        <td class="num"><?= (int) $c['bout_count'] ?></td>
                        <td class="row-actions">
                            <a href="<?= e(url('/admin/events/' . $id . '/anmeldungen', ['category' => $c['id']])) ?>">Anmeldungen</a>
                            <?php if ($canWrite): ?>
                                <form method="post" action="<?= e(url('/admin/events/' . $id . '/kategorie-loeschen')) ?>" class="inline" data-confirm="Kategorie „<?= e($c['name']) ?>“ entfernen?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="category_id" value="<?= (int) $c['id'] ?>">
                                    <button class="linklike linklike--danger" type="submit">Entfernen</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($categories === []): ?>
                    <tr><td colspan="7" class="empty">Noch keine Kategorie. Beispiele: „Herren -75 kg“, „Jugend U16 -55 kg“, „Damen Leichtkontakt“.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($canWrite): ?>
            <?php $andere = array_filter(EventRepo::all(), static fn (array $e): bool => (int) $e['id'] !== $id); ?>
            <?php if ($andere !== []): ?>
                <details class="plan-edit" style="margin-top:1rem">
                    <summary>Kategorien aus einem anderen Event übernehmen</summary>
                    <form method="post" action="<?= e(url('/admin/events/' . $id . '/kategorien-kopieren')) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <div class="field field--grow">
                            <label>Vorlage</label>
                            <select name="source_event_id">
                                <?php foreach ($andere as $a): ?>
                                    <option value="<?= (int) $a['id'] ?>"><?= e($a['name']) ?> (<?= e(format_date($a['starts_on'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn--sm" type="submit">Übernehmen</button>
                    </form>
                </details>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($canWrite): ?>
        <form method="post" action="<?= e(url('/admin/events/' . $id . '/kategorie')) ?>" class="card form">
            <?= csrf_field() ?>
            <input type="hidden" name="category_id" value="<?= (int) $form['id'] ?>">
            <div class="card__head"><h2><?= $edit !== null ? 'Kategorie bearbeiten' : 'Neue Kategorie' ?></h2></div>

            <div class="field">
                <label for="c-name">Name *</label>
                <input id="c-name" name="name" required value="<?= e($form['name']) ?>" placeholder="Herren -75 kg">
                <?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>
            <div class="field-row">
                <div class="field field--grow">
                    <label for="c-disc">Disziplin</label>
                    <input id="c-disc" name="discipline" value="<?= e($form['discipline']) ?>" placeholder="K1, Leichtkontakt, Judo …">
                </div>
                <div class="field field--sm">
                    <label for="c-gender">Geschlecht</label>
                    <select id="c-gender" name="gender">
                        <?php foreach (EventRepo::GENDERS as $k => $l): ?>
                            <option value="<?= e($k) ?>" <?= $form['gender'] === $k ? 'selected' : '' ?>><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="field field--xs"><label for="c-amin">Alter von</label><input id="c-amin" name="age_min" type="number" min="0" value="<?= e((string) $form['age_min']) ?>"></div>
                <div class="field field--xs"><label for="c-amax">Alter bis</label><input id="c-amax" name="age_max" type="number" min="0" value="<?= e((string) $form['age_max']) ?>"></div>
                <div class="field field--xs"><label for="c-wmin">Gewicht über (kg)</label><input id="c-wmin" name="weight_min" inputmode="decimal" value="<?= e($fmt($form['weight_min'])) ?>"></div>
                <div class="field field--xs"><label for="c-wmax">Gewicht bis (kg)</label><input id="c-wmax" name="weight_max" inputmode="decimal" value="<?= e($fmt($form['weight_max'])) ?>"></div>
            </div>
            <div class="field-row">
                <div class="field field--xs"><label for="c-rounds">Runden</label><input id="c-rounds" name="rounds" type="number" min="1" value="<?= (int) $form['rounds'] ?>"></div>
                <div class="field field--xs"><label for="c-min">Minuten je Runde</label><input id="c-min" name="round_minutes" inputmode="decimal" value="<?= e($fmt($form['round_minutes'])) ?>"></div>
                <div class="field field--xs"><label for="c-max">Max. Teilnehmer</label><input id="c-max" name="max_entries" type="number" min="0" value="<?= (int) $form['max_entries'] ?>"></div>
                <div class="field field--xs"><label for="c-sort">Reihung</label><input id="c-sort" name="sort_order" type="number" value="<?= (int) $form['sort_order'] ?>"></div>
            </div>
            <div class="field">
                <label for="c-mode">Modus</label>
                <select id="c-mode" name="mode">
                    <option value="ko" <?= $form['mode'] === 'ko' ? 'selected' : '' ?>>K.-o.-System (Turnierbaum)</option>
                    <option value="liste" <?= $form['mode'] === 'liste' ? 'selected' : '' ?>>Nur Teilnehmerliste (Kämpfe manuell)</option>
                </select>
            </div>
            <div class="field">
                <label for="c-note">Hinweis</label>
                <input id="c-note" name="note" value="<?= e($form['note']) ?>">
            </div>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit"><?= $edit !== null ? 'Speichern' : 'Kategorie anlegen' ?></button>
                <?php if ($edit !== null): ?>
                    <a class="btn btn--ghost" href="<?= e(url('/admin/events/' . $id . '/kategorien')) ?>">Neue Kategorie</a>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</div>
