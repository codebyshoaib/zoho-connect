<?php
/**
 * Serialization Service
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Domain\Serialization\Services;

use ZohoConnectSerializer\Domain\Booking\Entities\BookingPayload;

/**
 * Service for serializing payloads
 */
class SerializationService {

	/**
	 * Logger
	 *
	 * @var \ZohoConnectSerializer\Infrastructure\Logging\Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param \ZohoConnectSerializer\Infrastructure\Logging\Logger $logger Logger instance
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Serialize booking payload
	 *
	 * @param BookingPayload $payload Booking payload
	 * @return array Serialized payload
	 */
	public function serialize( BookingPayload $payload ) {
		$data = $payload->get_data();

		// Transform and format data for Zoho Flow
		$serialized = $this->transform_for_zoho_flow( $data );

		$this->logger->debug( 'Payload serialized', array(
			'original_keys' => array_keys( $data ),
			'serialized_keys' => array_keys( $serialized ),
		) );

		return $serialized;
	}

	/**
	 * Serialize CRBS booking for Zoho Flow
	 *
	 * @param int   $booking_id Booking post ID
	 * @param array $booking CRBS booking data
	 * @return array Serialized payload
	 */
	public function serialize_crbs_booking( $booking_id, array $booking ) {
		$meta = $booking['meta'] ?? array();
		$context = defined( 'PLUGIN_CRBS_CONTEXT' ) ? PLUGIN_CRBS_CONTEXT : 'crbs';

		// Log all available meta keys for debugging
		$this->logger->debug( 'Available booking meta keys', array(
			'booking_id' => $booking_id,
			'meta_keys' => array_keys( $meta ),
		) );

		// Extract customer information
		$email = $meta['client_contact_detail_email_address'] ?? '';
		$phone = $meta['client_contact_detail_phone_number'] ?? '';
		$first = $meta['client_contact_detail_first_name'] ?? '';
		$last  = $meta['client_contact_detail_last_name'] ?? '';
		$name  = trim( $first . ' ' . $last );

		// Extract booking dates - try multiple possible meta keys
		$pickup = $meta[ $context . '_pickup_datetime' ] 
			?? $meta['pickup_datetime'] 
			?? $meta[ $context . '_pickup_date' ] 
			?? $meta['pickup_date'] 
			?? '';
		
		$return = $meta[ $context . '_return_datetime' ] 
			?? $meta['return_datetime'] 
			?? $meta[ $context . '_return_date' ] 
			?? $meta['return_date'] 
			?? '';

		// Extract vehicle information
		$vehicle_id = $meta['vehicle_id'] ?? '';
		$vehicle_name = $meta['vehicle_name'] ?? '';

		// Extract location information
		$pickup_location_name = $meta['pickup_location_name'] ?? '';
		$return_location_name = $meta['return_location_name'] ?? '';

		// Extract financial information - CRBS uses specific field names
		// Currency: currency_id (e.g., "USD")
		$currency = $meta['currency_id'] 
			?? $meta[ $context . '_currency' ] 
			?? $meta['currency'] 
			?? 'USD';
		
		// Price: price_initial_value is the main booking price in CRBS
		$total = (float) ( $meta['price_initial_value'] ?? 0 );
		
		// If price_initial_value is 0, try other price fields as fallback
		if ( $total == 0 ) {
			$price_keys = array(
				$context . '_payment_total',
				'payment_total',
				$context . '_total',
				'total',
				$context . '_price',
				'price',
				$context . '_amount',
				'amount',
				$context . '_cost',
				'cost',
				$context . '_sum',
				'sum',
				$context . '_booking_total',
				'booking_total',
				$context . '_rental_total',
				'rental_total',
				$context . '_invoice_total',
				'invoice_total',
			);
			
			foreach ( $price_keys as $key ) {
				if ( isset( $meta[ $key ] ) && ! empty( $meta[ $key ] ) ) {
					$total = (float) $meta[ $key ];
					$this->logger->debug( 'Found price in fallback meta key', array(
						'booking_id' => $booking_id,
						'key' => $key,
						'value' => $total,
					) );
					break;
				}
			}
		} else {
			$this->logger->debug( 'Found price in price_initial_value', array(
				'booking_id' => $booking_id,
				'value' => $total,
			) );
		}
		
		// Extract status - try multiple possible meta keys
		$status_id = (int) ( $meta[ $context . '_booking_status_id' ] 
			?? $meta['booking_status_id'] 
			?? $meta[ $context . '_status_id' ] 
			?? $meta['status_id'] 
			?? 0 );

		// Build payload
		$payload = array(
			'event'    => 'crbs.booking.created',
			'event_id' => 'crbs_' . $booking_id,
			'booking_id' => $booking_id,
			'status_id' => $status_id,

			'customer' => array(
				'name'  => $name,
				'email' => $email,
				'phone' => $phone,
			),

			'booking' => array(
				'pickup_datetime' => $pickup,
				'return_datetime' => $return,
				'pickup_location' => $pickup_location_name,
				'return_location' => $return_location_name,
			),

			'vehicle' => array(
				'id' => $vehicle_id,
				'name' => $vehicle_name,
			),

			'invoice' => array(
				'currency' => $currency,
				'line_items' => array(
					array(
						'name' => 'Car Rental Booking #' . $booking_id,
						'qty'  => 1,
						'rate' => $total,
					),
				),
				'notes' => 'CRBS Booking #' . $booking_id,
			),
		);

		// Allow filtering of payload
		$payload = apply_filters( 'qzb_serialized_payload', $payload, $booking_id, $booking );

		$this->logger->debug( 'CRBS booking serialized', array(
			'booking_id' => $booking_id,
			'payload_keys' => array_keys( $payload ),
		) );

		return $payload;
	}

	/**
	 * Transform data for Zoho Flow format
	 *
	 * @param array $data Original data
	 * @return array Transformed data
	 */
	private function transform_for_zoho_flow( array $data ) {
		// Transformation logic will be implemented here
		// This is where you map Quantica Labs booking format to Zoho Flow format
		return $data;
	}
}
