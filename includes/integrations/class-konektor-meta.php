<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Meta {

    public static function get_config( $campaign ) {
        $pixel = $campaign->pixel_config ? json_decode( $campaign->pixel_config, true ) : [];
        return $pixel['meta'] ?? [];
    }

    public static function send_capi_event( $event_name, $lead_data, $meta_cfg ) {
        if ( empty( $meta_cfg['pixel_id'] ) || empty( $meta_cfg['token'] ) ) return;

        $pixel_id = sanitize_text_field( $meta_cfg['pixel_id'] );
        $token    = sanitize_text_field( $meta_cfg['token'] );

        $user_data = [
            'client_ip_address' => Konektor_Helper::get_client_ip(),
            'client_user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
        ];

        if ( ! empty( $lead_data['email'] ) ) {
            $user_data['em'] = [ hash( 'sha256', strtolower( trim( $lead_data['email'] ) ) ) ];
        }
        if ( ! empty( $lead_data['phone'] ) ) {
            $phone = preg_replace( '/[^0-9]/', '', $lead_data['phone'] );
            $user_data['ph'] = [ hash( 'sha256', $phone ) ];
        }

        $payload = [
            'data' => [ [
                'event_name'       => $event_name,
                'event_time'       => time(),
                'action_source'    => 'website',
                'event_source_url' => $lead_data['source_url'] ?? home_url(),
                'user_data'        => $user_data,
                'custom_data'      => [
                    'currency' => $meta_cfg['currency'] ?? 'IDR',
                    'value'    => $meta_cfg['value'] ?? '0',
                ],
            ] ],
        ];

        if ( ! empty( $meta_cfg['test_event_code'] ) ) {
            $payload['test_event_code'] = $meta_cfg['test_event_code'];
        }

        wp_remote_post(
            "https://graph.facebook.com/v19.0/{$pixel_id}/events?access_token={$token}",
            [
                'body'    => wp_json_encode( $payload ),
                'headers' => [ 'Content-Type' => 'application/json' ],
                'timeout' => 10,
            ]
        );
    }

    public static function get_pixel_script( $campaign, $event_type ) {
        $cfg = self::get_config( $campaign );
        if ( empty( $cfg['pixel_id'] ) ) return '';

        $pixel_id  = esc_js( $cfg['pixel_id'] );
        $event_map = [
            'page_load'   => $cfg['page_load_event']   ?? 'PageView',
            'form_submit' => $cfg['form_submit_event']  ?? 'Lead',
            'thanks_page' => $cfg['thanks_page_event']  ?? 'Purchase',
        ];
        $event = $event_map[ $event_type ] ?? 'PageView';

        $script = "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{$pixel_id}');fbq('track','{$event}');";

        if ( $event_type === 'thanks_page' && ! empty( $cfg['value'] ) ) {
            $currency = esc_js( $cfg['currency'] ?? 'IDR' );
            $value    = esc_js( $cfg['value'] ?? '0' );
            $script   = "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{$pixel_id}');fbq('track','Purchase',{value:{$value},currency:'{$currency}'});";
        }

        return '<script>' . $script . '</script>';
    }
}
