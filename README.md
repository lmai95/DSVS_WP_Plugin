# DSVS WP Plugin

WordPress-Plugin zum Einbinden der C2-Ligatabelle des Dart Sport Verband Schwaben.

## Version 0.1.0
- Shortcode `[dsv_tabelle]`
- Abruf über WordPress HTTP API
- 15-Minuten-Transient-Cache
- Serverseitiges DOM-Parsing
- Hervorhebung von **TSV Böbingen II**
- Responsive Tabelle

## Installation
ZIP in WordPress unter **Plugins > Installieren > Plugin hochladen** installieren und aktivieren. Danach `[dsv_tabelle]` in einen Shortcode-Block einfügen.

## Anforderungen
WordPress mit PHP-DOM-Erweiterung (`DOMDocument`).

## Lizenz
GPL-2.0-or-later.
