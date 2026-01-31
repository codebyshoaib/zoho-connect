<?php
/**
 * Plugin Activation
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Core;

/**
 * Handles plugin activation
 */
class Activator {

	/**
	 * Activate plugin
	 */
	public static function activate() {
		// Create database tables if needed
		// Set default options
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
