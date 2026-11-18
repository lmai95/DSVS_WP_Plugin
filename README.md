# DSVS WP Plugin

WordPress-Plugin für die C2-Liga des Dart Sport Verband Schwaben.

## Version 0.2.0
- Beide Teams `TSV Böbingen I` und `TSV Böbingen II` werden hervorgehoben.
- Ligatabelle, aktuelle Ergebnisse, kommende Spiele und Teamspiele.
- Aktuelle Ergebnisse werden über die WordPress REST API alle 60 Sekunden aktualisiert.
- Der Browser fragt nur WordPress ab; WordPress ruft den DSVS dank Cache höchstens einmal pro Minute ab.
- Getrennte Caches: Tabelle 10 Minuten, Spielplan 30 Minuten, aktuell 60 Sekunden.
- Letzter erfolgreicher Abruf dient bei einem temporären DSVS-Ausfall als Fallback.

## Installation
Die ZIP-Datei über **Plugins > Installieren > Plugin hochladen** installieren und aktivieren.

## Shortcodes
```
[dsv_tabelle]
[dsv_aktuelle_spiele]
[dsv_aktuelle_spiele team="TSV Böbingen I" polling="true"]
[dsv_kommende_spiele anzahl="4"]
[dsv_kommende_spiele team="TSV Böbingen II" anzahl="6"]
[dsv_team_spiele team="TSV Böbingen I" anzahl="10"]
```

## Hinweise
"Aktuell" entspricht den Daten, die der DSVS auf der Quellseite veröffentlicht. Das Plugin erzeugt keine eigenen Zwischenstände. PHP benötigt die DOM-Erweiterung (`DOMDocument`).

## Lizenz
GPL-2.0-or-later.
