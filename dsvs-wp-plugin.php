<?php
/**
 * Plugin Name: DSVS WP Plugin
 * Description: Tabellen sowie aktuelle und kommende Spiele mehrerer DSVS-Ligen.
 * Version: 0.3.1
 * Author: Lukas Maier
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dsvs-wp-plugin
 */
if (!defined('ABSPATH')) exit;

define('DSVS_VERSION', '0.3.1');
define('DSVS_TEAMS', array('TSV Böbingen I', 'TSV Böbingen II'));
define('DSVS_TABLE_CACHE', 10 * MINUTE_IN_SECONDS);
define('DSVS_SCHEDULE_CACHE', 30 * MINUTE_IN_SECONDS);
define('DSVS_LIVE_CACHE', 60);
define('DSVS_DIR', plugin_dir_path(__FILE__));
define('DSVS_ASSETS', plugin_dir_url(__FILE__) . 'assets/');

require_once DSVS_DIR . 'includes/class-dsvs-leagues.php';
require_once DSVS_DIR . 'includes/class-dsvs-fetcher.php';
require_once DSVS_DIR . 'includes/class-dsvs-parser.php';
require_once DSVS_DIR . 'includes/class-dsvs-renderer.php';

function dsvs_assets($polling = false) {
    wp_enqueue_style('dsvs-style', DSVS_ASSETS . 'css/dsvs.css', array(), DSVS_VERSION);
    if ($polling) {
        wp_enqueue_script('dsvs-live', DSVS_ASSETS . 'js/dsvs-live.js', array(), DSVS_VERSION, true);
        wp_localize_script('dsvs-live', 'dsvsLive', array(
            'baseUrl' => esc_url_raw(rest_url('dsvs/v1/current')),
            'interval' => 60000,
            'error' => __('Aktualisierung derzeit nicht möglich.', 'dsvs-wp-plugin'),
        ));
    }
}

function dsvs_data($league_key, $mode) {
    $league = DSVS_Leagues::get($league_key);
    if (!$league) return new WP_Error('dsvs_league', 'Unbekannte Liga.');
    $ttl = $mode === 'live' ? DSVS_LIVE_CACHE : ($mode === 'table' ? DSVS_TABLE_CACHE : DSVS_SCHEDULE_CACHE);
    $html = DSVS_Fetcher::get($league['url'], $ttl, $mode . '_' . $league['key']);
    if (is_wp_error($html)) return $html;
    return array(
        'league' => $league,
        'standings' => DSVS_Parser::standings($html),
        'current' => DSVS_Parser::current_games($html),
        'schedule' => DSVS_Parser::schedule($html),
    );
}

function dsvs_table_shortcode($atts) {
    $atts = shortcode_atts(array('liga' => 'C2', 'titel' => 'true'), $atts, 'dsv_tabelle');
    dsvs_assets(); $data = dsvs_data($atts['liga'], 'table');
    if (is_wp_error($data)) return DSVS_Renderer::error($data->get_error_message());
    if (empty($data['standings'])) return DSVS_Renderer::notice('Für ' . $data['league']['name'] . ' ist keine Tabelle verfügbar.');
    return DSVS_Renderer::standings($data['standings'], $data['league'], dsvs_bool($atts['titel']));
}
add_shortcode('dsv_tabelle', 'dsvs_table_shortcode');

function dsvs_current_shortcode($atts) {
    $atts = shortcode_atts(array('liga' => 'C2', 'team' => '', 'polling' => 'true', 'titel' => 'true'), $atts, 'dsv_aktuelle_spiele');
    $poll = dsvs_bool($atts['polling']); dsvs_assets($poll);
    $data = dsvs_data($atts['liga'], 'live');
    if (is_wp_error($data)) return DSVS_Renderer::error($data->get_error_message());
    $games = trim($atts['team']) === '' ? $data['current'] : DSVS_Parser::filter_team($data['current'], sanitize_text_field($atts['team']));
    $id = $poll ? 'dsvs-live-' . strtolower($data['league']['key']) : '';
    return DSVS_Renderer::games($games, $data['league'], 'Aktuelle Ergebnisse', $id, dsvs_bool($atts['titel']), $poll);
}
add_shortcode('dsv_aktuelle_spiele', 'dsvs_current_shortcode');

