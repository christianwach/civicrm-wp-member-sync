<?php
/**
 * Uninstaller.
 *
 * Handles uninstallation functionality.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Kick out if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}



/**
 * We need to remove all Capabilities granted to Users via this plugin.
 */
function civi_wp_member_sync_reset_caps() {

	// Get existing settings.
	$settings = get_option( 'civi_wp_member_sync_settings', [] );

	// Try network options if we have no data.
	if ( ! array_key_exists( 'data', $settings ) ) {

		// Get existing network settings.
		$settings = get_site_option( 'civi_wp_member_sync_settings', [] );

	}

	// Bail if we still have no data.
	if ( ! array_key_exists( 'data', $settings ) ) {
		return;
	}

	// Get 'capabilities' association rules.
	$rules = $settings['data']['capabilities'];

	// Init Capabilities list.
	$capabilities = [];

	// Sanity check.
	if ( count( $rules ) > 0 ) {
		foreach ( $rules as $rule ) {

			// Add base Capability.
			$capabilities[] = $rule['capability'];

			// Add current rule caps.
			if ( count( $rule['current_rule'] ) > 0 ) {
				foreach ( $rule['current_rule'] as $status ) {

					// Add status Capability.
					$capabilities[] = $rule['capability'] . '_' . $status;

				}
			}

			// Add expired rule caps.
			if ( count( $rule['expiry_rule'] ) > 0 ) {
				foreach ( $rule['expiry_rule'] as $status ) {

					// Add status Capability.
					$capabilities[] = $rule['capability'] . '_' . $status;

				}
			}

		}
	}

	// Get all WordPress Users.
	$users = get_users( [ 'all_with_meta' => true ] );

	// Loop through them.
	foreach ( $users as $user ) {

		// Skip if we don't have a valid User.
		if ( ! ( $user instanceof WP_User ) ) {
			continue;
		}
		if ( ! $user->exists() ) {
			continue;
		}

		if ( count( $capabilities ) > 0 ) {
			foreach ( $capabilities as $capability ) {

				// Remove Capability if they have it.
				if ( $user->has_cap( $capability ) ) {
					$user->remove_cap( $capability );
				}

			}
		}

	}

}

// Remove Capabilities from Users.
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
