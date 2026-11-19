<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Robust DOM parser for DSVS tables and fixtures.
 */
final class DSVS_Parser {
    private static function xpath( $html ) {
        if ( ! class_exists( 'DOMDocument' ) ) { return false; }

        $doc = new DOMDocument();
        $old = libxml_use_internal_errors( true );
        $ok = $doc->loadHTML( '<?xml encoding="UTF-8">' . $html );
        libxml_clear_errors();
        libxml_use_internal_errors( $old );

        return $ok ? new DOMXPath( $doc ) : false;
    }

    private static function clean( $text ) {
        return trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
    }

    private static function cells( DOMXPath $xpath, DOMNode $row ) {
        $cells = array();
        foreach ( $xpath->query( './/th|.//td', $row ) as $cell ) {
            $value = self::clean( $cell->textContent );
            if ( '' !== $value ) { $cells[] = $value; }
        }
        return $cells;
    }

    public static function get_standings( $html ) {
        $xpath = self::xpath( $html );
        if ( ! $xpath ) { return array(); }

        foreach ( $xpath->query( '//table' ) as $table ) {
            $text = self::clean( $table->textContent );
            if ( false === stripos( $text, 'Mannschaft' ) || false === stripos( $text, 'Punkte' ) ) { continue; }

            $rows = array();
            foreach ( $xpath->query( './/tr', $table ) as $row ) {
                $cells = self::cells( $xpath, $row );
                if ( count( $cells ) >= 9 && ctype_digit( ltrim( $cells[0], '0123456789 ' ) ) ) {
                    $rows[] = array_slice( $cells, 0, 9 );
                }
            }
            if ( $rows ) { return $rows; }
        }
        return array();
    }

    public static function get_current_games( $html ) {
        return self::games( $html, true );
    }

    public static function get_schedule( $html ) {
        $games = self::games( $html, false );
        usort( $games, function ( $a, $b ) { return $a['timestamp'] <=> $b['timestamp']; } );
        return $games;
    }

    private static function games( $html, $current_only ) {
        $xpath = self::xpath( $html );
        if ( ! $xpath ) { return array(); }

        $games = array();
        foreach ( $xpath->query( '//table' ) as $table ) {
            $table_text = self::clean( $table->textContent );
            $is_current = ( false !== stripos( $table_text, 'AKTUELLE ERGEBNISSE' ) );

            if ( $current_only !== $is_current ) { continue; }

            $header_map = array();
            $rows = $xpath->query( './/tr', $table );
            foreach ( $rows as $row_index => $row ) {
                $raw_cells = $xpath->query( './/th|.//td', $row );
                $cells = self::cells( $xpath, $row );

                if ( $row_index === 0 || $header_map === array() ) {
                    foreach ( $cells as $index => $heading ) {
                        $heading = mb_strtolower( self::clean( $heading ), 'UTF-8' );
                        if ( false !== mb_stripos( $heading, 'datum', 0, 'UTF-8' ) ) { $header_map['date'] = $index; }
                        elseif ( false !== mb_stripos( $heading, 'zeit', 0, 'UTF-8' ) ) { $header_map['time'] = $index; }
                        elseif ( false !== mb_stripos( $heading, 'heim', 0, 'UTF-8' ) ) { $header_map['home'] = $index; }
                        elseif ( false !== mb_stripos( $heading, 'gast', 0, 'UTF-8' ) ) { $header_map['away'] = $index; }
                        elseif ( false !== mb_stripos( $heading, 'ergebnis', 0, 'UTF-8' ) || false !== mb_stripos( $heading, 'spiele', 0, 'UTF-8' ) ) { $header_map['result'] = $index; }
                    }
                    if ( $header_map ) { continue; }
                }

                $game = self::parse_game_row( $cells, $header_map );
                if ( $game ) { $games[] = $game; }
            }
        }

        return $games;
    }

    private static function parse_game_row( $cells, $header_map ) {
        $date_index = isset( $header_map['date'] ) ? $header_map['date'] : false;
        $time_index = isset( $header_map['time'] ) ? $header_map['time'] : false;

        // Header-based parsing is used for current DSVS pages.
        if ( false !== $date_index ) {
            if ( ! isset( $cells[ $date_index ] ) ) { return false; }
            if ( ! preg_match( '/(\d{2}\.(?:\d{2})\.\d{4})/', $cells[ $date_index ], $matches ) ) { return false; }
            $date = $matches[1];
            $time = false !== $time_index && isset( $cells[ $time_index ] ) ? $cells[ $time_index ] : '19:30';
            if ( ! preg_match( '/\d{1,2}:\d{2}/', $time, $time_matches ) ) { return false; }
            $time = $time_matches[0];

            $home = isset( $header_map['home'] ) && isset( $cells[ $header_map['home'] ] ) ? $cells[ $header_map['home'] ] : '';
            $away = isset( $header_map['away'] ) && isset( $cells[ $header_map['away'] ] ) ? $cells[ $header_map['away'] ] : '';
            $result = isset( $header_map['result'] ) && isset( $cells[ $header_map['result'] ] ) ? $cells[ $header_map['result'] ] : ':';

            if ( '' === $home || '' === $away ) { return false; }
            $date_time = DateTime::createFromFormat( '!d.m.Y H:i', $date . ' ' . $time, wp_timezone() );
            if ( ! $date_time ) { return false; }

            return array(
                'date' => $date,
                'time' => $time,
                'timestamp' => $date_time->getTimestamp(),
                'home' => $home,
                'result' => $result,
                'away' => $away,
            );
        }

        // Compatibility fallback for pages without a DSVS-style header.
        foreach ( $cells as $index => $cell ) {
            if ( preg_match( '/(\d{2}\.\d{2}\.\d{4}).*?(\d{1,2}:\d{2})/u', $cell, $match ) ) {
                $date = $match[1];
                $time = $match[2];
                $rest = array_slice( $cells, $index + 1 );
                if ( count( $rest ) >= 2 ) {
                    $date_time = DateTime::createFromFormat( '!d.m.Y H:i', $date . ' ' . $time, wp_timezone() );
                    if ( $date_time ) {
                        return array(
                            'date' => $date,
                            'time' => $time,
                            'timestamp' => $date_time->getTimestamp(),
                            'home' => $rest[0],
                            'result' => count( $rest ) > 2 ? $rest[1] : ':',
                            'away' => end( $rest ),
                        );
                    }
                }
            }
        }
        return false;
    }

