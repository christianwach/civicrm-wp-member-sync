<?php /*
--------------------------------------------------------------------------------
CiviCRM WordPress Member Sync Uninstaller
--------------------------------------------------------------------------------
*/



// kick out if uninstall not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit(); }



/**
 * We need to remove all capabilities granted to users via this plugin
 */
function civi_wp_member_sync_reset_caps() {

	// get existing settings
	$settings = get_option( 'civi_wp_member_sync_settings', array() );

	// try network options if we have no data
	if ( ! array_key_exists( 'data', $settings ) ) {

		// get existing network settings
		$settings = get_site_option( 'civi_wp_member_sync_settings', array() );

	}

	// bail if we still have no data
	if ( ! array_key_exists( 'data', $settings ) ) return;

	// get 'capabilities' association rules
	$rules = $settings['data']['capabilities'];

	// init capabilities list
	$capabilities = array();

	// sanity check
	if ( count( $rules ) > 0 ) {
		foreach( $rules AS $rule ) {

			// add base capability
			$capabilities[] = $rule['capability'];

			// add current rule caps
			if ( count( $rule['current_rule'] ) > 0 ) {
				foreach( $rule['current_rule'] AS $status ) {

					// add status capability
					$capabilities[] = $rule['capability'] . '_' . $status;

				}
			}

			// add expired rule caps
			if ( count( $rule['expiry_rule'] ) > 0 ) {
				foreach( $rule['expiry_rule'] AS $status ) {

					// add status capability
					$capabilities[] = $rule['capability'] . '_' . $status;

				}
			}

		}
	}

	// get all WordPress users
	$users = get_users( array( 'all_with_meta' => true ) );

	// loop through them
	foreach( $users AS $user ) {

		// skip if we don't have a valid user
		if ( ! ( $user instanceof WP_User ) ) continue;
		if ( ! $user->exists() ) continue;

		if ( count( $capabilities ) > 0 ) {
			foreach( $capabilities AS $capability ) {

				// remove capability if they have it
				if ( $user->has_cap( $capability ) ) {
					$user->remove_cap( $capability );
				}

			}
		}

	}

}

// remove capabilities from users
civi_wp_member_sync_reset_caps();



// delete standalone options
delete_option( 'civi_wp_member_sync_version' );
delete_option( 'civi_wp_member_sync_settings' );

// are we deleting in multisite?
if ( is_multisite() ) {

	// delete network-activated options
	delete_site_option( 'civi_wp_member_sync_version' );
	delete_site_option( 'civi_wp_member_sync_settings' );

}



