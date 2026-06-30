# Joystream FM – Installationsanleitung

## 1. Was sich geändert hat
- Vorher: Admin-Daten (About-Texte, Social-Links, Partner, Kontakt, Login) lagen nur in `localStorage`
  des jeweiligen Browsers → jeder Besucher sah etwas anderes.
- Jetzt: Alles liegt zentral in einer MySQL-Datenbank auf deinem Server. Jeder Besucher lädt beim
  Öffnen der Seite (`loadAllData()`) automatisch die aktuellen Inhalte vom Server.
- Neu: Ein "Musikvideo"-Bereich, der automatisch ein passendes YouTube-Video zum aktuell laufenden
  Song sucht (per öffentlicher Invidious-Suche, ohne dass du einen YouTube API-Key brauchst) und
  eingebettet anzeigen kann.

## 2. Dateien hochladen
Lade folgende Dateien auf deinen Server unter `panel.joystream-fm.de` (gleicher Ordner wie deine
bestehenden `tracklist.php`, `gruwu.php` etc.):

- `db.php`
- `api.php`
- `setup.php`
- `schema.sql` (nur zum Import, nicht hochladen müssen, reicht auch lokal)

`index.html` ersetzt deine bisherige Startseite (auf `www.joystream-fm.de`).

## 3. Datenbank einrichten
1. Lege in deinem Hosting-Panel eine MySQL-Datenbank an (z.B. `joystream_fm`).
2. Importiere `schema.sql` in diese Datenbank (z.B. über phpMyAdmin → Importieren).
3. Trage in `db.php` deine echten Zugangsdaten ein (`$DB_HOST`, `$DB_NAME`, `$DB_USER`, `$DB_PASS`).

## 4. Ersten Admin-Account anlegen
1. Rufe einmalig `https://panel.joystream-fm.de/setup.php` im Browser auf.
   → Legt den Account `admin` / `joystream2025` mit korrekt gehashtem Passwort an.
2. **Logge dich danach sofort im Admin-Panel ein und ändere das Passwort** (Tab "🔑 Passwort").
3. Lösche `setup.php` vom Server (Sicherheit – sonst könnte jemand sie erneut aufrufen).

## 5. CORS prüfen
In `api.php` steht oben eine Liste erlaubter Domains (`$allowedOrigins`). Falls deine Seite unter
einer anderen Domain/Subdomain läuft, dort ergänzen.

## 6. Musikvideo-Feature
Nutzt öffentliche Invidious-Instanzen, um ohne API-Key nach "Künstler + Songtitel" auf YouTube zu
suchen und das gefundene Video einzubetten. Das ist kostenlos, aber:
- Öffentliche Invidious-Instanzen können gelegentlich offline/instabil sein (deshalb sind 3 Stück
  als Fallback hinterlegt in `INVIDIOUS_INSTANCES` in `index.html`).
- Für zuverlässigere Treffer (genauere Suche, weniger Ausfälle) wäre später ein eigener YouTube
  Data API Key sinnvoll – sag Bescheid, falls du das nachrüsten willst, das ist nur eine kleine
  Anpassung in der `fetchMusicVideo()`-Funktion.
- Besucher müssen aktiv auf "Anzeigen" klicken, das Video startet nicht automatisch (besser für
  Ladezeit & UX, falls gewünscht kann ich das ändern).

## 7. Was du im Admin-Panel jetzt zusätzlich hast
- Passwort ändern verlangt jetzt zur Sicherheit das aktuelle Passwort (vorher konnte man es ohne
  Bestätigung ändern).
- Alle Änderungen (About, Social, Partner, Kontakt) werden direkt an den Server gesendet und sind
  augenblicklich für alle Besucher sichtbar – kein Browser-Cache-Problem mehr.

## Mögliche nächste Ausbaustufen (sag Bescheid, wenn gewünscht)
- Mehrere Admin-Accounts mit Rollen (z.B. nur DJs dürfen Tracklist bearbeiten)
- Bild-Upload für Partner-Logos statt Emoji-Platzhalter
- Push-Benachrichtigung/Toast statt `alert()` bei Fehlern
- Automatisches Caching der Musikvideo-Suche in der DB, damit nicht jeder Besucher einzeln sucht
