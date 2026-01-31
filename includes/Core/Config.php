<?php
/**
 * Configuration Manager
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Core;

/**
 * Configuration management
 */
class Config {

	/**
	 * Option name prefix
	 *
	 * @var string
	 */
	private $option_prefix = 'zoho_connect_serializer_';

	/**
	 * Default configuration
	 *
	 * @var array
	 */
	private $defaults = array(
		'zoho_flow_webhook_url' => '',
		'api_namespace'          => 'zoho-connect-serializer/v1',
		'api_endpoint'           => 'booking',
		'enable_logging'         => true,
		'log_level'              => 'info',
		'request_timeout'        => 30,
		'retry_attempts'         => 3,
		'retry_delay'            => 5,
		'debug_output_method'    => 'console', // console, admin_page, both
	);

	/**
	 * Get configuration value
	 *
	 * @param string $key Configuration key
	 * @param mixed  $default Default value
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$option_name = $this->option_prefix . $key;
		$value = get_option( $option_name, null );

		if ( null === $value && isset( $this->defaults[ $key ] ) ) {
			return $this->defaults[ $key ];
		}

		if ( null === $value ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Set configuration value
	 *
	 * @param string $key Configuration key
	 * @param mixed  $value Configuration value
	 * @return bool
	 */
	public function set( $key, $value ) {
		$option_name = $this->option_prefix . $key;
		return update_option( $option_name, $value );
	}

	/**
	 * Get all configuration
	 *
	 * @return array
	 */
	public function all() {
		$config = array();
		foreach ( $this->defaults as $key => $default ) {
			$config[ $key ] = $this->get( $key );
		}
		return $config;
	}
}
