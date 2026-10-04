<?php

use App\Models\BoutRepo;

/**
 * Ein Kampf als Karte (Fightcard, Zeitplan, Live).
 *
 * @var array<string,mixed> $bout
 */
$red     = BoutRepo::cornerName($bout, 'red');
$blue    = BoutRepo::cornerName($bout, 'blue');
$isBreak = (int) $bout['is_break'] === 1;
$komma   = lang() === 'en' ? '.' : ',';
?>
<?php if ($isBreak): ?>
    <div class="fight fight--break">
        <span><?= e(t($bout['title'] ?: 'Pause')) ?><?= $bout['scheduled_time'] !== '' ? ' · ' . e($bout['scheduled_time']) : '' ?></span>
        <?php if ($bout['note'] !== ''): ?><small><?= e($bout['note']) ?></small><?php endif; ?>
    </div>
<?php else: ?>
    <div class="fight fight--<?= e($bout['status']) ?>">
        <div class="fight__head">
            <a class="fight__no" href="<?= e(url('/e/' . $event['slug'] . '/kampf/' . $bout['id'])) ?>">#<?= (int) $bout['bout_no'] ?></a>
            <span class="fight__info">
                <?php if ($bout['title'] !== ''): ?><strong><?= e($bout['title']) ?></strong> · <?php endif; ?>
                <?= e($bout['category_name'] ?? '') ?><?= $bout['round_label'] !== '' ? ' · ' . e(t($bout['round_label'])) : '' ?>
                · <?= (int) $bout['rounds'] ?>×<?= e(rtrim(rtrim(number_format((float) $bout['round_minutes'], 1, $komma, ''), '0'), $komma)) ?> min
            </span>
            <span class="fight__where">
                <?= isset($times[(int) $bout['id']]) ? e($times[(int) $bout['id']]['label']) . ' · ' : ($bout['scheduled_time'] !== '' ? e($bout['scheduled_time']) . ' · ' : '') ?><?= e($bout['venue_name'] ?? '') ?>
                <?php if ($bout['status'] === 'laufend'): ?><span class="live-badge">LIVE</span><?php endif; ?>
                <?php if ($bout['status'] === 'abgesagt'): ?><span class="badge-dark"><?= e(t('abgesagt')) ?></span><?php endif; ?>
            </span>
        </div>
        <div class="fight__body">
            <div class="fighter fighter--red<?= $bout['winner'] === 'red' ? ' is-winner' : '' ?>">
                <?php if (($bout['red_photo'] ?? '') !== ''): ?><img src="<?= e(upload_url($bout['red_photo'])) ?>" alt="" class="fighter__photo"><?php endif; ?>
                <span class="fighter__name"><?= $red !== '' ? e($red) : '<span class="muted">' . e(t('offen')) . '</span>' ?></span>
                <?php if ($bout['red_gym'] !== null): ?><span class="fighter__gym"><?= e(flag_emoji((string) $bout['red_nat'])) ?> <?= e($bout['red_gym']) ?><?= $bout['red_city'] ? ', ' . e($bout['red_city']) : '' ?></span><?php endif; ?>
                <?php if ($bout['red_entry_id'] !== null): ?><span class="fighter__rec"><?= (int) $bout['red_w'] ?>-<?= (int) $bout['red_l'] ?>-<?= (int) $bout['red_d'] ?></span><?php endif; ?>
            </div>
            <div class="fight__vs">
                <?php if ($bout['status'] === 'beendet'): ?>
                    <span class="fight__result"><?= e($bout['winner'] === 'draw' ? t('Unentschieden') : ($bout['winner'] === 'none' ? '–' : t('Sieg'))) ?><?= $bout['method'] !== '' ? '<br><small>' . e(t($bout['method'])) . '</small>' : '' ?><?= $bout['result_note'] !== '' ? '<br><small>' . e($bout['result_note']) . '</small>' : '' ?></span>
                <?php else: ?>
                    VS
                <?php endif; ?>
            </div>
            <div class="fighter fighter--blue<?= $bout['winner'] === 'blue' ? ' is-winner' : '' ?>">
                <?php if (($bout['blue_photo'] ?? '') !== ''): ?><img src="<?= e(upload_url($bout['blue_photo'])) ?>" alt="" class="fighter__photo"><?php endif; ?>
                <span class="fighter__name"><?= $blue !== '' ? e($blue) : '<span class="muted">' . e(t('offen')) . '</span>' ?></span>
                <?php if ($bout['blue_gym'] !== null): ?><span class="fighter__gym"><?= e(flag_emoji((string) $bout['blue_nat'])) ?> <?= e($bout['blue_gym']) ?><?= $bout['blue_city'] ? ', ' . e($bout['blue_city']) : '' ?></span><?php endif; ?>
                <?php if ($bout['blue_entry_id'] !== null): ?><span class="fighter__rec"><?= (int) $bout['blue_w'] ?>-<?= (int) $bout['blue_l'] ?>-<?= (int) $bout['blue_d'] ?></span><?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
