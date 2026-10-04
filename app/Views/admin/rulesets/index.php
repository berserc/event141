<?php

use App\Core\Ruleset;

/**
 * @var array<string,array<string,mixed>> $rulesets
 */
?>
<div class="page-head">
    <div>
        <h1><?= e(t('Regelsätze')) ?></h1>
        <p class="page-head__sub"><?= e(t('Alters- und Gewichtsklassen, Kampfzeiten und Disziplinen der Verbände – als Vorlage für die Kategorien eines Turniers.')) ?></p>
    </div>
</div>

<div class="notice">
    <?= t('Die Tabellen sind eine Arbeitshilfe und kommen mit jedem Update mit. Maßgeblich ist immer das aktuelle Regelwerk des Verbands. Übernommen wird ein Regelsatz beim Event unter <strong>Kategorien → „Aus Regelsatz anlegen“</strong>; die erzeugten Kategorien lassen sich danach frei anpassen.') ?>
</div>

<div class="card">
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th><?= e(t('Regelsatz')) ?></th><th><?= e(t('Verband')) ?></th><th><?= e(t('Disziplinen')) ?></th><th class="num"><?= e(t('Kategorien')) ?></th><th><?= e(t('Stand')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($rulesets as $rs): ?>
                <tr>
                    <td><a class="strong" href="<?= e(url('/admin/regelsaetze/' . $rs['code'])) ?>"><?= e(t($rs['name'])) ?></a></td>
                    <td><?= e(t($rs['org'])) ?></td>
                    <td><small><?= e(implode(', ', array_map(static fn ($name): string => t((string) $name), array_column($rs['disciplines'], 'name')))) ?></small></td>
                    <td class="num"><?= count(Ruleset::expand($rs)) ?></td>
                    <td><small><?= e(t($rs['version'])) ?></small></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rulesets === []): ?>
                <tr><td colspan="5" class="empty"><?= e(t('Keine Regelsätze hinterlegt.')) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
