<?php
/**
 * Membership class.
 *
 * Handles CiviCRM Membership functionality.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership class.
 *
 * Class for encapsulating CiviCRM Membership functionality.
 *
 * @since 0.1
 *
 * @package Civi_WP_Member_Sync
 */
class Civi_WP_Member_Sync_Members {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $plugin The plugin object
	 */
	public $plugin;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Initialise first.
		add_action( 'civi_wp_member_sync_initialised', [ $this, 'initialise' ], 7 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Initialise this object.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Get our login/logout sync setting.
		$login = absint( $this->plugin->admin->setting_get( 'login' ) );

		// Add hooks if set.
		if ( $login === 1 ) {

			// Add login check.
			add_action( 'wp_login', [ $this, 'sync_to_user' ], 10, 2 );

			// Add logout check (can't use 'wp_logout' action, as User no longer exists).
			add_action( 'clear_auth_cookie', [ $this, 'sync_on_logout' ] );

		}

		// Get our CiviCRM sync setting.
		$civicrm = absint( $this->plugin->admin->setting_get( 'civicrm' ) );

		// Add hooks if set.
		if ( $civicrm === 1 ) {

			// Intercept CiviCRM Membership add/edit form submission.
			add_action( 'civicrm_postProcess', [ $this, 'membership_form_process' ], 10, 2 );

			// Intercept before a CiviCRM Membership update.
			add_action( 'civicrm_pre', [ $this, 'membership_pre_update' ], 10, 4 );

			// Intercept a CiviCRM Membership update.
			add_action( 'civicrm_post', [ $this, 'membership_updated' ], 10, 4 );

			// Intercept a CiviCRM Membership deletion.
			add_action( 'civicrm_post', [ $this, 'membership_deleted' ], 10, 4 );

		}

		// Is this the back end?
		if ( is_admin() ) {

			// Add AJAX handler.
			add_action( 'wp_ajax_sync_memberships', [ $this, 'sync_all_civicrm_memberships' ] );

		}

		// Filter Memberships and override for Contact in Trash.
		add_filter( 'civi_wp_member_sync_memberships_get', [ $this, 'membership_override' ], 10, 3 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Sync Membership rules for all CiviCRM Memberships.
	 *
	 * @since 0.2.8
	 */
	public function sync_all_civicrm_memberships() {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return;
		}

		// Init AJAX return.
		$data = [];

		// Assume not creating Users.
		$create_users = false;

		// Grab "Create Users" value.
		$manual_sync_create = '';
		$manual_sync_create_raw = filter_input( INPUT_POST, 'civi_wp_member_sync_manual_sync_create' );
		if ( ! empty( $manual_sync_create_raw ) ) {
			$manual_sync_create = trim( wp_unslash( $manual_sync_create_raw ) );
		}

		// Override "Create Users" flag if chosen.
		if ( $manual_sync_create === 'y' ) {
			$create_users = true;
		}

		// Assume not dry run.
		$dry_run = false;

		// Override "dry run" flag if chosen.
		$manual_sync_dry_run = filter_input( INPUT_POST, 'civi_wp_member_sync_manual_sync_dry_run' );
		if ( ! empty( $manual_sync_dry_run ) && 'y' === trim( wp_unslash( $manual_sync_dry_run ) ) ) {
			$dry_run = true;
		}

		// If the Memberships offset value doesn't exist.
		if ( 'fgffgs' == get_option( '_civi_wpms_memberships_offset', 'fgffgs' ) ) {

			// Start at the beginning.
			$memberships_offset = 0;
			$memberships_from = 0;

			// Override if "From" field is populated.
			$manual_sync_from = filter_input( INPUT_POST, 'civi_wp_member_sync_manual_sync_from' );
			if ( ! empty( $manual_sync_from ) && is_numeric( trim( wp_unslash( $manual_sync_from ) ) ) ) {
				$memberships_from = (int) trim( wp_unslash( $manual_sync_from ) );
				$memberships_offset = $memberships_from;
			}

			add_option( '_civi_wpms_memberships_offset', (string) $memberships_from );

		} else {

			// Use the existing value.
			$memberships_offset = (int) get_option( '_civi_wpms_memberships_offset', '0' );

		}

		// Get batch count.
		$batch_count = $this->plugin->admin->setting_get_batch_count();

		// If the "To" field is populated.
		$manual_sync_to = filter_input( INPUT_POST, 'civi_wp_member_sync_manual_sync_to' );
		if ( ! empty( $manual_sync_to ) && is_numeric( trim( wp_unslash( $manual_sync_to ) ) ) ) {

			// Grab the "to" value.
			$memberships_to = (int) trim( wp_unslash( $manual_sync_to ) );

			// Update batch count if the end of the default batch is greater than the requested one.
			if ( $memberships_to > 0 && $memberships_to < ( $memberships_offset + $batch_count ) ) {
				$batch_count = $memberships_to - $memberships_offset;
			}

		}

		// Get CiviCRM Memberships.
		$memberships = $this->memberships_get( $memberships_offset, $batch_count );

		// If we have Membership details.
		if ( $memberships !== false && $batch_count > 0 ) {

			// Set finished flag.
			$data['finished'] = 'false';

			// Set "from" and "to" flags.
			$data['from'] = $memberships_offset;
			$data['to'] = $data['from'] + $batch_count;

			// Init processed array.
			$processed = [];

			// Init Feedback array.
			$feedback = [];

			// Loop through Memberships.
			foreach ( $memberships['values'] as $membership ) {

				// Get Contact ID.
				$civi_contact_id = isset( $membership['contact_id'] ) ? $membership['contact_id'] : false;
				if ( $civi_contact_id === false ) {
					continue;
				}

				/*
				 * The processed array is a bit of a hack:
				 *
				 * - the array is lost each time a batch finishes
				 * - there may be duplicate Contact IDs across batches
				 * - it means that the sync progress isn't entirely truthful
				 *
				 * However, when there are lots of Memberships per Contact (as
				 * some folks like to keep historical records of Memberships
				 * by creating new ones instead of renewing existing ones) then
				 * this should save a fair but of processing.
				 */

				// Continue if we've already processed this Contact.
				if ( in_array( $civi_contact_id, $processed ) ) {
					continue;
				}

				// Add Contact ID to processed so we don't re-process.
				$processed[] = $civi_contact_id;

				/*
				 * The use of existing code here is not the most efficient way to
				 * sync each Membership. However, given that in most cases there
				 * will only be one Membership per Contact, I think the overhead
				 * will be minimal. Moreover, this new chunked sync method limits
				 * the impact of a manual sync per request.
				 */

				// Get *all* Memberships for this Contact.
				$all_memberships = $this->membership_get_by_contact_id( $civi_contact_id );

				// Continue if there are no applicable rules for these Memberships.
				if ( ! $this->plugin->admin->rule_exists( $all_memberships ) ) {
					continue;
				}

				// Get WordPress User.
				$user = $this->plugin->users->wp_user_get_by_civi_id( $civi_contact_id );

				// If this isn't a Dry Run and we don't have a valid User.
				if ( ! ( $user instanceof WP_User ) || ! $user->exists() ) {

					// Create a WordPress User if asked to.
					if ( $create_users ) {

						// If this is not a Dry Run.
						if ( ! $dry_run ) {

							// Create WordPress User and prepare for sync.
							$user = $this->user_prepare_for_sync( $civi_contact_id );

						} else {

							// Create a dummy WordPress User.
							$user = $this->user_prepare_for_simulate( $civi_contact_id );

						}

						// Skip to next if something went wrong.
						if ( $user === false ) {
							continue;
						}
						if ( ! ( $user instanceof WP_User ) || ! $user->exists() ) {
							continue;
						}

					}

				}

				// Should this User be synced?
				if ( ! $this->user_should_be_synced( $user ) ) {
					continue;
				}

				// Simulate the "rule_apply" logic if Dry Run.
				if ( $dry_run ) {
					$result = $this->plugin->admin->rule_simulate( $user, $all_memberships );
				} else {
					$result = $this->plugin->admin->rule_apply( $user, $all_memberships );
				}

				// Build feedback row from template.
				ob_start();
				include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/manual-sync-feedback.php';
				$feedback[] = ob_get_contents();
				ob_end_clean();

			}

			// Append to data.
			$data['feedback'] = implode( "\n", $feedback );

			// Increment Memberships offset option.
			update_option( '_civi_wpms_memberships_offset', (string) $data['to'] );

		} else {

			// Delete the option to start from the beginning.
			delete_option( '_civi_wpms_memberships_offset' );

			// Set finished flag.
			$data['finished'] = 'true';

		}

		// Send data to browser.
		wp_send_json( $data );

	}

	/**
	 * Sync all Membership rules for existing WordPress Users.
	 *
	 * @since 0.1
	 *
	 * @return bool $success True if successful, false otherwise.
	 */
	public function sync_all_wp_user_memberships() {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return;
		}

		// Make sure CiviCRM file is included.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Get all WordPress Users.
		$users = get_users( [ 'all_with_meta' => true ] );

		// Loop through all Users.
		foreach ( $users as $user ) {

			// Skip if we don't have a valid User.
			if ( ! ( $user instanceof WP_User ) ) {
				continue;
			}
			if ( ! $user->exists() ) {
				continue;
			}

			// Call login method.
			$this->sync_to_user( $user->user_login, $user );

		}

	}

