<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Helper {

    public static function get_setting( $key, $default = '' ) {
        global $wpdb;
        $val = $wpdb->get_var( $wpdb->prepare(
            "SELECT setting_value FROM {$wpdb->prefix}konektor_settings WHERE setting_key = %s",
            $key
        ) );
        return $val !== null ? $val : $default;
    }

    public static function set_setting( $key, $value ) {
        global $wpdb;
        return $wpdb->replace(
            $wpdb->prefix . 'konektor_settings',
            [ 'setting_key' => $key, 'setting_value' => $value ]
        );
    }

    public static function get_client_ip() {
        $keys = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ];
        foreach ( $keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = trim( explode( ',', sanitize_text_field( $_SERVER[ $key ] ) )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
            }
        }
        return '';
    }

    public static function get_or_create_cookie_id() {
        $cookie_name = 'konektor_vid';
        if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
            return sanitize_text_field( $_COOKIE[ $cookie_name ] );
        }
        $id = Konektor_Crypto::generate_token( 16 );
        setcookie( $cookie_name, $id, time() + YEAR_IN_SECONDS, '/', '', is_ssl(), true );
        return $id;
    }

    public static function parse_shortcodes( $template, $data ) {
        $map = [
            '[cname]'    => $data['name']    ?? '',
            '[cemail]'   => $data['email']   ?? '',
            '[cphone]'   => $data['phone']   ?? '',
            '[caddress]' => $data['address'] ?? '',
            '[catatan]'  => $data['custom_message'] ?? '',
            '[product]'  => $data['product_name']   ?? '',
            '[quantity]' => $data['quantity']        ?? '',
            '[oname]'    => $data['operator_name']   ?? '',
        ];
        return str_replace( array_keys( $map ), array_values( $map ), $template );
    }

    public static function is_domain_allowed( $campaign ) {
        $allowed = json_decode( $campaign->allowed_domains ?? '[]', true );
        if ( empty( $allowed ) ) return true;
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host    = parse_url( $referer, PHP_URL_HOST );
        foreach ( $allowed as $domain ) {
            if ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) return true;
        }
        return false;
    }

    public static function json_response( $data, $status = 200 ) {
        wp_send_json( $data, $status );
    }

    public static function sanitize_phone( $phone ) {
        return preg_replace( '/[^0-9+]/', '', $phone );
    }

    public static function wa_url( $phone, $message = '' ) {
        $phone = ltrim( preg_replace( '/[^0-9]/', '', $phone ), '0' );
        $phone = '62' . $phone;
        return 'https://wa.me/' . $phone . ( $message ? '?text=' . rawurlencode( $message ) : '' );
    }
}
