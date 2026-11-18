# DSVS WP Plugin

WordPress-Plugin für mehrere Ligen des Dart Sport Verband Schwaben, Saison 2026/2027.

## Version 0.3.1
- Sicherheits-/Robustheitsupdate: `wp_safe_remote_get()`, 1-MiB-Response-Limit, DSVS-HTML-Plausibilitätsprüfung und REST-Liga-Whitelist
- Multi-Liga-Unterstützung: `OL`, `A`, `B`, `C1`, `C2`, `LC`, `CLVH`, `CLVR`
- Liga und Ansicht werden automatisch als Überschrift ausgegeben
- Kombinierte Übersicht der nächsten Spiele von TSV Böbingen I und II
- Beide Böbinger Mannschaften werden hervorgehoben
- Live-Aktualisierung aktueller Ergebnisse alle 60 Sekunden über WordPress REST API
- Serverseitige Caches und Fallback auf den letzten erfolgreichen Abruf

## Installation
ZIP über **Plugins > Installieren > Plugin hochladen** installieren. Eine vorhandene Version kann ersetzt werden.

## Shortcodes
```
[dsv_tabelle liga="C2"]
[dsv_tabelle liga="OL"]
[dsv_aktuelle_spiele liga="C2"]
[dsv_aktuelle_spiele liga="C1" polling="true"]
[dsv_kommende_spiele liga="LC" anzahl="10"]
[dsv_kommende_spiele liga="C2" team="TSV Böbingen II" anzahl="6"]
[dsv_team_spiele liga="C2" team="TSV Böbingen I" anzahl="10"]
[dsv_boebingen anzahl="8"]
```
Mit `titel="false"` lassen sich Plugin-Überschriften unterdrücken.

## Liga-Kürzel
| Kürzel | Wettbewerb |
|---|---|
| OL | Oberliga |
| A oder AL | A-Liga |
| B | B-Liga |
| C1 | C1-Liga |
| C2 | C2-Liga |
| LC | Liga Cup |
| CLVH | C-Liga Vergleichskampf Hinrunde |
| CLVR | C-Liga Vergleichskampf Rückrunde |

Cup und Vergleichskämpfe haben möglicherweise keine klassische Tabelle. In diesem Fall zeigt `[dsv_tabelle]` einen Hinweis.

## Anforderungen
WordPress, PHP 7.4 oder neuer und PHP-DOM (`DOMDocument`).

## Lizenz
GPL-2.0-or-later.
