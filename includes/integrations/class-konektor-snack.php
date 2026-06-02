<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Snack {

    const API_ENDPOINT = 'https://www.adsnebula.com/log/common/api';

    public static function get_config( $campaign ) {
        $pixel = $campaign->pixel_config ? json_decode( $campaign->pixel_config, true ) : [];
        $cfg = $pixel['snack'] ?? [];
        // Backward compatibility: if 'enabled' key doesn't exist, treat as enabled (old campaigns)
        if ( array_key_exists( 'enabled', $cfg ) && empty( $cfg['enabled'] ) ) return [];
        return $cfg;
    }

    /**
     * Kirim event ke Kwai/Snack Event API (server-side).
     *
     * @param string $event_name  Contoh: EVENT_FORM_SUBMIT, EVENT_PURCHASE, EVENT_CONTENT_VIEW
     * @param array  $lead_data   Data lead dari form submit
     * @param array  $snack_cfg   Config dari get_config()
     */
    public static function send_event( $event_name_or_type, $lead_data, $snack_cfg ) {
        if ( empty( $snack_cfg['pixel_id'] ) || empty( $snack_cfg['access_token'] ) ) return;

        // Accept either event_type ('page_load' etc.) or a literal event name
        $resolved   = self::get_event_name( $snack_cfg, $event_name_or_type );
        $event_name = $resolved !== '' ? $resolved
            : ( in_array( $event_name_or_type, [ 'page_load', 'form_submit', 'thanks_page' ] )
                ? '' : trim( $event_name_or_type ) );
        if ( empty( $event_name ) ) return;

        $pixel_id     = sanitize_text_field( $snack_cfg['pixel_id'] );
        $access_token = sanitize_text_field( $snack_cfg['access_token'] );
        $is_test      = ! empty( $snack_cfg['test_mode'] );

        // click_id: test mode pakai test_click_id dari panel, live pakai dari URL parameter
        if ( $is_test && ! empty( $snack_cfg['test_click_id'] ) ) {
            $click_id = sanitize_text_field( $snack_cfg['test_click_id'] );
        } else {
            // Priority: kwai_click_id dari extra_data → click_id → $_GET
            $extra  = is_array( $lead_data['extra_data'] ?? null ) ? $lead_data['extra_data'] : [];
            $click_id = sanitize_text_field(
                $extra['kwai_click_id']
                ?? $lead_data['click_id']
                ?? $_GET['click_id']
                ?? $_GET['clickid']
                ?? ''
            );
        }

        $payload = [
            'pixelId'         => $pixel_id,
            'access_token'    => $access_token,
            'event_name'      => $event_name,
            'clickid'         => $click_id,
            'is_attributed'   => 1,
            'mmpcode'         => 'PL',
            'pixelSdkVersion' => '9.9.9',
            'testFlag'        => $is_test ? 1 : 0,
            'trackFlag'       => $is_test ? 1 : 0,
            'third_party'     => 'konektor',
        ];

        // Value & currency untuk event purchase/conversion
        if ( ! empty( $snack_cfg['value'] ) ) {
            $payload['value']    = (string) $snack_cfg['value'];
            $payload['currency'] = sanitize_text_field( $snack_cfg['currency'] ?? 'IDR' );
        }

        // Properties opsional
        $properties = [];
        if ( ! empty( $lead_data['product_name'] ) ) {
            $properties['content_name'] = sanitize_text_field( $lead_data['product_name'] );
        }
        if ( ! empty( $lead_data['quantity'] ) ) {
            $properties['quantity'] = (int) $lead_data['quantity'];
        }
        if ( ! empty( $properties ) ) {
            $payload['properties'] = wp_json_encode( $properties );
        }

        $response = wp_remote_post( self::API_ENDPOINT, [
            'body'    => wp_json_encode( $payload ),
            'headers' => [
                'Content-Type' => 'application/json',
                'accept'       => 'application/json;charset=utf-8',
            ],
            'timeout' => 15,
        ] );

        Konektor_Logger::log( 'Snack/Kwai Event API', $event_name, self::API_ENDPOINT, $payload, $response );

        return $response;
    }

    /**
     * Pixel script browser (page_load) — tetap tersedia sebagai fallback
     * jika user isi HTML snippet manual.
     */
    public static function get_script( $campaign ) {
        $cfg  = self::get_config( $campaign );
        $html = trim( $cfg['html'] ?? '' );
        return $html;
    }

    /**
     * Mapping event_type ke Kwai event name.
     */
    public static function get_event_name( $snack_cfg, $event_type ) {
        $keys = [
            'page_load'   => 'page_load_event',
            'form_submit' => 'form_submit_event',
            'thanks_page' => 'thanks_page_event',
        ];
        $cfg_key = $keys[ $event_type ] ?? '';
        if ( ! $cfg_key ) return '';

        // If key exists in config but is empty, user chose "Tidak Digunakan"
        if ( array_key_exists( $cfg_key, $snack_cfg ) && trim( $snack_cfg[ $cfg_key ] ) === '' ) {
            return '';
        }

        return trim( ! empty( $snack_cfg[ $cfg_key ] ) ? $snack_cfg[ $cfg_key ] : '' );
    }
}
