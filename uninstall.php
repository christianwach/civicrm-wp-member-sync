<?php /*
--------------------------------------------------------------------------------
CiviCRM WordPress Member Sync Uninstaller.
--------------------------------------------------------------------------------
*/



// Kick out if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit(); }



/**
 * We need to remove all capabilities granted to users via this plugin.
 */
function civi_wp_member_sync_reset_caps() {

	// Get existing settings.
	$settings = get_option( 'civi_wp_member_sync_settings', array() );

	// Try network options if we have no data.
	if ( ! array_key_exists( 'data', $settings ) ) {

		// Get existing network settings.
		$settings = get_site_option( 'civi_wp_member_sync_settings', array() );

	}

	// Bail if we still have no data.
	if ( ! array_key_exists( 'data', $settings ) ) return;

	// Get 'capabilities' association rules.
	$rules = $settings['data']['capabilities'];

	// Init capabilities list.
	$capabilities = array();

	// Sanity check.
	if ( count( $rules ) > 0 ) {
		foreach( $rules AS $rule ) {

			// Add base capability.
			$capabilities[] = $rule['capability'];

			// Add current rule caps.
			if ( count( $rule['current_rule'] ) > 0 ) {
				foreach( $rule['current_rule'] AS $status ) {

					// Add status capability.
					$capabilities[] = $rule['capability'] . '_' . $status;

				}
			}

			// Add expired rule caps.
			if ( count( $rule['expiry_rule'] ) > 0 ) {
				foreach( $rule['expiry_rule'] AS $status ) {

					// Add status capability.
					$capabilities[] = $rule['capability'] . '_' . $status;

				}
			}

		}
	}

	// Get all WordPress users.
	$users = get_users( array( 'all_with_meta' => true ) );

	// Loop through them.
	foreach( $users AS $user ) {

		// Skip if we don't have a valid user.
		if ( ! ( $user instanceof WP_User ) ) continue;
		if ( ! $user->exists() ) continue;

		if ( count( $capabilities ) > 0 ) {
			foreach( $capabilities AS $capability ) {

				// Remove capability if they have it.
				if ( $user->has_cap( $capability ) ) {
					$user->remove_cap( $capability );
				}

			}
		}

	}

}

// Remove capabilities from users.
civi_wp_member_sync_reset_caps();



// Delete standalone options.
delete_option( 'civi_wp_member_sync_version' );
delete_option( 'civi_wp_member_sync_settings' );

// Are we deleting in multisite?
if ( is_multisite() ) {

	// Delete network-activated options.
	delete_site_option( 'civi_wp_member_sync_version' );
	delete_site_option( 'civi_wp_member_sync_settings' );

}



