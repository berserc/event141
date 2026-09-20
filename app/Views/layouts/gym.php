<?php

use App\Core\GymAuth;

/**
 * Layout des Gym-Bereichs (/gym): dunkles Design der Website, eigene Navigation.
 *
 * @var string                   $content
 * @var string                   $title
 * @var array<string,mixed>|null $authGym
 * @var array<string,string>     $settings
 */
$orgName = $settings['org_name'] ?? $appName;
$current = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

$items = [
    ['/gym', 'Übersicht'],
    ['/gym/sportler', 'Sportler'],
    ['/gym/gym141', 'Gym141'],
    ['/gym/profil', 'Gym-Daten'],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title !== '' ? $title . ' | Gym-Bereich' : 'Gym-Bereich') ?> – <?= e($orgName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/event.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/fightcard.css')) ?>">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="page page--gym">

<?php if (!empty($showEnvBanner)): ?>
    <div class="env-banner" role="status">Testumgebung</div>
<?php endif; ?>

<?php if (GymAuth::isAdminView()): ?>
    <div class="env-banner" role="status">
        Verwaltungsansicht: Sie sind als Gym „<?= e($authGym['name'] ?? '') ?>“ unterwegs.
        <form method="post" action="<?= e(url('/gym/logout')) ?>" class="inline"><?= csrf_field() ?>
            <button class="linklike" type="submit" style="color:#fff;text-decoration:underline">Zurück zur Verwaltung</button>
        </form>
    </div>
<?php endif; ?>

<header class="site-header">
    <div class="wrap site-header__inner">
        <a class="site-brand" href="<?= e(url('/gym')) ?>">
            <?php if (site_logo() !== ''): ?>
                <img src="<?= e(site_logo()) ?>" alt="" style="height:2.6rem;width:auto;max-width:9rem;object-fit:contain">
            <?php endif; ?>
            <span class="site-brand__name"><?= e($orgName) ?> <small>Gym-Bereich</small></span>
        </a>

        <nav class="site-nav" aria-label="Gym-Navigation">
            <?php if (!empty($authGym)): ?>
                <?php foreach ($items as [$path, $label]): ?>
                    <a href="<?= e(url($path)) ?>"<?= $current === url($path) || ($path !== '/gym' && str_starts_with($current, url($path))) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php endforeach; ?>
                <form method="post" action="<?= e(url('/gym/logout')) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn btn--ghost btn--sm btn--on-dark" type="submit">Abmelden</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('/')) ?>">Website</a>
                <a href="<?= e(url('/gym/login')) ?>">Anmelden</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="inhalt" class="site-main">
    <div class="wrap gym-wrap">
        <?php if (!empty($flash)): ?>
            <div class="flash-stack">
                <?php foreach ($flash as $message): ?>
                    <div class="flash flash--<?= e($message['type']) ?>" role="status"><?= e($message['message']) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </div>
</main>

<footer class="site-footer">
    <div class="wrap site-footer__inner">
        <div class="site-footer__col"><p class="site-footer__name"><?= e($orgName) ?></p></div>
        <nav class="site-footer__col site-footer__nav" aria-label="Rechtliches">
            <?php foreach ($footerPages as $footerPage): ?>
                <a href="<?= e(url('/seite/' . $footerPage['slug'])) ?>"><?= e($footerPage['title']) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</footer>
</body>
</html>
