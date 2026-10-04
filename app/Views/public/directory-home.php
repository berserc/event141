<?php

/**
 * Startseite des zentralen Verzeichnisses (event141.com): Produktseite von
 * Event141 und Kalender aller gemeldeten Events mit Filtern.
 *
 * @var list<array<string,mixed>> $calItems
 * @var array<string,string>      $calFilter
 * @var int                       $anzahlEvents   laufende und kommende Events
 * @var int                       $anzahlInstanzen
 */
$en      = lang() === 'en';
$kontakt = 'https://devworld-llc.com/' . ($en ? 'en/contact' : 'de/kontakt');
$produkt = 'https://devworld-llc.com/' . ($en ? 'en' : 'de') . '/event141';
$shots   = [
    ['website', t('Event-Website mit Fightcard, Zeitplan und Ergebnissen')],
    ['turnierbaum', t('Turnierbaum mit Setzliste, Freilosen und Druck als PDF')],
    ['ringansicht', t('Ringansicht für den Kampfrichtertisch')],
    ['anmeldungen', t('Anmeldungen: bestätigen, wiegen, setzen')],
];
?>
<section class="lp-hero">
    <div class="wrap">
        <p class="lp-hero__kicker"><?= e(t('Event- und Turnierverwaltung für Kampfsport')) ?></p>
        <h1><?= e(t('Alle Turniere. Ein Kalender.')) ?></h1>
        <p class="lead"><?= e(t('Event141 ist die Software für Galas und Turniere – von der Anmeldung über Turnierbaum und Zeitplan bis zum Live-Ergebnis. Und hier ist der gemeinsame Kalender aller Veranstalter, die damit arbeiten.')) ?></p>
        <div class="lp-actions">
            <a class="btn btn--primary" href="#events"><?= e(t('Events finden')) ?></a>
            <a class="btn btn--ghost btn--on-dark" href="#veranstalter"><?= e(t('Eigenes Turnier eintragen')) ?></a>
        </div>
        <div class="lp-stats">
            <span><b><?= (int) $anzahlEvents ?></b><?= e(t('laufende und kommende Events')) ?></span>
            <span><b><?= (int) $anzahlInstanzen ?></b><?= e(t('Veranstalter und Verbände')) ?></span>
        </div>
    </div>
</section>

<section class="lp-section">
    <div class="wrap">
        <h2><?= e(t('Event-Kalender')) ?></h2>
        <p class="lp-sub"><?= e(t('Turniere und Fight Nights aller Veranstalter – filterbar nach Sportart, Verband, Land und Region, als Liste oder Monatskalender.')) ?></p>
        <?php
        $calPath = '/';
        $calOrg  = true;
        require dirname(__DIR__) . '/partials/_event-calendar.php';
        ?>
    </div>
</section>

<section class="lp-section" id="software">
    <div class="wrap">
        <h2><?= e(t('Was Event141 kann')) ?></h2>
        <p class="lp-sub"><?= e(t('Eine kleine Web-Anwendung für den ganzen Wettkampf – ohne Tabellenkalkulation, ohne Abtippen.')) ?></p>
        <div class="lp-grid">
            <div class="lp-card"><h3><?= e(t('Gala & Turnier')) ?></h3><p><?= e(t('Fight Night mit Fightcard oder Turnier mit Kategorien und K.-o.-System – mehrtägig, mit Abschnitten und beliebig vielen Ringen und Tatamis.')) ?></p></div>
            <div class="lp-card"><h3><?= e(t('Regelsätze der Verbände')) ?></h3><p><?= e(t('WAKO Kickboxen, World Boxing und IFMA Muay Thai: alle Alters- und Gewichtsklassen mit Jahrgängen und Kampfzeiten per Klick als Kategorien.')) ?></p></div>
            <div class="lp-card"><h3><?= e(t('Anmeldung durch Gyms')) ?></h3><p><?= e(t('Vereine registrieren sich, pflegen ihre Sportler und melden sie selbst an – mit Hinweis, wenn Alter oder Gewicht nicht zur Klasse passen.')) ?></p></div>
            <div class="lp-card"><h3><?= e(t('Spinne & Running Order')) ?></h3><p><?= e(t('Turnierbäume mit Setzliste und Freilosen, lastverteilter Zeitplan, Running Order je Ring – alles filterbar und als PDF druckbar.')) ?></p></div>
            <div class="lp-card"><h3><?= e(t('Live am Wettkampftag')) ?></h3><p><?= e(t('Ringansicht für den Kampfrichtertisch: Kampf starten, Sieger eintragen. Sieger rücken im Baum auf, die Website zeigt LIVE und Ergebnisse sofort.')) ?></p></div>
            <div class="lp-card"><h3><?= e(t('Eigene Event-Website')) ?></h3><p><?= e(t('Jeder Veranstalter bekommt seinen Kalender, jedes Event seine Seite – auf Deutsch und Englisch, mit Sponsoren, Tickets, Galerie und Bericht.')) ?></p></div>
        </div>
    </div>
