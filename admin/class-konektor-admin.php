<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Admin {

    public static function init() {
        add_action( 'admin_menu',   [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_head',   [ __CLASS__, 'inline_styles' ] );
        add_action( 'admin_footer', [ __CLASS__, 'inline_scripts' ] );

        add_action( 'wp_ajax_konektor_admin_save_campaign',   [ __CLASS__, 'ajax_save_campaign' ] );
        add_action( 'wp_ajax_konektor_admin_delete_campaign', [ __CLASS__, 'ajax_delete_campaign' ] );
        add_action( 'wp_ajax_konektor_admin_save_operator',   [ __CLASS__, 'ajax_save_operator' ] );
        add_action( 'wp_ajax_konektor_admin_delete_operator', [ __CLASS__, 'ajax_delete_operator' ] );
        add_action( 'wp_ajax_konektor_admin_save_settings',   [ __CLASS__, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_konektor_admin_export_leads',    [ __CLASS__, 'ajax_export_leads' ] );
        add_action( 'wp_ajax_konektor_admin_update_lead',     [ __CLASS__, 'ajax_update_lead' ] );
        add_action( 'wp_ajax_konektor_admin_get_lead',        [ __CLASS__, 'ajax_get_lead' ] );
        add_action( 'wp_ajax_konektor_admin_unblock',         [ __CLASS__, 'ajax_unblock' ] );
        add_action( 'wp_ajax_konektor_admin_block_lead',      [ __CLASS__, 'ajax_block_lead' ] );
        add_action( 'wp_ajax_konektor_admin_delete_lead',     [ __CLASS__, 'ajax_delete_lead' ] );
        add_action( 'wp_ajax_konektor_admin_gen_token',       [ __CLASS__, 'ajax_gen_token' ] );
        add_action( 'wp_ajax_konektor_admin_test_pixel',      [ __CLASS__, 'ajax_test_pixel' ] );
        add_action( 'wp_ajax_konektor_admin_debug_campaign',   [ __CLASS__, 'ajax_debug_campaign' ] );
    }

    public static function register_menu() {
        add_menu_page( 'Konektor', 'Konektor', 'manage_options', 'konektor', [ __CLASS__, 'page_dashboard' ], 'dashicons-networking', 30 );
        add_submenu_page( 'konektor', 'Dashboard',     'Dashboard',     'manage_options', 'konektor',            [ __CLASS__, 'page_dashboard' ] );
        add_submenu_page( 'konektor', 'Kampanye',      'Kampanye',      'manage_options', 'konektor-campaigns',  [ __CLASS__, 'page_campaigns' ] );
        add_submenu_page( 'konektor', 'Operator / CS', 'Operator / CS', 'manage_options', 'konektor-operators',  [ __CLASS__, 'page_operators' ] );
        add_submenu_page( 'konektor', 'Leads',         'Leads',         'manage_options', 'konektor-leads',      [ __CLASS__, 'page_leads' ] );
        add_submenu_page( 'konektor', 'Analitik',      'Analitik',      'manage_options', 'konektor-analytics',  [ __CLASS__, 'page_analytics' ] );
        add_submenu_page( 'konektor', 'Pengaturan',    'Pengaturan',    'manage_options', 'konektor-settings',   [ __CLASS__, 'page_settings' ] );
        add_submenu_page( 'konektor', 'Log API',        'Log API',       'manage_options', 'konektor-log',        [ __CLASS__, 'page_log' ] );
        add_submenu_page( 'konektor', 'Panduan',       'Panduan',       'manage_options', 'konektor-guide',      [ __CLASS__, 'page_guide' ] );
        add_submenu_page( 'konektor', 'Tentang',       'Tentang',       'manage_options', 'konektor-about',      [ __CLASS__, 'page_about' ] );
    }

    /* ── Inline CSS (only on Konektor pages) ── */
    public static function inline_styles() {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'konektor' ) === false ) return;
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">' . "\n";
        echo '<style>' . self::get_css() . '</style>' . "\n";
    }

    /* ── Inline JS (only on Konektor pages) ── */
    public static function inline_scripts() {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'konektor' ) === false ) return;
        $nonce = wp_create_nonce( 'konektor_admin_nonce' );
        $ajax  = admin_url( 'admin-ajax.php' );
        echo '<script>var KonektorAdmin={nonce:"' . esc_js( $nonce ) . '",ajax_url:"' . esc_js( $ajax ) . '"};' . "\n";
        echo self::get_js();
        echo '</script>' . "\n";
    }

    /* ────────────────────────────────────────
       PAGE CONTROLLERS
    ──────────────────────────────────────── */
    public static function page_dashboard() {
        $summary = Konektor_Analytics::get_summary();
        include KONEKTOR_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public static function page_campaigns() {
        $action   = $_GET['action'] ?? 'list';
        $id       = (int) ( $_GET['id'] ?? 0 );
        $campaign = $id ? Konektor_Campaign::get( $id ) : null;

        if ( $action === 'new' && empty( $_GET['type'] ) ) {
            // Show type picker before the edit form
            include KONEKTOR_PLUGIN_DIR . 'admin/views/campaigns/new.php';
        } elseif ( $action === 'edit' || $action === 'new' ) {
            $operators = Konektor_Operator::get_all();
            $assigned  = $campaign ? Konektor_Campaign::get_operators( $campaign->id ) : [];
            // Pre-set type when coming from type picker
            $preset_type = $_GET['type'] ?? ( $campaign->type ?? 'form' );
            include KONEKTOR_PLUGIN_DIR . 'admin/views/campaigns/edit.php';
        } else {
            $campaigns = Konektor_Campaign::get_all();
            include KONEKTOR_PLUGIN_DIR . 'admin/views/campaigns/list.php';
        }
    }

    public static function page_operators() {
        $action   = $_GET['action'] ?? 'list';
        $id       = (int) ( $_GET['id'] ?? 0 );
        $operator = $id ? Konektor_Operator::get( $id ) : null;

        if ( $action === 'edit' || $action === 'new' ) {
            $campaigns = $operator ? Konektor_Operator::get_campaigns_for_operator( $operator->id ) : [];
            include KONEKTOR_PLUGIN_DIR . 'admin/views/operators/edit.php';
        } else {
            $operators = Konektor_Operator::get_all();
            include KONEKTOR_PLUGIN_DIR . 'admin/views/operators/list.php';
        }
    }

    public static function page_leads() {
        $campaign_id = (int) ( $_GET['campaign_id'] ?? 0 );
        $operator_id = (int) ( $_GET['operator_id'] ?? 0 );
        $status      = sanitize_text_field( $_GET['status'] ?? '' );
        $page        = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $campaigns   = Konektor_Campaign::get_all();
        $operators   = Konektor_Operator::get_all();

        $args = [ 'page' => $page ];
        if ( $campaign_id ) $args['campaign_id'] = $campaign_id;
        if ( $operator_id ) $args['operator_id'] = $operator_id;
        if ( $status )      $args['status']       = $status;

        $leads = Konektor_Lead::get_all( $args );
        $total = Konektor_Lead::count( $args );

        include KONEKTOR_PLUGIN_DIR . 'admin/views/leads.php';
    }

    public static function page_analytics() {
        $summary      = Konektor_Analytics::get_summary();
        $per_campaign = Konektor_Analytics::get_per_campaign();
        $per_operator = Konektor_Analytics::get_per_operator();
        $daily        = Konektor_Analytics::get_daily( 30 );
        include KONEKTOR_PLUGIN_DIR . 'admin/views/analytics.php';
    }

    public static function page_settings() {
        $settings = Konektor_Settings::get_all();
        $blocked  = Konektor_Blocker::get_all();
        include KONEKTOR_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public static function page_guide() {
        include KONEKTOR_PLUGIN_DIR . 'admin/views/guide.php';
    }

    public static function page_log() {
        // Handle clear log action
        if ( isset( $_POST['knk_clear_log'] ) && check_admin_referer( 'knk_clear_log' ) ) {
            Konektor_Logger::clear();
            echo '<div class="notice notice-success"><p>Log berhasil dihapus.</p></div>';
        }
        include KONEKTOR_PLUGIN_DIR . 'admin/views/log.php';
    }

    public static function page_about() {
        include KONEKTOR_PLUGIN_DIR . 'admin/views/about.php';
    }

    /* ────────────────────────────────────────
       AJAX HANDLERS
    ──────────────────────────────────────── */
    public static function ajax_save_campaign() {
        self::verify_nonce();
        $data = wp_unslash( $_POST );

        foreach ( ['form_config','thanks_page_config','pixel_config','operators','allowed_domains'] as $key ) {
            if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
                $data[ $key ] = json_decode( $data[ $key ], true ) ?: [];
            }
        }

        $id = (int) ( $data['id'] ?? 0 );
        $id = Konektor_Campaign::save( $data, $id );
        wp_send_json_success( [ 'id' => $id ] );
    }

    public static function ajax_delete_campaign() {
        self::verify_nonce();
        Konektor_Campaign::delete( (int) ( $_POST['id'] ?? 0 ) );
        wp_send_json_success();
    }

    public static function ajax_save_operator() {
        self::verify_nonce();
        $data = $_POST;

        // work_hours comes as JSON string
        if ( ! empty( $data['work_hours'] ) && is_string( $data['work_hours'] ) ) {
            $data['work_hours'] = json_decode( wp_unslash( $data['work_hours'] ), true ) ?: [];
        }

        $id = (int) ( $data['id'] ?? 0 );
        $id = Konektor_Operator::save( $data, $id );
        wp_send_json_success( [ 'id' => $id ] );
    }

    public static function ajax_delete_operator() {
        self::verify_nonce();
        Konektor_Operator::delete( (int) ( $_POST['id'] ?? 0 ) );
        wp_send_json_success();
    }

    public static function ajax_save_settings() {
        self::verify_nonce();
        $data = $_POST;

        // allowed_domains_global comes as JSON string
        if ( ! empty( $data['allowed_domains_global'] ) && is_string( $data['allowed_domains_global'] ) ) {
            $decoded = json_decode( wp_unslash( $data['allowed_domains_global'] ), true );
            if ( is_array( $decoded ) ) {
                $data['allowed_domains_global'] = wp_json_encode( $decoded );
            }
        }

        Konektor_Settings::save( $data );
        wp_send_json_success();
    }

    public static function ajax_export_leads() {
        self::verify_nonce();
        $args = [];
        if ( ! empty( $_GET['campaign_id'] ) ) $args['campaign_id'] = (int) $_GET['campaign_id'];
        if ( ! empty( $_GET['operator_id'] ) ) $args['operator_id'] = (int) $_GET['operator_id'];
        if ( ! empty( $_GET['status'] ) )       $args['status']       = sanitize_text_field( $_GET['status'] );

        $rows = Konektor_Lead::export_csv( $args );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="konektor-leads-' . date( 'Ymd-His' ) . '.csv"' );
        $out = fopen( 'php://output', 'w' );
        foreach ( $rows as $row ) fputcsv( $out, $row );
        fclose( $out );
        exit;
    }

    public static function ajax_update_lead() {
        self::verify_nonce();
        $lead_id = (int) ( $_POST['id'] ?? 0 );
        $status  = sanitize_text_field( $_POST['status'] ?? 'new' );
        $note    = sanitize_textarea_field( $_POST['note'] ?? '' );
        Konektor_Lead::update_status( $lead_id, $status, $note );
        wp_send_json_success();
    }

    public static function ajax_get_lead() {
        self::verify_nonce();
        $id   = (int) ( $_POST['id'] ?? 0 );
        $lead = Konektor_Lead::get( $id );
        if ( ! $lead ) wp_send_json_error( [ 'message' => 'Not found' ] );
        $l    = Konektor_Lead::decrypt_lead( clone $lead );

        $camp = $lead->campaign_id ? Konektor_Campaign::get( $lead->campaign_id ) : null;
        $op   = $lead->operator_id ? Konektor_Operator::get( $lead->operator_id ) : null;

        wp_send_json_success( [
            'id'             => (int) $l->id,
            'name'           => $l->name ?? '',
            'phone'          => $l->phone ?? '',
            'email'          => $l->email ?? '',
            'address'        => $l->address ?? '',
            'quantity'       => $l->quantity ?? '',
            'custom_message' => $l->custom_message ?? '',
            'campaign_name'  => $camp ? $camp->name : '',
            'operator_name'  => $op   ? $op->name   : '',
            'status'         => $l->status,
            'is_double'      => (bool) $l->is_double,
            'ip_address'     => $lead->ip_address ?? '',
            'cookie_id'      => $lead->cookie_id  ?? '',
            'user_agent'     => $lead->user_agent  ?? '',
            'referrer'       => $lead->referrer    ?? '',
            'source_url'     => $lead->source_url  ?? '',
            'created_at'     => $l->created_at,
        ] );
    }

    public static function ajax_unblock() {
        self::verify_nonce();
        Konektor_Blocker::unblock( (int) ( $_POST['id'] ?? 0 ) );
        wp_send_json_success();
    }

    public static function ajax_block_lead() {
        self::verify_nonce();
        $lead_id = (int) ( $_POST['lead_id'] ?? 0 );
        $reason  = sanitize_textarea_field( $_POST['reason'] ?? '' );
        Konektor_Blocker::also_block_lead( $lead_id, null, $reason );
        wp_send_json_success();
    }

    public static function ajax_delete_lead() {
        self::verify_nonce();
        Konektor_Lead::delete( (int) ( $_POST['id'] ?? 0 ) );
        wp_send_json_success();
    }

    public static function ajax_gen_token() {
        self::verify_nonce();
        $op_id = (int) ( $_POST['operator_id'] ?? 0 );
        $token = Konektor_Operator::generate_panel_token( $op_id );
        $base  = Konektor_Helper::get_setting( 'base_slug', 'konektor' );
        wp_send_json_success( [ 'url' => home_url( "/{$base}/cs-panel/?token={$token}" ) ] );
    }

    public static function ajax_test_pixel() {
        self::verify_nonce();

        $platform   = sanitize_text_field( $_POST['platform']   ?? '' );
        $event_type = sanitize_text_field( $_POST['event_type'] ?? 'page_load' );

        // pixel_config dikirim JS sudah lengkap termasuk test_event_code di dalamnya
        $pixel_raw = wp_unslash( $_POST['pixel_config'] ?? '' );
        $pixel_cfg = is_string( $pixel_raw ) ? ( json_decode( $pixel_raw, true ) ?: [] ) : [];

        // Lead data dummy untuk test
        $lead_data = [
            'source_url' => home_url(),
            'referrer'   => '',
            'name'       => 'Test User',
            'email'      => 'test@example.com',
            'phone'      => '081234567890',
        ];

        $result  = null;
        $log     = [];

        if ( $platform === 'meta' ) {
            // Ambil cfg SETELAH inject test_event_code
            $cfg = $pixel_cfg['meta'] ?? [];
            if ( empty( $cfg['pixel_id'] ) || empty( $cfg['token'] ) ) {
                wp_send_json_error( [ 'message' => 'Pixel ID dan Access Token Meta wajib diisi.' ] );
            }
            $event_map = [
                'page_load'   => $cfg['page_load_event']   ?? 'PageView',
                'form_submit' => $cfg['form_submit_event'] ?? 'Lead',
                'thanks_page' => $cfg['thanks_page_event'] ?? 'Purchase',
            ];
            $event_name = ! empty( $_POST['event_name_override'] )
                ? sanitize_text_field( $_POST['event_name_override'] )
                : ( $event_map[ $event_type ] ?? 'PageView' );
            $result = Konektor_Meta::send_capi_event( $event_name, $lead_data, $cfg );
            $log['event'] = $event_name;

        } elseif ( $platform === 'tiktok' ) {
            $cfg = $pixel_cfg['tiktok'] ?? [];
            if ( empty( $cfg['pixel_id'] ) || empty( $cfg['access_token'] ) ) {
                wp_send_json_error( [ 'message' => 'Pixel ID dan Access Token TikTok wajib diisi.' ] );
            }
            $result = Konektor_Tiktok::send_event( $event_type, $lead_data, $cfg );
            $log['event'] = Konektor_Tiktok::get_event_name( $cfg, $event_type );

        } elseif ( $platform === 'snack' ) {
            $cfg = $pixel_cfg['snack'] ?? [];
            if ( empty( $cfg['pixel_id'] ) || empty( $cfg['access_token'] ) ) {
                wp_send_json_error( [ 'message' => 'Pixel ID dan Access Token Snack wajib diisi.' ] );
            }
            $event_name = Konektor_Snack::get_event_name( $cfg, $event_type );
            $result = Konektor_Snack::send_event( $event_name, $lead_data, $cfg );
            $log['event'] = $event_name;

        } else {
            wp_send_json_error( [ 'message' => 'Platform tidak dikenal.' ] );
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [
                'message' => 'Koneksi gagal: ' . $result->get_error_message(),
                'log'     => $log,
            ] );
        }

        $code = wp_remote_retrieve_response_code( $result );
        $body = wp_remote_retrieve_body( $result );
        $ok   = ( $code >= 200 && $code < 300 );

        wp_send_json_success( [
            'ok'      => $ok,
            'code'    => $code,
            'body'    => $body,
            'event'   => $log['event'] ?? '',
            'message' => $ok ? 'Event berhasil dikirim! Cek di Test Events panel platform.' : 'Server menolak request.',
        ] );
    }

    public static function ajax_debug_campaign() {
        self::verify_nonce();
        $id       = (int) ( $_POST['campaign_id'] ?? 0 );
        $campaign = Konektor_Campaign::get( $id );
        if ( ! $campaign ) {
            wp_send_json_error( [ 'message' => 'Kampanye tidak ditemukan.' ] );
        }

        $pixel_raw = $campaign->pixel_config;
        $pixel     = $pixel_raw ? json_decode( $pixel_raw, true ) : [];

        $meta_cfg   = $pixel['meta']   ?? [];
        $tiktok_cfg = $pixel['tiktok'] ?? [];
        $snack_cfg  = $pixel['snack']  ?? [];

        // Sensor token — tampilkan 8 karakter pertama saja
        $mask = function( $v ) {
            if ( ! $v ) return '(KOSONG — inilah mengapa CAPI tidak dipanggil!)';
            return substr( $v, 0, 8 ) . str_repeat( '*', max( 0, strlen( $v ) - 8 ) );
        };

        // Coba fire CAPI test langsung dari kampanye jika test_event_code diisi di POST
        $test_code = sanitize_text_field( $_POST['test_event_code'] ?? '' );
        $capi_test_result = null;
        if ( $test_code && ! empty( $meta_cfg['token'] ) && ! empty( $meta_cfg['pixel_id'] ) ) {
            $cfg_with_test = $meta_cfg;
            $cfg_with_test['test_event_code'] = $test_code;
            $event_name = $meta_cfg['form_submit_event'] ?? 'Lead';
            $lead_data  = [
                'source_url' => home_url( '/konektor/' . $campaign->slug . '/' ),
                'name'       => 'Test Admin',
                'email'      => 'test@example.com',
                'phone'      => '081234567890',
            ];
            $resp = Konektor_Meta::send_capi_event( $event_name, $lead_data, $cfg_with_test );
            if ( is_wp_error( $resp ) ) {
                $capi_test_result = [ 'error' => $resp->get_error_message() ];
            } else {
                $capi_test_result = [
                    'http_code' => wp_remote_retrieve_response_code( $resp ),
                    'body'      => json_decode( wp_remote_retrieve_body( $resp ), true ),
                    'event'     => $event_name,
                ];
            }
        }

        wp_send_json_success( [
            'kampanye'    => $campaign->name,
            'slug'        => $campaign->slug,
            'url_form'    => home_url( '/' . Konektor_Helper::get_setting( 'base_slug', 'konektor' ) . '/' . $campaign->slug . '/' ),
            'status'      => $campaign->status,
            'double_lead' => (bool) $campaign->double_lead_enabled,
            'meta' => [
                'pixel_id'          => $meta_cfg['pixel_id']          ?? '(KOSONG)',
                'token'             => $mask( $meta_cfg['token']             ?? '' ),
                'test_event_code'   => $meta_cfg['test_event_code']   ?? '(kosong)',
                'page_load_event'   => $meta_cfg['page_load_event']   ?? '(tidak diset → default PageView)',
                'form_submit_event' => $meta_cfg['form_submit_event']  ?? '(tidak diset → default Lead)',
                'thanks_page_event' => $meta_cfg['thanks_page_event']  ?? '(tidak diset → default Purchase)',
                'CAPI_akan_dipanggil' => ( ! empty( $meta_cfg['token'] ) && ! empty( $meta_cfg['pixel_id'] ) ) ? 'YA' : 'TIDAK — token atau pixel_id kosong!',
            ],
            'tiktok' => [
                'pixel_id'          => $tiktok_cfg['pixel_id']         ?? '(KOSONG)',
                'access_token'      => $mask( $tiktok_cfg['access_token'] ?? '' ),
                'API_akan_dipanggil'=> ( ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) ) ? 'YA' : 'TIDAK — pixel_id atau access_token kosong!',
            ],
            'snack' => [
                'pixel_id'          => $snack_cfg['pixel_id']     ?? '(KOSONG)',
                'access_token'      => $mask( $snack_cfg['access_token'] ?? '' ),
                'API_akan_dipanggil'=> ( ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) ) ? 'YA' : 'TIDAK',
            ],
            'capi_test_result' => $capi_test_result ?? '(isi test_event_code di form untuk fire test langsung)',
        ] );
    }

    /* ────────────────────────────────────────
       PRIVATE HELPERS
    ──────────────────────────────────────── */
    private static function verify_nonce() {
        if ( ! check_ajax_referer( 'konektor_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
        }
    }

    /* ────────────────────────────────────────
       INLINE CSS
    ──────────────────────────────────────── */
    private static function get_css() {
        return <<<'CSS'
:root{--p:#2563eb;--p-dk:#1d4ed8;--p-lt:#eff6ff;--p-bd:#bfdbfe;--ok:#16a34a;--ok-lt:#dcfce7;--warn:#d97706;--warn-lt:#fef9c3;--err:#dc2626;--err-lt:#fee2e2;--g50:#f8fafc;--g100:#f1f5f9;--g200:#e2e8f0;--g300:#cbd5e1;--g400:#94a3b8;--g500:#64748b;--g600:#475569;--g700:#334155;--g800:#1e293b;--g900:#0f172a;--r:10px;--r-sm:7px;--sh:0 1px 3px rgba(0,0,0,.06),0 2px 8px rgba(0,0,0,.05);--sh-md:0 4px 16px rgba(0,0,0,.10)}
.knk-wrap,.knk-wrap *{box-sizing:border-box}
.knk-wrap{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:var(--g900);font-size:14px;line-height:1.5}
.knk-wrap.wrap{margin-top:12px}
/* page header */
.knk-ph{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.knk-ph-left{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.knk-ph-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.knk-ph h1,.knk-ph h2{margin:0;font-size:20px;font-weight:700;color:var(--g900)}
.knk-ph h1 em{font-style:normal;color:var(--p)}
.knk-breadcrumb{display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--g500);text-decoration:none;padding:5px 10px;border:1px solid var(--g200);border-radius:6px;background:#fff;transition:.15s}
.knk-breadcrumb:hover{color:var(--p);border-color:var(--p)}
/* buttons */
.knk-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;font-size:13px;font-weight:500;cursor:pointer;border:none;text-decoration:none;transition:.15s;white-space:nowrap;font-family:inherit;line-height:1.4}
.knk-btn:focus{outline:2px solid var(--p);outline-offset:2px}
.knk-btn-primary{background:var(--p);color:#fff}.knk-btn-primary:hover{background:var(--p-dk);color:#fff}
.knk-btn-outline{background:#fff;color:var(--p);border:1.5px solid var(--p)}.knk-btn-outline:hover{background:var(--p);color:#fff}
.knk-btn-ghost{background:var(--g100);color:var(--g700);border:1px solid var(--g200)}.knk-btn-ghost:hover{background:var(--g200)}
.knk-btn-danger{background:var(--err);color:#fff}.knk-btn-danger:hover{background:#b91c1c}
.knk-btn-sm{padding:4px 10px;font-size:12px;border-radius:5px}
.knk-btn-lg{padding:9px 18px;font-size:14px}
.knk-btn-icon{padding:5px 7px}
/* layout */
.knk-cols{display:grid;grid-template-columns:1fr 340px;gap:18px;align-items:start}
.knk-col-main,.knk-cols-main{min-width:0}
.knk-col-side,.knk-cols-side{min-width:0}
@media(max-width:1100px){.knk-cols{grid-template-columns:1fr}}
/* cards */
.knk-card{background:#fff;border:1px solid var(--g200);border-radius:var(--r);box-shadow:var(--sh);margin-bottom:16px;overflow:hidden}
.knk-card-head{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:13px 18px;border-bottom:1px solid var(--g200);background:var(--g50)}
.knk-card-head h2,.knk-card-title{margin:0;font-size:14px;font-weight:700}
.knk-card-head-tools{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.knk-card-body{padding:18px}
.knk-card-body-flush{padding:0}
.knk-sticky{}
/* form elements */
.knk-field{margin-bottom:15px}
.knk-field:last-child{margin-bottom:0}
.knk-label{display:block;font-size:12px;font-weight:600;color:var(--g700);margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px}
.knk-req{color:var(--err)}
.knk-input,.knk-select,.knk-textarea{width:100%;padding:8px 11px;font-size:13px;border:1.5px solid var(--g300);border-radius:var(--r-sm);background:#fff;color:var(--g900);transition:.15s;outline:none;font-family:inherit;appearance:none;-webkit-appearance:none}
.knk-select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%2364748b' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;padding-right:30px}
.knk-input:focus,.knk-select:focus,.knk-textarea:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
textarea.knk-input,.knk-textarea{resize:vertical;min-height:75px}
.knk-input-sm{font-size:12px;padding:5px 9px;border-radius:5px}
.knk-select-sm{display:inline-block;border:1px solid var(--g200);border-radius:5px;font-size:12px;padding:4px 22px 4px 7px;color:var(--g800);background:#fff;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%2364748b' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 4px center;appearance:none;-webkit-appearance:none;cursor:pointer}
.knk-input-group{display:flex}
.knk-input-prefix{display:flex;align-items:center;padding:8px 10px;background:var(--g100);border:1.5px solid var(--g300);border-right:none;border-radius:7px 0 0 7px;font-size:11px;color:var(--g500);white-space:nowrap;max-width:200px;overflow:hidden;text-overflow:ellipsis;flex-shrink:0}
.knk-input-group .knk-input{border-radius:0 7px 7px 0}
.knk-g2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.knk-g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.knk-g2 .knk-col-full,.knk-g3 .knk-col-full{grid-column:1/-1}
.knk-mono{font-family:monospace;font-size:12px}
@media(max-width:700px){.knk-g2,.knk-g3{grid-template-columns:1fr}}
.knk-hint{font-size:11px;color:var(--g500);margin-top:4px;line-height:1.5}
.knk-hint code{background:var(--g100);padding:1px 4px;border-radius:3px;font-size:11px}
/* toggle switch */
.knk-sw{display:inline-flex;cursor:pointer;flex-shrink:0}
.knk-sw input{display:none}
.knk-sw-track{width:36px;height:20px;background:var(--g300);border-radius:10px;position:relative;transition:.2s}
.knk-sw-track::after{content:'';position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.15)}
.knk-sw input:checked+.knk-sw-track{background:var(--p)}
.knk-sw input:checked+.knk-sw-track::after{left:18px}
.knk-sw-row{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:12px 0;border-bottom:1px solid var(--g100)}
.knk-sw-row:last-child{border-bottom:none;padding-bottom:0}
.knk-sw-row-label strong{font-size:13px;display:block;margin-bottom:2px}
/* badges */
.knk-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap}
.knk-badge-green{background:var(--ok-lt);color:#166534}
.knk-badge-yellow{background:var(--warn-lt);color:#92400e}
.knk-badge-red{background:var(--err-lt);color:#991b1b}
.knk-badge-blue{background:var(--p-lt);color:#1e40af}
.knk-badge-gray{background:var(--g100);color:var(--g700)}
/* tables */
.knk-table-wrap{background:#fff;border:1px solid var(--g200);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden}
.knk-table{width:100%;border-collapse:collapse;font-size:13px}
.knk-table th{padding:10px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--g500);background:var(--g50);border-bottom:1px solid var(--g200)}
.knk-table td{padding:11px 14px;border-bottom:1px solid var(--g100);vertical-align:middle;color:var(--g900)}
.knk-table tbody tr:last-child td{border-bottom:none}
.knk-table tbody tr:hover td{background:var(--g50)}
.knk-table .knk-table-name{font-weight:600}
.knk-table code{background:var(--g100);padding:2px 5px;border-radius:4px;font-size:11px}
/* stats */
.knk-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px;margin-bottom:20px}
.knk-stat{background:#fff;border:1px solid var(--g200);border-radius:var(--r);box-shadow:var(--sh);padding:18px 16px;text-align:center;border-top:3px solid var(--p)}
.knk-stat-blue{border-top-color:var(--p)}.knk-stat-green{border-top-color:var(--ok)}.knk-stat-yellow{border-top-color:var(--warn)}.knk-stat-red{border-top-color:var(--err)}.knk-stat-gray{border-top-color:var(--g400)}
.knk-stat-n{display:block;font-size:28px;font-weight:800;color:var(--g900);line-height:1.1}
.knk-stat-l{display:block;font-size:11px;font-weight:500;color:var(--g500);margin-top:4px}
/* url banner */
.knk-url-banner{display:flex;align-items:flex-start;gap:12px;background:var(--p-lt);border:1.5px solid var(--p-bd);border-radius:var(--r);padding:14px 16px;margin-bottom:18px}
.knk-url-banner-icon{font-size:24px;line-height:1;margin-top:2px;flex-shrink:0}
.knk-url-banner-body{flex:1;min-width:0}
.knk-url-banner-label{font-size:11px;font-weight:700;color:var(--p);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.knk-url-banner-row{display:flex;gap:7px;align-items:center;flex-wrap:wrap}
.knk-url-field{flex:1;min-width:180px;padding:7px 11px;font-size:12px;font-family:'SFMono-Regular',Consolas,monospace;background:#fff;border:1.5px solid var(--p-bd);border-radius:6px}
.knk-embed-toggle{margin-top:8px;font-size:12px;color:var(--p);cursor:pointer;background:none;border:none;padding:0;font-family:inherit}
.knk-embed-wrap{display:none;margin-top:6px}
.knk-embed-wrap.open{display:block}
.knk-embed-box{width:100%;padding:9px 11px;font-family:monospace;font-size:11px;background:#fff;border:1.5px solid var(--p-bd);border-radius:6px;resize:none;height:120px}
/* field builder */
.knk-section-head{display:flex;align-items:center;justify-content:space-between;margin:16px 0 8px}
.knk-section-head h3{margin:0;font-size:13px;font-weight:700;color:var(--g700)}
.knk-field-row{display:flex;align-items:center;gap:10px;padding:9px 12px;border:1.5px solid var(--g200);border-radius:7px;margin-bottom:5px;background:var(--g50);transition:.15s;cursor:default;user-select:none}
.knk-field-row.is-on{border-color:var(--p);background:var(--p-lt)}
.knk-field-row-drag{color:var(--g300);font-size:16px;cursor:grab;flex-shrink:0}
.knk-field-row-info{flex:1;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.knk-field-label-input{flex:1;min-width:120px;max-width:220px;font-weight:600}
.knk-field-row-type{font-size:10px;color:var(--g500);background:var(--g200);padding:1px 6px;border-radius:3px;text-transform:uppercase;white-space:nowrap}
.knk-field-row-req{font-size:12px;color:var(--g700);white-space:nowrap;flex-shrink:0}
.knk-extra-row{display:flex;align-items:center;gap:7px;padding:8px 10px;border:1.5px solid var(--g200);border-radius:7px;margin-bottom:5px;flex-wrap:wrap}
/* template thumbs */
.knk-tpl-bar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.knk-tpl-thumb{cursor:pointer;border:2px solid var(--g200);border-radius:8px;overflow:hidden;width:80px;text-align:center;font-size:11px;font-weight:600;color:var(--g500);transition:.15s}
.knk-tpl-thumb:hover{border-color:var(--p);color:var(--p)}
.knk-tpl-thumb.active{border-color:var(--p);color:var(--p);box-shadow:0 0 0 3px var(--p-lt)}
.knk-tpl-label{display:block;padding:4px 0}
.knk-preview-wrap{background:var(--g100);border-radius:0 0 var(--r) var(--r);overflow-y:auto;max-height:480px;padding:16px}
.knk-preview-wrap .konektor-form input,.knk-preview-wrap .konektor-form textarea,.knk-preview-wrap .konektor-form select,.knk-preview-wrap .konektor-btn-submit{pointer-events:none}
.knk-preview-label{font-size:10px;font-weight:700;color:var(--g500);text-transform:uppercase;letter-spacing:.5px;padding:8px 16px;background:var(--g200);border-bottom:1px solid var(--g300)}
/* operators in campaign edit */
.knk-op-item{display:flex;align-items:center;gap:10px;padding:11px 16px;border-bottom:1px solid var(--g100);transition:background .1s}
.knk-op-item:last-child{border-bottom:none}
.knk-op-item.is-on{background:var(--p-lt)}
.knk-op-item:hover{background:var(--g50)}
.knk-op-item.is-on:hover{background:#dbeafe}
.knk-op-meta{flex:1;display:flex;align-items:center;gap:6px;flex-wrap:wrap;min-width:0}
.knk-op-name{font-weight:600;font-size:13px}
.knk-op-weight-wrap{display:flex;flex-direction:column;align-items:center;gap:1px;flex-shrink:0}
.knk-op-weight-label{font-size:9px;color:var(--g500);text-transform:uppercase}
.knk-weight-input{width:48px;text-align:center;padding:4px 6px;border:1.5px solid var(--g300);border-radius:5px;font-size:13px;font-weight:700;outline:none}
.knk-weight-input:focus{border-color:var(--p)}
/* pixel tabs */
.knk-ptabs{display:flex;gap:4px;border-bottom:2px solid var(--g200);margin-bottom:16px}
.knk-ptab{padding:7px 13px;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;font-size:13px;font-weight:500;cursor:pointer;color:var(--g500);transition:.15s;font-family:inherit}
.knk-ptab:hover{color:var(--g900)}
.knk-ptab.active{border-bottom-color:var(--p);color:var(--p)}
.knk-pcontent{display:none}
.knk-pcontent.active{display:block}
/* chips */
.knk-chips{display:flex;flex-wrap:wrap;gap:5px;margin-top:6px}
.knk-chip{font-size:11px;font-family:monospace;padding:2px 7px;background:var(--g100);border:1px solid var(--g200);border-radius:4px;cursor:pointer;color:var(--p);transition:.1s;user-select:none}
.knk-chip:hover{background:var(--p);color:#fff;border-color:var(--p)}
/* dashboard quick */
.knk-quick{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
/* pagination */
.knk-pager{display:flex;gap:6px;margin-top:14px;flex-wrap:wrap}
/* modal */
.knk-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99999;align-items:center;justify-content:center}
.knk-modal-bg:not(.knk-hidden){display:flex}
.knk-modal{background:#fff;border-radius:var(--r);padding:24px;max-width:520px;width:94%;box-shadow:var(--sh-md)}
.knk-modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.knk-modal-title{font-size:15px;font-weight:600;color:var(--g800)}
.knk-modal-x{background:none;border:none;font-size:18px;cursor:pointer;color:var(--g500);line-height:1;padding:0;transition:color .15s}
.knk-modal-x:hover{color:var(--g800)}
.knk-modal-body{font-size:13px}
/* save status */
.knk-save-status{font-size:13px;font-weight:500}
.knk-save-status.ok{color:var(--ok)}
.knk-save-status.err{color:var(--err)}
/* empty state */
.knk-empty{text-align:center;padding:36px 20px;color:var(--g500);font-size:13px}
.knk-empty p{margin:0 0 12px}
/* misc */
.knk-hidden{display:none!important}
.knk-divider{border:none;border-top:1px solid var(--g200);margin:16px 0}
.knk-stab-content.knk-hidden{display:none!important}
/* operator search/add in campaign edit */
.knk-op-search-wrap{padding:12px 14px;border-bottom:1px solid var(--g100)}
.knk-op-search-input{width:100%;padding:7px 11px;font-size:13px;border:1.5px solid var(--g300);border-radius:6px;outline:none;font-family:inherit}
.knk-op-search-input:focus{border-color:var(--p)}
.knk-op-dropdown{position:absolute;z-index:100;background:#fff;border:1.5px solid var(--g200);border-radius:8px;box-shadow:var(--sh-md);width:100%;max-height:200px;overflow-y:auto;display:none}
.knk-op-dropdown.open{display:block}
.knk-op-dropdown-item{padding:9px 14px;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--g100)}
.knk-op-dropdown-item:last-child{border-bottom:none}
.knk-op-dropdown-item:hover{background:var(--p-lt);color:var(--p)}
.knk-op-search-rel{position:relative}
.knk-op-assigned-empty{padding:20px;text-align:center;color:var(--g400);font-size:13px}
CSS;
    }

    /* ────────────────────────────────────────
       INLINE JS
    ──────────────────────────────────────── */
    private static function get_js() {
        return <<<'JS'
(function($){
'use strict';
var nonce=KonektorAdmin.nonce,ajaxUrl=KonektorAdmin.ajax_url;

/* pixel tabs */
$(document).on('click','.knk-pixel-tab',function(){
  var tab=$(this).data('ptab');
  var $body=$(this).closest('.knk-card-body');
  $body.find('.knk-pixel-tab').removeClass('active');
  $(this).addClass('active');
  $body.find('.knk-pixel-content').removeClass('active');
  $body.find('[data-ptab="'+tab+'"].knk-pixel-content').addClass('active');
});
/* pixel tabs in campaign edit (different class) */
$(document).on('click','.knk-ptab[data-ptab]',function(){
  var tab=$(this).data('ptab');
  var $wrap=$(this).closest('.knk-card-body,.knk-card');
  $wrap.find('.knk-ptab[data-ptab]').removeClass('active');
  $(this).addClass('active');
  $wrap.find('.knk-pcontent').removeClass('active');
  $wrap.find('[data-ptab="'+tab+'"].knk-pcontent').addClass('active');
});

/* campaign type toggle — show/hide form builder */
function syncCampType(val){
  var isForm=val==='form';
  $('#form-builder-card').toggle(isForm);
  $('#form-preview-card').toggle(isForm);
}
$(document).on('change','#camp-type',function(){syncCampType($(this).val());});
$(document).ready(function(){
  if($('#camp-type').length)syncCampType($('#camp-type').val());
});

/* redirect type toggle */
$(document).on('change','#redirect-type',function(){
  var v=$(this).val();
  $('#redirect-url-row').toggleClass('knk-hidden',v!=='url');
  $('#redirect-cs-row').toggleClass('knk-hidden',v!=='cs');
});

/* double lead / block toggle */
$(document).on('change','#double-lead-toggle',function(){
  $('#double-lead-msg').toggleClass('knk-hidden',!this.checked);
});
$(document).on('change','#block-toggle',function(){
  $('#block-msg').toggleClass('knk-hidden',!this.checked);
});

/* work hours toggle */
$(document).on('change','#work-hours-toggle',function(){
  $('#work-hours-section').toggle(this.checked);
});

/* auto slug from name */
$(document).on('input','#camp-name',function(){
  var $s=$('#camp-slug');
  if($s.data('auto')!==false||$s.val()===''){
    $s.val(slugify($(this).val())).data('auto',true);
  }
});
$(document).on('input','#camp-slug',function(){
  $(this).data('auto',false).val(slugify($(this).val(),true));
});
function slugify(s,k){
  s=s.toLowerCase().replace(/\s+/g,'-').replace(/[^a-z0-9\-]/g,'');
  if(!k)s=s.replace(/-+/g,'-').replace(/^-|-$/g,'');
  return s;
}

/* template thumbs */
$(document).on('click','.knk-tpl-thumb',function(){
  var tpl=$(this).data('tpl');
  $('#form-tpl-select').val(tpl);
  $('.knk-tpl-thumb').removeClass('active');
  $(this).addClass('active');
  refreshPreview();
});
$(document).on('change','#form-tpl-select',function(){
  var tpl=$(this).val();
  $('.knk-tpl-thumb').removeClass('active');
  $('.knk-tpl-thumb[data-tpl="'+tpl+'"]').addClass('active');
  refreshPreview();
});
/* color picker sync */
$(document).on('input change','.knk-color-sync',function(){
  var target=$(this).data('target');
  $('#'+target).val(this.value);
  refreshPreview();
});
$(document).on('input','[name^="form_config[custom_style]"]',function(){
  var id=$(this).attr('id');
  $('[data-target="'+id+'"]').val($(this).val()||'#ffffff');
  refreshPreview();
});

$(document).on('change','.knk-field-enable-cb',function(){
  $(this).closest('.knk-field-row').toggleClass('is-on',this.checked);
  refreshPreview();
});
$(document).on('input','input[name="store_name"],input[name="product_name"],input[name="form_config[submit_label]"]',function(){
  refreshPreview();
});

function refreshPreview(){
  var tpl=$('#form-tpl-select').val()||'modern';
  var lbl=$('input[name="form_config[submit_label]"]').val()||'Kirim Sekarang';
  var store=$('input[name="store_name"]').val();
  var prod=$('input[name="product_name"]').val();
  var fields=[];
  $('#knk-field-list .knk-field-row').each(function(){
    var $r=$(this);
    if($r.find('.knk-field-enable-cb').is(':checked')){
      fields.push({
        label:$r.find('.knk-field-label-input').val()||$r.find('input[name$="[label]"]').val()||'',
        type:$r.find('input[name$="[type]"]').val()||'text'
      });
    }
  });
  var colors={modern:'#2563eb',classic:'#dc2626',minimal:'#111',card:'#2563eb',gradient:'#fff'};
  var bg={modern:'#fff',classic:'#fff',minimal:'#fff',card:'#f8fafc',gradient:'#1e3a5f'};
  var btnc=colors[tpl]||'#2563eb';
  var bgg=bg[tpl]||'#fff';
  var h='<div style="background:'+bgg+';padding:16px;border-radius:8px;font-family:inherit">';
  if(store||prod){
    h+='<div style="margin-bottom:12px">';
    if(store)h+='<p style="margin:0;font-size:11px;color:#888">'+esc(store)+'</p>';
    if(prod)h+='<h3 style="margin:4px 0 0;font-size:16px;font-weight:700">'+esc(prod)+'</h3>';
    h+='</div>';
  }
  fields.forEach(function(f){
    h+='<div style="margin-bottom:10px">';
    h+='<label style="display:block;font-size:11px;font-weight:600;margin-bottom:4px">'+esc(f.label)+'</label>';
    if(f.type==='textarea'){
      h+='<textarea style="width:100%;padding:7px;border:1.5px solid #cbd5e1;border-radius:6px;font-size:12px;resize:none;height:60px" placeholder="'+esc(f.label)+'"></textarea>';
    }else{
      h+='<input type="'+esc(f.type)+'" style="width:100%;padding:7px 10px;border:1.5px solid #cbd5e1;border-radius:6px;font-size:12px" placeholder="'+esc(f.label)+'">';
    }
    h+='</div>';
  });
  h+='<button style="width:100%;padding:10px;background:'+btnc+';color:#fff;border:none;border-radius:7px;font-size:14px;font-weight:600;cursor:default">'+esc(lbl)+'</button>';
  h+='</div>';
  $('#knk-form-preview').html(h);
}

/* extra field builder */
var extraIdx=$('.knk-extra-row').length;
$(document).on('click','#knk-add-field',function(){
  var i=extraIdx++;
  var h='<div class="knk-extra-row" data-idx="'+i+'">'
    +'<input type="text" name="form_config[extra_fields]['+i+'][label]" class="knk-input knk-input-sm" style="flex:1;min-width:100px" placeholder="Label">'
    +'<select name="form_config[extra_fields]['+i+'][type]" class="knk-select-sm">'
    +'<option value="text">Text</option><option value="tel">Telepon</option><option value="email">Email</option>'
    +'<option value="number">Angka</option><option value="textarea">Textarea</option>'
    +'</select>'
    +'<label style="font-size:12px;display:flex;align-items:center;gap:3px;white-space:nowrap"><input type="checkbox" name="form_config[extra_fields]['+i+'][required]" value="1"> Wajib</label>'
    +'<label style="font-size:12px;display:flex;align-items:center;gap:3px;white-space:nowrap"><input type="checkbox" name="form_config[extra_fields]['+i+'][enabled]" value="1" checked> Tampil</label>'
    +'<button type="button" class="knk-btn knk-btn-danger knk-btn-icon knk-rm-extra" title="Hapus"><i class="fa-solid fa-xmark"></i></button>'
    +'</div>';
  $('#knk-extra-fields').append(h);
});
$(document).on('click','.knk-rm-extra,.knk-remove-extra',function(){
  $(this).closest('.knk-extra-row').remove();
});

/* ── CAMPAIGN SAVE ── */
$(document).on('submit','#konektor-campaign-form',function(e){
  e.preventDefault();
  var $form=$(this);
  var $st=$('#knk-save-status');
  var id=parseInt($form.data('id'))||0;
  $st.text('Menyimpan...').removeClass('ok err');

  var payload=buildCampaignPayload($form,id);

  $.ajax({
    url:ajaxUrl,type:'POST',data:payload,dataType:'json',
    success:function(res){
      if(res.success){
        $st.html('<i class="fa-solid fa-check"></i> Tersimpan!').addClass('ok');
        if(!id&&res.data&&res.data.id){
          $form.data('id',res.data.id);
          var u=new URL(window.location.href);
          u.searchParams.set('action','edit');
          u.searchParams.set('id',res.data.id);
          window.history.replaceState({},'',u.toString());
        }
      }else{
        $st.html('<i class="fa-solid fa-circle-exclamation"></i> '+(res.data&&res.data.message?res.data.message:'Gagal')).addClass('err');
      }
    },
    error:function(x){$st.html('<i class="fa-solid fa-circle-exclamation"></i> Error '+x.status).addClass('err');}
  });
});

function buildCampaignPayload($form,id){
  var p={action:'konektor_admin_save_campaign',nonce:nonce,id:id};
  var pixelMeta={},pixelGoogle={},pixelTiktok={},pixelSnack={test_mode:'0'};
  var formCfgOther={};

  $form.serializeArray().forEach(function(item){
    var k=item.name,v=item.value;
    var mPix=k.match(/^pixel_config\[(meta|google|tiktok|snack)\]\[(\w+)\]$/);
    if(mPix){
      if(mPix[1]==='meta')pixelMeta[mPix[2]]=v;
      else if(mPix[1]==='google')pixelGoogle[mPix[2]]=v;
      else if(mPix[1]==='tiktok')pixelTiktok[mPix[2]]=v;
      else pixelSnack[mPix[2]]=v;
      return;
    }
    var mThanks=k.match(/^thanks_page_config\[(\w+)\]$/);
    if(mThanks){p['_thanks_'+mThanks[1]]=v;return;}
    var mCfg=k.match(/^form_config\[(\w+)\]$/);
    if(mCfg){formCfgOther[mCfg[1]]=v;return;}
    p[k]=v;
  });

  // checkboxes not in serializeArray when unchecked
  p.double_lead_enabled=$form.find('input[name="double_lead_enabled"]').is(':checked')?1:0;
  p.block_enabled=$form.find('input[name="block_enabled"]').is(':checked')?1:0;

  // build form_config.fields from DOM
  var fieldsArr=[];
  $form.find('#knk-field-list .knk-field-row').each(function(){
    var $r=$(this);
    fieldsArr.push({
      name:$r.find('input[name$="[name]"]').val(),
      label:$r.find('input[name$="[label]"]').val(),
      placeholder:$r.find('input[name$="[placeholder]"]').val(),
      type:$r.find('input[name$="[type]"]').val(),
      enabled:$r.find('.knk-field-enable-cb').is(':checked')?1:0,
      required:$r.find('input[name$="[required]"]').is(':checked')?1:0
    });
  });

  // extra fields
  var extrasArr=[];
  $form.find('#knk-extra-fields .knk-extra-row').each(function(){
    var $r=$(this);
    var lbl=$r.find('input[name$="[label]"]').val();
    if(!lbl)return;
    extrasArr.push({
      label:lbl,
      type:$r.find('select').val(),
      required:$r.find('input[name$="[required]"]').is(':checked')?1:0,
      enabled:$r.find('input[name$="[enabled]"]').is(':checked')?1:0
    });
  });

  p.form_config=JSON.stringify($.extend(formCfgOther,{fields:fieldsArr,extra_fields:extrasArr}));

  // operators from assigned list (search/add UI)
  var ops=[];
  var $opContainer=$form.find('#knk-op-assigned');
  if($opContainer.length){
    $opContainer.find('.knk-op-item').each(function(){
      var $row=$(this);
      var opId=parseInt($row.find('input[name$="[id]"]').val());
      var opWt=parseInt($row.find('.knk-weight-input').val())||1;
      if(opId) ops.push({id:opId,weight:opWt});
    });
  }
  p.operators=JSON.stringify(ops);

  p.pixel_config=JSON.stringify({meta:pixelMeta,google:pixelGoogle,tiktok:pixelTiktok,snack:pixelSnack});

  // allowed_domains from textarea
  var domsRaw=p['allowed_domains_raw']||'';
  p.allowed_domains=JSON.stringify(domsRaw.split('\n').map(function(s){return s.trim();}).filter(Boolean));
  delete p['allowed_domains_raw'];

  // thanks_page_config
  var tc={};
  Object.keys(p).forEach(function(k){
    if(k.indexOf('_thanks_')===0){tc[k.slice(8)]=p[k];delete p[k];}
  });
  p.thanks_page_config=JSON.stringify(tc);

  // normalize slug
  if(p.slug)p.slug=p.slug.toLowerCase().replace(/[^a-z0-9\-]/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');

  return p;
}

/* delete campaign */
$(document).on('click','.knk-delete-campaign',function(e){
  e.preventDefault();
  if(!confirm('Hapus kampanye ini?'))return;
  $.post(ajaxUrl,{action:'konektor_admin_delete_campaign',nonce:nonce,id:$(this).data('id')},function(r){
    if(r.success)location.reload();
  });
});

/* ── OPERATOR SAVE ── */
$(document).on('submit','#konektor-operator-form',function(e){
  e.preventDefault();
  var $form=$(this);
  var $st=$form.find('.knk-save-status');
  var id=parseInt($form.data('id'))||0;

  var whMap={};
  var data={action:'konektor_admin_save_operator',nonce:nonce,id:id};

  $form.serializeArray().forEach(function(item){
    var m=item.name.match(/^work_hours\[(\d+)\]\[(\w+)\]$/);
    if(m){
      if(!whMap[m[1]])whMap[m[1]]={};
      whMap[m[1]][m[2]]=item.value;
    }else{
      data[item.name]=item.value;
    }
  });

  var wh=[];
  Object.keys(whMap).forEach(function(i){
    var s=whMap[i];
    if(s.active==='1')wh.push({day:s.day,start:s.start,end:s.end});
  });
  data.work_hours=JSON.stringify(wh);
  data.work_hours_enabled=$form.find('input[name="work_hours_enabled"]').is(':checked')?1:0;

  $st.text('Menyimpan...').removeClass('ok err');
  $.ajax({
    url:ajaxUrl,type:'POST',data:data,dataType:'json',
    success:function(r){
      if(r.success){
        $st.html('<i class="fa-solid fa-check"></i> Tersimpan!').addClass('ok');
        if(!id&&r.data&&r.data.id){
          $form.data('id',r.data.id);
          var u=new URL(window.location.href);
          u.searchParams.set('action','edit');
          u.searchParams.set('id',r.data.id);
          window.history.replaceState({},'',u.toString());
        }
      }else{$st.html('<i class="fa-solid fa-circle-exclamation"></i> Gagal').addClass('err');}
    },
    error:function(){$st.html('<i class="fa-solid fa-circle-exclamation"></i> Error koneksi').addClass('err');}
  });
});

/* delete operator */
$(document).on('click','.knk-delete-operator',function(e){
  e.preventDefault();
  if(!confirm('Hapus operator ini?'))return;
  $.post(ajaxUrl,{action:'konektor_admin_delete_operator',nonce:nonce,id:$(this).data('id')},function(r){
    if(r.success)location.reload();
  });
});

/* ── SETTINGS SAVE ── */
$(document).on('submit','#konektor-settings-form',function(e){
  e.preventDefault();
  var $st=$(this).find('.knk-save-status');
  var data={action:'konektor_admin_save_settings',nonce:nonce};
  $(this).serializeArray().forEach(function(v){data[v.name]=v.value;});
  // domains textarea → JSON array
  var dr=data['allowed_domains_global']||'';
  data.allowed_domains_global=JSON.stringify(dr.split('\n').map(function(s){return s.trim();}).filter(Boolean));
  data.encrypt_lead_data=$(this).find('input[name="encrypt_lead_data"]').is(':checked')?'1':'0';
  $st.text('Menyimpan...').removeClass('ok err');
  $.ajax({
    url:ajaxUrl,type:'POST',data:data,dataType:'json',
    success:function(r){$st.html(r.success?'<i class="fa-solid fa-check"></i> Tersimpan!':'<i class="fa-solid fa-circle-exclamation"></i> Gagal').toggleClass('ok',!!r.success).toggleClass('err',!r.success);}
  });
});

/* settings tabs */
$(document).on('click','.knk-ptab[data-stab]',function(){
  var tab=$(this).data('stab');
  var $ctx=$(this).closest('.knk-wrap');
  $ctx.find('.knk-ptab[data-stab]').removeClass('active');
  $(this).addClass('active');
  $ctx.find('.knk-stab-content').addClass('knk-hidden');
  $('#stab-'+tab).removeClass('knk-hidden');
});

/* lead status */
$(document).on('change','.knk-lead-status',function(){
  $.post(ajaxUrl,{action:'konektor_admin_update_lead',nonce:nonce,id:$(this).data('id'),status:$(this).val()});
});

/* unblock */
$(document).on('click','.knk-unblock',function(e){
  e.preventDefault();
  if(!confirm('Hapus blokir ini?'))return;
  $.post(ajaxUrl,{action:'konektor_admin_unblock',nonce:nonce,id:$(this).data('id')},function(r){
    if(r.success)location.reload();
  });
});

/* delete lead */
$(document).on('click','.knk-delete-lead',function(e){
  e.preventDefault();
  if(!confirm('Hapus lead ini permanen? Data tidak bisa dikembalikan.'))return;
  var btn=$(this);
  btn.prop('disabled',true);
  $.post(ajaxUrl,{action:'konektor_admin_delete_lead',nonce:nonce,id:btn.data('id')},function(r){
    if(r.success)btn.closest('tr').fadeOut(300,function(){$(this).remove();});
    else btn.prop('disabled',false);
  });
});

/* block lead from leads table */
$(document).on('click','.knk-block-lead',function(e){
  e.preventDefault();
  var id=$(this).data('id');
  var reason=prompt('Alasan pemblokiran (opsional):','');
  if(reason===null)return;
  var btn=$(this);
  btn.prop('disabled',true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
  $.post(ajaxUrl,{action:'konektor_admin_block_lead',nonce:nonce,lead_id:id,reason:reason},function(r){
    if(r.success){
      btn.closest('tr').find('.knk-lead-status').val('blocked').trigger('change');
      btn.replaceWith('<span class="knk-badge knk-badge-red"><i class="fa-solid fa-ban"></i> Diblokir</span>');
    }else{
      btn.prop('disabled',false).html('<i class="fa-solid fa-ban"></i> Blokir');
    }
  });
});

/* generate panel token */
$(document).on('click','.knk-gen-token',function(e){
  e.preventDefault();
  var btn=$(this);
  $.post(ajaxUrl,{action:'konektor_admin_gen_token',nonce:nonce,operator_id:btn.data('id')},function(r){
    if(r.success){
      $('#knk-token-url').val(r.data.url);
      $('#knk-token-modal').removeClass('knk-hidden');
    }
  });
});
$(document).on('click','.knk-token-modal-close',function(){
  $('#knk-token-modal').addClass('knk-hidden');
});
$(document).on('click','#knk-token-url',function(){$(this).select();});
$(document).on('click','#knk-token-copy',function(){
  var $i=$('#knk-token-url');$i.select();
  navigator.clipboard.writeText($i.val());
  $(this).html('<i class="fa-solid fa-check"></i> Tersalin');
  setTimeout(function(){$('#knk-token-copy').html('<i class="fa-regular fa-copy"></i> Salin');},2000);
});

/* lead detail modal */
$(document).on('click','.knk-view-lead',function(e){
  e.preventDefault();
  var id=$(this).data('id');
  $('#knk-lead-detail-body').html('<div style="text-align:center;padding:20px;color:#64748b">Memuat...</div>');
  $('#knk-lead-modal').removeClass('knk-hidden');
  $.post(ajaxUrl,{action:'konektor_admin_get_lead',nonce:nonce,id:id},function(r){
    if(r.success&&r.data){
      var l=r.data;
      var tipe=l.name?'Form Order':'WA Link Click';
      var rows=[['ID',l.id],['Tipe',tipe],['Nama',l.name||'—'],['No HP',l.phone||'—'],['Email',l.email||'—'],
        ['Alamat',l.address||'—'],['Jumlah',l.quantity||'—'],['Pesan',l.custom_message||'—'],
        ['Kampanye',l.campaign_name||'—'],['Operator',l.operator_name||'—'],
        ['Status',l.status],['Double Lead',l.is_double?'Ya':'Tidak'],
        ['IP Address',l.ip_address||'—'],['Cookie ID',l.cookie_id||'—'],
        ['Source URL',l.source_url||'—'],['Referrer',l.referrer||'—'],
        ['User Agent',l.user_agent||'—'],['Tanggal',l.created_at]];
      var h='<table style="width:100%;font-size:13px;border-collapse:collapse">';
      rows.forEach(function(r){
        h+='<tr style="border-bottom:1px solid #f1f5f9">'
          +'<td style="padding:8px 12px;font-weight:600;color:#64748b;width:110px">'+r[0]+'</td>'
          +'<td style="padding:8px 12px">'+(r[1]||'—')+'</td></tr>';
      });
      h+='</table>';
      $('#knk-lead-detail-body').html(h);
    }else{
      $('#knk-lead-detail-body').html('<p style="color:#dc2626;text-align:center">Gagal memuat data.</p>');
    }
  });
});
$(document).on('click','#knk-lead-modal-close',function(){$('#knk-lead-modal').addClass('knk-hidden');});
$(document).on('click','#knk-lead-modal',function(e){if(e.target===this)$(this).addClass('knk-hidden');});

/* ── OPERATOR SEARCH / ADD in campaign edit ── */
var $opSearch=$('#knk-op-search-input');
var $opDrop=$('#knk-op-dropdown');
var $opList=$('#knk-op-assigned');
var opAll=[];

// Build list from hidden data
if($('#knk-op-data').length){
  try{ opAll=JSON.parse($('#knk-op-data').text()||'[]'); }catch(e){}
}

$(document).on('input','#knk-op-search-input',function(){
  var q=$(this).val().toLowerCase();
  var assigned=getAssignedIds();
  var matches=opAll.filter(function(o){ return o.name.toLowerCase().includes(q) && !assigned.includes(o.id); });
  renderOpDropdown(matches);
});

$(document).on('focus','#knk-op-search-input',function(){
  var q=$(this).val().toLowerCase();
  var assigned=getAssignedIds();
  var matches=opAll.filter(function(o){ return o.name.toLowerCase().includes(q) && !assigned.includes(o.id); });
  renderOpDropdown(matches);
});

$(document).on('click','.knk-op-dropdown-item',function(){
  var id=parseInt($(this).data('id'));
  var name=$(this).data('name');
  var type=$(this).data('type');
  addOperatorToList(id, name, type, 1);
  $('#knk-op-search-input').val('');
  $opDrop.removeClass('open');
});

$(document).on('click','body',function(e){
  if(!$(e.target).closest('.knk-op-search-rel').length){
    $opDrop.removeClass('open');
  }
});

$(document).on('click','.knk-op-remove',function(){
  $(this).closest('.knk-op-item').remove();
  updateOpEmpty();
});

function getAssignedIds(){
  var ids=[];
  $('#knk-op-assigned .knk-op-item').each(function(){
    ids.push(parseInt($(this).find('input[name$="[id]"]').val()));
  });
  return ids;
}

function renderOpDropdown(matches){
  var $d=$('#knk-op-dropdown');
  if(!matches.length){ $d.removeClass('open'); return; }
  var tc={whatsapp:'green',telegram:'blue',email:'gray',line:'green'};
  $d.html(matches.map(function(o){
    return '<div class="knk-op-dropdown-item" data-id="'+o.id+'" data-name="'+esc(o.name)+'" data-type="'+o.type+'">'
      +'<i class="'+(o.type==='whatsapp'?'fa-brands fa-whatsapp':'fa-solid fa-user')+'" style="color:var(--g400)"></i>'
      +esc(o.name)
      +'<span class="knk-badge knk-badge-'+tc[o.type]+'" style="margin-left:auto">'+o.type+'</span>'
      +'</div>';
  }).join('')).addClass('open');
}

function addOperatorToList(id, name, type, weight){
  var tc={whatsapp:'green',telegram:'blue',email:'gray',line:'green'};
  var color=tc[type]||'gray';
  var faIcon=type==='whatsapp'?'fa-brands fa-whatsapp':'fa-solid fa-user';
  var idx=Date.now();
  var h='<div class="knk-op-item is-on">'
    +'<input type="hidden" name="operators['+idx+'][id]" value="'+id+'">'
    +'<div class="knk-op-meta">'
    +'<i class="'+faIcon+'" style="color:var(--g400)"></i>'
    +'<span class="knk-op-name">'+esc(name)+'</span>'
    +'<span class="knk-badge knk-badge-'+color+'">'+type+'</span>'
    +'</div>'
    +'<div class="knk-op-weight-wrap">'
    +'<span class="knk-op-weight-label">Bobot</span>'
    +'<input type="number" name="operators['+idx+'][weight]" value="'+weight+'" min="1" max="10" class="knk-weight-input">'
    +'</div>'
    +'<button type="button" class="knk-btn knk-btn-ghost knk-btn-sm knk-op-remove" title="Hapus"><i class="fa-solid fa-xmark"></i></button>'
    +'</div>';
  $('#knk-op-assigned').find('.knk-op-assigned-empty').remove();
  $('#knk-op-assigned').append(h);
}

function updateOpEmpty(){
  if(!$('#knk-op-assigned .knk-op-item').length){
    $('#knk-op-assigned').html('<div class="knk-op-assigned-empty"><i class="fa-solid fa-user-plus" style="font-size:20px;margin-bottom:6px;display:block;opacity:.3"></i>Belum ada CS. Cari dan tambahkan di atas.</div>');
  }
}


/* ── DEBUG CONFIG KAMPANYE ── */
$(document).on('click','#dbg-check-btn',function(){
  var id=$('#dbg-campaign-id').val();
  var testCode=$('#dbg-test-code').val().trim();
  if(!id){alert('Pilih kampanye dulu.');return;}
  var $pre=$('#dbg-result');
  $pre.show().text('Memuat...');
  $.post(ajaxUrl,{
    action:'konektor_admin_debug_campaign',
    nonce:nonce,
    campaign_id:id,
    test_event_code:testCode
  },function(r){
    if(r.success){
      $pre.text(JSON.stringify(r.data,null,2));
    } else {
      $pre.text('Error: '+(r.data&&r.data.message?r.data.message:'Gagal'));
    }
  },'json');
});

/* ── TEST PERISTIWA API ── */
$(document).on('change','#tp-platform',function(){
  var v=$(this).val();
  $('#tp-fields-meta,#tp-fields-tiktok,#tp-fields-snack').hide();
  $('#tp-fields-'+v).show();
  $('#tp-result').hide();
  $('#tp-status').text('');
});

$(document).on('click','#tp-send-btn',function(){
  var platform=$('#tp-platform').val();
  var eventType=$('#tp-event-type').val();
  var $btn=$(this);
  var $status=$('#tp-status');
  var $result=$('#tp-result');

  // Bangun pixel_config sesuai platform
  var pixelCfg={};
  if(platform==='meta'){
    var pixId=$('#tp-meta-pixel-id').val().trim();
    var token=$('#tp-meta-token').val().trim();
    var testCode=$('#tp-meta-test-code').val().trim();
    var evOverride=$('#tp-meta-event-override').val().trim();
    if(!pixId||!token){$status.text('Pixel ID dan Access Token wajib diisi.').css('color','var(--err)');return;}
    var metaCfg={pixel_id:pixId,token:token};
    if(testCode)metaCfg.test_event_code=testCode;
    if(evOverride){
      metaCfg.page_load_event=evOverride;
      metaCfg.form_submit_event=evOverride;
      metaCfg.thanks_page_event=evOverride;
    }
    pixelCfg={meta:metaCfg};
  } else if(platform==='tiktok'){
    var pixId=$('#tp-tiktok-pixel-id').val().trim();
    var token=$('#tp-tiktok-token').val().trim();
    var testCode=$('#tp-tiktok-test-code').val().trim();
    if(!pixId||!token){$status.text('Pixel ID dan Access Token wajib diisi.').css('color','var(--err)');return;}
    var ttCfg={pixel_id:pixId,access_token:token};
    if(testCode)ttCfg.test_event_code=testCode;
    pixelCfg={tiktok:ttCfg};
  } else if(platform==='snack'){
    var pixId=$('#tp-snack-pixel-id').val().trim();
    var token=$('#tp-snack-token').val().trim();
    if(!pixId||!token){$status.text('Pixel ID dan Access Token wajib diisi.').css('color','var(--err)');return;}
    pixelCfg={snack:{pixel_id:pixId,access_token:token}};
  }

  $btn.prop('disabled',true).html('<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...');
  $status.text('').css('color','');
  $result.hide();

  $.ajax({
    url:ajaxUrl,
    type:'POST',
    data:{
      action:'konektor_admin_test_pixel',
      nonce:nonce,
      platform:platform,
      event_type:eventType,
      pixel_config:JSON.stringify(pixelCfg)
    },
    dataType:'json',
    success:function(res){
      $btn.prop('disabled',false).html('<i class="fa-solid fa-paper-plane"></i> Kirim Test Event');
      $result.show();
      if(res.success){
        var d=res.data;
        var isOk=d.ok;
        var bodyFmt=d.body;
        try{bodyFmt=JSON.stringify(JSON.parse(d.body),null,2);}catch(e){}
        $('#tp-result-banner')
          .text((isOk?'✓ Berhasil — ':'✗ Gagal — ')+(d.message||''))
          .css({'background':isOk?'var(--ok-lt)':'var(--err-lt)','color':isOk?'#166534':'#991b1b'});
        $('#tp-result-body').text('HTTP '+d.code+'\n\nEvent: '+d.event+'\n\n'+bodyFmt);
        $status.text(isOk?'Event terkirim!':'Server menolak.').css('color',isOk?'var(--ok)':'var(--err)');
      } else {
        $('#tp-result-banner')
          .text('✗ Error — '+(res.data&&res.data.message?res.data.message:'Gagal'))
          .css({'background':'var(--err-lt)','color':'#991b1b'});
        $('#tp-result-body').text(JSON.stringify(res.data,null,2));
        $status.text('Gagal').css('color','var(--err)');
      }
    },
    error:function(x){
      $btn.prop('disabled',false).html('<i class="fa-solid fa-paper-plane"></i> Kirim Test Event');
      $status.text('Error koneksi: HTTP '+x.status).css('color','var(--err)');
    }
  });
});

/* helper */
function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s||''));return d.innerHTML;}

})(jQuery);
JS;
    }
}
