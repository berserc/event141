<?php

/** Englisch – Verwaltung → Events: Liste, Grunddaten, Kopf/Tabs, Aufbau, Kategorien, Anmeldungen. */

return [
    // Tabs und Seitentitel
    'Aufbau'            => 'Structure',
    'Kategorien'        => 'Categories',
    'Anmeldungen'       => 'Entries',
    'Fightcard'         => 'Fight card',
    'Kämpfe'            => 'Bouts',
    'Zeitplan'          => 'Schedule',
    'Ergebnisse'        => 'Results',
    'Sponsoren'         => 'Sponsors',
    'Galerie & Bericht' => 'Gallery & report',
    'Listen & Druck'    => 'Lists & print',
    'Nicht gefunden'    => 'Not found',
    'Neues Event'       => 'New event',

    // Allgemeine Beschriftungen
    'Name'          => 'Name',
    'Name *'        => 'Name *',
    'Status'        => 'Status',
    'Event'         => 'Event',
    'Gym'           => 'Gym',
    'Gyms'          => 'Gyms',
    'Datum'         => 'Date',
    'Typ'           => 'Type',
    'Ort'           => 'City',
    'Kategorie'     => 'Category',
    'Hinweis'       => 'Note',
    'Reihung'       => 'Order',
    'Details'       => 'Details',
    'Runden'        => 'Rounds',
    'Geschlecht'    => 'Gender',
    'Alter'         => 'Age',
    'Gewicht'       => 'Weight',
    'von'           => 'from',
    'bis'           => 'to',
    'Speichern'     => 'Save',
    'Entfernen'     => 'Remove',
    'Löschen'       => 'Delete',
    'Hinzufügen'    => 'Add',
    'Filtern'       => 'Filter',
    'Zurücksetzen'  => 'Reset',
    'Zur Liste'     => 'Back to list',
    '– keine –'     => '– none –',
    '„%s“'          => '“%s”',
    'nicht veröffentlicht' => 'not published',

    // Liste
    'Fightcard importieren'    => 'Import fight card',
    'Noch kein Event angelegt.' => 'No events yet.',

    // Kopfbereich
    'Website ansehen ↗' => 'View website ↗',
    'Status: %s'        => 'Status: %s',
    'Unbekannter Status.' => 'Unknown status.',

    // Übersicht: Kennzahlen
    'bestätigte Anmeldungen' => 'confirmed entries',
    'offen zu bestätigen'    => 'awaiting confirmation',
    'Kämpfe beendet'         => 'Bouts finished',
    'nicht eingeplant'       => 'not scheduled',

    // Übersicht: Ticket141
    'Ticketverkauf'          => 'Ticket sales',
    'Shop ansehen ↗'         => 'View shop ↗',
    'Ticket141-Verwaltung ↗' => 'Ticket141 admin ↗',
    'Noch nicht gekoppelt. „In Ticket141 anlegen“ legt dort ein Event mit Name, Datum, Beginn, Einlass und Location an (als Entwurf) – Preise und Verkaufsstart dann in Ticket141.'
        => 'Not linked yet. “Create in Ticket141” creates an event there with name, date, start, doors and venue (as a draft) – prices and start of sales are then set in Ticket141.',
    'Gekoppelt mit „%s“, aber Ticket141 antwortet gerade nicht: %s'
        => 'Linked to “%s”, but Ticket141 is not responding at the moment: %s',
    'Gekoppelt mit „%s“. Für Verkaufszahlen einen API-Schlüssel unter Einstellungen → Ticket141 hinterlegen.'
        => 'Linked to “%s”. For sales figures, store an API key under Settings → Ticket141.',
    'Tickets verkauft'    => 'Tickets sold',
    'Umsatz'              => 'Revenue',
    'eingelassen'         => 'checked in',
    'offene Bestellungen' => 'open orders',
    'Status in Ticket141: <strong>%s</strong>, veröffentlicht'       => 'Status in Ticket141: <strong>%s</strong>, published',
    'Status in Ticket141: <strong>%s</strong>, nicht veröffentlicht' => 'Status in Ticket141: <strong>%s</strong>, not published',
    'In Ticket141 anlegen'               => 'Create in Ticket141',
    'Ticket141-Stammdaten aktualisieren' => 'Update basic details in Ticket141',
    'Überträgt Name, Untertitel, Datum, Beginn, Einlass und Location nach Ticket141 (Preise bleiben dort unangetastet).'
        => 'Transfers name, tagline, date, start, doors and venue to Ticket141 (prices there are left untouched).',
    'Die Ticket141-Kopplung' => 'The Ticket141 integration',
    'Ticket141 ist nicht eingerichtet – Adresse und API-Schlüssel unter Einstellungen → Ticket141 eintragen.'
        => 'Ticket141 is not set up – enter the address and API key under Settings → Ticket141.',
    'Das Ticket141-Event ist ein Entwurf: dort Kategorien mit Preisen anlegen und den Verkauf starten.'
        => 'The Ticket141 event is a draft: create categories with prices there and start sales.',
    'Event in Ticket141 angelegt („%s“).'       => 'Event created in Ticket141 (“%s”).',
    'Ticket141-Stammdaten aktualisiert („%s“).' => 'Basic details updated in Ticket141 (“%s”).',
    'Verwaltung: %s'                            => 'Admin: %s',

    // Formular: Grunddaten
    'Grunddaten'       => 'Basic details',
    'z. B. Fight Night 3 oder Steirische Meisterschaft' => 'e.g. Fight Night 3 or Styrian Championship',
    'Art des Events *' => 'Event type *',
    '<strong>Turnier:</strong> Kategorien, Anmeldungen, Turnierbaum je Kategorie. <strong>Gala:</strong> Fightcard mit einzeln zusammengestellten Kämpfen. Jederzeit umstellbar – vorhandene Daten bleiben erhalten.'
        => '<strong>Tournament:</strong> categories, entries, one bracket per category. <strong>Gala:</strong> fight card with individually matched bouts. Can be switched at any time – existing data is kept.',
    'Sportart'   => 'Sport',
    'Kickboxen'  => 'Kickboxing',
    'Untertitel' => 'Tagline',
    'Ein Satz für Startseite und Kopfzeile' => 'One sentence for the home page and header',
    'Beginn *'   => 'Start date *',
    'Ende <small>(mehrtägig)</small>' => 'End date <small>(multi-day)</small>',
    'Einlass'    => 'Doors',
    'Beginn'     => 'Start',
    'URL-Kürzel' => 'URL slug',
    'wird aus dem Namen gebildet' => 'generated from the name',
    'Event-Seite: %s' => 'Event page: %s',

    // Formular: Veranstaltungsort, Kontakt
    'Veranstaltungsort' => 'Venue',
    'Halle / Location'  => 'Hall / venue',
    'Sporthalle 2'      => 'Sports hall 2',
    'Straße'            => 'Street',
    'PLZ'               => 'Postcode',
    'Kontakt & Tickets' => 'Contact & tickets',
    'E-Mail'            => 'Email',
    'Telefon'           => 'Phone',
    'Ticket-Link'       => 'Ticket link',

    // Formular: Anmeldung durch Gyms
    'Anmeldung durch Gyms' => 'Entry by gyms',
    'Gyms dürfen ihre Sportler selbst anmelden (Status „Anmeldung offen“ nötig)'
        => 'Gyms may enter their athletes themselves (requires status “Registration open”)',
    'Anmeldung ab'    => 'Registration from',
    'Anmeldeschluss'  => 'Registration deadline',
    'Max. Teilnehmer' => 'Max. participants',
    'Max. Teilnehmer <small>(0 = unbegrenzt)</small>' => 'Max. participants <small>(0 = unlimited)</small>',
    'Startgeld (€)'   => 'Entry fee (€)',

    // Formular: Website
    'Event-Seite öffentlich sichtbar' => 'Event page publicly visible',
    'Teilnehmerliste (bestätigte Anmeldungen) öffentlich zeigen' => 'Show participant list (confirmed entries) publicly',
    'Ergebnisse öffentlich zeigen'    => 'Show results publicly',
    'Soll dieses Event die <strong>komplette Startseite</strong> sein (eigene Event-Homepage), unter <a href="%s">Einstellungen → Startseite</a> auswählen.'
        => 'If this event should be the <strong>entire home page</strong> (its own event homepage), select it under <a href="%s">Settings → Home page</a>.',

    // Formular: Fightcard, Zeiten, Tickets
    'Fightcard, Zeiten & Tickets' => 'Fight card, times & tickets',
    'Kurzname'        => 'Short name',
    'Min. je Kampf'   => 'Min. per bout',
    'Min. je Pause'   => 'Min. per break',
    'Einlass-Hinweis' => 'Admission note',
    'Einlass ab 10 Jahren' => 'Admission from age 10',
    'Aus Beginnzeit und Dauer werden die voraussichtlichen Beginnzeiten aller Kämpfe berechnet (einzelne Kämpfe können eine eigene Dauer haben).'
        => 'The expected start times of all bouts are calculated from the start time and duration (individual bouts can have their own duration).',
    'Live-Modus: Zeiten richten sich nach den echten Start-/Endzeiten (am Veranstaltungstag einschalten)'
        => 'Live mode: times follow the actual start/end times (switch on on the day of the event)',
    'Countdown auf der Event-Seite' => 'Countdown on the event page',
    'Karte (Google Maps, erst nach Klick geladen)' => 'Map (Google Maps, loaded only after a click)',
    'Text zur Location' => 'Text about the venue',
    'Mitten in Weiz – gut erreichbar, genug Parkplätze' => 'In the centre of Weiz – easy to reach, plenty of parking',
    'Ticketpreise' => 'Ticket prices',
    'Vorverkauf'   => 'Advance sale',
    'Preis'        => 'Price',
    'hervorheben'  => 'highlight',
    'Text über den Tickets' => 'Text above the tickets',
    'Sei live dabei – wenn weg, dann weg!' => 'Be there live – when they’re gone, they’re gone!',
    'Ticket141-Event <small>(Kürzel)</small>' => 'Ticket141 event <small>(slug)</small>',
    'wird beim Anlegen in Ticket141 gesetzt' => 'set when the event is created in Ticket141',
    'Ist ein Ticket141-Event verknüpft, zeigt die Event-Seite dessen Kategorien mit Preis und Verfügbarkeit und einen „Tickets kaufen“-Knopf (die Ticketpreise oben dienen dann nur noch als Ersatz, falls Ticket141 nicht erreichbar ist). Leer = keine Kopplung.'
        => 'If a Ticket141 event is linked, the event page shows its categories with price and availability and a “Buy tickets” button (the ticket prices above then only serve as a fallback if Ticket141 cannot be reached). Empty = not linked.',
    'Am einfachsten: oben „In Ticket141 anlegen“.' => 'Easiest: “Create in Ticket141” above.',
    'Social Media' => 'Social media',

    // Formular: Beschreibung, Bilder, Löschen
    'Beschreibung <small>(Event-Seite)</small>' => 'Description <small>(event page)</small>',
    'Bilder'      => 'Images',
    'Logo'        => 'Logo',
    'Poster'      => 'Poster',
    'Titelbild'   => 'Hero image',
    'Titelgürtel' => 'Title belt',
    'Event-Logo, quadratisch, max. 600 px' => 'Event logo, square, max. 600 px',
    'Plakat hochkant, max. 1200 px'        => 'Portrait poster, max. 1200 px',
    'Breites Bild oben auf der Event-Seite, max. 1900 px' => 'Wide image at the top of the event page, max. 1900 px',
    'Freigestellt (PNG/WebP) – erscheint bei Titelkämpfen über den Kämpfern'
        => 'Cut-out (PNG/WebP) – shown above the fighters in title bouts',
    'kein Bild'            => 'no image',
    'Event anlegen'        => 'Create event',
    'Änderungen speichern' => 'Save changes',
    'Bild wirklich entfernen?' => 'Really remove this image?',
    '%s entfernen'         => 'Remove %s',
    'Event löschen'        => 'Delete event',
    'Löscht das Event mit allen Anmeldungen, Kämpfen, Tagen und Kategorien. Sportler und Gyms bleiben erhalten.'
        => 'Deletes the event with all its entries, bouts, days and categories. Athletes and gyms are kept.',
    'Event „%s“ endgültig löschen?' => 'Permanently delete event “%s”?',

    // Meldungen: Event
    'Bitte die markierten Felder prüfen.' => 'Please check the highlighted fields.',
    'Bitte einen Namen angeben.'          => 'Please enter a name.',
    'Bitte ein gültiges Datum angeben.'   => 'Please enter a valid date.',
    'Das Ende liegt vor dem Beginn.'      => 'The end date is before the start date.',
    'Keine gültige E-Mail-Adresse.'       => 'Not a valid email address.',
    'Event angelegt, aber ein Bild wurde nicht übernommen: %s' => 'Event created, but an image was not saved: %s',
    'Event angelegt. Als Nächstes: Aufbau (Tage, Abschnitte, Wettkampfstätten) und Kategorien.'
        => 'Event created. Next: structure (days, sessions, competition areas) and categories.',
    'Ein Bild wurde nicht übernommen: %s' => 'An image was not saved: %s',
    'Typ auf Gala umgestellt: Kämpfe werden jetzt als Fightcard manuell zusammengestellt. Bestehende Turnierkämpfe bleiben erhalten.'
        => 'Type switched to gala: bouts are now put together manually as a fight card. Existing tournament bouts are kept.',
    'Typ auf Turnier umgestellt: je Kategorie lässt sich jetzt ein Turnierbaum erzeugen. Bestehende Fightcard-Kämpfe bleiben erhalten.'
        => 'Type switched to tournament: a bracket can now be generated for each category. Existing fight card bouts are kept.',
    'Event gespeichert.' => 'Event saved.',
    'Bild entfernt.'     => 'Image removed.',
    'Event „%s“ samt Anmeldungen und Kämpfen gelöscht.' => 'Event “%s” deleted along with its entries and bouts.',

    // Aufbau
    '<strong>So ist ein Event aufgebaut:</strong> Jeder <em>Tag</em> wird in <em>Abschnitte</em> unterteilt (z. B. „Vormittag – Vorrunden“, „Abend – Finals“), und Kämpfe finden auf <em>Wettkampfstätten</em> statt (Ring 1, Ring 2, Matte A …). Im <a href="%s">Zeitplan</a> werden die Kämpfe dann je Abschnitt auf die Wettkampfstätten verteilt.'
        => '<strong>How an event is structured:</strong> each <em>day</em> is divided into <em>sessions</em> (e.g. “Morning – preliminaries”, “Evening – finals”), and bouts take place on <em>competition areas</em> (Ring 1, Ring 2, Mat A …). In the <a href="%s">schedule</a>, the bouts of each session are then assigned to the competition areas.',
    'Tag bearbeiten'    => 'Edit day',
    'Bezeichnung'       => 'Label',
    'Tag 1 – Vorrunden' => 'Day 1 – preliminaries',
    'Tag 2 – Finals'    => 'Day 2 – finals',
    'Tag samt Abschnitten entfernen?' => 'Remove day including its sessions?',
    'Noch keine Abschnitte an diesem Tag.' => 'No sessions on this day yet.',
    'Abschnitt'            => 'Session',
    'Abschnitt entfernen?' => 'Remove session?',
    'Neuer Abschnitt'      => 'New session',
    'z. B. Vormittag – Vorrunden' => 'e.g. Morning – preliminaries',
    'Weiteren Tag hinzufügen' => 'Add another day',
    'Tag anlegen'          => 'Create day',
    'Die Tage des Event-Zeitraums (Beginn bis Ende) werden automatisch angelegt.'
        => 'The days of the event period (start to end) are created automatically.',
    'Wettkampfstätten'     => 'Competition areas',
    'Ringe, Matten, Käfige – alles, wo gleichzeitig gekämpft wird.' => 'Rings, mats, cages – anywhere bouts take place at the same time.',
    'Kurz'                 => 'Abbr.',
    'Farbe'                => 'Colour',
    'Wettkampfstätte entfernen?' => 'Remove competition area?',
    'Ringansicht'          => 'Ring view',
    'Noch keine Wettkampfstätte – mindestens eine (z. B. „Ring 1“) wird für den Zeitplan gebraucht.'
        => 'No competition area yet – at least one (e.g. “Ring 1”) is needed for the schedule.',
    'Neue Wettkampfstätte' => 'New competition area',

    // Meldungen: Aufbau
    'Diesen Tag gibt es schon.' => 'This day already exists.',
    'Tag gespeichert.'          => 'Day saved.',
    'Tag samt Abschnitten entfernt (Kämpfe bleiben erhalten und sind jetzt „nicht eingeplant“).'
        => 'Day and its sessions removed (bouts are kept and are now “not scheduled”).',
    'Bitte einen Namen für den Abschnitt angeben.' => 'Please enter a name for the session.',
    'Abschnitt gespeichert.'    => 'Session saved.',
    'Abschnitt entfernt.'       => 'Session removed.',
    'Bitte einen Namen für die Wettkampfstätte angeben (z. B. Ring 1).' => 'Please enter a name for the competition area (e.g. Ring 1).',
    'Wettkampfstätte gespeichert.' => 'Competition area saved.',
    'Wettkampfstätte entfernt.'    => 'Competition area removed.',
    'Datensatz nicht gefunden.'    => 'Record not found.',

    // Kategorien
    'Bei einer <strong>Gala</strong> sind Kategorien optional – sie dienen nur zur Beschriftung der Kämpfe (z. B. „K1 -75 kg“). Turnierbäume werden nur bei Turnieren erzeugt.'
        => 'For a <strong>gala</strong>, categories are optional – they only serve to label the bouts (e.g. “K1 -75 kg”). Brackets are only generated for tournaments.',
    'Anmeld.' => 'Entries',
    'Kategorie „%s“ entfernen?' => 'Remove category “%s”?',
    'Noch keine Kategorie. Beispiele: „Herren -75 kg“, „Jugend U16 -55 kg“, „Damen Leichtkontakt“.'
        => 'No categories yet. Examples: “Men -75 kg”, “Youth U16 -55 kg”, “Women light contact”.',
    'Kategorien aus einem anderen Event übernehmen' => 'Copy categories from another event',
    'Vorlage'    => 'Template',
    'Übernehmen' => 'Copy',
    'Aus Regelsatz anlegen: <strong>%s</strong> <small class="muted">(%d Kategorien)</small>'
        => 'Create from rule set: <strong>%s</strong> <small class="muted">(%d categories)</small>',
    'Tabellen ansehen' => 'View tables',
    'Disziplinen'      => 'Disciplines',
    'Altersklassen'    => 'Age classes',
    'Gleichnamige Kategorien bleiben unangetastet; alles lässt sich danach einzeln anpassen oder entfernen.'
        => 'Categories with the same name are left untouched; everything can be adjusted or removed individually afterwards.',
    'Kategorien anlegen'   => 'Create categories',
    'Kategorie bearbeiten' => 'Edit category',
    'Neue Kategorie'       => 'New category',
    'Herren -75 kg'        => 'Men -75 kg',
    'Disziplin'            => 'Discipline',
    'K1, Leichtkontakt, Judo …' => 'K1, light contact, judo …',
    'Alter von'            => 'Age from',
    'Alter bis'            => 'Age to',
    'Gewicht über (kg)'    => 'Weight over (kg)',
    'Gewicht bis (kg)'     => 'Weight up to (kg)',
    'Minuten je Runde'     => 'Minutes per round',
    'Modus'                => 'Mode',
    'K.-o.-System (Turnierbaum)' => 'Knockout system (bracket)',
    'Nur Teilnehmerliste (Kämpfe manuell)' => 'Participant list only (bouts created manually)',
    'Kategorie anlegen'    => 'Create category',

    // Meldungen: Kategorien
    'Bitte einen Namen für die Kategorie angeben.' => 'Please enter a name for the category.',
    'Kategorie gespeichert.' => 'Category saved.',
    'Kategorie entfernt.'    => 'Category removed.',
    'Die Kategorie hat noch Anmeldungen – bitte zuerst umbuchen oder abmelden.'
        => 'The category still has entries – please move or withdraw them first.',
    'Bitte ein anderes Event als Vorlage wählen.' => 'Please choose a different event as the template.',
    '%d Kategorien aus „%s“ übernommen.' => '%d categories copied from “%s”.',
    'Regelsatz nicht gefunden.' => 'Rule set not found.',
    'Bitte mindestens eine Disziplin, eine Altersklasse und ein Geschlecht wählen.'
        => 'Please select at least one discipline, one age class and one gender.',
    'Diese Auswahl ergibt keine Kategorien (die gewählten Altersklassen gibt es in den gewählten Disziplinen nicht).'
        => 'This selection produces no categories (the selected age classes do not exist in the selected disciplines).',
    '%d Kategorien aus „%s“ angelegt (%d gab es schon).' => '%d categories created from “%s” (%d already existed).',
    '%d Kategorien aus „%s“ angelegt.' => '%d categories created from “%s”.',

    // Anmeldungen
    'Name oder Gym suchen' => 'Search name or gym',
    'alle Status'          => 'all statuses',
    'alle Kategorien'      => 'all categories',
    'alle Gyms'            => 'all gyms',
    'CSV-Export'           => 'CSV export',
    'Wiegen'               => 'Weigh-in',
    'Setz.'                => 'Seed',
    'Anmerkung des Gyms'   => 'Note from the gym',
    'Wiegegewicht – Enter speichert' => 'Weigh-in weight – press Enter to save',
    'Setzposition (1 = topgesetzt)'  => 'Seed (1 = top seed)',
    'Startgeld bezahlt'    => 'Entry fee paid',
    'Anmeldung löschen?'   => 'Delete entry?',
    'Keine Anmeldungen für diesen Filter.' => 'No entries for this filter.',
    'Keine Anmeldungen.'   => 'No entries.',
    'Ausgewählte:'         => 'Selected:',
    'bestätigen'           => 'confirm',
    'ablehnen'             => 'reject',
    'abmelden'             => 'withdraw',
    'löschen'              => 'delete',
    'Ausgewählte Anmeldungen löschen?' => 'Delete selected entries?',
    'Sportler manuell anmelden' => 'Enter athlete manually',
    'Für Galas oder wenn ein Gym nicht selbst anmeldet. Der Sportler muss bei einem Gym angelegt sein – <a href="%s">neuen Sportler anlegen</a>.'
        => 'For galas or when a gym does not enter its athletes itself. The athlete must be registered with a gym – <a href="%s">create a new athlete</a>.',
    'Name eingeben …'      => 'Type a name …',
    'Aus der Liste wählen – die Nummer am Ende (#id) ordnet den Sportler zu.'
        => 'Choose from the list – the number at the end (#id) identifies the athlete.',

    // Meldungen: Anmeldungen
    'Bitte einen Sportler auswählen.' => 'Please select an athlete.',
    'Dieser Sportler ist in dieser Kategorie bereits angemeldet.' => 'This athlete is already entered in this category.',
    '%s angemeldet (bestätigt).' => '%s entered (confirmed).',
    'Anmeldung gespeichert.'     => 'Entry saved.',
    'Keine Anmeldungen ausgewählt.' => 'No entries selected.',
    '%d Anmeldung(en) gelöscht.' => 'Entries deleted: %d.',
    '%d Anmeldung(en) auf „%s“ gesetzt.' => 'Entries set to “%2$s”: %1$d.',
    'Startgeld als bezahlt markiert.' => 'Entry fee marked as paid.',
    'Unbekannte Aktion.'         => 'Unknown action.',
    'Anmeldung gelöscht.'        => 'Entry deleted.',
    'Anmeldung nicht gefunden.'  => 'Entry not found.',

    // CSV-Export
    'Zuname'           => 'Last name',
    'Vorname'          => 'First name',
    'Kampfname'        => 'Ring name',
    'Geburtsdatum'     => 'Date of birth',
    'Nation'           => 'Nationality',
    'Gewicht gemeldet' => 'Declared weight',
    'Wiegegewicht'     => 'Weigh-in weight',
    'Setzung'          => 'Seed',
    'Startgeld'        => 'Entry fee',
    'Bilanz'           => 'Record',
    'Anmerkung'        => 'Note',
    'ja'               => 'yes',
    'nein'             => 'no',
];
