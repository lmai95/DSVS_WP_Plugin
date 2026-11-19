<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Fetch and cache HTML only from the fixed DSVS origin. */
final class DSVS_Fetcher {
    const MAX_RESPONSE_SIZE = 1048576;
    const LOCK_TIMEOUT      = 30;
    const WAIT_SECONDS      = 2;

    public static function get( $url, $duration, $namespace ) {
        $parts = wp_parse_url( $url );
        if (
            ! is_array( $parts ) ||
            empty( $parts['scheme'] ) || 'https' !== strtolower( $parts['scheme'] ) ||
            empty( $parts['host'] ) || 'dsv-schwaben.de' !== strtolower( $parts['host'] ) ||
            isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] )
        ) {
            return new WP_Error( 'dsvs_invalid_url', 'Ungültige DSVS-URL.' );
        }

        $key       = 'dsvs_' . sanitize_key( $namespace ) . '_' . md5( $url );
        $cached    = get_transient( $key );
        if ( false !== $cached ) { return $cached; }

        // An option insert is atomic in WordPress' options table, unlike a transient check/set.
        $lock_key = 'dsvs_lock_' . md5( $url );
        $lock_now = time();
        if ( ! add_option( $lock_key, $lock_now, '', false ) ) {
            $lock_created = (int) get_option( $lock_key );
            if ( $lock_created && $lock_now - $lock_created > self::LOCK_TIMEOUT ) {
                delete_option( $lock_key );
                add_option( $lock_key, $lock_now, '', false );
            }

            $deadline = microtime( true ) + self::WAIT_SECONDS;
            do {
                usleep( 100000 );
                $cached = get_transient( $key );
                if ( false !== $cached ) { return $cached; }
            } while ( microtime( true ) < $deadline );

            return new WP_Error( 'dsvs_fetch_busy', 'DSVS-Abfrage läuft bereits.' );
        }

        try {
            // Recheck after acquiring the lock in case another request populated the cache.
            $cached = get_transient( $key );
            if ( false !== $cached ) { return $cached; }

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
            if ( is_wp_error( $response ) ) { return $response; }

            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );
            if (
                200 !== $code ||
                ! is_string( $body ) ||
                strlen( $body ) < 500 ||
                strlen( $body ) > self::MAX_RESPONSE_SIZE ||
                false === stripos( $body, '<table' )
            ) {
                return new WP_Error( 'dsvs_invalid_response', 'Unerwartete DSVS-Antwort.' );
            }

            set_transient( $key, $body, max( 30, absint( $duration ) ) );
            return $body;
        } finally {
            delete_option( $lock_key );
        }
    }
}
