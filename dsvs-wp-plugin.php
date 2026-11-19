<?php
/**
 * Plugin Name: DSVS WP Plugin
 * Description: Tabellen sowie aktuelle und kommende Spiele mehrerer DSVS-Ligen.
 * Version: 0.4.7
 * Author: Lukas Maier
 * License: GPL-2.0-or-later
 * Text Domain: dsvs-wp-plugin
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'DSVS_VERSION', '0.4.7' );
define( 'DSVS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DSVS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DSVS_LIVE_CACHE_SECONDS', 60 );
define( 'DSVS_TABLE_CACHE_SECONDS', 10 * MINUTE_IN_SECONDS );
define( 'DSVS_SCHEDULE_CACHE_SECONDS', 30 * MINUTE_IN_SECONDS );

require_once DSVS_PLUGIN_DIR . 'includes/class-dsvs-settings.php';
require_once DSVS_PLUGIN_DIR . 'includes/class-dsvs-leagues.php';
require_once DSVS_PLUGIN_DIR . 'includes/class-dsvs-fetcher.php';
require_once DSVS_PLUGIN_DIR . 'includes/class-dsvs-parser.php';
require_once DSVS_PLUGIN_DIR . 'includes/class-dsvs-renderer.php';

DSVS_Settings::init();

function dsvs_enqueue_assets( $enable_live_updates = false ) {
    wp_enqueue_style( 'dsvs-style', DSVS_PLUGIN_URL . 'assets/css/dsvs.css', array(), DSVS_VERSION );
    if ( ! $enable_live_updates ) { return; }
    wp_enqueue_script( 'dsvs-live', DSVS_PLUGIN_URL . 'assets/js/dsvs-live.js', array(), DSVS_VERSION, true );
    wp_localize_script(
        'dsvs-live',
        'dsvsLive',
        array(
            'endpoint'     => esc_url_raw( rest_url( 'dsvs/v1/current' ) ),
            'intervalMs'   => 60000,
            'errorMessage' => __( 'DSVS-Ergebnisse konnten nicht aktualisiert werden.', 'dsvs-wp-plugin' ),
        )
    );
}

function dsvs_get_league_data( $league_key, $data_type = 'schedule' ) {
    $league = DSVS_Leagues::get( $league_key );
    if ( ! $league ) { return new WP_Error( 'dsvs_unknown_league', 'Unbekannte Liga.' ); }

    $cache_seconds = DSVS_SCHEDULE_CACHE_SECONDS;
    if ( 'live' === $data_type ) {
        $cache_seconds = DSVS_LIVE_CACHE_SECONDS;
    } elseif ( 'table' === $data_type ) {
        $cache_seconds = DSVS_TABLE_CACHE_SECONDS;
    }

    $html = DSVS_Fetcher::get( $league['url'], $cache_seconds, $data_type . '_' . $league['key'] );
    if ( is_wp_error( $html ) ) { return $html; }

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
    $data = dsvs_get_league_data( $attributes['liga'], 'table' );
    return is_wp_error( $data ) ? DSVS_Renderer::error() : DSVS_Renderer::standings( $data['standings'], $data['league'] );
}
add_shortcode( 'dsv_tabelle', 'dsvs_table_shortcode' );

function dsvs_current_games_shortcode( $attributes ) {
    $attributes = shortcode_atts( array( 'liga' => 'C2' ), $attributes, 'dsv_aktuelle_spiele' );
    dsvs_enqueue_assets( true );
    $data = dsvs_get_league_data( $attributes['liga'], 'live' );
    return is_wp_error( $data )
        ? DSVS_Renderer::error()
        : DSVS_Renderer::games( $data['current'], $data['league'], 'Aktuelle Ergebnisse', 'dsvs-live-' . strtolower( $data['league']['key'] ), true );
}
add_shortcode( 'dsv_aktuelle_spiele', 'dsvs_current_games_shortcode' );

function dsvs_upcoming_games_shortcode( $attributes ) {
    $attributes = shortcode_atts( array( 'liga' => 'C2', 'anzahl' => 4 ), $attributes, 'dsv_kommende_spiele' );
    dsvs_enqueue_assets();
    $data = dsvs_get_league_data( $attributes['liga'], 'schedule' );
    if ( is_wp_error( $data ) ) { return DSVS_Renderer::error(); }
    $games = DSVS_Parser::get_upcoming_highlighted_games( $data['schedule'], max( 1, min( 50, absint( $attributes['anzahl'] ) ) ) );
    return DSVS_Renderer::games( $games, $data['league'], 'Kommende Spiele' );
}
add_shortcode( 'dsv_kommende_spiele', 'dsvs_upcoming_games_shortcode' );

/** Build the DSVS ranking URL belonging to a configured league. */
function dsvs_get_ranking_url( $league ) {
    return sprintf(
        'https://dsv-schwaben.de/sb_ligarangliste.php?p=209--%s-%d-0',
        DSVS_Settings::season(),
        absint( $league['id'] )
    );
}