    public static function get_ranking( $html ) {
        $xpath = self::xpath( $html );
        if ( ! $xpath ) { return array(); }

        foreach ( $xpath->query( '//table' ) as $table ) {
            $table_text = self::clean( $table->textContent );
            if ( false === stripos( $table_text, 'Rangliste' ) || false === stripos( $table_text, 'Begegnungen' ) ) { continue; }

            $ranking = array();
            foreach ( $xpath->query( './/tr', $table ) as $row ) {
                $cells = array();
                foreach ( $xpath->query( './/td', $row ) as $cell ) { $cells[] = $cell; }
                if ( 7 !== count( $cells ) ) { continue; }

                $rank = self::clean( $cells[0]->textContent );
                $played = self::clean( $cells[3]->textContent );
                $won = self::clean( $cells[4]->textContent );
                $lost = self::clean( $cells[5]->textContent );
                $quote = self::clean( $cells[6]->textContent );

                if ( ! preg_match( '/^\d+$/', $rank ) || ! preg_match( '/^\d+$/', $played ) || ! preg_match( '/^\d+$/', $won ) || ! preg_match( '/^\d+$/', $lost ) || false === strpos( $quote, '%' ) ) { continue; }

                $identity = self::parse_ranking_identity( $xpath, $cells[2] );
                $ranking[] = array(
                    'rank' => $rank,
                    'player' => $identity['player'],
                    'team' => $identity['team'],
                    'played' => $played,
                    'won' => $won,
                    'lost' => $lost,
                    'quote' => $quote,
                );
            }
            if ( $ranking ) { return $ranking; }
        }
        return array();
    }

    private static function parse_ranking_identity( DOMXPath $xpath, DOMNode $cell ) {
        $player_link = $xpath->query( './/a[contains(@href,"verein-mitglied-statistiken.php")]', $cell )->item( 0 );
        $player = $player_link ? self::clean( $player_link->textContent ) : '';
        $team = '';
        $seen_break = false;

        foreach ( $cell->childNodes as $node ) {
            if ( XML_ELEMENT_NODE === $node->nodeType && 'br' === strtolower( $node->nodeName ) ) { $seen_break = true; continue; }
            if ( $seen_break && XML_TEXT_NODE === $node->nodeType ) {
                $candidate = self::clean( $node->textContent );
                if ( '' !== $candidate ) { $team = $candidate; break; }
            }
        }

        if ( '' === $team ) {
            $full = self::clean( $cell->textContent );
            if ( '' !== $player && 0 === mb_stripos( $full, $player, 0, 'UTF-8' ) ) {
                $team = trim( mb_substr( $full, mb_strlen( $player, 'UTF-8'), null, 'UTF-8') );
            }
        }
        return array( 'player' => $player, 'team' => $team );
    }

    public static function filter_ranking( $ranking, $limit = 20, $include_own = true ) {
        if ( 0 === $limit ) { return $ranking; }
        $visible = array_slice( $ranking, 0, $limit );
        if ( ! $include_own ) { return $visible; }

        $included_ranks = array();
        foreach ( $visible as $entry ) { $included_ranks[ (string) $entry['rank'] ] = true; }
        foreach ( $ranking as $entry ) {
            if ( DSVS_Settings::is_highlighted_team( $entry['team'] ) && ! isset( $included_ranks[ (string) $entry['rank'] ] ) ) {
                $visible[] = $entry;
                $included_ranks[ (string) $entry['rank'] ] = true;
            }
        }
        usort( $visible, function ( $a, $b ) { return (int) $a['rank'] <=> (int) $b['rank']; } );
        return $visible;
    }

    public static function get_upcoming_highlighted_games( $games, $limit ) {
        $now = current_time( 'timestamp' );
        $games = array_values( array_filter( $games, function ( $game ) use ( $now ) {
            return $game['timestamp'] >= $now &&
                ( DSVS_Settings::is_highlighted_team( $game['home'] ) || DSVS_Settings::is_highlighted_team( $game['away'] ) );
        } ) );
        return array_slice( $games, 0, $limit );
    }
}
