<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Downloads DSVS pages and keeps requests away from the source via WordPress transients. */
final class DSVS_Fetcher {
    private const MAX_RESPONSE_SIZE = 1048576; // 1 MiB.

    public static function get( $url, $cache_duration, $namespace ) {
        $cache_key = 'dsvs_' . sanitize_key( $namespace ) . '_' . md5( $url );
        $cached_html = get_transient( $cache_key );

        if ( false !== $cached_html ) {
            return $cached_html;
        }

        $response = wp_safe_remote_get(
            $url,
            array(
                'timeout'             => 12,
                'redirection'         => 3,
                'limit_response_size' => self::MAX_RESPONSE_SIZE,
                'headers'             => array( 'Accept' => 'text/html' ),
                'user-agent'          => 'DSVS-WP-Plugin/' . DSVS_VERSION . '; ' . home_url( '/' ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if ( 200 !== $response_code ) {
            return new WP_Error( 'dsvs_http_error', 'DSVS HTTP status ' . $response_code );
        }

        if ( ! self::is_valid_html( $response_body ) ) {
            return new WP_Error( 'dsvs_invalid_response', 'Unerwartete DSVS-Antwort.' );
        }

        set_transient( $cache_key, $response_body, max( 30, absint( $cache_duration ) ) );
        return $response_body;
    }

    private static function is_valid_html( $html ) {
        $length = strlen( $html );
        if ( $length < 500 || $length > self::MAX_RESPONSE_SIZE ) {
            return false;
        }

        return false !== stripos( $html, '<html' ) && false !== stripos( $html, '<table' );
    }
}
