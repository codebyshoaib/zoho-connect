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
			__( 'Zoho Connect Serializer', 'zoho-connect-serializer' ),
			__( 'Zoho Connect', 'zoho-connect-serializer' ),
			'manage_options',
			'zoho-connect-serializer',
			array( $this, 'render_page' ),
			'dashicons-admin-generic',
			30
		);
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		include ZOHO_CONNECT_SERIALIZER_PLUGIN_DIR . 'templates/admin/settings-page.php';
	}
}
