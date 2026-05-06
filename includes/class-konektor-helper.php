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
        // httponly=false agar JS embed di landing page bisa baca cookie yang sama
        setcookie( $cookie_name, $id, time() + YEAR_IN_SECONDS, '/', '', is_ssl(), false );
        $_COOKIE[ $cookie_name ] = $id;
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

    /**
     * Fire server-side CAPI events when a lead status changes.
     * Only fires for purchased (conversion) and cancelled (reversal) statuses.
     */
    public static function fire_lead_status_capi( $lead_id, $status ) {
        if ( ! in_array( $status, [ 'purchased', 'cancelled' ], true ) ) {
            return;
        }

        $lead = Konektor_Lead::get( $lead_id );
        if ( ! $lead ) return;

        $campaign = Konektor_Campaign::get( $lead->campaign_id );
        if ( ! $campaign ) return;

        $lead_data = Konektor_Lead::decrypt_lead( clone $lead );
        $lead_arr = [
            'source_url'   => $lead->source_url ?? '',
            'referrer'     => $lead->referrer ?? '',
            'name'         => $lead_data->name ?? '',
            'email'        => $lead_data->email ?? '',
            'phone'        => $lead_data->phone ?? '',
            'product_name' => $campaign->product_name ?? '',
        ];

        $meta_cfg   = Konektor_Meta::get_config( $campaign );
        $tiktok_cfg = Konektor_Tiktok::get_config( $campaign );
        $snack_cfg  = Konektor_Snack::get_config( $campaign );

        if ( $status === 'purchased' ) {
            // Conversion — fire thanks_page event
            if ( ! empty( $meta_cfg['token'] ) ) {
                $event = $meta_cfg['thanks_page_event'] ?? '';
                Konektor_Meta::send_capi_event( $event, $lead_arr, $meta_cfg );
            }
            if ( ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) ) {
                Konektor_Tiktok::send_event( 'thanks_page', $lead_arr, $tiktok_cfg );
            }
            if ( ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) ) {
                Konektor_Snack::send_event( $snack_cfg['thanks_page_event'] ?? '', $lead_arr, $snack_cfg );
            }
        } elseif ( $status === 'cancelled' ) {
            // Re-fire form_submit event as status update
            if ( ! empty( $meta_cfg['token'] ) ) {
                $event = $meta_cfg['form_submit_event'] ?? '';
                Konektor_Meta::send_capi_event( $event, $lead_arr, $meta_cfg );
            }
            if ( ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) ) {
                Konektor_Tiktok::send_event( 'form_submit', $lead_arr, $tiktok_cfg );
            }
            if ( ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) ) {
                Konektor_Snack::send_event( $snack_cfg['form_submit_event'] ?? '', $lead_arr, $snack_cfg );
            }
        }
    }
}
