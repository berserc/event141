<?php

/**
 * Unternavigation einer Event-Seite.
 *
 * @var array<string,mixed> $event
 * @var string              $eventTab
 */
$base = '/e/' . $event['slug'];
$tabs = [
    ['uebersicht', 'Übersicht', $base],
    ['kaempfe', $event['type'] === 'gala' ? 'Fightcard' : 'Turnierplan', $base . '/kaempfe'],
    ['zeitplan', 'Zeitplan', $base . '/zeitplan'],
];

if ((int) $event['show_entries'] === 1) {
    $tabs[] = ['teilnehmer', 'Teilnehmer', $base . '/teilnehmer'];
}

if ((int) $event['show_results'] === 1) {
    $tabs[] = ['ergebnisse', 'Ergebnisse', $base . '/ergebnisse'];
}
?>
<nav class="event-nav" aria-label="Event-Seiten">
    <div class="wrap event-nav__inner">
        <?php foreach ($tabs as [$key, $label, $path]): ?>
            <a href="<?= e(url($path)) ?>"<?= $eventTab === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
</nav>
