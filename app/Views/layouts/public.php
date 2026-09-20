<?php

/**
 * Öffentliches Layout (Event-Website).
 *
 * @var string                        $content
 * @var string                        $title
 * @var string                        $metaDesc
 * @var string                        $appName
 * @var string                        $activePage
 * @var list<array<string,mixed>>     $footerPages
 * @var array<string,string>          $settings
 */
$orgName   = $settings['org_name'] ?? $appName;
$pageTitle = $title !== '' ? $title . ' | ' . $orgName : $orgName;
$gymArea   = $gymArea ?? true;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <?php if (($metaDesc ?? '') !== ''): ?>
        <meta name="description" content="<?= e($metaDesc) ?>">
    <?php endif; ?>
    <?php if (!empty($noindex)): ?>
        <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <meta name="theme-color" content="#101014">
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/event.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/fightcard.css')) ?>">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="page page--<?= e($activePage) ?>">
<a class="skip-link" href="#inhalt">Direkt zum Inhalt</a>

<?php if (!empty($showEnvBanner)): ?>
    <div class="env-banner" role="status">
        Testumgebung – Änderungen hier wirken sich <strong>nicht</strong> auf die Produktivseite aus.
    </div>
<?php endif; ?>

<header class="site-header">
    <div class="wrap site-header__inner">
        <a class="site-brand" href="<?= e(url('/')) ?>">
            <?php if (site_logo() !== ''): ?>
                <img src="<?= e(site_logo()) ?>" alt="" style="height:2.6rem;width:auto;max-width:9rem;object-fit:contain">
            <?php endif; ?>
            <span class="site-brand__name">
                <?= e($orgName) ?>
                <?php if (($settings['org_tagline'] ?? '') !== ''): ?>
                    <small><?= e($settings['org_tagline']) ?></small>
                <?php endif; ?>
            </span>
        </a>

        <nav class="site-nav" aria-label="Hauptnavigation">
            <a href="<?= e(url('/')) ?>"<?= $activePage === 'home' ? ' aria-current="page"' : '' ?>>Events</a>
            <?php if ($gymArea): ?>
                <a href="<?= e(url('/gym')) ?>" class="site-nav__gym">Gym-Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="inhalt" class="site-main">
    <?php if (!empty($flash)): ?>
        <div class="wrap" style="padding-top:1rem">
            <?php foreach ($flash as $message): ?>
                <div class="flash flash--<?= e($message['type']) ?>" role="status"><?= e($message['message']) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="wrap site-footer__inner">
        <div class="site-footer__col">
            <p class="site-footer__name"><?= e($orgName) ?></p>
            <?php if (($settings['org_street'] ?? '') !== ''): ?>
                <p><?= e($settings['org_street']) ?><br>
                   <?= e(trim(($settings['org_zip'] ?? '') . ' ' . ($settings['org_city'] ?? ''))) ?></p>
            <?php endif; ?>
        </div>

        <div class="site-footer__col">
            <?php if (($settings['org_email'] ?? '') !== ''): ?>
                <p><?= mail_link($settings['org_email'], 'contact-link contact-link--invert') ?></p>
            <?php endif; ?>
            <?php if (($settings['org_phone'] ?? '') !== ''): ?>
                <p><?= tel_link($settings['org_phone'], 'contact-link contact-link--invert') ?></p>
            <?php endif; ?>
            <?php if (($settings['org_website'] ?? '') !== ''): ?>
                <p><?= link_out($settings['org_website'], '', 'contact-link contact-link--invert') ?></p>
            <?php endif; ?>
        </div>

        <nav class="site-footer__col site-footer__nav" aria-label="Rechtliches">
            <?php foreach ($footerPages as $footerPage): ?>
                <a href="<?= e(url('/seite/' . $footerPage['slug'])) ?>"><?= e($footerPage['title']) ?></a>
            <?php endforeach; ?>
            <?php if ($gymArea): ?>
                <a href="<?= e(url('/gym/registrieren')) ?>">Gym registrieren</a>
            <?php endif; ?>
        </nav>
    </div>
    <p class="site-footer__powered">Powered by <a href="https://event141.com" rel="noopener">Event141</a></p>
</footer>
<script src="<?= e(asset('js/media.js')) ?>" defer></script>
<script src="<?= e(asset('js/event.js')) ?>" defer></script>
</body>
</html>
