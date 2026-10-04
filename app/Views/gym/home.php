<?php

use App\Models\EntryRepo;
use App\Models\EventRepo;

/**
 * @var array<string,mixed>       $gym
 * @var list<array<string,mixed>> $open      Events mit offener Anmeldung
 * @var list<array<string,mixed>> $entries   eigene Anmeldungen
 * @var list<array<string,mixed>> $athletes
 */
$byEvent = [];
foreach ($entries as $x) {
    $byEvent[(int) $x['event_id']][] = $x;
}
?>
<h1><?= e($gym['name']) ?></h1>
<?php if ($gym['status'] === 'neu'): ?>
    <p class="m-warn"><?= e(t('Eure Registrierung ist noch nicht vom Veranstalter bestätigt – Anmeldungen sind trotzdem schon möglich.')) ?></p>
<?php endif; ?>

<div class="m-grid m-grid--3">
    <a class="m-stat" href="<?= e(url('/gym/sportler')) ?>"><?= t('<strong>%d</strong> aktive Sportler', count($athletes)) ?></a>
    <a class="m-stat" href="#anmeldungen"><?= t('<strong>%d</strong> Anmeldungen', count($entries)) ?></a>
    <a class="m-stat" href="<?= e(url('/gym/gym141')) ?>"><strong><?= $gym['gym141_token'] !== '' ? '✓' : '–' ?></strong> <?= e($gym['gym141_token'] !== '' ? t('Gym141 verbunden') : t('Gym141 nicht verbunden')) ?></a>
</div>

<div class="m-card">
    <h2><?= e(t('Offene Anmeldungen')) ?></h2>
    <?php if ($open === []): ?>
        <p class="muted"><?= e(t('Derzeit ist kein Event zur Anmeldung geöffnet.')) ?></p>
    <?php else: ?>
        <?php foreach ($open as $ev): ?>
            <?php $mine = $byEvent[(int) $ev['id']] ?? []; ?>
            <div class="m-event">
                <div>
                    <strong><?= e($ev['name']) ?></strong> <small class="muted"><?= e(t(EventRepo::TYPES[$ev['type']] ?? '')) ?></small><br>
                    <small><?= e(format_date_range($ev['starts_on'], $ev['ends_on'])) ?><?= $ev['venue_city'] !== '' ? ' · ' . e($ev['venue_city']) : '' ?>
                        <?= $ev['registration_until'] ? ' · ' . e(t('Anmeldeschluss %s', format_date($ev['registration_until']))) : '' ?>
                        <?= (float) $ev['entry_fee'] > 0 ? ' · ' . e(t('Startgeld %s', format_money($ev['entry_fee']))) : '' ?></small>
                    <?php if ($mine !== []): ?><br><small class="m-ok"><?= e(t('%d eigene Anmeldung(en)', count($mine))) ?></small><?php endif; ?>
                </div>
                <a class="btn btn--primary btn--sm" href="<?= e(url('/gym/event/' . $ev['id'])) ?>"><?= e(t('Sportler anmelden')) ?></a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="m-card" id="anmeldungen">
    <h2><?= e(t('Eure Anmeldungen')) ?></h2>
    <?php if ($entries === []): ?>
        <p class="muted"><?= e(t('Noch keine Anmeldungen.')) ?></p>
    <?php else: ?>
        <table class="m-table">
            <thead><tr><th><?= e(t('Event')) ?></th><th><?= e(t('Sportler##einzahl')) ?></th><th><?= e(t('Kategorie')) ?></th><th><?= e(t('Status')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($entries as $x): ?>
                <tr>
                    <td><a href="<?= e(url('/gym/event/' . $x['event_id'])) ?>"><?= e($x['event_name']) ?></a><br><small class="muted"><?= e(format_date($x['starts_on'])) ?></small></td>
                    <td><?= e($x['first_name'] . ' ' . $x['last_name']) ?></td>
                    <td><?= e($x['category_name'] ?? '–') ?></td>
                    <td><span class="badge-dark badge-dark--<?= e($x['status']) ?>"><?= e(t(EntryRepo::STATUS[$x['status']] ?? $x['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if ($athletes === []): ?>
    <div class="m-card">
        <h2><?= e(t('Erste Schritte')) ?></h2>
        <ol class="steps">
            <li><?= t('<a href="%s">Gym141 verbinden</a> und Mitglieder als Sportler holen – oder <a href="%s">Sportler manuell anlegen</a>.', e(url('/gym/gym141')), e(url('/gym/sportler/neu'))) ?></li>
            <li><?= e(t('Bei einem offenen Event die Sportler in der passenden Kategorie anmelden.')) ?></li>
            <li><?= e(t('Der Veranstalter bestätigt die Anmeldung; danach erscheinen eure Sportler in Zeitplan und Turnierbaum.')) ?></li>
        </ol>
    </div>
<?php endif; ?>
