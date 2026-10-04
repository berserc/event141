<?php

/**
 * Filterzeile ueber den Turnierbaeumen: Disziplin, Altersklasse, Geschlecht,
 * Suche. Schickt die Auswahl per GET an $filterAction.
 *
 * @var string                $filterAction  Zieladresse (ohne Query)
 * @var array<string,string>  $filter        aus CategoryFilter::fromQuery()
 * @var array{disciplines:list<string>,ages:array<string,string>,genders:list<string>} $filterOptions
 * @var int                   $filterShown   Anzahl angezeigter Turnierbaeume/Kategorien
 * @var int                   $filterTotal   Anzahl insgesamt
 */
$aktiv = \App\Core\CategoryFilter::isActive($filter);
?>
<form method="get" action="<?= e($filterAction) ?>" class="cat-filter" role="search" aria-label="<?= e(t('Turnierbäume filtern')) ?>">
    <?php if (count($filterOptions['disciplines']) > 1): ?>
        <label><span><?= e(t('Disziplin')) ?></span>
            <select name="disziplin" onchange="this.form.submit()">
                <option value=""><?= e(t('alle')) ?></option>
                <?php foreach ($filterOptions['disciplines'] as $d): ?>
                    <option value="<?= e($d) ?>" <?= $filter['disziplin'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>

    <?php if (count($filterOptions['ages']) > 1): ?>
        <label><span><?= e(t('Altersklasse')) ?></span>
            <select name="alter" onchange="this.form.submit()">
                <option value=""><?= e(t('alle')) ?></option>
                <?php foreach ($filterOptions['ages'] as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $filter['alter'] === (string) $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>

    <?php if (count($filterOptions['genders']) > 1): ?>
        <label><span><?= e(t('Geschlecht')) ?></span>
            <select name="geschlecht" onchange="this.form.submit()">
                <option value=""><?= e(t('alle')) ?></option>
                <option value="m" <?= $filter['geschlecht'] === 'm' ? 'selected' : '' ?>><?= e(t('männlich')) ?></option>
                <option value="w" <?= $filter['geschlecht'] === 'w' ? 'selected' : '' ?>><?= e(t('weiblich')) ?></option>
            </select>
        </label>
    <?php endif; ?>

    <label class="cat-filter__search"><span><?= e(t('Suche')) ?></span>
        <input type="search" name="suche" value="<?= e($filter['suche']) ?>" placeholder="<?= e(t('Kategorie, Sportler oder Gym')) ?>">
    </label>

    <button class="btn btn--sm btn--primary" type="submit"><?= e(t('Filtern')) ?></button>
    <?php if ($aktiv): ?>
        <a class="cat-filter__reset" href="<?= e($filterAction) ?>"><?= e(t('Zurücksetzen')) ?></a>
    <?php endif; ?>

    <span class="cat-filter__count"><?= e($aktiv ? t('%1$d von %2$d', $filterShown, $filterTotal) : t('%d insgesamt', $filterTotal)) ?></span>
</form>
