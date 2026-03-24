# Mediapool Rename für REDAXO 5

Addon zur Umbenennung von Dateinamen im Medienpool. Benennt die physische Datei um und aktualisiert **automatisch alle Referenzen** in der gesamten Datenbank.

## Features

* Zusätzliches Meta-Info-Feld "Gewünschter Dateiname" in der Detailansicht einer Mediendatei
* Automatische Bereinigung des Dateinamens (Sonderzeichen, Umlaute etc.) via `rex_string::normalize()`
* Umbenennung der physischen Datei im Medienordner
* Automatische Aktualisierung aller Datenbank-Referenzen (Word-Boundary-basiert)
* Abbruch mit Fehlermeldung, wenn der gewünschte Dateiname bereits existiert
* Cache wird nach erfolgreicher Umbenennung automatisch geleert

## Was wird aktualisiert?

### Wird aktualisiert

| Referenz-Typ | Beispiel | Status |
|---|---|---|
| Direkte Dateinamen in VARCHAR/TEXT-Spalten | `bild.jpg` in `rex_article_slice`, `rex_media` | ✅ |
| Komma-separierte Medienlisten | `bild1.jpg,bild2.jpg` | ✅ |
| Alle Tabellen der Datenbank | Auch eigene Tabellen und YForm-Tabellen | ✅ |

### Wird noch nicht aktualisiert (geplant)

| Referenz-Typ | Beispiel | Status |
|---|---|---|
| TEXTAREA-Felder (HTML/Markdown) | `<img src="index.php?rex_media_type=...&rex_media_file=bild.jpg">` | ⏳ |
| be_media-Felder in YForm-Tabellendefinitionen | YForm-Feldtyp `be_media`, `be_medialist` | ⏳ |
| JSON-kodierte Referenzen | `{"image":"bild.jpg"}` | ⏳ |
| Serialisierte PHP-Daten | `s:8:"bild.jpg"` | ⏳ |
| Media-Manager-Cache | Gecachte Bilder mit altem Dateinamen | ⏳ |

## Installation

1. Im REDAXO-Installer nach `mediapool_rename` suchen und installieren
2. Das Addon aktivieren
3. Fertig – in der Detailansicht jeder Mediendatei erscheint das Feld "Gewünschter Dateiname"

## Verwendung

1. Im Medienpool eine Datei öffnen (Detailansicht)
2. Im Feld **"Gewünschter Dateiname (ohne Endung)"** den neuen Namen eingeben
3. Datei speichern
4. Die Datei wird umbenannt und alle Referenzen in der Datenbank aktualisiert

> **Hinweis:** Die Dateiendung wird automatisch beibehalten. Nur der Name vor der Endung wird ersetzt.

> **Achtung:** Erstelle vor der Umbenennung ein Backup. Die Umbenennung kann nicht rückgängig gemacht werden.

## Voraussetzungen

- REDAXO >= 5.18.3
- PHP >= 8.3
- Addon `mediapool` >= 2.3.0
- Addon `metainfo` >= 2.6.0

## Lizenz

MIT License, siehe [LICENCE](LICENCE)

## Autor

**Alexander Walther** | [alex plus de](https://www.alexplus.de)  
Basierend auf *explorit new media* von Tobias Daeschner