	/**
	 * Check User's Membership record during logout.
	 *
	 * @since 0.1
	 */
	public function sync_on_logout() {

		// Get User.
		$user = wp_get_current_user();
		$user_login = $user->user_login;

		// Call login method.
		$this->sync_to_user( $user_login, $user );

	}

	/**
	 * Maybe create a WordPress User.
	 *
	 * @since 0.3.4
	 *
	 * @param int $civi_contact_id The numeric ID of the CiviCRM Contact.
	 * @return WP_User|bool $user The WordPress User object - or false on failure.
	 */
	public function user_prepare_for_sync( $civi_contact_id ) {

		// Maybe create WordPress User.
		$user = $this->plugin->users->wp_user_create_from_contact_id( $civi_contact_id );

		// Bail if something goes wrong.
		if ( $user === false ) {
			return false;
		}
		if ( ! ( $user instanceof WP_User ) || ! $user->exists() ) {
			return false;
		}

		// Get sync method.
		$method = $this->plugin->admin->setting_get_method();

		// When syncing Roles, remove the default Role from the new User because
		// rule_apply() will set a Role when it runs.
		if ( $method == 'roles' ) {
			$user->remove_role( get_option( 'default_role' ) );
		}

		// --<
		return $user;

	}

	/**
	 * Prepare a dummy WordPress User for the Simulate process.
	 *
	 * @since 0.5
	 *
	 * @param int $civi_contact_id The numeric ID of the CiviCRM Contact.
	 * @return WP_User|bool $user The WordPress User object - or false on failure.
	 */
	public function user_prepare_for_simulate( $civi_contact_id ) {

		// Get CiviCRM Contact.
		$civi_contact = $this->plugin->users->civi_get_contact_by_contact_id( $civi_contact_id );
		if ( $civi_contact === false ) {
			return false;
		}

		// Bail if the Contact has no email.
		if ( empty( $civi_contact['email'] ) ) {
			return false;
		}

		// Let's mimic a User.
		$user = new WP_User();
		$user->ID = PHP_INT_MAX;

		// Create username from display name.
		$user_name = sanitize_title( sanitize_user( $civi_contact['display_name'] ) );
		$user_name = apply_filters( 'civi_wp_member_sync_new_username', $user_name, $civi_contact );
		$user->user_login = $this->plugin->users->unique_username( $user_name, $civi_contact );

		// Add Display Name and First & Last Names.
		$user->display_name = $civi_contact['display_name'];
		$user->first_name = $civi_contact['first_name'];
		$user->last_name = $civi_contact['last_name'];

		// --<
		return $user;

	}

