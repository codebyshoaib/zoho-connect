<?php
/**
 * Booking Payload Entity
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Domain\Booking\Entities;

/**
 * Booking payload entity
 */
class BookingPayload {

	/**
	 * Payload data
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Constructor
	 *
	 * @param array $data Payload data
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get payload data
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get specific field value
	 *
	 * @param string $key Field key
	 * @param mixed  $default Default value
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : $default;
	}

	/**
	 * Validate payload
	 *
	 * @return bool
	 */
	public function is_valid() {
		// Validation logic will be implemented here
		return ! empty( $this->data );
	}

	/**
	 * Convert to array
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->data;
	}
}
