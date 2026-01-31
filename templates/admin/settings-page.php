<?php
/**
 * Admin Settings Page Template
 *
 * @package ZohoConnectSerializer
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$config = \ZohoConnectSerializer\Core\Plugin::get_instance()
	->get_container()
	->make( 'config' );

// Handle form submission
if ( isset( $_POST['submit'] ) && check_admin_referer( 'crbs_zoho_settings' ) ) {
	$config->set( 'zoho_flow_webhook_url', sanitize_text_field( $_POST['zoho_flow_webhook_url'] ?? '' ) );
	$config->set( 'enable_logging', isset( $_POST['enable_logging'] ) ? 1 : 0 );
	$config->set( 'log_level', sanitize_text_field( $_POST['log_level'] ?? 'info' ) );
	$config->set( 'debug_output_method', sanitize_text_field( $_POST['debug_output_method'] ?? 'console' ) );
	
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved!', 'crbs-zoho-flow-bridge' ) . '</p></div>';
}

// Check if CRBS is active
$crbs_active = class_exists( 'CRBSBooking' );
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<?php if ( ! $crbs_active ) : ?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Warning:', 'crbs-zoho-flow-bridge' ); ?></strong> <?php esc_html_e( 'CRBS plugin is not active. This plugin requires CRBS to function.', 'crbs-zoho-flow-bridge' ); ?></p>
		</div>
	<?php endif; ?>
	
	<form method="post" action="">
		<?php wp_nonce_field( 'crbs_zoho_settings' ); ?>
		
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="zoho_flow_webhook_url">
						<?php esc_html_e( 'Zoho Flow Webhook URL', 'crbs-zoho-flow-bridge' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="url" 
						id="zoho_flow_webhook_url"
						name="zoho_flow_webhook_url"
						value="<?php echo esc_attr( $config->get( 'zoho_flow_webhook_url' ) ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Enter the webhook URL from Zoho Flow (optional for now - currently in debug mode)', 'crbs-zoho-flow-bridge' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="debug_output_method">
						<?php esc_html_e( 'Debug Output Method', 'crbs-zoho-flow-bridge' ); ?>
					</label>
				</th>
				<td>
					<select id="debug_output_method" name="debug_output_method">
						<option value="console" <?php selected( $config->get( 'debug_output_method' ), 'console' ); ?>>
							<?php esc_html_e( 'Console (error_log)', 'crbs-zoho-flow-bridge' ); ?>
						</option>
						<option value="admin_page" <?php selected( $config->get( 'debug_output_method' ), 'admin_page' ); ?>>
							<?php esc_html_e( 'Admin Page', 'crbs-zoho-flow-bridge' ); ?>
						</option>
						<option value="both" <?php selected( $config->get( 'debug_output_method' ), 'both' ); ?>>
							<?php esc_html_e( 'Both', 'crbs-zoho-flow-bridge' ); ?>
						</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'How to output serialized payload data for debugging', 'crbs-zoho-flow-bridge' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="enable_logging">
						<?php esc_html_e( 'Enable Logging', 'crbs-zoho-flow-bridge' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="checkbox" 
						id="enable_logging"
						name="enable_logging"
						value="1"
						<?php checked( $config->get( 'enable_logging', true ) ); ?>
					/>
					<p class="description">
						<?php esc_html_e( 'Enable logging for debugging purposes', 'crbs-zoho-flow-bridge' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="log_level">
						<?php esc_html_e( 'Log Level', 'crbs-zoho-flow-bridge' ); ?>
					</label>
				</th>
				<td>
					<select id="log_level" name="log_level">
						<option value="debug" <?php selected( $config->get( 'log_level' ), 'debug' ); ?>>
							<?php esc_html_e( 'Debug', 'crbs-zoho-flow-bridge' ); ?>
						</option>
						<option value="info" <?php selected( $config->get( 'log_level' ), 'info' ); ?>>
							<?php esc_html_e( 'Info', 'crbs-zoho-flow-bridge' ); ?>
						</option>
						<option value="warning" <?php selected( $config->get( 'log_level' ), 'warning' ); ?>>
							<?php esc_html_e( 'Warning', 'crbs-zoho-flow-bridge' ); ?>
						</option>
						<option value="error" <?php selected( $config->get( 'log_level' ), 'error' ); ?>>
							<?php esc_html_e( 'Error', 'crbs-zoho-flow-bridge' ); ?>
						</option>
					</select>
				</td>
			</tr>
		</table>
		
		<?php submit_button(); ?>
	</form>

	<div class="card" style="margin-top: 20px;">
		<h2><?php esc_html_e( 'How It Works', 'crbs-zoho-flow-bridge' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'When a CRBS booking is saved/updated, the plugin automatically captures the booking data.', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><?php esc_html_e( 'The booking data is serialized into a format ready for Zoho Flow.', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><?php esc_html_e( 'Currently in debug mode: payloads are output to console or admin page (based on settings above).', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><?php esc_html_e( 'View all processed payloads in the "View Payloads" submenu.', 'crbs-zoho-flow-bridge' ); ?></li>
		</ol>
	</div>

	<div class="card" style="margin-top: 20px;">
		<h2><?php esc_html_e( 'Booking Status Configuration', 'crbs-zoho-flow-bridge' ); ?></h2>
		<p><strong><?php esc_html_e( 'CRBS Booking Statuses:', 'crbs-zoho-flow-bridge' ); ?></strong></p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><strong>1</strong> = <?php esc_html_e( 'Pending (new)', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><strong>2</strong> = <?php esc_html_e( 'Processing (accepted)', 'crbs-zoho-flow-bridge' ); ?> ✓ <em><?php esc_html_e( '(default)', 'crbs-zoho-flow-bridge' ); ?></em></li>
			<li><strong>3</strong> = <?php esc_html_e( 'Cancelled (rejected)', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><strong>4</strong> = <?php esc_html_e( 'Completed (finished)', 'crbs-zoho-flow-bridge' ); ?> ✓ <em><?php esc_html_e( '(default)', 'crbs-zoho-flow-bridge' ); ?></em></li>
			<li><strong>5</strong> = <?php esc_html_e( 'On hold', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><strong>6</strong> = <?php esc_html_e( 'Refunded', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><strong>7</strong> = <?php esc_html_e( 'Failed', 'crbs-zoho-flow-bridge' ); ?></li>
		</ul>
		<p><em><?php esc_html_e( 'By default, only statuses 2 (Processing) and 4 (Completed) are processed.', 'crbs-zoho-flow-bridge' ); ?></em></p>
	</div>

	<div class="card" style="margin-top: 20px;">
		<h2><?php esc_html_e( 'Troubleshooting', 'crbs-zoho-flow-bridge' ); ?></h2>
		<p><strong><?php esc_html_e( 'If bookings are not appearing:', 'crbs-zoho-flow-bridge' ); ?></strong></p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'By default, only bookings with status IDs 2 (Processing) or 4 (Completed) are processed.', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><?php esc_html_e( 'The booking must be published (not draft).', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><?php esc_html_e( 'To process ALL booking statuses, add this to your theme\'s functions.php:', 'crbs-zoho-flow-bridge' ); ?></li>
		</ul>
		<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; margin: 10px 0;"><code>add_filter('qzb_allow_all_booking_statuses', '__return_true');</code></pre>
		<p><?php esc_html_e( 'Or to allow specific status IDs, add:', 'crbs-zoho-flow-bridge' ); ?></p>
		<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; margin: 10px 0;"><code>add_filter('qzb_allowed_booking_statuses', function($statuses) {
    return array(1, 2, 3, 4, 5, 6, 7); // Add your status IDs here
});</code></pre>
		<p><?php esc_html_e( 'To force re-processing a booking that was already processed, add this to wp-config.php:', 'crbs-zoho-flow-bridge' ); ?></p>
		<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; margin: 10px 0;"><code>define('QZB_FORCE_RESEND', true);</code></pre>
	</div>
</div>
