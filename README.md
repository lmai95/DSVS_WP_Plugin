# DSVS WP Plugin

WordPress-Plugin zur automatischen Anzeige von Tabellen, aktuellen Ergebnissen und kommenden Spielen des Dart Sport Verband Schwaben (DSVS).

Das Plugin liest die öffentlich verfügbaren DSVS-Spielplandaten serverseitig ein und stellt sie über WordPress-Shortcodes bereit. TSV Böbingen I und TSV Böbingen II werden besonders markiert. Version 0.3.2 übernimmt außerdem automatisch die globalen Elementor-Farben.

## Funktionen

- Ligatabellen direkt aus dem DSVS-Spielplan
- Aktuelle Ergebnisse mit automatischer Aktualisierung
- Kommende Spiele von TSV Böbingen I und TSV Böbingen II
- Unterstützung mehrerer Wettbewerbe
- Hervorhebung der Böbinger Mannschaften
- Elementor Global Colors für Primary, Secondary, Text und Accent
- Fallback-Farben, wenn Elementor nicht verwendet wird
- Responsive Tabellen für Desktop und Smartphone
- WordPress-Cache zur Reduzierung der Zugriffe auf den DSVS-Server
- Abgesicherte externe Requests über `wp_safe_remote_get()`

## Unterstützte Wettbewerbe

| Kürzel | Wettbewerb |
|---|---|
| `OL` | Oberliga |
| `A` oder `AL` | A-Liga |
| `B` | B-Liga |
| `C1` | C1-Liga |
| `C2` | C2-Liga |
| `LC` | Liga Cup |
| `CLVH` | C-Liga Vergleichskampf (Hinrunde) |
| `CLVR` | C-Liga Vergleichskampf (Rückrunde) |

Die Saison und die DSVS-Quellen sind aktuell für **2026/2027** konfiguriert.

## Installation

### Installation über WordPress

1. Die ZIP-Datei `dsvs-wp-plugin-0.3.2.zip` herunterladen.
2. Im WordPress-Backend **Plugins → Installieren → Plugin hochladen** öffnen.
3. Die ZIP-Datei auswählen.
4. **Jetzt installieren** anklicken.
5. Anschließend das Plugin aktivieren.

Wenn bereits eine ältere Version installiert ist, kann WordPress anbieten, die vorhandene Version durch die neue Version zu ersetzen.

### Installation aus dem GitHub-Repository

Alternativ den Ordner `dsvs-wp-plugin` nach

```text
/wp-content/plugins/dsvs-wp-plugin/
```

kopieren und das Plugin anschließend im WordPress-Backend aktivieren.

## Verwendung in Elementor

In Elementor ein **Shortcode-Widget** an der gewünschten Position einfügen und dort einen der folgenden Shortcodes eintragen.

### Ligatabelle

```text
[dsv_tabelle liga="C2"]
```

Zeigt die Tabelle der C2-Liga an.

Weitere Beispiele:

```text
[dsv_tabelle liga="OL"]
[dsv_tabelle liga="A"]
[dsv_tabelle liga="B"]
[dsv_tabelle liga="C1"]
```

TSV Böbingen I und TSV Böbingen II werden automatisch hervorgehoben, wenn sie in der jeweiligen Tabelle vorkommen.

### Aktuelle Ergebnisse

```text
[dsv_aktuelle_spiele liga="C2"]
```

Zeigt die aktuell vom DSVS bereitgestellten Ergebnisse dieser Liga an.

Diese Ansicht wird im Browser regelmäßig über die WordPress REST API aktualisiert. Der DSVS-Server wird dabei durch einen serverseitigen Cache geschützt und nicht bei jedem Browser-Refresh direkt abgefragt.

Beispiele:

```text
[dsv_aktuelle_spiele liga="OL"]
[dsv_aktuelle_spiele liga="C1"]
[dsv_aktuelle_spiele liga="LC"]
```

### Kommende Spiele

```text
[dsv_kommende_spiele liga="C2" anzahl="4"]
```

Zeigt die nächsten vier noch offenen Spiele von TSV Böbingen I beziehungsweise TSV Böbingen II in der C2-Liga.

Die Anzahl kann angepasst werden:

```text
[dsv_kommende_spiele liga="C2" anzahl="8"]
```

## Empfohlener Aufbau der Darts-Seite

Für die TSV-Böbingen-Darts-Seite bietet sich beispielsweise folgende Reihenfolge an:

