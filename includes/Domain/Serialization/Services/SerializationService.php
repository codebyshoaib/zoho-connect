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

		// Get Zoho Books item ID from vehicle ACF field
		$zoho_item_id = '';
		if ( ! empty( $vehicle_id ) ) {
			// Check if ACF is available
			if ( function_exists( 'get_field' ) ) {
				$zoho_item_id = get_field( 'zoho_item_id', $vehicle_id );
				// ACF might return array or string, normalize to string
				if ( is_array( $zoho_item_id ) ) {
					$zoho_item_id = isset( $zoho_item_id['value'] ) ? $zoho_item_id['value'] : '';
				}
				$zoho_item_id = trim( (string) $zoho_item_id );
				
				if ( empty( $zoho_item_id ) ) {
					$this->logger->warning( 'Vehicle has no Zoho Books item ID configured', array(
						'booking_id' => $booking_id,
						'vehicle_id' => $vehicle_id,
						'vehicle_name' => $vehicle_name,
					) );
				} else {
					$this->logger->debug( 'Found Zoho Books item ID from vehicle ACF field', array(
						'booking_id' => $booking_id,
						'vehicle_id' => $vehicle_id,
						'zoho_item_id' => $zoho_item_id,
					) );
				}
			} else {
				$this->logger->warning( 'ACF function get_field not available', array(
					'booking_id' => $booking_id,
					'vehicle_id' => $vehicle_id,
				) );
			}
		}

		// Extract location information
		$pickup_location_name = $meta['pickup_location_name'] ?? '';
		$return_location_name = $meta['return_location_name'] ?? '';

		// Extract financial information - CRBS uses specific field names
		// Currency: currency_id (e.g., "USD")
		$currency = $meta['currency_id'] 
			?? $meta[ $context . '_currency' ] 
			?? $meta['currency'] 
			?? 'USD';
		
		// Price: Get TOTAL booking amount (rate * days), not just per-day rate
		// Priority 1: Look for total amount fields (CRBS may have calculated total already)
		$total_keys = array(
			$context . '_payment_total',
			'payment_total',
			$context . '_total',
			'total',
			$context . '_booking_total',
			'booking_total',
			$context . '_rental_total',
			'rental_total',
			$context . '_invoice_total',
			'invoice_total',
			$context . '_price_initial_value',
			'price_initial_value',
		);
		
		$total = 0;
		$found_key = '';
		$per_day_rate = 0;
		
		// First, try to find total amount fields
		foreach ( $total_keys as $key ) {
			if ( isset( $meta[ $key ] ) && ! empty( $meta[ $key ] ) ) {
				$value = (float) $meta[ $key ];
				// Only use if value is greater than 0.01 (ignore 0, 0.01, etc.)
				if ( $value > 0.01 ) {
					$total = $value;
					$found_key = $key;
					break;
				}
			}
		}
		
		// Priority 2: If no total found, get per-day rate and calculate total from dates
		if ( $total == 0 ) {
			// Get per-day rate
			$per_day_keys = array(
				'price_rental_day_value',
				$context . '_price_rental_day_value',
				$context . '_price',
				'price',
			);
			
			foreach ( $per_day_keys as $key ) {
				if ( isset( $meta[ $key ] ) && ! empty( $meta[ $key ] ) ) {
					$value = (float) $meta[ $key ];
					if ( $value > 0.01 ) {
						$per_day_rate = $value;
						break;
					}
				}
			}
			
			// Calculate days from pickup and return dates
			if ( $per_day_rate > 0 && ! empty( $pickup ) && ! empty( $return ) ) {
				$days = $this->calculate_rental_days( $pickup, $return );
				if ( $days > 0 ) {
					$total = $per_day_rate * $days;
					$found_key = 'calculated_from_dates';
					$this->logger->debug( 'Calculated total from per-day rate and dates', array(
						'booking_id' => $booking_id,
						'per_day_rate' => $per_day_rate,
						'days' => $days,
						'total' => $total,
					) );
				}
			}
		}
		
		// Priority 3: Fallback to other price fields if still not found
		if ( $total == 0 ) {
			$fallback_keys = array(
				$context . '_amount',
				'amount',
				$context . '_cost',
				'cost',
				$context . '_sum',
				'sum',
			);
			
			foreach ( $fallback_keys as $key ) {
				if ( isset( $meta[ $key ] ) && ! empty( $meta[ $key ] ) ) {
					$value = (float) $meta[ $key ];
					if ( $value > 0.01 ) {
						$total = $value;
						$found_key = $key;
						break;
					}
				}
			}
		}
		
		// If not found in known fields, scan ALL meta fields for numeric values that look like prices
		if ( $total == 0 ) {
			$price_keywords = array( 'price', 'total', 'amount', 'cost', 'sum', 'payment', 'fee', 'charge', 'rental', 'value' );
			
			// Search in meta first
			foreach ( $meta as $key => $value ) {
				// Handle string values that might be numeric
				if ( is_string( $value ) ) {
					$value = trim( $value );
					if ( empty( $value ) || ! is_numeric( $value ) ) {
						continue;
					}
				}
				
				// Skip non-numeric or empty values
				if ( empty( $value ) || ! is_numeric( $value ) ) {
					continue;
				}
				
				$value_float = (float) $value;
				
				// Look for values between 1 and 1,000,000 (reasonable price range)
				// AND check if key contains price-related keywords
				$key_lower = strtolower( $key );
				$has_price_keyword = false;
				
				foreach ( $price_keywords as $keyword ) {
					if ( strpos( $key_lower, $keyword ) !== false ) {
						$has_price_keyword = true;
						break;
					}
				}
				
				// If it has price keyword AND is in reasonable range, use it
				if ( $has_price_keyword && $value_float >= 1 && $value_float <= 1000000 ) {
					$total = $value_float;
					$found_key = $key;
					break;
				}
			}
			
			// If still not found, try ANY numeric value in reasonable range (last resort)
			if ( $total == 0 ) {
				foreach ( $meta as $key => $value ) {
					// Handle string values that might be numeric
					if ( is_string( $value ) ) {
						$value = trim( $value );
						if ( empty( $value ) || ! is_numeric( $value ) ) {
							continue;
						}
					}
					
					if ( empty( $value ) || ! is_numeric( $value ) ) {
						continue;
					}
					
					$value_float = (float) $value;
					
					// Use any value between 100 and 1,000,000 (likely a price, not an ID)
					if ( $value_float >= 100 && $value_float <= 1000000 ) {
						$total = $value_float;
						$found_key = $key;
						break;
					}
				}
			}
			
			// Last resort: check booking array root level (not just meta)
			if ( $total == 0 ) {
				foreach ( $booking as $key => $value ) {
					// Skip meta (already checked) and non-numeric values
					if ( $key === 'meta' || empty( $value ) || ! is_numeric( $value ) ) {
						continue;
					}
					
					$value_float = (float) $value;
					$key_lower = strtolower( $key );
					
					// Check for price-related keywords in root level
					foreach ( $price_keywords as $keyword ) {
						if ( strpos( $key_lower, $keyword ) !== false && $value_float >= 1 && $value_float <= 1000000 ) {
							$total = $value_float;
							$found_key = $key . ' (root)';
							break 2;
						}
					}
				}
			}
		}
		
		// Get per-day rate for debug info (if not already set)
		if ( $per_day_rate == 0 ) {
			$per_day_keys = array(
				'price_rental_day_value',
				$context . '_price_rental_day_value',
			);
			foreach ( $per_day_keys as $key ) {
				if ( isset( $meta[ $key ] ) && ! empty( $meta[ $key ] ) ) {
					$value = (float) $meta[ $key ];
					if ( $value > 0.01 ) {
						$per_day_rate = $value;
						break;
					}
				}
			}
		}
		
		// Calculate days for debug info
		$rental_days = 0;
		if ( ! empty( $pickup ) && ! empty( $return ) ) {
			$rental_days = $this->calculate_rental_days( $pickup, $return );
		}
		
		if ( $total > 0 ) {
			$this->logger->debug( 'Found total booking amount', array(
				'booking_id' => $booking_id,
				'key' => $found_key,
				'total' => $total,
				'per_day_rate' => $per_day_rate,
				'rental_days' => $rental_days,
			) );
		} else {
			$this->logger->warning( 'No valid total amount found in booking meta', array(
				'booking_id' => $booking_id,
				'per_day_rate' => $per_day_rate,
				'rental_days' => $rental_days,
				'available_keys' => array_keys( $meta ),
			) );
		}
		
		// Extract status - try multiple possible meta keys
		$status_id = (int) ( $meta[ $context . '_booking_status_id' ] 
			?? $meta['booking_status_id'] 
			?? $meta[ $context . '_status_id' ] 
			?? $meta['status_id'] 
			?? 0 );

		// Handle zero rate case - Zoho Books rejects 0 rates
		// If rate is 0, set a minimum value to avoid API errors
		if ( $total == 0 ) {
			$this->logger->warning( 'Booking has zero rate, setting minimum value to avoid Zoho Books API error', array(
				'booking_id' => $booking_id,
				'original_rate' => 0,
			) );
			$total = 0.01; // Set minimum value to avoid "Invalid value passed for Rate" error
		}

		// Build line item
		// Note: rate and qty must be numbers (not strings) for Zoho Books API
		// line_items is an array to support multiple items (e.g., booking + addons)
		// In Zoho Flow, access first item with: line_items[0].rate, line_items[0].qty, line_items[0].name
		$line_item = array(
			'name' => 'Car Rental Booking #' . $booking_id,
			'qty'  => $rental_days > 0 ? (int) $rental_days : 1,
			'rate' => $rental_days > 0 ? (float) $per_day_rate : (float) $total,
		);

		// Add Zoho Books item ID if available (for template mapping)
		if ( ! empty( $zoho_item_id ) ) {
			$line_item['item_id'] = $zoho_item_id;
		}

		// Build line items array - supports multiple items
		$line_items = array( $line_item );

		// Allow filtering to add additional line items (e.g., insurance addons)
		$line_items = apply_filters( 'qzb_line_items', $line_items, $booking_id, $booking, $meta );

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
				'line_items' => $line_items, // Array of line items - properly formatted as JSON array
				'notes' => 'CRBS Booking #' . $booking_id,
				// Debug info: show which field was used for total and calculation details
				'_debug' => array(
					'total_field' => $found_key ?: 'not_found',
					'total_amount' => $total,
					'per_day_rate' => $per_day_rate,
					'rental_days' => $rental_days,
					'pickup_date' => $pickup,
					'return_date' => $return,
				),
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
	 * Calculate rental days from pickup and return dates
	 *
	 * @param string $pickup_date Pickup date/datetime
	 * @param string $return_date Return date/datetime
	 * @return float Number of rental days (can be fractional for partial days)
	 */
	private function calculate_rental_days( $pickup_date, $return_date ) {
		if ( empty( $pickup_date ) || empty( $return_date ) ) {
			return 0;
		}
		
		// Try to parse dates - CRBS uses various formats
		$pickup_timestamp = $this->parse_date( $pickup_date );
		$return_timestamp = $this->parse_date( $return_date );
		
		if ( ! $pickup_timestamp || ! $return_timestamp ) {
			return 0;
		}
		
		// Calculate difference in seconds
		$diff_seconds = $return_timestamp - $pickup_timestamp;
		
		// Convert to days (including fractional days)
		$days = $diff_seconds / ( 24 * 60 * 60 );
		
		// Always ceil to full days (partial days count as a full day)
		return (int) ceil( $days );
	}
	
	/**
	 * Parse date string to timestamp
	 * Handles various CRBS date formats
	 *
	 * @param string $date_string Date string in various formats
	 * @return int|false Unix timestamp or false on failure
	 */
	private function parse_date( $date_string ) {
		if ( empty( $date_string ) ) {
			return false;
		}
		
		// Try common CRBS date formats
		// Format 1: "04-03-2026 01:00" (DD-MM-YYYY HH:MM)
		// Format 2: "2026-03-04 01:00" (YYYY-MM-DD HH:MM)
		// Format 3: "04-03-2026" (DD-MM-YYYY)
		// Format 4: "2026-03-04" (YYYY-MM-DD)
		
		// Try strtotime first (handles most formats)
		$timestamp = strtotime( $date_string );
		if ( $timestamp !== false ) {
			return $timestamp;
		}
		
		// Try parsing DD-MM-YYYY format manually
		if ( preg_match( '/^(\d{2})-(\d{2})-(\d{4})(?:\s+(\d{2}):(\d{2}))?$/', $date_string, $matches ) ) {
			$day = $matches[1];
			$month = $matches[2];
			$year = $matches[3];
			$hour = isset( $matches[4] ) ? $matches[4] : '00';
			$minute = isset( $matches[5] ) ? $matches[5] : '00';
			
			$date_string = "$year-$month-$day $hour:$minute:00";
			$timestamp = strtotime( $date_string );
			if ( $timestamp !== false ) {
				return $timestamp;
			}
		}
		
		return false;
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
