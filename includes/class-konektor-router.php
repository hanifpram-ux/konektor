<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles custom URL routing for Konektor campaigns.
 *
 * WA Link:  GET  /{base}/{slug}          → redirect to WhatsApp/CS
 * Form page:GET  /{base}/{slug}          → render standalone form page
 * Form POST:POST /{base}/{slug}/submit   → process form submission (JSON response)
 * Assets:        /{base}/assets/{file}   → serve CSS/JS for embed
 */
class Konektor_Router {

    public static function init() {
        add_action( 'init',                [ __CLASS__, 'register_rewrite_rules' ] );
        add_filter( 'query_vars',          [ __CLASS__, 'add_query_vars' ] );
        add_action( 'template_redirect',   [ __CLASS__, 'handle_request' ] );
        add_action( 'save_post',           [ __CLASS__, 'flush_on_save' ] );
    }

    public static function register_rewrite_rules() {
        $base = Konektor_Helper::get_setting( 'base_slug', 'konektor' );
        $b    = preg_quote( $base, '/' );

        // CS Panel
        add_rewrite_rule( "^{$b}/cs-panel/?$", 'index.php?konektor_action=cs_panel', 'top' );

        // Assets
        add_rewrite_rule( "^{$b}/assets/([^/]+)/?$", 'index.php?konektor_asset=$matches[1]', 'top' );

        // Thanks page (normal): /{base}/{slug}/thanks/
        add_rewrite_rule( "^{$b}/([^/]+)/thanks/?$", 'index.php?konektor_slug=$matches[1]&konektor_action=thanks', 'top' );

        // Thanks page (double): /{base}/{slug}/thanks-double/
        add_rewrite_rule( "^{$b}/([^/]+)/thanks-double/?$", 'index.php?konektor_slug=$matches[1]&konektor_action=thanks_double', 'top' );

        // Check endpoint: /{base}/{slug}/check/
        add_rewrite_rule( "^{$b}/([^/]+)/check/?$", 'index.php?konektor_slug=$matches[1]&konektor_action=check', 'top' );

        // Form submit: POST /{base}/{slug}/submit
        add_rewrite_rule( "^{$b}/([^/]+)/submit/?$", 'index.php?konektor_slug=$matches[1]&konektor_action=submit', 'top' );

        // Pixel endpoint: GET /{base}/{slug}/pixel/ — server-side page_load event
        add_rewrite_rule( "^{$b}/([^/]+)/pixel/?$", 'index.php?konektor_slug=$matches[1]&konektor_action=pixel', 'top' );

        // Form config: GET /{base}/{slug}/config
        add_rewrite_rule( "^{$b}/([^/]+)/config/?$", 'index.php?konektor_slug=$matches[1]&konektor_action=config', 'top' );

        // Campaign page: GET /{base}/{slug}
        add_rewrite_rule( "^{$b}/([^/]+)/?$", 'index.php?konektor_slug=$matches[1]&konektor_action=view', 'top' );
    }

    public static function add_query_vars( $vars ) {
        $vars[] = 'konektor_slug';
        $vars[] = 'konektor_action';
        $vars[] = 'konektor_asset';
        return $vars;
    }

    public static function handle_request() {
        $slug   = get_query_var( 'konektor_slug' );
        $action = get_query_var( 'konektor_action' );
        $asset  = get_query_var( 'konektor_asset' );

        if ( $asset ) {
            self::serve_asset( $asset );
            exit;
        }

        if ( $action === 'cs_panel' ) {
            self::render_cs_panel();
            exit;
        }

        if ( ! $slug ) return;

        $campaign = Konektor_Campaign::get_by_slug( $slug );

        if ( ! $campaign ) {
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            return;
        }

        if ( $action === 'submit' ) {
            self::handle_submit( $campaign );
            exit;
        }

        if ( $action === 'pixel' ) {
            self::handle_pixel_event( $campaign );
            exit;
        }

        if ( $action === 'config' ) {
            self::serve_config( $campaign );
            exit;
        }

        // Thanks page normal: /{base}/{slug}/thanks/?t=TOKEN
        if ( $action === 'thanks' ) {
            $token   = sanitize_text_field( $_GET['t'] ?? '' );
            $tp_data = $token ? get_transient( 'knk_tp_' . $token ) : null;
            if ( $tp_data && (int) $tp_data['campaign_id'] === $campaign->id ) {
                delete_transient( 'knk_tp_' . $token );
                // fire_pixels=true hanya untuk link/WA — form sudah kirim di handle_submit
                $fire = ( $tp_data['source'] ?? 'form' ) === 'link';
                self::render_thanks_page( $campaign, $tp_data['redirect_url'], $fire );
            } else {
                self::render_thanks_page( $campaign, '', false );
            }
            exit;
        }

        // Thanks page double: /{base}/{slug}/thanks-double/?t=TOKEN
        if ( $action === 'thanks_double' ) {
            $token   = sanitize_text_field( $_GET['t'] ?? '' );
            $tp_data = $token ? get_transient( 'knk_tp_' . $token ) : null;
            if ( $tp_data && (int) $tp_data['campaign_id'] === $campaign->id ) {
                delete_transient( 'knk_tp_' . $token );
                self::render_thanks_double_page( $campaign, $tp_data['redirect_url'] );
            } else {
                self::render_thanks_double_page( $campaign, '' );
            }
            exit;
        }

        // Check: /{base}/{slug}/check/?_vid=VID — cek double lalu redirect ke thanks atau thanks-double
        if ( $action === 'check' ) {
            self::handle_check( $campaign );
            exit;
        }

        // Default: view — proses lead lalu redirect ke thanks page
        if ( $campaign->type === 'wa_link' || $campaign->type === 'link' ) {
            self::handle_wa_redirect( $campaign );
            exit;
        }

        // Form page view
        self::render_form_page( $campaign );
        exit;
    }

