<!DOCTYPE html>
<html lang="<?= e(lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('Fehler')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body class="page">
<main class="site-main">
    <article class="wrap text-page">
        <h1><?= e(t('Es ist ein Fehler aufgetreten')) ?></h1>
        <p><?= e(t('Bitte versuchen Sie es später erneut. Der Fehler wurde protokolliert.')) ?></p>
        <p><a class="btn" href="<?= e(url('/')) ?>"><?= e(t('Zur Startseite')) ?></a></p>
    </article>
</main>
</body>
</html>
