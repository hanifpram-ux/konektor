<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_API {

    public static function init() {
        add_action( 'wp_ajax_nopriv_konektor_submit_form', [ __CLASS__, 'handle_form_submit' ] );
        add_action( 'wp_ajax_konektor_submit_form',        [ __CLASS__, 'handle_form_submit' ] );
        add_action( 'wp_ajax_nopriv_konektor_wa_click',    [ __CLASS__, 'handle_wa_click' ] );
        add_action( 'wp_ajax_konektor_wa_click',           [ __CLASS__, 'handle_wa_click' ] );

        // Telegram webhook
        add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );

        // CS Panel REST endpoints
        add_action( 'rest_api_init', [ __CLASS__, 'register_cs_routes' ] );
    }

    public static function register_rest_routes() {
        register_rest_route( 'konektor/v1', '/telegram-webhook', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'telegram_webhook' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public static function register_cs_routes() {
        register_rest_route( 'konektor/v1', '/cs/leads', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'cs_get_leads' ],
            'permission_callback' => [ __CLASS__, 'verify_cs_token' ],
        ] );
        register_rest_route( 'konektor/v1', '/cs/leads/(?P<id>\d+)/status', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'cs_update_status' ],
            'permission_callback' => [ __CLASS__, 'verify_cs_token' ],
        ] );
        register_rest_route( 'konektor/v1', '/cs/leads/(?P<id>\d+)/block', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'cs_block_lead' ],
            'permission_callback' => [ __CLASS__, 'verify_cs_token' ],
        ] );
    }

    public static function verify_cs_token( $request ) {
        $token    = sanitize_text_field( $request->get_header( 'X-CS-Token' ) ?? $request->get_param( 'token' ) );
        $operator = Konektor_Operator::verify_panel_token( $token );
        if ( $operator ) {
            $request->set_param( '_operator', $operator );
            return true;
        }
        return new WP_Error( 'unauthorized', 'Token tidak valid', [ 'status' => 401 ] );
    }

    public static function telegram_webhook( $request ) {
        $data = $request->get_json_params();
        Konektor_Telegram::handle_webhook( $data );
        return new WP_REST_Response( [ 'ok' => true ] );
    }

    public static function handle_form_submit() {
        check_ajax_referer( 'konektor_nonce', 'nonce' );

        // Rate limit: 20 submit per IP per menit
        $ip = Konektor_Helper::get_client_ip();
        if ( ! Konektor_Helper::rate_limit( 'submit_' . $ip, 20, 60 ) ) {
            wp_send_json_error( [ 'message' => 'Terlalu banyak permintaan. Coba lagi sebentar.' ], 429 );
        }

        $campaign_id = (int) ( $_POST['campaign_id'] ?? 0 );
        $campaign    = Konektor_Campaign::get( $campaign_id );

        if ( ! $campaign || $campaign->status !== 'active' ) {
            wp_send_json_error( [ 'message' => 'Kampanye tidak aktif.' ], 400 );
        }

        // Domain check
        if ( ! Konektor_Helper::is_domain_allowed( $campaign ) ) {
            wp_send_json_error( [ 'message' => 'Domain tidak diizinkan.' ], 403 );
        }

        // Block check
        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked() ) {
            $msg = $campaign->block_message ?: 'Akses Anda telah diblokir.';
            wp_send_json_error( [ 'message' => $msg, 'blocked' => true ], 403 );
        }

        $phone = Konektor_Helper::sanitize_phone( $_POST['phone'] ?? '' );
        $email = sanitize_email( $_POST['email'] ?? '' );

        // Validate phone: min 10 digits
        if ( $phone !== '' ) {
            $digits = preg_replace( '/\D/', '', $phone );
            if ( strlen( $digits ) < 10 ) {
                wp_send_json_error( [ 'message' => 'Masukkan nomor HP yang valid (min. 10 digit).' ], 400 );
            }
        }

        // Validate email format
        if ( $email !== '' && ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => 'Masukkan alamat email yang valid (contoh: nama@domain.com).' ], 400 );
        }

        $source_url = esc_url_raw( substr( $_POST['source_url'] ?? '', 0, 2000 ) );
        $vid        = sanitize_text_field( substr( $_POST['_vid'] ?? '', 0, 128 ) );
        $ip         = Konektor_Helper::get_client_ip();

        // Referrer: dari form data (JS kirim document.referrer landing page), fallback HTTP_REFERER
        $referrer = sanitize_text_field( substr( $_POST['referrer'] ?? '', 0, 2000 ) );
        if ( $referrer === '' ) $referrer = sanitize_text_field( substr( $_SERVER['HTTP_REFERER'] ?? '', 0, 2000 ) );

        // Click ID: dari form data atau fallback ke tracking params
        // Parse dulu tracking params agar fbclid tersedia sebagai fallback
        $tracking_params_pre = Konektor_Helper::parse_tracking_params( esc_url_raw( substr( $_POST['source_url'] ?? '', 0, 2000 ) ) ?: null );
        $click_id = sanitize_text_field(
            $_POST['click_id'] ?? ( $_GET['click_id'] ?? ( $_GET['clickid'] ?? ( $tracking_params_pre['fbclid'] ?? '' ) ) )
        );

        // Gunakan tracking params yang sudah di-parse sebelumnya
        $tracking_params = $tracking_params_pre;

        // Extra data: tracking params + non-standard form fields
        $std_keys  = [ 'name', 'phone', 'email', 'address', 'quantity', 'custom_message', '_vid', 'source_url', 'referrer', 'click_id', 'nonce', 'action', 'campaign_id' ];
        $extra_data = $tracking_params;
        foreach ( $_POST as $k => $v ) {
            if ( ! in_array( $k, $std_keys, true ) ) {
                $extra_data[ sanitize_key( $k ) ] = sanitize_text_field( (string) $v );
            }
        }

        // fbclid dari form (JS forward hidden input) jika belum ada dari source_url
        if ( empty( $extra_data['fbclid'] ) && ! empty( $_POST['fbclid'] ) ) {
            $extra_data['fbclid'] = sanitize_text_field( $_POST['fbclid'] );
        }

        // Double lead check (6-field scope: phone, email, vid, ip, source_url)
        $is_double = $campaign->double_lead_enabled
            ? Konektor_Lead::check_double( $campaign_id, $phone, $email, $vid, $ip, $source_url )
            : false;

        // Pick operator (duplikat tetap ikut rotator sesuai bobot)
        $operator = Konektor_Rotator::pick( $campaign_id );

        $lead_data = [
            'campaign_id'    => $campaign_id,
            'operator_id'    => $operator ? $operator->id : null,
            'name'           => sanitize_text_field( substr( $_POST['name'] ?? '', 0, 200 ) ),
            'email'          => $email,
            'phone'          => $phone,
            'address'        => sanitize_textarea_field( substr( $_POST['address'] ?? '', 0, 500 ) ),
            'quantity'       => sanitize_text_field( substr( $_POST['quantity'] ?? '', 0, 100 ) ),
            'custom_message' => sanitize_textarea_field( substr( $_POST['custom_message'] ?? '', 0, 2000 ) ),
            'product_name'   => $campaign->product_name,
            'source_url'     => $source_url,
            'referrer'       => $referrer,
            'click_id'       => $click_id ?: ( $extra_data['fbclid'] ?? '' ),
            '_vid'           => $vid,
            'extra_data'     => $extra_data,
            'ip'             => $ip,
            'user_agent'     => substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 512 ),
        ];

        $lead_id = Konektor_Lead::create( $lead_data );
        if ( $is_double ) Konektor_Lead::mark_double( $lead_id );

        // Log event
        Konektor_Analytics::log_event( $campaign_id, 'form_submit', $lead_id );

        // Server-side CAPI — form_submit (skip untuk double lead)
        $meta_cfg   = Konektor_Meta::get_config( $campaign );
        $tiktok_cfg = Konektor_Tiktok::get_config( $campaign );
        $snack_cfg  = Konektor_Snack::get_config( $campaign );

        if ( ! $is_double ) {
            if ( ! empty( $meta_cfg['pixel_id'] ) && ! empty( $meta_cfg['token'] ) ) {
                Konektor_Meta::send_capi_event( $meta_cfg['form_submit_event'] ?? '', $lead_data, $meta_cfg );
            }
            if ( ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) ) {
                Konektor_Tiktok::send_event( 'form_submit', $lead_data, $tiktok_cfg );
            }
            if ( ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) ) {
                Konektor_Snack::send_event( Konektor_Snack::get_event_name( $snack_cfg, 'form_submit' ), $lead_data, $snack_cfg );
            }
        }

        // Telegram notification (skip duplikat)
        if ( ! $is_double && $operator && $operator->telegram_chat_id ) {
            $lead_obj = Konektor_Lead::get( $lead_id );
            Konektor_Telegram::notify_lead( $lead_obj, $operator, $campaign );
        }

        // Jika duplikat — return pesan double sekarang (setelah lead tersimpan)
        if ( $is_double ) {
            $msg = $campaign->double_lead_message ?: 'Anda pernah mendaftar sebelumnya. Silahkan hubungi CS kami.';
            $thanks_cfg = Konektor_Campaign::get_thanks_config( $campaign );
            wp_send_json_success( [
                'message'       => $msg,
                'double'        => true,
                'redirect_url'  => '',
                'redirect_type' => $thanks_cfg['redirect_type'] ?? 'none',
                'delay'         => (int) ( $thanks_cfg['delay_redirect'] ?? 3 ),
                'lead_id'       => $lead_id,
            ] );
            return;
        }

        // Build redirect URL
        $thanks_cfg   = Konektor_Campaign::get_thanks_config( $campaign );
        $redirect_url = '';

        if ( $thanks_cfg['redirect_type'] === 'cs' && $operator ) {
            $redirect_url = Konektor_Rotator::get_redirect_url( $operator, $campaign, array_merge( $lead_data, [ 'operator_name' => $operator->name ] ) );
            Konektor_Analytics::log_event( $campaign_id, 'wa_click', $lead_id );
        } elseif ( $thanks_cfg['redirect_type'] === 'url' ) {
            $redirect_url = esc_url( $thanks_cfg['redirect_url'] ?? '' );
        }

        wp_send_json_success( [
            'message'       => $thanks_cfg['description'] ?? 'Terima kasih!',
            'redirect_url'  => $redirect_url,
            'redirect_type' => $thanks_cfg['redirect_type'] ?? 'none',
            'delay'         => (int) ( $thanks_cfg['delay_redirect'] ?? 3 ),
            'lead_id'       => $lead_id,
        ] );
    }

    public static function handle_wa_click() {
        check_ajax_referer( 'konektor_nonce', 'nonce' );

        $campaign_id = (int) ( $_POST['campaign_id'] ?? 0 );
        $campaign    = Konektor_Campaign::get( $campaign_id );
        if ( ! $campaign ) wp_send_json_error( [], 404 );

        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked() ) {
            $msg = $campaign->block_message ?: 'Akses Anda telah diblokir.';
            wp_send_json_error( [ 'message' => $msg, 'blocked' => true ], 403 );
        }

        $operator = Konektor_Rotator::pick( $campaign_id );
        if ( ! $operator ) wp_send_json_error( [ 'message' => 'Tidak ada operator tersedia.' ], 503 );

        $vid        = sanitize_text_field( substr( $_POST['_vid'] ?? '', 0, 128 ) );
        $source_url = esc_url_raw( substr( $_POST['source_url'] ?? '', 0, 2000 ) );
        $referrer   = sanitize_text_field( substr( $_POST['referrer'] ?? ( $_SERVER['HTTP_REFERER'] ?? '' ), 0, 2000 ) );
        $ip         = Konektor_Helper::get_client_ip();

        // Parse tracking params dari source_url
        $tracking = Konektor_Helper::parse_tracking_params( $source_url ?: null );
        $click_id = sanitize_text_field( $_POST['click_id'] ?? ( $_GET['click_id'] ?? ( $tracking['fbclid'] ?? '' ) ) );

        // Session guard: cegah duplicate lead + events pada back-navigation
        $sess_key = 'knk_wa_' . $campaign_id . '_' . md5( $vid ?: session_id() );
        if ( ! session_id() ) @session_start();
        if ( isset( $_SESSION[ $sess_key ] ) ) {
            // Back-navigation: langsung return URL tanpa buat lead baru
            $url = Konektor_Rotator::get_redirect_url( $operator, $campaign, [ 'product_name' => $campaign->product_name, 'operator_name' => $operator->name ] );
            wp_send_json_success( [ 'url' => $url ] );
            return;
        }

        // Cross-session repeat check via double-lead
        $is_repeat = $campaign->double_lead_enabled
            ? Konektor_Lead::check_double( $campaign_id, '', '', $vid, $ip, $source_url )
            : false;

        $lead_data = [
            'campaign_id' => $campaign_id,
            'operator_id' => $operator->id,
            'name'        => '',
            'phone'       => '',
            'email'       => '',
            'source_url'  => $source_url,
            'referrer'    => $referrer,
            'click_id'    => $click_id,
            '_vid'        => $vid,
            'extra_data'  => ! empty( $tracking ) ? $tracking : [],
            'ip'          => $ip,
            'user_agent'  => substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 512 ),
        ];

        $lead_id = Konektor_Lead::create( $lead_data );
        if ( $is_repeat ) Konektor_Lead::mark_double( $lead_id );

        // Session guard: simpan lead_id agar back-navigation tidak buat lead baru
        $_SESSION[ $sess_key ] = $lead_id;

        Konektor_Analytics::log_event( $campaign_id, 'form_submit', $lead_id );
        Konektor_Analytics::log_event( $campaign_id, 'wa_click', $lead_id );

        if ( ! $is_repeat ) {
            $meta_cfg = Konektor_Meta::get_config( $campaign );
            if ( ! empty( $meta_cfg['token'] ) ) {
                Konektor_Meta::send_capi_event( $meta_cfg['form_submit_event'] ?? '', $lead_data, $meta_cfg );
            }
            $tiktok_cfg = Konektor_Tiktok::get_config( $campaign );
            if ( ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) ) {
                Konektor_Tiktok::send_event( 'form_submit', $lead_data, $tiktok_cfg );
            }
            $snack_cfg = Konektor_Snack::get_config( $campaign );
            if ( ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) ) {
                Konektor_Snack::send_event( Konektor_Snack::get_event_name( $snack_cfg, 'form_submit' ), $lead_data, $snack_cfg );
            }
        }

        $url = Konektor_Rotator::get_redirect_url( $operator, $campaign, [ 'product_name' => $campaign->product_name, 'operator_name' => $operator->name ] );
        wp_send_json_success( [ 'url' => $url ] );
    }

    public static function cs_get_leads( $request ) {
        $operator = $request->get_param( '_operator' );
        $leads    = Konektor_Lead::get_all( [
            'operator_id' => $operator->operator_id,
            'camp_type'   => 'form',
            'per_page'    => 20,
            'page'        => max( 1, (int) $request->get_param( 'page' ) ),
        ] );

        $out = [];
        foreach ( $leads as $l ) {
            $d = Konektor_Lead::decrypt_lead( clone $l );
            global $wpdb;
            $camp_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}konektor_campaigns WHERE id=%d", $l->campaign_id ) );
            $out[] = [
                'id'       => $l->id,
                'campaign' => $camp_name,
                'name'     => $d->name,
                'phone'    => $d->phone,
                'email'    => $d->email,
                'address'  => $d->address,
                'quantity' => $d->quantity,
                'message'  => $d->custom_message,
                'status'   => $l->status,
                'double'   => (bool) $l->is_double,
                'date'     => $l->created_at,
            ];
        }

        return new WP_REST_Response( [ 'leads' => $out ] );
    }

    public static function cs_update_status( $request ) {
        $operator = $request->get_param( '_operator' );
        $lead_id  = (int) $request->get_param( 'id' );
        $status   = sanitize_text_field( $request->get_param( 'status' ) );
        $note     = sanitize_textarea_field( $request->get_param( 'note' ) );
        $result   = Konektor_Lead::update_status( $lead_id, $status, $note, $operator->operator_id );
        // Fire server-side CAPI for purchased / cancelled status changes
        if ( $result ) {
            Konektor_Helper::fire_lead_status_capi( $lead_id, $status );
        }
        return new WP_REST_Response( [ 'success' => (bool) $result ] );
    }

    public static function cs_block_lead( $request ) {
        $operator = $request->get_param( '_operator' );
        $lead_id  = (int) $request->get_param( 'id' );
        $reason   = sanitize_textarea_field( $request->get_param( 'reason' ) );
        Konektor_Blocker::also_block_lead( $lead_id, $operator->operator_id, $reason );
        // Fire server-side CAPI for cancelled (block is treated as invalid lead)
        Konektor_Helper::fire_lead_status_capi( $lead_id, 'cancelled' );
        return new WP_REST_Response( [ 'success' => true ] );
    }
}
