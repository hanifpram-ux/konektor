<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Snack {

    public static function get_config( $campaign ) {
        $pixel = $campaign->pixel_config ? json_decode( $campaign->pixel_config, true ) : [];
        return $pixel['snack'] ?? [];
    }

    // Returns the raw HTML snippet stored by the user — output as-is into <head>
    public static function get_script( $campaign ) {
        $cfg  = self::get_config( $campaign );
        $html = trim( $cfg['html'] ?? '' );
        return $html;
    }
}
