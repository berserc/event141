<?php

use App\Core\Ruleset;

/**
 * @var array<string,array<string,mixed>> $rulesets
 */
?>
<div class="page-head">
    <div>
        <h1>Regelsätze</h1>
        <p class="page-head__sub">Alters- und Gewichtsklassen, Kampfzeiten und Disziplinen der Verbände – als Vorlage für die Kategorien eines Turniers.</p>
    </div>
</div>

<div class="notice">
    Die Tabellen sind eine Arbeitshilfe und kommen mit jedem Update mit. Maßgeblich ist immer das aktuelle Regelwerk des Verbands.
    Übernommen wird ein Regelsatz beim Event unter <strong>Kategorien → „Aus Regelsatz anlegen“</strong>; die erzeugten Kategorien lassen sich danach frei anpassen.
</div>

<div class="card">
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Regelsatz</th><th>Verband</th><th>Disziplinen</th><th class="num">Kategorien</th><th>Stand</th></tr></thead>
            <tbody>
            <?php foreach ($rulesets as $rs): ?>
                <tr>
                    <td><a class="strong" href="<?= e(url('/admin/regelsaetze/' . $rs['code'])) ?>"><?= e($rs['name']) ?></a></td>
                    <td><?= e($rs['org']) ?></td>
                    <td><small><?= e(implode(', ', array_column($rs['disciplines'], 'name'))) ?></small></td>
                    <td class="num"><?= count(Ruleset::expand($rs)) ?></td>
                    <td><small><?= e($rs['version']) ?></small></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rulesets === []): ?>
                <tr><td colspan="5" class="empty">Keine Regelsätze hinterlegt.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
