<?php
/**
 * Plugin Name: WPBrewer IP Check
 * Plugin URI: https://wpbrewer.com/products/wpbr-ip-check
 * Description: WPBrewer IP Check is a simple IP check tool that can verify the actual IP when the website calls external services.
 * Version: 1.0.0
 * Author: WPBrewer
 * Author URI: https://wpbrewer.com
 * Text Domain: wpbr-ip-check
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WPBrewer\IPCheck
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 */
define( 'WPBR_IP_CHECK_VERSION', '1.0.0' );

/**
 * Plugin base path.
 */
define( 'WPBR_IP_CHECK_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin base URL.
 */
define( 'WPBR_IP_CHECK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload classes.
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

/**
 * Initialize the plugin.
 */
add_action( 'plugins_loaded', function() {
	WPBrewer\IPCheck\IPCheckService::get_instance()->init();
} );
