<?php

use App\Core\Auth;

/**
 * Aufbau: Tage → Abschnitte, Wettkampfstätten.
 *
 * @var array<string,mixed>       $event
 * @var list<array<string,mixed>> $days
 * @var list<array<string,mixed>> $venues
 */
$id       = (int) $event['id'];
$subtitle = 'Aufbau';
$canWrite = Auth::canWrite();
require __DIR__ . '/_head.php';
?>

<div class="notice">
    <strong>So ist ein Event aufgebaut:</strong> Jeder <em>Tag</em> wird in <em>Abschnitte</em> unterteilt
    (z. B. „Vormittag – Vorrunden“, „Abend – Finals“), und Kämpfe finden auf <em>Wettkampfstätten</em> statt
    (Ring 1, Ring 2, Matte A …). Im <a href="<?= e(url('/admin/events/' . $id . '/zeitplan')) ?>">Zeitplan</a>
    werden die Kämpfe dann je Abschnitt auf die Wettkampfstätten verteilt.
</div>

<div class="form-grid form-grid--wide">
    <div>
        <?php foreach ($days as $day): ?>
            <div class="card day-card">
                <div class="card__head">
                    <h2><?= e(format_date_long($day['day_date'])) ?><?= $day['label'] !== '' ? ' – ' . e($day['label']) : '' ?></h2>
                    <?php if ($canWrite): ?>
                        <details class="plan-edit">
                            <summary>Tag bearbeiten</summary>
                            <form method="post" action="<?= e(url('/admin/events/' . $id . '/tag')) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="day_id" value="<?= (int) $day['id'] ?>">
                                <div class="field field--sm"><label>Datum</label><input type="date" name="day_date" value="<?= e($day['day_date']) ?>" required></div>
                                <div class="field field--grow"><label>Bezeichnung</label><input name="label" value="<?= e($day['label']) ?>" placeholder="Tag 1 – Vorrunden"></div>
                                <div class="field field--grow"><label>Hinweis</label><input name="note" value="<?= e($day['note']) ?>"></div>
                                <div class="field field--xs"><label>Reihung</label><input type="number" name="sort_order" value="<?= (int) $day['sort_order'] ?>"></div>
                                <button class="btn btn--sm" type="submit">Speichern</button>
                                <button class="linklike linklike--danger" type="submit" formaction="<?= e(url('/admin/events/' . $id . '/tag-loeschen')) ?>" data-confirm-click="Tag samt Abschnitten entfernen?">Entfernen</button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div>

                <?php if ($day['sessions'] === []): ?>
                    <p class="muted">Noch keine Abschnitte an diesem Tag.</p>
                <?php else: ?>
                    <table class="table table--compact">
                        <thead><tr><th>Abschnitt</th><th>von</th><th>bis</th><th>Hinweis</th><th class="num">Reihung</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($day['sessions'] as $s): ?>
                            <tr>
                                <?php if ($canWrite): ?>
                                    <form method="post" action="<?= e(url('/admin/events/' . $id . '/abschnitt')) ?>" id="sess-<?= (int) $s['id'] ?>"></form>
                                    <td>
                                        <input type="hidden" name="session_id" value="<?= (int) $s['id'] ?>" form="sess-<?= (int) $s['id'] ?>">
                                        <input type="hidden" name="day_id" value="<?= (int) $day['id'] ?>" form="sess-<?= (int) $s['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>" form="sess-<?= (int) $s['id'] ?>">
                                        <input name="name" value="<?= e($s['name']) ?>" form="sess-<?= (int) $s['id'] ?>" required>
                                    </td>
                                    <td><input type="time" name="starts_at" value="<?= e($s['starts_at']) ?>" form="sess-<?= (int) $s['id'] ?>" class="input--time"></td>
                                    <td><input type="time" name="ends_at" value="<?= e($s['ends_at']) ?>" form="sess-<?= (int) $s['id'] ?>" class="input--time"></td>
                                    <td><input name="note" value="<?= e($s['note']) ?>" form="sess-<?= (int) $s['id'] ?>"></td>
                                    <td class="num"><input type="number" name="sort_order" value="<?= (int) $s['sort_order'] ?>" form="sess-<?= (int) $s['id'] ?>" class="input--xs"></td>
                                    <td class="row-actions">
                                        <button class="btn btn--sm" type="submit" form="sess-<?= (int) $s['id'] ?>">Speichern</button>
                                        <button class="linklike linklike--danger" type="submit" form="sess-<?= (int) $s['id'] ?>" formaction="<?= e(url('/admin/events/' . $id . '/abschnitt-loeschen')) ?>" data-confirm-click="Abschnitt entfernen?">Entfernen</button>
                                    </td>
                                <?php else: ?>
                                    <td><?= e($s['name']) ?></td>
                                    <td><?= e($s['starts_at']) ?></td>
                                    <td><?= e($s['ends_at']) ?></td>
                                    <td><?= e($s['note']) ?></td>
                                    <td class="num"><?= (int) $s['sort_order'] ?></td>
                                    <td></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php if ($canWrite): ?>
                    <form method="post" action="<?= e(url('/admin/events/' . $id . '/abschnitt')) ?>" class="inline-form inline-form--new">
                        <?= csrf_field() ?>
                        <input type="hidden" name="day_id" value="<?= (int) $day['id'] ?>">
                        <div class="field field--grow"><label>Neuer Abschnitt</label><input name="name" placeholder="z. B. Vormittag – Vorrunden" required></div>
                        <div class="field field--xs"><label>von</label><input type="time" name="starts_at"></div>
                        <div class="field field--xs"><label>bis</label><input type="time" name="ends_at"></div>
                        <button class="btn btn--sm btn--primary" type="submit">Hinzufügen</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($canWrite): ?>
            <div class="card">
                <div class="card__head"><h2>Weiteren Tag hinzufügen</h2></div>
                <form method="post" action="<?= e(url('/admin/events/' . $id . '/tag')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="day_id" value="0">
                    <div class="field field--sm"><label>Datum</label><input type="date" name="day_date" required></div>
                    <div class="field field--grow"><label>Bezeichnung</label><input name="label" placeholder="Tag 2 – Finals"></div>
                    <button class="btn btn--sm btn--primary" type="submit">Tag anlegen</button>
                </form>
                <p class="field__hint">Die Tage des Event-Zeitraums (Beginn bis Ende) werden automatisch angelegt.</p>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="card">
            <div class="card__head"><h2>Wettkampfstätten</h2></div>
            <p class="muted">Ringe, Matten, Käfige – alles, wo gleichzeitig gekämpft wird.</p>

            <?php foreach ($venues as $v): ?>
                <?php if ($canWrite): ?>
                    <form method="post" action="<?= e(url('/admin/events/' . $id . '/staette')) ?>" class="inline-form venue-row">
                        <?= csrf_field() ?>
                        <input type="hidden" name="venue_id" value="<?= (int) $v['id'] ?>">
                        <div class="field field--grow"><label>Name</label><input name="name" value="<?= e($v['name']) ?>" required></div>
                        <div class="field field--xs"><label>Kurz</label><input name="short_name" value="<?= e($v['short_name']) ?>" maxlength="8"></div>
                        <div class="field field--xs"><label>Farbe</label><input type="color" name="color" value="<?= e($v['color'] ?: '#e63946') ?>"></div>
                        <div class="field field--xs"><label>Reihung</label><input type="number" name="sort_order" value="<?= (int) $v['sort_order'] ?>"></div>
                        <div class="contact-row__actions">
                            <button class="btn btn--sm" type="submit">Speichern</button>
                            <button class="linklike linklike--danger" type="submit" formaction="<?= e(url('/admin/events/' . $id . '/staette-loeschen')) ?>" data-confirm-click="Wettkampfstätte entfernen?">Entfernen</button>
                        </div>
                        <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/events/' . $id . '/ring/' . $v['id'])) ?>">Ringansicht</a>
                    </form>
                <?php else: ?>
                    <p><span class="venue-dot" style="background:<?= e($v['color'] ?: '#e63946') ?>"></span> <?= e($v['name']) ?>
                        <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/events/' . $id . '/ring/' . $v['id'])) ?>">Ringansicht</a></p>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($venues === []): ?>
                <p class="muted">Noch keine Wettkampfstätte – mindestens eine (z. B. „Ring 1“) wird für den Zeitplan gebraucht.</p>
            <?php endif; ?>

            <?php if ($canWrite): ?>
                <form method="post" action="<?= e(url('/admin/events/' . $id . '/staette')) ?>" class="inline-form inline-form--new">
                    <?= csrf_field() ?>
                    <input type="hidden" name="venue_id" value="0">
                    <div class="field field--grow"><label>Neue Wettkampfstätte</label><input name="name" placeholder="Ring 1" required></div>
                    <div class="field field--xs"><label>Kurz</label><input name="short_name" placeholder="R1" maxlength="8"></div>
                    <div class="field field--xs"><label>Farbe</label><input type="color" name="color" value="#e63946"></div>
                    <button class="btn btn--sm btn--primary" type="submit">Hinzufügen</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
