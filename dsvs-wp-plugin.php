<?php
/**
 * Plugin Name: DSVS WP Plugin
 * Description: Tabellen sowie aktuelle und kommende Spiele mehrerer DSVS-Ligen.
 * Version: 0.3.2
 * Author: Lukas Maier
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH')) exit;
define('DSVS_VERSION','0.3.2');
define('DSVS_TEAMS',array('TSV Böbingen I','TSV Böbingen II'));
define('DSVS_DIR',plugin_dir_path(__FILE__)); define('DSVS_ASSETS',plugin_dir_url(__FILE__).'assets/');
require_once DSVS_DIR.'includes/class-dsvs-leagues.php'; require_once DSVS_DIR.'includes/class-dsvs-fetcher.php'; require_once DSVS_DIR.'includes/class-dsvs-parser.php'; require_once DSVS_DIR.'includes/class-dsvs-renderer.php';
function dsvs_assets($poll=false){wp_enqueue_style('dsvs-style',DSVS_ASSETS.'css/dsvs.css',array(),DSVS_VERSION);if($poll){wp_enqueue_script('dsvs-live',DSVS_ASSETS.'js/dsvs-live.js',array(),DSVS_VERSION,true);wp_localize_script('dsvs-live','dsvsLive',array('baseUrl'=>esc_url_raw(rest_url('dsvs/v1/current')),'interval'=>60000));}}
function dsvs_data($key,$mode='schedule'){$l=DSVS_Leagues::get($key);if(!$l)return new WP_Error('league','Unbekannte Liga');$ttl=$mode==='live'?60:($mode==='table'?600:1800);$h=DSVS_Fetcher::get($l['url'],$ttl,$mode.'_'.$l['key']);if(is_wp_error($h))return $h;return array('league'=>$l,'standings'=>DSVS_Parser::standings($h),'current'=>DSVS_Parser::current_games($h),'schedule'=>DSVS_Parser::schedule($h));}
function dsvs_table($a){$a=shortcode_atts(array('liga'=>'C2'),$a);dsvs_assets();$d=dsvs_data($a['liga'],'table');return is_wp_error($d)?DSVS_Renderer::error():DSVS_Renderer::standings($d['standings'],$d['league']);} add_shortcode('dsv_tabelle','dsvs_table');
function dsvs_current($a){$a=shortcode_atts(array('liga'=>'C2'),$a);dsvs_assets(true);$d=dsvs_data($a['liga'],'live');return is_wp_error($d)?DSVS_Renderer::error():DSVS_Renderer::games($d['current'],$d['league'],'Aktuelle Ergebnisse','dsvs-live-'.strtolower($d['league']['key']),true);} add_shortcode('dsv_aktuelle_spiele','dsvs_current');
function dsvs_upcoming($a){$a=shortcode_atts(array('liga'=>'C2','anzahl'=>4),$a);dsvs_assets();$d=dsvs_data($a['liga']);return is_wp_error($d)?DSVS_Renderer::error():DSVS_Renderer::games(DSVS_Parser::upcoming($d['schedule'],absint($a['anzahl'])),$d['league'],'Kommende Spiele');} add_shortcode('dsv_kommende_spiele','dsvs_upcoming');
add_action('rest_api_init',function(){register_rest_route('dsvs/v1','/current',array('methods'=>'GET','permission_callback'=>'__return_true','args'=>array('liga'=>array('default'=>'C2','sanitize_callback'=>'sanitize_text_field','validate_callback'=>function($v){return DSVS_Leagues::get($v)!==false;})),'callback'=>function($r){$d=dsvs_data($r['liga'],'live');if(is_wp_error($d))return new WP_REST_Response(array('message'=>'invalid'),400);return rest_ensure_response(array('html'=>DSVS_Renderer::games($d['current'],$d['league'],'Aktuelle Ergebnisse','dsvs-live-'.strtolower($d['league']['key']),true)));}));});
