<?php
/**
 * Manual Trigger for Processing Bookings
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Infrastructure\Admin;

use ZohoConnectSerializer\Domain\Booking\Services\BookingService;

/**
 * Manual trigger for processing bookings
 */
class ManualTrigger {

	/**
	 * Handle manual processing request
	 *
	 * @param int $booking_id Booking ID
	 * @return array Result
	 */
	public static function process_booking( $booking_id ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array(
				'success' => false,
				'error' => 'Permission denied',
			);
		}

		if ( ! class_exists( 'CRBSBooking' ) ) {
			return array(
				'success' => false,
				'error' => 'CRBS plugin not active',
			);
		}

		$Booking = new \CRBSBooking();
		$booking = $Booking->getBooking( $booking_id );

		if ( ! $booking || ! is_array( $booking ) ) {
			return array(
				'success' => false,
				'error' => 'Could not load booking from CRBS',
			);
		}

		// Get services
		$plugin = \ZohoConnectSerializer\Core\Plugin::get_instance();
		$container = $plugin->get_container();
		
		$booking_service = $container->make( 'booking_service' );
		
		$result = $booking_service->process_crbs_booking( $booking_id, $booking );

		if ( $result['success'] ) {
			// Store payload
			update_post_meta( $booking_id, '_qzb_sent_to_zoho', '1' );
			update_post_meta( $booking_id, '_qzb_sent_at', current_time( 'mysql' ) );
			update_post_meta( $booking_id, '_qzb_payload_data', $result['payload'] );
			update_post_meta( $booking_id, '_qzb_payload_json', wp_json_encode( $result['payload'], JSON_PRETTY_PRINT ) );
		}

		return $result;
	}
}
