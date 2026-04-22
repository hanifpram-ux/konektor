<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Install {

    public static function activate() {
        self::create_tables();
        self::default_settings();
        update_option( 'konektor_db_version', KONEKTOR_DB_VERSION );
        Konektor_Router::register_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = [];

        // Campaigns (Form or WA Link)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}konektor_campaigns (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(191) NOT NULL COMMENT 'Custom URL slug per campaign',
            type ENUM('form','wa_link') NOT NULL DEFAULT 'form',
            store_name VARCHAR(255) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            form_config LONGTEXT DEFAULT NULL COMMENT 'JSON: fields, template, submit label',
            thanks_page_config LONGTEXT DEFAULT NULL COMMENT 'JSON: description, redirect_type, redirect_url, redirect_cs_type, custom_message',
            pixel_config LONGTEXT DEFAULT NULL COMMENT 'JSON: meta, google, tiktok',
            double_lead_enabled TINYINT(1) NOT NULL DEFAULT 1,
            double_lead_message TEXT DEFAULT NULL,
            followup_message TEXT DEFAULT NULL COMMENT 'Pesan follow-up WA ke customer',
            allowed_domains TEXT DEFAULT NULL COMMENT 'JSON array, null=all',
            block_enabled TINYINT(1) NOT NULL DEFAULT 1,
            block_message TEXT DEFAULT NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_slug (slug)
        ) $charset;";

        // Operators / CS
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}konektor_operators (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            type ENUM('whatsapp','email','telegram','line') NOT NULL DEFAULT 'whatsapp',
            value VARCHAR(500) NOT NULL COMMENT 'Phone/email/username/token',
            status ENUM('on','off') NOT NULL DEFAULT 'on',
            work_hours_enabled TINYINT(1) NOT NULL DEFAULT 0,
            work_hours LONGTEXT DEFAULT NULL COMMENT 'JSON: [{day,start,end}]',
            telegram_chat_id VARCHAR(100) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";

        // Campaign <-> Operator pivot with weight
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}konektor_campaign_operators (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            operator_id BIGINT UNSIGNED NOT NULL,
            weight TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-10',
            PRIMARY KEY (id),
            UNIQUE KEY uq_camp_op (campaign_id, operator_id)
        ) $charset;";

        // Leads
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}konektor_leads (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            operator_id BIGINT UNSIGNED DEFAULT NULL,
            name VARCHAR(255) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            quantity VARCHAR(100) DEFAULT NULL,
            custom_message TEXT DEFAULT NULL,
            extra_data LONGTEXT DEFAULT NULL COMMENT 'JSON for extra form fields',
            ip_address VARCHAR(45) DEFAULT NULL,
            cookie_id VARCHAR(128) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            referrer TEXT DEFAULT NULL,
            fingerprint VARCHAR(64) DEFAULT NULL COMMENT 'SHA256 of phone+email',
            status ENUM('new','contacted','purchased','cancelled','blocked') NOT NULL DEFAULT 'new',
            status_note TEXT DEFAULT NULL,
            followed_up_at DATETIME DEFAULT NULL,
            is_double TINYINT(1) NOT NULL DEFAULT 0,
            source_url TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_campaign (campaign_id),
            KEY idx_operator (operator_id),
            KEY idx_fingerprint (fingerprint),
            KEY idx_status (status),
            KEY idx_cookie (cookie_id)
        ) $charset;";

        // Blocked customers
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}konektor_blocked (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL = global',
            ip_address VARCHAR(45) DEFAULT NULL,
            fingerprint VARCHAR(64) DEFAULT NULL,
            cookie_id VARCHAR(128) DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            reason TEXT DEFAULT NULL,
            blocked_by BIGINT UNSIGNED DEFAULT NULL COMMENT 'operator_id',
            blocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ip (ip_address),
            KEY idx_fingerprint (fingerprint),
            KEY idx_cookie (cookie_id)
        ) $charset;";

        // Analytics events
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}konektor_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            event_type ENUM('page_load','form_view','form_submit','thanks_page','wa_click') NOT NULL,
            lead_id BIGINT UNSIGNED DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            referrer TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_campaign_event (campaign_id, event_type),
            KEY idx_created (created_at)
        ) $charset;";

        // Settings (key-value)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}konektor_settings (
            setting_key VARCHAR(191) NOT NULL,
            setting_value LONGTEXT DEFAULT NULL,
            PRIMARY KEY (setting_key)
        ) $charset;";

        // CS Panel tokens (untuk akses halaman CS)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}konektor_operator_tokens (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            operator_id BIGINT UNSIGNED NOT NULL,
            token VARCHAR(128) NOT NULL,
            expires_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_token (token),
            KEY idx_operator (operator_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $sql as $query ) {
            dbDelta( $query );
        }

        // Add columns that may be missing from older installs
        self::maybe_add_columns();
    }

    private static function maybe_add_columns() {
        global $wpdb;
        $leads = $wpdb->prefix . 'konektor_leads';

        $existing = $wpdb->get_results( "DESCRIBE $leads", ARRAY_A );
        $cols     = array_column( $existing, 'Field' );

        if ( ! in_array( 'cookie_id', $cols ) ) {
            $wpdb->query( "ALTER TABLE $leads ADD COLUMN cookie_id VARCHAR(128) DEFAULT NULL AFTER ip_address" );
            $wpdb->query( "ALTER TABLE $leads ADD INDEX idx_cookie (cookie_id)" );
        }
        if ( ! in_array( 'referrer', $cols ) ) {
            $wpdb->query( "ALTER TABLE $leads ADD COLUMN referrer TEXT DEFAULT NULL AFTER user_agent" );
        }
        if ( ! in_array( 'source_url', $cols ) ) {
            $wpdb->query( "ALTER TABLE $leads ADD COLUMN source_url TEXT DEFAULT NULL AFTER is_double" );
        }
        if ( ! in_array( 'status_note', $cols ) ) {
            $wpdb->query( "ALTER TABLE $leads ADD COLUMN status_note TEXT DEFAULT NULL AFTER status" );
        }
        if ( ! in_array( 'followed_up_at', $cols ) ) {
            $wpdb->query( "ALTER TABLE $leads ADD COLUMN followed_up_at DATETIME DEFAULT NULL AFTER status_note" );
        }

        // Campaigns table — add followup_message column
        $campaigns = $wpdb->prefix . 'konektor_campaigns';
        $existing_c = $wpdb->get_results( "DESCRIBE $campaigns", ARRAY_A );
        $cols_c     = array_column( $existing_c, 'Field' );
        if ( ! in_array( 'followup_message', $cols_c ) ) {
            $wpdb->query( "ALTER TABLE $campaigns ADD COLUMN followup_message TEXT DEFAULT NULL AFTER double_lead_message" );
        }

        // Blocked table — add operator_name column for display
        $blocked = $wpdb->prefix . 'konektor_blocked';
        $existing_b = $wpdb->get_results( "DESCRIBE $blocked", ARRAY_A );
        $cols_b     = array_column( $existing_b, 'Field' );
        if ( ! in_array( 'operator_name', $cols_b ) ) {
            $wpdb->query( "ALTER TABLE $blocked ADD COLUMN operator_name VARCHAR(255) DEFAULT NULL AFTER blocked_by" );
        }
    }

    private static function default_settings() {
        global $wpdb;
        $table = $wpdb->prefix . 'konektor_settings';
        $defaults = [
            'telegram_bot_token'     => '',
            'allowed_domains_global' => '[]',
            'cs_panel_slug'          => 'cs-panel',
            'encrypt_lead_data'      => '1',
            'base_slug'              => 'konektor',
        ];
        foreach ( $defaults as $key => $val ) {
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT setting_key FROM $table WHERE setting_key = %s", $key ) );
            if ( ! $exists ) {
                $wpdb->insert( $table, [ 'setting_key' => $key, 'setting_value' => $val ] );
            }
        }
    }
}
