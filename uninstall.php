<?php /*
--------------------------------------------------------------------------------
CiviCRM WordPress Member Sync Uninstaller
--------------------------------------------------------------------------------
*/



// kick out if uninstall not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit(); }



// delete standalone options
delete_option( 'civi_wp_member_sync_version' );
delete_option( 'civi_wp_member_sync_settings' );

// are we deleting in multisite?
if ( is_multisite() ) {

	// delete multisite options
	//delete_site_option( 'civi_wp_member_sync_network_settings );

}



