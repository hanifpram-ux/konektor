<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Tiktok {

    const API_ENDPOINT = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';

    public static function get_config( $campaign ) {
        $pixel = $campaign->pixel_config ? json_decode( $campaign->pixel_config, true ) : [];
        return $pixel['tiktok'] ?? [];
    }

    /**
     * Send event via TikTok Events API (server-side, full API).
     *
     * @param string $event_type  'page_load' | 'form_submit' | 'thanks_page'
     * @param array  $lead_data   Lead data (phone, email, source_url, etc.)
     * @param array  $tiktok_cfg  Config from get_config()
     */
    public static function send_event( $event_type, $lead_data, $tiktok_cfg ) {
        $pixel_id     = trim( $tiktok_cfg['pixel_id']      ?? '' );
        $access_token = trim( $tiktok_cfg['access_token']  ?? '' );
        if ( ! $pixel_id || ! $access_token ) return;

        $default_map = [
            'page_load'   => 'PageView',
            'form_submit' => 'SubmitForm',
            'thanks_page' => 'PlaceAnOrder',
        ];
        $cfg_key_map = [
            'page_load'   => 'page_load_event',
            'form_submit' => 'form_submit_event',
            'thanks_page' => 'thanks_page_event',
        ];
        $cfg_key    = $cfg_key_map[ $event_type ] ?? '';
        $event_name = trim( ( $cfg_key && ! empty( $tiktok_cfg[ $cfg_key ] ) )
            ? $tiktok_cfg[ $cfg_key ]
            : ( $default_map[ $event_type ] ?? 'PageView' )
        );

        $event_id = md5( uniqid( $event_name . $event_type, true ) );

        // User data — hash PII with SHA-256
        $user_data = [
            'ip'         => Konektor_Helper::get_client_ip(),
            'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
        ];

        // TikTok cookie _ttp untuk matching
        if ( ! empty( $_COOKIE['_ttp'] ) ) {
            $user_data['ttp'] = sanitize_text_field( $_COOKIE['_ttp'] );
        }

        if ( ! empty( $lead_data['email'] ) ) {
            $user_data['email'] = hash( 'sha256', strtolower( trim( $lead_data['email'] ) ) );
        }
        if ( ! empty( $lead_data['phone'] ) ) {
            $phone = preg_replace( '/[^0-9]/', '', $lead_data['phone'] );
            if ( str_starts_with( $phone, '0' ) ) $phone = '62' . substr( $phone, 1 );
            $user_data['phone_number'] = hash( 'sha256', '+' . $phone );
        }

        $event = [
            'event'      => $event_name,
            'event_id'   => $event_id,
            'timestamp'  => (string) time(),
            'user_data'  => $user_data,
            'page'       => [
                'url'      => $lead_data['source_url'] ?? home_url(),
                'referrer' => $lead_data['referrer']   ?? ( $_SERVER['HTTP_REFERER'] ?? '' ),
            ],
        ];

        // Properties untuk conversion events
        $conversion_events = [ 'Purchase', 'PlaceAnOrder', 'CompleteRegistration', 'SubmitForm', 'Subscribe' ];
        if ( in_array( $event_name, $conversion_events ) ) {
            $props = [];
            if ( ! empty( $tiktok_cfg['value'] ) ) {
                $props['value']    = (float) $tiktok_cfg['value'];
                $props['currency'] = sanitize_text_field( $tiktok_cfg['currency'] ?? 'IDR' );
            }
            if ( ! empty( $lead_data['product_name'] ) ) {
                $props['content_name'] = sanitize_text_field( $lead_data['product_name'] );
            }
            if ( $props ) $event['properties'] = $props;
        }

        $payload = [
            'pixel_code' => $pixel_id,
            'data'       => [ $event ],
        ];

        if ( ! empty( $tiktok_cfg['test_event_code'] ) ) {
            $payload['test_event_code'] = sanitize_text_field( $tiktok_cfg['test_event_code'] );
        }

        $response = wp_remote_post( self::API_ENDPOINT, [
            'body'    => wp_json_encode( $payload ),
            'headers' => [
                'Content-Type' => 'application/json',
                'Access-Token' => $access_token,
            ],
            'timeout' => 15,
        ] );

        Konektor_Logger::log( 'TikTok Events API', $event_name, self::API_ENDPOINT, $payload, $response );

        return $response;
    }

    public static function get_event_name( $tiktok_cfg, $event_type ) {
        $default_map = [
            'page_load'   => 'PageView',
            'form_submit' => 'SubmitForm',
            'thanks_page' => 'PlaceAnOrder',
        ];
        $cfg_key_map = [
            'page_load'   => 'page_load_event',
            'form_submit' => 'form_submit_event',
            'thanks_page' => 'thanks_page_event',
        ];
        $cfg_key = $cfg_key_map[ $event_type ] ?? '';
        return trim( ( $cfg_key && ! empty( $tiktok_cfg[ $cfg_key ] ) )
            ? $tiktok_cfg[ $cfg_key ]
            : ( $default_map[ $event_type ] ?? 'PageView' )
        );
    }

    /**
     * Browser-side TikTok Pixel script (ttq).
     * Renders the base pixel + the event for the given page type.
     *
     * @param object $campaign
     * @param string $event_type  'page_load' | 'form_submit' | 'thanks_page'
     */
    public static function get_script( $campaign, $event_type ) {
        $cfg = self::get_config( $campaign );
        if ( empty( $cfg['pixel_id'] ) ) return '';

        $pixel_id = esc_js( trim( $cfg['pixel_id'] ) );
        $event    = esc_js( self::get_event_name( $cfg, $event_type ) );
        $event_id = esc_js( md5( uniqid( $event_type, true ) ) );

        if ( $event_type === 'thanks_page' && ! empty( $cfg['value'] ) ) {
            $value      = esc_js( $cfg['value'] );
            $currency   = esc_js( $cfg['currency'] ?? 'IDR' );
            $track_call = "ttq.track('{$event}', { value: {$value}, currency: '{$currency}' }, { event_id: '{$event_id}' });";
        } else {
            $track_call = "ttq.track('{$event}', {}, { event_id: '{$event_id}' });";
        }

        // ttq.page() hanya untuk form page (page_load) — thanks page tidak boleh fire PageView
        $page_call = ( $event_type === 'page_load' ) ? "\n    ttq.page();" : '';

        return <<<HTML
<!-- TikTok Pixel Code -->
<script>
  !function(w, d, t) {
    w.TiktokAnalyticsObject = t;
    var ttq = w[t] = w[t] || [];
    ttq.methods = ["page", "track", "identify", "instances", "debug", "on", "off", "once", "ready", "alias", "group", "enableCookie", "disableCookie"], ttq.setAndDefer = function(t, e) {
      t[e] = function() {
        t.push([e].concat(Array.prototype.slice.call(arguments, 0)))
      }
    };
    for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]);
    ttq.instance = function(t) {
      for (var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) ttq.setAndDefer(e, ttq.methods[n]);
      return e
    }, ttq.load = function(e, n) {
      var i = "https://analytics.tiktok.com/i18n/pixel/events.js";
      ttq._i = ttq._i || {}, ttq._i[e] = [], ttq._i[e]._u = i, ttq._t = ttq._t || {}, ttq._t[e] = +new Date, ttq._o = ttq._o || {}, ttq._o[e] = n || {};
      var o = document.createElement("script");
      o.type = "text/javascript", o.async = !0, o.src = i + "?sdkid=" + e + "&lib=" + t;
      var a = document.getElementsByTagName("script")[0];
      a.parentNode.insertBefore(o, a)
    };
    ttq.load('{$pixel_id}');{$page_call}
  }(window, document, 'ttq');
  {$track_call}
</script>
<!-- End TikTok Pixel Code -->

HTML;
    }
}
