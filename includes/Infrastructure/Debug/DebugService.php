<?php
/**
 * Debug Service
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Infrastructure\Debug;

use ZohoConnectSerializer\Core\Config;
use ZohoConnectSerializer\Infrastructure\Logging\Logger;

/**
 * Service for debugging and outputting payload data
 */
class DebugService {

	/**
	 * Configuration
	 *
	 * @var Config
	 */
	private $config;

	/**
	 * Logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param Config $config Configuration instance
	 * @param Logger $logger Logger instance
	 */
	public function __construct( Config $config, Logger $logger ) {
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * Output payload data
	 *
	 * @param array $payload Serialized payload
	 * @param int   $booking_id Booking ID
	 */
	public function output_payload( array $payload, $booking_id ) {
		$output_method = $this->config->get( 'debug_output_method', 'console' );

		switch ( $output_method ) {
			case 'console':
				$this->output_to_console( $payload, $booking_id );
				// Also store for admin view even if console is selected
				$this->store_for_admin_view( $payload, $booking_id );
				break;

			case 'admin_page':
				$this->store_for_admin_view( $payload, $booking_id );
				break;

			case 'both':
				$this->output_to_console( $payload, $booking_id );
				$this->store_for_admin_view( $payload, $booking_id );
				break;
		}

		// Always log
		$this->logger->info( 'Payload output', array(
			'booking_id' => $booking_id,
			'method' => $output_method,
		) );
	}

	/**
	 * Output to console (error_log)
	 *
	 * @param array $payload Payload data
	 * @param int   $booking_id Booking ID
	 */
	private function output_to_console( array $payload, $booking_id ) {
		$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		
		error_log( sprintf(
			"\n========== CRBS Booking #%d Payload ==========\n%s\n==========================================\n",
			$booking_id,
			$json
		) );
	}

	/**
	 * Store payload for admin view
	 *
	 * @param array $payload Payload data
	 * @param int   $booking_id Booking ID
	 */
	private function store_for_admin_view( array $payload, $booking_id ) {
		// Store in post meta for admin page viewing
		update_post_meta( $booking_id, '_qzb_payload_json', wp_json_encode( $payload, JSON_PRETTY_PRINT ) );
		update_post_meta( $booking_id, '_qzb_payload_data', $payload );
		update_post_meta( $booking_id, '_qzb_payload_timestamp', current_time( 'mysql' ) );
		// Also mark as sent so it shows in the list
		update_post_meta( $booking_id, '_qzb_sent_to_zoho', '1' );
		update_post_meta( $booking_id, '_qzb_sent_at', current_time( 'mysql' ) );
	}
}
