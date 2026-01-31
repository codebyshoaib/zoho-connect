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
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<form method="post" action="options.php">
		<?php settings_fields( 'zoho_connect_serializer_settings' ); ?>
		
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="zoho_flow_webhook_url">
						<?php esc_html_e( 'Zoho Flow Webhook URL', 'zoho-connect-serializer' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="url" 
						id="zoho_flow_webhook_url"
						name="zoho_connect_serializer_zoho_flow_webhook_url"
						value="<?php echo esc_attr( $config->get( 'zoho_flow_webhook_url' ) ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Enter the webhook URL from Zoho Flow', 'zoho-connect-serializer' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="enable_logging">
						<?php esc_html_e( 'Enable Logging', 'zoho-connect-serializer' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="checkbox" 
						id="enable_logging"
						name="zoho_connect_serializer_enable_logging"
						value="1"
						<?php checked( $config->get( 'enable_logging', true ) ); ?>
					/>
					<p class="description">
						<?php esc_html_e( 'Enable logging for debugging purposes', 'zoho-connect-serializer' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="log_level">
						<?php esc_html_e( 'Log Level', 'zoho-connect-serializer' ); ?>
					</label>
				</th>
				<td>
					<select id="log_level" name="zoho_connect_serializer_log_level">
						<option value="debug" <?php selected( $config->get( 'log_level' ), 'debug' ); ?>>
							<?php esc_html_e( 'Debug', 'zoho-connect-serializer' ); ?>
						</option>
						<option value="info" <?php selected( $config->get( 'log_level' ), 'info' ); ?>>
							<?php esc_html_e( 'Info', 'zoho-connect-serializer' ); ?>
						</option>
						<option value="warning" <?php selected( $config->get( 'log_level' ), 'warning' ); ?>>
							<?php esc_html_e( 'Warning', 'zoho-connect-serializer' ); ?>
						</option>
						<option value="error" <?php selected( $config->get( 'log_level' ), 'error' ); ?>>
							<?php esc_html_e( 'Error', 'zoho-connect-serializer' ); ?>
						</option>
					</select>
				</td>
			</tr>
		</table>
		
		<?php submit_button(); ?>
	</form>
</div>
