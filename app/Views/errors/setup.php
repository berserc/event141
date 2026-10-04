<!DOCTYPE html>
<html lang="<?= e(lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('Einrichtung erforderlich')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body class="page">
<main class="site-main">
    <article class="wrap text-page">
        <h1><?= e(t('Einrichtung erforderlich')) ?></h1>
        <p><?= t('Die Datenbank wurde noch nicht angelegt. Entweder im Browser <a href="%s">setup.php</a> aufrufen oder auf dem Server einmalig ausführen:', e(url('/setup.php'))) ?></p>
        <pre><code>php bin/install.php</code></pre>
        <p><?= t('Danach ist die Verwaltung unter <code>/admin</code> erreichbar.') ?></p>
    </article>
</main>
</body>
</html>
