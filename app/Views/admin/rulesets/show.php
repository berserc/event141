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
        <h1><?= e(t($ruleset['name'])) ?></h1>
        <p class="page-head__sub"><?= e(t($ruleset['org'])) ?> · <a href="<?= e(url('/admin/regelsaetze')) ?>"><?= e(t('alle Regelsätze')) ?></a></p>
    </div>
</div>

<div class="card">
    <p><strong><?= e(t('Stand:')) ?></strong> <?= e(t($ruleset['version'])) ?>
        <?php if ((string) ($ruleset['source'] ?? '') !== ''): ?> · <a href="<?= e($ruleset['source']) ?>" target="_blank" rel="noopener"><?= e(t('Quelle ↗')) ?></a><?php endif; ?></p>
    <?php if ((string) ($ruleset['age_rule'] ?? '') !== ''): ?><p><strong><?= e(t('Alter:')) ?></strong> <?= e(t($ruleset['age_rule'])) ?></p><?php endif; ?>
    <?php if ((string) ($ruleset['note'] ?? '') !== ''): ?><p class="muted"><?= e(t($ruleset['note'])) ?></p><?php endif; ?>
    <p class="muted" style="margin-bottom:0"><?= e(t('Ohne Gewähr – maßgeblich ist das aktuelle Regelwerk des Verbands.')) ?></p>
</div>

<?php foreach ($ruleset['disciplines'] as $disc): ?>
    <div class="card">
        <div class="card__head">
            <h2><?= e(t($disc['name'])) ?> <small class="muted">(<?= e($disc['short']) ?>)</small></h2>
            <span class="badge"><?= $disc['area'] === 'tatami' ? 'Tatami' : 'Ring' ?></span>
        </div>
        <?php if ((string) ($disc['equipment'] ?? '') !== ''): ?><p class="muted"><?= e(t($disc['equipment'])) ?></p><?php endif; ?>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th><?= e(t('Altersklasse')) ?></th><th><?= e(t('Alter')) ?><br><small><?= e(t('Jahrgänge %d', (int) date('Y'))) ?></small></th><th><?= e(t('Kampfzeit')) ?></th><th><?= isset($disc['divisions'][0]['styles']) ? e(t('Kategorien')) : e(t('Gewichtsklassen (kg)')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($disc['divisions'] as $div): ?>
                    <?php $klasse = t((string) $div['class']); $klasseDe = lang() === 'de' ? (string) ($div['de'] ?? '') : ''; ?>
                    <tr>
                        <td><strong><?= e($klasse) ?></strong><?php if ($klasseDe !== '' && $klasseDe !== $klasse): ?><br><small class="muted"><?= e($klasseDe) ?></small><?php endif; ?></td>
                        <td style="white-space:nowrap"><?= (int) $div['age'][0] ?>–<?= (int) $div['age'][1] ?><br><small class="muted"><?= e(birth_years((int) $div['age'][0], (int) $div['age'][1], (int) date('Y'))) ?></small></td>
                        <td style="white-space:nowrap">
                            <?php if (isset($div['styles'])): ?>
                                <?= e(t('bis %s', $zeit((float) $div['minutes']))) ?>
                            <?php else: ?>
                                <?= (int) $div['rounds'] ?> × <?= e($zeit((float) $div['minutes'])) ?><br><small class="muted"><?= e(t('Pause %s', $zeit((float) ($div['break'] ?? 1)))) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($div['styles'])): ?>
                                <?= e(implode(' · ', array_map(static fn ($style): string => t((string) $style), (array) $div['styles']))) ?> <small class="muted"><?= e(t('(je männlich/weiblich)')) ?></small>
                            <?php else: ?>
                                <?php foreach (['m' => t('m'), 'w' => t('w')] as $g => $gl): ?>
                                    <?php if (isset($div['weights'][$g])): ?>
                                        <div><small class="muted"><?= e($gl) ?>:</small> <?= e($liste((array) $div['weights'][$g])) ?></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ((string) ($div['note'] ?? '') !== ''): ?><small class="muted"><?= e(t($div['note'])) ?></small><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
