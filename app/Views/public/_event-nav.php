<?php

/**
 * Unternavigation einer Event-Seite.
 *
 * @var array<string,mixed> $event
 * @var string              $eventTab
 */
$base = '/e/' . $event['slug'];
$tabs = [
    ['uebersicht', t('Übersicht'), $base],
    ['kaempfe', $event['type'] === 'gala' ? t('Fightcard') : t('Turnierplan'), $base . '/kaempfe'],
    ['zeitplan', t('Zeitplan'), $base . '/zeitplan'],
];

if ((int) $event['show_entries'] === 1) {
    $tabs[] = ['teilnehmer', t('Teilnehmer'), $base . '/teilnehmer'];
}

if ((int) $event['show_results'] === 1) {
    $tabs[] = ['ergebnisse', t('Ergebnisse'), $base . '/ergebnisse'];
}

if ((int) ($event['report_published'] ?? 0) === 1 && trim((string) ($event['report_text'] ?? '')) !== '') {
    $tabs[] = ['bericht', t('Bericht'), $base . '/bericht'];
}

if (\App\Models\GalleryRepo::hasGalleries((int) $event['id'])) {
    $tabs[] = ['galerie', t('Galerie'), $base . '/galerie'];
}
?>
<nav class="event-nav" aria-label="<?= e(t('Event-Seiten')) ?>">
    <div class="wrap event-nav__inner">
        <?php foreach ($tabs as [$key, $label, $path]): ?>
            <a href="<?= e(url($path)) ?>"<?= $eventTab === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
</nav>
