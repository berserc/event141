<?php

use App\Core\Auth;
use App\Models\BoutRepo;

/**
 * Eine Kampfzeile in Listen (Fightcard, Turnierbaum-Tabelle, Zeitplan).
 *
 * @var array<string,mixed> $bout
 * @var array<string,mixed> $event
 * @var bool                $showPlace  Spalte "Abschnitt/Ring" anzeigen
 * @var bool                $showMove   Pfeile zum Verschieben
 */
$showPlace = $showPlace ?? true;
$showMove  = $showMove ?? false;
$id        = (int) $event['id'];
$red       = BoutRepo::cornerName($bout, 'red');
$blue      = BoutRepo::cornerName($bout, 'blue');
$isBreak   = (int) $bout['is_break'] === 1;
?>
<tr class="bout-row bout-row--<?= e($bout['status']) ?><?= $isBreak ? ' bout-row--break' : '' ?><?= (int) $bout['active'] === 1 ? '' : ' bout-row--inactive' ?>">
    <td class="num mono">
        <?php if (!$isBreak): ?>
            <a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $bout['id'])) ?>">#<?= (int) $bout['bout_no'] ?></a>
        <?php else: ?>
            <a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $bout['id'])) ?>">⏸</a>
        <?php endif; ?>
        <?php if ($bout['scheduled_time'] !== ''): ?><br><small><?= e($bout['scheduled_time']) ?></small><?php endif; ?>
    </td>
    <?php if ($isBreak): ?>
        <td colspan="<?= $showPlace ? 4 : 3 ?>"><em><?= e($bout['title'] !== '' ? $bout['title'] : 'Pause') ?></em>
            <?php if ($bout['note'] !== ''): ?><small class="muted"><?= e($bout['note']) ?></small><?php endif; ?></td>
    <?php else: ?>
        <td>
            <?php if ($bout['title'] !== ''): ?><strong class="bout-title"><?= e($bout['title']) ?></strong><br><?php endif; ?>
            <small class="muted"><?= e($bout['category_name'] ?? '') ?><?= $bout['round_label'] !== '' ? ' · ' . e($bout['round_label']) : '' ?>
                · <?= (int) $bout['rounds'] ?>×<?= e(rtrim(rtrim(number_format((float) $bout['round_minutes'], 1, ',', ''), '0'), ',')) ?> min</small>
        </td>
        <td class="corner corner--red<?= $bout['winner'] === 'red' ? ' is-winner' : '' ?>">
            <?= $red !== '' ? e($red) : '<span class="muted">offen</span>' ?>
            <?php if ($bout['red_gym'] !== null): ?><br><small class="muted"><?= e($bout['red_gym']) ?></small><?php endif; ?>
        </td>
        <td class="corner corner--blue<?= $bout['winner'] === 'blue' ? ' is-winner' : '' ?>">
            <?= $blue !== '' ? e($blue) : '<span class="muted">offen</span>' ?>
            <?php if ($bout['blue_gym'] !== null): ?><br><small class="muted"><?= e($bout['blue_gym']) ?></small><?php endif; ?>
        </td>
        <?php if ($showPlace): ?>
            <td>
                <?php if ($bout['session_id'] !== null && $bout['venue_id'] !== null): ?>
                    <small><?= e(format_date($bout['day_date'])) ?> · <?= e($bout['session_name']) ?><br>
                    <span class="venue-dot" style="background:<?= e($bout['venue_color'] ?: '#e63946') ?>"></span> <?= e($bout['venue_name']) ?></small>
                <?php else: ?>
                    <small class="muted">nicht eingeplant</small>
                <?php endif; ?>
            </td>
        <?php endif; ?>
    <?php endif; ?>
    <td>
        <span class="pill pill--bout-<?= e($bout['status']) ?>"><?= e(BoutRepo::STATUS[$bout['status']] ?? $bout['status']) ?></span>
        <?php if ((int) $bout['active'] !== 1): ?><span class="badge badge--muted">inaktiv</span><?php endif; ?>
        <?php if ($bout['status'] === 'beendet' && !$isBreak): ?>
            <br><small><?= e(BoutRepo::WINNER[$bout['winner']] ?? '') ?><?= $bout['method'] !== '' ? ' · ' . e($bout['method']) : '' ?><?= $bout['result_note'] !== '' ? ' ' . e($bout['result_note']) : '' ?></small>
        <?php endif; ?>
    </td>
    <td class="row-actions">
        <a href="<?= e(url('/admin/events/' . $id . '/kampf/' . $bout['id'])) ?>"><?= Auth::canScore() && !$isBreak && $bout['status'] !== 'beendet' ? 'Ergebnis' : 'Öffnen' ?></a>
        <?php if ($showMove && Auth::canWrite()): ?>
            <form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $bout['id'] . '/verschieben')) ?>" class="inline">
                <?= csrf_field() ?>
                <button class="linklike" name="dir" value="up" type="submit" title="nach oben">▲</button>
                <button class="linklike" name="dir" value="down" type="submit" title="nach unten">▼</button>
            </form>
        <?php endif; ?>
    </td>
</tr>
