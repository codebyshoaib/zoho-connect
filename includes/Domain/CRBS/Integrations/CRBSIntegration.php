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

		// Hook into CRBS booking save with lower priority to ensure CRBS has saved all meta
		// Using priority 99 to run after CRBS has finished saving all data
		add_action( "save_post_{$this->cpt_name}", array( $this, 'on_booking_saved' ), 99, 3 );
		
		// Also hook into transition_post_status to catch status changes
		add_action( "transition_post_status", array( $this, 'on_post_status_transition' ), 10, 3 );
		
		// Register scheduled event handler
		add_action( 'qzb_process_booking', array( $this, 'process_scheduled_booking' ), 10, 1 );

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

		// Prevent duplicate sending - but allow processing new bookings
		// Only skip if it was already processed AND this is an update (not a new booking)
		$sent = get_post_meta( $post_id, '_qzb_sent_to_zoho', true );
		if ( $sent === '1' && ! defined( 'QZB_FORCE_RESEND' ) && $update ) {
			// If it's an update and already processed, skip to avoid duplicates
			$this->logger->debug( 'Booking already processed, skipping duplicate', array( 
				'post_id' => $post_id,
				'is_update' => $update,
			) );
			return;
		}
		// For new bookings ($update = false), always process even if meta exists
		
		// Try to load booking immediately to check if data is available
		$Booking = new \CRBSBooking();
		$booking = $Booking->getBooking( $post_id );
		
		if ( $booking && is_array( $booking ) ) {
			$meta = $booking['meta'] ?? array();
			// Check if essential data is available
			$has_essential_data = ! empty( $meta['pickup_datetime'] ) || ! empty( $meta['price_initial_value'] ) || ! empty( $meta['vehicle_id'] );
			
			if ( $has_essential_data ) {
				// Data is ready, process immediately
				$this->logger->info( 'Booking data available immediately, processing now', array( 'post_id' => $post_id ) );
				$this->process_booking_immediately( $post_id, $booking );
				return;
			}
		}
		
		// Data not ready yet, schedule processing with a delay
		$this->logger->info( 'Booking data not ready, scheduling delayed processing', array( 'post_id' => $post_id ) );
		$this->schedule_booking_processing( $post_id );
		return;
	}

	/**
	 * Handle post status transition
	 *
	 * @param string  $new_status New post status
	 * @param string  $old_status Old post status
	 * @param \WP_Post $post Post object
	 */
	public function on_post_status_transition( $new_status, $old_status, $post ) {
		// Only process if transitioning to published and it's our CPT
		if ( $new_status !== 'publish' || $post->post_type !== $this->cpt_name ) {
			return;
		}

		// Schedule processing with delay
		$this->schedule_booking_processing( $post->ID );
	}

	/**
	 * Schedule booking processing with delay
	 *
	 * @param int $post_id Post ID
	 */
	private function schedule_booking_processing( $post_id ) {
		// Use wp_schedule_single_event with a 3 second delay to ensure CRBS has saved all meta
		if ( ! wp_next_scheduled( 'qzb_process_booking', array( $post_id ) ) ) {
			wp_schedule_single_event( time() + 3, 'qzb_process_booking', array( $post_id ) );
			$this->logger->info( 'Scheduled booking processing', array( 'post_id' => $post_id ) );
		}
	}

	/**
	 * Process booking immediately (when data is available)
	 *
	 * @param int   $post_id Post ID
	 * @param array $booking Booking data
	 */
	private function process_booking_immediately( $post_id, array $booking ) {
		// Get booking status
		$context = defined( 'PLUGIN_CRBS_CONTEXT' ) ? PLUGIN_CRBS_CONTEXT : 'crbs';
		$status_id = (int) ( $booking['meta'][ $context . '_booking_status_id' ] ?? $booking['meta']['booking_status_id'] ?? 0 );
		
		// Process ALL booking statuses by default
		$allowed_statuses = apply_filters( 'qzb_allowed_booking_statuses', array() );
		$allow_all_statuses = apply_filters( 'qzb_allow_all_booking_statuses', true );

		// Only filter by status if explicitly configured
		if ( ! $allow_all_statuses && ! empty( $allowed_statuses ) && ! in_array( $status_id, $allowed_statuses, true ) ) {
			$this->logger->debug( 'Booking status not allowed', array(
				'post_id' => $post_id,
				'status_id' => $status_id,
			) );
			return;
		}

		// Process the booking
		$this->logger->info( 'Processing CRBS booking immediately', array( 
			'post_id' => $post_id,
			'status_id' => $status_id,
		) );

		$result = $this->booking_service->process_crbs_booking( $post_id, $booking );

		if ( $result['success'] ) {
			update_post_meta( $post_id, '_qzb_sent_to_zoho', '1' );
			update_post_meta( $post_id, '_qzb_sent_at', current_time( 'mysql' ) );
			update_post_meta( $post_id, '_qzb_payload_data', $result['payload'] );
			update_post_meta( $post_id, '_qzb_payload_json', wp_json_encode( $result['payload'], JSON_PRETTY_PRINT ) );
			
			$this->logger->info( 'Booking processed and stored immediately', array( 
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
	 * Process scheduled booking (called after delay)
	 *
	 * @param int $post_id Post ID
	 */
	public function process_scheduled_booking( $post_id ) {
		$post = get_post( $post_id );
		
		if ( ! $post || $post->post_status !== 'publish' ) {
			$this->logger->debug( 'Scheduled booking not published, skipping', array( 'post_id' => $post_id ) );
			return;
		}

		// Check if already processed (unless forcing resend)
		$sent = get_post_meta( $post_id, '_qzb_sent_to_zoho', true );
		if ( $sent === '1' && ! defined( 'QZB_FORCE_RESEND' ) ) {
			$this->logger->debug( 'Scheduled booking already processed, skipping', array( 'post_id' => $post_id ) );
			return;
		}

		// Load booking via CRBS
		$Booking = new \CRBSBooking();
		$booking = $Booking->getBooking( $post_id );

		if ( ! $booking || ! is_array( $booking ) ) {
			$this->logger->error( 'Could not load booking from CRBS (scheduled)', array( 
				'post_id' => $post_id,
			) );
			return;
		}

		$this->logger->debug( 'Booking loaded from CRBS (scheduled)', array( 
			'post_id' => $post_id,
			'has_meta' => isset( $booking['meta'] ),
			'meta_keys' => isset( $booking['meta'] ) ? array_keys( $booking['meta'] ) : array(),
		) );

		// Verify we have essential data before processing
		$meta = $booking['meta'] ?? array();
		$has_essential_data = ! empty( $meta['pickup_datetime'] ) || ! empty( $meta['price_initial_value'] ) || ! empty( $meta['vehicle_id'] );
		
		if ( ! $has_essential_data ) {
			$this->logger->warning( 'Booking meta not fully loaded yet, retrying...', array( 
				'post_id' => $post_id,
				'meta_keys' => array_keys( $meta ),
			) );
			// Retry after another 2 seconds (max 2 retries)
			$retry_count = get_post_meta( $post_id, '_qzb_retry_count', true );
			$retry_count = (int) $retry_count;
			if ( $retry_count < 2 ) {
				update_post_meta( $post_id, '_qzb_retry_count', $retry_count + 1 );
				if ( ! wp_next_scheduled( 'qzb_process_booking', array( $post_id ) ) ) {
					wp_schedule_single_event( time() + 2, 'qzb_process_booking', array( $post_id ) );
				}
			} else {
				$this->logger->error( 'Max retries reached, booking meta still not available', array( 'post_id' => $post_id ) );
			}
			return;
		}

		// Clear retry count on success
		delete_post_meta( $post_id, '_qzb_retry_count' );

		// Get booking status for logging
		$context = defined( 'PLUGIN_CRBS_CONTEXT' ) ? PLUGIN_CRBS_CONTEXT : 'crbs';
		$status_id = (int) ( $booking['meta'][ $context . '_booking_status_id' ] ?? $booking['meta']['booking_status_id'] ?? 0 );
		
		// Process ALL booking statuses by default (can be filtered if needed)
		// 1 = Pending (new) ✓
		// 2 = Processing (accepted) ✓
		// 3 = Cancelled (rejected) ✓
		// 4 = Completed (finished) ✓
		// 5 = On hold ✓
		// 6 = Refunded ✓
		// 7 = Failed ✓
		$allowed_statuses = apply_filters( 'qzb_allowed_booking_statuses', array() ); // Empty array = all statuses allowed
		$allow_all_statuses = apply_filters( 'qzb_allow_all_booking_statuses', true ); // Default: process all statuses

		$this->logger->info( 'Processing booking', array(
			'post_id' => $post_id,
			'status_id' => $status_id,
			'status_name' => $this->get_status_name( $status_id ),
			'allowed_statuses' => $allowed_statuses,
			'allow_all' => $allow_all_statuses,
		) );

		// Only filter by status if explicitly configured via filter
		if ( ! $allow_all_statuses && ! empty( $allowed_statuses ) && ! in_array( $status_id, $allowed_statuses, true ) ) {
			$this->logger->debug( 'Booking status not in allowed list - SKIPPING', array(
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
