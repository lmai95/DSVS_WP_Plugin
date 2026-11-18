<?php
if (!defined('ABSPATH')) exit;
final class DSVS_Fetcher {
    public static function get($url, $ttl, $namespace='default') {
        $key='dsvs_'.sanitize_key($namespace).'_'.md5($url); $cached=get_transient($key);
        if ($cached!==false) return $cached;
        $response=wp_safe_remote_get($url,array('timeout'=>12,'redirection'=>3,'limit_response_size'=>1048576,'headers'=>array('Accept'=>'text/html'),'user-agent'=>'DSVS-WP-Plugin/'.DSVS_VERSION.'; '.home_url('/')));
        if (is_wp_error($response)) return self::stale_or_error($key,$response);
        $code=wp_remote_retrieve_response_code($response); $body=wp_remote_retrieve_body($response);
        if ($code!==200 || trim($body)==='') return self::stale_or_error($key,new WP_Error('dsvs_http','DSVS HTTP '.$code));
        if (!self::valid_html($body)) return self::stale_or_error($key,new WP_Error('dsvs_invalid','Unerwartete DSVS-Antwort.'));
        set_transient($key,$body,max(30,absint($ttl))); update_option($key.'_stale',$body,false); return $body;
    }
    private static function valid_html($body) {
        if (strlen($body) < 500 || strlen($body) > 1048576) return false;
        $lower = strtolower($body);
        return strpos($lower, '<html') !== false && strpos($lower, '<table') !== false;
    }
    private static function stale_or_error($key,$error) { $stale=get_option($key.'_stale'); return $stale ? $stale : $error; }
}
