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
        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked( $campaign_id ) ) {
            $msg = $campaign->block_message ?: 'Akses Anda telah diblokir.';
            wp_send_json_error( [ 'message' => $msg, 'blocked' => true ], 403 );
        }

        $phone = Konektor_Helper::sanitize_phone( $_POST['phone'] ?? '' );
        $email = sanitize_email( $_POST['email'] ?? '' );

        // Double lead check
        if ( $campaign->double_lead_enabled && Konektor_Lead::check_double( $campaign_id, $phone, $email ) ) {
            $msg = $campaign->double_lead_message ?: 'Anda pernah mendaftar sebelumnya. Silahkan hubungi CS kami.';
            wp_send_json_error( [ 'message' => $msg, 'double' => true, 'redirect_cs' => true ], 200 );
        }

        // Pick operator
        $operator = Konektor_Rotator::pick( $campaign_id );

        $lead_data = [
            'campaign_id'    => $campaign_id,
            'operator_id'    => $operator ? $operator->id : null,
            'name'           => sanitize_text_field( $_POST['name'] ?? '' ),
            'email'          => $email,
            'phone'          => $phone,
            'address'        => sanitize_textarea_field( $_POST['address'] ?? '' ),
            'quantity'       => sanitize_text_field( $_POST['quantity'] ?? '' ),
            'custom_message' => sanitize_textarea_field( $_POST['custom_message'] ?? '' ),
            'product_name'   => $campaign->product_name,
            'source_url'     => esc_url_raw( $_POST['source_url'] ?? '' ),
        ];

        $lead_id = Konektor_Lead::create( $lead_data );

        // Log event
        Konektor_Analytics::log_event( $campaign_id, 'form_submit', $lead_id );

        // Meta CAPI
        $meta_cfg = Konektor_Meta::get_config( $campaign );
        if ( ! empty( $meta_cfg['token'] ) ) {
            Konektor_Meta::send_capi_event( $meta_cfg['form_submit_event'] ?? 'Lead', $lead_data, $meta_cfg );
        }

        // Telegram notification
        if ( $operator && $operator->telegram_chat_id ) {
            $lead_obj         = Konektor_Lead::get( $lead_id );
            Konektor_Telegram::notify_lead( $lead_obj, $operator, $campaign );
        }

        // Build redirect URL
        $thanks_cfg   = Konektor_Campaign::get_thanks_config( $campaign );
        $redirect_url = '';

        if ( $thanks_cfg['redirect_type'] === 'cs' && $operator ) {
            $redirect_url = Konektor_Rotator::get_redirect_url( $operator, $campaign, array_merge( $lead_data, [ 'operator_name' => $operator->name ] ) );
        } elseif ( $thanks_cfg['redirect_type'] === 'url' ) {
            $redirect_url = esc_url( $thanks_cfg['redirect_url'] ?? '' );
        }

        // Meta CAPI thanks page
        if ( ! empty( $meta_cfg['token'] ) ) {
            Konektor_Meta::send_capi_event( $meta_cfg['thanks_page_event'] ?? 'Purchase', $lead_data, $meta_cfg );
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

        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked( $campaign_id ) ) {
            $msg = $campaign->block_message ?: 'Akses Anda telah diblokir.';
            wp_send_json_error( [ 'message' => $msg, 'blocked' => true ], 403 );
        }

        $operator = Konektor_Rotator::pick( $campaign_id );
        if ( ! $operator ) wp_send_json_error( [ 'message' => 'Tidak ada operator tersedia.' ], 503 );

        Konektor_Analytics::log_event( $campaign_id, 'wa_click' );

        $thanks_cfg = Konektor_Campaign::get_thanks_config( $campaign );
        $url        = Konektor_Rotator::get_redirect_url( $operator, $campaign, [ 'product_name' => $campaign->product_name, 'operator_name' => $operator->name ] );

        wp_send_json_success( [ 'url' => $url ] );
    }

    public static function cs_get_leads( $request ) {
        $operator = $request->get_param( '_operator' );
        $leads    = Konektor_Lead::get_all( [
            'operator_id' => $operator->operator_id,
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
        return new WP_REST_Response( [ 'success' => (bool) $result ] );
    }

    public static function cs_block_lead( $request ) {
        $operator = $request->get_param( '_operator' );
        $lead_id  = (int) $request->get_param( 'id' );
        $reason   = sanitize_textarea_field( $request->get_param( 'reason' ) );
        Konektor_Blocker::also_block_lead( $lead_id, $operator->operator_id, $reason );
        return new WP_REST_Response( [ 'success' => true ] );
    }
}
