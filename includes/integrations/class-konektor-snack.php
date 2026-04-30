<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Snack {

    const API_ENDPOINT = 'https://www.adsnebula.com/log/common/api';

    public static function get_config( $campaign ) {
        $pixel = $campaign->pixel_config ? json_decode( $campaign->pixel_config, true ) : [];
        return $pixel['snack'] ?? [];
    }

    /**
     * Kirim event ke Kwai/Snack Event API (server-side).
     *
     * @param string $event_name  Contoh: EVENT_FORM_SUBMIT, EVENT_PURCHASE, EVENT_CONTENT_VIEW
     * @param array  $lead_data   Data lead dari form submit
     * @param array  $snack_cfg   Config dari get_config()
     */
    public static function send_event( $event_name, $lead_data, $snack_cfg ) {
        if ( empty( $snack_cfg['pixel_id'] ) || empty( $snack_cfg['access_token'] ) ) return;

        $pixel_id     = sanitize_text_field( $snack_cfg['pixel_id'] );
        $access_token = sanitize_text_field( $snack_cfg['access_token'] );
        $is_test      = ! empty( $snack_cfg['test_mode'] );

        // click_id: test mode pakai test_click_id dari panel, live pakai dari URL parameter
        if ( $is_test && ! empty( $snack_cfg['test_click_id'] ) ) {
            $click_id = sanitize_text_field( $snack_cfg['test_click_id'] );
        } else {
            $click_id = sanitize_text_field( $lead_data['click_id'] ?? $_GET['click_id'] ?? $_GET['clickid'] ?? '' );
        }

        $payload = [
            'pixelId'         => $pixel_id,
            'access_token'    => $access_token,
            'event_name'      => $event_name,
            'clickid'         => $click_id,
            'is_attributed'   => 1,
            'mmpcode'         => 'PL',
            'pixelSdkVersion' => '9.9.9',
            'testFlag'        => $is_test,
            'trackFlag'       => $is_test,
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
        $map = [
            'page_load'   => $snack_cfg['page_load_event']   ?? 'EVENT_CONTENT_VIEW',
            'form_submit' => $snack_cfg['form_submit_event'] ?? 'EVENT_FORM_SUBMIT',
            'thanks_page' => $snack_cfg['thanks_page_event'] ?? 'EVENT_COMPLETE_REGISTRATION',
        ];
        return $map[ $event_type ] ?? 'EVENT_CONTENT_VIEW';
    }
}
