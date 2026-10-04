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
$subtitle = t('Kategorien');
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
    <div class="notice"><?= t('Bei einer <strong>Gala</strong> sind Kategorien optional – sie dienen nur zur Beschriftung der Kämpfe (z. B. „K1 -75 kg“). Turnierbäume werden nur bei Turnieren erzeugt.') ?></div>
<?php endif; ?>

<div class="form-grid form-grid--wide">
    <div class="card">
        <div class="card__head"><h2><?= e(t('Kategorien')) ?></h2></div>
        <div class="table-scroll">
            <table class="table table--compact">
                <thead><tr><th><?= e(t('Kategorie')) ?></th><th><?= e(t('Details')) ?></th><th><?= e(t('Runden')) ?></th><th class="num"><?= e(t('Anmeld.')) ?></th><th class="num"><?= e(t('bestätigt')) ?></th><th class="num"><?= e(t('Kämpfe')) ?></th><th></th></tr></thead>
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
                            <a href="<?= e(url('/admin/events/' . $id . '/anmeldungen', ['category' => $c['id']])) ?>"><?= e(t('Anmeldungen')) ?></a>
                            <?php if ($canWrite): ?>
                                <form method="post" action="<?= e(url('/admin/events/' . $id . '/kategorie-loeschen')) ?>" class="inline" data-confirm="<?= e(t('Kategorie „%s“ entfernen?', $c['name'])) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="category_id" value="<?= (int) $c['id'] ?>">
                                    <button class="linklike linklike--danger" type="submit"><?= e(t('Entfernen')) ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($categories === []): ?>
                    <tr><td colspan="7" class="empty"><?= e(t('Noch keine Kategorie. Beispiele: „Herren -75 kg“, „Jugend U16 -55 kg“, „Damen Leichtkontakt“.')) ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($canWrite): ?>
            <?php $andere = array_filter(EventRepo::all(), static fn (array $e): bool => (int) $e['id'] !== $id); ?>
            <?php if ($andere !== []): ?>
                <details class="plan-edit" style="margin-top:1rem">
                    <summary><?= e(t('Kategorien aus einem anderen Event übernehmen')) ?></summary>
                    <form method="post" action="<?= e(url('/admin/events/' . $id . '/kategorien-kopieren')) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <div class="field field--grow">
                            <label><?= e(t('Vorlage')) ?></label>
                            <select name="source_event_id">
                                <?php foreach ($andere as $a): ?>
                                    <option value="<?= (int) $a['id'] ?>"><?= e($a['name']) ?> (<?= e(format_date($a['starts_on'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn--sm" type="submit"><?= e(t('Übernehmen')) ?></button>
                    </form>
                </details>
            <?php endif; ?>

            <?php foreach (\App\Core\Ruleset::all() as $rs): ?>
                <details class="plan-edit" style="margin-top:.6rem">
                    <summary><?= t('Aus Regelsatz anlegen: <strong>%s</strong> <small class="muted">(%d Kategorien)</small>', e(t($rs['name'])), count(\App\Core\Ruleset::expand($rs))) ?></summary>
                    <form method="post" action="<?= e(url('/admin/events/' . $id . '/regelsatz')) ?>" class="form" style="margin-top:.6rem">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ruleset" value="<?= e($rs['code']) ?>">
                        <p class="muted" style="margin-top:0"><?= e(t($rs['version'])) ?> ·
                            <a href="<?= e(url('/admin/regelsaetze/' . $rs['code'])) ?>"><?= e(t('Tabellen ansehen')) ?></a></p>
                        <div class="field">
                            <label><?= e(t('Disziplinen')) ?></label>
                            <div class="checkbox-grid">
                                <?php foreach ($rs['disciplines'] as $dKey => $disc): ?>
                                    <label class="check"><input type="checkbox" name="disc[]" value="<?= e((string) $dKey) ?>" checked> <?= e(t($disc['name'])) ?> <small class="muted">(<?= $disc['area'] === 'tatami' ? 'Tatami' : 'Ring' ?>)</small></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="field">
                            <label><?= e(t('Altersklassen')) ?></label>
                            <div class="checkbox-grid">
                                <?php foreach (\App\Core\Ruleset::classes($rs) as $cKey => $cLabel): ?>
                                    <label class="check"><input type="checkbox" name="class[]" value="<?= e((string) $cKey) ?>" checked> <?= e(t($cLabel)) ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="field">
                            <label><?= e(t('Geschlecht')) ?></label>
                            <div class="checkbox-grid">
                                <label class="check"><input type="checkbox" name="gender[]" value="m" checked> <?= e(t('männlich')) ?></label>
                                <label class="check"><input type="checkbox" name="gender[]" value="w" checked> <?= e(t('weiblich')) ?></label>
                            </div>
                        </div>
                        <p class="field__hint"><?= e(t('Gleichnamige Kategorien bleiben unangetastet; alles lässt sich danach einzeln anpassen oder entfernen.')) ?></p>
                        <button class="btn btn--sm" type="submit"><?= e(t('Kategorien anlegen')) ?></button>
                    </form>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($canWrite): ?>
        <form method="post" action="<?= e(url('/admin/events/' . $id . '/kategorie')) ?>" class="card form">
            <?= csrf_field() ?>
            <input type="hidden" name="category_id" value="<?= (int) $form['id'] ?>">
            <div class="card__head"><h2><?= e($edit !== null ? t('Kategorie bearbeiten') : t('Neue Kategorie')) ?></h2></div>

            <div class="field">
                <label for="c-name"><?= e(t('Name *')) ?></label>
                <input id="c-name" name="name" required value="<?= e($form['name']) ?>" placeholder="<?= e(t('Herren -75 kg')) ?>">
                <?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>
            <div class="field-row">
                <div class="field field--grow">
                    <label for="c-disc"><?= e(t('Disziplin')) ?></label>
                    <input id="c-disc" name="discipline" value="<?= e($form['discipline']) ?>" placeholder="<?= e(t('K1, Leichtkontakt, Judo …')) ?>">
                </div>
                <div class="field field--sm">
                    <label for="c-gender"><?= e(t('Geschlecht')) ?></label>
                    <select id="c-gender" name="gender">
                        <?php foreach (EventRepo::GENDERS as $k => $l): ?>
                            <option value="<?= e($k) ?>" <?= $form['gender'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="field field--xs"><label for="c-amin"><?= e(t('Alter von')) ?></label><input id="c-amin" name="age_min" type="number" min="0" value="<?= e((string) $form['age_min']) ?>"></div>
                <div class="field field--xs"><label for="c-amax"><?= e(t('Alter bis')) ?></label><input id="c-amax" name="age_max" type="number" min="0" value="<?= e((string) $form['age_max']) ?>"></div>
                <div class="field field--xs"><label for="c-wmin"><?= e(t('Gewicht über (kg)')) ?></label><input id="c-wmin" name="weight_min" inputmode="decimal" value="<?= e($fmt($form['weight_min'])) ?>"></div>
                <div class="field field--xs"><label for="c-wmax"><?= e(t('Gewicht bis (kg)')) ?></label><input id="c-wmax" name="weight_max" inputmode="decimal" value="<?= e($fmt($form['weight_max'])) ?>"></div>
            </div>
            <div class="field-row">
                <div class="field field--xs"><label for="c-rounds"><?= e(t('Runden')) ?></label><input id="c-rounds" name="rounds" type="number" min="1" value="<?= (int) $form['rounds'] ?>"></div>
                <div class="field field--xs"><label for="c-min"><?= e(t('Minuten je Runde')) ?></label><input id="c-min" name="round_minutes" inputmode="decimal" value="<?= e($fmt($form['round_minutes'])) ?>"></div>
                <div class="field field--xs"><label for="c-max"><?= e(t('Max. Teilnehmer')) ?></label><input id="c-max" name="max_entries" type="number" min="0" value="<?= (int) $form['max_entries'] ?>"></div>
                <div class="field field--xs"><label for="c-sort"><?= e(t('Reihung')) ?></label><input id="c-sort" name="sort_order" type="number" value="<?= (int) $form['sort_order'] ?>"></div>
            </div>
            <div class="field">
                <label for="c-mode"><?= e(t('Modus')) ?></label>
                <select id="c-mode" name="mode">
                    <option value="ko" <?= $form['mode'] === 'ko' ? 'selected' : '' ?>><?= e(t('K.-o.-System (Turnierbaum)')) ?></option>
                    <option value="liste" <?= $form['mode'] === 'liste' ? 'selected' : '' ?>><?= e(t('Nur Teilnehmerliste (Kämpfe manuell)')) ?></option>
                </select>
            </div>
            <div class="field">
                <label for="c-note"><?= e(t('Hinweis')) ?></label>
                <input id="c-note" name="note" value="<?= e($form['note']) ?>">
            </div>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit"><?= e($edit !== null ? t('Speichern') : t('Kategorie anlegen')) ?></button>
                <?php if ($edit !== null): ?>
                    <a class="btn btn--ghost" href="<?= e(url('/admin/events/' . $id . '/kategorien')) ?>"><?= e(t('Neue Kategorie')) ?></a>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</div>
