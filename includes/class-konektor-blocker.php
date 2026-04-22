<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Blocker {

    public static function init() {
        // Cookie ID set on every frontend request
        add_action( 'init', [ __CLASS__, 'set_cookie' ] );
    }

    public static function set_cookie() {
        if ( ! is_admin() ) {
            Konektor_Helper::get_or_create_cookie_id();
        }
    }

    public static function is_blocked( $campaign_id = null ) {
        global $wpdb;
        $ip        = Konektor_Helper::get_client_ip();
        $cookie_id = Konektor_Lead::resolve_vid( sanitize_text_field( $_GET['_vid'] ?? '' ) );
        $table     = $wpdb->prefix . 'konektor_blocked';

        $conditions  = [];
        $params      = [];

        if ( $ip ) {
            $conditions[] = 'ip_address = %s';
            $params[]     = $ip;
        }
        if ( $cookie_id ) {
            $conditions[] = 'cookie_id = %s';
            $params[]     = $cookie_id;
        }

        if ( empty( $conditions ) ) return false;

        $cond_sql     = implode( ' OR ', $conditions );
        $campaign_sql = $campaign_id ? 'AND (campaign_id IS NULL OR campaign_id = ' . (int) $campaign_id . ')' : 'AND campaign_id IS NULL';

        $sql = "SELECT id FROM $table WHERE ($cond_sql) $campaign_sql LIMIT 1";
        return (bool) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );
    }

    public static function block( $data, $operator_id = null ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'konektor_blocked', [
            'campaign_id' => ! empty( $data['campaign_id'] ) ? (int) $data['campaign_id'] : null,
            'ip_address'  => sanitize_text_field( $data['ip_address'] ?? '' ),
            'fingerprint' => sanitize_text_field( $data['fingerprint'] ?? '' ),
            'cookie_id'   => sanitize_text_field( $data['cookie_id'] ?? '' ),
            'phone'       => sanitize_text_field( $data['phone'] ?? '' ),
            'email'       => sanitize_email( $data['email'] ?? '' ),
            'reason'      => sanitize_textarea_field( $data['reason'] ?? '' ),
            'blocked_by'  => $operator_id ? (int) $operator_id : null,
        ] );
    }

    public static function unblock( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'konektor_blocked', [ 'id' => (int) $id ] );
    }

    public static function get_all( $args = [] ) {
        global $wpdb;
        $limit  = ! empty( $args['per_page'] ) ? (int) $args['per_page'] : 50;
        $offset = ! empty( $args['page'] ) ? ( (int) $args['page'] - 1 ) * $limit : 0;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT b.*, o.name as operator_name
             FROM {$wpdb->prefix}konektor_blocked b
             LEFT JOIN {$wpdb->prefix}konektor_operators o ON o.id = b.blocked_by
             ORDER BY b.blocked_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ) );
    }

    public static function also_block_lead( $lead_id, $operator_id, $reason = '' ) {
        $lead = Konektor_Lead::get( $lead_id );
        if ( ! $lead ) return;
        $lead = Konektor_Lead::decrypt_lead( $lead );
        self::block( [
            'campaign_id' => $lead->campaign_id,
            'ip_address'  => $lead->ip_address,
            'fingerprint' => $lead->fingerprint,
            'phone'       => $lead->phone,
            'email'       => $lead->email,
            'reason'      => $reason,
        ], $operator_id );
        Konektor_Lead::update_status( $lead_id, 'blocked', $reason, $operator_id );
    }
}
