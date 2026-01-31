<?php
/**
 * Admin Settings
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Infrastructure\Admin;

use ZohoConnectSerializer\Core\Config;

/**
 * Admin settings handler
 */
class Settings {

	/**
	 * Configuration
	 *
	 * @var Config
	 */
	private $config;

	/**
	 * Constructor
	 *
	 * @param Config $config Configuration instance
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Register settings
	 */
	public function register() {
		// Settings are now handled directly in the admin page template
		// This method is kept for future use if needed
	}
}
