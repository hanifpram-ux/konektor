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
        $name    = $data['name']           ?? '';
        $email   = $data['email']          ?? '';
        $phone   = $data['phone']          ?? '';
        $address = $data['address']        ?? '';
        $catatan = $data['custom_message'] ?? '';
        $map = [
            // canonical (standalone-compatible aliases)
            '[name]'           => $name,
            '[phone]'          => $phone,
            '[email]'          => $email,
            '[address]'        => $address,
            '[custom_message]' => $catatan,
            // WP-native shortcodes
            '[cname]'          => $name,
            '[cemail]'         => $email,
            '[cphone]'         => $phone,
            '[caddress]'       => $address,
            '[catatan]'        => $catatan,
            '[product]'        => $data['product_name']  ?? '',
            '[quantity]'       => $data['quantity']       ?? '',
            '[oname]'          => $data['operator_name']  ?? '',
        ];
        return str_replace( array_keys( $map ), array_values( $map ), $template );
    }

    public static function is_domain_allowed( $campaign ) {
        $allowed = json_decode( $campaign->allowed_domains ?? '[]', true );
        if ( empty( $allowed ) ) return true;
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host    = parse_url( $referer, PHP_URL_HOST );
        foreach ( $allowed as $domain ) {
            if ( $host === $domain || ( strlen( $host ) > strlen( $domain ) && substr( $host, -( strlen( $domain ) + 1 ) ) === '.' . $domain ) ) return true;
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
     * Parse fbclid, ttclid, kwai_click_id, dan UTM params dari URL atau $_GET.
     * Side-effect: set cookie _fbc dari fbclid untuk Meta CAPI dedup.
     *
     * @param string|null $url  URL lengkap untuk di-parse (null = pakai $_GET)
     * @return array
     */
    public static function parse_tracking_params( $url = null ) {
        if ( $url !== null ) {
            $qs     = wp_parse_url( $url, PHP_URL_QUERY );
            $params = [];
            if ( $qs ) wp_parse_str( $qs, $params );
        } else {
            $params = $_GET;
        }

        $result = [];

        // Meta — fbclid + set _fbc cookie
        $fbclid = isset( $params['fbclid'] ) ? substr( trim( $params['fbclid'] ), 0, 500 ) : '';
        if ( $fbclid !== '' ) {
            $result['fbclid'] = $fbclid;
            $fbc = 'fb.1.' . ( time() * 1000 ) . '.' . $fbclid;
            if ( empty( $_COOKIE['_fbc'] ) ) {
                setcookie( '_fbc', $fbc, time() + 7776000, '/', '', is_ssl(), false );
                $_COOKIE['_fbc'] = $fbc;
            }
        }

        // TikTok
        $ttclid = isset( $params['ttclid'] ) ? substr( trim( $params['ttclid'] ), 0, 500 ) : '';
        if ( $ttclid !== '' ) $result['ttclid'] = $ttclid;

        // Snack/Kwai
        $kwai = isset( $params['kwai_click_id'] ) ? substr( trim( $params['kwai_click_id'] ), 0, 500 )
              : ( isset( $params['click_id'] )    ? substr( trim( $params['click_id'] ),      0, 500 ) : '' );
        if ( $kwai !== '' ) $result['kwai_click_id'] = $kwai;

        // UTM params
        foreach ( [ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_id' ] as $k ) {
            $v = isset( $params[ $k ] ) ? substr( strip_tags( trim( $params[ $k ] ) ), 0, 200 ) : '';
            if ( $v !== '' ) $result[ $k ] = $v;
        }

        return $result;
    }

    /**
     * Fire server-side CAPI events when a lead status changes.
     * Reads status_events mapping from campaign pixel_config.
     * Supports per-platform event names: { "contacted": { "meta": "Contact", "tiktok": "Contact" } }
     * or flat mapping: { "contacted": "Contact" } (same event for all platforms).
     * Falls back to legacy purchased/cancelled behavior if no mapping exists.
     */
    public static function fire_lead_status_capi( $lead_id, $status ) {
        $lead = Konektor_Lead::get( $lead_id );
        if ( ! $lead ) return;

        $campaign = Konektor_Campaign::get( $lead->campaign_id );
        if ( ! $campaign ) return;

        $pixel = $campaign->pixel_config ? json_decode( $campaign->pixel_config, true ) : [];
        $status_events = $pixel['status_events'] ?? [];

        $mapping = $status_events[ $status ] ?? [];
        if ( empty( $mapping ) ) {
            // Backward compatibility: if no mapping for purchased/cancelled, derive from legacy config
            if ( ! in_array( $status, [ 'purchased', 'cancelled' ], true ) ) return;
            $meta_cfg = Konektor_Meta::get_config( $campaign );
            $event_name = $status === 'purchased'
                ? ( $meta_cfg['thanks_page_event'] ?? '' )
                : ( $meta_cfg['form_submit_event'] ?? '' );
            if ( ! $event_name ) return;
            $mapping = ['meta' => $event_name, 'tiktok' => $event_name, 'snack' => $event_name];
        }

        // Flat string mapping → convert to per-platform
        if ( is_string( $mapping ) ) {
            $mapping = ['meta' => $mapping, 'tiktok' => $mapping, 'snack' => $mapping];
        }

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

        $use_browser = function( array $cfg, $token_key ) {
            $mode = $cfg['pixel_mode'] ?? 'capi';
            if ( $mode === 'pixel' ) return true;
            return empty( $cfg[ $token_key ] );
        };

        if ( ! empty( $mapping['meta'] ) && ! empty( $meta_cfg['token'] ) && ! $use_browser( $meta_cfg, 'token' ) ) {
            Konektor_Meta::send_capi_event( $mapping['meta'], $lead_arr, $meta_cfg );
        }
        if ( ! empty( $mapping['tiktok'] ) && ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) && ! $use_browser( $tiktok_cfg, 'access_token' ) ) {
            Konektor_Tiktok::send_event( $mapping['tiktok'], $lead_arr, $tiktok_cfg );
        }
        if ( ! empty( $mapping['snack'] ) && ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) && ! $use_browser( $snack_cfg, 'access_token' ) ) {
            Konektor_Snack::send_event( $mapping['snack'], $lead_arr, $snack_cfg );
        }
    }

    /**
     * Rate limiter using WordPress transients.
     * Returns true if allowed, false if rate limit exceeded.
     */
    public static function rate_limit( $key, $limit = 20, $window = 60 ) {
        $tk    = 'knk_rl_' . md5( $key );
        $count = (int) get_transient( $tk );
        if ( $count === 0 ) {
            set_transient( $tk, 1, $window );
            return true;
        }
        if ( $count >= $limit ) return false;
        set_transient( $tk, $count + 1, $window );
        return true;
    }

    /**
     * Detect automated bots/crawlers that must not create leads or fire pixels.
     */
    public static function is_bot_request() {
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if ( $ua === '' ) return true;

        $bots = [
            'facebookexternalhit', 'Facebot',
            'Twitterbot', 'LinkedInBot', 'WhatsApp', 'TelegramBot',
            'Discordbot', 'Slackbot', 'slack-imgproxy',
            'Pinterest', 'Googlebot', 'Googlebot-Image',
            'bingbot', 'BingPreview', 'DuckDuckBot',
            'YandexBot', 'Baiduspider', 'ia_archiver',
            'AhrefsBot', 'SemrushBot', 'MJ12bot',
            'Bytespider',
            'HeadlessChrome', 'PhantomJS',
            'python-requests', 'Go-http-client',
            'curl/', 'Wget/',
        ];

        foreach ( $bots as $bot ) {
            if ( stripos( $ua, $bot ) !== false ) return true;
        }
        return false;
    }

    /**
     * Sanitize a CSS property value — strip </style> breakout attempts.
     */
    public static function sanitize_css_value( $v ) {
        $v = preg_replace( '#</?style[^>]*>#i', '', (string) $v );
        $v = preg_replace( '#<script[^>]*>.*?</script>#is', '', $v );
        $v = preg_replace( '#[\x00-\x08\x0b\x0c\x0e-\x1f]#', '', $v );
        return $v;
    }
}