/** Shortcode: [dsv_rangliste liga="C2"] */
function dsvs_ranking_shortcode( $attributes ) {
    $attributes = shortcode_atts(
        array( 'liga' => 'C2', 'anzahl' => 20, 'eigene' => 'true' ),
        $attributes,
        'dsv_rangliste'
    );
    dsvs_enqueue_assets();

    $league = DSVS_Leagues::get( $attributes['liga'] );
    if ( ! $league ) { return DSVS_Renderer::error(); }

    $html = DSVS_Fetcher::get(
        dsvs_get_ranking_url( $league ),
        DSVS_TABLE_CACHE_SECONDS,
        'ranking_' . $league['key']
    );
    if ( is_wp_error( $html ) ) { return DSVS_Renderer::error(); }

    $ranking = DSVS_Parser::get_ranking( $html );
    $limit = absint( $attributes['anzahl'] );
    // Zero retains the documented "show all" behavior; positive limits are capped.
    if ( 0 !== $limit ) { $limit = min( 100, $limit ); }
    $include_own = filter_var( $attributes['eigene'], FILTER_VALIDATE_BOOLEAN );
    $ranking = DSVS_Parser::filter_ranking( $ranking, $limit, $include_own );

    return DSVS_Renderer::ranking( $ranking, $league );
}
add_shortcode( 'dsv_rangliste', 'dsvs_ranking_shortcode' );

function dsvs_validate_rest_league( $value ) {
    return false !== DSVS_Leagues::get( $value );
}

/** Apply a modest per-IP ceiling to the unauthenticated live-data route. */
function dsvs_rest_live_rate_limit() {
    $remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    if ( '' === $remote_addr ) { $remote_addr = 'unknown'; }

    // Do not trust X-Forwarded-For: clients can spoof it unless a trusted proxy normalizes it.
    $key = 'dsvs_live_rl_' . substr( wp_hash( $remote_addr ), 0, 32 );
    $count = (int) get_transient( $key );
    if ( $count >= 120 ) {
        return new WP_Error(
            'dsvs_rate_limited',
            __( 'Zu viele Aktualisierungsanfragen. Bitte später erneut versuchen.', 'dsvs-wp-plugin' ),
            array( 'status' => 429, 'retry_after' => 60 )
        );
    }
    set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
    return true;
}

function dsvs_register_rest_routes() {
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
add_action( 'rest_api_init', 'dsvs_register_rest_routes' );

function dsvs_rest_current_games( WP_REST_Request $request ) {
    $rate_limit = dsvs_rest_live_rate_limit();
    if ( is_wp_error( $rate_limit ) ) {
        $response = new WP_REST_Response( array( 'message' => $rate_limit->get_error_message() ), 429 );
        $response->header( 'Retry-After', '60' );
        $response->header( 'Cache-Control', 'no-store, private' );
        return $response;
    }

    $data = dsvs_get_league_data( $request->get_param( 'liga' ), 'live' );
    if ( is_wp_error( $data ) ) {
        $response = new WP_REST_Response( array( 'message' => 'DSVS data unavailable.' ), 503 );
        $response->header( 'Cache-Control', 'no-store, private' );
        return $response;
    }

    $response = rest_ensure_response(
        array(
            'html' => DSVS_Renderer::games(
                $data['current'],
                $data['league'],
                'Aktuelle Ergebnisse',
                'dsvs-live-' . strtolower( $data['league']['key'] ),
                true
            ),
        )
    );
    $response->header( 'Cache-Control', 'no-store, private' );
    return $response;
}
