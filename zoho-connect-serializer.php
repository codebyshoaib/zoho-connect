<?php
/**
 * Plugin Name: Zoho Flow Connect
 * Plugin URI: https://github.com/codebyshoaib/zoho-flow-connect
 * Description: Connects QuanticaLabs CRBS to Zoho Flow using webhooks
 * Version: 1.0.0
 * Author: Shoaib Ud Din
 * Author URI: https://github.com/codebyshoaib
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: crbs-zoho-flow-bridge
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
 * Load core classes needed for activation/deactivation
 */
require_once ZOHO_CONNECT_SERIALIZER_PLUGIN_DIR . 'includes/Core/Activator.php';
require_once ZOHO_CONNECT_SERIALIZER_PLUGIN_DIR . 'includes/Core/Deactivator.php';

/**
 * Initialize plugin updater
 * 
 * Configure this with your GitHub username and repository name.
 * Updates will be pulled from GitHub releases (tags).
 * 
 * Note: Update checker must be initialized early, before WordPress checks for updates.
 */
function zoho_connect_serializer_init_updater() {
	$github_username = 'codebyshoaib'; 
	$github_repo     = 'zoho-connect'; 
	
	$updater = new \ZohoConnectSerializer\Infrastructure\Updater\PluginUpdater(
		__FILE__,
		$github_username,
		$github_repo
	);
	$updater->init();
}

/**
 * Initialize the plugin
 */
function zoho_connect_serializer_init() {
	$autoloader = new \ZohoConnectSerializer\Includes\Autoloader();
	$autoloader->register();
	
	$plugin = \ZohoConnectSerializer\Core\Plugin::get_instance();
	$plugin->run();
}

// Initialize updater early - must run before WordPress checks for updates
add_action( 'init', 'zoho_connect_serializer_init_updater', 0 );

// Initialize plugin on plugins_loaded hook
add_action( 'plugins_loaded', 'zoho_connect_serializer_init' );

/**
 * Activation hook
 */
register_activation_hook( __FILE__, function() {
	if ( class_exists( 'ZohoConnectSerializer\Core\Activator' ) ) {
		\ZohoConnectSerializer\Core\Activator::activate();
	} else {
		// Fallback: just flush rewrite rules if class not found
		flush_rewrite_rules();
	}
} );

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, function() {
	if ( class_exists( 'ZohoConnectSerializer\Core\Deactivator' ) ) {
		\ZohoConnectSerializer\Core\Deactivator::deactivate();
	} else {
		// Fallback: just flush rewrite rules if class not found
		flush_rewrite_rules();
	}
} );
