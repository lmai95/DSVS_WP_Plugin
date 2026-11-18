<?php
/**
 * Plugin Name: DSVS WP Plugin
 * Description: Ligatabelle sowie aktuelle und kommende Spiele des DSV Schwaben.
 * Version: 0.2.0
 * Author: Lukas Maier
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dsvs-wp-plugin
 */
if (!defined('ABSPATH')) exit;

define('DSVS_VERSION', '0.2.0');
define('DSVS_URL', 'https://dsv-schwaben.de/sb_spielplan.php?p=209--2026/2027-92');
define('DSVS_TEAMS', array('TSV Böbingen I', 'TSV Böbingen II'));
define('DSVS_TABLE_CACHE', 10 * MINUTE_IN_SECONDS);
define('DSVS_SCHEDULE_CACHE', 30 * MINUTE_IN_SECONDS);
define('DSVS_LIVE_CACHE', 60);
define('DSVS_DIR', plugin_dir_path(__FILE__));
define('DSVS_ASSETS', plugin_dir_url(__FILE__) . 'assets/');

require_once DSVS_DIR . 'includes/class-dsvs-fetcher.php';
require_once DSVS_DIR . 'includes/class-dsvs-parser.php';
require_once DSVS_DIR . 'includes/class-dsvs-renderer.php';

function dsvs_assets($poll = false) {
    wp_enqueue_style('dsvs-style', DSVS_ASSETS . 'css/dsvs.css', array(), DSVS_VERSION);
    if ($poll) {
        wp_enqueue_script('dsvs-live', DSVS_ASSETS . 'js/dsvs-live.js', array(), DSVS_VERSION, true);
        wp_localize_script('dsvs-live', 'dsvsLive', array(
            'url' => esc_url_raw(rest_url('dsvs/v1/current')),
            'interval' => 60000,
            'error' => __('Aktualisierung derzeit nicht möglich.', 'dsvs-wp-plugin'),
        ));
    }
}

function dsvs_data($mode = 'schedule') {
    $ttl = $mode === 'live' ? DSVS_LIVE_CACHE : ($mode === 'table' ? DSVS_TABLE_CACHE : DSVS_SCHEDULE_CACHE);
    $html = DSVS_Fetcher::get(DSVS_URL, $ttl, $mode);
    if (is_wp_error($html)) return $html;
    return array(
        'standings' => DSVS_Parser::standings($html),
        'current' => DSVS_Parser::current_games($html),
        'schedule' => DSVS_Parser::schedule($html),
    );
}

function dsvs_table_shortcode() {
    dsvs_assets(); $data = dsvs_data('table');
    if (is_wp_error($data) || empty($data['standings'])) return DSVS_Renderer::error();
    return DSVS_Renderer::standings($data['standings']);
}
add_shortcode('dsv_tabelle', 'dsvs_table_shortcode');

function dsvs_current_shortcode($atts = array()) {
    $atts = shortcode_atts(array('team' => '', 'polling' => 'true'), $atts, 'dsv_aktuelle_spiele');
    $poll = filter_var($atts['polling'], FILTER_VALIDATE_BOOLEAN);
    dsvs_assets($poll); $data = dsvs_data('live');
    if (is_wp_error($data)) return DSVS_Renderer::error();
    $games = DSVS_Parser::filter_team($data['current'], sanitize_text_field($atts['team']));
    return DSVS_Renderer::games($games, 'Aktuelle Ergebnisse', $poll ? 'dsvs-live-results' : '');
}
add_shortcode('dsv_aktuelle_spiele', 'dsvs_current_shortcode');

function dsvs_upcoming_shortcode($atts = array()) {
    $atts = shortcode_atts(array('team' => '', 'anzahl' => 4), $atts, 'dsv_kommende_spiele');
    dsvs_assets(); $data = dsvs_data('schedule');
    if (is_wp_error($data)) return DSVS_Renderer::error();
    $limit = max(1, min(20, absint($atts['anzahl'])));
    $games = DSVS_Parser::upcoming($data['schedule'], sanitize_text_field($atts['team']), $limit);
    return DSVS_Renderer::games($games, 'Kommende Spiele');
}
add_shortcode('dsv_kommende_spiele', 'dsvs_upcoming_shortcode');

function dsvs_team_games_shortcode($atts = array()) {
    $atts = shortcode_atts(array('team' => 'TSV Böbingen I', 'anzahl' => 10), $atts, 'dsv_team_spiele');
    dsvs_assets(); $data = dsvs_data('schedule');
    if (is_wp_error($data)) return DSVS_Renderer::error();
    $games = DSVS_Parser::filter_team($data['schedule'], sanitize_text_field($atts['team']));
    $games = array_slice($games, 0, max(1, min(50, absint($atts['anzahl']))));
    return DSVS_Renderer::games($games, 'Spiele: ' . sanitize_text_field($atts['team']));
}
add_shortcode('dsv_team_spiele', 'dsvs_team_games_shortcode');

add_action('rest_api_init', function () {
    register_rest_route('dsvs/v1', '/current', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => function () {
            $data = dsvs_data('live');
            if (is_wp_error($data)) return new WP_REST_Response(array('message' => $data->get_error_message()), 503);
            return rest_ensure_response(array(
                'html' => DSVS_Renderer::games($data['current'], 'Aktuelle Ergebnisse', 'dsvs-live-results'),
                'generated' => current_time('c'),
            ));
        },
        'permission_callback' => '__return_true',
    ));
});
