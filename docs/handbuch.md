# Kurzanleitung für die Verwaltung

Zum Weitergeben an Organisation und Ringleitung.
Die Verwaltung ist erreichbar unter **deine-domain.tld/admin**, der Bereich für
Gyms und Vereine unter **deine-domain.tld/gym**.

---

## Anmelden

Benutzername und Startpasswort bekommst du vom Admin. Passwort vergessen? Der
Admin setzt es unter *Benutzer* zurück und gibt dir ein neues Startpasswort.

---

## Was du siehst

| Rolle | Umfang |
|---|---|
| Ringleitung | Ringansicht: Kämpfe starten, Ergebnisse eintragen |
| Organisation | alle Events, Anmeldungen, Kämpfe, Zeitplan, Sportler und Gyms |
| Admin (Superuser) | alles – zusätzlich Benutzer, Seiten, API, Einstellungen, Updates |

---

## Ein Event anlegen

*Events → Neues Event.* Pflicht sind Name, Datum und die **Art**:

* **Gala** (Fight Night): die Kämpfe werden einzeln zusammengestellt – mit
  Pausen, Showblöcken, Titelkampf und Fightcard auf der Website.
* **Turnier**: Sportler melden sich in Kategorien an, daraus entsteht ein
  K.-o.-Turnierbaum, der auf Tage, Abschnitte und Ringe verteilt wird.

Die Art lässt sich später umstellen; bereits angelegte Kämpfe bleiben erhalten.

Im Formular stehen außerdem Veranstaltungsort (mit Karte auf der Website),
Kontakt und Tickets, die Regeln für die **Anmeldung durch Gyms** (Zeitraum,
Startgeld), die Website-Texte, Bilder (Poster, Titelbild, Titelgürtel) und
Social-Media-Links.

**Status** (Knopfleiste oben): *Entwurf → Anmeldung offen → Anmeldung
geschlossen → Läuft → Beendet.* Erst mit „Anmeldung offen“ sehen Gyms das
Event im Gym-Bereich; „Veröffentlicht“ schaltet die Website frei.

> Gratis-Umfang: ein aktives Event (alles außer „Beendet“). Mit einer
> Pro-Lizenz sind es beliebig viele – siehe *Einstellungen → Lizenz*.

---

## Aufbau (Turnier und mehrtägige Events)

Unter *Aufbau* legst du **Tage**, je Tag **Abschnitte** (Vormittag – Vorrunden,
Abend – Finals …) und die **Wettkampfstätten** (Ring 1, Ring 2, Matte A …) an.
Eine Gala braucht meist nur einen Tag mit einem Abschnitt und einem Ring – das
wird beim Anlegen automatisch vorbereitet.

---

## Kategorien

*Kategorien* gibt es nur beim Turnier: Disziplin, Geschlecht, Alters- und
Gewichtsbereich, Runden und Rundenlänge. „Kategorien kopieren“ übernimmt die
Liste eines früheren Events.

---

## Anmeldungen

Gyms melden ihre Sportler im Gym-Bereich selbst an; die Verwaltung kann unter
*Anmeldungen* ebenfalls Sportler eintragen. Je Zeile: **bestätigen** oder
**ablehnen**, Gewicht beim Wiegen erfassen, Setzposition vergeben, Startgeld
als bezahlt markieren. Mehrere Zeilen ankreuzen und unten die **Sammelaktion**
wählen. *CSV-Export* liefert die aktuelle Liste für Excel.

Bei Alter oder Gewicht außerhalb der Kategorie erscheint ein Hinweis – die
Anmeldung ist trotzdem möglich.

---

## Kämpfe

**Gala:** unter *Kämpfe* jeden Kampf anlegen – rote und blaue Ecke aus den
Sportlern wählen, Disziplin, Gewichtsklasse, Runden, Block (Main Card,
Prelims …), Titel (z. B. „Hauptkampf“), Gürtel-Bezeichnung beim Titelkampf,
**Story** (Text unter dem Kampf, auf der Website als „Story lesen“ aufklappbar)
und ob die Bilanz angezeigt wird. **Pausen** und Showblöcke sind eigene
Einträge. Reihenfolge per Pfeil; die Kampfnummern zählen von unten (erster
Kampf des Abends = 1, Hauptkampf = höchste Nummer).

**Turnier:** je Kategorie *Turnierbaum erzeugen* – aus den bestätigten
Anmeldungen entsteht der K.-o.-Baum mit Setzliste und Freilosen. Sieger rücken
nach dem Ergebnis automatisch in die nächste Runde.

**Inaktiv** setzt einen Kampf ab (Absage): er verschwindet von Website und
Zeitplan, die Daten bleiben – jederzeit wieder aktivierbar.

---

## Zeitplan und Beginnzeiten

