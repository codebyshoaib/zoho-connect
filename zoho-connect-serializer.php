<?php
/**
 * Plugin Name: Zoho Connect Serializer
 * Plugin URI: https://example.com/zoho-connect-serializer
 * Description: Serializes payloads from Quantica Labs booking plugin and sends to Zoho Flow via webhook
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: zoho-connect-serializer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin version
 */
define( 'ZOHO_CONNECT_SERIALIZER_VERSION', '1.0.0' );
define( 'ZOHO_CONNECT_SERIALIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZOHO_CONNECT_SERIALIZER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ZOHO_CONNECT_SERIALIZER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader
 */
require_once ZOHO_CONNECT_SERIALIZER_PLUGIN_DIR . 'includes/Autoloader.php';

/**
 * Initialize the plugin
 */
function zoho_connect_serializer_init() {
	$autoloader = new \ZohoConnectSerializer\Includes\Autoloader();
	$autoloader->register();
	
	$plugin = \ZohoConnectSerializer\Core\Plugin::get_instance();
	$plugin->run();
}

// Initialize plugin on plugins_loaded hook
add_action( 'plugins_loaded', 'zoho_connect_serializer_init' );

/**
 * Activation hook
 */
register_activation_hook( __FILE__, function() {
	\ZohoConnectSerializer\Core\Activator::activate();
} );

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, function() {
	\ZohoConnectSerializer\Core\Deactivator::deactivate();
} );
