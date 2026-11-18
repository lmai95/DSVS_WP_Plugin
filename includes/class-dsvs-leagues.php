<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Central configuration for all supported DSVS competitions. */
final class DSVS_Leagues {
    private static $leagues = array(
        'OL'   => array( 'name' => 'Oberliga', 'id' => 86 ),
        'A'    => array( 'name' => 'A-Liga', 'id' => 87 ),
        'B'    => array( 'name' => 'B-Liga', 'id' => 88 ),
        'C1'   => array( 'name' => 'C1-Liga', 'id' => 90 ),
        'C2'   => array( 'name' => 'C2-Liga', 'id' => 92 ),
        'LC'   => array( 'name' => 'Liga Cup', 'id' => 93 ),
        'CLVH' => array( 'name' => 'C-Liga Vergleichskampf (Hinrunde)', 'id' => 96 ),
        'CLVR' => array( 'name' => 'C-Liga Vergleichskampf (Rückrunde)', 'id' => 97 ),
    );

    public static function get( $league_key ) {
        $league_key = strtoupper( trim( (string) $league_key ) );

        if ( 'AL' === $league_key ) {
            $league_key = 'A';
        }

        if ( ! isset( self::$leagues[ $league_key ] ) ) {
            return false;
        }

        $league = self::$leagues[ $league_key ];
        $league['key'] = $league_key;
        $league['url'] = sprintf(
            'https://dsv-schwaben.de/sb_spielplan.php?p=209--2026/2027-%d',
            $league['id']
        );

        return $league;
    }
}