*Zeitplan*: Kämpfe auf Abschnitte und Ringe **verteilen** (automatisch reihum
oder einzeln), Reihenfolge festlegen, **nummerieren**. Die voraussichtlichen
Beginnzeiten ergeben sich aus der Startzeit des Abschnitts und der Dauer je
Kampf/Pause.

**Live-Modus** (Knopf auf der Event-Seite): ab jetzt zählen die echten
Start- und Endzeiten. Ein Kampf, der überzieht, schiebt alle folgenden Zeiten
nach hinten – auf der Website und in der Ringansicht.

---

## Ringansicht (Kampfrichtertisch)

*Ring 1, Ring 2 …* öffnet die Ansicht für ein Gerät am Ring: großer nächster
Kampf, **Kampf starten**, dann **Sieger Rot / Sieger Blau / Unentschieden** mit
Siegart (K.o., Punkte, Aufgabe …) und Runde. Die Website zeigt sofort LIVE bzw.
das Ergebnis. Ein Ergebnis lässt sich unter *Kämpfe* wieder öffnen.

---

## Website

Jedes Event hat eine Website unter `/e/<kürzel>`: Übersicht mit Countdown und
Live-Leiste, Fightcard bzw. Turnierplan, Zeitplan, Teilnehmer je Gym,
Ergebnisse. Unter *Einstellungen → Startseite* kann ein Event die komplette
Startseite der Installation sein (z. B. als Seite einer Fight Night).

**Fightcard:** Hauptkampf als große Karte (bei Titelkampf mit Gürtel und
Sternen), Bilanz und Alter als Chips, Sieger mit Lorbeerkranz und goldenem
„Winner“-Band über dem Foto, Story aufklappbar. Die Seite aktualisiert sich
alle 45 Sekunden von selbst.

**Fotos und Videos:** beim Sportler Foto und optional ein kurzes Video
hochladen; Modus *Foto*, *Video in Schleife* oder *Foto ↔ Video im Wechsel*
(Foto-Dauer 1–5 s). Beide Ecken eines Kampfs laufen synchron.

**Sponsoren:** unter *Sponsoren* Logos hochladen (Hauptsponsor groß).

---

## Ticketverkauf mit Ticket141

Tickets verkauft das Schwesterprodukt **Ticket141** (Ticketshop mit
Stripe-Zahlung, QR-Tickets und Einlass-Scanner). Event141 ist damit gekoppelt
(Pro-Funktion):

1. **Einrichten** (Admin): in Ticket141 unter *API & Webhooks* einen Schlüssel
   mit Rechten „write“ erzeugen. In Event141 unter *Einstellungen → Ticket141*
   Adresse der Ticket141-Instanz und Schlüssel eintragen, *Speichern &
   Verbindung prüfen*. Der Schlüssel wird nach dem Speichern nicht mehr
   angezeigt; das leere Feld lässt ihn unverändert.
2. **Event koppeln**: in der Event-Übersicht (Karte *Ticketverkauf*) auf
   **In Ticket141 anlegen**. Ticket141 bekommt Name, Untertitel, Datum, Beginn
   (erste Abschnittszeit, sonst Beginnzeit, sonst 19:00), Einlass, Location und
   den Link zur Event-Seite; das Kürzel des Ticket141-Events landet im
   Event-Formular (*Ticket141-Event*). Das neue Event ist dort ein **Entwurf**:
   Kategorien mit Preisen und Kontingent anlegen und den Verkauf starten.
   Ändern sich später Datum oder Ort, überträgt **Ticket141-Stammdaten
   aktualisieren** die Daten noch einmal (Preise bleiben unangetastet).
   Ein bereits in Ticket141 bestehendes Event lässt sich auch von Hand
   koppeln: dessen Kürzel ins Feld *Ticket141-Event* eintragen.
3. **Auf der Event-Seite** erscheinen unter *Tickets sichern* die Kategorien
   aus Ticket141 mit Preis und Verfügbarkeit („nur noch 12“, „ausverkauft“)
   und der Knopf **Tickets kaufen** zum Shop. Mit der Einstellung *Shop als
   Widget einbetten* läuft der Kauf direkt auf der Event-Seite. Die Daten
   werden fünf Minuten zwischengespeichert; ist Ticket141 gerade nicht
   erreichbar, gilt der letzte Stand bzw. nur der Knopf (dann mit den manuell
   eingetragenen Ticketpreisen als Ersatz).
4. **Verkaufszahlen**: die Karte *Ticketverkauf* zeigt verkaufte Tickets,
   Umsatz, Einlass und offene Bestellungen samt Link zur Ticket141-Verwaltung.
   Die Lese-API (`/api/v1/event/{kürzel}`) liefert die Shop-Adresse als
   `tickets_url`, damit eine eigene Event-Website darauf verlinken kann.

Ohne Ticket141 bleiben die manuellen *Ticketpreise* und der *Ticket-Link* im
Event-Formular wie gehabt.

---

## Bilder, Galerien und Bericht