    // ─── WA Link Redirect ───────────────────────────────────────────

    private static function handle_wa_redirect( $campaign ) {
        if ( ! Konektor_Helper::is_domain_allowed( $campaign ) ) {
            wp_die( 'Domain tidak diizinkan.', 403 );
        }
        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked() ) {
            self::render_blocked_page( $campaign );
            exit;
        }

        // _vid dari embed JS (landing page domain) diutamakan — lebih akurat dari server cookie
        $vid_from_qs = sanitize_text_field( $_GET['_vid'] ?? '' );
        $vid         = $vid_from_qs ?: Konektor_Helper::get_or_create_cookie_id();

        // Cek duplicate via transient server-side (per VID + campaign)
        $is_double = $campaign->double_lead_enabled && self::has_done( $campaign->id, $vid );

        $operator = Konektor_Rotator::pick( $campaign->id );
        if ( ! $operator ) {
            wp_die( 'Tidak ada CS yang tersedia saat ini.' );
        }

        // Selalu catat lead; duplicate ditandai is_double=1
        $lead_data = [
            'campaign_id' => $campaign->id,
            'operator_id' => $operator->id,
            'name'        => '',
            'email'       => '',
            'phone'       => '',
            'address'     => '',
            'source_url'  => esc_url_raw( sanitize_text_field( $_GET['_src'] ?? '' ) ?: ( $_SERVER['HTTP_REFERER'] ?? '' ) ),
            'referrer'    => esc_url_raw( $_SERVER['HTTP_REFERER'] ?? '' ),
            '_vid'        => $vid,
        ];
        $lead_id = Konektor_Lead::create( $lead_data );
        if ( $is_double && $lead_id ) {
            Konektor_Lead::mark_double( $lead_id );
        }

        if ( ! $is_double ) {
            self::mark_done( $campaign->id, $vid );
            Konektor_Analytics::log_event( $campaign->id, 'wa_click', $lead_id );

            // Meta CAPI
            $meta_cfg = Konektor_Meta::get_config( $campaign );
            if ( ! empty( $meta_cfg['token'] ) ) {
                Konektor_Meta::send_capi_event( $meta_cfg['form_submit_event'] ?? 'Lead', $lead_data, $meta_cfg );
            }

            // TikTok Events API
            $tiktok_cfg = Konektor_Tiktok::get_config( $campaign );
            if ( ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) ) {
                Konektor_Tiktok::send_event( 'form_submit', $lead_data, $tiktok_cfg );
            }

