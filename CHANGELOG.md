# Changelog

## 0.4.4
- Ranglistenparser korrigiert: Spielername und Mannschaft werden anhand der DOM-/Zeilenstruktur getrennt
- Numerische Statistikwerte werden nicht mehr als Namensbestandteil interpretiert
- Generischer Fallback für konfigurierte Mannschaftsnamen bleibt erhalten
- README um eine ausführliche Anleitung für `[dsv_rangliste]` erweitert
- Bestehende Shortcodes und Einstellungen bleiben kompatibel

## 0.4.3
- Ranglistenüberschriften verständlicher ausgeschrieben: Spieler / Mannschaft, Spiele, Siege, Niederlagen und Siegquote

## 0.4.2
- Ranglistenparser korrigiert: Begegnungszahl wird nicht mehr doppelt unter Name/Mannschaft ausgegeben
- Spieler-/Mannschaftszelle DOM-basiert getrennt
- Team-Highlighting bleibt erhalten
- Alle Shortcodes aus 0.4.1 bleiben kompatibel

## 0.4.1
- Neuer Shortcode `[dsv_rangliste liga="C2"]`
- DSVS-Rangliste Begegnungen wird geparst und im bestehenden Design ausgegeben
- Spieler konfigurierter Mannschaften werden hervorgehoben
- Bestehende Shortcodes und Einstellungen bleiben erhalten

## 0.4.0
- Allgemeine Einstellungsseite unter Einstellungen → DSVS Liga
- Vereinsname, hervorgehobene Mannschaften und Saison konfigurierbar
- Böbingen-spezifische Parserlogik durch allgemeine Team-Hervorhebung ersetzt
- Alle bestehenden Shortcodes erhalten
- UiCore-/Elementor-Farben und Security-Härtungen erhalten
