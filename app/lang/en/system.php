<?php

/**
 * Englisch – System: Meldungen aus Kernklassen und Modellen (Lizenz, Uploads,
 * Bildbibliothek, Sitzung, Gym141-/Ticket141-Anbindung, E-Mail-Versand) und
 * berechnete Beschriftungen (Zeit-Labels, Kategorie-Beschreibung).
 */

return [
    // Lizenz (License)
    'Lizenz gesperrt: Dieser Lizenzschlüssel wird auf mehreren Domains verwendet. Event141 Pro gilt für ein System – bitte die überzähligen Installationen stilllegen oder eine weitere Lizenz erwerben (account.devworld-llc.com). Sobald nur noch eine Domain aktiv ist, wird die Lizenz automatisch wieder gültig.'
        => 'Licence blocked: this licence key is being used on several domains. Event141 Pro covers one system – please shut down the surplus installations or purchase an additional licence (account.devworld-llc.com). As soon as only one domain is active, the licence becomes valid again automatically.',
    'Die Gratis-Version erlaubt %d aktives Event. Für unbegrenzte Events, Turnierbaum und Gym141-Kopplung gibt es Event141 Pro auf account.devworld-llc.com – den Lizenzschlüssel dann unter Einstellungen eintragen. (Ein Event auf „Beendet“ zu stellen schafft ebenfalls Platz.)'
        => 'The free version allows %d active event. For unlimited events, brackets and Gym141 integration, get Event141 Pro at account.devworld-llc.com – then enter the licence key under Settings. (Setting an event to “Finished” also frees up a slot.)',
    '%s gehört zu Event141 Pro. Lizenz auf account.devworld-llc.com, den Schlüssel dann unter Einstellungen eintragen.'
        => '%s is part of Event141 Pro. Get a licence at account.devworld-llc.com, then enter the key under Settings.',

    // Uploads (Upload)
    'Die Datei konnte nicht gelesen werden.'               => 'The file could not be read.',
    'Die Datei ist größer als 8 MB.'                       => 'The file is larger than 8 MB.',
    'Nur JPG-, PNG-, GIF- oder WEBP-Bilder sind erlaubt.'  => 'Only JPG, PNG, GIF or WEBP images are allowed.',
    'Das Upload-Verzeichnis konnte nicht angelegt werden.' => 'The upload directory could not be created.',
    'Die Datei konnte nicht gespeichert werden.'           => 'The file could not be saved.',
    'Das Video ist größer als 100 MB.'                     => 'The video is larger than 100 MB.',
    'Nur MP4- oder WebM-Videos sind erlaubt.'              => 'Only MP4 or WebM videos are allowed.',
    'Die Datei ist zu groß.'                               => 'The file is too large.',
    'Der Upload wurde abgebrochen.'                        => 'The upload was interrupted.',
    'Auf dem Server fehlt ein temporäres Verzeichnis.'     => 'The server is missing a temporary directory.',
    'Die Datei konnte nicht geschrieben werden.'           => 'The file could not be written.',
    'Beim Upload ist ein Fehler aufgetreten.'              => 'An error occurred during the upload.',

    // Bildbibliothek (ImageTool)
    'Datei fehlt.'                                         => 'File missing.',
    'Die Datei ist größer als %s MB.'                      => 'The file is larger than %s MB.',
    'Die Bildbibliothek braucht die PHP-Erweiterung GD.'   => 'The image library requires the PHP extension GD.',
    'Die Datei ist kein Bild.'                             => 'The file is not an image.',
    'Nur JPG, PNG, GIF oder WebP – oder das Bild ist zu groß für den Speicher.'
        => 'Only JPG, PNG, GIF or WebP – or the image is too large for the available memory.',
    'Der Upload-Ordner konnte nicht angelegt werden.'      => 'The upload folder could not be created.',
    'Das Bild konnte nicht gespeichert werden (Schreibrechte im Upload-Ordner?).'
        => 'The image could not be saved (write permissions on the upload folder?).',
    'Die Datei ist zu groß (Server-Limit).'                => 'The file is too large (server limit).',
    'Keine Datei ausgewählt.'                              => 'No file selected.',

    // Sitzung (Csrf)
    'Die Sitzung ist abgelaufen. Bitte erneut versuchen.'  => 'Your session has expired. Please try again.',

    // Voraussichtliche Beginnzeiten (Timetable)
    'seit %s' => 'since %s',
    'ca. %s'  => 'approx. %s',

    // Gym141-Anbindung (Gym141Client)
    'Bitte die Adresse der Gym141-Instanz angeben.' => 'Please enter the address of the Gym141 instance.',
    'Die Gym141-Adresse ist keine gültige URL.'     => 'The Gym141 address is not a valid URL.',
    'Gym141 hat kein Token geliefert.'              => 'Gym141 did not return a token.',
    'Gym141 hat keine gültige Antwort geliefert (HTTP %d). Stimmt die Adresse?'
        => 'Gym141 did not return a valid response (HTTP %d). Is the address correct?',
    'Gym141-Fehler (HTTP %d)'                       => 'Gym141 error (HTTP %d)',
    'Verbindung konnte nicht aufgebaut werden.'     => 'The connection could not be established.',
    'Gym141 nicht erreichbar: %s'                   => 'Gym141 unreachable: %s',

    // Ticket141-Anbindung (Ticket141Client)
    'Bitte die Adresse der Ticket141-Instanz angeben.'         => 'Please enter the address of the Ticket141 instance.',
    'Die Ticket141-Adresse ist keine gültige URL.'             => 'The Ticket141 address is not a valid URL.',
    'Bitte einen Ticket141-API-Schlüssel (tk_…) hinterlegen.'  => 'Please enter a Ticket141 API key (tk_…).',
    'Unter dieser Adresse antwortet kein Ticket141.'           => 'No Ticket141 instance is responding at this address.',
    'Ticket141 hat kein Event-Kürzel geliefert.'               => 'Ticket141 did not return an event slug.',
    'Ticket141 ist nicht konfiguriert (Einstellungen → Ticket141).' => 'Ticket141 is not configured (Settings → Ticket141).',
    'Ticket141 hat keine gültige Antwort geliefert (HTTP %d). Stimmt die Adresse?'
        => 'Ticket141 did not return a valid response (HTTP %d). Is the address correct?',
    'Ticket141-Fehler (HTTP %d)'                               => 'Ticket141 error (HTTP %d)',
    'Ticket141 nicht erreichbar: %s'                           => 'Ticket141 unreachable: %s',
    'ausverkauft'                                              => 'sold out',
    'nur noch %d'                                              => 'only %d left',

    // E-Mail-Versand (Mailer)
    'Ungültige Empfängeradresse.' => 'Invalid recipient address.',
    'PHP mail() ist auf diesem Server nicht verfügbar – bitte SMTP-Zugangsdaten hinterlegen.'
        => 'PHP mail() is not available on this server – please enter SMTP credentials.',
    'SMTP-Versand fehlgeschlagen: %s'           => 'SMTP delivery failed: %s',
    'Verbindung zu %s:%d fehlgeschlagen (%s).'  => 'Connection to %s:%d failed (%s).',
    'keine Antwort auf %s'                      => 'no response to %s',
    'STARTTLS-Verschlüsselung fehlgeschlagen.'  => 'STARTTLS encryption failed.',

    // Kategorie-Beschreibung (EventRepo::categoryInfo)
    '%s–%s J.'      => '%s–%s yrs',
    '>%s bis %s kg' => '>%s to %s kg',
    'bis %s kg'     => 'up to %s kg',

    // Anzeigename mit Kampfname (EntryRepo::label)
    '%s „%s“' => '%s “%s”',
];
