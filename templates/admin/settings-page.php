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
						<?php esc_html_e( 'Enter the webhook URL from Zoho Flow', 'crbs-zoho-flow-bridge' ); ?>
					</p>
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
			<li><?php esc_html_e( 'In debug mode, payloads are output to console or admin page.', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><?php esc_html_e( 'To enable actual webhook sending, you\'ll add the webhook URL to the Zoho Flow flow and set the webhook URL in the plugin settings.', 'crbs-zoho-flow-bridge' ); ?></li>
			<li><?php esc_html_e( 'View all processed payloads in the "View Payloads" submenu.', 'crbs-zoho-flow-bridge' ); ?></li>
		</ol>
	</div>
</div>
