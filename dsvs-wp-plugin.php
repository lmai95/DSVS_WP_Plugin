<?php
/**
 * Plugin Name: DSVS WP Plugin
 * Description: Tabellen sowie aktuelle und kommende Spiele mehrerer DSVS-Ligen.
 * Version: 0.4.4
 * Author: Lukas Maier
 * License: GPL-2.0-or-later
 * Text Domain: dsvs-wp-plugin
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'DSVS_VERSION', '0.4.4' );
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
    wp_localize_script( 'dsvs-live', 'dsvsLive', array( 'endpoint'=>esc_url_raw( rest_url( 'dsvs/v1/current' ) ), 'intervalMs'=>60000, 'errorMessage'=>__( 'DSVS-Ergebnisse konnten nicht aktualisiert werden.', 'dsvs-wp-plugin' ) ) );
}
function dsvs_get_league_data( $league_key, $data_type = 'schedule' ) {
    $league = DSVS_Leagues::get( $league_key );
    if ( ! $league ) { return new WP_Error( 'dsvs_unknown_league', 'Unbekannte Liga.' ); }
    $cache_seconds = DSVS_SCHEDULE_CACHE_SECONDS;
    if ( 'live' === $data_type ) { $cache_seconds = DSVS_LIVE_CACHE_SECONDS; }
    elseif ( 'table' === $data_type ) { $cache_seconds = DSVS_TABLE_CACHE_SECONDS; }
    $html = DSVS_Fetcher::get( $league['url'], $cache_seconds, $data_type . '_' . $league['key'] );
    if ( is_wp_error( $html ) ) { return $html; }
    return array( 'league'=>$league, 'standings'=>DSVS_Parser::get_standings($html), 'current'=>DSVS_Parser::get_current_games($html), 'schedule'=>DSVS_Parser::get_schedule($html) );
}
function dsvs_table_shortcode( $attributes ) {
    $attributes=shortcode_atts(array('liga'=>'C2'),$attributes,'dsv_tabelle'); dsvs_enqueue_assets();
    $data=dsvs_get_league_data($attributes['liga'],'table');
    return is_wp_error($data) ? DSVS_Renderer::error() : DSVS_Renderer::standings($data['standings'],$data['league']);
}
add_shortcode('dsv_tabelle','dsvs_table_shortcode');
function dsvs_current_games_shortcode( $attributes ) {
    $attributes=shortcode_atts(array('liga'=>'C2'),$attributes,'dsv_aktuelle_spiele'); dsvs_enqueue_assets(true);
    $data=dsvs_get_league_data($attributes['liga'],'live');
    return is_wp_error($data) ? DSVS_Renderer::error() : DSVS_Renderer::games($data['current'],$data['league'],'Aktuelle Ergebnisse','dsvs-live-'.strtolower($data['league']['key']),true);
}
add_shortcode('dsv_aktuelle_spiele','dsvs_current_games_shortcode');
function dsvs_upcoming_games_shortcode( $attributes ) {
    $attributes=shortcode_atts(array('liga'=>'C2','anzahl'=>4),$attributes,'dsv_kommende_spiele'); dsvs_enqueue_assets();
    $data=dsvs_get_league_data($attributes['liga'],'schedule'); if(is_wp_error($data)){return DSVS_Renderer::error();}
    $games=DSVS_Parser::get_upcoming_highlighted_games($data['schedule'],max(1,min(50,absint($attributes['anzahl']))));
    return DSVS_Renderer::games($games,$data['league'],'Kommende Spiele');
}
add_shortcode('dsv_kommende_spiele','dsvs_upcoming_games_shortcode');

/** Build the DSVS ranking URL belonging to a configured league. */
function dsvs_get_ranking_url( $league ) {
    $season = DSVS_Settings::season();
    return sprintf(
        'https://dsv-schwaben.de/sb_ligarangliste.php?p=209--%s-%d-0',
        $season,
        absint( $league['id'] )
    );
}

/** Shortcode: [dsv_rangliste liga="C2"] */
function dsvs_ranking_shortcode( $attributes ) {
    $attributes = shortcode_atts( array( 'liga' => 'C2' ), $attributes, 'dsv_rangliste' );
    dsvs_enqueue_assets();

    $league = DSVS_Leagues::get( $attributes['liga'] );
    if ( ! $league ) {
        return DSVS_Renderer::error();
    }

    $ranking_url = dsvs_get_ranking_url( $league );
    $html = DSVS_Fetcher::get( $ranking_url, DSVS_TABLE_CACHE_SECONDS, 'ranking_' . $league['key'] );
    if ( is_wp_error( $html ) ) {
        return DSVS_Renderer::error();
    }

    $ranking = DSVS_Parser::get_ranking( $html );
    return DSVS_Renderer::ranking( $ranking, $league );
}
add_shortcode( 'dsv_rangliste', 'dsvs_ranking_shortcode' );

function dsvs_validate_rest_league($value){return false!==DSVS_Leagues::get($value);}
add_action('rest_api_init',function(){register_rest_route('dsvs/v1','/current',array('methods'=>WP_REST_Server::READABLE,'permission_callback'=>'__return_true','args'=>array('liga'=>array('default'=>'C2','sanitize_callback'=>'sanitize_text_field','validate_callback'=>'dsvs_validate_rest_league')),'callback'=>'dsvs_rest_current_games'));});
function dsvs_rest_current_games(WP_REST_Request $request){$data=dsvs_get_league_data($request->get_param('liga'),'live');if(is_wp_error($data)){return new WP_REST_Response(array('message'=>'DSVS data unavailable.'),503);}return rest_ensure_response(array('html'=>DSVS_Renderer::games($data['current'],$data['league'],'Aktuelle Ergebnisse','dsvs-live-'.strtolower($data['league']['key']),true)));}
