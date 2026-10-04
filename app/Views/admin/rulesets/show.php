<?php

use App\Core\Ruleset;

/**
 * @var array<string,mixed> $ruleset
 */
$zeit = static function (float $min): string {
    $sek = (int) round($min * 60);

    return intdiv($sek, 60) . ':' . str_pad((string) ($sek % 60), 2, '0', STR_PAD_LEFT);
};
$liste = static fn (array $limits): string => implode(' · ', array_map(static fn ($l): string => str_replace(' kg', '', Ruleset::weightLabel($l)), $limits));
?>
<div class="page-head">
    <div>
        <h1><?= e($ruleset['name']) ?></h1>
        <p class="page-head__sub"><?= e($ruleset['org']) ?> · <a href="<?= e(url('/admin/regelsaetze')) ?>">alle Regelsätze</a></p>
    </div>
</div>

<div class="card">
    <p><strong>Stand:</strong> <?= e($ruleset['version']) ?>
        <?php if ((string) ($ruleset['source'] ?? '') !== ''): ?> · <a href="<?= e($ruleset['source']) ?>" target="_blank" rel="noopener">Quelle ↗</a><?php endif; ?></p>
    <?php if ((string) ($ruleset['age_rule'] ?? '') !== ''): ?><p><strong>Alter:</strong> <?= e($ruleset['age_rule']) ?></p><?php endif; ?>
    <?php if ((string) ($ruleset['note'] ?? '') !== ''): ?><p class="muted"><?= e($ruleset['note']) ?></p><?php endif; ?>
    <p class="muted" style="margin-bottom:0">Ohne Gewähr – maßgeblich ist das aktuelle Regelwerk des Verbands.</p>
</div>

<?php foreach ($ruleset['disciplines'] as $disc): ?>
    <div class="card">
        <div class="card__head">
            <h2><?= e($disc['name']) ?> <small class="muted">(<?= e($disc['short']) ?>)</small></h2>
            <span class="badge"><?= $disc['area'] === 'tatami' ? 'Tatami' : 'Ring' ?></span>
        </div>
        <?php if ((string) ($disc['equipment'] ?? '') !== ''): ?><p class="muted"><?= e($disc['equipment']) ?></p><?php endif; ?>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Altersklasse</th><th>Alter</th><th>Kampfzeit</th><th><?= isset($disc['divisions'][0]['styles']) ? 'Kategorien' : 'Gewichtsklassen (kg)' ?></th></tr></thead>
                <tbody>
                <?php foreach ($disc['divisions'] as $div): ?>
                    <tr>
                        <td><strong><?= e($div['class']) ?></strong><?php if ((string) ($div['de'] ?? '') !== '' && $div['de'] !== $div['class']): ?><br><small class="muted"><?= e($div['de']) ?></small><?php endif; ?></td>
                        <td style="white-space:nowrap"><?= (int) $div['age'][0] ?>–<?= (int) $div['age'][1] ?></td>
                        <td style="white-space:nowrap">
                            <?php if (isset($div['styles'])): ?>
                                bis <?= e($zeit((float) $div['minutes'])) ?>
                            <?php else: ?>
                                <?= (int) $div['rounds'] ?> × <?= e($zeit((float) $div['minutes'])) ?><br><small class="muted">Pause <?= e($zeit((float) ($div['break'] ?? 1))) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($div['styles'])): ?>
                                <?= e(implode(' · ', (array) $div['styles'])) ?> <small class="muted">(je männlich/weiblich)</small>
                            <?php else: ?>
                                <?php foreach (['m' => 'm', 'w' => 'w'] as $g => $gl): ?>
                                    <?php if (isset($div['weights'][$g])): ?>
                                        <div><small class="muted"><?= $gl ?>:</small> <?= e($liste((array) $div['weights'][$g])) ?></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ((string) ($div['note'] ?? '') !== ''): ?><small class="muted"><?= e($div['note']) ?></small><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
