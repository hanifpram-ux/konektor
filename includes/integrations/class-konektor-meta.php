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
        $event_id = md5( uniqid( $event_name, true ) );

        $user_data = [
            'client_ip_address' => Konektor_Helper::get_client_ip(),
            'client_user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
        ];

        // Browser cookies untuk matching
        if ( ! empty( $_COOKIE['_fbp'] ) ) {
            $user_data['fbp'] = sanitize_text_field( $_COOKIE['_fbp'] );
        }
        if ( ! empty( $_COOKIE['_fbc'] ) ) {
            $user_data['fbc'] = sanitize_text_field( $_COOKIE['_fbc'] );
        }

        // Hashed PII
        if ( ! empty( $lead_data['email'] ) ) {
            $user_data['em'] = [ hash( 'sha256', strtolower( trim( $lead_data['email'] ) ) ) ];
        }
        if ( ! empty( $lead_data['phone'] ) ) {
            $phone = preg_replace( '/[^0-9]/', '', $lead_data['phone'] );
            // Normalisasi: awalan 0 → 62 (format E.164 Indonesia)
            if ( str_starts_with( $phone, '0' ) ) {
                $phone = '62' . substr( $phone, 1 );
            }
            $user_data['ph'] = [ hash( 'sha256', $phone ) ];
        }
        if ( ! empty( $lead_data['name'] ) ) {
            $parts = explode( ' ', trim( $lead_data['name'] ), 2 );
            $user_data['fn'] = [ hash( 'sha256', strtolower( $parts[0] ) ) ];
            if ( ! empty( $parts[1] ) ) {
                $user_data['ln'] = [ hash( 'sha256', strtolower( $parts[1] ) ) ];
            }
        }

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

    public static function get_pixel_script( $campaign, $event_type ) {
        $cfg = self::get_config( $campaign );
        if ( empty( $cfg['pixel_id'] ) ) return '';

        $pixel_id = esc_js( $cfg['pixel_id'] );
        $event_map = [
            'page_load'   => $cfg['page_load_event']  ?? 'PageView',
            'form_submit' => $cfg['form_submit_event'] ?? 'Lead',
            'thanks_page' => $cfg['thanks_page_event'] ?? 'Purchase',
        ];
        $event    = esc_js( $event_map[ $event_type ] ?? 'PageView' );
        $event_id = esc_js( md5( uniqid( $event_type, true ) ) );

        if ( $event_type === 'thanks_page' && ! empty( $cfg['value'] ) ) {
            $currency   = esc_js( $cfg['currency'] ?? 'IDR' );
            $value      = esc_js( $cfg['value'] ?? '0' );
            $track_call = "fbq('track', 'Purchase', { value: {$value}, currency: '{$currency}' }, { eventID: '{$event_id}' });";
        } else {
            $track_call = "fbq('track', '{$event}', {}, { eventID: '{$event_id}' });";
        }

        // thanks_page: matikan autoConfig agar Meta tidak auto-fire PageView saat init
        $init_options = ( $event_type !== 'page_load' ) ? ", { autoConfig: false }" : '';

        return <<<HTML
<!-- Facebook Pixel Code -->
<script>
  !function(f, b, e, v, n, t, s) {
    if (f.fbq) return;
    n = f.fbq = function() {
      n.callMethod ?
        n.callMethod.apply(n, arguments) : n.queue.push(arguments)
    };
    if (!f._fbq) f._fbq = n;
    n.push = n;
    n.loaded = !0;
    n.version = '2.0';
    n.queue = [];
    t = b.createElement(e);
    t.async = !0;
    t.src = v;
    s = b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t, s)
  }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '{$pixel_id}'{$init_options});
  {$track_call}
</script>
<!-- End Facebook Pixel Code -->

HTML;
    }
}
