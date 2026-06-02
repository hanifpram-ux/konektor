<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Meta {

    public static function get_config( $campaign ) {
        $pixel = $campaign->pixel_config ? json_decode( $campaign->pixel_config, true ) : [];
        $cfg = $pixel['meta'] ?? [];
        // Backward compatibility: if 'enabled' key doesn't exist, treat as enabled (old campaigns)
        if ( array_key_exists( 'enabled', $cfg ) && empty( $cfg['enabled'] ) ) return [];
        return $cfg;
    }

    public static function send_capi_event( $event_name, $lead_data, $meta_cfg ) {
        if ( empty( $event_name ) ) return;
        if ( empty( $meta_cfg['pixel_id'] ) || empty( $meta_cfg['token'] ) ) return;

        $pixel_id = sanitize_text_field( $meta_cfg['pixel_id'] );
        $token    = sanitize_text_field( $meta_cfg['token'] );
        $event_id  = bin2hex( random_bytes( 16 ) );
        $user_data = self::build_user_data( $lead_data );

        $event_data = [
            'event_name'       => $event_name,
            'event_time'       => time(),
            'event_id'         => $event_id,
            'action_source'    => 'website',
            'event_source_url' => $lead_data['source_url'] ?? home_url(),
            'user_data'        => $user_data,
        ];

        // custom_data hanya untuk event konversi, bukan PageView/ViewContent
        $no_custom_data_events = [ 'PageView', 'ViewContent', 'Search' ];
        if ( ! in_array( $event_name, $no_custom_data_events ) ) {
            $custom_data = [
                'currency' => $meta_cfg['currency'] ?? 'IDR',
                'value'    => (float) ( $meta_cfg['value'] ?? 0 ),
            ];
            if ( ! empty( $lead_data['product_name'] ) ) {
                $custom_data['content_name'] = sanitize_text_field( $lead_data['product_name'] );
            }
            $event_data['custom_data'] = $custom_data;
        }

        $payload = [ 'data' => [ $event_data ] ];

        if ( ! empty( $meta_cfg['test_event_code'] ) ) {
            $payload['test_event_code'] = sanitize_text_field( $meta_cfg['test_event_code'] );
        }

        $endpoint = "https://graph.facebook.com/v25.0/{$pixel_id}/events";
        $response = wp_remote_post(
            $endpoint . "?access_token={$token}",
            [
                'body'    => wp_json_encode( $payload ),
                'headers' => [ 'Content-Type' => 'application/json' ],
                'timeout' => 15,
            ]
        );

        Konektor_Logger::log( 'Meta CAPI', $event_name, $endpoint, $payload, $response );

        return $response;
    }

    private static function build_user_data( $lead ) {
        $ud = [
            'client_ip_address' => isset( $lead['ip'] ) ? $lead['ip'] : Konektor_Helper::get_client_ip(),
            'client_user_agent' => substr( isset( $lead['user_agent'] ) ? $lead['user_agent'] : ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 512 ),
        ];
        if ( ! empty( $_COOKIE['_fbp'] ) ) $ud['fbp'] = sanitize_text_field( $_COOKIE['_fbp'] );
        if ( ! empty( $_COOKIE['_fbc'] ) ) {
            $ud['fbc'] = sanitize_text_field( $_COOKIE['_fbc'] );
        } elseif ( ! empty( $lead['extra_data']['fbclid'] ) ) {
            // fbclid dari extra_data (server-side only, tidak ada cookie)
            $ud['fbc'] = 'fb.1.' . ( time() * 1000 ) . '.' . $lead['extra_data']['fbclid'];
        }
        if ( ! empty( $lead['email'] ) ) {
            $ud['em'] = [ hash( 'sha256', strtolower( trim( $lead['email'] ) ) ) ];
        }
        if ( ! empty( $lead['phone'] ) ) {
            $p = preg_replace( '/[^0-9]/', '', $lead['phone'] );
            if ( substr( $p, 0, 1 ) === '0' ) $p = '62' . substr( $p, 1 );
            $ud['ph'] = [ hash( 'sha256', $p ) ];
        }
        if ( ! empty( $lead['name'] ) ) {
            $parts    = explode( ' ', trim( $lead['name'] ), 2 );
            $ud['fn'] = [ hash( 'sha256', strtolower( $parts[0] ) ) ];
            if ( ! empty( $parts[1] ) ) $ud['ln'] = [ hash( 'sha256', strtolower( $parts[1] ) ) ];
        }
        return $ud;
    }

    public static function get_pixel_script( $campaign, $event_type ) {
        $cfg = self::get_config( $campaign );
        if ( empty( $cfg['pixel_id'] ) ) return '';

        $pixel_id = esc_js( $cfg['pixel_id'] );
        $event_map = [
            'page_load'   => ! empty( $cfg['page_load_event'] )   ? $cfg['page_load_event']   : '',
            'form_submit' => ! empty( $cfg['form_submit_event'] ) ? $cfg['form_submit_event'] : '',
            'thanks_page' => ! empty( $cfg['thanks_page_event'] ) ? $cfg['thanks_page_event'] : '',
        ];
        $event = isset( $event_map[ $event_type ] ) ? trim( $event_map[ $event_type ] ) : '';
        if ( empty( $event ) ) return '';
        $event    = esc_js( $event );
        $event_id = esc_js( bin2hex( random_bytes( 8 ) ) );
        $sess_key = esc_js( 'knk_m_' . (int) $campaign->id . '_' . $event_type );
        $init_opts = ( $event_type !== 'page_load' ) ? ", { autoConfig: false }" : '';

        if ( $event_type === 'thanks_page' && ! empty( $cfg['value'] ) ) {
            $value      = esc_js( (float) $cfg['value'] );
            $currency   = esc_js( $cfg['currency'] ?? 'IDR' );
            $track_call = "fbq('track','{$event}',{value:{$value},currency:'{$currency}'},{eventID:'{$event_id}'});";
        } else {
            $track_call = "fbq('track','{$event}',{},{eventID:'{$event_id}'});";
        }

        return <<<HTML
<!-- Meta Pixel -->
<script>
  !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
  fbq('init','{$pixel_id}'{$init_opts});
  (function(){var _k='{$sess_key}';if(sessionStorage.getItem(_k))return;sessionStorage.setItem(_k,'1');{$track_call}})();
</script>
<!-- End Meta Pixel -->

HTML;
    }
}
