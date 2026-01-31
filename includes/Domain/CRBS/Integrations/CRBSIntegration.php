<?php
/**
 * CRBS Integration
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Domain\CRBS\Integrations;

use ZohoConnectSerializer\Domain\Booking\Services\BookingService;
use ZohoConnectSerializer\Infrastructure\Logging\Logger;

/**
 * Integration with CRBS (Quantica Labs) booking plugin
 */
class CRBSIntegration {

	/**
	 * Booking service
	 *
	 * @var BookingService
	 */
	private $booking_service;

	/**
	 * Logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * CRBS CPT name
	 *
	 * @var string
	 */
	private $cpt_name = '';

	/**
	 * Constructor
	 *
	 * @param BookingService $booking_service Booking service instance
	 * @param Logger         $logger Logger instance
	 */
	public function __construct( BookingService $booking_service, Logger $logger ) {
		$this->booking_service = $booking_service;
		$this->logger = $logger;
	}

	/**
	 * Initialize CRBS integration
	 */
	public function init() {
		// Check if CRBS is active
		if ( ! class_exists( 'CRBSBooking' ) ) {
			$this->logger->warning( 'CRBS plugin is not active' );
			return;
		}

		// Get CRBS CPT name
		$Booking = new \CRBSBooking();
		$this->cpt_name = $Booking->getCPTName();

		if ( empty( $this->cpt_name ) ) {
			$this->logger->warning( 'Could not get CRBS CPT name' );
			return;
		}

		// Hook into CRBS booking save
		add_action( "save_post_{$this->cpt_name}", array( $this, 'on_booking_saved' ), 10, 3 );

		$this->logger->info( 'CRBS integration initialized', array(
			'cpt_name' => $this->cpt_name,
		) );
	}

	/**
	 * Handle booking save
	 *
	 * @param int      $post_id Post ID
	 * @param \WP_Post $post Post object
	 * @param bool     $update Whether this is an update
	 */
	public function on_booking_saved( $post_id, $post, $update ) {
		// Log that hook was triggered
		$this->logger->info( 'CRBS booking save hook triggered', array( 
			'post_id' => $post_id,
			'post_status' => $post->post_status ?? 'unknown',
			'is_update' => $update,
		) );

		// Safety guards
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			$this->logger->debug( 'Skipping autosave/revision', array( 'post_id' => $post_id ) );
			return;
		}

		if ( $post->post_status !== 'publish' ) {
			$this->logger->debug( 'Post not published, skipping', array( 
				'post_id' => $post_id,
				'post_status' => $post->post_status,
			) );
			return;
		}

		// Prevent duplicate sending (optional - can be removed for testing)
		$sent = get_post_meta( $post_id, '_qzb_sent_to_zoho', true );
		if ( $sent === '1' && ! defined( 'QZB_FORCE_RESEND' ) ) {
			$this->logger->debug( 'Booking already sent, skipping', array( 'post_id' => $post_id ) );
			return;
		}

		// Load booking via CRBS
		$Booking = new \CRBSBooking();
		$booking = $Booking->getBooking( $post_id );

		if ( ! $booking || ! is_array( $booking ) ) {
			$this->logger->error( 'Could not load booking from CRBS', array( 
				'post_id' => $post_id,
				'booking_data' => $booking,
			) );
			return;
		}

		$this->logger->debug( 'Booking loaded from CRBS', array( 
			'post_id' => $post_id,
			'has_meta' => isset( $booking['meta'] ),
			'meta_keys' => isset( $booking['meta'] ) ? array_keys( $booking['meta'] ) : array(),
		) );

		// Filter by booking status (optional - can be configured)
		$context = defined( 'PLUGIN_CRBS_CONTEXT' ) ? PLUGIN_CRBS_CONTEXT : 'crbs';
		$status_id = (int) ( $booking['meta'][ $context . '_booking_status_id' ] ?? 0 );
		
		// Default allowed statuses based on CRBS statuses:
		// 1 = Pending (new)
		// 2 = Processing (accepted) ✓
		// 3 = Cancelled (rejected)
		// 4 = Completed (finished) ✓
		// 5 = On hold
		// 6 = Refunded
		// 7 = Failed
		$allowed_statuses = apply_filters( 'qzb_allowed_booking_statuses', array( 2, 4 ) ); // Processing & Completed by default
		$allow_all_statuses = apply_filters( 'qzb_allow_all_booking_statuses', false ); // Set to true to process all statuses

		$this->logger->info( 'Checking booking status', array(
			'post_id' => $post_id,
			'status_id' => $status_id,
			'status_name' => $this->get_status_name( $status_id ),
			'allowed_statuses' => $allowed_statuses,
			'allow_all' => $allow_all_statuses,
			'context' => $context,
			'meta_key' => $context . '_booking_status_id',
		) );

		if ( ! $allow_all_statuses && ! in_array( $status_id, $allowed_statuses, true ) ) {
			$this->logger->warning( 'Booking status not allowed - SKIPPING', array(
				'post_id' => $post_id,
				'status_id' => $status_id,
				'status_name' => $this->get_status_name( $status_id ),
				'allowed_statuses' => $allowed_statuses,
			) );
			return;
		}

		// Process booking
		$this->logger->info( 'Processing CRBS booking', array( 
			'post_id' => $post_id,
			'status_id' => $status_id,
		) );

		$result = $this->booking_service->process_crbs_booking( $post_id, $booking );

		if ( $result['success'] ) {
			// Store payload in multiple formats for compatibility
			update_post_meta( $post_id, '_qzb_sent_to_zoho', '1' );
			update_post_meta( $post_id, '_qzb_sent_at', current_time( 'mysql' ) );
			update_post_meta( $post_id, '_qzb_payload_data', $result['payload'] );
			update_post_meta( $post_id, '_qzb_payload_json', wp_json_encode( $result['payload'], JSON_PRETTY_PRINT ) );
			
			$this->logger->info( 'Booking processed and stored', array( 
				'post_id' => $post_id,
			) );
		} else {
			$this->logger->error( 'Failed to process booking', array( 
				'post_id' => $post_id,
				'error' => $result['error'] ?? 'Unknown error',
			) );
		}
	}

	/**
	 * Get status name by ID
	 *
	 * @param int $status_id Status ID
	 * @return string Status name
	 */
	private function get_status_name( $status_id ) {
		$statuses = array(
			1 => 'Pending (new)',
			2 => 'Processing (accepted)',
			3 => 'Cancelled (rejected)',
			4 => 'Completed (finished)',
			5 => 'On hold',
			6 => 'Refunded',
			7 => 'Failed',
		);
		return $statuses[ $status_id ] ?? 'Unknown (' . $status_id . ')';
	}
}
