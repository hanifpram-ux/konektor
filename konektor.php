<?php
/**
 * Plugin Name: Konektor - CS Rotator & Lead Management
 * Plugin URI: https://hanifprm.my.id
 * Description: CS Rotator, Lead Management, Custom Form, Telegram Bot, Meta CAPI, Google Ads, TikTok Ads integration with full analytics and security.
 * Version: 1.0.1
 * Author: Hanif Pramono
 * Author URI: https://hanifprm.my.id
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: konektor
 * Domain Path: /languages
 *
 * Konektor - CS Rotator & Lead Management
 * Copyright (C) 2024 Hanif Pramono (https://hanifprm.my.id)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'KONEKTOR_VERSION', '1.0.1' );
define( 'KONEKTOR_PLUGIN_FILE', __FILE__ );
define( 'KONEKTOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KONEKTOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KONEKTOR_DB_VERSION', '1.2.0' );
// KONEKTOR_ENCRYPTION_KEY di-define setelah WordPress fully loaded (di konektor_init)

require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-install.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-crypto.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-helper.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-router.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-operator.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-rotator.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-campaign.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-form.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-lead.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-blocker.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-analytics.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-settings.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/integrations/class-konektor-telegram.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/integrations/class-konektor-meta.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/integrations/class-konektor-google.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/integrations/class-konektor-tiktok.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/integrations/class-konektor-snack.php';
require_once KONEKTOR_PLUGIN_DIR . 'includes/class-konektor-api.php';
// Shortcode removed — routing handled by Konektor_Router

if ( is_admin() ) {
    require_once KONEKTOR_PLUGIN_DIR . 'admin/class-konektor-admin.php';
}

register_activation_hook( __FILE__, [ 'Konektor_Install', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Konektor_Install', 'deactivate' ] );

// Flush rewrite rules when plugin version changes
function konektor_maybe_flush_rewrite() {
    $flushed = get_option( 'konektor_rewrite_flushed', '0' );
    if ( $flushed !== KONEKTOR_VERSION ) {
        add_action( 'init', function() {
            flush_rewrite_rules();
        }, 999 );
        update_option( 'konektor_rewrite_flushed', KONEKTOR_VERSION );
    }
}
add_action( 'plugins_loaded', 'konektor_maybe_flush_rewrite', 5 );

function konektor_init() {
    if ( ! defined( 'KONEKTOR_ENCRYPTION_KEY' ) ) {
        define( 'KONEKTOR_ENCRYPTION_KEY', defined( 'KONEKTOR_SECRET_KEY' ) ? KONEKTOR_SECRET_KEY : wp_salt( 'auth' ) );
    }

    // Run DB upgrade if needed
    $installed = get_option( 'konektor_db_version', '0' );
    if ( version_compare( $installed, KONEKTOR_DB_VERSION, '<' ) ) {
        Konektor_Install::create_tables();
        update_option( 'konektor_db_version', KONEKTOR_DB_VERSION );
    }

    Konektor_Router::init();
    Konektor_API::init();
    Konektor_Blocker::init();
    if ( is_admin() ) {
        Konektor_Admin::init();
    }
    load_plugin_textdomain( 'konektor', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'konektor_init' );
