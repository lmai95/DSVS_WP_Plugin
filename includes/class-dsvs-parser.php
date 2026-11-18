<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Converts the DSVS HTML tables into simple PHP arrays used by the renderer. */
final class DSVS_Parser {
    public static function get_standings( $html ) {
        $xpath = self::create_xpath( $html );
        if ( ! $xpath ) {
            return array();
        }

        foreach ( $xpath->query( '//table' ) as $table ) {
            $table_text = self::clean_text( $table->textContent );
            if ( false === stripos( $table_text, 'Mannschaft' ) || false === stripos( $table_text, 'Partien' ) ) {
                continue;
            }

            $standings = array();
            foreach ( $xpath->query( './/tr', $table ) as $row ) {
                $cells = self::get_row_cells( $xpath, $row );
                if ( count( $cells ) >= 9 && ctype_digit( $cells[0] ) ) {
                    $standings[] = array_slice( $cells, 0, 9 );
                }
            }

            if ( ! empty( $standings ) ) {
                return $standings;
            }
        }

        return array();
    }

    public static function get_current_games( $html ) {
        return self::get_games_from_tables( $html, true );
    }

    public static function get_schedule( $html ) {
        $games = self::get_games_from_tables( $html, false );
        usort( $games, array( __CLASS__, 'sort_games_by_time' ) );
        return $games;
    }

    public static function get_upcoming_boebingen_games( $games, $limit ) {
        $now = current_time( 'timestamp' );

        $upcoming_games = array_filter(
            $games,
            function ( $game ) use ( $now ) {
                $is_future = $game['timestamp'] >= $now;
                $is_open = ':' === $game['result'];
                $involves_boebingen = self::is_boebingen_team( $game['home'] ) || self::is_boebingen_team( $game['away'] );
                return $is_future && $is_open && $involves_boebingen;
            }
        );

        return array_slice( array_values( $upcoming_games ), 0, $limit );
    }

    public static function is_boebingen_team( $team_name ) {
        foreach ( DSVS_BOEBINGEN_TEAMS as $boebingen_team ) {
            if ( 0 === strcasecmp( trim( $team_name ), trim( $boebingen_team ) ) ) {
                return true;
            }
        }
        return false;
    }

    public static function sort_games_by_time( $first_game, $second_game ) {
        return $first_game['timestamp'] <=> $second_game['timestamp'];
    }

    private static function create_xpath( $html ) {
        if ( ! class_exists( 'DOMDocument' ) ) {
            return false;
        }

        $document = new DOMDocument();
        $previous_setting = libxml_use_internal_errors( true );
        $loaded = $document->loadHTML( '<?xml encoding="UTF-8">' . $html );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous_setting );

        return $loaded ? new DOMXPath( $document ) : false;
    }

    private static function get_games_from_tables( $html, $current_results_only ) {
        $xpath = self::create_xpath( $html );
        if ( ! $xpath ) {
            return array();
        }

        $games = array();
        foreach ( $xpath->query( '//table' ) as $table ) {
            $is_current_results_table = false !== stripos( $table->textContent, 'AKTUELLE ERGEBNISSE' );
            if ( $current_results_only !== $is_current_results_table ) {
                continue;
            }

            foreach ( $xpath->query( './/tr', $table ) as $row ) {
                $game = self::parse_game_row( self::get_row_cells( $xpath, $row ) );
                if ( $game ) {
                    $games[] = $game;
                }
            }
        }

        return $games;
    }

    private static function parse_game_row( $cells ) {
        $date_index = false;
        $date = '';
        $time = '';

        foreach ( $cells as $index => $cell ) {
            if ( preg_match( '/(\d{2}\.\d{2}\.\d{4}).*?(\d{2}:\d{2})/u', $cell, $match ) ) {
                $date_index = $index;
                $date = $match[1];
                $time = $match[2];
                break;
            }
        }

        if ( false === $date_index ) {
            return false;
        }

        $remaining_cells = array_slice( $cells, $date_index + 1 );
        foreach ( $remaining_cells as $result_index => $value ) {
            if ( ! preg_match( '/^(?:\d+\s*:\s*\d+|:)$/', $value ) ) {
                continue;
            }

            if ( $result_index < 1 || ! isset( $remaining_cells[ $result_index + 1 ] ) ) {
                return false;
            }

            $date_time = DateTime::createFromFormat( '!d.m.Y H:i', $date . ' ' . $time, wp_timezone() );
            if ( ! $date_time ) {
                return false;
            }

            return array(
                'date'      => $date,
                'time'      => $time,
                'timestamp' => $date_time->getTimestamp(),
                'home'      => $remaining_cells[ $result_index - 1 ],
                'result'    => preg_replace( '/\s+/', '', $value ),
                'away'      => $remaining_cells[ $result_index + 1 ],
            );
        }

        return false;
    }

    private static function get_row_cells( DOMXPath $xpath, $row ) {
        $cells = array();
        foreach ( $xpath->query( './th|./td', $row ) as $cell ) {
            $value = self::clean_text( $cell->textContent );
            if ( '' !== $value ) {
                $cells[] = $value;
            }
        }
        return $cells;
    }

    private static function clean_text( $text ) {
        return trim( preg_replace( '/\s+/u', ' ', $text ) );
    }
}
