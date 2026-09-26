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

if ((int) ($event['report_published'] ?? 0) === 1 && trim((string) ($event['report_text'] ?? '')) !== '') {
    $tabs[] = ['bericht', 'Bericht', $base . '/bericht'];
}

if (\App\Models\GalleryRepo::hasGalleries((int) $event['id'])) {
    $tabs[] = ['galerie', 'Galerie', $base . '/galerie'];
}
?>
<nav class="event-nav" aria-label="Event-Seiten">
    <div class="wrap event-nav__inner">
        <?php foreach ($tabs as [$key, $label, $path]): ?>
            <a href="<?= e(url($path)) ?>"<?= $eventTab === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
</nav>
