<?php
/**
 * Admin Page
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Infrastructure\Admin;

use ZohoConnectSerializer\Core\Config;

/**
 * Admin page handler
 */
class AdminPage {

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
	 * Register admin page
	 */
	public function register() {
		add_menu_page(
			__( 'CRBS → Zoho Flow Bridge', 'crbs-zoho-flow-bridge' ),
			__( 'Zoho Flow Bridge', 'crbs-zoho-flow-bridge' ),
			'manage_options',
			'crbs-zoho-flow-bridge',
			array( $this, 'render_settings_page' ),
			'dashicons-admin-generic',
			30
		);

		// Add submenu for debug view
		add_submenu_page(
			'crbs-zoho-flow-bridge',
			__( 'View Payloads', 'crbs-zoho-flow-bridge' ),
			__( 'View Payloads', 'crbs-zoho-flow-bridge' ),
			'manage_options',
			'crbs-zoho-flow-bridge-payloads',
			array( $this, 'render_payloads_page' )
		);
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		include ZOHO_CONNECT_SERIALIZER_PLUGIN_DIR . 'templates/admin/settings-page.php';
	}

	/**
	 * Render payloads debug page
	 */
	public function render_payloads_page() {
		include ZOHO_CONNECT_SERIALIZER_PLUGIN_DIR . 'templates/admin/payloads-page.php';
	}
}
