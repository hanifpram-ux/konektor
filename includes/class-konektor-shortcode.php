<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Shortcode {

    public static function init() {
        add_shortcode( 'konektor_form',    [ __CLASS__, 'form_shortcode' ] );
        add_shortcode( 'konektor_wa_link', [ __CLASS__, 'wa_link_shortcode' ] );
        add_action( 'wp_enqueue_scripts',  [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'template_redirect',   [ __CLASS__, 'maybe_render_cs_panel' ] );
    }

    public static function enqueue_assets() {
        wp_enqueue_style(
            'konektor-public',
            KONEKTOR_PLUGIN_URL . 'public/css/konektor-public.css',
            [],
            KONEKTOR_VERSION
        );
        wp_enqueue_script(
            'konektor-public',
            KONEKTOR_PLUGIN_URL . 'public/js/konektor-public.js',
            [ 'jquery' ],
            KONEKTOR_VERSION,
            true
        );
        wp_localize_script( 'konektor-public', 'KonektorAjax', [
            'url'   => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'konektor_nonce' ),
        ] );
    }

    public static function form_shortcode( $atts ) {
        $atts     = shortcode_atts( [ 'id' => 0 ], $atts );
        $campaign = Konektor_Campaign::get( (int) $atts['id'] );

        if ( ! $campaign || $campaign->status !== 'active' || $campaign->type !== 'form' ) {
            return '';
        }

        // Block check
        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked( $campaign->id ) ) {
            $msg = $campaign->block_message ?: 'Akses Anda telah diblokir.';
            return '<div class="konektor-blocked">' . esc_html( $msg ) . '</div>';
        }

        // Domain check
        if ( ! Konektor_Helper::is_domain_allowed( $campaign ) ) {
            return '';
        }

        Konektor_Analytics::log_event( $campaign->id, 'form_view' );

        $pixel_scripts = self::collect_pixel_scripts( $campaign, 'page_load' );

        return $pixel_scripts . Konektor_Form::render( $campaign );
    }

    public static function wa_link_shortcode( $atts ) {
        $atts     = shortcode_atts( [ 'id' => 0, 'label' => 'Chat via WhatsApp', 'class' => '' ], $atts );
        $campaign = Konektor_Campaign::get( (int) $atts['id'] );

        if ( ! $campaign || $campaign->status !== 'active' ) return '';

        if ( $campaign->block_enabled && Konektor_Blocker::is_blocked( $campaign->id ) ) {
            return '<div class="konektor-blocked">' . esc_html( $campaign->block_message ?: 'Akses diblokir.' ) . '</div>';
        }

        Konektor_Analytics::log_event( $campaign->id, 'page_load' );

        $pixel_scripts = self::collect_pixel_scripts( $campaign, 'page_load' );
        $btn_class     = 'konektor-wa-btn ' . esc_attr( $atts['class'] );
        $label         = esc_html( $atts['label'] );
        $campaign_id   = esc_attr( $campaign->id );

        return $pixel_scripts . "<div class='konektor-wa-wrap'><button class='{$btn_class}' data-campaign='{$campaign_id}'>{$label}</button></div>";
    }

    public static function collect_pixel_scripts( $campaign, $event_type ) {
        $out = '';
        $out .= Konektor_Meta::get_pixel_script( $campaign, $event_type );
        $out .= Konektor_Google::get_script( $campaign, $event_type );
        $out .= Konektor_Tiktok::get_script( $campaign, $event_type );
        $out .= Konektor_Tiktok::get_script( $campaign, $event_type, true ); // Snack Video
        return $out;
    }

    public static function maybe_render_cs_panel() {
        $slug  = Konektor_Helper::get_setting( 'cs_panel_slug', 'cs-panel' );
        $path  = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );

        if ( $path !== $slug ) return;

        $token    = sanitize_text_field( $_GET['token'] ?? '' );
        $operator = $token ? Konektor_Operator::verify_panel_token( $token ) : null;

        include KONEKTOR_PLUGIN_DIR . 'public/cs-panel.php';
        exit;
    }
}
