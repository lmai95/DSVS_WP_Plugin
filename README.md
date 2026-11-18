# DSVS WP Plugin

WordPress-Plugin für Tabellen, aktuelle Ergebnisse und kommende Spiele des Dart Sport Verband Schwaben. Die Daten werden serverseitig von den öffentlichen DSVS-Spielplanseiten gelesen.

## Installation

1. `dsvs-wp-plugin-0.3.3.zip` in WordPress unter **Plugins → Installieren → Plugin hochladen** auswählen.
2. Plugin installieren und aktivieren.
3. In Elementor ein **Shortcode-Widget** einfügen.
4. Einen der Shortcodes unten eintragen.

## Shortcodes

```text
[dsv_tabelle liga="C2"]
[dsv_aktuelle_spiele liga="C2"]
[dsv_kommende_spiele liga="C2" anzahl="6"]
```

Ohne `liga` wird C2 verwendet.

## Liga-Kürzel

| Kürzel | Wettbewerb |
|---|---|
| `OL` | Oberliga |
| `A` / `AL` | A-Liga |
| `B` | B-Liga |
| `C1` | C1-Liga |
| `C2` | C2-Liga |
| `LC` | Liga Cup |
| `CLVH` | C-Liga Vergleichskampf Hinrunde |
| `CLVR` | C-Liga Vergleichskampf Rückrunde |

## Elementor-Farben

Das Plugin verwendet die globalen Elementor-Farben automatisch:

- **UiCore Primary** (`--e-global-color-uicore_primary`): Ligaüberschrift
- **UiCore Secondary** (`--e-global-color-uicore_secondary`): Tabellenkopf
- **UiCore Body** (`--e-global-color-uicore_body`): Texte
- **UiCore Accent** (`--e-global-color-uicore_accent`): Markierung für TSV Böbingen I und TSV Böbingen II

Die Farben werden im UiCore/Elementor Style Kit unter **Theme Style → Globale Farben** gepflegt. Das Plugin verwendet zuerst die UiCore-Variablen, danach Standard-Elementor-Variablen und erst zuletzt feste Fallback-Werte.

## Aktualisierung

- Aktuelle Ergebnisse: Cache ca. 60 Sekunden
- Tabelle: Cache ca. 10 Minuten
- Spielplan: Cache ca. 30 Minuten

Die Live-Ansicht fragt den WordPress-REST-Endpunkt ab. WordPress greift wegen des serverseitigen Caches nicht mit jedem Browser-Poll erneut auf DSVS zu.

## Sicherheit

- externe Requests über `wp_safe_remote_get()`
- maximale Response-Größe 1 MiB
- Ligawerte im REST-Endpunkt werden gegen die feste Liga-Liste validiert
- externe Inhalte werden vor der HTML-Ausgabe escaped
- keine API-Schlüssel oder Passwörter
- keine eigenen Datenbanktabellen

## Voraussetzungen

- WordPress
- PHP mit `DOMDocument` (PHP-DOM)
- HTTPS-Zugriff des Servers auf `dsv-schwaben.de`
- Elementor optional

## Saisonwechsel

Die URLs sind derzeit für 2026/2027 hinterlegt. Für eine neue Saison muss die zentrale Liga-Konfiguration in `includes/class-dsvs-leagues.php` angepasst werden.

## Entwicklung

Version 0.3.4 ist funktional zu 0.3.2 ausgerichtet, aber der Code wurde lesbarer strukturiert:

- sprechende Variablennamen
- kleine Methoden mit klarer Aufgabe
- formatierter PHP-, CSS- und JavaScript-Code
- zentrale Liga-Konfiguration
- Kommentare nur an Stellen, an denen sie die Wartbarkeit verbessern

## Lizenz

GPL-2.0-or-later.