	/**
	 * Check if a User's Membership should by synced.
	 *
	 * @since 0.2.6
	 *
	 * @param object $user The WordPress User object.
	 * @return bool $should_be_synced Whether or not the User should be synced.
	 */
	public function user_should_be_synced( $user ) {

		// Kick out if we don't receive a valid User.
		if ( ! ( $user instanceof WP_User ) ) {
			return false;
		}
		if ( ! $user->exists() ) {
			return false;
		}

		// Assume User should be synced.
		$should_be_synced = true;

		// Exclude admins by default.
		if ( is_super_admin( $user->ID ) || $user->has_cap( 'delete_users' ) ) {
			$should_be_synced = false;
		}

		/**
		 * Let other plugins override whether a User should be synced.
		 *
		 * @since 0.2
		 *
		 * @param bool $should_be_synced True if the User should be synced, false otherwise.
		 * @param object $user The WordPress User object.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		return apply_filters( 'civi_wp_member_sync_user_should_be_synced', $should_be_synced, $user );

	}

	/**
	 * Sync a User's Role based on their Membership record.
	 *
	 * @since 0.1
	 *
	 * @param string $user_login Logged in User's username.
	 * @param WP_User $user WP_User object of the logged-in User.
	 */
	public function sync_to_user( $user_login, $user ) {

		// Should this User be synced?
		if ( ! $this->user_should_be_synced( $user ) ) {
			return;
		}

		// Get CiviCRM Contact ID.
		$civi_contact_id = $this->plugin->users->civi_contact_id_get( $user );

		// Bail if we don't have one.
		if ( $civi_contact_id === false ) {
			return;
		}

		// Get Memberships.
		$memberships = $this->membership_get_by_contact_id( $civi_contact_id );

		// Bail if there are no applicable rules for these Memberships.
		if ( ! $this->plugin->admin->rule_exists( $memberships ) ) {
			return;
		}

		// Update WordPress User.
		$this->plugin->admin->rule_apply( $user, $memberships );

	}

	/**
	 * Inspect a CiviCRM Membership prior to it being updated.
	 *
	 * Membership renewals may change the type of Membership with no hint of the
	 * change in the data that is passed to "hook_civicrm_post". In order to see
	 * if a change of Membership Type has occurred, we need to retrieve the
	 * Membership here before the operation and compare afterwards in the
	 * membership_updated() method below.
	 *
	 * @see https://github.com/christianwach/civicrm-wp-member-sync/issues/24
	 *
	 * @since 0.1
	 *
	 * @param string $op the type of database operation.
	 * @param string $objectName the type of object.
	 * @param integer $objectId the ID of the object.
	 * @param object $objectRef the object.
	 */
	public function membership_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// Target our object type.
		if ( $objectName != 'Membership' ) {
			return;
		}

		// Only process edit operations.
		if ( $op != 'edit' ) {
			return;
		}

		// Get details of CiviCRM Membership.
		$membership = civicrm_api( 'Membership', 'get', [
			'version' => '3',
			'sequential' => 1,
			'id' => $objectId,
		] );

		// Sanity check.
		if (
			$membership['is_error'] == 0 &&
			isset( $membership['values'] ) &&
			count( $membership['values'] ) > 0
		) {

			// Store in property for later inspection.
			$this->membership_pre = $membership;

		}

	}

