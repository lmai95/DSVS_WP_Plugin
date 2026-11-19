# DSVS WP Plugin

Version 0.4.4 ergänzt eine allgemeine Einstellungsseite und erhält alle bestehenden Shortcodes.

## Installation
1. ZIP unter **Plugins → Installieren → Plugin hochladen** installieren.
2. Plugin aktivieren.
3. Unter **Einstellungen → DSVS Liga** Verein, Mannschaften und Saison festlegen.
4. In Elementor ein Shortcode-Widget verwenden.

## Einstellungen
- **Vereinsname**: frei wählbar.
- **Mannschaften hervorheben**: eine exakte DSVS-Mannschaftsbezeichnung pro Zeile.
- **Saison**: Format `2026/2027`.

Standardwerte bleiben TSV Böbingen I und II, damit bestehende Installationen nach dem Update unverändert funktionieren.

## Bestehende Shortcodes
```text
[dsv_tabelle liga="C2"]
[dsv_aktuelle_spiele liga="C2"]
[dsv_kommende_spiele liga="C2" anzahl="6"]
[dsv_rangliste liga="C2"]
```

## Farben
UiCore-Farben werden bevorzugt: `uicore_primary`, `uicore_secondary`, `uicore_body`, `uicore_accent`. Standard-Elementor-Variablen und sichere Fallbacks bleiben erhalten.

## Hinweis zur Saison
Die Saison wird aus den Einstellungen übernommen. Die Liga-IDs in `class-dsvs-leagues.php` müssen bei einer neuen DSVS-Saison geprüft werden.

## Lizenz
GPL-2.0-or-later.

## Spielerrangliste

Mit dem Shortcode

```text
[dsv_rangliste liga="C2"]
```

wird die DSVS-Spielerrangliste **Begegnungen** der gewählten Liga eingebunden. Die Rangliste wird aus der zur Liga gehörenden DSVS-Ranglistenseite ausgelesen und verwendet dasselbe Design wie die übrigen Plugin-Tabellen.

Die Ausgabe enthält:

- **Rang**
- **Spieler / Mannschaft**
- **Spiele**
- **Siege**
- **Niederlagen**
- **Siegquote**

Spielername und Mannschaft werden getrennt dargestellt. Beispiel:

```text
Moritz Boger
TSV Böbingen I
```

Die Mannschaftszeile wird außerdem verwendet, um Einträge der unter **Einstellungen → DSVS Liga → Mannschaften hervorheben** konfigurierten Teams zu erkennen. Passende Spieler werden mit der UiCore-/Elementor-Accent-Farbe markiert. Dadurch funktioniert die Hervorhebung auch für andere Vereine, wenn dort deren exakte DSVS-Mannschaftsnamen eingetragen werden.

Weitere Beispiele:

```text
[dsv_rangliste liga="OL"]
[dsv_rangliste liga="A"]
[dsv_rangliste liga="B"]
[dsv_rangliste liga="C1"]
[dsv_rangliste liga="C2"]
```

Nicht jeder Wettbewerb muss eine entsprechende Spielerrangliste bereitstellen. Wenn keine passende Rangliste erkannt wird, zeigt das Plugin einen neutralen Hinweis statt einer leeren oder fehlerhaften Tabelle.

### Fehlerbehebung bei der Rangliste

Wenn Spieler oder Mannschaften nicht korrekt getrennt erscheinen:

1. WordPress-/Elementor-Cache leeren.
2. Browser-Cache aktualisieren.
3. Prüfen, ob tatsächlich Plugin-Version **0.4.4** aktiv ist.
4. Prüfen, ob sich die HTML-Struktur der DSVS-Ranglistenseite geändert hat.

Version 0.4.4 behandelt Spielername und Mannschaft anhand der Struktur der DSVS-Tabellenzelle getrennt. Reine Statistikwerte werden nicht als Bestandteil des Spielernamens übernommen.

## Lizenz

GPL-2.0-or-later.
