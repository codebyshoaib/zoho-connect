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
								<?php
								$process_url = wp_nonce_url(
									admin_url( 'admin.php?page=crbs-zoho-flow-bridge-payloads&process_booking=1&booking_id=' . $booking_id ),
									'process_booking_' . $booking_id
								);
								?>
								<a href="<?php echo esc_url( $process_url ); ?>" class="button button-primary" style="padding: 8px 20px; font-weight: 600; border-radius: 4px; text-decoration: none;">
									<?php esc_html_e( 'Send Invoice', 'crbs-zoho-flow-bridge' ); ?>
								</a>
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
					<th><?php esc_html_e( 'Sent At', 'crbs-zoho-flow-bridge' ); ?></th>
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
