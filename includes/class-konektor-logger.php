<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Logger {

    const TABLE = 'konektor_api_logs';

    public static function enabled() {
        return Konektor_Helper::get_setting( 'debug_log', '0' ) === '1';
    }

    public static function log( $platform, $event, $endpoint, $payload, $response ) {
        // Selalu log — tidak perlu setting debug untuk tracking CAPI

        global $wpdb;

        if ( is_wp_error( $response ) ) {
            $status_code = 0;
            $body        = $response->get_error_message();
            $success     = 0;
        } else {
            $status_code = wp_remote_retrieve_response_code( $response );
            $body        = wp_remote_retrieve_body( $response );
            $success     = ( $status_code >= 200 && $status_code < 300 ) ? 1 : 0;
        }

        $wpdb->insert( $wpdb->prefix . self::TABLE, [
            'platform'    => sanitize_text_field( $platform ),
            'event_name'  => sanitize_text_field( $event ),
            'endpoint'    => sanitize_text_field( $endpoint ),
            'payload'     => wp_json_encode( $payload ),
            'response'    => is_string( $body ) ? $body : wp_json_encode( $body ),
            'status_code' => (int) $status_code,
            'success'     => $success,
            'created_at'  => current_time( 'mysql' ),
        ] );
    }

    public static function get_logs( $args = [] ) {
        global $wpdb;
        $table    = $wpdb->prefix . self::TABLE;
        $where    = '1=1';
        $params   = [];
        $per_page = (int) ( $args['per_page'] ?? 50 );
        $offset   = ( (int) ( $args['page'] ?? 1 ) - 1 ) * $per_page;

        if ( ! empty( $args['platform'] ) ) {
            $where   .= ' AND platform = %s';
            $params[] = $args['platform'];
        }
        if ( isset( $args['success'] ) && $args['success'] !== '' ) {
            $where   .= ' AND success = %d';
            $params[] = (int) $args['success'];
        }

        $sql = "SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
    }

    public static function count( $args = [] ) {
        global $wpdb;
        $table  = $wpdb->prefix . self::TABLE;
        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['platform'] ) ) {
            $where   .= ' AND platform = %s';
            $params[] = $args['platform'];
        }
        if ( isset( $args['success'] ) && $args['success'] !== '' ) {
            $where   .= ' AND success = %d';
            $params[] = (int) $args['success'];
        }

        $sql = "SELECT COUNT(*) FROM $table WHERE $where";
        return (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) ) : $wpdb->get_var( $sql ) );
    }

    public static function clear() {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}" . self::TABLE );
    }

    public static function create_table() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}" . self::TABLE . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            platform VARCHAR(50) NOT NULL,
            event_name VARCHAR(100) NOT NULL,
            endpoint VARCHAR(500) NOT NULL,
            payload LONGTEXT DEFAULT NULL,
            response LONGTEXT DEFAULT NULL,
            status_code SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            success TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_platform (platform),
            KEY idx_success (success),
            KEY idx_created (created_at)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}
