<?php
/**
 * Plugin Deactivation
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Core;

/**
 * Handles plugin deactivation
 */
class Deactivator {

	/**
	 * Deactivate plugin
	 */
	public static function deactivate() {
		// Clean up scheduled events
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
