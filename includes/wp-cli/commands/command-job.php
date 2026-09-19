<?php
/**
 * Job command class.
 *
 * @package Civi_WP_Member_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Run CiviCRM Member Sync cron jobs.
 *
 * ## EXAMPLES
 *
 *     # Dry run with existing WordPress Users.
 *     $ wp cvwpms job sync-memberships --dry-run
 *     +-----+--------------+-----------+-----------------+----------+
 *     | New | Contact Name | Username  | Membership Type | Status   |
 *     +-----+--------------+-----------+-----------------+----------+
 *     | no  | Ray Cruz     | ray-cruz  | General         | New      |
 *     | no  | Troy Cruz    | troy-cruz | General         | Deceased |
 *     +-----+--------------+-----------+-----------------+----------+
 *
 *     # Dry run creating WordPress Users.
 *     $ wp cvwpms job sync-memberships --dry-run --create-users
 *     +-----+------------------------+----------------------+-----------------+----------+
 *     | New | Contact Name           | Username             | Membership Type | Status   |
 *     +-----+------------------------+----------------------+-----------------+----------+
 *     | yes | Lawerence Olsen        | lawerence-olsen      | General         | Expired  |
 *     | yes | Ray McReynolds Sr.     | ray-mcreynolds-sr    | Student         | Expired  |
 *     | yes | Mrs. Damaris Wilson    | mrs-damaris-wilson   | General         | Expired  |
 *     | no  | Ray Cruz               | ray-cruz             | General         | New      |
 *     | yes | Dr. Bob Zope-Yadav Jr. | dr-bob-zope-yadav-jr | Student         | Expired  |
 *     | no  | Troy Cruz              | troy-cruz            | General         | Deceased |
 *     | yes | Rebekah Roberts        | rebekah-roberts      | General         | Expired  |
 *     | yes | Erik Patel             | erik-patel           | Student         | Expired  |
 *     | yes | Angelika Samuels       | angelika-samuels     | Lifetime        | Current  |
 *     | yes | tanyay@example.co.uk   | tanyayexample-co-uk  | Lifetime        | Current  |
 *     +-----+------------------------+----------------------+-----------------+----------+
 *     Success: Executed 'sync-memberships' job.
 *
 *     # Dry run creating WordPress Users and limiting to a range of Membership IDs.
 *     $ wp cvwpms job sync-memberships --dry-run --create-users --id-from=2 --id-to=6
 *     +-----+------------------------+----------------------+-----------------+----------+
 *     | New | Contact Name           | Username             | Membership Type | Status   |
 *     +-----+------------------------+----------------------+-----------------+----------+
 *     | yes | Lawerence Olsen        | lawerence-olsen      | General         | Expired  |
 *     | yes | Ray McReynolds Sr.     | ray-mcreynolds-sr    | Student         | Expired  |
 *     | yes | Erik Patel             | erik-patel           | Student         | Expired  |
 *     +-----+------------------------+----------------------+-----------------+----------+
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
class CiviCRM_WPMS_CLI_Command_Job extends CiviCRM_WPMS_CLI_Command {

	/**
	 * Sync CiviCRM Memberships to Roles or Capabilities on WordPress Users.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Run the sync with no change and get feedback on what would happen.
	 *
	 * [--create-users]
	 * : Create a WordPress User for each Membership when one does not already exist.
	 *
	 * [--id-from=<id-from>]
	 * : Optionally run the sync from the given Membership ID.
	 *
	 * [--id-to=<id-to>]
	 * : Optionally run the sync to the given Membership ID.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - pretty
	 *   - json
	 *   - table
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Dry run with existing WordPress Users.
	 *     $ wp cvwpms job sync-memberships --dry-run
	 *     +-----+--------------+-----------+-----------------+----------+
	 *     | New | Contact Name | Username  | Membership Type | Status   |
	 *     +-----+--------------+-----------+-----------------+----------+
	 *     | no  | Ray Cruz     | ray-cruz  | General         | New      |
	 *     | no  | Troy Cruz    | troy-cruz | General         | Deceased |
	 *     +-----+--------------+-----------+-----------------+----------+
	 *
	 *     # Dry run creating WordPress Users.
	 *     $ wp cvwpms job sync-memberships --dry-run --create-users
	 *     +-----+------------------------+----------------------+-----------------+----------+
	 *     | New | Contact Name           | Username             | Membership Type | Status   |
	 *     +-----+------------------------+----------------------+-----------------+----------+
	 *     | yes | Lawerence Olsen        | lawerence-olsen      | General         | Expired  |
	 *     | yes | Ray McReynolds Sr.     | ray-mcreynolds-sr    | Student         | Expired  |
	 *     | yes | Mrs. Damaris Wilson    | mrs-damaris-wilson   | General         | Expired  |
	 *     | no  | Ray Cruz               | ray-cruz             | General         | New      |
	 *     | yes | Dr. Bob Zope-Yadav Jr. | dr-bob-zope-yadav-jr | Student         | Expired  |
	 *     | no  | Troy Cruz              | troy-cruz            | General         | Deceased |
	 *     | yes | Rebekah Roberts        | rebekah-roberts      | General         | Expired  |
	 *     | yes | Erik Patel             | erik-patel           | Student         | Expired  |
	 *     | yes | Angelika Samuels       | angelika-samuels     | Lifetime        | Current  |
	 *     | yes | tanyay@example.co.uk   | tanyayexample-co-uk  | Lifetime        | Current  |
	 *     +-----+------------------------+----------------------+-----------------+----------+
	 *     Success: Executed 'sync-memberships' job.
	 *
	 *     # Dry run creating WordPress Users and limiting to a range of Membership IDs.
	 *     $ wp cvwpms job sync-memberships --dry-run --create-users --id-from=2 --id-to=6
	 *     +-----+------------------------+----------------------+-----------------+----------+
	 *     | New | Contact Name           | Username             | Membership Type | Status   |
	 *     +-----+------------------------+----------------------+-----------------+----------+
	 *     | yes | Lawerence Olsen        | lawerence-olsen      | General         | Expired  |
	 *     | yes | Ray McReynolds Sr.     | ray-mcreynolds-sr    | Student         | Expired  |
	 *     | yes | Erik Patel             | erik-patel           | Student         | Expired  |
	 *     +-----+------------------------+----------------------+-----------------+----------+
	 *     Success: Executed 'sync-memberships' job.
	 *
	 * @alias sync-memberships
	 *
	 * @since 0.7.0
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function sync_memberships( $args, $assoc_args ) {

		// Grab associative arguments.
		$format = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		$plugin = civicrm_wpms();

		/** This action is documented in includes/civi-wp-ms-admin.php */
		do_action( 'civi_wp_member_sync_pre_sync_all' );

		// Modify batch count.
		add_filter(
			'civi_wp_member_sync_get_batch_count',
			function () {
				return 0;
			}
		);

		// Sync all CiviCRM Memberships.
		$results = $plugin->members->sync_all_civicrm_memberships( $assoc_args );

		/** This action is documented in includes/civi-wp-ms-admin.php */
		do_action( 'civi_wp_member_sync_after_sync_all' );

		switch ( $format ) {

			// Pretty-print output.
			case 'pretty':
				WP_CLI::log( print_r( $results, true ) );
				break;

			// Display output as JSON.
			case 'json':
				$json = json_encode( $results );
				if ( JSON_ERROR_NONE !== json_last_error() ) {
					WP_CLI::error( sprintf( WP_CLI::colorize( 'Failed to encode JSON: %Y%s.%n' ), json_last_error_msg() ) );
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $json . "\n";
				break;

			// Display output as table (default).
			case 'table':
			default:
				// Define the table columns.
				$fields = [ 'New', 'Contact Name', 'Username', 'Membership Type', 'Status' ];

				/**
				 * Filters the current table columns.
				 *
				 * @since 0.7.0
				 *
				 * @param array $fields The default array of fields.
				 */
				$fields = apply_filters( 'cwms/wpcli/sync_memberships/table/fields', $fields );

				// Build the rows.
				$rows = [];
				foreach ( $results['feedback'] as $result ) {
					foreach ( $result as $item ) {

						$row = [
							'New'             => $item['is_new'] ? 'Yes' : 'No',
							'Contact Name'    => $item['display_name'],
							'Username'        => $item['username'],
							'Membership Type' => $item['membership_name'],
							'Status'          => $item['membership_status'],
						];

						/**
						 * Filters the current table row.
						 *
						 * @since 0.7.0
						 *
						 * @param array $row The default row.
						 * @param array $item The item being processed.
						 */
						$row = apply_filters( 'cwms/wpcli/sync_memberships/table/row', $row, $item );

						// Add to rows.
						$rows[] = $row;

					}
				}

				// Display the rows.
				$args = [ 'format' => $format ];
				$formatter = new \WP_CLI\Formatter( $args, $fields );
				$formatter->display_items( $rows );

		}

		WP_CLI::log( '' );
		WP_CLI::success( "Executed 'sync-memberships' job." );

	}

	/**
	 * Run the CiviCRM Membership Rules for all existing WordPress Users.
	 *
	 * ## OPTIONS
	 *
	 * [--vvv]
	 * : Run the sync with verbose output.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp cvwpms job sync-users
	 *     Synced Membership Rules for User (User ID: 4)
	 *     Synced Membership Rules for User (User ID: 5)
	 *     Success: Executed 'sync-users' job.
	 *
	 * @alias sync-users
	 *
	 * @since 0.7.0
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function sync_users( $args, $assoc_args ) {

		// Grab associative arguments.
		$verbose = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'vvv', false );

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		// Sync Memberships for all existing WordPress Users.
		civicrm_wpms()->members->sync_all_wp_user_memberships( $verbose );

		WP_CLI::log( '' );
		WP_CLI::success( "Executed 'sync_users' job." );

	}

}
