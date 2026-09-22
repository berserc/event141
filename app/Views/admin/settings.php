<?php

/**
 * @var array<string,string>      $settings
 * @var list<array<string,mixed>> $events
 */
$s = static fn (string $k, string $d = ''): string => e($settings[$k] ?? $d);
?>
<div class="page-head"><h1>Einstellungen</h1></div>

<form method="post" action="<?= e(url('/admin/einstellungen')) ?>" class="form">
    <?= csrf_field() ?>
    <div class="form-grid">
        <fieldset class="card">
            <legend>Veranstalter</legend>
            <div class="field"><label for="org_name">Name</label><input id="org_name" name="org_name" value="<?= $s('org_name') ?>" required></div>
            <div class="field"><label for="org_tagline">Untertitel</label><input id="org_tagline" name="org_tagline" value="<?= $s('org_tagline') ?>"></div>
            <div class="field"><label for="org_street">Straße</label><input id="org_street" name="org_street" value="<?= $s('org_street') ?>"></div>
            <div class="field-row">
                <div class="field field--xs"><label for="org_zip">PLZ</label><input id="org_zip" name="org_zip" value="<?= $s('org_zip') ?>"></div>
                <div class="field field--grow"><label for="org_city">Ort</label><input id="org_city" name="org_city" value="<?= $s('org_city') ?>"></div>
            </div>
            <div class="field-row">
                <div class="field field--grow"><label for="org_email">E-Mail</label><input id="org_email" name="org_email" type="email" value="<?= $s('org_email') ?>"></div>
                <div class="field field--sm"><label for="org_phone">Telefon</label><input id="org_phone" name="org_phone" value="<?= $s('org_phone') ?>"></div>
            </div>
            <div class="field"><label for="org_website">Website</label><input id="org_website" name="org_website" value="<?= $s('org_website') ?>"></div>
        </fieldset>

        <fieldset class="card">
            <legend>Betriebsmodus</legend>
            <label class="check"><input type="checkbox" name="public_site" value="1" <?= ($settings['public_site'] ?? '1') !== '0' ? 'checked' : '' ?>> Öffentliche Event-Website bereitstellen</label>
            <p class="field__hint">Abgeschaltet: / führt zur Anmeldung, Suchmaschinen werden ausgesperrt. Die Lese-API (/api/…) bleibt erreichbar.</p>
            <label class="check"><input type="checkbox" name="gym_area" value="1" <?= ($settings['gym_area'] ?? '1') !== '0' ? 'checked' : '' ?>> Gym-Bereich (/gym) aktiv – Gyms melden Sportler selbst an</label>
            <label class="check"><input type="checkbox" name="gym_signup" value="1" <?= ($settings['gym_signup'] ?? '1') !== '0' ? 'checked' : '' ?>> Gyms dürfen sich selbst registrieren</label>

            <legend style="margin-top:1rem">Startseite</legend>
            <div class="field">
                <label for="home_event">Startseite zeigt</label>
                <select id="home_event" name="home_event">
                    <option value="">Liste aller veröffentlichten Events</option>
                    <?php foreach ($events as $ev): ?>
                        <option value="<?= e($ev['slug']) ?>" <?= ($settings['home_event'] ?? '') === $ev['slug'] ? 'selected' : '' ?>>nur „<?= e($ev['name']) ?>“ (eigene Event-Homepage)</option>
                    <?php endforeach; ?>
                </select>
                <p class="field__hint">So wird eine Installation zur Homepage eines einzelnen Events (z. B. fightnight.at).</p>
            </div>
            <div class="field"><label for="home_title">Titel der Eventliste</label><input id="home_title" name="home_title" value="<?= $s('home_title', 'Unsere Events') ?>"></div>
            <div class="field"><label for="home_text">Einleitungstext</label><textarea id="home_text" name="home_text" rows="5" class="js-richtext"><?= $s('home_text') ?></textarea></div>
        </fieldset>

        <fieldset class="card">
            <legend>Lizenz</legend>
            <?php $lic = \App\Core\License::state(); $aktiv = \App\Core\License::activeEventCount(); ?>
            <?php if (\App\Core\License::isPro() && \App\Core\License::key() !== ''): ?>
                <p><span class="pill pill--aktiv">Event141 Pro</span>
                    <?= empty($lic['expires_at']) ? 'unbefristet (Lifetime)' : 'gültig bis ' . e(format_date(substr((string) $lic['expires_at'], 0, 10))) ?>
                    <?= !empty($lic['trial']) ? ' · Testphase' : '' ?></p>
            <?php elseif (\App\Core\License::key() !== ''): ?>
                <p><span class="pill pill--offen">Lizenz nicht gültig</span> <?= e((string) ($lic['reason'] ?? '')) ?></p>
            <?php else: ?>
                <p class="muted">Event141 ist Open Source. Gratis: <?= \App\Core\License::FREE_EVENT_LIMIT ?> aktives Event (derzeit <?= (int) $aktiv ?>).
                    <strong>Event141 Pro</strong> bringt unbegrenzte Events, den automatischen Turnierbaum sowie die Gym141- und Ticket141-Kopplung –
                    <a href="https://account.devworld-llc.com" target="_blank" rel="noopener">account.devworld-llc.com</a>.</p>
            <?php endif; ?>
            <div class="field">
                <label for="devworld_license_key">DevWorld-Lizenzschlüssel</label>
                <input id="devworld_license_key" name="devworld_license_key" autocomplete="off" placeholder="DW-XXXX-XXXX-XXXX-XXXX" value="<?= $s('devworld_license_key') ?>">
                <p class="field__hint">Wird beim Speichern sofort geprüft. Die Lizenz gilt für eine Domain.</p>
            </div>
            <button class="btn btn--sm" type="submit" formaction="<?= e(url('/admin/einstellungen/lizenz-pruefen')) ?>" formnovalidate>Lizenz jetzt prüfen</button>
        </fieldset>

        <fieldset class="card">
            <legend>Ticket141 (Ticketshop)</legend>
            <?php $tkUrl = $settings['ticket141_url'] ?? ''; $tkKey = ($settings['ticket141_api_key'] ?? '') !== ''; ?>
            <p class="muted">
                Ticketverkauf über das Schwesterprodukt <strong>Ticket141</strong>: Events per Knopf dort anlegen,
                Kategorien mit Preis und Verfügbarkeit auf der Event-Seite zeigen, Verkaufszahlen in der Verwaltung sehen.
                <?= $tkUrl !== '' ? '<span class="pill pill--aktiv">gekoppelt</span>' : '' ?>
            </p>
            <div class="field">
                <label for="ticket141_url">Adresse der Ticket141-Instanz</label>
                <input id="ticket141_url" name="ticket141_url" value="<?= e($tkUrl) ?>" placeholder="https://tickets.example.org" autocomplete="off">
                <p class="field__hint">Leer = keine Kopplung.</p>
            </div>
            <div class="field">
                <label for="ticket141_api_key">API-Schlüssel <small>(tk_…, Rechte „write“)</small></label>
                <input id="ticket141_api_key" name="ticket141_api_key" type="password" autocomplete="new-password" placeholder="<?= $tkKey ? 'gespeichert – zum Ändern neu eingeben' : 'tk_…' ?>">
                <p class="field__hint">In Ticket141 unter <em>API &amp; Webhooks</em> erzeugen. Leer lassen = bleibt unverändert.</p>
            </div>
            <label class="check"><input type="checkbox" name="ticket141_embed" value="1" <?= ($settings['ticket141_embed'] ?? '0') === '1' ? 'checked' : '' ?>> Shop als Widget direkt auf der Event-Seite einbetten (statt nur „Tickets kaufen“-Knopf)</label>
            <button class="btn btn--sm" type="submit" formaction="<?= e(url('/admin/einstellungen/ticket141-pruefen')) ?>" formnovalidate>Speichern &amp; Verbindung prüfen</button>
        </fieldset>

        <fieldset class="card">
            <legend>Ergebnisse</legend>
            <div class="field">
                <label for="win_methods">Siegarten <small>(eine je Zeile)</small></label>
                <textarea id="win_methods" name="win_methods" rows="8"><?= $s('win_methods') ?></textarea>
            </div>
        </fieldset>
    </div>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Einstellungen speichern</button>
    </div>
</form>