	/**
	 * Update a WordPress User when a CiviCRM Membership is updated.
	 *
	 * As noted by @axaak, this method should not blindly apply the rule for the
	 * edited Membership because it is possible that a Contact has multiple
	 * Memberships and the one being edited may be a historical record.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function membership_updated( $op, $objectName, $objectId, $objectRef ) {

		// Target our object type.
		if ( $objectName != 'Membership' ) {
			return;
		}

		// Kick out if we don't have a Contact ID.
		if ( ! isset( $objectRef->contact_id ) ) {
			return;
		}

		// Only process create and edit operations.
		if ( ! in_array( $op, [ 'create', 'edit' ] ) ) {
			return;
		}

		// Init previous Membership.
		$previous_membership = null;

		// For edit operations, we first need to check for renewals.
		if ( $op == 'edit' && isset( $this->membership_pre ) && isset( $objectRef->membership_type_id ) ) {

			// Make sure we're comparing like with like.
			$previous_type_id = absint( $this->membership_pre['values'][0]['membership_type_id'] );
			$current_type_id = absint( $objectRef->membership_type_id );

			// Do we have different CiviCRM Membership Types?
			if ( $previous_type_id !== $current_type_id ) {

				/*
				 * This occurs when there is a renewal and the Membership Type
				 * is changed during the renewal process.
				 *
				 * We need to remove the assigned Capability or Role because
				 * there is no remaining record of the previous Membership that
				 * will be acted on when rule_apply() is called with the true
				 * list of Memberships following this renewal check.
				 */

