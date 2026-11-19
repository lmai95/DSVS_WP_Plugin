# DSVS WP Plugin

WordPress-Plugin zur Einbindung von Tabellen, aktuellen Ergebnissen, kommenden Spielen und Spielerranglisten des Dart Sport Verband Schwaben (DSVS).

## Funktionen

- mehrere DSVS-Ligen und Wettbewerbe
- Ligatabellen
- aktuelle Ergebnisse mit regelmäßigem Live-Refresh
- kommende Spiele der konfigurierten eigenen Mannschaften
- Spielerrangliste „Begegnungen“
- frei konfigurierbarer Verein und beliebig viele eigene Mannschaften
- UiCore-/Elementor-Farben mit Fallbacks
- serverseitiger WordPress-Cache
- abgesicherte externe Requests

## Installation

1. `dsvs-wp-plugin-0.4.6.zip` unter **Plugins → Installieren → Plugin hochladen** installieren.
2. Plugin aktivieren.
3. Unter **Einstellungen → DSVS Liga** Verein, eigene Mannschaften und Saison konfigurieren.
4. In Elementor ein Shortcode-Widget oder in WordPress einen Shortcode-Block einfügen.

## Einstellungen

### Vereinsname
Frei wählbarer Name des Vereins.

### Mannschaften hervorheben
Eine exakte DSVS-Mannschaftsbezeichnung pro Zeile. Diese Liste steuert:

- farbliche Hervorhebung in Tabellen und Ranglisten
- Auswahl der kommenden eigenen Spiele
- Option `eigene="true"` der Spielerrangliste

### Saison
Format `2026/2027`. Die Liga-IDs müssen bei einem Saisonwechsel weiterhin geprüft werden.

## Shortcodes

### Ligatabelle

```text
[dsv_tabelle liga="C2"]
```

### Aktuelle Ergebnisse

```text
[dsv_aktuelle_spiele liga="C2"]
```

### Kommende eigene Spiele

```text
[dsv_kommende_spiele liga="C2" anzahl="6"]
```

### Spielerrangliste

```text
[dsv_rangliste liga="C2"]
```

Ohne weitere Parameter zeigt die Rangliste **Top 20 plus alle Spieler der konfigurierten eigenen Mannschaften**.

#### Parameter `anzahl`

Bestimmt, wie viele Plätze vom Anfang der Rangliste angezeigt werden.

```text
[dsv_rangliste liga="C2" anzahl="10"]
[dsv_rangliste liga="C2" anzahl="20"]
[dsv_rangliste liga="C2" anzahl="30"]
```

`anzahl="0"` zeigt die vollständige Rangliste:

```text
[dsv_rangliste liga="C2" anzahl="0"]
```

#### Parameter `eigene`

Mit `eigene="true"` werden zusätzlich zu den Top-N-Plätzen alle Spieler der unter **Einstellungen → DSVS Liga** hinterlegten Mannschaften angezeigt, auch wenn deren Rang außerhalb von `anzahl` liegt. Das ist der Standard.

```text
[dsv_rangliste liga="C2" anzahl="20" eigene="true"]
```

Nur exakt die ersten 20 Plätze:

```text
[dsv_rangliste liga="C2" anzahl="20" eigene="false"]
```

Die Ausgabe enthält **Rang**, **Spieler / Mannschaft**, **Spiele**, **Siege**, **Niederlagen** und **Siegquote**.

## Unterstützte Liga-Kürzel

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

Nicht jeder Wettbewerb muss eine klassische Tabelle oder Spielerrangliste anbieten.

## Styling

Das Plugin bevorzugt UiCore-/Elementor-Variablen:

- `uicore_primary`: Überschriften
- `uicore_secondary`: Tabellenkopf
- `uicore_body`: Text
- `uicore_accent`: Hervorhebung eigener Mannschaften und Spieler

Falls UiCore nicht vorhanden ist, werden Standard-Elementor-Variablen und anschließend sichere Fallback-Farben verwendet.

## Cache

- aktuelle Ergebnisse: ca. 60 Sekunden
- Tabellen und Ranglisten: ca. 10 Minuten
- Spielplan: ca. 30 Minuten

## Sicherheit

- externe Requests über `wp_safe_remote_get()`
- Response-Größe auf 1 MiB begrenzt
- REST-Liga-Parameter gegen die konfigurierte Liga-Liste validiert
- externe Inhalte vor der Ausgabe escaped
- keine API-Schlüssel oder Passwörter
- keine eigenen Datenbanktabellen

## Voraussetzungen

- WordPress
- PHP mit `DOMDocument` / PHP-DOM
- HTTPS-Zugriff auf `dsv-schwaben.de`
- Elementor/UiCore optional

## Lizenz

GPL-2.0-or-later.
