<?php

use App\Core\EventCalendar;
use App\Models\EventRepo;

/**
 * Event-Kalender mit Filtern – als Liste oder Monatskalender.
 *
 * @var list<array<string,mixed>> $calItems  Eintraege (EventCalendar::fromLocal / fromDirectory)
 * @var array<string,string>      $calFilter aus EventCalendar::filterFromQuery()
 * @var string                    $calPath   Pfad der Seite (z. B. "/")
 * @var bool                      $calOrg    Veranstalter je Eintrag zeigen (Verzeichnis)
 */
$calOrg   = $calOrg ?? false;
$opt      = EventCalendar::options($calItems);
$liste    = EventCalendar::apply($calItems, $calFilter);
$link     = static fn (array $o = []): string => url($calPath, EventCalendar::query($calFilter, $o)) . '#events';
$kalender = $calFilter['ansicht'] === 'kalender';
$aktiv    = EventCalendar::isActive($calFilter);

$auswahl = static function (string $name, string $label, array $werte, string $gewaehlt, bool $folded = true): string {
    if (count($werte) < 2 && $gewaehlt === '') {
        return '';
    }

    $html = '<label><span>' . e($label) . '</span><select name="' . e($name) . '" onchange="this.form.submit()"><option value="">' . e(t('alle')) . '</option>';

    foreach ($werte as $key => $text) {
        $wert  = $folded ? (string) $text : (string) $key;
        $html .= '<option value="' . e($wert) . '"' . (fold_text($wert) === fold_text($gewaehlt) ? ' selected' : '') . '>' . e((string) $text) . '</option>';
    }

    return $html . '</select></label>';
};

