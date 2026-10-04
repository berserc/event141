<?php

use App\Models\BoutRepo;

/**
 * Ringansicht: grosse Bedienung fuer den Kampfrichtertisch.
 *
 * @var array<string,mixed>       $event
 * @var array<string,mixed>|null  $venue
 * @var list<array<string,mixed>> $venues
 * @var list<array<string,mixed>> $sessions
 * @var int                       $sessionId
 * @var list<array<string,mixed>> $bouts
 * @var list<string>              $methods
 */
$id = (int) $event['id'];
?>
<div class="page-head">
    <div>
        <h1><?= e(t('Ringansicht')) ?><?= $venue !== null ? ' – ' . e($venue['name']) : '' ?></h1>
        <p class="page-head__sub"><?= e($event['name']) ?> · <a href="<?= e(url('/admin/events/' . $id . '/zeitplan')) ?>"><?= e(t('zum Zeitplan')) ?></a></p>
    </div>
    <div class="page-head__actions">
        <form method="get" class="inline-form">
            <select name="session" onchange="this.form.submit()">
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= $sessionId === (int) $s['id'] ? 'selected' : '' ?>><?= e(format_date($s['day_date'])) ?> · <?= e(t($s['name'])) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php foreach ($venues as $v): ?>
            <a class="btn btn--sm <?= $venue !== null && (int) $v['id'] === (int) $venue['id'] ? 'btn--primary' : 'btn--ghost' ?>" href="<?= e(url('/admin/events/' . $id . '/ring/' . $v['id'], ['session' => $sessionId])) ?>"><?= e($v['name']) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($venue === null): ?>
    <div class="notice notice--warn"><?= e(t('Wettkampfstätte nicht gefunden.')) ?></div>
<?php elseif ($bouts === []): ?>
    <div class="notice"><?= e(t('Für diesen Abschnitt sind auf %s keine Kämpfe eingeplant.', $venue['name'])) ?></div>
<?php endif; ?>

<div class="ring-list">
    <?php foreach ($bouts as $b): ?>
        <?php
        $isBreak = (int) $b['is_break'] === 1;
        $red     = BoutRepo::cornerName($b, 'red');
        $blue    = BoutRepo::cornerName($b, 'blue');
        ?>
        <div class="ring-bout ring-bout--<?= e($b['status']) ?><?= $isBreak ? ' ring-bout--break' : '' ?>">
            <div class="ring-bout__head">
                <span class="ring-bout__no"><?= $isBreak ? '⏸' : '#' . (int) $b['bout_no'] ?></span>
                <span><?= e($isBreak ? t($b['title'] ?: 'Pause') : ($b['category_name'] ?? $b['title'])) ?><?= $b['round_label'] !== '' ? ' · ' . e(t($b['round_label'])) : '' ?>
                    <?php if (!$isBreak): ?><small class="muted">· <?= (int) $b['rounds'] ?>×<?= e(rtrim(rtrim(number_format((float) $b['round_minutes'], 1, ',', ''), '0'), ',')) ?> min</small><?php endif; ?></span>
                <span class="pill pill--bout-<?= e($b['status']) ?>"><?= e(t(BoutRepo::STATUS[$b['status']] ?? $b['status'])) ?></span>
            </div>

            <?php if ($isBreak): ?>
                <?php continue; ?>
            <?php endif; ?>

            <div class="versus versus--big">
                <div class="versus__corner versus__corner--red<?= $b['winner'] === 'red' ? ' is-winner' : '' ?>">
                    <span class="versus__label"><?= e(t('Rot')) ?></span>
                    <strong><?= $red !== '' ? e($red) : '<span class="muted">' . e(t('offen')) . '</span>' ?></strong>
                    <?php if ($b['red_gym'] !== null): ?><small><?= e($b['red_gym']) ?></small><?php endif; ?>
                </div>
                <div class="versus__vs">vs</div>
                <div class="versus__corner versus__corner--blue<?= $b['winner'] === 'blue' ? ' is-winner' : '' ?>">
                    <span class="versus__label"><?= e(t('Blau')) ?></span>
                    <strong><?= $blue !== '' ? e($blue) : '<span class="muted">' . e(t('offen')) . '</span>' ?></strong>
                    <?php if ($b['blue_gym'] !== null): ?><small><?= e($b['blue_gym']) ?></small><?php endif; ?>
                </div>
            </div>

            <?php if ($b['status'] === 'beendet'): ?>
                <p class="result-line"><strong><?= e(t(BoutRepo::WINNER[$b['winner']] ?? '')) ?></strong>
                    <?= $b['winner'] === 'red' ? e($red) : ($b['winner'] === 'blue' ? e($blue) : '') ?>
                    <?= $b['method'] !== '' ? '· ' . e(t($b['method'])) : '' ?> <?= e($b['result_note']) ?>
                    <form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $b['id'] . '/status')) ?>" class="inline" data-confirm="<?= e(t('Ergebnis zurücknehmen?')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="status" value="reopen">
                        <button class="linklike" type="submit"><?= e(t('zurücknehmen')) ?></button>
                    </form>
                </p>
            <?php elseif ($b['status'] === 'abgesagt'): ?>
                <form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $b['id'] . '/status')) ?>" class="inline">
                    <?= csrf_field() ?><button class="btn btn--sm" name="status" value="geplant" type="submit"><?= e(t('Absage aufheben')) ?></button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e(url('/admin/events/' . $id . '/kampf/' . $b['id'] . '/ergebnis')) ?>" class="ring-form">
                    <?= csrf_field() ?>
                    <?php if ($b['status'] !== 'laufend'): ?>
                        <button class="btn btn--primary btn--big" type="submit" formaction="<?= e(url('/admin/events/' . $id . '/kampf/' . $b['id'] . '/status')) ?>" name="status" value="laufend" formnovalidate><?= e(t('▶ Kampf starten')) ?></button>
                    <?php endif; ?>
                    <div class="winner-pick winner-pick--big">
                        <label class="winner-pick__opt winner-pick__opt--red"><input type="radio" name="winner" value="red" <?= $b['red_entry_id'] === null ? 'disabled' : '' ?>><span><?= e(t('Sieg Rot')) ?></span></label>
                        <label class="winner-pick__opt winner-pick__opt--blue"><input type="radio" name="winner" value="blue" <?= $b['blue_entry_id'] === null ? 'disabled' : '' ?>><span><?= e(t('Sieg Blau')) ?></span></label>
                        <label class="winner-pick__opt"><input type="radio" name="winner" value="draw"><span><?= e(t('Unentschieden')) ?></span></label>
                    </div>
                    <div class="ring-form__row">
                        <select name="method" aria-label="<?= e(t('Siegart')) ?>">
                            <option value=""><?= e(t('Siegart …')) ?></option>
                            <?php foreach ($methods as $m): ?><option value="<?= e($m) ?>"><?= e(t($m)) ?></option><?php endforeach; ?>
                        </select>
                        <input name="result_note" placeholder="<?= e(t('Details (3:0, Rd. 2 …)')) ?>">
                        <button class="btn btn--primary" type="submit"><?= e(t('Ergebnis speichern')) ?></button>
                        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/events/' . $id . '/kampf/' . $b['id'])) ?>"><?= e(t('Details')) ?></a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
