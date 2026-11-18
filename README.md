# DSVS WP Plugin

Version 0.4.0 ergänzt eine allgemeine Einstellungsseite und erhält alle bestehenden Shortcodes.

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
```

## Farben
UiCore-Farben werden bevorzugt: `uicore_primary`, `uicore_secondary`, `uicore_body`, `uicore_accent`. Standard-Elementor-Variablen und sichere Fallbacks bleiben erhalten.

## Hinweis zur Saison
Die Saison wird aus den Einstellungen übernommen. Die Liga-IDs in `class-dsvs-leagues.php` müssen bei einer neuen DSVS-Saison geprüft werden.

## Lizenz
GPL-2.0-or-later.
