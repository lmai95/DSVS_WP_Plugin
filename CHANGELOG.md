# Changelog

## 0.4.6
- `[dsv_rangliste]` um Parameter `anzahl` erweitert
- Standardmäßig werden die Top 20 Plätze angezeigt
- `anzahl="0"` zeigt die vollständige Rangliste
- Neuer Parameter `eigene`: bei `true` werden Spieler konfigurierter eigener Mannschaften zusätzlich zu den Top-N angezeigt
- `eigene="false"` begrenzt die Ausgabe strikt auf `anzahl`
- README vollständig aktualisiert und allgemein gehalten

## 0.4.5
- Ranglistenparser an die echte 7-spaltige DSVS-HTML-Struktur angepasst
- Logo-Spalte wird beim Parsing bewusst übersprungen
- Spielername wird aus dem DSVS-Statistik-Link gelesen
- Mannschaft wird aus dem Text nach dem Zeilenumbruch gelesen
- Spiele, Siege, Niederlagen und Siegquote werden direkt aus den Spalten 4 bis 7 übernommen

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
