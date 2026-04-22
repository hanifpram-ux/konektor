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

        // CS Panel: /{base}/cs-panel (must be before generic slug rule)
        add_rewrite_rule( '^' . preg_quote( $base, '/' ) . '/cs-panel/?$', 'index.php?konektor_action=cs_panel', 'top' );

        // Assets: /{base}/assets/{file}
        add_rewrite_rule( '^' . preg_quote( $base, '/' ) . '/assets/([^/]+)/?$', 'index.php?konektor_asset=$matches[1]', 'top' );

        // Form submit: POST /{base}/{slug}/submit
        add_rewrite_rule( '^' . preg_quote( $base, '/' ) . '/([^/]+)/submit/?$', 'index.php?konektor_slug=$matches[1]&konektor_action=submit', 'top' );

        // Form config (for embed JS): GET /{base}/{slug}/config
        add_rewrite_rule( '^' . preg_quote( $base, '/' ) . '/([^/]+)/config/?$', 'index.php?konektor_slug=$matches[1]&konektor_action=config', 'top' );

        // Campaign page: GET /{base}/{slug}
        add_rewrite_rule( '^' . preg_quote( $base, '/' ) . '/([^/]+)/?$', 'index.php?konektor_slug=$matches[1]&konektor_action=view', 'top' );
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

        if ( $action === 'config' ) {
            self::serve_config( $campaign );
            exit;
        }

        // Default: view
        if ( $campaign->type === 'wa_link' ) {
            self::handle_wa_redirect( $campaign );
            exit;
        }

        // Form page view
        self::render_form_page( $campaign );
        exit;
    }

    // ─── WA Link Redirect ───────────────────────────────────────────

    private static function handle_wa_redirect( $campaign ) {
        // Domain check
        if ( ! Konektor_Helper::is_domain_allowed( $campaign ) ) {
            wp_die( 'Domain tidak diizinkan.', 403 );
        }

        // Block check
        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked( $campaign->id ) ) {
            $msg = $campaign->block_message ?: 'Akses Anda telah diblokir.';
            wp_die( esc_html( $msg ), 'Akses Diblokir', [ 'response' => 403 ] );
        }

        Konektor_Analytics::log_event( $campaign->id, 'wa_click' );

        // Resolve or create visitor cookie (server-side for direct WA link visits)
        $vid_from_qs = sanitize_text_field( $_GET['_vid'] ?? '' );
        $vid         = Konektor_Helper::get_or_create_cookie_id();
        if ( ! $vid && $vid_from_qs ) $vid = $vid_from_qs;

        // Double lead check (cookie + IP — no phone/email for WA clicks)
        $is_double = false;
        if ( $campaign->double_lead_enabled ) {
            $is_double = Konektor_Lead::check_double_wa( $campaign->id, $vid );
        }

        $operator = Konektor_Rotator::pick( $campaign->id );

        // Record WA click as a lead BEFORE operator check so click is always tracked
        $lead_data = [
            'campaign_id' => $campaign->id,
            'operator_id' => $operator ? $operator->id : null,
            'name'        => '',
            'email'       => '',
            'phone'       => '',
            'address'     => '',
            'source_url'  => esc_url_raw( sanitize_text_field( $_GET['_src'] ?? '' ) ?: ( $_SERVER['HTTP_REFERER'] ?? '' ) ),
            'referrer'    => esc_url_raw( $_SERVER['HTTP_REFERER'] ?? '' ),
            '_vid'        => $vid,
        ];
        $lead_id = Konektor_Lead::create( $lead_data );

        if ( ! $operator ) {
            wp_die( 'Tidak ada CS yang tersedia saat ini. Silahkan coba beberapa saat lagi.' );
        }


        if ( $is_double && $lead_id ) {
            Konektor_Lead::mark_double( $lead_id );
        }

        // Only fire analytics + pixel for non-double clicks
        if ( ! $is_double ) {
            Konektor_Analytics::log_event( $campaign->id, 'wa_click', $lead_id );

            $meta_cfg = Konektor_Meta::get_config( $campaign );
            if ( ! empty( $meta_cfg['token'] ) ) {
                Konektor_Meta::send_capi_event( $meta_cfg['form_submit_event'] ?? 'Lead', $lead_data, $meta_cfg );
            }
        }

        // Telegram notify — only if lead saved and operator has chat_id
        if ( $lead_id && $operator->telegram_chat_id ) {
            $lead_obj = Konektor_Lead::get( $lead_id );
            if ( $lead_obj ) {
                Konektor_Telegram::notify_lead( $lead_obj, $operator, $campaign );
            }
        }

        $url = Konektor_Rotator::get_redirect_url(
            $operator, $campaign,
            [ 'product_name' => $campaign->product_name, 'operator_name' => $operator->name ]
        );

        if ( ! $url ) {
            wp_die( 'Tidak dapat mengarahkan ke CS.' );
        }

        // Build client-side pixel scripts (TikTok, Meta pixel, Google) for wa_click
        $pixel_scripts = Konektor_Meta::get_pixel_script( $campaign, 'form_submit' )
                       . Konektor_Google::get_script( $campaign, 'form_submit' )
                       . Konektor_Tiktok::get_script( $campaign, 'form_submit' )
                       . Konektor_Snack::get_script( $campaign );

        // If any client-side pixel exists, render intermediate page that fires pixels then redirects
        if ( $pixel_scripts ) {
            self::render_wa_redirect_page( $url, $pixel_scripts, $campaign );
        } else {
            wp_redirect( $url, 302 );
        }
    }

    private static function render_wa_redirect_page( $url, $pixel_scripts, $campaign ) {
        $safe_url   = esc_url( $url );
        $safe_title = esc_html( $campaign->product_name ?: $campaign->name );
        status_header( 200 );
        nocache_headers();
        echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
        echo '<title>' . $safe_title . '</title>';
        echo '<meta http-equiv="refresh" content="1;url=' . $safe_url . '">';
        echo $pixel_scripts;
        echo '</head><body>';
        echo '<script>setTimeout(function(){window.location.href=' . wp_json_encode( $url ) . ';},800);</script>';
        echo '</body></html>';
    }

    // ─── Form Submit (JSON POST) ─────────────────────────────────────

    private static function handle_submit( $campaign ) {
        $is_html_embed = ! empty( $_POST['_html_embed'] );

        if ( ! $is_html_embed ) {
            header( 'Content-Type: application/json; charset=utf-8' );
        }

        // Allow cross-origin for embed (landing page lain)
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ( $origin ) {
            // Validate allowed domains
            $allowed = json_decode( $campaign->allowed_domains ?? '[]', true );
            $host    = parse_url( $origin, PHP_URL_HOST );
            $ok      = empty( $allowed ); // empty = semua boleh
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

        // Handle preflight
        if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
            http_response_code( 204 );
            exit;
        }

        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            http_response_code( 405 );
            echo wp_json_encode( [ 'success' => false, 'message' => 'Method not allowed.' ] );
            exit;
        }

        if ( $campaign->status !== 'active' ) {
            http_response_code( 400 );
            echo wp_json_encode( [ 'success' => false, 'message' => 'Kampanye tidak aktif.' ] );
            exit;
        }

        // Block check
        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked( $campaign->id ) ) {
            http_response_code( 403 );
            echo wp_json_encode( [ 'success' => false, 'blocked' => true, 'message' => $campaign->block_message ?: 'Akses Anda telah diblokir.' ] );
            exit;
        }

        // Parse body: support JSON and form-data
        $input = self::parse_input();

        $phone = Konektor_Helper::sanitize_phone( $input['phone'] ?? '' );
        $email = sanitize_email( $input['email'] ?? '' );

        // Cookie VID: prefer $_COOKIE, fallback to _vid from JSON body (cross-domain embed)
        $vid_from_body = sanitize_text_field( $input['_vid'] ?? '' );

        // Double lead check
        if ( $campaign->double_lead_enabled && Konektor_Lead::check_double( $campaign->id, $phone, $email, $vid_from_body ) ) {
            echo wp_json_encode( [
                'success'     => false,
                'double'      => true,
                'redirect_cs' => true,
                'message'     => $campaign->double_lead_message ?: 'Anda pernah mendaftar. Silahkan hubungi CS kami.',
            ] );
            exit;
        }

        // Pick operator
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
            '_vid'           => $vid_from_body,
        ];

        // Extra fields
        $std_keys = [ 'name','email','phone','address','quantity','custom_message','source_url' ];
        $extra    = array_diff_key( $input, array_flip( $std_keys ) );
        if ( $extra ) $lead_data['extra_data'] = $extra;

        $lead_id = Konektor_Lead::create( $lead_data );

        Konektor_Analytics::log_event( $campaign->id, 'form_submit', $lead_id );

        // Meta CAPI
        $meta_cfg = Konektor_Meta::get_config( $campaign );
        if ( ! empty( $meta_cfg['token'] ) ) {
            Konektor_Meta::send_capi_event( $meta_cfg['form_submit_event'] ?? 'Lead', $lead_data, $meta_cfg );
            Konektor_Meta::send_capi_event( $meta_cfg['thanks_page_event'] ?? 'Purchase', $lead_data, $meta_cfg );
        }

        // Telegram notify
        if ( $operator && $operator->telegram_chat_id && $lead_id ) {
            $lead_obj = Konektor_Lead::get( $lead_id );
            if ( $lead_obj ) {
                Konektor_Telegram::notify_lead( $lead_obj, $operator, $campaign );
            }
        }

        // Build redirect URL
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

        if ( $is_html_embed ) {
            if ( $redirect_url ) {
                wp_redirect( $redirect_url, 302 );
            } else {
                $base = Konektor_Helper::get_setting( 'base_slug', 'konektor' );
                wp_redirect( home_url( "/{$base}/{$campaign->slug}" ) );
            }
            exit;
        }

        echo wp_json_encode( [
            'success'      => true,
            'message'      => $thanks_cfg['description'] ?? 'Terima kasih!',
            'redirect_url' => $redirect_url,
            'redirect_type'=> $thanks_cfg['redirect_type'] ?? 'none',
            'delay'        => (int) ( $thanks_cfg['delay_redirect'] ?? 3 ),
        ] );
        exit;
    }

    // ─── Standalone Form Page ────────────────────────────────────────

    private static function render_form_page( $campaign ) {
        // Block check
        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked( $campaign->id ) ) {
            $msg = $campaign->block_message ?: 'Akses Anda telah diblokir.';
            wp_die( esc_html( $msg ), 'Diblokir', [ 'response' => 403 ] );
        }

        if ( ! Konektor_Helper::is_domain_allowed( $campaign ) ) {
            wp_die( 'Domain tidak diizinkan.', 403 );
        }

        Konektor_Analytics::log_event( $campaign->id, 'form_view' );

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

        $cfg     = Konektor_Campaign::get_form_config( $campaign );
        $thanks  = Konektor_Campaign::get_thanks_config( $campaign );

        // Add store/product to config for embed renderer
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
