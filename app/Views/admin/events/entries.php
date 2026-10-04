<?php

use App\Core\Auth;
use App\Models\AthleteRepo;
use App\Models\EntryRepo;

/**
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $entries
 * @var list<array<string,mixed>> $categories
 * @var list<array<string,mixed>> $gyms
 * @var list<array<string,mixed>> $athletes
 * @var array<string,mixed>       $filter
 * @var array<string,mixed>       $stats
 */
$id       = (int) $event['id'];
$subtitle = t('Anmeldungen');
$canWrite = Auth::canWrite();
require __DIR__ . '/_head.php';
?>

<div class="stat-grid stat-grid--compact">
    <?php foreach (EntryRepo::STATUS as $key => $label): ?>
        <a class="stat <?= $filter['status'] === $key ? 'is-active' : '' ?> <?= $key === 'angemeldet' && $stats['entries'][$key] > 0 ? 'stat--warn' : '' ?>" href="<?= e(url('/admin/events/' . $id . '/anmeldungen', ['status' => $key])) ?>">
            <span class="stat__value"><?= (int) $stats['entries'][$key] ?></span>
            <span class="stat__label"><?= e(t($label)) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <form method="get" class="filters">
        <input type="search" name="q" value="<?= e($filter['q']) ?>" placeholder="<?= e(t('Name oder Gym suchen')) ?>">
        <select name="status">
            <option value=""><?= e(t('alle Status')) ?></option>
            <?php foreach (EntryRepo::STATUS as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $filter['status'] === $key ? 'selected' : '' ?>><?= e(t($label)) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($categories !== []): ?>
            <select name="category">
                <option value=""><?= e(t('alle Kategorien')) ?></option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $filter['category'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <select name="gym">
            <option value=""><?= e(t('alle Gyms')) ?></option>
            <?php foreach ($gyms as $g): ?>
                <option value="<?= (int) $g['id'] ?>" <?= $filter['gym'] === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn--sm" type="submit"><?= e(t('Filtern')) ?></button>
        <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/events/' . $id . '/anmeldungen')) ?>"><?= e(t('Zurücksetzen')) ?></a>
        <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/events/' . $id . '/anmeldungen.csv')) ?>"><?= e(t('CSV-Export')) ?></a>
    </form>

    <form method="post" action="<?= e(url('/admin/events/' . $id . '/anmeldungen/sammelaktion')) ?>" id="bulk-form">
        <?= csrf_field() ?>
        <div class="table-scroll">
            <table class="table">
                <thead>
                <tr>
                    <?php if ($canWrite): ?><th class="col-check"><input type="checkbox" data-check-all="bulk-form" aria-label="<?= e(t('alle')) ?>"></th><?php endif; ?>
                    <th><?= e(t('Sportler##einzahl')) ?></th>
                    <th><?= e(t('Gym')) ?></th>
                    <th><?= e(t('Kategorie')) ?></th>
                    <th><?= e(t('Alter')) ?></th>
                    <th><?= e(t('Gewicht')) ?></th>
                    <th><?= e(t('Wiegen')) ?></th>
                    <th><?= e(t('Setz.')) ?></th>
                    <th><?= e(t('Status')) ?></th>
                    <th>€</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $x): ?>
                    <tr class="entry-row entry-row--<?= e($x['status']) ?>">
                        <?php if ($canWrite): ?><td class="col-check"><input type="checkbox" name="ids[]" value="<?= (int) $x['id'] ?>" form="bulk-form"></td><?php endif; ?>
                        <td>
                            <a class="strong" href="<?= e(url('/admin/sportler/' . $x['athlete_id'])) ?>"><?= e($x['last_name']) ?> <?= e($x['first_name']) ?></a>
                            <?php if ($x['nickname'] !== ''): ?><small class="muted"><?= e(t('„%s“', $x['nickname'])) ?></small><?php endif; ?>
                            <?php if ($x['note'] !== ''): ?><br><small class="muted" title="<?= e(t('Anmerkung des Gyms')) ?>">💬 <?= e($x['note']) ?></small><?php endif; ?>
                        </td>
                        <td><a href="<?= e(url('/admin/gyms/' . $x['gym_id'])) ?>"><?= e($x['gym_name']) ?></a></td>
                        <td>
                            <?php if ($canWrite && $categories !== []): ?>
                                <form method="post" action="<?= e(url('/admin/events/' . $id . '/anmeldung/' . $x['id'])) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <select name="category_id" onchange="this.form.submit()" class="select--inline">
                                        <option value=""><?= e(t('– keine –')) ?></option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= (int) $c['id'] ?>" <?= (int) $x['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            <?php else: ?>
                                <?= e($x['category_name'] ?? '–') ?>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= e((string) (age_from($x['birthdate'], (string) $event['starts_on']) ?? '–')) ?></td>
                        <td class="num"><?= e(format_weight($x['athlete_weight']) ?: '–') ?></td>
                        <td>
                            <?php if ($canWrite): ?>
                                <form method="post" action="<?= e(url('/admin/events/' . $id . '/anmeldung/' . $x['id'])) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input name="weighed" value="<?= $x['weighed'] !== null ? e(number_format((float) $x['weighed'], 1, ',', '')) : '' ?>" class="input--xs" inputmode="decimal" placeholder="kg" title="<?= e(t('Wiegegewicht – Enter speichert')) ?>">
                                </form>
                            <?php else: ?>
                                <?= e(format_weight($x['weighed'])) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($canWrite): ?>
                                <form method="post" action="<?= e(url('/admin/events/' . $id . '/anmeldung/' . $x['id'])) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input name="seed" type="number" min="0" value="<?= (int) $x['seed'] ?: '' ?>" class="input--xs" title="<?= e(t('Setzposition (1 = topgesetzt)')) ?>">
                                </form>
                            <?php else: ?>
                                <?= (int) $x['seed'] ?: '' ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($canWrite): ?>
                                <form method="post" action="<?= e(url('/admin/events/' . $id . '/anmeldung/' . $x['id'])) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <select name="status" onchange="this.form.submit()" class="select--inline pill pill--entry-<?= e($x['status']) ?>">
                                        <?php foreach (EntryRepo::STATUS as $key => $label): ?>
                                            <option value="<?= e($key) ?>" <?= $x['status'] === $key ? 'selected' : '' ?>><?= e(t($label)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            <?php else: ?>
                                <span class="pill pill--entry-<?= e($x['status']) ?>"><?= e(t(EntryRepo::STATUS[$x['status']] ?? $x['status'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($canWrite): ?>
                                <form method="post" action="<?= e(url('/admin/events/' . $id . '/anmeldung/' . $x['id'])) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="paid_form" value="1">
                                    <input type="checkbox" name="paid" value="1" <?= (int) $x['paid'] === 1 ? 'checked' : '' ?> onchange="this.form.submit()" title="<?= e(t('Startgeld bezahlt')) ?>">
                                </form>
                            <?php else: ?>
                                <?= (int) $x['paid'] === 1 ? '✓' : '' ?>
                            <?php endif; ?>
                        </td>
                        <td class="row-actions">
                            <?php if ($canWrite): ?>
                                <form method="post" action="<?= e(url('/admin/events/' . $id . '/anmeldung/' . $x['id'] . '/loeschen')) ?>" class="inline" data-confirm="<?= e(t('Anmeldung löschen?')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="linklike linklike--danger" type="submit"><?= e(t('Löschen')) ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($entries === []): ?>
                    <tr><td colspan="11" class="empty"><?= e($filter['status'] !== '' || $filter['q'] !== '' ? t('Keine Anmeldungen für diesen Filter.') : t('Keine Anmeldungen.')) ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($canWrite && $entries !== []): ?>
            <div class="bulkbar">
                <span><?= e(t('Ausgewählte:')) ?></span>
                <button class="btn btn--sm btn--primary" name="action" value="bestaetigt" type="submit"><?= e(t('bestätigen')) ?></button>
                <button class="btn btn--sm" name="action" value="abgelehnt" type="submit"><?= e(t('ablehnen')) ?></button>
                <button class="btn btn--sm" name="action" value="abgemeldet" type="submit"><?= e(t('abmelden')) ?></button>
                <button class="btn btn--sm" name="action" value="bezahlt" type="submit"><?= e(t('Startgeld bezahlt')) ?></button>
                <button class="btn btn--sm btn--danger" name="action" value="loeschen" type="submit" data-confirm-click="<?= e(t('Ausgewählte Anmeldungen löschen?')) ?>"><?= e(t('löschen')) ?></button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($canWrite): ?>
    <div class="card">
        <div class="card__head"><h2><?= e(t('Sportler manuell anmelden')) ?></h2></div>
        <p class="muted"><?= t('Für Galas oder wenn ein Gym nicht selbst anmeldet. Der Sportler muss bei einem Gym angelegt sein – <a href="%s">neuen Sportler anlegen</a>.', e(url('/admin/sportler/neu'))) ?></p>
        <form method="post" action="<?= e(url('/admin/events/' . $id . '/anmeldungen')) ?>" class="inline-form">
            <?= csrf_field() ?>
            <div class="field field--grow">
                <label for="athlete_id"><?= e(t('Sportler##einzahl')) ?></label>
                <input list="athlete-list" id="athlete-search" placeholder="<?= e(t('Name eingeben …')) ?>" autocomplete="off" data-pick="#athlete_id">
                <datalist id="athlete-list">
                    <?php foreach ($athletes as $a): ?>
                        <option value="<?= e($a['last_name'] . ' ' . $a['first_name'] . ' (' . $a['gym_name'] . ') #' . $a['id']) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <input type="hidden" name="athlete_id" id="athlete_id">
                <p class="field__hint"><?= e(t('Aus der Liste wählen – die Nummer am Ende (#id) ordnet den Sportler zu.')) ?></p>
            </div>
            <?php if ($categories !== []): ?>
                <div class="field field--grow">
                    <label for="category_id"><?= e(t('Kategorie')) ?></label>
                    <select name="category_id" id="category_id">
                        <option value=""><?= e(t('– keine –')) ?></option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <button class="btn btn--primary" type="submit"><?= e(t('Anmelden##sportler')) ?></button>
        </form>
    </div>
    <script>
    (function () {
        var s = document.getElementById('athlete-search'), h = document.getElementById('athlete_id');
        if (!s || !h) return;
        s.addEventListener('input', function () {
            var m = /#(\d+)\s*$/.exec(s.value);
            h.value = m ? m[1] : '';
        });
    })();
    </script>
<?php endif; ?>
