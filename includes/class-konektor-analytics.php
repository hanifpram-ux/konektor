<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Analytics {

    public static function log_event( $campaign_id, $event_type, $lead_id = null ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'konektor_events', [
            'campaign_id' => (int) $campaign_id,
            'event_type'  => $event_type,
            'lead_id'     => $lead_id ? (int) $lead_id : null,
            'ip_address'  => Konektor_Helper::get_client_ip(),
            'referrer'    => sanitize_text_field( $_SERVER['HTTP_REFERER'] ?? '' ),
        ] );
    }

    public static function get_summary() {
        global $wpdb;
        $table_l = $wpdb->prefix . 'konektor_leads';
        $table_e = $wpdb->prefix . 'konektor_events';

        return [
            'total_leads'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_l" ),
            'new_leads'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_l WHERE status='new'" ),
            'purchased'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_l WHERE status='purchased'" ),
            'double_leads'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_l WHERE is_double=1" ),
            'blocked'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_l WHERE status='blocked'" ),
            'page_loads'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_e WHERE event_type='page_load'" ),
            'form_submits'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_e WHERE event_type='form_submit'" ),
        ];
    }

    public static function get_per_campaign() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT c.id, c.name, c.type,
                    COUNT(DISTINCT l.id) as total_leads,
                    SUM(CASE WHEN l.status='purchased' THEN 1 ELSE 0 END) as purchased,
                    SUM(CASE WHEN l.is_double=1 THEN 1 ELSE 0 END) as doubles,
                    SUM(CASE WHEN e.event_type='page_load' THEN 1 ELSE 0 END) as page_loads,
                    SUM(CASE WHEN e.event_type='form_submit' THEN 1 ELSE 0 END) as form_submits
             FROM {$wpdb->prefix}konektor_campaigns c
             LEFT JOIN {$wpdb->prefix}konektor_leads l ON l.campaign_id = c.id
             LEFT JOIN {$wpdb->prefix}konektor_events e ON e.campaign_id = c.id
             GROUP BY c.id
             ORDER BY total_leads DESC"
        );
    }

    public static function get_per_operator() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT o.id, o.name, o.type,
                    COUNT(DISTINCT l.id) as total_leads,
                    SUM(CASE WHEN l.status='purchased' THEN 1 ELSE 0 END) as purchased,
                    SUM(CASE WHEN l.status='new' THEN 1 ELSE 0 END) as pending
             FROM {$wpdb->prefix}konektor_operators o
             LEFT JOIN {$wpdb->prefix}konektor_leads l ON l.operator_id = o.id
             GROUP BY o.id
             ORDER BY total_leads DESC"
        );
    }

    public static function get_daily( $days = 30, $campaign_id = null ) {
        global $wpdb;
        $where  = '';
        $params = [ $days ];

        if ( $campaign_id ) {
            $where    = 'AND campaign_id = %d';
            $params[] = $campaign_id;
        }

        $sql = "SELECT DATE(created_at) as date, COUNT(*) as leads
                FROM {$wpdb->prefix}konektor_leads
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                $where
                GROUP BY DATE(created_at)
                ORDER BY date ASC";

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
    }

    public static function get_conversion_rate( $campaign_id = null ) {
        global $wpdb;
        $where  = $campaign_id ? $wpdb->prepare( 'WHERE campaign_id = %d', $campaign_id ) : '';
        $loads  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}konektor_events WHERE event_type='page_load' $where" );
        $submits = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}konektor_events WHERE event_type='form_submit' $where" );
        if ( ! $loads ) return 0;
        return round( ( $submits / $loads ) * 100, 2 );
    }
}
