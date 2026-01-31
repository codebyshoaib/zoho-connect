<?php
/**
 * REST API Router
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Infrastructure\API;

use ZohoConnectSerializer\Core\Config;

/**
 * REST API router
 */
class Router {

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
	 * Register REST API routes
	 */
	public function register_routes() {
		$namespace = $this->config->get( 'api_namespace' );
		$endpoint = $this->config->get( 'api_endpoint' );

		register_rest_route(
			$namespace,
			'/' . $endpoint,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_booking_request' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => $this->get_booking_args(),
			)
		);
	}

	/**
	 * Handle booking request
	 *
	 * @param \WP_REST_Request $request REST request
	 * @return \WP_REST_Response
	 */
	public function handle_booking_request( \WP_REST_Request $request ) {
		$controller = \ZohoConnectSerializer\Core\Plugin::get_instance()
			->get_container()
			->make( 'booking_controller' );

		return $controller->handle( $request );
	}

	/**
	 * Check permissions
	 *
	 * @param \WP_REST_Request $request REST request
	 * @return bool
	 */
	public function check_permissions( \WP_REST_Request $request ) {
		// Permission logic will be implemented here
		// For now, allow authenticated users or implement API key validation
		return current_user_can( 'manage_options' ) || $this->validate_api_key( $request );
	}

	/**
	 * Validate API key
	 *
	 * @param \WP_REST_Request $request REST request
	 * @return bool
	 */
	private function validate_api_key( \WP_REST_Request $request ) {
		// API key validation logic will be implemented here
		return false;
	}

	/**
	 * Get booking endpoint arguments
	 *
	 * @return array
	 */
	private function get_booking_args() {
		return array(
			// Arguments validation will be implemented here
		);
	}
}
