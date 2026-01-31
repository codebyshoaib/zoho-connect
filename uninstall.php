<?php
/**
 * Uninstall script
 *
 * @package ZohoConnectSerializer
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options
delete_option( 'zoho_connect_serializer_zoho_flow_webhook_url' );
delete_option( 'zoho_connect_serializer_enable_logging' );
delete_option( 'zoho_connect_serializer_log_level' );
delete_option( 'zoho_connect_serializer_debug_output_method' );

// Note: We don't delete post meta (_qzb_sent_to_zoho, etc.) to preserve booking history
// If you want to clean up, uncomment the following:
/*
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_qzb_%'" );
*/
