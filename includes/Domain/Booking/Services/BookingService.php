<?php
/**
 * Booking Service
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Domain\Booking\Services;

use ZohoConnectSerializer\Domain\Booking\Entities\BookingPayload;
use ZohoConnectSerializer\Domain\Booking\Repositories\BookingPayloadRepository;
use ZohoConnectSerializer\Domain\Serialization\Services\SerializationService;
use ZohoConnectSerializer\Domain\Webhook\Services\ZohoFlowWebhookService;

/**
 * Service for handling booking operations
 */
class BookingService {

	/**
	 * Booking payload repository
	 *
	 * @var BookingPayloadRepository
	 */
	private $repository;

	/**
	 * Serialization service
	 *
	 * @var SerializationService
	 */
	private $serialization_service;

	/**
	 * Webhook service
	 *
	 * @var ZohoFlowWebhookService
	 */
	private $webhook_service;

	/**
	 * Logger
	 *
	 * @var \ZohoConnectSerializer\Infrastructure\Logging\Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param BookingPayloadRepository $repository Repository instance
	 * @param SerializationService      $serialization_service Serialization service
	 * @param ZohoFlowWebhookService    $webhook_service Webhook service
	 * @param \ZohoConnectSerializer\Infrastructure\Logging\Logger $logger Logger instance
	 */
	public function __construct(
		BookingPayloadRepository $repository,
		SerializationService $serialization_service,
		ZohoFlowWebhookService $webhook_service,
		$logger
	) {
		$this->repository = $repository;
		$this->serialization_service = $serialization_service;
		$this->webhook_service = $webhook_service;
		$this->logger = $logger;
	}

	/**
	 * Process booking payload
	 *
	 * @param array $payload_data Raw payload data
	 * @return array
	 */
	public function process_booking( array $payload_data ) {
		try {
			// Create booking payload entity
			$payload = new BookingPayload( $payload_data );

			// Validate payload
			if ( ! $payload->is_valid() ) {
				throw new \Exception( 'Invalid booking payload' );
			}

			// Save payload
			$this->repository->save( $payload );

			// Serialize payload
			$serialized = $this->serialization_service->serialize( $payload );

			// Send to Zoho Flow
			$result = $this->webhook_service->send( $serialized );

			$this->logger->info( 'Booking processed successfully', array(
				'payload_id' => $result['id'] ?? null,
			) );

			return array(
				'success' => true,
				'data'    => $result,
			);

		} catch ( \Exception $e ) {
			$this->logger->error( 'Error processing booking', array(
				'error' => $e->getMessage(),
			) );

			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}

	/**
	 * Process CRBS booking
	 *
	 * @param int   $booking_id Booking post ID
	 * @param array $booking CRBS booking data
	 * @return array
	 */
	public function process_crbs_booking( $booking_id, array $booking ) {
		try {
			// Serialize CRBS booking for Zoho Flow
			$serialized = $this->serialization_service->serialize_crbs_booking( $booking_id, $booking );

			// For now, just output/debug (webhook sending will be enabled later)
			$debug_service = \ZohoConnectSerializer\Core\Plugin::get_instance()
				->get_container()
				->make( 'debug_service' );

			$debug_service->output_payload( $serialized, $booking_id );

			$this->logger->info( 'CRBS booking processed', array(
				'booking_id' => $booking_id,
				'payload_keys' => array_keys( $serialized ),
			) );

			return array(
				'success' => true,
				'payload' => $serialized,
			);

		} catch ( \Exception $e ) {
			$this->logger->error( 'Error processing CRBS booking ' . $booking_id, array(
				'booking_id' => $booking_id,
				'error' => $e->getMessage(),
			) );

			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}
}
