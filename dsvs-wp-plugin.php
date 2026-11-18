<?php
/**
 * Plugin Name: DSVS WP Plugin
 * Description: Zeigt Tabellen sowie aktuelle und kommende Spiele mehrerer DSVS-Ligen an.
 * Version: 0.3.3
 * Author: Lukas Maier
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DSVS_VERSION', '0.3.3' );
define( 'DSVS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DSVS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'DSVS_LIVE_CACHE_SECONDS', 60 );
define( 'DSVS_TABLE_CACHE_SECONDS', 10 * MINUTE_IN_SECONDS );
define( 'DSVS_SCHEDULE_CACHE_SECONDS', 30 * MINUTE_IN_SECONDS );

define(
    'DSVS_BOEBINGEN_TEAMS',
    array(
        'TSV Böbingen I',
        'TSV Böbingen II',
    )
);

require_once DSVS_PLUGIN_DIR . 'includes/class-dsvs-leagues.php';
require_once DSVS_PLUGIN_DIR . 'includes/class-dsvs-fetcher.php';
require_once DSVS_PLUGIN_DIR . 'includes/class-dsvs-parser.php';
require_once DSVS_PLUGIN_DIR . 'includes/class-dsvs-renderer.php';

/** Enqueue the common stylesheet and, if requested, the live update script. */
function dsvs_enqueue_assets( $enable_live_updates = false ) {
    wp_enqueue_style(
        'dsvs-style',
        DSVS_PLUGIN_URL . 'assets/css/dsvs.css',
        array(),
        DSVS_VERSION
    );

    if ( ! $enable_live_updates ) {
        return;
    }

    wp_enqueue_script(
        'dsvs-live',
        DSVS_PLUGIN_URL . 'assets/js/dsvs-live.js',
        array(),
        DSVS_VERSION,
        true
    );

    wp_localize_script(
        'dsvs-live',
        'dsvsLive',
        array(
            'endpoint'       => esc_url_raw( rest_url( 'dsvs/v1/current' ) ),
            'intervalMs'     => 60000,
            'errorMessage'   => __( 'DSVS-Ergebnisse konnten nicht aktualisiert werden.', 'dsvs-wp-plugin' ),
        )
    );
}

/** Load and parse one league. */
function dsvs_get_league_data( $league_key, $data_type = 'schedule' ) {
    $league = DSVS_Leagues::get( $league_key );

    if ( ! $league ) {
        return new WP_Error( 'dsvs_unknown_league', 'Unbekannte Liga.' );
    }

    $cache_seconds = DSVS_SCHEDULE_CACHE_SECONDS;

    if ( 'live' === $data_type ) {
        $cache_seconds = DSVS_LIVE_CACHE_SECONDS;
    } elseif ( 'table' === $data_type ) {
        $cache_seconds = DSVS_TABLE_CACHE_SECONDS;
    }

    $html = DSVS_Fetcher::get(
        $league['url'],
        $cache_seconds,
        $data_type . '_' . $league['key']
    );

    if ( is_wp_error( $html ) ) {
        return $html;
    }

    return array(
        'league'    => $league,
        'standings' => DSVS_Parser::get_standings( $html ),
        'current'   => DSVS_Parser::get_current_games( $html ),
        'schedule'  => DSVS_Parser::get_schedule( $html ),
    );
}

function dsvs_table_shortcode( $attributes ) {
    $attributes = shortcode_atts( array( 'liga' => 'C2' ), $attributes, 'dsv_tabelle' );
    dsvs_enqueue_assets();

    $league_data = dsvs_get_league_data( $attributes['liga'], 'table' );

    if ( is_wp_error( $league_data ) ) {
        return DSVS_Renderer::error();
    }

    return DSVS_Renderer::standings( $league_data['standings'], $league_data['league'] );
}
add_shortcode( 'dsv_tabelle', 'dsvs_table_shortcode' );

function dsvs_current_games_shortcode( $attributes ) {
    $attributes = shortcode_atts( array( 'liga' => 'C2' ), $attributes, 'dsv_aktuelle_spiele' );
    dsvs_enqueue_assets( true );

    $league_data = dsvs_get_league_data( $attributes['liga'], 'live' );

    if ( is_wp_error( $league_data ) ) {
        return DSVS_Renderer::error();
    }

    return DSVS_Renderer::games(
        $league_data['current'],
        $league_data['league'],
        'Aktuelle Ergebnisse',
        'dsvs-live-' . strtolower( $league_data['league']['key'] ),
        true
    );
}
add_shortcode( 'dsv_aktuelle_spiele', 'dsvs_current_games_shortcode' );

function dsvs_upcoming_games_shortcode( $attributes ) {
    $attributes = shortcode_atts(
        array(
            'liga'   => 'C2',
            'anzahl' => 4,
        ),
        $attributes,
        'dsv_kommende_spiele'
    );

    dsvs_enqueue_assets();
    $league_data = dsvs_get_league_data( $attributes['liga'], 'schedule' );

    if ( is_wp_error( $league_data ) ) {
        return DSVS_Renderer::error();
    }

    $limit = max( 1, min( 50, absint( $attributes['anzahl'] ) ) );
    $upcoming_games = DSVS_Parser::get_upcoming_boebingen_games( $league_data['schedule'], $limit );

    return DSVS_Renderer::games( $upcoming_games, $league_data['league'], 'Kommende Spiele' );
}
add_shortcode( 'dsv_kommende_spiele', 'dsvs_upcoming_games_shortcode' );

/** Validate REST league names against the fixed league configuration. */
function dsvs_validate_rest_league( $value ) {
    return false !== DSVS_Leagues::get( $value );
}

add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'dsvs/v1',
            '/current',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'permission_callback' => '__return_true',
                'args'                => array(
                    'liga' => array(
                        'default'           => 'C2',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => 'dsvs_validate_rest_league',
                    ),
                ),
                'callback'            => 'dsvs_rest_current_games',
            )
        );
    }
);

function dsvs_rest_current_games( WP_REST_Request $request ) {
    $league_data = dsvs_get_league_data( $request->get_param( 'liga' ), 'live' );

    if ( is_wp_error( $league_data ) ) {
        return new WP_REST_Response( array( 'message' => 'DSVS data unavailable.' ), 503 );
    }

    $html = DSVS_Renderer::games(
        $league_data['current'],
        $league_data['league'],
        'Aktuelle Ergebnisse',
        'dsvs-live-' . strtolower( $league_data['league']['key'] ),
        true
    );

    return rest_ensure_response( array( 'html' => $html ) );
}
