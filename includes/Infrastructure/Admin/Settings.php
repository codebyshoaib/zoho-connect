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
		register_setting(
			'zoho_connect_serializer_settings',
			'zoho_connect_serializer_zoho_flow_webhook_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			)
		);

		register_setting(
			'zoho_connect_serializer_settings',
			'zoho_connect_serializer_enable_logging',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		register_setting(
			'zoho_connect_serializer_settings',
			'zoho_connect_serializer_log_level',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}
}
