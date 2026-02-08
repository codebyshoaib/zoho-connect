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
	 * Pending bookings to process
	 *
	 * @var array
	 */
	private $pending_bookings = array();

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

		// Hook into CRBS booking save with very high priority to run AFTER CRBS has saved all meta
		// Using priority 999 to ensure CRBS has completely finished saving all data
		add_action( "save_post_{$this->cpt_name}", array( $this, 'on_booking_saved' ), 999, 3 );
		
		// Also hook into transition_post_status to catch status changes (including new->publish)
		// Use high priority to run after CRBS
		add_action( "transition_post_status", array( $this, 'on_post_status_transition' ), 999, 3 );
		
		// Hook into wp_insert_post_data to catch bookings as early as possible
		add_filter( 'wp_insert_post_data', array( $this, 'on_wp_insert_post_data' ), 999, 2 );
		
		// Use shutdown hook as final backup (runs after all other hooks)
		// This gives CRBS maximum time to save all data
		add_action( 'shutdown', array( $this, 'process_pending_bookings' ), 999 );

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

		// Check if already processed (skip if already done)
		$sent = get_post_meta( $post_id, '_qzb_sent_to_zoho', true );
		if ( $sent === '1' && ! defined( 'QZB_FORCE_RESEND' ) ) {
			$this->logger->debug( 'Booking already processed, skipping', array( 'post_id' => $post_id ) );
			return;
		}

		// ALWAYS try to process - don't check if it's "new" or not
		// Just process any booking that hasn't been processed yet
		// Process regardless of post status (draft, publish, etc.)
		$this->logger->info( 'Booking save detected, attempting immediate processing', array( 
			'post_id' => $post_id,
			'post_status' => $post->post_status ?? 'unknown',
			'is_update' => $update,
		) );
		
		// Add to pending list for shutdown processing (most reliable - runs after CRBS saves everything)
		$this->pending_bookings[] = $post_id;
		
		// Try to process immediately with retry loop (waits for data if needed)
		$this->process_booking_directly( $post_id );
	}

	/**
	 * Handle post status transition
	 *
	 * @param string  $new_status New post status
	 * @param string  $old_status Old post status
	 * @param \WP_Post $post Post object
	 */
	public function on_post_status_transition( $new_status, $old_status, $post ) {
		// Only process if it's our CPT
		if ( $post->post_type !== $this->cpt_name ) {
			return;
		}

		// Process when transitioning to published (including new->publish for new bookings)
		if ( $new_status === 'publish' ) {
			// Check if already processed
			$sent = get_post_meta( $post->ID, '_qzb_sent_to_zoho', true );
			if ( $sent === '1' && ! defined( 'QZB_FORCE_RESEND' ) ) {
				$this->logger->debug( 'Booking already processed on status transition', array( 'post_id' => $post->ID ) );
				return;
			}

			$this->logger->info( 'Booking status transitioned to publish, processing', array( 
				'post_id' => $post->ID,
				'old_status' => $old_status,
				'new_status' => $new_status,
			) );
			
			// Add to pending for shutdown (most reliable)
			$this->pending_bookings[] = $post->ID;
			
			// Also try immediately
			$this->process_booking_directly( $post->ID );
		}
	}

	/**
	 * Handle wp_insert_post_data filter to catch bookings early
	 * This runs before the post is saved, so we can prepare
	 *
	 * @param array $data Post data
	 * @param array $postarr Post array
	 * @return array Unchanged post data
	 */
	public function on_wp_insert_post_data( $data, $postarr ) {
		// Only process if it's our CPT
		if ( isset( $data['post_type'] ) && $data['post_type'] === $this->cpt_name ) {
			// This is just to log that we detected a booking being created
			// Actual processing happens in save_post hook
			$this->logger->debug( 'CRBS booking data detected in wp_insert_post_data', array(
				'post_type' => $data['post_type'] ?? 'unknown',
				'post_status' => $data['post_status'] ?? 'unknown',
			) );
		}
		
		return $data;
	}

	/**
	 * Process booking directly and immediately (synchronous, no cron, no delays)
	 * Retries in the same request until data is available or max retries reached
	 *
	 * @param int $post_id Post ID
	 * @return bool True if processed, false if data not ready after retries
	 */
	private function process_booking_directly( $post_id ) {
		// Check if already processed
		$sent = get_post_meta( $post_id, '_qzb_sent_to_zoho', true );
		if ( $sent === '1' && ! defined( 'QZB_FORCE_RESEND' ) ) {
			$this->logger->debug( 'Booking already processed, skipping', array( 'post_id' => $post_id ) );
			return true;
		}

		// Check if CRBS is available
		if ( ! class_exists( 'CRBSBooking' ) ) {
			$this->logger->warning( 'CRBS not available', array( 'post_id' => $post_id ) );
			return false;
		}

		$Booking = new \CRBSBooking();
		
		// Retry loop: try multiple times in the same request with small delays
		// This ensures we wait for CRBS to finish saving all meta data
		// Increased retries and delay to give CRBS more time
		$max_retries = 20; // Increased from 10 to 20
		$retry_delay_ms = 200; // Increased from 100ms to 200ms between retries
		
		for ( $attempt = 1; $attempt <= $max_retries; $attempt++ ) {
			$booking = $Booking->getBooking( $post_id );
			
			// Check if booking data is available - be more lenient with the check
			// Just check if we got an array back, even if meta is empty initially
			if ( $booking && is_array( $booking ) ) {
				// Ensure meta exists (even if empty)
				if ( ! isset( $booking['meta'] ) ) {
					$booking['meta'] = array();
				}
				
				// Process it - don't require meta to be non-empty
				// The serialization service will handle missing fields gracefully
				$this->logger->info( 'Booking data loaded, processing immediately', array( 
					'post_id' => $post_id,
					'attempt' => $attempt,
					'has_meta' => ! empty( $booking['meta'] ),
				) );
				$this->process_booking_immediately( $post_id, $booking );
				return true;
			}
			
			// Data not ready yet, wait a bit and retry
			if ( $attempt < $max_retries ) {
				usleep( $retry_delay_ms * 1000 ); // Convert ms to microseconds
				$this->logger->debug( 'Booking data not available yet, retrying', array( 
					'post_id' => $post_id,
					'attempt' => $attempt,
					'max_retries' => $max_retries,
				) );
			}
		}
		
		// After all retries, data still not available
		$this->logger->warning( 'Booking data not available after all retries', array( 
			'post_id' => $post_id,
			'max_retries' => $max_retries,
		) );
		return false;
	}

	/**
	 * Process booking immediately (when data is available)
	 *
	 * @param int   $post_id Post ID
	 * @param array $booking Booking data
	 */
	private function process_booking_immediately( $post_id, array $booking ) {
		// Get booking status - try multiple possible field names
		$context = defined( 'PLUGIN_CRBS_CONTEXT' ) ? PLUGIN_CRBS_CONTEXT : 'crbs';
		$status_id = (int) ( $booking['meta'][ $context . '_booking_status_id' ] 
			?? $booking['meta']['booking_status_id'] 
			?? $booking['meta'][ $context . '_status_id' ]
			?? $booking['meta']['status_id']
			?? 0 );
		
		// Process ALL booking statuses by default (including status 0/unknown)
		$allowed_statuses = apply_filters( 'qzb_allowed_booking_statuses', array() );
		$allow_all_statuses = apply_filters( 'qzb_allow_all_booking_statuses', true );

		// Only filter by status if explicitly configured
		// If allow_all is true OR allowed_statuses is empty, process everything (including status 0)
		if ( ! $allow_all_statuses && ! empty( $allowed_statuses ) && ! in_array( $status_id, $allowed_statuses, true ) ) {
			$this->logger->debug( 'Booking status not allowed', array(
				'post_id' => $post_id,
				'status_id' => $status_id,
				'status_name' => $this->get_status_name( $status_id ),
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
	 * Process pending bookings on shutdown (final attempt after all WordPress hooks)
	 * This runs as the very last thing, giving CRBS maximum time to save all data
	 */
	public function process_pending_bookings() {
		if ( empty( $this->pending_bookings ) ) {
			return;
		}

		// Remove duplicates
		$this->pending_bookings = array_unique( $this->pending_bookings );

		foreach ( $this->pending_bookings as $post_id ) {
			// Skip if already processed
			$sent = get_post_meta( $post_id, '_qzb_sent_to_zoho', true );
			if ( $sent === '1' && ! defined( 'QZB_FORCE_RESEND' ) ) {
				$this->logger->debug( 'Booking already processed, skipping on shutdown', array( 'post_id' => $post_id ) );
				continue;
			}

			// Final attempt - process with retry loop
			$this->logger->info( 'Final attempt: Processing booking on shutdown', array( 'post_id' => $post_id ) );
			$this->process_booking_directly( $post_id );
		}

		// Clear pending list
		$this->pending_bookings = array();
	}

	/**
	 * Get status name by ID
	 *
	 * @param int $status_id Status ID
	 * @return string Status name
	 */
	private function get_status_name( $status_id ) {
		$statuses = array(
			0 => 'Not set / Unknown',
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
