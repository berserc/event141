<?php

/**
 * Startseite einer Instanz: der Kalender aller veröffentlichten Events (Liste oder Monatsblatt, mit Filtern).
 *
 * @var list<array<string,mixed>> $calItems  Kalender-Eintraege (EventCalendar::fromLocal)
 * @var array<string,string>      $calFilter
 * @var string                    $introTitle
 * @var string                    $introText
 */
?>
<section class="hero-intro">
    <div class="wrap">
        <h1><?= e($introTitle) ?></h1>
        <?php if (trim($introText) !== ''): ?><div class="hero-intro__text"><?= $introText ?></div><?php endif; ?>
    </div>
</section>

<section class="wrap">
    <?php
    $calPath = '/';
    require dirname(__DIR__) . '/partials/_event-calendar.php';
    ?>
</section>