```text
[dsv_aktuelle_spiele liga="C2"]

[dsv_kommende_spiele liga="C2" anzahl="6"]

[dsv_tabelle liga="C2"]
```

Für den Liga Cup kann zusätzlich ein eigener Abschnitt verwendet werden:

```text
[dsv_aktuelle_spiele liga="LC"]
[dsv_kommende_spiele liga="LC" anzahl="6"]
```

## Elementor-Farben

Version 0.3.2 verwendet automatisch die globalen Elementor-Farbvariablen:

| Elementor-Farbe | Verwendung im Plugin |
|---|---|
| Primary | Ligaüberschrift |
| Secondary | Tabellenkopf |
| Text | Tabellen- und Beschreibungstext |
| Accent | Markierung von TSV Böbingen I und II |

Damit können die Farben zentral unter **Elementor → Website-Einstellungen → Globale Farben** geändert werden, ohne die Plugin-Dateien anzupassen.

Wenn Elementor nicht verfügbar ist, verwendet das Plugin eigene Fallback-Farben und bleibt funktionsfähig.

Die Schriftart wird nicht vom Plugin festgelegt. Sie wird vom aktiven WordPress-/Elementor-Theme geerbt.

## Aktualisierung und Cache

Das Plugin ruft die DSVS-Seiten nicht bei jedem Seitenaufruf neu ab.

Aktuell gelten ungefähr folgende Cache-Zeiten:

| Daten | Cache |
|---|---:|
| Aktuelle Ergebnisse | 60 Sekunden |
| Ligatabelle | 10 Minuten |
| Spielplan / kommende Spiele | 30 Minuten |

Dadurch können auch mehrere Besucher gleichzeitig die Vereinsseite öffnen, ohne den DSVS-Server unnötig stark zu belasten.

Wichtig: Das Plugin erzeugt keine eigenen Live-Ergebnisse. Es kann nur die Daten anzeigen, die der DSVS bereits auf seiner Website veröffentlicht hat.

## Sicherheit

Version 0.3.2 enthält die Sicherheitsmaßnahmen aus Version 0.3.1:

- externe Seiten werden mit `wp_safe_remote_get()` geladen
- maximale Antwortgröße ist begrenzt
- REST-Liga-Parameter werden gegen die im Plugin hinterlegte Liga-Liste geprüft
- aus dem DSVS gelesene Texte werden vor der HTML-Ausgabe escaped
- es werden keine Zugangsdaten oder API-Schlüssel gespeichert
- das Plugin benötigt keine eigenen Datenbanktabellen

## Voraussetzungen

- WordPress
- PHP mit `DOMDocument` / PHP-DOM
- ausgehende HTTPS-Verbindungen zu `dsv-schwaben.de`
- Elementor ist optional

## Fehlerbehebung

### Es werden keine Daten angezeigt

Prüfen:

1. Ist das Plugin aktiviert?
2. Ist der Shortcode korrekt geschrieben?
3. Ist das Liga-Kürzel gültig, zum Beispiel `C2` statt `C-2`?
4. Kann der WordPress-Server `https://dsv-schwaben.de` erreichen?
5. Ist PHP-DOM beziehungsweise `DOMDocument` auf dem Webserver verfügbar?

### Änderungen beim DSVS erscheinen nicht sofort

Das ist aufgrund des Caches normal. Aktuelle Ergebnisse können bis zu ungefähr 60 Sekunden, Tabellen bis zu ungefähr 10 Minuten und Spielplandaten bis zu ungefähr 30 Minuten verzögert sein.

### Die Farben passen nicht zur Website

Bei Elementor zuerst unter **Website-Einstellungen → Globale Farben** die Werte für Primary, Secondary, Text und Accent prüfen. Das Plugin verwendet diese Werte automatisch.

### Eine Liga zeigt keine Tabelle

Nicht jeder Wettbewerb besitzt zwangsläufig eine klassische Ligatabelle. Das betrifft insbesondere Cup- oder Vergleichswettbewerbe. Ergebnisse und Spielplan können trotzdem verfügbar sein.

## Saisonwechsel

Die DSVS-URLs sind derzeit auf die Saison **2026/2027** eingestellt. Beim Wechsel auf eine neue Saison müssen die DSVS-Quellen im Plugin angepasst beziehungsweise geprüft werden.

Langfristig ist eine konfigurierbare Saison sinnvoll, sodass für einen Saisonwechsel kein Quellcode geändert werden muss.

## Lizenz

GPL-2.0-or-later.