// Statusmarken eines Eintrags
$marken = static function (array $i): string {
    $out = '';

    if ($i['status'] === 'laufend') {
        $out .= '<span class="live-badge">LIVE</span>';
    } elseif ($i['status'] === 'beendet' || (string) $i['ends_on'] < date('Y-m-d')) {
        $out .= '<span class="cal-badge">' . e(t('Beendet')) . '</span>';
    } elseif ($i['registration_open']) {
        $out .= '<span class="cal-badge cal-badge--open">' . e((string) $i['registration_until'] !== ''
            ? t('Anmeldung offen bis %s', format_date($i['registration_until']))
            : t('Anmeldung offen')) . '</span>';
    }

    return $out;
};
?>
<div class="event-cal" id="events">
    <form method="get" action="<?= e(url($calPath)) ?>#events" class="cat-filter" role="search" aria-label="<?= e(t('Events filtern')) ?>">
        <?php if ($kalender): ?><input type="hidden" name="ansicht" value="kalender"><?php endif; ?>
        <?= $auswahl('sportart', t('Sportart'), $opt['sports'], $calFilter['sportart']) ?>
        <?= $auswahl('verband', t('Verband'), $opt['federations'], $calFilter['verband']) ?>
        <?php
        $laender = [];
        foreach ($opt['countries'] as $iso => $nameLand) { $laender[$iso] = trim(flag_emoji((string) $iso) . ' ' . $nameLand); }
        ?>
        <?= $auswahl('land', t('Land'), $laender, $calFilter['land'], false) ?>
        <?= $auswahl('region', t('Region'), $opt['regions'], $calFilter['region']) ?>
        <?= $auswahl('art', t('Art'), ['turnier' => t('Turnier'), 'gala' => t('Gala / Fight Night')], $calFilter['art'], false) ?>
        <?php if (!$kalender): ?>
            <label><span><?= e(t('Zeitraum')) ?></span>
                <select name="zeit" onchange="this.form.submit()">
                    <option value=""><?= e(t('laufend und kommend')) ?></option>
                    <option value="vergangen" <?= $calFilter['zeit'] === 'vergangen' ? 'selected' : '' ?>><?= e(t('vergangen')) ?></option>
                    <option value="alle" <?= $calFilter['zeit'] === 'alle' ? 'selected' : '' ?>><?= e(t('alle')) ?></option>
                </select>
            </label>
        <?php endif; ?>
        <label class="cat-filter__search"><span><?= e(t('Suche')) ?></span>
            <input type="search" name="suche" value="<?= e($calFilter['suche']) ?>" placeholder="<?= e(t('Event, Ort oder Veranstalter')) ?>">
        </label>
        <button class="btn btn--sm btn--primary" type="submit"><?= e(t('Filtern')) ?></button>
        <?php if ($aktiv): ?><a class="cat-filter__reset" href="<?= e(url($calPath, $kalender ? ['ansicht' => 'kalender'] : [])) ?>#events"><?= e(t('Zurücksetzen')) ?></a><?php endif; ?>

        <span class="cal-views">
            <a href="<?= e($link(['ansicht' => 'liste', 'monat' => ''])) ?>"<?= !$kalender ? ' aria-current="true"' : '' ?>>☰ <?= e(t('Liste')) ?></a>
            <a href="<?= e($link(['ansicht' => 'kalender'])) ?>"<?= $kalender ? ' aria-current="true"' : '' ?>>▦ <?= e(t('Kalender')) ?></a>
        </span>
    </form>

    <?php if ($kalender): ?>
        <?php
        $alle  = EventCalendar::apply($calItems, $calFilter, false);
        $blatt = EventCalendar::month($alle, EventCalendar::startMonth($alle, $calFilter['monat']));
        ?>
        <div class="cal-month__head">
            <a class="btn btn--ghost btn--on-dark btn--sm" href="<?= e($link(['monat' => $blatt['prev']])) ?>" aria-label="<?= e(t('Voriger Monat')) ?>">‹</a>
            <h3><?= e($blatt['titel']) ?></h3>
            <a class="btn btn--ghost btn--on-dark btn--sm" href="<?= e($link(['monat' => $blatt['next']])) ?>" aria-label="<?= e(t('Nächster Monat')) ?>">›</a>
        </div>
        <div class="cal-month" role="grid">
            <?php foreach (['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'] as $wt): ?><div class="cal-month__wd"><?= e(t($wt)) ?></div><?php endforeach; ?>
            <?php foreach ($blatt['wochen'] as $woche): ?>
                <?php foreach ($woche as $tag): ?>
                    <div class="cal-day<?= $tag['imMonat'] ? '' : ' is-out' ?><?= $tag['heute'] ? ' is-today' : '' ?><?= $tag['items'] === [] ? ' is-empty' : '' ?>">
                        <span class="cal-day__no"><?= (int) $tag['tag'] ?><small><?= e(format_date($tag['datum'])) ?></small></span>
                        <?php foreach ($tag['items'] as $i): ?>
                            <a class="cal-chip cal-chip--<?= e($i['type']) ?>" href="<?= e($i['url']) ?>" title="<?= e($i['name'] . ' · ' . format_date_range($i['starts_on'], $i['ends_on']) . ($i['city'] !== '' ? ' · ' . $i['city'] : '')) ?>">
                                <?= e($i['name']) ?><?php if ($i['city'] !== ''): ?> <small><?= e($i['city']) ?></small><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?php if ($liste === []): ?>
            <p class="muted"><?= e($aktiv ? t('Kein Event passt zu diesem Filter.') : t('Derzeit ist kein Event angekündigt.')) ?></p>
        <?php endif; ?>

        <?php $monat = ''; ?>
        <div class="cal-list">
            <?php foreach ($liste as $i): ?>
                <?php if (substr($i['starts_on'], 0, 7) !== $monat): ?>
                    <?php $monat = substr($i['starts_on'], 0, 7); ?>
                    <h3 class="cal-list__month"><?= e(EventCalendar::month([], $monat)['titel']) ?></h3>
                <?php endif; ?>
                <a class="cal-row" href="<?= e($i['url']) ?>">
                    <span class="cal-row__date">
                        <strong><?= e(format_date_range($i['starts_on'], $i['ends_on'])) ?></strong>
                        <small><?= e(t(EventRepo::TYPES[$i['type']] ?? '')) ?></small>
                    </span>
                    <span class="cal-row__main">
                        <strong><?= e($i['name']) ?></strong>
                        <small><?= e(implode(' · ', array_filter([$calOrg ? $i['org'] : '', $i['sport'], $i['federation']]))) ?></small>
                    </span>
                    <span class="cal-row__place">
                        <span><?= e(trim(flag_emoji($i['country']) . ' ' . $i['city'])) ?></span>
                        <small><?= e(implode(', ', array_filter([$i['venue'], $i['region']]))) ?></small>
                    </span>
                    <span class="cal-row__state"><?= $marken($i) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