            // Snack/Kwai Event API
            $snack_cfg = Konektor_Snack::get_config( $campaign );
            if ( ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) ) {
                Konektor_Snack::send_event( Konektor_Snack::get_event_name( $snack_cfg, 'form_submit' ), $lead_data, $snack_cfg );
            }
        }

        if ( $lead_id && $operator->telegram_chat_id ) {
            $lead_obj = Konektor_Lead::get( $lead_id );
            if ( $lead_obj ) Konektor_Telegram::notify_lead( $lead_obj, $operator, $campaign );
        }

        $url = Konektor_Rotator::get_redirect_url(
            $operator, $campaign,
            [ 'product_name' => $campaign->product_name, 'operator_name' => $operator->name ]
        );
        if ( ! $url ) wp_die( 'Tidak dapat mengarahkan ke CS.' );

        self::redirect_to_thanks( $campaign, $url, $is_double, 'link' );
    }

    // ─── Duplicate state via server transient (per VID + campaign) ──

    private static function has_done( $campaign_id, $vid ) {
        if ( ! $vid ) return false;
        return (bool) get_transient( 'knk_done_' . (int) $campaign_id . '_' . md5( $vid ) );
    }

    private static function mark_done( $campaign_id, $vid ) {
        if ( ! $vid ) return;
        set_transient( 'knk_done_' . (int) $campaign_id . '_' . md5( $vid ), 1, YEAR_IN_SECONDS );
    }

    // ─── Helpers ────────────────────────────────────────────────────

    private static function make_token( $campaign, $redirect_url, $source = 'form' ) {
        $token = wp_generate_password( 24, false );
        set_transient( 'knk_tp_' . $token, [
            'campaign_id'  => $campaign->id,
            'redirect_url' => $redirect_url,
            'source'       => $source, // 'form' atau 'link'
        ], 300 );
        return $token;
    }

    private static function make_thanks_url( $campaign, $redirect_url, $source = 'form' ) {
        $base  = Konektor_Helper::get_setting( 'base_slug', 'konektor' );
        $token = self::make_token( $campaign, $redirect_url, $source );
        return home_url( "/{$base}/{$campaign->slug}/thanks/?t={$token}" );
    }

    private static function make_thanks_double_url( $campaign, $redirect_url, $source = 'form' ) {
        $base  = Konektor_Helper::get_setting( 'base_slug', 'konektor' );
        $token = self::make_token( $campaign, $redirect_url, $source );
        return home_url( "/{$base}/{$campaign->slug}/thanks-double/?t={$token}" );
    }

    private static function redirect_to_thanks( $campaign, $redirect_url, $is_double, $source = 'form' ) {
        $url = $is_double
            ? self::make_thanks_double_url( $campaign, $redirect_url, $source )
            : self::make_thanks_url( $campaign, $redirect_url, $source );
        wp_redirect( $url, 302 );
        exit;
    }

    // ─── Check endpoint ──────────────────────────────────────────────

    private static function handle_check( $campaign ) {
        // Tidak ada lead dicatat di sini — hanya redirect berdasarkan state
        $vid = sanitize_text_field( $_GET['_vid'] ?? '' );
        if ( ! $vid ) $vid = Konektor_Helper::get_or_create_cookie_id();

        $is_double = $campaign->double_lead_enabled && self::has_done( $campaign->id, $vid );

        $operator     = Konektor_Rotator::pick( $campaign->id );
        $redirect_url = '';
        if ( $operator ) {
            $thanks_cfg = Konektor_Campaign::get_thanks_config( $campaign );
            if ( ( $thanks_cfg['redirect_type'] ?? 'cs' ) === 'cs' ) {
                $redirect_url = Konektor_Rotator::get_redirect_url( $operator, $campaign,
                    [ 'product_name' => $campaign->product_name, 'operator_name' => $operator->name ]
                );
            } elseif ( ( $thanks_cfg['redirect_type'] ?? '' ) === 'url' ) {
                $redirect_url = esc_url( $thanks_cfg['redirect_url'] ?? '' );
            }
        }

        if ( $is_double ) {
            wp_redirect( self::make_thanks_double_url( $campaign, $redirect_url ), 302 );
        } else {
            wp_redirect( self::make_thanks_url( $campaign, $redirect_url ), 302 );
        }
        exit;
    }

    // ─── Pixel endpoint: server-side page_load event ────────────────

    private static function handle_pixel_event( $campaign ) {
        // Allow cross-origin — embed di domain lain
        header( 'Access-Control-Allow-Origin: *' );
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Cache-Control: no-store' );

        if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
            http_response_code( 204 ); exit;
        }

        $source_url = esc_url_raw( sanitize_text_field( $_GET['url'] ?? ( $_SERVER['HTTP_REFERER'] ?? home_url() ) ) );
        $lead_data  = [
            'source_url' => $source_url,
            'referrer'   => esc_url_raw( $_SERVER['HTTP_REFERER'] ?? '' ),
        ];

        // Meta CAPI — page_load (PageView)
        $meta_cfg = Konektor_Meta::get_config( $campaign );
        if ( ! empty( $meta_cfg['token'] ) ) {
            $event_name = $meta_cfg['page_load_event'] ?? 'PageView';
            Konektor_Meta::send_capi_event( $event_name, $lead_data, $meta_cfg );
        }

        // TikTok Events API — page_load
        $tiktok_cfg = Konektor_Tiktok::get_config( $campaign );
        if ( ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) ) {
            Konektor_Tiktok::send_event( 'page_load', $lead_data, $tiktok_cfg );
        }

        // Snack/Kwai Event API — page_load
        $snack_cfg = Konektor_Snack::get_config( $campaign );
        if ( ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) ) {
            Konektor_Snack::send_event(
                Konektor_Snack::get_event_name( $snack_cfg, 'page_load' ),
                $lead_data,
                $snack_cfg
            );
        }

        echo wp_json_encode( [ 'ok' => true ] );
        exit;
    }

    // ─── Thanks Page (normal — first time) ──────────────────────────

    private static function render_thanks_page( $campaign, $redirect_url, $fire_pixels = false ) {
        $thanks_cfg   = Konektor_Campaign::get_thanks_config( $campaign );
        $redirect_type = $thanks_cfg['redirect_type'] ?? 'cs';
        $delay         = max( 1, (int) ( $thanks_cfg['delay_redirect'] ?? 3 ) );

        // Rebuild redirect_url jika kosong
        if ( ! $redirect_url ) {
            if ( $redirect_type === 'cs' ) {
                $op = Konektor_Rotator::pick( $campaign->id );
                if ( $op ) {
                    $redirect_url = Konektor_Rotator::get_redirect_url( $op, $campaign,
                        [ 'operator_name' => $op->name, 'product_name' => $campaign->product_name ]
                    );
                }
            } elseif ( $redirect_type === 'url' ) {
                $redirect_url = esc_url( $thanks_cfg['redirect_url'] ?? '' );
            }
        }
        if ( $redirect_type === 'none' ) $redirect_url = '';

        // Tembak thanks_page API — hanya untuk link/WA (form sudah kirim di handle_submit)
        if ( $fire_pixels ) {
            $lead_data_tp = [ 'source_url' => home_url(), 'referrer' => esc_url_raw( $_SERVER['HTTP_REFERER'] ?? '' ) ];

            $meta_cfg = Konektor_Meta::get_config( $campaign );
            if ( ! empty( $meta_cfg['token'] ) ) {
                Konektor_Meta::send_capi_event( $meta_cfg['thanks_page_event'] ?? 'Purchase', $lead_data_tp, $meta_cfg );
            }

            $tiktok_cfg = Konektor_Tiktok::get_config( $campaign );
            if ( ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) ) {
                Konektor_Tiktok::send_event( 'thanks_page', $lead_data_tp, $tiktok_cfg );
            }

            $snack_cfg = Konektor_Snack::get_config( $campaign );
            if ( ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) ) {
                Konektor_Snack::send_event( Konektor_Snack::get_event_name( $snack_cfg, 'thanks_page' ), $lead_data_tp, $snack_cfg );
            }
        }

        // Thanks page: Meta, TikTok, Snack sudah di-fire via API di handle_submit/handle_wa_redirect.
        // Hanya Google yang tetap browser-side karena butuh gclid dari browser untuk conversion tracking.
        $pixel_head = Konektor_Google::get_script( $campaign, 'thanks_page' );

        [ $btn_icon, $btn_label ] = self::resolve_btn( $redirect_url, $thanks_cfg['btn_label'] ?? '' );

        $title      = $thanks_cfg['description'] ?? 'Terima kasih!';
        $message    = $thanks_cfg['sub_message']  ?? '';
        $accent     = '#16a34a';
        $icon_bg    = '#dcfce7';
        $is_double  = false;

        status_header( 200 );
        nocache_headers();
        include KONEKTOR_PLUGIN_DIR . 'public/thanks-page.php';
    }

    // ─── Thanks Page Double (duplicate lead) ─────────────────────────

    private static function render_thanks_double_page( $campaign, $redirect_url ) {
        $thanks_cfg   = Konektor_Campaign::get_thanks_config( $campaign );
        $redirect_type = $thanks_cfg['redirect_type'] ?? 'cs';
        $delay         = max( 1, (int) ( $thanks_cfg['delay_redirect'] ?? 3 ) );

        // Untuk double: selalu redirect ke CS agar user bisa hubungi ulang
        if ( ! $redirect_url ) {
            $op = Konektor_Rotator::pick( $campaign->id );
            if ( $op ) {
                $redirect_url = Konektor_Rotator::get_redirect_url( $op, $campaign,
                    [ 'operator_name' => $op->name, 'product_name' => $campaign->product_name ]
                );
            }
        }
        if ( $redirect_type === 'none' ) $redirect_url = '';

        // Pixel — double: tidak ada pixel sama sekali
        $pixel_head = '';

        [ $btn_icon, ] = self::resolve_btn( $redirect_url, '' );
        $btn_label = 'Hubungi CS Kami';

        $double_message = $campaign->double_lead_message
            ?: 'Anda pernah mengisi form sebelumnya, silahkan dapat menghubungi CS kembali';

        $title   = 'Anda Sudah Terdaftar';
        $message = '';
        $accent  = '#d97706';
        $icon_bg = '#fef9c3';

        status_header( 200 );
        nocache_headers();
        include KONEKTOR_PLUGIN_DIR . 'public/thanks-page-double.php';
    }

    private static function resolve_btn( $redirect_url, $override_label ) {
        if ( strpos( $redirect_url, 'wa.me' ) !== false || strpos( $redirect_url, 'whatsapp' ) !== false ) {
            $icon = 'wa'; $label = 'Hubungi via WhatsApp';
        } elseif ( strpos( $redirect_url, 't.me' ) !== false || strpos( $redirect_url, 'telegram' ) !== false ) {
            $icon = 'tg'; $label = 'Hubungi via Telegram';
        } elseif ( strpos( $redirect_url, 'mailto:' ) !== false ) {
            $icon = 'mail'; $label = 'Kirim Email';
        } elseif ( strpos( $redirect_url, 'line.me' ) !== false ) {
            $icon = 'line'; $label = 'Hubungi via LINE';
        } else {
            $icon = ''; $label = 'Lanjutkan';
        }
        if ( $override_label ) $label = $override_label;
        return [ $icon, $label ];
    }

    // ─── Blocked Page ────────────────────────────────────────────────

    private static function render_blocked_page( $campaign ) {
        $message = $campaign->block_message ?: 'Akses Anda telah diblokir oleh administrator.';
        status_header( 403 );
        nocache_headers();
        include KONEKTOR_PLUGIN_DIR . 'public/blocked-page.php';
    }

    // ─── Form Submit ─────────────────────────────────────────────────

    private static function handle_submit( $campaign ) {
        // CORS untuk embed fetch (cross-origin)
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ( $origin ) {
            $allowed = json_decode( $campaign->allowed_domains ?? '[]', true );
            $host    = parse_url( $origin, PHP_URL_HOST );
            $ok      = empty( $allowed );
            if ( ! $ok ) {
                foreach ( $allowed as $d ) {
                    if ( $host === $d || str_ends_with( $host, '.' . $d ) ) { $ok = true; break; }
                }
            }
            if ( $ok ) {
                header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
                header( 'Access-Control-Allow-Methods: POST, OPTIONS' );
                header( 'Access-Control-Allow-Headers: Content-Type, X-Requested-With' );
                header( 'Vary: Origin' );
            }
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
            http_response_code( 204 );
            exit;
        }

        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            wp_die( 'Method not allowed.', 405 );
        }

        // Embed form kirim JSON; standalone form POST biasa
        $ct       = $_SERVER['CONTENT_TYPE'] ?? '';
        $is_embed = str_contains( $ct, 'application/json' );

        if ( $is_embed ) {
            header( 'Content-Type: application/json; charset=utf-8' );
        }

        if ( $campaign->status !== 'active' ) {
            if ( $is_embed ) {
                echo wp_json_encode( [ 'success' => false, 'message' => 'Kampanye tidak aktif.' ] );
            } else {
                wp_die( 'Kampanye tidak aktif.' );
            }
            exit;
        }

        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked() ) {
            if ( $is_embed ) {
                echo wp_json_encode( [ 'success' => false, 'blocked' => true, 'message' => $campaign->block_message ?: 'Akses Anda telah diblokir.' ] );
            } else {
                self::render_blocked_page( $campaign );
            }
            exit;
        }

        $input = self::parse_input();

        $phone = Konektor_Helper::sanitize_phone( $input['phone'] ?? '' );
        $email = sanitize_email( $input['email'] ?? '' );
        // _vid dari embed JS diutamakan, fallback ke server cookie
        $vid   = sanitize_text_field( $input['_vid'] ?? $_COOKIE['konektor_vid'] ?? '' );

        // Cek duplicate via transient server-side (per VID + campaign)
        $is_double = $campaign->double_lead_enabled && self::has_done( $campaign->id, $vid );

        $operator = Konektor_Rotator::pick( $campaign->id );

        $lead_data = [
            'campaign_id'    => $campaign->id,
            'operator_id'    => $operator ? $operator->id : null,
            'name'           => sanitize_text_field( $input['name']    ?? '' ),
            'email'          => $email,
            'phone'          => $phone,
            'address'        => sanitize_textarea_field( $input['address']  ?? '' ),
            'quantity'       => sanitize_text_field( $input['quantity'] ?? '' ),
            'custom_message' => sanitize_textarea_field( $input['custom_message'] ?? '' ),
            'product_name'   => $campaign->product_name,
            'source_url'     => esc_url_raw( $input['source_url'] ?? ( $_SERVER['HTTP_REFERER'] ?? '' ) ),
            '_vid'           => $vid,
        ];

        $std_keys = [ 'name','email','phone','address','quantity','custom_message','source_url','_vid' ];
        $extra    = array_diff_key( $input, array_flip( $std_keys ) );
        if ( $extra ) $lead_data['extra_data'] = $extra;

        $lead_id = Konektor_Lead::create( $lead_data );
        if ( $is_double && $lead_id ) {
            Konektor_Lead::mark_double( $lead_id );
        }

        if ( ! $is_double ) {
            self::mark_done( $campaign->id, $vid );
            Konektor_Analytics::log_event( $campaign->id, 'form_submit', $lead_id );

            // Meta CAPI — form_submit + thanks_page
            $meta_cfg = Konektor_Meta::get_config( $campaign );

            // Debug log: rekam state saat submit
            global $wpdb;
            $wpdb->insert( $wpdb->prefix . 'konektor_api_logs', [
                'platform'    => 'DEBUG submit',
                'event_name'  => 'submit_trace',
                'endpoint'    => 'internal',
                'payload'     => wp_json_encode( [
                    'token_set'         => ! empty( $meta_cfg['token'] ),
                    'pixel_id'          => $meta_cfg['pixel_id'] ?? '(kosong)',
                    'form_submit_event' => $meta_cfg['form_submit_event'] ?? '(kosong)',
                    'thanks_page_event' => $meta_cfg['thanks_page_event'] ?? '(kosong)',
                    'test_event_code'   => $meta_cfg['test_event_code'] ?? '(kosong)',
                    'is_double'         => $is_double,
                    'vid'               => $vid,
                    'phone'             => $phone,
                    'name'              => $lead_data['name'],
                ] ),
                'response'    => 'debug only',
                'status_code' => 0,
                'success'     => 1,
                'created_at'  => current_time( 'mysql' ),
            ] );

            if ( ! empty( $meta_cfg['token'] ) ) {
                Konektor_Meta::send_capi_event( $meta_cfg['form_submit_event'] ?? 'Lead', $lead_data, $meta_cfg );
                Konektor_Meta::send_capi_event( $meta_cfg['thanks_page_event'] ?? 'Purchase', $lead_data, $meta_cfg );
            }

            // TikTok Events API — form_submit + thanks_page
            $tiktok_cfg = Konektor_Tiktok::get_config( $campaign );
            if ( ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) ) {
                Konektor_Tiktok::send_event( 'form_submit', $lead_data, $tiktok_cfg );
                Konektor_Tiktok::send_event( 'thanks_page', $lead_data, $tiktok_cfg );
            }

            // Snack/Kwai Event API — form_submit + thanks_page
            $snack_cfg = Konektor_Snack::get_config( $campaign );
            if ( ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) ) {
                Konektor_Snack::send_event( Konektor_Snack::get_event_name( $snack_cfg, 'form_submit' ), $lead_data, $snack_cfg );
                Konektor_Snack::send_event( Konektor_Snack::get_event_name( $snack_cfg, 'thanks_page' ), $lead_data, $snack_cfg );
            }
        }

        if ( $operator && $operator->telegram_chat_id && $lead_id ) {
            $lead_obj = Konektor_Lead::get( $lead_id );
            if ( $lead_obj ) {
                Konektor_Telegram::notify_lead( $lead_obj, $operator, $campaign );
            }
        }

        $thanks_cfg   = Konektor_Campaign::get_thanks_config( $campaign );
        $redirect_url = '';

        if ( $thanks_cfg['redirect_type'] === 'cs' && $operator ) {
            $redirect_url = Konektor_Rotator::get_redirect_url(
                $operator, $campaign,
                array_merge( $lead_data, [ 'operator_name' => $operator->name ] )
            );
        } elseif ( $thanks_cfg['redirect_type'] === 'url' && ! empty( $thanks_cfg['redirect_url'] ) ) {
            $redirect_url = esc_url( $thanks_cfg['redirect_url'] );
        }

        $tp_url = $is_double
            ? self::make_thanks_double_url( $campaign, $redirect_url )
            : self::make_thanks_url( $campaign, $redirect_url );

        if ( $is_embed ) {
            echo wp_json_encode( [ 'success' => true, 'double' => $is_double, 'thanks_page_url' => $tp_url ] );
        } else {
            wp_redirect( $tp_url, 302 );
        }
        exit;
    }

    // ─── Standalone Form Page ────────────────────────────────────────

    private static function render_form_page( $campaign ) {
        // Block check
        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked() ) {
            self::render_blocked_page( $campaign );
            exit;
        }

        if ( ! Konektor_Helper::is_domain_allowed( $campaign ) ) {
            wp_die( 'Domain tidak diizinkan.', 403 );
        }

        Konektor_Analytics::log_event( $campaign->id, 'form_view' );

        // CAPI server-side page_load — fire di sini agar tercatat di panel platform
        $page_lead_data = [
            'source_url' => home_url( '/' . Konektor_Helper::get_setting( 'base_slug', 'konektor' ) . '/' . $campaign->slug . '/' ),
            'referrer'   => esc_url_raw( $_SERVER['HTTP_REFERER'] ?? '' ),
        ];

        $meta_cfg = Konektor_Meta::get_config( $campaign );
        if ( ! empty( $meta_cfg['token'] ) ) {
            $event_name = $meta_cfg['page_load_event'] ?? 'PageView';
            Konektor_Meta::send_capi_event( $event_name, $page_lead_data, $meta_cfg );
        }

        $tiktok_cfg = Konektor_Tiktok::get_config( $campaign );
        if ( ! empty( $tiktok_cfg['pixel_id'] ) && ! empty( $tiktok_cfg['access_token'] ) ) {
            Konektor_Tiktok::send_event( 'page_load', $page_lead_data, $tiktok_cfg );
        }

        $snack_cfg = Konektor_Snack::get_config( $campaign );
        if ( ! empty( $snack_cfg['pixel_id'] ) && ! empty( $snack_cfg['access_token'] ) ) {
            Konektor_Snack::send_event( Konektor_Snack::get_event_name( $snack_cfg, 'page_load' ), $page_lead_data, $snack_cfg );
        }

        $submit_url = Konektor_Campaign::get_submit_url( $campaign );
        $config     = Konektor_Campaign::get_form_config( $campaign );
        $thanks_cfg = Konektor_Campaign::get_thanks_config( $campaign );

        $pixel_head = Konektor_Meta::get_pixel_script( $campaign, 'page_load' )
                    . Konektor_Google::get_script( $campaign, 'page_load' )
                    . Konektor_Tiktok::get_script( $campaign, 'page_load' )
                    . Konektor_Snack::get_script( $campaign );

        include KONEKTOR_PLUGIN_DIR . 'public/form-page.php';
    }

    // ─── Form config endpoint (for embed JS) ────────────────────────

    private static function serve_config( $campaign ) {
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Access-Control-Allow-Origin: *' );

        $cfg    = Konektor_Campaign::get_form_config( $campaign );
        $thanks = Konektor_Campaign::get_thanks_config( $campaign );

        $cfg['store_name']   = $campaign->store_name;
        $cfg['product_name'] = $campaign->product_name;

        echo wp_json_encode( [
            'success'      => true,
            'type'         => $campaign->type,
            'form_config'  => $cfg,
            'thanks_config'=> $thanks,
        ] );
    }

    // ─── Serve static assets (CSS/JS for embed) ─────────────────────

    private static function serve_asset( $file ) {
        $allowed = [ 'form.css', 'form.js' ];
        if ( ! in_array( $file, $allowed, true ) ) {
            http_response_code( 404 );
            exit;
        }

        $map = [
            'form.css' => KONEKTOR_PLUGIN_DIR . 'public/css/konektor-public.css',
            'form.js'  => KONEKTOR_PLUGIN_DIR . 'public/js/konektor-embed.js',
        ];
        $path = $map[ $file ];

        if ( ! file_exists( $path ) ) { http_response_code( 404 ); exit; }

        $mime = str_ends_with( $file, '.css' ) ? 'text/css' : 'application/javascript';
        header( 'Content-Type: ' . $mime . '; charset=utf-8' );
        header( 'Cache-Control: public, max-age=86400' );
        readfile( $path );
    }

    // ─── CS Panel ───────────────────────────────────────────────────

    private static function render_cs_panel() {
        $token    = sanitize_text_field( $_GET['token'] ?? '' );
        $operator = $token ? Konektor_Operator::verify_panel_token( $token ) : null;

        // Handle AJAX actions (POST with action param)
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            header( 'Content-Type: application/json; charset=utf-8' );
            if ( ! $operator ) {
                echo wp_json_encode( [ 'success' => false, 'message' => 'Token tidak valid.' ] );
                exit;
            }
            $act = sanitize_text_field( $_POST['act'] ?? '' );
            self::handle_cs_ajax( $act, $operator );
            exit;
        }

        include KONEKTOR_PLUGIN_DIR . 'public/cs-panel.php';
    }

    private static function handle_cs_ajax( $act, $operator ) {
        global $wpdb;

        if ( $act === 'get_leads' ) {
            $page        = max( 1, (int) ( $_POST['page'] ?? 1 ) );
            $status      = sanitize_text_field( $_POST['status'] ?? '' );
            $campaign_id = (int) ( $_POST['campaign_id'] ?? 0 );
            $per_page    = 20;
            $offset      = ( $page - 1 ) * $per_page;

            $where  = 'l.operator_id = %d';
            $params = [ $operator->operator_id ];
            if ( $status ) { $where .= ' AND l.status = %s'; $params[] = $status; }
            if ( $campaign_id ) { $where .= ' AND l.campaign_id = %d'; $params[] = $campaign_id; }

            $total = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}konektor_leads l WHERE $where", ...$params
            ) );

            $leads_raw = $wpdb->get_results( $wpdb->prepare(
                "SELECT l.*, c.name AS camp_name FROM {$wpdb->prefix}konektor_leads l
                 LEFT JOIN {$wpdb->prefix}konektor_campaigns c ON c.id = l.campaign_id
                 WHERE $where ORDER BY l.id DESC LIMIT %d OFFSET %d",
                ...array_merge( $params, [ $per_page, $offset ] )
            ) );

            $out = [];
            foreach ( $leads_raw as $l ) {
                $d = Konektor_Lead::decrypt_lead( clone $l );
                $out[] = [
                    'id'         => (int) $l->id,
                    'campaign'   => $l->camp_name ?? '',
                    'name'       => $d->name ?? '',
                    'phone'      => $d->phone ?? '',
                    'email'      => $d->email ?? '',
                    'address'    => $d->address ?? '',
                    'quantity'   => $d->quantity ?? '',
                    'message'    => $d->custom_message ?? '',
                    'status'     => $l->status,
                    'is_double'     => (bool) $l->is_double,
                    'ip'            => $l->ip_address ?? '',
                    'cookie'        => $l->cookie_id ?? '',
                    'referrer'      => $l->referrer ?? '',
                    'ua'            => $l->user_agent ?? '',
                    'date'          => $l->created_at,
                    'followed_up_at'=> $l->followed_up_at ?? '',
                ];
            }
            echo wp_json_encode( [ 'success' => true, 'leads' => $out, 'total' => $total, 'pages' => ceil( $total / $per_page ) ] );
            return;
        }

        if ( $act === 'update_status' ) {
            $lead_id = (int) ( $_POST['lead_id'] ?? 0 );
            $status  = sanitize_text_field( $_POST['status'] ?? '' );
            $note    = sanitize_textarea_field( $_POST['note'] ?? '' );
            $ok = Konektor_Lead::update_status( $lead_id, $status, $note, $operator->operator_id );
            echo wp_json_encode( [ 'success' => (bool) $ok ] );
            return;
        }

        if ( $act === 'block_lead' ) {
            $lead_id = (int) ( $_POST['lead_id'] ?? 0 );
            $reason  = sanitize_textarea_field( $_POST['reason'] ?? '' );
            Konektor_Blocker::also_block_lead( $lead_id, $operator->operator_id, $reason );
            echo wp_json_encode( [ 'success' => true ] );
            return;
        }

        if ( $act === 'follow_up' ) {
            $lead_id      = (int) ( $_POST['lead_id'] ?? 0 );
            $lead         = Konektor_Lead::get( $lead_id );
            if ( ! $lead ) {
                echo wp_json_encode( [ 'success' => false, 'message' => 'Lead tidak ditemukan.' ] );
                return;
            }
            $campaign     = Konektor_Campaign::get( $lead->campaign_id );
            $op_obj       = $lead->operator_id ? Konektor_Operator::get( $lead->operator_id ) : null;
            $decrypt      = Konektor_Lead::decrypt_lead( clone $lead );
            $url          = $campaign ? Konektor_Rotator::get_followup_url( $campaign, $decrypt, $op_obj ) : '';
            Konektor_Lead::mark_followed_up( $lead_id );
            echo wp_json_encode( [ 'success' => true, 'url' => $url ] );
            return;
        }

        if ( $act === 'get_campaigns' ) {
            $camps = $wpdb->get_results( $wpdb->prepare(
                "SELECT DISTINCT c.id, c.name FROM {$wpdb->prefix}konektor_campaigns c
                 JOIN {$wpdb->prefix}konektor_campaign_operators co ON co.campaign_id = c.id
                 WHERE co.operator_id = %d ORDER BY c.name ASC",
                $operator->operator_id
            ) );
            echo wp_json_encode( [ 'success' => true, 'campaigns' => $camps ] );
            return;
        }

        echo wp_json_encode( [ 'success' => false, 'message' => 'Unknown action.' ] );
    }

    // ─── Helpers ────────────────────────────────────────────────────

    private static function parse_input() {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if ( str_contains( $ct, 'application/json' ) ) {
            $raw = file_get_contents( 'php://input' );
            return json_decode( $raw, true ) ?: [];
        }
        return $_POST;
    }

    public static function flush_on_save( $post_id ) {
        // Flush rewrite rules when settings saved (handled via settings save AJAX instead)
    }

    public static function flush() {
        flush_rewrite_rules();
    }
}
