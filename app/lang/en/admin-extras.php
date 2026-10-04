<?php

/**
 * Englisch – Verwaltung: Sponsoren, Fightcard-Import, Listen & Druck (Aushang,
 * Kämpfer-Checkliste, Kabineneinteilung), Galerie & Bericht, Bildbibliothek.
 */

return [
    // Allgemein
    'Name'         => 'Name',
    'Link'         => 'Link',
    'Logo'         => 'Logo',
    'Text'         => 'Text',
    'Gym'          => 'Gym',
    'Gyms'         => 'Gyms',
    'Events'       => 'Events',
    'Ring'         => 'Ring',
    'Titel'        => 'Title',
    'Titel *'      => 'Title *',
    'Reihung'      => 'Order',
    'Speichern'    => 'Save',
    'Entfernen'    => 'Remove',
    'Löschen'      => 'Delete',
    'Hinzufügen'   => 'Add',
    'Zurück'       => 'Back',
    'Zur Liste'    => 'Back to list',
    'Suchen'       => 'Search',
    'Zurücksetzen' => 'Reset',
    'Hochladen'    => 'Upload',
    'ja'           => 'yes',
    'nein'         => 'no',
    'offen'        => 'TBD',
    'Klasse'       => 'Class',
    'Kampf'        => 'Bout',
    'Bilder'       => 'Images',

    // Sponsoren
    'Sponsoren' => 'Sponsors',
    'Erscheinen unten auf der Event-Seite. Hauptsponsoren bekommen eine große Kachel. Die Kachelfarbe passt den Hintergrund ans Logo an (weißes Logo → dunkle Kachel).'
        => 'Shown at the bottom of the event page. Main sponsors get a large tile. The tile colour matches the background to the logo (white logo → dark tile).',
    'Kachel'                  => 'Tile',
    'Farbe'                   => 'Colour',
    'Haupt'                   => 'Main',
    'sichtbar'                => 'visible',
    'Logo ersetzen'           => 'Replace logo',
    'Sponsor „%s“ entfernen?' => 'Remove sponsor “%s”?',
    'Noch keine Sponsoren.'   => 'No sponsors yet.',
    'Neuer Sponsor'           => 'New sponsor',
    'Hauptsponsor'            => 'Main sponsor',
    'Bitte den Namen des Sponsors angeben.' => 'Please enter the name of the sponsor.',
    'Logo nicht übernommen: %s' => 'Logo not saved: %s',
    'Sponsor gespeichert.'      => 'Sponsor saved.',
    'Sponsor entfernt.'         => 'Sponsor removed.',

    // Fightcard-Import
    'Fightcard importieren' => 'Import fight card',
    'Übernimmt ein Event aus einer bestehenden Event-Website im NAFN-Format (<code>data/fights.json</code>).'
        => 'Imports an event from an existing event website in NAFN format (<code>data/fights.json</code>).',
    'Angelegt wird ein <strong>Gala-Event</strong> mit allen Kämpfen und Pausen (Reihenfolge, Blöcke, Storys, Status, Ergebnisse), dazu die <strong>Gyms</strong> und <strong>Kämpfer</strong> (Kampfname, Alter, Bilanz, Bio, Foto/Video-Modus). Schon vorhandene Gyms/Kämpfer mit gleichem Namen werden wiederverwendet. Das Event bleibt zunächst unveröffentlicht.'
        => 'Creates a <strong>gala event</strong> with all bouts and breaks (order, blocks, stories, status, results), plus the <strong>gyms</strong> and <strong>fighters</strong> (ring name, age, record, bio, photo/video mode). Existing gyms/fighters with the same name are reused. The event stays unpublished for now.',
    'Von einer Website holen'   => 'Fetch from a website',
    'Adresse der Event-Website' => 'Address of the event website',
    'Gelesen werden <code>/data/fights.json</code> und (falls vorhanden) <code>/data/fighters.json</code>.'
        => 'Reads <code>/data/fights.json</code> and (if present) <code>/data/fighters.json</code>.',
    'Kämpferfotos und -videos mit übernehmen (kann dauern)' => 'Also import fighter photos and videos (may take a while)',
    'Früheren Import dieses Events auffrischen (gleiche Event-ID: Schlüssel, Webhooks, Sponsoren, Bilder und Website-Einstellungen bleiben)'
        => 'Refresh an earlier import of this event (same event ID: keys, webhooks, sponsors, images and website settings are kept)',
    'Importieren'                         => 'Import',
    'Dateien hochladen'                   => 'Upload files',
    '(Kämpfer-Pool, optional)'            => '(fighter pool, optional)',
    'Medien von dieser Adresse nachladen:' => 'Load media from this address:',
    'Keine gültige fights.json gefunden unter %s.' => 'No valid fights.json found at %s.',
    'Keine gültige fights.json gefunden.'          => 'No valid fights.json found.',
    'Import fehlgeschlagen: %s'                    => 'Import failed: %s',
    'Fightcard importiert: %d Kämpfe, %d neue Sportler, %d neue Gyms, %d Mediendateien. Das Event ist noch nicht veröffentlicht.'
        => 'Fight card imported: %d bouts, %d new athletes, %d new gyms, %d media files. The event is not published yet.',

    // Live-Modus
    'Live-Modus an: die Beginnzeiten richten sich jetzt nach den echten Start- und Endzeiten der Kämpfe.'
        => 'Live mode on: start times now follow the actual start and end times of the bouts.',
    'Live-Modus aus: die Beginnzeiten werden aus Startzeit und geplanter Dauer berechnet.'
        => 'Live mode off: start times are calculated from the start time and the planned duration.',

    // Listen & Druck
    'Listen & Druck'                              => 'Lists & print',
    'Fightcard-Aushang (Kabinen, Kampfgericht)'   => 'Fight card notice sheet (changing rooms, officials)',
    'Kämpfer-Checkliste (Waage, Musik, Arzt)'     => 'Fighter checklist (weigh-in, music, medical)',
    'Kabineneinteilung + Türschilder'             => 'Changing room allocation + door signs',
    'Teilnehmer als CSV (Excel)'                  => 'Participants as CSV (Excel)',
    'Die Druckansichten öffnen in einem neuen Tab – dort mit Strg+P drucken oder als PDF speichern.'
        => 'The print views open in a new tab – print from there with Ctrl+P or save as PDF.',
    'Kabinen-Konflikt:'    => 'Changing room conflict:',
    'Checkliste'           => 'Checklist',
    'Checkliste speichern' => 'Save checklist',
    'Gewicht Waage (kg)'   => 'Weigh-in weight (kg)',
    'Einlaufmusik'         => 'Walkout music',
    'Ärztl. Unters.'       => 'Medical check',
    'Kabine'               => 'Changing room',
    'Titel / Datei'        => 'Title / file',
    'z. B. 1'              => 'e.g. 1',
    'Noch keine bestätigten Anmeldungen.' => 'No confirmed entries yet.',
    'Kabinen-Regel: Gegner dürfen nicht in derselben Kabine sein – Konflikte werden oben rot gemeldet. Gyms am besten zusammenlassen.'
        => 'Changing room rule: opponents must not share a changing room – conflicts are flagged in red above. Best to keep gyms together.',
    'Checkliste gespeichert (%d Sportler).' => 'Checklist saved (%d athletes).',
    'Kampf #%s: %s und %s sind beide in Kabine „%s“.' => 'Bout #%s: %s and %s are both in changing room “%s”.',

    // Druckseiten
    'Fightcard'             => 'Fight card',
    'Beginn %s Uhr'         => 'Start %s',
    'Zeiten sind Richtwerte' => 'Times are approximate',
    'Stand %s'              => 'As of %s',
    'Nr.'                   => 'No.',
    'ca.'                   => 'approx.',
    'ca. %s'                => 'approx. %s',
    'Rote Ecke'             => 'Red corner',
    'Blaue Ecke'            => 'Blue corner',
    'Disziplin / Klasse'    => 'Discipline / class',
    'Runden'                => 'Rounds',
    'Kämpfer-Check'         => 'Fighter check',
    '%d Sportler'           => '%d athletes',
    'Musik'                 => 'Music',
    'fehlt'                 => 'missing',
    'Kabinen'               => 'Changing rooms',
    'Kabine %s'             => 'Changing room %s',
    'Ohne Kabine: %s'       => 'No changing room: %s',
    'Noch keine Kabinen vergeben – unter „Listen & Druck“ in der Checkliste je Sportler eine Kabine eintragen.'
        => 'No changing rooms assigned yet – enter a changing room for each athlete in the checklist under “Lists & print”.',

    // Galerie & Bericht
    'Galerie & Bericht' => 'Gallery & report',
    'Noch keine Bilder in der Bildbibliothek – zuerst unter <a href="%s">Medien</a> hochladen (mit Tags, z. B. „%s“).'
        => 'No images in the image library yet – upload them under <a href="%s">Media</a> first (with tags, e.g. “%s”).',
    'Event-Bericht' => 'Event report',
    'Erscheint nach dem Event auf der Event-Seite (Teaser) und unter <a href="%s" target="_blank" rel="noopener">%s ↗</a>. Leerzeile = Absatz, „## “ am Zeilenanfang = Zwischenüberschrift.'
        => 'Shown after the event on the event page (teaser) and at <a href="%s" target="_blank" rel="noopener">%s ↗</a>. Blank line = paragraph, “## ” at the start of a line = subheading.',
    'Überschrift'                            => 'Heading',
    'z. B. Das war die Fight Night 3'        => 'e.g. That was Fight Night 3',
    'Bericht auf der Website anzeigen'       => 'Show report on the website',
    'Titelbild (ein Bild – das gewählte gilt)' => 'Cover image (one image – the selected one is used)',
    'Bilder zum Bericht (%d)'                => 'Report images (%d)',
    'Bericht speichern'                      => 'Save report',
    'Bericht gespeichert.'                   => 'Report saved.',
    'Galerien (%d)'                          => 'Galleries (%d)',
    'Neue Galerie'                           => 'New gallery',
    'Bildersammlungen mit eigener Seite, z. B. „Impressionen“, „Backstage“, „Publikum“. Übersicht unter <a href="%s" target="_blank" rel="noopener">%s ↗</a>; der Reiter „Galerie“ erscheint auf der Event-Seite, sobald eine Galerie sichtbar ist.'
        => 'Image collections with their own page, e.g. “Impressions”, “Backstage”, “Crowd”. Overview at <a href="%s" target="_blank" rel="noopener">%s ↗</a>; the “Gallery” tab appears on the event page as soon as a gallery is visible.',
    'Noch keine Galerie.' => 'No gallery yet.',
    'Sichtbar'            => 'Visible',
    'Verstecken'          => 'Hide',
    'Sichtbar machen'     => 'Make visible',
    'Galerie „%s“ löschen? Die Bilder bleiben in der Bildbibliothek.' => 'Delete gallery “%s”? The images stay in the image library.',
    'Bilder je Kampf'     => 'Images per bout',
    'Bilder und ein Nachwort („Nach dem Kampf“) trägst du direkt beim jeweiligen Kampf ein (<a href="%s">Fightcard</a> → Kampf öffnen).'
        => 'Images and an epilogue (“After the bout”) are entered directly on the bout itself (<a href="%s">Fight card</a> → open bout).',
    'Bisher mit Bildern: %d Kämpfe, %d Bilder.' => 'With images so far: %d bouts, %d images.',
    'Noch kein Kampf mit Bildern.'              => 'No bout with images yet.',
    'Kampf nicht gefunden.'                     => 'Bout not found.',
    'Bilder zum Kampf gespeichert.'             => 'Bout images saved.',

    // Galerie-Formular
    'Galerie: %s'                         => 'Gallery: %s',
    'URL-Kürzel'                          => 'URL slug',
    'wird aus dem Titel gebildet'         => 'generated from the title',
    'Text (optional – Leerzeile = Absatz)' => 'Text (optional – blank line = paragraph)',
    'Auf der Website sichtbar'            => 'Visible on the website',
    'Öffentlich:'                         => 'Public:',
    'Erst suchen oder nach Tag filtern, dann anhaken; unten die Reihenfolge festlegen. Das erste Bild ist das Titelbild – oder in der Reihenfolge-Leiste ★ klicken.'
        => 'Search or filter by tag first, then tick; set the order below. The first image is the cover image – or click ★ in the order bar.',
    'Galerie anlegen'             => 'Create gallery',
    'Galerie nicht gefunden.'     => 'Gallery not found.',
    'Galerie angelegt.'           => 'Gallery created.',
    'Galerie gespeichert.'        => 'Gallery saved.',
    'Galerie gelöscht – die Bilder bleiben in der Bildbibliothek.' => 'Gallery deleted – the images stay in the image library.',
    'Bitte einen Titel eingeben.' => 'Please enter a title.',

    // Bildbibliothek
    'Bildbibliothek: einmal hochladen, mit Tags verschlagworten – dann bei Kämpfen, im Event-Bericht und in Galerien auswählen.'
        => 'Image library: upload once, add tags – then select for bouts, the event report and galleries.',
    '%d Bilder, %d Tags.' => '%d images, %d tags.',
    'Bilder hochladen'    => 'Upload images',
    'Dateien (JPG, PNG, GIF, WebP – Mehrfachauswahl; werden am Server auf max. %d px verkleinert, Handyfotos richtig gedreht)'
        => 'Files (JPG, PNG, GIF, WebP – multiple selection; resized on the server to max. %d px, phone photos rotated correctly)',
    'Tags (mit Komma trennen)'           => 'Tags (separate with commas)',
    'z. B. nafn 3, hauptkampf, reiser'   => 'e.g. nafn 3, main event, reiser',
    'Bildtext (optional, gilt für alle hochgeladenen Bilder)' => 'Caption (optional, applies to all uploaded images)',
    'z. B. Titelkampf Aykac vs. Reiser'  => 'e.g. Title bout Aykac vs. Reiser',
    'Tipp: Tags wie Anlass, Jahr oder Gruppe – danach lassen sich Bilder überall gezielt finden.'
        => 'Tip: use tags such as occasion, year or group – images can then be found easily anywhere.',
    'Suchen: Tag, Bildtext, Dateiname … (mehrere Wörter = alle müssen passen)'
        => 'Search: tag, caption, file name … (several words = all must match)',
    'Noch keine Bilder – oben hochladen.' => 'No images yet – upload above.',
    'Kein Bild passt zur Suche.'          => 'No image matches the search.',
    'Aktion für die ausgewählten Bilder ausführen?' => 'Apply the action to the selected images?',
    '%d Bild(er)'         => '%d image(s)',
    'alle auswählen'      => 'select all',
    'Bild öffnen'         => 'Open image',
    'Tags / Bildtext'     => 'Tags / caption',
    'Tags, mit Komma'     => 'Tags, comma-separated',
    'Bildtext'            => 'Caption',
    'Ausgewählte Bilder:' => 'Selected images:',
    'Tags hinzufügen'     => 'Add tags',
    'Tags entfernen'      => 'Remove tags',
    'Tags (Komma)'        => 'Tags (comma)',
    'Ausführen'           => 'Apply',
    'Löschen entfernt die Bilder auch aus Kämpfen, Galerien und dem Bericht.'
        => 'Deleting also removes the images from bouts, galleries and the report.',
    '%d Bild(er) übernommen.'         => '%d image(s) added.',
    'Keine Datei ausgewählt.'         => 'No file selected.',
    'Bild nicht gefunden.'            => 'Image not found.',
    'Bild gespeichert.'               => 'Image saved.',
    'Keine Bilder ausgewählt.'        => 'No images selected.',
    'Bitte Tags eingeben.'            => 'Please enter tags.',
    'Tags bei %d Bild(ern) ergänzt.'  => 'Tags added to %d image(s).',
    'Tags bei %d Bild(ern) entfernt.' => 'Tags removed from %d image(s).',
    '%d Bild(er) gelöscht.'           => '%d image(s) deleted.',
    'Unbekannte Aktion.'              => 'Unknown action.',

    // Bild-Auswahl
    'Noch keine Bilder in der Bildbibliothek – unter <a href="%s">Bilder</a> hochladen.'
        => 'No images in the image library yet – upload them under <a href="%s">Images</a>.',
    'nur Ausgewählte' => 'selected only',
    'Reihenfolge – ziehen zum Sortieren, ✕ entfernt:' => 'Order – drag to sort, ✕ removes:',
];