</section>

<section class="lp-section">
    <div class="wrap">
        <h2><?= e(t('Einblicke')) ?></h2>
        <div class="lp-shots">
            <?php foreach ($shots as [$bild, $text]): ?>
                <figure>
                    <a href="<?= e(asset('img/landing/' . $bild . '.webp')) ?>" target="_blank" rel="noopener"><img src="<?= e(asset('img/landing/' . $bild . '-thumb.webp')) ?>" alt="<?= e($text) ?>" loading="lazy" width="720" height="450"></a>
                    <figcaption><?= e($text) ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="lp-section" id="veranstalter">
    <div class="wrap">
        <h2><?= e(t('So kommt dein Turnier in den Kalender')) ?></h2>
        <p class="lp-sub"><?= e(t('Der Kalender zeigt Events aus Event141-Instanzen. Ob ein Event hier erscheint, entscheidet der Veranstalter mit einem Haken im Event.')) ?></p>
        <div class="lp-grid lp-steps">
            <div class="lp-card"><h3><?= e(t('Instanz')) ?></h3><p><?= e(t('Als Verband oder Verein eine eigene Adresse wie verband.event141.com bekommen – oder Event141 auf dem eigenen Webspace installieren.')) ?></p></div>
            <div class="lp-card"><h3><?= e(t('Event anlegen')) ?></h3><p><?= e(t('Jedes Turnier ist unter verband.event141.com/turniername erreichbar; die Startseite der Instanz ist der Kalender des Verbands.')) ?></p></div>
            <div class="lp-card"><h3><?= e(t('Ins Verzeichnis')) ?></h3><p><?= e(t('Im Event „Im Event141-Verzeichnis listen“ anhaken, Sportart, Verband, Land und Region angeben – Sekunden später steht es hier.')) ?></p></div>
        </div>
    </div>
</section>

<section class="lp-section" id="preise">
    <div class="wrap">
        <h2><?= e(t('Preise')) ?></h2>
        <p class="lp-sub"><?= e(t('Event141 ist Open Source. Der Eintrag im Kalender ist kostenlos.')) ?></p>
        <div class="lp-price">
            <div class="lp-card">
                <h3>Free</h3>
                <p class="lp-price__amount">€ 0 <small><?= e(t('für immer')) ?></small></p>
                <ul>
                    <li><?= e(t('1 aktives Event')) ?></li>
                    <li><?= e(t('Event-Website, Zeitplan, Ergebnisse')) ?></li>
                    <li><?= e(t('Anmeldungen und Gym-Bereich')) ?></li>
                    <li><?= e(t('Eintrag im Kalender')) ?></li>
                </ul>
            </div>
            <div class="lp-card is-pro">
                <h3>Pro</h3>
                <p class="lp-price__amount">€ 19,90 <small><?= e(t('pro Monat')) ?></small></p>
                <ul>
                    <li><?= e(t('Unbegrenzt viele Events – der ganze Verbandskalender')) ?></li>
                    <li><?= e(t('Automatischer Turnierbaum')) ?></li>
                    <li><?= e(t('Kopplung mit Gym141')) ?></li>
                    <li><?= e(t('Jederzeit kündbar')) ?></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="wrap">
    <div class="lp-cta">
        <h2><?= e(t('Dein Verband, dein Kalender')) ?></h2>
        <p><?= e(t('Wir richten die Instanz ein – oder du lädst Event141 herunter und hostest selbst.')) ?></p>
        <div class="lp-actions" style="justify-content:center">
            <a class="btn btn--primary" href="<?= e($kontakt) ?>"><?= e(t('Instanz anfragen')) ?></a>
            <a class="btn btn--ghost btn--on-dark" href="<?= e($produkt) ?>#download"><?= e(t('Herunterladen und selbst hosten')) ?></a>
        </div>
    </div>
</section>
