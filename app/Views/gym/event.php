<?php

use App\Models\EntryRepo;
use App\Models\EventRepo;

/**
 * @var array<string,mixed>       $gym
 * @var array<string,mixed>       $event
 * @var bool                      $open
 * @var list<array<string,mixed>> $categories
 * @var list<array<string,mixed>> $athletes
 * @var list<array<string,mixed>> $entries
 */
$entered = [];
foreach ($entries as $x) {
    $entered[(int) $x['athlete_id']][] = $x;
}
?>
<h1><?= e($event['name']) ?></h1>
<p class="lead">
    <?= e(t(EventRepo::TYPES[$event['type']] ?? '')) ?> · <?= e(format_date_range($event['starts_on'], $event['ends_on'])) ?>
    <?= $event['venue_name'] !== '' ? '· ' . e($event['venue_name']) : '' ?><?= $event['venue_city'] !== '' ? ', ' . e($event['venue_city']) : '' ?>
    <?php if ((int) $event['published'] === 1): ?>· <a href="<?= e(url('/e/' . $event['slug'])) ?>" target="_blank" rel="noopener"><?= e(t('Event-Seite ↗')) ?></a><?php endif; ?>
</p>

<?php if (!$open): ?>
    <p class="m-warn"><?= e(t('Die Anmeldung für dieses Event ist derzeit geschlossen.')) ?></p>
<?php else: ?>
    <p class="m-ok"><?= e($event['registration_until'] ? t('Anmeldung offen bis %s', format_date($event['registration_until'])) : t('Anmeldung offen')) ?><?= (float) $event['entry_fee'] > 0 ? ' · ' . e(t('Startgeld %s je Sportler', format_money($event['entry_fee']))) : '' ?>.</p>
<?php endif; ?>

<div class="m-card">
    <h2><?= e(t('Eure Anmeldungen')) ?> <small class="muted">(<?= count($entries) ?>)</small></h2>
    <?php if ($entries === []): ?>
        <p class="muted"><?= e(t('Noch niemand angemeldet.')) ?></p>
    <?php else: ?>
        <table class="m-table">
            <thead><tr><th><?= e(t('Sportler##einzahl')) ?></th><th><?= e(t('Kategorie')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($entries as $x): ?>
                <tr>
                    <td><?= e($x['first_name'] . ' ' . $x['last_name']) ?></td>
                    <td><?= e($x['category_name'] ?? '–') ?></td>
                    <td><span class="badge-dark badge-dark--<?= e($x['status']) ?>"><?= e(t(EntryRepo::STATUS[$x['status']] ?? $x['status'])) ?></span>
                        <?= $x['weighed'] !== null ? '<small class="muted">' . e(t('gewogen %s', format_weight($x['weighed']))) . '</small>' : '' ?></td>
                    <td>
                        <?php if ($open && in_array($x['status'], ['angemeldet', 'bestaetigt'], true)): ?>
                            <form method="post" action="<?= e(url('/gym/anmeldung/' . $x['id'] . '/abmelden')) ?>" data-confirm="<?= e(t('%s abmelden?', $x['first_name'] . ' ' . $x['last_name'])) ?>">
                                <?= csrf_field() ?><button class="linklike" type="submit"><?= e(t('abmelden')) ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if ($open): ?>
    <form method="post" action="<?= e(url('/gym/event/' . $event['id'] . '/anmelden')) ?>" class="m-card m-form">
        <?= csrf_field() ?>
        <h2><?= e(t('Sportler anmelden')) ?></h2>

        <?php if ($categories !== []): ?>
            <div class="m-field">
                <label for="category_id"><?= e(t('Kategorie')) ?></label>
                <select id="category_id" name="category_id" required>
                    <option value=""><?= e(t('– bitte wählen –')) ?></option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?><?= EventRepo::categoryInfo($c) !== '' ? ' (' . e(EventRepo::categoryInfo($c)) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($athletes === []): ?>
            <p class="muted"><?= t('Noch keine aktiven Sportler – <a href="%s">anlegen</a> oder <a href="%s">aus Gym141 holen</a>.', e(url('/gym/sportler/neu')), e(url('/gym/gym141'))) ?></p>
        <?php else: ?>
            <div class="m-athlete-grid">
                <?php foreach ($athletes as $a): ?>
                    <?php $alter = age_from($a['birthdate'], (string) $event['starts_on']); ?>
                    <label class="m-athlete">
                        <input type="checkbox" name="athlete_ids[]" value="<?= (int) $a['id'] ?>">
                        <span>
                            <strong><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong>
                            <small><?= $alter !== null ? e(t('%d J.', $alter)) : '' ?><?= $a['weight'] ? ' · ' . e(format_weight($a['weight'])) : '' ?><?= $a['gender'] !== 'unbekannt' ? ' · ' . e(t(strtoupper((string) $a['gender']))) : '' ?></small>
                            <?php if (isset($entered[(int) $a['id']])): ?>
                                <small class="m-ok"><?= e(t('bereits: %s', implode(', ', array_map(static fn (array $x): string => (string) ($x['category_name'] ?? t('angemeldet')), $entered[(int) $a['id']])))) ?></small>
                            <?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="m-field"><label for="note"><?= e(t('Anmerkung an den Veranstalter')) ?></label><input id="note" name="note" placeholder="<?= e(t('optional')) ?>"></div>
            <button class="btn btn--primary" type="submit"><?= e(t('Ausgewählte anmelden')) ?></button>
        <?php endif; ?>
    </form>
<?php endif; ?>

<p><a href="<?= e(url('/gym')) ?>"><?= e(t('← Zur Übersicht')) ?></a></p>

<script>
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) { if (!confirm(f.dataset.confirm)) ev.preventDefault(); });
});
</script>
