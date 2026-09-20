# Event141 – Plattform-API

Event141 ist die zentrale Plattform: Event-Websites, Anzeigetafeln, Apps und
Gym141 greifen per API zu. Alle Antworten sind JSON, CORS ist offen (`*`).

## Authentifizierung

Schlüssel (`ek_…`) werden unter **Verwaltung → API & Kopplungen** erzeugt und nur
einmal im Klartext angezeigt (gespeichert wird der SHA-256-Hash).

    Authorization: Bearer ek_…        (oder: X-Api-Key: ek_…)

| Schlüsselart | Rechte |
|---|---|
| Plattform | alle Events lesen (auch Entwürfe); mit `write` Kämpfe steuern |
| Event | wie Plattform, aber auf ein Event beschränkt |
| Gym | nur `/gym/…`: eigene Sportler, Anmeldungen, Ergebnisse. Gyms erzeugen ihn auch selbst im Gym-Bereich unter „Gym141“ |

Ohne Schlüssel sind **veröffentlichte** Events lesbar:
`/api/events`, `/api/event/{slug}`, `/api/event/{slug}/live`, `/api/event/{slug}/fightcard`.

## Endpunkte (`/api/v1`)

| Methode | Pfad | Zweck |
|---|---|---|
| GET | `/ping` | Schlüssel prüfen |
| GET | `/events` | Eventliste |
| GET | `/event/{slug}` | Event komplett: Tage, Abschnitte, Ringe, Kategorien, Sponsoren, Kämpfe mit berechneten Zeiten |
| GET | `/event/{slug}/live` | nur Status/Ergebnisse/Zeiten (klein, zum Pollen) |
| GET | `/event/{slug}/fightcard` | Fightcard im **NAFN-Format** (`fights.json`) |
| POST | `/event/{slug}/bout/{id}` | Kampf steuern (scope `write`) |

`POST …/bout/{id}` – Body:

    {"action": "live"}                                   Kampf läuft
    {"action": "result", "winner": "red|blue|draw|none",
     "method": "KO", "round": "2", "note": "1:12"}       Ergebnis (Sieger rückt im Turnierbaum auf)
    {"action": "reset"}                                  Ergebnis zurücknehmen
    {"action": "cancel"}                                 absagen

### Gym-Schlüssel (Gegenrichtung der Gym141-Kopplung)

| Methode | Pfad | Zweck |
|---|---|---|
| GET | `/gym` | eigenes Gym |
| GET | `/gym/events` | Events mit offener Anmeldung, Kategorien, eigene Anmeldungen |
| GET | `/gym/athletes` | eigene Sportler |
| POST | `/gym/athletes` | Sportler abgleichen (Upsert über `external_ref`, sonst Name) |
| POST | `/gym/event/{slug}/entries` | Sportler anmelden |
| POST | `/gym/entry/{id}/withdraw` | abmelden (solange die Anmeldung offen ist) |
| GET | `/gym/results` | Kämpfe der eigenen Sportler mit `outcome` win/loss/draw – z. B. für „Erfolge“ in Gym141 |

    POST /gym/athletes
    {"athletes": [{"external_ref": "gym141:17", "first_name": "Anna", "last_name": "Berger",
                   "birthdate": "1998-07-25", "gender": "w", "weight": 58.5}]}

    POST /gym/event/steirische-meisterschaft-2026/entries
    {"entries": [{"external_ref": "gym141:17", "category_id": 3, "note": "optional"}]}

## Webhooks

Unter **API & Kopplungen → Webhooks** eine Adresse hinterlegen. Bei jeder
Änderung an einem Event (Kämpfe, Status, Ergebnisse, Anmeldungen, Stammdaten)
sendet Event141 nach dem Absenden der eigenen Antwort:

    POST <url>
    X-Event141-Signature: sha256=<HMAC-SHA256(body, secret)>
    {"event": "nafn-4", "event_id": 3, "type": "event.changed", "at": "2026-09-20T18:55:51+00:00"}

Der Empfänger prüft die Signatur und holt sich den frischen Stand über die API.

## Kopplung einer bestehenden Event-Website (nafn.at)

Die NAFN-Website liest `data/fights.json`. Mit der Kopplung kommt diese Datei
aus Event141 – das Frontend bleibt unverändert:

1. Event in Event141 anlegen oder **Events → Fightcard importieren** (holt
   `fights.json`, Kämpfer-Pool, Fotos und Videos von der Website).
2. API-Schlüssel erzeugen (Lesen, auf das Event beschränkt).
3. Webhook `https://nafn.at/admin/event141-hook.php` für das Event anlegen.
4. Auf der Website `admin/event141.config.example.php` nach
   `admin/event141.config.php` kopieren: Adresse, Event-Kürzel, Schlüssel, Secret.

Ab dann: Kämpfe, Live-Status und Ergebnisse in Event141 pflegen (Ringansicht) –
die Website ist Sekunden später aktuell. Im NAFN-Admin gibt es zusätzlich den
Knopf „Jetzt mit Event141 abgleichen“.