**Bildbibliothek** (*Medien (Bilder)* im Menü): Fotos vom Event auf einmal
hochladen – sie werden am Server verkleinert (max. 1800 px), Handyfotos
richtig gedreht, eine Vorschau wird angelegt. Beim Hochladen gibst du **Tags**
an (mit Komma, z. B. „nafn 3, hauptkampf, reiser“) und optional einen
Bildtext. Suche und Tag-Wolke finden jedes Bild wieder; mit Mehrfachauswahl
ergänzt oder entfernst du Tags oder löschst Bilder (mit Nachfrage – die
Bilder verschwinden dann auch aus Kämpfen, Galerien und dem Bericht).

Im Event unter dem Reiter **Galerie & Bericht**:

* **Event-Bericht** – Überschrift, Text (Leerzeile = Absatz, `## ` am
  Zeilenanfang = Zwischenüberschrift), Titelbild und Bilder aus der
  Bibliothek. Mit *Bericht auf der Website anzeigen* erscheint er als Teaser
  auf der Event-Seite, als eigener Reiter und unter `/e/<event>/bericht`.
* **Galerien** – benannte Bildersammlungen („Impressionen“, „Backstage“ …)
  mit eigener Seite `/e/<event>/galerie/<kürzel>`. Bilder auswählen: erst
  nach Tag filtern, anhaken, unten die Reihenfolge ziehen, ★ setzt das
  Titelbild. Sobald eine Galerie sichtbar ist, bekommt die Event-Seite den
  Reiter *Galerie*.
* **Je Kampf** – auf der Kampfseite (Fightcard → Kampf) gibt es das Feld
  **Nachwort** („Nach dem Kampf“) und darunter **Bilder zum Kampf**. Beides
  erscheint auf der Kampf-Detailseite unter Ergebnis und Story und wird in
  der Fightcard-API mitgeliefert (`epilog`, `gallery`), sodass eine gekoppelte
  Event-Website es übernehmen kann.

---

## Listen und Druck

*Listen* erzeugt druckfertige Seiten: **Fightcard-Aushang** (Kabinen,
Kampfgericht), **Kämpfer-Checkliste** (Waage, Musik, ärztliche Untersuchung) und
**Kabineneinteilung** mit Türschildern – mit Warnung, wenn Gegner in derselben
Kabine landen.

---

## Sportler und Gyms

*Sportler*: Name, Kampfname, Gym, Geburtsdatum (oder Alter), Bilanz, Bio,
Foto/Video. *Gyms & Vereine*: die registrierten Gyms mit ihren Sportlern und
Anmeldungen; ein Gym lässt sich hier auch ohne eigenen Zugang anlegen.

**Gym141:** Ein Gym, das seine Mitglieder in Gym141 verwaltet, verbindet im
Gym-Bereich unter *Gym141* einmal seine Instanz und übernimmt Mitglieder als
Sportler – ohne Abtippen. Umgekehrt kann Gym141 mit einem **Gym-API-Schlüssel**
(Gym-Bereich → API-Schlüssel) Sportler abgleichen, zu Events anmelden und
Ergebnisse abholen.

---

## Import einer bestehenden Fightcard

*Events → Fightcard importieren*: eine `fights.json` im NAFN-Format (per
Adresse oder Upload) wird samt Kämpfer-Pool, Fotos und Videos übernommen.
„Ersetzen“ baut die Kämpfe eines bestehenden Events neu auf; die Kampf-IDs der
Quelle bleiben erhalten, damit geteilte Links weiter funktionieren.

---

## API und Kopplungen (Admin)

*API & Kopplungen*: **API-Schlüssel** (Lesen oder Schreiben; für die ganze
Plattform oder nur ein Event) und **Webhooks** (werden bei jeder Änderung an
einem Event aufgerufen, HMAC-signiert). Damit holt sich z. B. eine bestehende
Event-Website die Fightcard live aus Event141. Der Schlüssel wird nur einmal
angezeigt. Details in `docs/api.md`.

---

## Für den Admin

* **Benutzer** – Konten anlegen, Rolle vergeben, Passwörter zurücksetzen.
* **Seiten** – Impressum, Datenschutz und weitere Seiten.
* **Einstellungen** – Veranstalterdaten, Startseite, Siegarten, Lizenzschlüssel
  (*Lizenz jetzt prüfen*), Ticket141-Kopplung (Adresse, Schlüssel, Widget).
* **Updates** – *Nach Updates suchen* fragt den Update-Server ab; *Update jetzt
  installieren* sichert vorher die Datenbank und ersetzt nur die
  Anwendungsdateien. Eigene Daten, Fotos und Videos bleiben unangetastet.
* **Protokoll** – wer hat wann was geändert.

---

## Testumgebung

Unter einer **dev.**-Subdomain (oder lokal) läuft dieselbe Anwendung mit
eigener Datenbank; ein gelber Balken zeigt das an. Änderungen dort erscheinen
**nicht** auf der Echtseite, und umgekehrt.
