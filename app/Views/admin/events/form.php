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
        <h1><?= e(t('Neues Event')) ?></h1>
        <div class="page-head__actions"><a class="btn btn--ghost" href="<?= e(url('/admin/events')) ?>"><?= e(t('Zur Liste')) ?></a></div>
    </div>
<?php else: ?>
    <?php require __DIR__ . '/_head.php'; ?>

    <div class="stat-grid stat-grid--compact">
        <a class="stat" href="<?= e(url('/admin/events/' . $id . '/anmeldungen')) ?>">
            <span class="stat__value"><?= (int) $stats['entries']['bestaetigt'] ?><small>/<?= (int) $stats['entries']['gesamt'] ?></small></span>
            <span class="stat__label"><?= e(t('bestätigte Anmeldungen')) ?></span>
        </a>
        <a class="stat <?= $stats['entries']['angemeldet'] > 0 ? 'stat--warn' : '' ?>" href="<?= e(url('/admin/events/' . $id . '/anmeldungen', ['status' => 'angemeldet'])) ?>">
            <span class="stat__value"><?= (int) $stats['entries']['angemeldet'] ?></span>
            <span class="stat__label"><?= e(t('offen zu bestätigen')) ?></span>
        </a>
        <a class="stat" href="<?= e(url('/admin/events/' . $id . '/anmeldungen')) ?>">
            <span class="stat__value"><?= (int) $stats['gyms'] ?></span>
            <span class="stat__label"><?= e(t('Gyms')) ?></span>
        </a>
        <a class="stat" href="<?= e(url('/admin/events/' . $id . '/kaempfe')) ?>">
            <span class="stat__value"><?= (int) $stats['bouts']['beendet'] ?><small>/<?= (int) $stats['bouts']['gesamt'] ?></small></span>
            <span class="stat__label"><?= e(t('Kämpfe beendet')) ?></span>
        </a>
        <a class="stat <?= $stats['unplaced'] > 0 ? 'stat--danger' : 'stat--ok' ?>" href="<?= e(url('/admin/events/' . $id . '/zeitplan')) ?>">
            <span class="stat__value"><?= (int) $stats['unplaced'] ?></span>
            <span class="stat__label"><?= e(t('nicht eingeplant')) ?></span>
        </a>
    </div>

    <?php $tk = $ticket141 ?? null; ?>
    <?php if ($tk !== null): ?>
        <div class="card">
            <div class="card__head">
                <h2><?= e(t('Ticketverkauf')) ?> <small class="muted">(Ticket141)</small></h2>
                <?php if ($tk['slug'] !== ''): ?>
                    <p>
                        <a href="<?= e($tk['shop_url']) ?>" target="_blank" rel="noopener"><?= e(t('Shop ansehen ↗')) ?></a>
                        <?php if ($tk['admin_url'] !== ''): ?> · <a href="<?= e($tk['admin_url']) ?>" target="_blank" rel="noopener"><?= e(t('Ticket141-Verwaltung ↗')) ?></a><?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($tk['slug'] === ''): ?>
                <p class="muted"><?= e(t('Noch nicht gekoppelt. „In Ticket141 anlegen“ legt dort ein Event mit Name, Datum, Beginn, Einlass und Location an (als Entwurf) – Preise und Verkaufsstart dann in Ticket141.')) ?></p>
            <?php elseif ($tk['error'] !== ''): ?>
                <p class="muted"><?= e(t('Gekoppelt mit „%s“, aber Ticket141 antwortet gerade nicht: %s', $tk['slug'], $tk['error'])) ?></p>
            <?php elseif (!$tk['has_key']): ?>
                <p class="muted"><?= e(t('Gekoppelt mit „%s“. Für Verkaufszahlen einen API-Schlüssel unter Einstellungen → Ticket141 hinterlegen.', $tk['slug'])) ?></p>
            <?php elseif ($tk['stats'] !== null): ?>
                <?php $st = $tk['stats']['stats']; ?>
                <div class="stat-grid stat-grid--compact">
                    <div class="stat"><span class="stat__value"><?= (int) ($st['tickets'] ?? 0) ?></span><span class="stat__label"><?= e(t('Tickets verkauft')) ?></span></div>
                    <div class="stat"><span class="stat__value"><?= e(\App\Core\Ticket141Client::price((int) ($st['gross_cents'] ?? 0) - (int) ($st['refunded_cents'] ?? 0))) ?></span><span class="stat__label"><?= e(t('Umsatz')) ?></span></div>
                    <div class="stat"><span class="stat__value"><?= (int) ($st['checked_in'] ?? 0) ?></span><span class="stat__label"><?= e(t('eingelassen')) ?></span></div>
                    <div class="stat"><span class="stat__value"><?= (int) ($st['orders_open'] ?? 0) ?></span><span class="stat__label"><?= e(t('offene Bestellungen')) ?></span></div>
                </div>
                <?php if (!empty($tk['stats']['categories'])): ?>
                    <p class="muted">
                        <?php foreach ($tk['stats']['categories'] as $i => $c): ?><?= $i > 0 ? ' · ' : '' ?><?= e($c['name']) ?>: <?= (int) $c['sold'] ?><?= (int) $c['quota'] > 0 ? '/' . (int) $c['quota'] : '' ?><?php endforeach; ?>
                    </p>
                <?php endif; ?>
                <p class="muted">
                    <?= !empty($tk['event']['published'])
                        ? t('Status in Ticket141: <strong>%s</strong>, veröffentlicht', e((string) ($tk['event']['status'] ?? '')))
                        : t('Status in Ticket141: <strong>%s</strong>, nicht veröffentlicht', e((string) ($tk['event']['status'] ?? ''))) ?>

                    <?= empty($tk['sale']['open']) && ($tk['sale']['reason'] ?? '') !== '' ? ' – ' . e((string) $tk['sale']['reason']) : '' ?>
                </p>
            <?php endif; ?>

            <?php if (Auth::canWrite()): ?>
                <form method="post" action="<?= e(url('/admin/events/' . $id . '/ticket141/anlegen')) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn btn--sm<?= $tk['slug'] === '' ? ' btn--primary' : '' ?>" type="submit"><?= e($tk['slug'] === '' ? t('In Ticket141 anlegen') : t('Ticket141-Stammdaten aktualisieren')) ?></button>
                </form>
                <?php if ($tk['slug'] !== ''): ?><small class="muted"><?= e(t('Überträgt Name, Untertitel, Datum, Beginn, Einlass und Location nach Ticket141 (Preise bleiben dort unangetastet).')) ?></small><?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form">
    <?= csrf_field() ?>

    <div class="form-grid">
        <fieldset class="card">
            <legend><?= e(t('Grunddaten')) ?></legend>

            <div class="field">
                <label for="name"><?= e(t('Name *')) ?></label>
                <input id="name" name="name" required value="<?= e($event['name']) ?>" placeholder="<?= e(t('z. B. Fight Night 3 oder Steirische Meisterschaft')) ?>"<?= $ro ?>>
                <?= $err('name') ?>
            </div>

            <div class="field-row">
                <div class="field field--grow">
                    <label for="type"><?= e(t('Art des Events *')) ?></label>
                    <select id="type" name="type"<?= $ro ?>>
                        <?php foreach (EventRepo::TYPES as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $event['type'] === $key ? 'selected' : '' ?>><?= e(t($label)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="field__hint">
                        <?= t('<strong>Turnier:</strong> Kategorien, Anmeldungen, Turnierbaum je Kategorie. <strong>Gala:</strong> Fightcard mit einzeln zusammengestellten Kämpfen. Jederzeit umstellbar – vorhandene Daten bleiben erhalten.') ?>
                    </p>
                </div>
                <div class="field field--sm">
                    <label for="sport"><?= e(t('Sportart')) ?></label>
                    <input id="sport" name="sport" value="<?= e($event['sport']) ?>" placeholder="<?= e(t('Kickboxen')) ?>"<?= $ro ?>>
                </div>
            </div>

            <div class="field">
                <label for="tagline"><?= e(t('Untertitel')) ?></label>
                <input id="tagline" name="tagline" value="<?= e($event['tagline']) ?>" placeholder="<?= e(t('Ein Satz für Startseite und Kopfzeile')) ?>"<?= $ro ?>>
            </div>

            <div class="field-row">
                <div class="field field--sm">
                    <label for="starts_on"><?= e(t('Beginn *')) ?></label>
                    <input id="starts_on" name="starts_on" type="date" required value="<?= e($event['starts_on']) ?>"<?= $ro ?>>
                    <?= $err('starts_on') ?>
                </div>
                <div class="field field--sm">
                    <label for="ends_on"><?= t('Ende <small>(mehrtägig)</small>') ?></label>
                    <input id="ends_on" name="ends_on" type="date" value="<?= e((string) $event['ends_on']) ?>"<?= $ro ?>>
                    <?= $err('ends_on') ?>
                </div>
                <div class="field field--xs">
                    <label for="doors_time"><?= e(t('Einlass')) ?></label>
                    <input id="doors_time" name="doors_time" type="time" value="<?= e($event['doors_time']) ?>"<?= $ro ?>>
                </div>
                <div class="field field--xs">
                    <label for="start_time"><?= e(t('Beginn')) ?></label>
                    <input id="start_time" name="start_time" type="time" value="<?= e($event['start_time']) ?>"<?= $ro ?>>
                </div>
            </div>

            <div class="field-row">
                <div class="field field--grow">
                    <label for="status"><?= e(t('Status')) ?></label>
                    <select id="status" name="status"<?= $ro ?>>
                        <?php foreach (EventRepo::STATUS as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $event['status'] === $key ? 'selected' : '' ?>><?= e(t($label)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field field--grow">
                    <label for="slug"><?= e(t('URL-Kürzel')) ?></label>
                    <input id="slug" name="slug" value="<?= e($event['slug']) ?>" placeholder="<?= e(t('wird aus dem Namen gebildet')) ?>"<?= $ro ?>>
                    <p class="field__hint"><?= e(t('Event-Seite: %s', '/e/' . ($event['slug'] ?: '…'))) ?></p>
                </div>
            </div>
        </fieldset>

        <fieldset class="card">
            <legend><?= e(t('Veranstaltungsort')) ?></legend>

            <div class="field">
                <label for="venue_name"><?= e(t('Halle / Location')) ?></label>
                <input id="venue_name" name="venue_name" value="<?= e($event['venue_name']) ?>" placeholder="<?= e(t('Sporthalle 2')) ?>"<?= $ro ?>>
            </div>
            <div class="field">
                <label for="venue_street"><?= e(t('Straße')) ?></label>
                <input id="venue_street" name="venue_street" value="<?= e($event['venue_street']) ?>"<?= $ro ?>>
            </div>
            <div class="field-row">
                <div class="field field--xs">
                    <label for="venue_zip"><?= e(t('PLZ')) ?></label>
                    <input id="venue_zip" name="venue_zip" value="<?= e($event['venue_zip']) ?>"<?= $ro ?>>
                </div>
                <div class="field field--grow">
                    <label for="venue_city"><?= e(t('Ort')) ?></label>
                    <input id="venue_city" name="venue_city" value="<?= e($event['venue_city']) ?>"<?= $ro ?>>
                </div>
            </div>

            <legend style="margin-top:1rem"><?= e(t('Kontakt & Tickets')) ?></legend>
            <div class="field-row">
                <div class="field field--grow">
                    <label for="contact_email"><?= e(t('E-Mail')) ?></label>
                    <input id="contact_email" name="contact_email" type="email" value="<?= e($event['contact_email']) ?>"<?= $ro ?>>
                    <?= $err('contact_email') ?>
                </div>
                <div class="field field--sm">
                    <label for="contact_phone"><?= e(t('Telefon')) ?></label>
                    <input id="contact_phone" name="contact_phone" value="<?= e($event['contact_phone']) ?>"<?= $ro ?>>
                </div>
            </div>
            <div class="field">
                <label for="ticket_url"><?= e(t('Ticket-Link')) ?></label>
                <input id="ticket_url" name="ticket_url" value="<?= e($event['ticket_url']) ?>" placeholder="https://…"<?= $ro ?>>
            </div>
        </fieldset>

        <fieldset class="card">
            <legend><?= e(t('Anmeldung durch Gyms')) ?></legend>

            <label class="check">
                <input type="checkbox" name="gym_registration" value="1" <?= (int) $event['gym_registration'] === 1 ? 'checked' : '' ?><?= $ro ?>>
                <?= e(t('Gyms dürfen ihre Sportler selbst anmelden (Status „Anmeldung offen“ nötig)')) ?>
            </label>

            <div class="field">
                <label for="age_mode"><?= e(t('Altersklassen gelten nach')) ?></label>
                <select id="age_mode" name="age_mode"<?= $ro !== '' ? ' disabled' : '' ?>>
                    <?php foreach (\App\Models\EventRepo::AGE_MODES as $mKey => $mLabel): ?>
                        <option value="<?= e($mKey) ?>" <?= (string) ($event['age_mode'] ?? 'jahrgang') === $mKey ? 'selected' : '' ?>><?= e(t($mLabel)) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="field__hint"><?= e(t('WAKO, World Boxing und IFMA rechnen nach dem Geburtsjahr – der Geburtstag spielt keine Rolle. Bei den Kategorien stehen dann die Jahrgänge dabei.')) ?></p>
            </div>

            <div class="field-row">
                <div class="field field--sm">
                    <label for="registration_from"><?= e(t('Anmeldung ab')) ?></label>
                    <input id="registration_from" name="registration_from" type="date" value="<?= e((string) $event['registration_from']) ?>"<?= $ro ?>>
                </div>
                <div class="field field--sm">
                    <label for="registration_until"><?= e(t('Anmeldeschluss')) ?></label>
                    <input id="registration_until" name="registration_until" type="date" value="<?= e((string) $event['registration_until']) ?>"<?= $ro ?>>
                </div>
            </div>
            <div class="field-row">
                <div class="field field--sm">
                    <label for="max_entries"><?= t('Max. Teilnehmer <small>(0 = unbegrenzt)</small>') ?></label>
                    <input id="max_entries" name="max_entries" type="number" min="0" value="<?= (int) $event['max_entries'] ?>"<?= $ro ?>>
                </div>
                <div class="field field--sm">
                    <label for="entry_fee"><?= e(t('Startgeld (€)')) ?></label>
                    <input id="entry_fee" name="entry_fee" inputmode="decimal" value="<?= e(number_format((float) $event['entry_fee'], 2, ',', '')) ?>"<?= $ro ?>>
                </div>
            </div>
        </fieldset>

        <fieldset class="card">
            <legend><?= e(t('Website')) ?></legend>

            <label class="check">
                <input type="checkbox" name="published" value="1" <?= (int) $event['published'] === 1 ? 'checked' : '' ?><?= $ro ?>>
                <?= e(t('Event-Seite öffentlich sichtbar')) ?>
            </label>
            <label class="check">
                <input type="checkbox" name="show_entries" value="1" <?= (int) $event['show_entries'] === 1 ? 'checked' : '' ?><?= $ro ?>>
                <?= e(t('Teilnehmerliste (bestätigte Anmeldungen) öffentlich zeigen')) ?>
            </label>
            <label class="check">
                <input type="checkbox" name="show_results" value="1" <?= (int) $event['show_results'] === 1 ? 'checked' : '' ?><?= $ro ?>>
                <?= e(t('Ergebnisse öffentlich zeigen')) ?>
            </label>
            <p class="field__hint">
                <?= t('Soll dieses Event die <strong>komplette Startseite</strong> sein (eigene Event-Homepage), unter <a href="%s">Einstellungen → Startseite</a> auswählen.', e(url('/admin/einstellungen'))) ?>
            </p>
        </fieldset>
    </div>

    <fieldset class="card">
        <legend><?= e(t('Fightcard, Zeiten & Tickets')) ?></legend>
        <div class="field-row">
            <div class="field field--sm"><label for="short_name"><?= e(t('Kurzname')) ?></label><input id="short_name" name="short_name" value="<?= e($event['short_name']) ?>" placeholder="NAFN 3"<?= $ro ?>></div>
            <div class="field field--xs"><label for="default_bout_minutes"><?= e(t('Min. je Kampf')) ?></label><input id="default_bout_minutes" name="default_bout_minutes" type="number" min="1" value="<?= (int) $event['default_bout_minutes'] ?>"<?= $ro ?>></div>
            <div class="field field--xs"><label for="default_break_minutes"><?= e(t('Min. je Pause')) ?></label><input id="default_break_minutes" name="default_break_minutes" type="number" min="1" value="<?= (int) $event['default_break_minutes'] ?>"<?= $ro ?>></div>
            <div class="field field--grow"><label for="min_age_note"><?= e(t('Einlass-Hinweis')) ?></label><input id="min_age_note" name="min_age_note" value="<?= e($event['min_age_note']) ?>" placeholder="<?= e(t('Einlass ab 10 Jahren')) ?>"<?= $ro ?>></div>
        </div>
        <p class="field__hint"><?= e(t('Aus Beginnzeit und Dauer werden die voraussichtlichen Beginnzeiten aller Kämpfe berechnet (einzelne Kämpfe können eine eigene Dauer haben).')) ?></p>
        <label class="check"><input type="checkbox" name="live_mode" value="1" <?= (int) $event['live_mode'] === 1 ? 'checked' : '' ?><?= $ro ?>> <?= e(t('Live-Modus: Zeiten richten sich nach den echten Start-/Endzeiten (am Veranstaltungstag einschalten)')) ?></label>
        <label class="check"><input type="checkbox" name="show_countdown" value="1" <?= (int) $event['show_countdown'] === 1 ? 'checked' : '' ?><?= $ro ?>> <?= e(t('Countdown auf der Event-Seite')) ?></label>
        <label class="check"><input type="checkbox" name="show_map" value="1" <?= (int) $event['show_map'] === 1 ? 'checked' : '' ?><?= $ro ?>> <?= e(t('Karte (Google Maps, erst nach Klick geladen)')) ?></label>
        <div class="field"><label for="location_note"><?= e(t('Text zur Location')) ?></label><input id="location_note" name="location_note" value="<?= e($event['location_note']) ?>" placeholder="<?= e(t('Mitten in Weiz – gut erreichbar, genug Parkplätze')) ?>"<?= $ro ?>></div>

        <legend style="margin-top:1rem"><?= e(t('Ticketpreise')) ?></legend>
        <?php $ticketRows = \App\Models\EventRepo::tickets($event); ?>
        <?php for ($i = 0; $i < max(3, count($ticketRows) + 1); $i++): ?>
            <?php $t = $ticketRows[$i] ?? ['label' => '', 'price' => '', 'note' => '', 'highlight' => false]; ?>
            <div class="field-row">
                <div class="field field--sm"><label><?= e(t('Kategorie')) ?></label><input name="tickets[<?= $i ?>][label]" value="<?= e($t['label']) ?>" placeholder="<?= e(t('Vorverkauf')) ?>"<?= $ro ?>></div>
                <div class="field field--xs"><label><?= e(t('Preis')) ?></label><input name="tickets[<?= $i ?>][price]" value="<?= e($t['price']) ?>" placeholder="35 €"<?= $ro ?>></div>
                <div class="field field--grow"><label><?= e(t('Hinweis')) ?></label><input name="tickets[<?= $i ?>][note]" value="<?= e($t['note']) ?>"<?= $ro ?>></div>
                <label class="check"><input type="checkbox" name="tickets[<?= $i ?>][highlight]" value="1" <?= $t['highlight'] ? 'checked' : '' ?><?= $ro ?>> <?= e(t('hervorheben')) ?></label>
            </div>
        <?php endfor; ?>
        <div class="field"><label for="ticket_note"><?= e(t('Text über den Tickets')) ?></label><input id="ticket_note" name="ticket_note" value="<?= e($event['ticket_note']) ?>" placeholder="<?= e(t('Sei live dabei – wenn weg, dann weg!')) ?>"<?= $ro ?>></div>
        <div class="field">
            <label for="ticket141_slug"><?= t('Ticket141-Event <small>(Kürzel)</small>') ?></label>
            <input id="ticket141_slug" name="ticket141_slug" value="<?= e((string) ($event['ticket141_slug'] ?? '')) ?>" placeholder="<?= e(t('wird beim Anlegen in Ticket141 gesetzt')) ?>"<?= $ro ?>>
            <p class="field__hint">
                <?= e(t('Ist ein Ticket141-Event verknüpft, zeigt die Event-Seite dessen Kategorien mit Preis und Verfügbarkeit und einen „Tickets kaufen“-Knopf (die Ticketpreise oben dienen dann nur noch als Ersatz, falls Ticket141 nicht erreichbar ist). Leer = keine Kopplung.')) ?>

                <?= !$isNew && (string) ($event['ticket141_slug'] ?? '') === '' && ($ticket141 ?? null) !== null ? e(t('Am einfachsten: oben „In Ticket141 anlegen“.')) : '' ?>
            </p>
        </div>

        <legend style="margin-top:1rem"><?= e(t('Social Media')) ?></legend>
        <?php $socialLinks = \App\Models\EventRepo::social($event); ?>
        <div class="field-row">
            <?php foreach (\App\Controllers\EventAdminController::SOCIAL as $net => $label): ?>
                <div class="field field--grow"><label><?= e($label) ?></label><input name="social[<?= e($net) ?>]" value="<?= e($socialLinks[$net] ?? '') ?>" placeholder="https://…"<?= $ro ?>></div>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset class="card">
        <legend><?= t('Beschreibung <small>(Event-Seite)</small>') ?></legend>
        <div class="field">
            <textarea id="description" name="description" rows="10" class="js-richtext"<?= $ro ?>><?= e($event['description']) ?></textarea>
        </div>
    </fieldset>

    <fieldset class="card">
        <legend><?= e(t('Bilder')) ?></legend>
        <div class="image-grid">
            <?php foreach ([
                'logo'   => [t('Logo'), $event['logo_path'], t('Event-Logo, quadratisch, max. 600 px')],
                'poster' => [t('Poster'), $event['poster_path'], t('Plakat hochkant, max. 1200 px')],
                'hero'   => [t('Titelbild'), $event['hero_path'], t('Breites Bild oben auf der Event-Seite, max. 1900 px')],
                'belt'   => [t('Titelgürtel'), $event['belt_path'] ?? '', t('Freigestellt (PNG/WebP) – erscheint bei Titelkämpfen über den Kämpfern')],
            ] as $key => [$label, $path, $hint]): ?>
                <div class="image-slot">
                    <p class="image-slot__label"><?= e($label) ?></p>
                    <?php if ((string) $path !== ''): ?>
                        <img src="<?= e(upload_url((string) $path)) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <div class="image-slot__empty"><?= e(t('kein Bild')) ?></div>
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
            <button class="btn btn--primary" type="submit"><?= e($isNew ? t('Event anlegen') : t('Änderungen speichern')) ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/admin/events')) ?>"><?= e(t('Zur Liste')) ?></a>
        </div>
    <?php endif; ?>
</form>

<?php if (!$isNew && Auth::canWrite()): ?>
    <?php foreach (['logo' => $event['logo_path'], 'poster' => $event['poster_path'], 'hero' => $event['hero_path'], 'belt' => $event['belt_path'] ?? ''] as $key => $path): ?>
        <?php if ((string) $path === '') { continue; } ?>
        <form method="post" action="<?= e(url('/admin/events/' . $id . '/bild-entfernen')) ?>" class="inline" data-confirm="<?= e(t('Bild wirklich entfernen?')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="field" value="<?= e($key) ?>_path">
            <button class="linklike linklike--danger" type="submit"><?= e(t('%s entfernen', ucfirst($key))) ?></button>
        </form>
    <?php endforeach; ?>

    <?php if (Auth::isSuperuser()): ?>
        <div class="card card--danger">
            <div class="card__head"><h2><?= e(t('Event löschen')) ?></h2></div>
            <p class="muted"><?= e(t('Löscht das Event mit allen Anmeldungen, Kämpfen, Tagen und Kategorien. Sportler und Gyms bleiben erhalten.')) ?></p>
            <form method="post" action="<?= e(url('/admin/events/' . $id . '/loeschen')) ?>" data-confirm="<?= e(t('Event „%s“ endgültig löschen?', $event['name'])) ?>">
                <?= csrf_field() ?>
                <button class="btn btn--danger" type="submit"><?= e(t('Event löschen')) ?></button>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>
