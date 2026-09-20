<?php

use App\Core\Auth;
use App\Models\EventRepo;

/**
 * @var array<string,mixed>  $event
 * @var array<string,string> $errors
 * @var bool                 $isNew
 * @var array<string,mixed>  $stats (nur bei bestehendem Event)
 */
$id     = (int) ($event['id'] ?? 0);
$action = $isNew ? url('/admin/events') : url('/admin/events/' . $id);
$err    = static fn (string $f): string => isset($errors[$f]) ? '<p class="field__error">' . e($errors[$f]) . '</p>' : '';
$ro     = Auth::canWrite() ? '' : ' disabled';

if ($isNew): ?>
    <div class="page-head">
        <h1>Neues Event</h1>
        <div class="page-head__actions"><a class="btn btn--ghost" href="<?= e(url('/admin/events')) ?>">Zur Liste</a></div>
    </div>
<?php else: ?>
    <?php require __DIR__ . '/_head.php'; ?>

    <div class="stat-grid stat-grid--compact">
        <a class="stat" href="<?= e(url('/admin/events/' . $id . '/anmeldungen')) ?>">
            <span class="stat__value"><?= (int) $stats['entries']['bestaetigt'] ?><small>/<?= (int) $stats['entries']['gesamt'] ?></small></span>
            <span class="stat__label">bestätigte Anmeldungen</span>
        </a>
        <a class="stat <?= $stats['entries']['angemeldet'] > 0 ? 'stat--warn' : '' ?>" href="<?= e(url('/admin/events/' . $id . '/anmeldungen', ['status' => 'angemeldet'])) ?>">
            <span class="stat__value"><?= (int) $stats['entries']['angemeldet'] ?></span>
            <span class="stat__label">offen zu bestätigen</span>
        </a>
        <a class="stat" href="<?= e(url('/admin/events/' . $id . '/anmeldungen')) ?>">
            <span class="stat__value"><?= (int) $stats['gyms'] ?></span>
            <span class="stat__label">Gyms</span>
        </a>
        <a class="stat" href="<?= e(url('/admin/events/' . $id . '/kaempfe')) ?>">
            <span class="stat__value"><?= (int) $stats['bouts']['beendet'] ?><small>/<?= (int) $stats['bouts']['gesamt'] ?></small></span>
            <span class="stat__label">Kämpfe beendet</span>
        </a>
        <a class="stat <?= $stats['unplaced'] > 0 ? 'stat--danger' : 'stat--ok' ?>" href="<?= e(url('/admin/events/' . $id . '/zeitplan')) ?>">
            <span class="stat__value"><?= (int) $stats['unplaced'] ?></span>
            <span class="stat__label">nicht eingeplant</span>
        </a>
    </div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form">
    <?= csrf_field() ?>

    <div class="form-grid">
        <fieldset class="card">
            <legend>Grunddaten</legend>

            <div class="field">
                <label for="name">Name *</label>
                <input id="name" name="name" required value="<?= e($event['name']) ?>" placeholder="z. B. Fight Night 3 oder Steirische Meisterschaft"<?= $ro ?>>
                <?= $err('name') ?>
            </div>

            <div class="field-row">
                <div class="field field--grow">
                    <label for="type">Art des Events *</label>
                    <select id="type" name="type"<?= $ro ?>>
                        <?php foreach (EventRepo::TYPES as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $event['type'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="field__hint">
                        <strong>Turnier:</strong> Kategorien, Anmeldungen, Turnierbaum je Kategorie.
                        <strong>Gala:</strong> Fightcard mit einzeln zusammengestellten Kämpfen.
                        Jederzeit umstellbar – vorhandene Daten bleiben erhalten.
                    </p>
                </div>
                <div class="field field--sm">
                    <label for="sport">Sportart</label>
                    <input id="sport" name="sport" value="<?= e($event['sport']) ?>" placeholder="Kickboxen"<?= $ro ?>>
                </div>
            </div>

            <div class="field">
                <label for="tagline">Untertitel</label>
                <input id="tagline" name="tagline" value="<?= e($event['tagline']) ?>" placeholder="Ein Satz für Startseite und Kopfzeile"<?= $ro ?>>
            </div>

            <div class="field-row">
                <div class="field field--sm">
                    <label for="starts_on">Beginn *</label>
                    <input id="starts_on" name="starts_on" type="date" required value="<?= e($event['starts_on']) ?>"<?= $ro ?>>
                    <?= $err('starts_on') ?>
                </div>
                <div class="field field--sm">
                    <label for="ends_on">Ende <small>(mehrtägig)</small></label>
                    <input id="ends_on" name="ends_on" type="date" value="<?= e((string) $event['ends_on']) ?>"<?= $ro ?>>
                    <?= $err('ends_on') ?>
                </div>
                <div class="field field--xs">
                    <label for="doors_time">Einlass</label>
                    <input id="doors_time" name="doors_time" type="time" value="<?= e($event['doors_time']) ?>"<?= $ro ?>>
                </div>
                <div class="field field--xs">
                    <label for="start_time">Beginn</label>
                    <input id="start_time" name="start_time" type="time" value="<?= e($event['start_time']) ?>"<?= $ro ?>>
                </div>
            </div>

            <div class="field-row">
                <div class="field field--grow">
                    <label for="status">Status</label>
                    <select id="status" name="status"<?= $ro ?>>
                        <?php foreach (EventRepo::STATUS as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $event['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field field--grow">
                    <label for="slug">URL-Kürzel</label>
                    <input id="slug" name="slug" value="<?= e($event['slug']) ?>" placeholder="wird aus dem Namen gebildet"<?= $ro ?>>
                    <p class="field__hint">Event-Seite: /e/<?= e($event['slug'] ?: '…') ?></p>
                </div>
            </div>
        </fieldset>

        <fieldset class="card">
            <legend>Veranstaltungsort</legend>

            <div class="field">
                <label for="venue_name">Halle / Location</label>
                <input id="venue_name" name="venue_name" value="<?= e($event['venue_name']) ?>" placeholder="Sporthalle 2"<?= $ro ?>>
            </div>
            <div class="field">
                <label for="venue_street">Straße</label>
                <input id="venue_street" name="venue_street" value="<?= e($event['venue_street']) ?>"<?= $ro ?>>
            </div>
            <div class="field-row">
                <div class="field field--xs">
                    <label for="venue_zip">PLZ</label>
                    <input id="venue_zip" name="venue_zip" value="<?= e($event['venue_zip']) ?>"<?= $ro ?>>
                </div>
                <div class="field field--grow">
                    <label for="venue_city">Ort</label>
                    <input id="venue_city" name="venue_city" value="<?= e($event['venue_city']) ?>"<?= $ro ?>>
                </div>
            </div>

            <legend style="margin-top:1rem">Kontakt &amp; Tickets</legend>
            <div class="field-row">
                <div class="field field--grow">
                    <label for="contact_email">E-Mail</label>
                    <input id="contact_email" name="contact_email" type="email" value="<?= e($event['contact_email']) ?>"<?= $ro ?>>
                    <?= $err('contact_email') ?>
                </div>
                <div class="field field--sm">
                    <label for="contact_phone">Telefon</label>
                    <input id="contact_phone" name="contact_phone" value="<?= e($event['contact_phone']) ?>"<?= $ro ?>>
                </div>
            </div>
            <div class="field">
                <label for="ticket_url">Ticket-Link</label>
                <input id="ticket_url" name="ticket_url" value="<?= e($event['ticket_url']) ?>" placeholder="https://…"<?= $ro ?>>
            </div>
        </fieldset>

        <fieldset class="card">
            <legend>Anmeldung durch Gyms</legend>

            <label class="check">
                <input type="checkbox" name="gym_registration" value="1" <?= (int) $event['gym_registration'] === 1 ? 'checked' : '' ?><?= $ro ?>>
                Gyms dürfen ihre Sportler selbst anmelden (Status „Anmeldung offen“ nötig)
            </label>

            <div class="field-row">
                <div class="field field--sm">
                    <label for="registration_from">Anmeldung ab</label>
                    <input id="registration_from" name="registration_from" type="date" value="<?= e((string) $event['registration_from']) ?>"<?= $ro ?>>
                </div>
                <div class="field field--sm">
                    <label for="registration_until">Anmeldeschluss</label>
                    <input id="registration_until" name="registration_until" type="date" value="<?= e((string) $event['registration_until']) ?>"<?= $ro ?>>
                </div>
            </div>
            <div class="field-row">
                <div class="field field--sm">
                    <label for="max_entries">Max. Teilnehmer <small>(0 = unbegrenzt)</small></label>
                    <input id="max_entries" name="max_entries" type="number" min="0" value="<?= (int) $event['max_entries'] ?>"<?= $ro ?>>
                </div>
                <div class="field field--sm">
                    <label for="entry_fee">Startgeld (€)</label>
                    <input id="entry_fee" name="entry_fee" inputmode="decimal" value="<?= e(number_format((float) $event['entry_fee'], 2, ',', '')) ?>"<?= $ro ?>>
                </div>
            </div>
        </fieldset>

        <fieldset class="card">
            <legend>Website</legend>

            <label class="check">
                <input type="checkbox" name="published" value="1" <?= (int) $event['published'] === 1 ? 'checked' : '' ?><?= $ro ?>>
                Event-Seite öffentlich sichtbar
            </label>
            <label class="check">
                <input type="checkbox" name="show_entries" value="1" <?= (int) $event['show_entries'] === 1 ? 'checked' : '' ?><?= $ro ?>>
                Teilnehmerliste (bestätigte Anmeldungen) öffentlich zeigen
            </label>
            <label class="check">
                <input type="checkbox" name="show_results" value="1" <?= (int) $event['show_results'] === 1 ? 'checked' : '' ?><?= $ro ?>>
                Ergebnisse öffentlich zeigen
            </label>
            <p class="field__hint">
                Soll dieses Event die <strong>komplette Startseite</strong> sein (eigene Event-Homepage),
                unter <a href="<?= e(url('/admin/einstellungen')) ?>">Einstellungen → Startseite</a> auswählen.
            </p>
        </fieldset>
    </div>

    <fieldset class="card">
        <legend>Fightcard, Zeiten &amp; Tickets</legend>
        <div class="field-row">
            <div class="field field--sm"><label for="short_name">Kurzname</label><input id="short_name" name="short_name" value="<?= e($event['short_name']) ?>" placeholder="NAFN 3"<?= $ro ?>></div>
            <div class="field field--xs"><label for="default_bout_minutes">Min. je Kampf</label><input id="default_bout_minutes" name="default_bout_minutes" type="number" min="1" value="<?= (int) $event['default_bout_minutes'] ?>"<?= $ro ?>></div>
            <div class="field field--xs"><label for="default_break_minutes">Min. je Pause</label><input id="default_break_minutes" name="default_break_minutes" type="number" min="1" value="<?= (int) $event['default_break_minutes'] ?>"<?= $ro ?>></div>
            <div class="field field--grow"><label for="min_age_note">Einlass-Hinweis</label><input id="min_age_note" name="min_age_note" value="<?= e($event['min_age_note']) ?>" placeholder="Einlass ab 10 Jahren"<?= $ro ?>></div>
        </div>
        <p class="field__hint">Aus Beginnzeit und Dauer werden die voraussichtlichen Beginnzeiten aller Kämpfe berechnet (einzelne Kämpfe können eine eigene Dauer haben).</p>
        <label class="check"><input type="checkbox" name="live_mode" value="1" <?= (int) $event['live_mode'] === 1 ? 'checked' : '' ?><?= $ro ?>> Live-Modus: Zeiten richten sich nach den echten Start-/Endzeiten (am Veranstaltungstag einschalten)</label>
        <label class="check"><input type="checkbox" name="show_countdown" value="1" <?= (int) $event['show_countdown'] === 1 ? 'checked' : '' ?><?= $ro ?>> Countdown auf der Event-Seite</label>
        <label class="check"><input type="checkbox" name="show_map" value="1" <?= (int) $event['show_map'] === 1 ? 'checked' : '' ?><?= $ro ?>> Karte (Google Maps, erst nach Klick geladen)</label>
        <div class="field"><label for="location_note">Text zur Location</label><input id="location_note" name="location_note" value="<?= e($event['location_note']) ?>" placeholder="Mitten in Weiz – gut erreichbar, genug Parkplätze"<?= $ro ?>></div>

        <legend style="margin-top:1rem">Ticketpreise</legend>
        <?php $ticketRows = \App\Models\EventRepo::tickets($event); ?>
        <?php for ($i = 0; $i < max(3, count($ticketRows) + 1); $i++): ?>
            <?php $t = $ticketRows[$i] ?? ['label' => '', 'price' => '', 'note' => '', 'highlight' => false]; ?>
            <div class="field-row">
                <div class="field field--sm"><label>Kategorie</label><input name="tickets[<?= $i ?>][label]" value="<?= e($t['label']) ?>" placeholder="Vorverkauf"<?= $ro ?>></div>
                <div class="field field--xs"><label>Preis</label><input name="tickets[<?= $i ?>][price]" value="<?= e($t['price']) ?>" placeholder="35 €"<?= $ro ?>></div>
                <div class="field field--grow"><label>Hinweis</label><input name="tickets[<?= $i ?>][note]" value="<?= e($t['note']) ?>"<?= $ro ?>></div>
                <label class="check"><input type="checkbox" name="tickets[<?= $i ?>][highlight]" value="1" <?= $t['highlight'] ? 'checked' : '' ?><?= $ro ?>> hervorheben</label>
            </div>
        <?php endfor; ?>
        <div class="field"><label for="ticket_note">Text über den Tickets</label><input id="ticket_note" name="ticket_note" value="<?= e($event['ticket_note']) ?>" placeholder="Sei live dabei – wenn weg, dann weg!"<?= $ro ?>></div>

        <legend style="margin-top:1rem">Social Media</legend>
        <?php $socialLinks = \App\Models\EventRepo::social($event); ?>
        <div class="field-row">
            <?php foreach (\App\Controllers\EventAdminController::SOCIAL as $net => $label): ?>
                <div class="field field--grow"><label><?= e($label) ?></label><input name="social[<?= e($net) ?>]" value="<?= e($socialLinks[$net] ?? '') ?>" placeholder="https://…"<?= $ro ?>></div>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset class="card">
        <legend>Beschreibung <small>(Event-Seite)</small></legend>
        <div class="field">
            <textarea id="description" name="description" rows="10" class="js-richtext"<?= $ro ?>><?= e($event['description']) ?></textarea>
        </div>
    </fieldset>

    <fieldset class="card">
        <legend>Bilder</legend>
        <div class="image-grid">
            <?php foreach ([
                'logo'   => ['Logo', $event['logo_path'], 'Event-Logo, quadratisch, max. 600 px'],
                'poster' => ['Poster', $event['poster_path'], 'Plakat hochkant, max. 1200 px'],
                'hero'   => ['Titelbild', $event['hero_path'], 'Breites Bild oben auf der Event-Seite, max. 1900 px'],
                'belt'   => ['Titelgürtel', $event['belt_path'] ?? '', 'Freigestellt (PNG/WebP) – erscheint bei Titelkämpfen über den Kämpfern'],
            ] as $key => [$label, $path, $hint]): ?>
                <div class="image-slot">
                    <p class="image-slot__label"><?= e($label) ?></p>
                    <?php if ((string) $path !== ''): ?>
                        <img src="<?= e(upload_url((string) $path)) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <div class="image-slot__empty">kein Bild</div>
                    <?php endif; ?>
                    <?php if (Auth::canWrite()): ?>
                        <input type="file" name="<?= e($key) ?>" accept="image/jpeg,image/png,image/gif,image/webp">
                    <?php endif; ?>
                    <p class="field__hint"><?= e($hint) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <?php if (Auth::canWrite()): ?>
        <div class="form-actions">
            <button class="btn btn--primary" type="submit"><?= $isNew ? 'Event anlegen' : 'Änderungen speichern' ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/admin/events')) ?>">Zur Liste</a>
        </div>
    <?php endif; ?>
</form>

<?php if (!$isNew && Auth::canWrite()): ?>
    <?php foreach (['logo' => $event['logo_path'], 'poster' => $event['poster_path'], 'hero' => $event['hero_path'], 'belt' => $event['belt_path'] ?? ''] as $key => $path): ?>
        <?php if ((string) $path === '') { continue; } ?>
        <form method="post" action="<?= e(url('/admin/events/' . $id . '/bild-entfernen')) ?>" class="inline" data-confirm="Bild wirklich entfernen?">
            <?= csrf_field() ?>
            <input type="hidden" name="field" value="<?= e($key) ?>_path">
            <button class="linklike linklike--danger" type="submit"><?= e(ucfirst($key)) ?> entfernen</button>
        </form>
    <?php endforeach; ?>

    <?php if (Auth::isSuperuser()): ?>
        <div class="card card--danger">
            <div class="card__head"><h2>Event löschen</h2></div>
            <p class="muted">Löscht das Event mit allen Anmeldungen, Kämpfen, Tagen und Kategorien. Sportler und Gyms bleiben erhalten.</p>
            <form method="post" action="<?= e(url('/admin/events/' . $id . '/loeschen')) ?>" data-confirm="Event „<?= e($event['name']) ?>“ endgültig löschen?">
                <?= csrf_field() ?>
                <button class="btn btn--danger" type="submit">Event löschen</button>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>