				// Assign Membership for processing below.
				$previous_membership = $this->membership_pre;

			}

		}

		// If there is an applicable rule for the previous Membership?
		if ( $this->plugin->admin->rule_exists( $previous_membership ) ) {

			// Get WordPress User for this Contact ID.
			$user = $this->plugin->users->wp_user_get_by_civi_id( $objectRef->contact_id );

			// If we don't receive a valid User.
			if ( ! ( $user instanceof WP_User ) ) {

				// Create WordPress User and prepare for sync.
				$user = $this->user_prepare_for_sync( $objectRef->contact_id );

				// Bail if something went wrong.
				if ( $user === false ) {
					return;
				}
				if ( ! ( $user instanceof WP_User ) || ! $user->exists() ) {
					return;
				}

			}

			// Bail if this User should not be synced.
			if ( ! $this->user_should_be_synced( $user ) ) {
				return;
			}

			// Cast Membership as object for processing by rule_undo().
			$membership_data = (object) $previous_membership['values'][0];

			// Update WordPress User as if the Membership has been deleted.
			$this->plugin->admin->rule_undo( $user, $membership_data );

		}

		// Get all the Memberships for this Contact.
		$memberships = $this->membership_get_by_contact_id( $objectRef->contact_id );

		// Bail if there are no applicable rules for these Memberships.
		if ( ! $this->plugin->admin->rule_exists( $memberships ) ) {
			return;
		}

		// If we didn't get a WordPress User previously.
		if ( ! isset( $user ) ) {

			// Get WordPress User for this Contact ID.
			$user = $this->plugin->users->wp_user_get_by_civi_id( $objectRef->contact_id );

			// If we don't receive a valid User.
			if ( ! ( $user instanceof WP_User ) ) {

				// Create WordPress User and prepare for sync.
				$user = $this->user_prepare_for_sync( $objectRef->contact_id );

				// Bail if something went wrong.
				if ( $user === false ) {
					return;
				}
				if ( ! ( $user instanceof WP_User ) || ! $user->exists() ) {
					return;
				}

			}

			// Bail if this User should not be synced.
			if ( ! $this->user_should_be_synced( $user ) ) {
				return;
			}

		}

		// Update WordPress User.
		$this->plugin->admin->rule_apply( $user, $memberships );

		/**
		 * Broadcast the Membership update.
		 *
		 * @since 0.3.4
		 *
		 * @param str $op The type of operation.
		 * @param WP_User $user The WordPress User object.
		 * @param object $objectRef The CiviCRM Membership being updated.
		 * @param object $previous_membership The previous CiviCRM Membership if this is a renewal.
		 */
		do_action( 'civi_wp_member_sync_membership_updated', $op, $user, $objectRef, $previous_membership );

	}

	/**
	 * Update a WordPress User when a CiviCRM Membership is deleted.
	 *
	 * @since 0.3
	 *
	 * @param string $op the type of database operation.
	 * @param string $objectName the type of object.
	 * @param integer $objectId the ID of the object.
	 * @param object $objectRef the object.
	 */
	public function membership_deleted( $op, $objectName, $objectId, $objectRef ) {

		// Target our object type.
		if ( $objectName != 'Membership' ) {
			return;
		}

		// Kick out if we don't have a Contact ID.
		if ( ! isset( $objectRef->contact_id ) ) {
			return;
		}

		// Only process delete operations.
		if ( $op != 'delete' ) {
			return;
		}

		// Get WordPress User for this Contact ID.
		$user = $this->plugin->users->wp_user_get_by_civi_id( $objectRef->contact_id );

		// Bail if we don't receive a valid User.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Should this User be synced?
		if ( ! $this->user_should_be_synced( $user ) ) {
			return;
		}

		// Undo WordPress User's Membership.
		$this->plugin->admin->rule_undo( $user, $objectRef );

		/**
		 * Broadcast that a Membership has been deleted.
		 *
		 * @since 0.5.1
		 *
		 * @param WP_User $user The WordPress User object.
		 * @param object $objectRef The CiviCRM Membership being deleted.
		 */
		do_action( 'civi_wp_member_sync_membership_deleted', $user, $objectRef );

	}

	/**
	 * Update a WordPress User Role when a CiviCRM Membership is added.
	 *
	 * @since 0.1
	 *
	 * @param string $formName the CiviCRM form name.
	 * @param object $form the CiviCRM form object.
	 */
	public function membership_form_process( $formName, &$form ) {

		// Kick out if not Membership form.
		if ( ! ( $form instanceof CRM_Member_Form_Membership ) ) {
			return;
		}

	}

	/**
	 * Get Membership records.
	 *
	 * This method is called with a few key params. It's main purpose is to
	 * collect API calls to one place for easier debugging.
	 *
	 * The API call has been refined to get the Memberships ordered by end date.
	 * The reason for this is that Civi_WP_Member_Sync_Admin::rule_apply() has
	 * an implicit expectation of Membership sequence because subsequent checks
	 * override those that come before. For further info, refer to the docblock
	 * for rule_apply(). This has been further refined to sort the returned data
	 * such that current Memberships come at the end of the array. Props @axaak.
	 *
	 * @since 0.1
	 *
	 * @param int $offset The numerical offset to apply.
	 * @param int $limit The numerical limit to apply.
	 * @param int $contact_id The numerical CiviCRM Contact ID.
	 * @param int $type_id The numerical CiviCRM Membership Type ID.
	 * @param int $status_id The numerical CiviCRM Membership Status ID.
	 * @return bool|array $data CiviCRM formatted Membership data or false on failure.
	 */
	public function memberships_get( $offset = 0, $limit = 0, $contact_id = 0, $type_id = 0, $status_id = 0 ) {

		// Init return as boolean.
		$data = false;

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return $data;
		}

		// Configure API query params.
		$params = [
			'version' => '3',
			'sequential' => 1,
			'status_id.is_current_member' => [
				'IS NOT NULL' => 1,
			],
			'options' => [
				'sort' => 'contact_id ASC, status_id.is_current_member ASC, end_date ASC',
			],
			'return' => [
				'id',
				'contact_id',
				'membership_type_id',
				'join_date',
				'start_date',
				'end_date',
				'source',
				'status_id',
				'is_test',
				'is_pay_later',
				'status_id.is_current_member',
			],
		];

		// Add offset if supplied.
		if ( $offset !== 0 ) {
			$params['options']['offset'] = $offset;
		}

		// Always add limit.
		$params['options']['limit'] = $limit;

		// Amend params using Contact ID if supplied.
		if ( $contact_id !== 0 ) {
			$params['contact_id'] = $contact_id;
			$params['options']['sort'] = 'status_id.is_current_member ASC, end_date ASC';
		}

		// Amend params using Membership Type ID if supplied.
		if ( $type_id !== 0 ) {
			$params['membership_type_id'] = $type_id;
		}

		// Amend params using Membership Status ID if supplied.
		if ( $status_id !== 0 ) {
			$params['status_id'] = $status_id;
		}

		// Get details of CiviCRM Memberships.
		$result = civicrm_api( 'Membership', 'get', $params );

		// Log and bail on error.
		if ( ! empty( $result['is_error'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $data;
		}

		// Override default if we have Memberships.
		if ( ! empty( $result['values'] ) ) {
			$data = $result;
		}

		/**
		 * Allow Membership data to be filtered.
		 *
		 * Use this filter to amend Membership data for a CiviCRM Contact.
		 *
		 * It is used within this plugin itself to non-destructively override
		 * the Status of Memberships where the Contact has been "soft deleted".
		 *
		 * @since 0.4.1
		 *
		 * @param bool|array $data The array of Membership data returned by the CiviCRM API.
		 * @param array $params The params used to query the CiviCRM API.
		 * @param int|array $contact_id The query params for the CiviCRM Contact ID.
		 * @return bool|array $data The array of Membership data returned by the CiviCRM API.
		 */
		$data = apply_filters( 'civi_wp_member_sync_memberships_get', $data, $params, $contact_id );

		// --<
		return $data;

	}

	/**
	 * Filter Membership data where a Contact is in the Trash.
	 *
	 * @since 0.4.1
	 *
	 * @param bool|array $data The existing array of Membership data returned by the CiviCRM API.
	 * @param array $params The params used to query the CiviCRM API.
	 * @param int|array $contact_id The query params for the CiviCRM Contact ID.
	 * @return bool|array $data The modified array of Membership data returned by the CiviCRM API.
	 */
	public function membership_override( $data, $params, $contact_id ) {

		// Sanity checks.
		if ( $data === false ) {
			return $data;
		}

		// Build query.
		$query = [
			'version' => 3,
			'sequential' => 1,
			'is_deleted' => 1,
			'options' => [
				'limit' => 0,
			],
		];

		// Only search Contacts if specified.
		if ( ! empty( $contact_id ) ) {
			$query['id'] = $contact_id;
		}

		// Get data assuming Contact(s) is/are in the Trash.
		$result = civicrm_api( 'Contact', 'get', $query );

		// Log and bail on error.
		if ( ! empty( $result['is_error'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'contact_id' => $contact_id,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $data;
		}

		// Bail quietly when there are no results.
		if ( empty( $result['values'] ) ) {
			return $data;
		}

		// Extract the Contact IDs.
		$contact_ids = wp_list_pluck( $result['values'], 'contact_id' );

		// Override Membership Statuses.
		$overrides = [];
		foreach ( $data['values'] as $membership ) {

			// Skip checks if this Membership doesn't refer to a Contact in Trash.
			if ( ! in_array( $membership['contact_id'], $contact_ids ) ) {
				$overrides[] = $membership;
				continue;
			}

			// Check if Membership is already expired.
			$expired = $this->membership_is_expired( $membership );

			// Maybe overwrite with an expired status.
			if ( ! empty( $expired ) && $expired['is_expired'] === false ) {
				$membership['status_id'] = $expired['status_id'];
				$membership['status_id.is_current_member'] = 0;
			}

			// Always populate overrides.
			$overrides[] = $membership;

		}

		// Overwrite values array.
		$data['values'] = $overrides;

		// --<
		return $data;

	}

	/**
	 * Check if a Membership is expired.
	 *
	 * This returns an array in ALL cases, though it will be an EMPTY array if
	 * an error is encountered. When there is no error, the array will contain
	 * an expired Status ID and a boolean corresponding to whether or no the
	 * Membership is expired.
	 *
	 * @since 0.4.1
	 *
	 * @param array $membership The CiviCRM Membership data.
	 * @return array $expired Array containing an expired Status ID and an expired boolean.
	 */
	public function membership_is_expired( $membership ) {

		// Init return as empty.
		$expired = [];

		// Get sync method.
		$method = $this->plugin->admin->setting_get_method();

		// Bail if something went wrong.
		if ( ! isset( $membership['membership_type_id'] ) ) {
			return $expired;
		}
		if ( ! isset( $membership['status_id'] ) ) {
			return $expired;
		}

		// Get Membership Type and status rule.
		$membership_type_id = $membership['membership_type_id'];
		$status_id = $membership['status_id'];

		// Get association rule for this Membership Type.
		$association_rule = $this->plugin->admin->rule_get_by_type( $membership_type_id, $method );
		if ( $association_rule === false ) {
			return $expired;
		}

		// Get status rules.
		$current_rule = $association_rule['current_rule'];
		$expiry_rule = $association_rule['expiry_rule'];

		// Always add an expired Status ID.
		$expired['status_id'] = array_shift( $expiry_rule );

		// Does the Membership Status match a current status rule?
		if ( array_search( $status_id, $current_rule ) ) {
			$expired['is_expired'] = false;
		} else {
			$expired['is_expired'] = true;
		}

		// --<
		return $expired;

	}

	/**
	 * Get Membership records by CiviCRM Contact ID.
	 *
	 * @since 0.1
	 *
	 * @param int $civi_contact_id The numerical CiviCRM Contact ID.
	 * @return array $membership CiviCRM formatted Membership data.
	 */
	public function membership_get_by_contact_id( $civi_contact_id ) {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return false;
		}

		// Pass to centralised method.
		$offset = 0;
		$limit = 0;
		return $this->memberships_get( $offset, $limit, $civi_contact_id );

	}

	/**
	 * Get name of CiviCRM Membership Type by ID.
	 *
	 * @since 0.1
	 *
	 * @param int $type_id the numeric ID of the Membership Type.
	 * @return string $name The name of the Membership Type.
	 */
	public function membership_name_get_by_id( $type_id = 0 ) {

		// Sanity checks.
		if ( ! is_numeric( $type_id ) ) {
			return false;
		}
		if ( $type_id === 0 ) {
			return false;
		}

		// Init return.
		$name = '';

		// Get Membership Types.
		$membership_types = $this->types_get_all();
		if ( ! is_array( $membership_types ) ) {
			return false;
		}
		if ( count( $membership_types ) == 0 ) {
			return false;
		}

		// Flip for easier searching.
		$membership_types = array_flip( $membership_types );

		// Init current Roles.
		$name = array_search( $type_id, $membership_types );

		// --<
		return $name;

	}

	/**
	 * Filter the Memberships to return only those for which a rule exists.
	 *
	 * @since 0.3.7
	 *
	 * @param array $memberships The Memberships to inspect.
	 * @return array $filtered The Memberships with a rule.
	 */
	public function memberships_filter( $memberships ) {

		// Init filtered array.
		$filtered = [
			'is_error' => 0,
			'count' => 0,
			'version' => 3,
			'values' => [],
		];

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return $filtered;
		}

		// Bail if we didn't get Memberships passed.
		if ( $memberships === false ) {
			return $filtered;
		}
		if ( empty( $memberships ) ) {
			return $filtered;
		}

		// Get sync method.
		$method = $this->plugin->admin->setting_get_method();

		// Loop through the supplied Memberships.
		foreach ( $memberships['values'] as $membership ) {

			// Continue with next Membership if something went wrong.
			if ( empty( $membership['membership_type_id'] ) ) {
				continue;
			}
			if ( ! isset( $membership['status_id'] ) ) {
				continue;
			}

			// Get Membership Type and status rule.
			$membership_type_id = $membership['membership_type_id'];
			$status_id = $membership['status_id'];

			// Get association rule for this Membership Type.
			$association_rule = $this->plugin->admin->rule_get_by_type( $membership_type_id, $method );

			// Continue with next Membership if we have an error or no rule exists.
			if ( $association_rule === false ) {
				continue;
			}

			// Continue with next Membership if something is wrong with rule.
			if ( empty( $association_rule['current_rule'] ) ) {
				continue;
			}
			if ( empty( $association_rule['expiry_rule'] ) ) {
				continue;
			}

			// Get status rules.
			$current_rule = $association_rule['current_rule'];
			$expiry_rule = $association_rule['expiry_rule'];

			// Which sync method are we using?
			if ( $method == 'roles' ) {

				// Continue with next Membership if something is wrong with rule.
				if ( empty( $association_rule['current_wp_role'] ) ) {
					continue;
				}
				if ( empty( $association_rule['expired_wp_role'] ) ) {
					continue;
				}

				// Make a copy, just to be safe.
				$filter = $membership;

				// Does the User's Membership Status match a current status rule?
				if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {
					$filter['member_sync'] = 'current';
				} else {
					$filter['member_sync'] = 'expired';
				}

				// Rule applies.
				$filtered['values'][] = $filter;

			} else {

				// Make a copy, just to be safe.
				$filter = $membership;

				// Does the User's Membership Status match a current status rule?
				if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {
					$filter['member_sync'] = 'current';
				} else {
					$filter['member_sync'] = 'expired';
				}

				// Rule applies.
				$filtered['values'][] = $filter;

			}

		}

		// Update count.
		$filtered['count'] = count( $filtered['values'] );

		// --<
		return $filtered;

	}

	/**
	 * Retrieve the number of Memberships.
	 *
	 * @since 0.2.8
	 *
	 * @return int $membership_count The number of Memberships.
	 */
	public function memberships_get_count() {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return 0;
		}

		// Get all CiviCRM Memberships.
		$membership_count = civicrm_api( 'Membership', 'getcount', [
			'version' => '3',
			'options' => [
				'limit' => '0',
			],
		] );

		// Sanity check in case of error.
		if ( ! is_numeric( $membership_count ) ) {
			$membership_count = 0;
		}

		// --<
		return $membership_count;

	}

	/**
	 * Get Membership Types.
	 *
	 * @since 0.1
	 *
	 * @return array $membership_type List of types, key is ID, value is name.
	 */
	public function types_get_all() {

		// Only calculate once.
		if ( isset( $this->membership_types ) ) {
			return $this->membership_types;
		}

		// Init return.
		$this->membership_types = [];

		// Return empty array if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return [];
		}

		// Get all Membership Type details.
		$membership_type_details = civicrm_api( 'MembershipType', 'get', [
			'version' => '3',
			'sequential' => '1',
			'options' => [
				'limit' => '0',
			],
		] );

		// Construct array of types.
		foreach ( $membership_type_details['values'] as $key => $values ) {
			$this->membership_types[ $values['id'] ] = $values['name'];
		}

		// --<
		return $this->membership_types;

	}

	/**
	 * Get Membership Status rules.
	 *
	 * @since 0.1
	 *
	 * @return array $membership_status List of status rules, key is ID, value is name.
	 */
	public function status_rules_get_all() {

		// Only calculate once.
		if ( isset( $this->membership_status_rules ) ) {
			return $this->membership_status_rules;
		}

		// Init return.
		$this->membership_status_rules = [];

		// Return empty array if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return [];
		}

		// Get all Membership Status details.
		$membership_status_details = civicrm_api( 'MembershipStatus', 'get', [
			'version' => '3',
			'sequential' => '1',
			'options' => [
				'limit' => '0',
			],
		] );

		// Construct array of status rules.
		foreach ( $membership_status_details['values'] as $key => $values ) {
			$this->membership_status_rules[ $values['id'] ] = $values['name'];
		}

		// --<
		return $this->membership_status_rules;

	}

	/**
	 * Get name of CiviCRM Membership Status by ID.
	 *
	 * @since 0.5
	 *
	 * @param int $status_id the numeric ID of the Membership Status.
	 * @return string|bool $name The name of the Membership Status, false if not found.
	 */
	public function status_name_get_by_id( $status_id = 0 ) {

		// Sanity checks.
		if ( ! is_numeric( $status_id ) ) {
			return false;
		}
		if ( $status_id === 0 ) {
			return false;
		}

		// Get Membership Statuses.
		$membership_statuses = $this->status_rules_get_all();
		if ( ! is_array( $membership_statuses ) ) {
			return false;
		}
		if ( count( $membership_statuses ) == 0 ) {
			return false;
		}

		// Flip for easier searching.
		$membership_statuses = array_flip( $membership_statuses );

		// Find the item (returns false if not found).
		$name = array_search( $status_id, $membership_statuses );

		// --<
		return $name;

	}

	/**
	 * Get Role/Membership names.
	 *
	 * @since 0.1
	 *
	 * @param string $values Serialised array of status rule IDs.
	 * @return string $status_rules The list of status rules, one per line.
	 */
	public function status_rules_get_current( $values ) {

		// Init return.
		$status_rules = '';

		// Get current rules for this item.
		$current_rules = $this->status_rules_get_current_array( $values );

		// If there are some.
		if ( $current_rules !== false && is_array( $current_rules ) ) {

			// Separate with line break.
			$status_rules = implode( '<br>', $current_rules );

		}

		// --<
		return $status_rules;

	}

	/**
	 * Get Membership Status rules for a particular item.
	 *
	 * @since 0.1
	 *
	 * @param string $values Serialised array of status rule IDs.
	 * @return array $rules_array The list of Membership Status rules for this item.
	 */
	public function status_rules_get_current_array( $values ) {

		// Get Membership Status rules.
		$status_rules = $this->status_rules_get_all();
		if ( ! is_array( $status_rules ) ) {
			return false;
		}
		if ( count( $status_rules ) == 0 ) {
			return false;
		}

		// Flip for easier searching.
		$status_rules = array_flip( $status_rules );

		// Init return.
		$rules_array = [];

		// Init current rule.
		$current_rule = maybe_unserialize( $values );

		// Build rules array for this item.
		if ( ! empty( $current_rule ) ) {
			if ( is_array( $current_rule ) ) {
				foreach ( $current_rule as $key => $value ) {
					$rules_array[] = array_search( $key, $status_rules );
				}
			}
		}

		// --<
		return $rules_array;

	}

}
