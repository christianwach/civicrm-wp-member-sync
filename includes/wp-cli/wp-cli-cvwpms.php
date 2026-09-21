<?php
/**
 * WP-CLI integration for this plugin.
 *
 * @package Civi_WP_Member_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Set up WP-CLI commands for this plugin.
 *
 * @since 0.7.0
 */
function civicrm_wpms_cli_bootstrap() {

	// Only do this once.
	static $done;
	if ( isset( $done ) && true === $done ) {
		return;
	}

	// Include files.
	require __DIR__ . '/commands/command-base.php';
	require __DIR__ . '/commands/command-cvwpms.php';
	require __DIR__ . '/commands/command-job.php';

	// ----------------------------------------------------------------------------
	// Add commands.
	// ----------------------------------------------------------------------------

	// Add top-level command.
	WP_CLI::add_command( 'cvwpms', 'CiviCRM_WPMS_CLI_Command' );
	WP_CLI::add_command( 'membersync', 'CiviCRM_WPMS_CLI_Command' );

	// Add Job command.
	WP_CLI::add_command( 'cvwpms job', 'CiviCRM_WPMS_CLI_Command_Job', [ 'before_invoke' => 'CiviCRM_WPMS_CLI_Command_Job::check_dependencies' ] );
	WP_CLI::add_command( 'membersync job', 'CiviCRM_WPMS_CLI_Command_Job', [ 'before_invoke' => 'CiviCRM_WPMS_CLI_Command_Job::check_dependencies' ] );

	// We're done.
	$done = true;

}

// Set up commands.
WP_CLI::add_hook( 'before_wp_load', 'civicrm_wpms_cli_bootstrap' );
