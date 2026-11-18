<?php
if (!defined('ABSPATH')) exit;
final class DSVS_Fetcher {
    public static function get($url) {
        $key='dsvs_html_'.md5($url); $cached=get_transient($key);
        if ($cached!==false) return $cached;
        $res=wp_remote_get($url,array('timeout'=>10,'user-agent'=>'DSVS-WP-Plugin/'.DSVS_VERSION.'; '.home_url('/')));
        if (is_wp_error($res)) return $res;
        $code=wp_remote_retrieve_response_code($res); if ($code!==200) return new WP_Error('dsvs_http','DSVS HTTP '.$code);
        $body=wp_remote_retrieve_body($res); if (!$body) return new WP_Error('dsvs_empty','Leere Antwort vom DSVS.');
        set_transient($key,$body,DSVS_CACHE_SECONDS); return $body;
    }
}
