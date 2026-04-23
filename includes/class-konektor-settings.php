<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Settings {

    public static function get_all() {
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT setting_key, setting_value FROM {$wpdb->prefix}konektor_settings" );
        $out  = [];
        foreach ( $rows as $r ) {
            $out[ $r->setting_key ] = $r->setting_value;
        }
        return $out;
    }

    public static function save( $data ) {
        $allowed = [
            'telegram_bot_token',
            'allowed_domains_global',
            'cs_panel_slug',
            'encrypt_lead_data',
            'tiktok_pixel_id',
            'snack_video_pixel_id',
            'base_slug',
        ];
        $json_keys = [ 'allowed_domains_global' ];
        foreach ( $allowed as $key ) {
            if ( ! isset( $data[ $key ] ) ) continue;
            if ( in_array( $key, $json_keys ) ) {
                // already a JSON string — validate then store as-is
                $decoded = json_decode( wp_unslash( $data[ $key ] ), true );
                Konektor_Helper::set_setting( $key, is_array( $decoded ) ? wp_json_encode( $decoded ) : '[]' );
            } else {
                Konektor_Helper::set_setting( $key, sanitize_text_field( $data[ $key ] ) );
            }
        }
        // Flush rewrite rules after saving base_slug
        Konektor_Router::register_rewrite_rules();
        Konektor_Router::flush();
    }
}
