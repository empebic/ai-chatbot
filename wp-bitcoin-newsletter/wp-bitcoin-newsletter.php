<?php
/**
 * Plugin Name: WP Bitcoin Newsletter (Pay-per-Subscribe)
 * Description: Newsletter subscriptions are processed only after successful Bitcoin Lightning payment. Supports multiple newsletter providers and subscriber management.
 * Version: 0.1.0
 * Author: Your Company
 * Text Domain: wpbn
 * License: GPL-2.0-or-later
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPBN_VERSION', '0.1.0' );
define( 'WPBN_PLUGIN_FILE', __FILE__ );
define( 'WPBN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPBN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register a simple PSR-4 autoloader for this plugin namespace.
 */
spl_autoload_register(
    function ( $class ) {
        $prefix = 'WpBitcoinNewsletter\\';
        if ( strpos( $class, $prefix ) !== 0 ) {
            return;
        }
        $relative = substr( $class, strlen( $prefix ) );
        $file     = WPBN_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
);

/**
 * Activation: create or update required database tables.
 */
register_activation_hook(
    __FILE__,
    function () {
        if ( ! class_exists( 'WpBitcoinNewsletter\\Database\\Installer' ) ) {
            require_once WPBN_PLUGIN_DIR . 'src/Database/Installer.php';
        }
        \WpBitcoinNewsletter\Database\Installer::activate();
    }
);

/**
 * Bootstrap plugin after all plugins are loaded.
 */
add_action(
    'plugins_loaded',
    function () {
        if ( ! class_exists( 'WpBitcoinNewsletter\\Plugin' ) ) {
            require_once WPBN_PLUGIN_DIR . 'src/Plugin.php';
        }
        \WpBitcoinNewsletter\Plugin::instance()->boot();
    }
);

