<?php
/**
 * Command class.
 *
 * @package Civi_WP_Member_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Manage CiviCRM Member Sync through the command-line.
 *
 * ## EXAMPLES
 *
 *     $ wp cvwpms job sync-memberships
 *     Success: Executed 'sync-memberships' job.
 *
 *     $ wp cvwpms job sync-users
 *     Synced Membership Rules for User (User ID: 4)
 *     Synced Membership Rules for User (User ID: 5)
 *     Success: Executed 'sync-users' job.
 *
 * @since 0.7.0
 *
 * @package Civi_WP_Member_Sync
 */
class CiviCRM_WPMS_CLI_Command extends CiviCRM_WPMS_CLI_Command_Base {

	/**
	 * Adds our description and sub-commands.
	 *
	 * @since 0.7.0
	 *
	 * @param object $command The command.
	 * @return array $info The array of information about the command.
	 */
	private function command_to_array( $command ) {

		$info = [
			'name'        => $command->get_name(),
			'description' => $command->get_shortdesc(),
			'longdesc'    => $command->get_longdesc(),
		];

		foreach ( $command->get_subcommands() as $subcommand ) {
			$info['subcommands'][] = $this->command_to_array( $subcommand );
		}

		if ( empty( $info['subcommands'] ) ) {
			$info['synopsis'] = (string) $command->get_synopsis();
		}

		return $info;

	}

}
