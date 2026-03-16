<?php
/**
 * Admin Payloads View Page Template
 *
 * @package ZohoConnectSerializer
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Handle manual processing
if ( isset( $_GET['process_booking'] ) && isset( $_GET['booking_id'] ) && check_admin_referer( 'process_booking_' . $_GET['booking_id'] ) ) {
	$process_id = intval( $_GET['booking_id'] );
	$result = \ZohoConnectSerializer\Infrastructure\Admin\ManualTrigger::process_booking( $process_id );
	
	if ( $result['success'] ) {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Booking processed successfully!', 'crbs-zoho-flow-bridge' ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Error: ', 'crbs-zoho-flow-bridge' ) . esc_html( $result['error'] ?? 'Unknown error' ) . '</p></div>';
	}
}

// Get booking ID from query string
$booking_id = isset( $_GET['booking_id'] ) ? intval( $_GET['booking_id'] ) : 0;

// If specific booking ID provided, show that payload
if ( $booking_id > 0 ) {
	$payload_json = get_post_meta( $booking_id, '_qzb_payload_json', true );
	$sent_at = get_post_meta( $booking_id, '_qzb_sent_at', true );
	$booking_title = get_the_title( $booking_id );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'View Payload', 'crbs-zoho-flow-bridge' ); ?></h1>
		
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=crbs-zoho-flow-bridge-payloads' ) ); ?>" class="button">
				<?php esc_html_e( '← Back to List', 'crbs-zoho-flow-bridge' ); ?>
			</a>
		</p>

		<div class="card">
			<h2><?php echo esc_html( sprintf( __( 'Booking: %s', 'crbs-zoho-flow-bridge' ), $booking_title ) ); ?></h2>
			<p><strong><?php esc_html_e( 'Booking ID:', 'crbs-zoho-flow-bridge' ); ?></strong> <?php echo esc_html( $booking_id ); ?></p>
			<?php if ( $sent_at ) : ?>
				<p><strong><?php esc_html_e( 'Sent At:', 'crbs-zoho-flow-bridge' ); ?></strong> <?php echo esc_html( $sent_at ); ?></p>
			<?php endif; ?>
		</div>

		<div class="card">
			<h2><?php esc_html_e( 'Payload Data', 'crbs-zoho-flow-bridge' ); ?></h2>
			<?php if ( $payload_json ) : ?>
				<pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto;"><code><?php echo esc_html( $payload_json ); ?></code></pre>
				<p>
					<button onclick="copyToClipboard()" class="button button-primary">
						<?php esc_html_e( 'Copy JSON', 'crbs-zoho-flow-bridge' ); ?>
					</button>
				</p>
				<script>
				function copyToClipboard() {
					const text = <?php echo wp_json_encode( $payload_json ); ?>;
					navigator.clipboard.writeText(text).then(function() {
						alert('<?php esc_html_e( 'JSON copied to clipboard!', 'crbs-zoho-flow-bridge' ); ?>');
					});
				}
				</script>
			<?php else : ?>
				<p><?php esc_html_e( 'No payload data found for this booking.', 'crbs-zoho-flow-bridge' ); ?></p>
			<?php endif; ?>
		</div>

		<?php
		// Debug: Show raw CRBS booking data
		if ( class_exists( 'CRBSBooking' ) ) {
			$Booking = new \CRBSBooking();
			$raw_booking = $Booking->getBooking( $booking_id );
			if ( $raw_booking && is_array( $raw_booking ) ) {
				$raw_meta = $raw_booking['meta'] ?? array();
				
				// Find all numeric/price-like fields
				$price_candidates = array();
				$context = defined( 'PLUGIN_CRBS_CONTEXT' ) ? PLUGIN_CRBS_CONTEXT : 'crbs';
				$price_keywords = array( 'price', 'total', 'amount', 'cost', 'sum', 'payment', 'fee', 'charge', 'rental' );
				
				foreach ( $raw_meta as $key => $value ) {
					$key_lower = strtolower( $key );
					// Check if key contains price-related keywords or is numeric
					foreach ( $price_keywords as $keyword ) {
						if ( strpos( $key_lower, $keyword ) !== false && is_numeric( $value ) && (float) $value > 0 ) {
							$price_candidates[ $key ] = $value;
							break;
						}
					}
					// Also check if it's a numeric value that might be a price
					if ( is_numeric( $value ) && (float) $value > 0 && (float) $value < 100000 ) {
						if ( ! isset( $price_candidates[ $key ] ) ) {
							$price_candidates[ $key ] = $value;
						}
					}
				}
				?>
				<div class="card" style="margin-top: 20px; max-width: 100% !important;">
					<h2><?php esc_html_e( 'Debug: Price Field Detection', 'crbs-zoho-flow-bridge' ); ?></h2>
					<?php if ( ! empty( $price_candidates ) ) : ?>
						<p><strong><?php esc_html_e( 'Possible price fields found:', 'crbs-zoho-flow-bridge' ); ?></strong></p>
						<table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Meta Key', 'crbs-zoho-flow-bridge' ); ?></th>
									<th><?php esc_html_e( 'Value', 'crbs-zoho-flow-bridge' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $price_candidates as $key => $value ) : ?>
									<tr>
										<td><code><?php echo esc_html( $key ); ?></code></td>
										<td><strong><?php echo esc_html( $value ); ?></strong></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<p><em><?php esc_html_e( 'If you see the correct price above, let me know the meta key name and I\'ll update the code.', 'crbs-zoho-flow-bridge' ); ?></em></p>
					<?php else : ?>
						<p><?php esc_html_e( 'No obvious price fields found. Check the full meta data below.', 'crbs-zoho-flow-bridge' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="card" style="margin-top: 20px;">
					<h2><?php esc_html_e( 'Debug: Raw CRBS Booking Meta', 'crbs-zoho-flow-bridge' ); ?></h2>
					<p><em><?php esc_html_e( 'This shows all available meta keys from CRBS. Use this to identify the correct field names.', 'crbs-zoho-flow-bridge' ); ?></em></p>
					<details>
						<summary style="cursor: pointer; padding: 10px; background: #f0f0f0; border: 1px solid #ddd;">
							<strong><?php esc_html_e( 'Click to view all meta keys', 'crbs-zoho-flow-bridge' ); ?></strong>
						</summary>
						<pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; margin-top: 10px; max-height: 400px; overflow-y: auto;"><code><?php echo esc_html( wp_json_encode( $raw_meta, JSON_PRETTY_PRINT ) ); ?></code></pre>
					</details>
				</div>
				<?php
			}
		}
		?>
	</div>
	<?php
	return;
}

// List all bookings with payloads
$args = array(
	'post_type'      => class_exists( 'CRBSBooking' ) ? ( new \CRBSBooking() )->getCPTName() : 'crbs_booking',
	'posts_per_page' => 50,
	'meta_query'     => array(
		array(
			'key'     => '_qzb_sent_to_zoho',
			'value'   => '1',
			'compare' => '=',
		),
	),
	'orderby'         => 'date',
	'order'           => 'DESC',
);

$bookings = new \WP_Query( $args );
?>

<div class="wrap" style="max-width: 100%;">
	<h1><?php esc_html_e( 'Your Recent Bookings', 'crbs-zoho-flow-bridge' ); ?></h1>

	<?php
	// Get recent bookings
	$recent_args = array(
		'post_type'      => class_exists( 'CRBSBooking' ) ? ( new \CRBSBooking() )->getCPTName() : 'crbs_booking',
		'posts_per_page' => 20,
		'orderby'         => 'date',
		'order'           => 'DESC',
		'post_status'     => 'publish',
	);
	$recent_bookings = new \WP_Query( $recent_args );
	
	if ( $recent_bookings->have_posts() ) :
		$status_names = array(
			0 => 'Not set / Unknown',
			1 => 'Pending (new)',
			2 => 'Processing (accepted)',
			3 => 'Cancelled (rejected)',
			4 => 'Completed (finished)',
			5 => 'On hold',
			6 => 'Refunded',
			7 => 'Failed',
		);
		$context = defined( 'PLUGIN_CRBS_CONTEXT' ) ? PLUGIN_CRBS_CONTEXT : 'crbs';
		?>
		<div class="card" style="margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 100% !important;">
			<table class="wp-list-table widefat fixed striped" style="margin: 0;">
				<thead>
					<tr>
						<th style="padding: 15px;"><?php esc_html_e( 'Booking ID', 'crbs-zoho-flow-bridge' ); ?></th>
						<th style="padding: 15px;"><?php esc_html_e( 'Title', 'crbs-zoho-flow-bridge' ); ?></th>
						<th style="padding: 15px;"><?php esc_html_e( 'Status', 'crbs-zoho-flow-bridge' ); ?></th>
						<th style="padding: 15px;"><?php esc_html_e( 'Date', 'crbs-zoho-flow-bridge' ); ?></th>
						<th style="padding: 15px; text-align: center;"><?php esc_html_e( 'Action', 'crbs-zoho-flow-bridge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php while ( $recent_bookings->have_posts() ) : $recent_bookings->the_post(); ?>
						<?php
						$booking_id = get_the_ID();
						$processed = get_post_meta( $booking_id, '_qzb_sent_to_zoho', true ) === '1';
						
						// Get booking status
						$Booking = new \CRBSBooking();
						$booking = $Booking->getBooking( $booking_id );
						$status_id = 0;
						$status_name = 'Unknown';
						
						if ( $booking && is_array( $booking ) ) {
							$status_id = (int) ( $booking['meta'][ $context . '_booking_status_id' ] 
								?? $booking['meta']['booking_status_id'] 
								?? 0 );
							$status_name = $status_names[ $status_id ] ?? ( $status_id === 0 ? 'Not set / Unknown' : 'Unknown (' . $status_id . ')' );
						}
						
						$booking_date = get_the_date( 'Y-m-d H:i', $booking_id );
						
						// Get payload - if not stored, generate it on-the-fly
						$payload_json = get_post_meta( $booking_id, '_qzb_payload_json', true );
						$payload_data = null;
						
						if ( empty( $payload_json ) && $booking && is_array( $booking ) ) {
							// Generate payload on-the-fly for unprocessed bookings
							try {
								$plugin = \ZohoConnectSerializer\Core\Plugin::get_instance();
								$container = $plugin->get_container();
								$serialization_service = $container->make( 'serialization_service' );
								
								$payload_data = $serialization_service->serialize_crbs_booking( $booking_id, $booking );
								$payload_json = wp_json_encode( $payload_data, JSON_PRETTY_PRINT );
							} catch ( \Exception $e ) {
								// If generation fails, payload_json will remain empty
								$payload_json = '';
							}
						} else if ( ! empty( $payload_json ) ) {
							// If stored as JSON string, decode it for consistency
							$payload_data = json_decode( $payload_json, true );
						}
						?>
						<tr>
							<td style="padding: 15px; font-weight: 600;"><?php echo esc_html( $booking_id ); ?></td>
							<td style="padding: 15px;"><strong><?php echo esc_html( get_the_title() ); ?></strong></td>
							<td style="padding: 15px;">
								<span style="display: inline-block; padding: 4px 12px; border-radius: 12px; background: #f0f0f0; font-size: 13px;">
									<?php echo esc_html( $status_name ); ?>
								</span>
							</td>
							<td style="padding: 15px; color: #666;"><?php echo esc_html( $booking_date ); ?></td>
							<td style="padding: 15px; text-align: center;">
								<div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
									<?php
									$process_url = wp_nonce_url(
										admin_url( 'admin.php?page=crbs-zoho-flow-bridge-payloads&process_booking=1&booking_id=' . $booking_id ),
										'process_booking_' . $booking_id
									);
									?>
									<a href="<?php echo esc_url( $process_url ); ?>" class="button button-primary" style="padding: 8px 20px; font-weight: 600; border-radius: 4px; text-decoration: none;">
										<?php esc_html_e( 'Send Invoice', 'crbs-zoho-flow-bridge' ); ?>
									</a>
									<button 
										type="button" 
										class="button view-payload-btn" 
										data-booking-id="<?php echo esc_attr( $booking_id ); ?>"
										data-payload="<?php echo ! empty( $payload_json ) ? esc_attr( base64_encode( $payload_json ) ) : ''; ?>"
										style="padding: 8px 12px; border-radius: 4px; cursor: pointer; background: #2271b1; color: #fff; border: 1px solid #2271b1;"
										title="<?php esc_attr_e( 'View Payload', 'crbs-zoho-flow-bridge' ); ?>"
									>
										<span class="dashicons dashicons-visibility" style="font-size: 16px; line-height: 1.2;"></span>
									</button>
								</div>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
			<?php wp_reset_postdata(); ?>
		</div>
	<?php else : ?>
		<div class="notice notice-info">
			<p><?php esc_html_e( 'No recent bookings found.', 'crbs-zoho-flow-bridge' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! $bookings->have_posts() ) : ?>
		<div class="notice notice-info">
			<p><?php esc_html_e( 'No bookings with payloads found. Create or update a booking in CRBS to see payloads here.', 'crbs-zoho-flow-bridge' ); ?></p>
			<p><strong><?php esc_html_e( 'Note:', 'crbs-zoho-flow-bridge' ); ?></strong> <?php esc_html_e( 'By default, only bookings with status "Processing (accepted)" or "Completed (finished)" are processed. Check the debug table above to see your booking status.', 'crbs-zoho-flow-bridge' ); ?></p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Booking ID', 'crbs-zoho-flow-bridge' ); ?></th>
					<th><?php esc_html_e( 'Title', 'crbs-zoho-flow-bridge' ); ?></th>
					<th><?php esc_html_e( 'Processed At', 'crbs-zoho-flow-bridge' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'crbs-zoho-flow-bridge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php while ( $bookings->have_posts() ) : $bookings->the_post(); ?>
					<?php
					$post_id = get_the_ID();
					$sent_at = get_post_meta( $post_id, '_qzb_sent_at', true );
					?>
					<tr>
						<td><?php echo esc_html( $post_id ); ?></td>
						<td><strong><?php echo esc_html( get_the_title() ); ?></strong></td>
						<td><?php echo esc_html( $sent_at ? $sent_at : __( 'N/A', 'crbs-zoho-flow-bridge' ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=crbs-zoho-flow-bridge-payloads&booking_id=' . $post_id ) ); ?>" class="button button-small">
								<?php esc_html_e( 'View Payload', 'crbs-zoho-flow-bridge' ); ?>
							</a>
						</td>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>
		<?php wp_reset_postdata(); ?>
	<?php endif; ?>
</div>

<!-- Modal for viewing payload data -->
<div id="payload-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
	<div style="background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; width: 90%; max-width: 900px; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-height: 85vh; display: flex; flex-direction: column;">
		<div style="padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; background: #f9f9f9; border-radius: 4px 4px 0 0;">
			<h2 style="margin: 0; font-size: 20px; color: #23282d;">
				<?php esc_html_e( 'Booking Payload Data', 'crbs-zoho-flow-bridge' ); ?>
				<span id="modal-booking-id" style="color: #666; font-weight: normal; font-size: 16px;"></span>
			</h2>
			<button type="button" id="close-modal" style="background: transparent; border: none; font-size: 28px; font-weight: bold; color: #aaa; cursor: pointer; padding: 0; width: 30px; height: 30px; line-height: 1; border-radius: 3px; transition: all 0.2s;" onmouseover="this.style.color='#000'; this.style.background='#f0f0f0';" onmouseout="this.style.color='#aaa'; this.style.background='transparent';">
				&times;
			</button>
		</div>
		<div style="padding: 20px; overflow-y: auto; flex: 1;">
			<div id="modal-content">
				<p style="color: #666;"><?php esc_html_e( 'Loading...', 'crbs-zoho-flow-bridge' ); ?></p>
			</div>
		</div>
		<div style="padding: 15px 20px; border-top: 1px solid #ddd; background: #f9f9f9; border-radius: 0 0 4px 4px; display: flex; justify-content: flex-end; gap: 10px;">
			<button type="button" id="copy-payload-btn" class="button button-secondary" style="display: none;">
				<?php esc_html_e( 'Copy JSON', 'crbs-zoho-flow-bridge' ); ?>
			</button>
			<button type="button" id="close-modal-btn" class="button button-primary">
				<?php esc_html_e( 'Close', 'crbs-zoho-flow-bridge' ); ?>
			</button>
		</div>
	</div>
</div>

<style>
	.view-payload-btn:hover {
		background: #135e96 !important;
		border-color: #135e96 !important;
	}
	.view-payload-btn:active {
		background: #0a4b78 !important;
		border-color: #0a4b78 !important;
	}
	#payload-modal pre {
		background: #f5f5f5;
		padding: 15px;
		border: 1px solid #ddd;
		border-radius: 4px;
		overflow-x: auto;
		margin: 0;
		font-size: 13px;
		line-height: 1.5;
	}
	#payload-modal code {
		font-family: 'Courier New', Courier, monospace;
	}
</style>

<script>
(function() {
	const modal = document.getElementById('payload-modal');
	const modalContent = document.getElementById('modal-content');
	const modalBookingId = document.getElementById('modal-booking-id');
	const closeModal = document.getElementById('close-modal');
	const closeModalBtn = document.getElementById('close-modal-btn');
	const copyPayloadBtn = document.getElementById('copy-payload-btn');
	let currentPayload = '';

	// Decode base64 string
	function decodeBase64(str) {
		try {
			return decodeURIComponent(escape(atob(str)));
		} catch (e) {
			return str;
		}
	}

	// Open modal when eye button is clicked
	document.addEventListener('click', function(e) {
		if (e.target.closest('.view-payload-btn')) {
			const btn = e.target.closest('.view-payload-btn');
			const bookingId = btn.getAttribute('data-booking-id');
			const payloadEncoded = btn.getAttribute('data-payload');
			
			// Decode base64 payload
			let payload = '';
			if (payloadEncoded && payloadEncoded !== 'null' && payloadEncoded !== '') {
				payload = decodeBase64(payloadEncoded);
			}
			
			currentPayload = payload;
			modalBookingId.textContent = '# ' + bookingId;
			
			if (payload && payload !== 'null' && payload !== '') {
				try {
					const payloadObj = JSON.parse(payload);
					const formattedJson = JSON.stringify(payloadObj, null, 2);
					modalContent.innerHTML = '<pre><code>' + escapeHtml(formattedJson) + '</code></pre>';
					copyPayloadBtn.style.display = 'inline-block';
				} catch (e) {
					modalContent.innerHTML = '<pre><code>' + escapeHtml(payload) + '</code></pre>';
					copyPayloadBtn.style.display = 'inline-block';
				}
			} else {
				modalContent.innerHTML = '<p style="color: #d63638;"><?php esc_html_e( 'No payload data found for this booking.', 'crbs-zoho-flow-bridge' ); ?></p>';
				copyPayloadBtn.style.display = 'none';
			}
			
			modal.style.display = 'block';
			document.body.style.overflow = 'hidden';
		}
	});

	// Close modal functions
	function closeModalFunc() {
		modal.style.display = 'none';
		document.body.style.overflow = '';
		modalContent.innerHTML = '<p style="color: #666;"><?php esc_html_e( 'Loading...', 'crbs-zoho-flow-bridge' ); ?></p>';
		currentPayload = '';
	}

	closeModal.addEventListener('click', closeModalFunc);
	closeModalBtn.addEventListener('click', closeModalFunc);

	// Close modal when clicking outside
	modal.addEventListener('click', function(e) {
		if (e.target === modal) {
			closeModalFunc();
		}
	});

	// Close modal with Escape key
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && modal.style.display === 'block') {
			closeModalFunc();
		}
	});

	// Copy payload to clipboard
	copyPayloadBtn.addEventListener('click', function() {
		if (currentPayload) {
			try {
				// Try to parse and format as JSON
				const payloadObj = JSON.parse(currentPayload);
				const formattedJson = JSON.stringify(payloadObj, null, 2);
				navigator.clipboard.writeText(formattedJson).then(function() {
					alert('<?php esc_html_e( 'JSON copied to clipboard!', 'crbs-zoho-flow-bridge' ); ?>');
				}).catch(function(err) {
					console.error('Failed to copy:', err);
					alert('<?php esc_html_e( 'Failed to copy to clipboard.', 'crbs-zoho-flow-bridge' ); ?>');
				});
			} catch (e) {
				// If not valid JSON, copy as-is
				navigator.clipboard.writeText(currentPayload).then(function() {
					alert('<?php esc_html_e( 'Data copied to clipboard!', 'crbs-zoho-flow-bridge' ); ?>');
				}).catch(function(err) {
					console.error('Failed to copy:', err);
					alert('<?php esc_html_e( 'Failed to copy to clipboard.', 'crbs-zoho-flow-bridge' ); ?>');
				});
			}
		}
	});

	// Escape HTML function
	function escapeHtml(text) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}
})();
</script>
