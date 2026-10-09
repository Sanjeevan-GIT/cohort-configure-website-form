<?php
/**
 * Uninstall script for Configure Website Form plugin.
 *
 * @package ConfigureWebsiteForm
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Delete custom tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cwf_quotes" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cwf_subscribers" );

// Delete options.
delete_option( 'cwf_recipient_emails' );
delete_option( 'cwf_from_email' );
delete_option( 'cwf_smtp_enabled' );
delete_option( 'cwf_smtp_email' );
delete_option( 'cwf_smtp_app_password' );
delete_option( 'cwf_mailchimp_enabled' );
delete_option( 'cwf_mailchimp_api_key' );
delete_option( 'cwf_mailchimp_list_id' );
delete_option( 'cwf_mailchimp_status' );