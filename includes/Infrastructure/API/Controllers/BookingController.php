<?php
/**
 * Booking Controller
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Infrastructure\API\Controllers;

use ZohoConnectSerializer\Domain\Booking\Services\BookingService;

/**
 * REST API controller for booking endpoints
 */
class BookingController {

	/**
	 * Booking service
	 *
	 * @var BookingService
	 */
	private $booking_service;

	/**
	 * Logger
	 *
	 * @var \ZohoConnectSerializer\Infrastructure\Logging\Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param BookingService $booking_service Booking service instance
	 * @param \ZohoConnectSerializer\Infrastructure\Logging\Logger $logger Logger instance
	 */
	public function __construct( BookingService $booking_service, $logger ) {
		$this->booking_service = $booking_service;
		$this->logger = $logger;
	}

	/**
	 * Handle booking request
	 *
	 * @param \WP_REST_Request $request REST request
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ) {
		$payload = $request->get_json_params();

		$this->logger->info( 'Received booking request', array(
			'payload_keys' => array_keys( $payload ?? array() ),
		) );

		$result = $this->booking_service->process_booking( $payload ?? array() );

		if ( $result['success'] ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $result['data'],
				),
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => false,
				'error'   => $result['error'],
			),
			400
		);
	}
}