function dsvs_upcoming_shortcode($atts) {
    $atts = shortcode_atts(array('liga' => 'C2', 'team' => '', 'anzahl' => 4, 'titel' => 'true'), $atts, 'dsv_kommende_spiele');
    dsvs_assets(); $data = dsvs_data($atts['liga'], 'schedule');
    if (is_wp_error($data)) return DSVS_Renderer::error($data->get_error_message());
    $limit = max(1, min(50, absint($atts['anzahl'])));
    $games = DSVS_Parser::upcoming($data['schedule'], sanitize_text_field($atts['team']), $limit, trim($atts['team']) === '');
    return DSVS_Renderer::games($games, $data['league'], 'Kommende Spiele', '', dsvs_bool($atts['titel']), false);
}
add_shortcode('dsv_kommende_spiele', 'dsvs_upcoming_shortcode');

function dsvs_team_games_shortcode($atts) {
    $atts = shortcode_atts(array('liga' => 'C2', 'team' => 'TSV Böbingen I', 'anzahl' => 10, 'titel' => 'true'), $atts, 'dsv_team_spiele');
    dsvs_assets(); $data = dsvs_data($atts['liga'], 'schedule');
    if (is_wp_error($data)) return DSVS_Renderer::error($data->get_error_message());
    $games = DSVS_Parser::filter_team($data['schedule'], sanitize_text_field($atts['team']));
    $games = array_slice($games, 0, max(1, min(100, absint($atts['anzahl']))));
    return DSVS_Renderer::games($games, $data['league'], 'Spiele: ' . sanitize_text_field($atts['team']), '', dsvs_bool($atts['titel']), false);
}
add_shortcode('dsv_team_spiele', 'dsvs_team_games_shortcode');

function dsvs_boebingen_shortcode($atts) {
    $atts = shortcode_atts(array('anzahl' => 8, 'titel' => 'true'), $atts, 'dsv_boebingen');
    dsvs_assets(); $all = array(); $errors = array();
    foreach (DSVS_Leagues::all() as $league) {
        $data = dsvs_data($league['key'], 'schedule');
        if (is_wp_error($data)) { $errors[] = $league['key']; continue; }
        $games = DSVS_Parser::upcoming($data['schedule'], '', 100, true);
        foreach ($games as $game) { $game['league'] = $league; $all[] = $game; }
    }
    usort($all, array('DSVS_Parser', 'sort_games'));
    $all = array_slice($all, 0, max(1, min(50, absint($atts['anzahl']))));
    return DSVS_Renderer::combined_games($all, 'Nächste Spiele TSV Böbingen', dsvs_bool($atts['titel']), $errors);
}
add_shortcode('dsv_boebingen', 'dsvs_boebingen_shortcode');

function dsvs_bool($value) { return filter_var($value, FILTER_VALIDATE_BOOLEAN); }

add_action('rest_api_init', function () {
    register_rest_route('dsvs/v1', '/current', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'dsvs_rest_current',
        'permission_callback' => '__return_true',
        'args' => array('liga' => array(
            'default' => 'C2',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'dsvs_validate_rest_league',
        )),
    ));
});

function dsvs_validate_rest_league($value, $request = null, $param = null) {
    return DSVS_Leagues::get($value) !== false;
}

function dsvs_rest_current(WP_REST_Request $request) {
    $data = dsvs_data($request->get_param('liga'), 'live');
    if (is_wp_error($data)) return new WP_REST_Response(array('message' => $data->get_error_message()), 400);
    return rest_ensure_response(array(
        'html' => DSVS_Renderer::games($data['current'], $data['league'], 'Aktuelle Ergebnisse', 'dsvs-live-' . strtolower($data['league']['key']), true, true),
        'generated' => current_time('c'),
    ));
}
