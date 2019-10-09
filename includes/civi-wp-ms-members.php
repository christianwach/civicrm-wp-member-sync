<?php

/**
 * CiviCRM WordPress Member Sync Membership class.
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

		// Store reference to plugin
		$this->plugin = $plugin;

		// Initialise first.
		add_action( 'civi_wp_member_sync_initialised', array( $this, 'initialise' ), 7 );

	}



	//##########################################################################



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
			add_action( 'wp_login', array( $this, 'sync_to_user' ), 10, 2 );

			// Add logout check (can't use 'wp_logout' action, as user no longer exists).
			add_action( 'clear_auth_cookie', array( $this, 'sync_on_logout' ) );

		}

		// Get our CiviCRM sync setting.
		$civicrm = absint( $this->plugin->admin->setting_get( 'civicrm' ) );

		// Add hooks if set.
		if ( $civicrm === 1 ) {

			// Intercept CiviCRM membership add/edit form submission.
			add_action( 'civicrm_postProcess', array( $this, 'membership_form_process' ), 10, 2 );

			// Intercept before a CiviCRM membership update.
			add_action( 'civicrm_pre', array( $this, 'membership_pre_update' ), 10, 4 );

			// Intercept a CiviCRM membership update.
			add_action( 'civicrm_post', array( $this, 'membership_updated' ), 10, 4 );

			// Intercept a CiviCRM membership deletion.
			add_action( 'civicrm_post', array( $this, 'membership_deleted' ), 10, 4 );

		}

		// Is this the back end?
		if ( is_admin() ) {

			// Add AJAX handler.
			add_action( 'wp_ajax_sync_memberships', array( $this, 'sync_all_civicrm_memberships' ) );

		}

		// Filter memberships and override for Contact in Trash.
		add_filter( 'civi_wp_member_sync_memberships_get', array( $this, 'membership_override' ), 10, 3 );

	}



	//##########################################################################



	/**
	 * Sync membership rules for all CiviCRM Memberships.
	 *
	 * @since 0.2.8
	 */
	public function sync_all_civicrm_memberships() {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return;

		// Init AJAX return.
		$data = array();

		// Get batch count.
		$batch_count = $this->plugin->admin->setting_get_batch_count();

		// Assume not creating users.
		$create_users = false;

		// Override "create users" flag if chosen.
		if (
			isset( $_POST['civi_wp_member_sync_manual_sync_create'] ) AND
			$_POST['civi_wp_member_sync_manual_sync_create'] == 'y'
		) {
			$create_users = true;
		}

		// If the memberships offset value doesn't exist.
		if ( 'fgffgs' == get_option( '_civi_wpms_memberships_offset', 'fgffgs' ) ) {

			// Start at the beginning.
			$memberships_offset = 0;
			add_option( '_civi_wpms_memberships_offset', '0' );

		} else {

			// Use the existing value.
			$memberships_offset = intval( get_option( '_civi_wpms_memberships_offset', '0' ) );

		}

		// Get CiviCRM memberships.
		$memberships = $this->memberships_get( $memberships_offset, $batch_count );

		// If we have membership details.
		if ( $memberships !== false ) {

			// Set finished flag.
			$data['finished'] = 'false';

			// Set from and to flags.
			$data['from'] = intval( $memberships_offset );
			$data['to'] = $data['from'] + $batch_count;

			// Init processed array.
			$processed = array();

			// Loop through memberships.
			foreach( $memberships['values'] AS $membership ) {

				// Get contact ID.
				$civi_contact_id = isset( $membership['contact_id'] ) ? $membership['contact_id'] : false;

				// Sanity check.
				if ( $civi_contact_id === false ) continue;

				/*
				 * The processed array is a bit of a hack:
				 *
				 * - the array is lost each time a batch finishes
				 * - there may be duplicate contact IDs across batches
				 * - it means that the sync progress isn't entirely truthful
				 *
				 * However, when there are lots of memberships per contact (as
				 * some folks like to keep historical records of memberships
				 * by creating new ones instead of renewing existing ones) then
				 * this should save a fair but of processing.
				 */

				// Continue if we've already processed this contact.
				if ( in_array( $civi_contact_id, $processed ) ) continue;

				// Add contact ID to processed so we don't re-process.
				$processed[] = $civi_contact_id;

				/*
				 * The use of existing code here is not the most efficient way to
				 * sync each membership. However, given that in most cases there
				 * will only be one membership per contact, I think the overhead
				 * will be minimal. Moreover, this new chunked sync method limits
				 * the impact of a manual sync per request.
				 */

				// Get *all* memberships for this contact.
				$all_memberships = $this->membership_get_by_contact_id( $civi_contact_id );

				// Continue if there are no applicable rules for these memberships.
				if ( ! $this->plugin->admin->rule_exists( $all_memberships ) ) continue;

				// Get WordPress user.
				$user = $this->plugin->users->wp_user_get_by_civi_id( $civi_contact_id );

				// If we don't have a valid user.
				if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) {

					// Create a WordPress user if asked to.
					if ( $create_users ) {

						// Create WordPress user and prepare for sync.
						$user = $this->user_prepare_for_sync( $civi_contact_id );

						// Skip to next if something went wrong.
						if ( $user === false ) continue;
						if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) continue;

					}

				}

				// Should this user be synced?
				if ( ! $this->user_should_be_synced( $user ) ) continue;

				// Apply rules for this WordPress user.
				$this->plugin->admin->rule_apply( $user, $all_memberships );

			}

			// Increment memberships offset option.
			update_option( '_civi_wpms_memberships_offset', (string) $data['to'] );

		} else {

			// Delete the option to start from the beginning.
			delete_option( '_civi_wpms_memberships_offset' );

			// Set finished flag.
			$data['finished'] = 'true';

		}

		// Is this an AJAX request?
		if ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) {

			// Set reasonable headers.
			header( 'Content-type: text/plain' );
			header( "Cache-Control: no-cache" );
			header( "Expires: -1" );

			// Echo.
			echo json_encode( $data );

			// Die.
			exit();

		}

	}



	/**
	 * Sync all membership rules for existing WordPress users.
	 *
	 * @since 0.1
	 *
	 * @return bool $success True if successful, false otherwise.
	 */
	public function sync_all_wp_user_memberships() {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return;

		// Make sure CiviCRM file is included.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Get all WordPress users.
		$users = get_users( array( 'all_with_meta' => true ) );

		// Loop through all users.
		foreach( $users AS $user ) {

			// Skip if we don't have a valid user.
			if ( ! ( $user instanceof WP_User ) ) { continue; }
			if ( ! $user->exists() ) { continue; }

			// Call login method.
			$this->sync_to_user( $user->user_login, $user );

		}

	}



	/**
	 * Check user's membership record during logout.
	 *
	 * @since 0.1
	 */
	public function sync_on_logout() {

		// Get user.
		$user = wp_get_current_user();
		$user_login = $user->user_login;

		// Call login method.
		$this->sync_to_user( $user_login, $user );

	}



	/**
	 * Maybe create a WordPress user.
	 *
	 * @since 0.3.4
	 *
	 * @param int $civi_contact_id The numeric ID of the CiviCRM contact.
	 * @return WP_User|bool $user The WordPress user object - or false on failure.
	 */
	public function user_prepare_for_sync( $civi_contact_id ) {

		// Maybe create WordPress user.
		$user = $this->plugin->users->wp_user_create_from_contact_id( $civi_contact_id );

		// Bail if something goes wrong.
		if ( $user === false ) return false;
		if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) return false;

		// Get sync method.
		$method = $this->plugin->admin->setting_get_method();

		// When syncing roles, remove the default role from the new user because
		// rule_apply() will set a role when it runs.
		if ( $method == 'roles' ) {
			$user->remove_role( get_option( 'default_role' ) );
		}

		// --<
		return $user;

	}



	/**
	 * Check if a user's membership should by synced.
	 *
	 * @since 0.2.6
	 *
	 * @param object $user The WordPress user object.
	 * @return bool $should_be_synced Whether or not the user should be synced.
	 */
	public function user_should_be_synced( $user ) {

		// Kick out if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) return false;
		if ( ! $user->exists() ) return false;

		// Assume user should be synced.
		$should_be_synced = true;

		// Exclude admins by default.
		if ( is_super_admin( $user->ID ) OR $user->has_cap( 'delete_users' ) ) {
			$should_be_synced = false;
		}

		/**
		 * Let other plugins override whether a user should be synced.
		 *
		 * @since 0.2
		 *
		 * @param bool $should_be_synced True if the user should be synced, false otherwise.
		 * @param object $user The WordPress user object.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		return apply_filters( 'civi_wp_member_sync_user_should_be_synced', $should_be_synced, $user );

	}



	/**
	 * Sync a user's role based on their membership record.
	 *
	 * @since 0.1
	 *
	 * @param string $user_login Logged in user's username.
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public function sync_to_user( $user_login, $user ) {

		// Should this user be synced?
		if ( ! $this->user_should_be_synced( $user ) ) return;

		// Get CiviCRM contact ID.
		$civi_contact_id = $this->plugin->users->civi_contact_id_get( $user );

		// Bail if we don't have one.
		if ( $civi_contact_id === false ) return;

		// Get memberships.
		$memberships = $this->membership_get_by_contact_id( $civi_contact_id );

		// Bail if there are no applicable rules for these memberships.
		if ( ! $this->plugin->admin->rule_exists( $memberships ) ) return;

		// Update WordPress user.
		$this->plugin->admin->rule_apply( $user, $memberships );

	}



	/**
	 * Inspect a CiviCRM membership prior to it being updated.
	 *
	 * Membership renewals may change the type of membership with no hint of the
	 * change in the data that is passed to "hook_civicrm_post". In order to see
	 * if a change of membership type has occurred, we need to retrieve the
	 * membership here before the operation and compare afterwards in the
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
		if ( $objectName != 'Membership' ) return;

		// Only process edit operations.
		if ( $op != 'edit' ) return;

		// Get details of CiviCRM membership.
		$membership = civicrm_api( 'Membership', 'get', array(
			'version' => '3',
			'sequential' => 1,
			'id' => $objectId,
		));

		// Sanity check.
		if (
			$membership['is_error'] == 0 AND
			isset( $membership['values'] ) AND
			count( $membership['values'] ) > 0
		) {

			// Store in property for later inspection.
			$this->membership_pre = $membership;

		}

	}



	/**
	 * Update a WordPress user when a CiviCRM membership is updated.
	 *
	 * As noted by @axaak, this method should not blindly apply the rule for the
	 * edited membership because it is possible that a contact has multiple
	 * memberships and the one being edited may be a historical record.
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
		if ( $objectName != 'Membership' ) return;

		// Kick out if we don't have a contact ID.
		if ( ! isset( $objectRef->contact_id ) ) return;

		// Only process create and edit operations.
		if ( ! in_array( $op, array( 'create', 'edit' ) ) ) return;

		// Init previous membership.
		$previous_membership = null;

		// For edit operations, we first need to check for renewals.
		if ( $op == 'edit' AND isset( $this->membership_pre ) AND isset( $objectRef->membership_type_id ) ) {

			// Make sure we're comparing like with like.
			$previous_type_id = absint( $this->membership_pre['values'][0]['membership_type_id'] );
			$current_type_id = absint( $objectRef->membership_type_id );

			// Do we have different CiviCRM membership types?
			if ( $previous_type_id !== $current_type_id ) {

				/*
				 * This occurs when there is a renewal and the membership type
				 * is changed during the renewal process.
				 *
				 * We need to remove the assigned capability or role because
				 * there is no remaining record of the previous membership that
				 * will be acted on when rule_apply() is called with the true
				 * list of memberships following this renewal check.
				 */

				// Assign membership for processing below.
				$previous_membership = $this->membership_pre;

			}

		}

		// If there is an applicable rule for the previous membership?
		if ( $this->plugin->admin->rule_exists( $previous_membership ) ) {

			// Get WordPress user for this contact ID.
			$user = $this->plugin->users->wp_user_get_by_civi_id( $objectRef->contact_id );

			// If we don't receive a valid user.
			if ( ! ( $user instanceof WP_User ) ) {

				// Create WordPress user and prepare for sync.
				$user = $this->user_prepare_for_sync( $objectRef->contact_id );

				// Bail if something went wrong.
				if ( $user === false ) return;
				if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) return;

			}

			// Bail if this user should not be synced.
			if ( ! $this->user_should_be_synced( $user ) ) return;

			// Cast membership as object for processing by rule_undo().
			$membership_data = (object) $previous_membership['values'][0];

			// Update WordPress user as if the membership has been deleted.
			$this->plugin->admin->rule_undo( $user, $membership_data );

		}

		// Get all the memberships for this contact.
		$memberships = $this->membership_get_by_contact_id( $objectRef->contact_id );

		// Bail if there are no applicable rules for these memberships.
		if ( ! $this->plugin->admin->rule_exists( $memberships ) ) return;

		// If we didn't get a WordPress user previously.
		if ( ! isset( $user ) ) {

			// Get WordPress user for this contact ID.
			$user = $this->plugin->users->wp_user_get_by_civi_id( $objectRef->contact_id );

			// If we don't receive a valid user.
			if ( ! ( $user instanceof WP_User ) ) {

				// Create WordPress user and prepare for sync.
				$user = $this->user_prepare_for_sync( $objectRef->contact_id );

				// Bail if something went wrong.
				if ( $user === false ) return;
				if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) return;

			}

			// Bail if this user should not be synced.
			if ( ! $this->user_should_be_synced( $user ) ) return;

		}

		// Update WordPress user.
		$this->plugin->admin->rule_apply( $user, $memberships );

		/**
		 * Broadcast the membership update.
		 *
		 * @since 0.3.4
		 *
		 * @param str $op The type of operation.
		 * @param WP_User $user The WordPress user object.
		 * @param object $objectRef The CiviCRM membership being updated.
		 * @param object $previous_membership The previous CiviCRM membership if this is a renewal.
		 */
		do_action( 'civi_wp_member_sync_membership_updated', $op, $user, $objectRef, $previous_membership );

	}



	/**
	 * Update a WordPress user when a CiviCRM membership is deleted.
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
		if ( $objectName != 'Membership' ) return;

		// Kick out if we don't have a contact ID.
		if ( ! isset( $objectRef->contact_id ) ) return;

		// Only process delete operations.
		if ( $op != 'delete' ) return;

		// Get WordPress user for this contact ID.
		$user = $this->plugin->users->wp_user_get_by_civi_id( $objectRef->contact_id );

		// Bail if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) return;

		// Should this user be synced?
		if ( ! $this->user_should_be_synced( $user ) ) return;

		// Undo WordPress user's membership.
		$this->plugin->admin->rule_undo( $user, $objectRef );

	}



	/**
	 * Update a WordPress user role when a CiviCRM membership is added.
	 *
	 * @since 0.1
	 *
	 * @param string $formName the CiviCRM form name.
	 * @param object $form the CiviCRM form object.
	 */
	public function membership_form_process( $formName, &$form ) {

		// Kick out if not membership form.
		if ( ! ( $form instanceof CRM_Member_Form_Membership ) ) return;

	}



	/**
	 * Get membership records.
	 *
	 * This method is called with a few key params. It's main purpose is to
	 * collect API calls to one place for easier debugging.
	 *
	 * The API call has been refined to get the Memberships ordered by end date.
	 * The reason for this is that Civi_WP_Member_Sync_Admin::rule_apply() has
	 * an implicit expectation of membership sequence because subsequent checks
	 * override those that come before. For further info, refer to the docblock
	 * for rule_apply(). This has been further refined to sort the returned data
	 * such that current memberships come at the end of the array. Props @axaak.
	 *
	 * @since 0.1
	 *
	 * @param int $offset The numerical offset to apply.
	 * @param int $limit The numerical limit to apply.
	 * @param int $contact_id The numerical CiviCRM contact ID.
	 * @return bool|array $data CiviCRM formatted membership data or false on failure.
	 */
	public function memberships_get( $offset = 0, $limit = 0, $contact_id = 0 ) {

		// Init return as boolean.
		$data = false;

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return $data;

		// Configure API query params.
		$params = array(
			'version' => '3',
			'sequential' => 1,
			'status_id.is_current_member' => array(
				'IS NOT NULL' => 1
			),
			'options' => array(
				'sort' => 'contact_id ASC, status_id.is_current_member ASC, end_date ASC',
			),
			'return' => array(
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
				'status_id.is_current_member'
			),
		);

		// Add offset if supplied.
		if ( $offset !== 0 ) {
			$params['options']['offset'] = $offset;
		}

		// Always add limit.
		$params['options']['limit'] = $limit;

		// Amend params using contact ID if supplied.
		if ( $contact_id !== 0 ) {
			$params['contact_id'] = $contact_id;
			$params['options']['sort'] = 'status_id.is_current_member ASC, end_date ASC';
		}

		// Get details of CiviCRM memberships.
		$memberships = civicrm_api( 'Membership', 'get', $params );

		// If we have membership details.
		if (
			$memberships['is_error'] == 0 AND
			isset( $memberships['values'] ) AND
			count( $memberships['values'] ) > 0
		) {

			// Override default with CiviCRM API data.
			$data = $memberships;

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
		 * @param int $contact_id The numerical CiviCRM Contact ID.
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
	 * @param int $contact_id The numerical CiviCRM Contact ID.
	 * @return bool|array $data The modified array of Membership data returned by the CiviCRM API.
	 */
	public function membership_override( $data, $params, $contact_id ) {

		// Sanity checks.
		if ( $data === false ) return $data;
		if ( $contact_id === 0 ) return $data;

		// Get data assuming Contact is in the Trash.
		$result = civicrm_api( 'Contact', 'get', array(
			'version' => 3,
			'sequential' => 1,
			'id' => $contact_id,
			'is_deleted' => 1,
		));

		// If Contact is in the Trash.
		if (
			$result['is_error'] == 0 AND
			isset( $result['values'] ) AND
			count( $result['values'] ) > 0
		) {

			// Override Membership Statuses.
			$overrides = array();
			foreach( $data['values'] AS $membership ) {

				// Check if Membership is already expired.
				$expired = $this->membership_is_expired( $membership );

				// Maybe overwrite with an expired status.
				if ( ! empty( $expired ) AND $expired['is_expired'] === false ) {
					$membership['status_id'] = $expired['status_id'];
					$membership['status_id.is_current_member'] = 0;
				}

				// Always populate overrides.
				$overrides[] = $membership;

			}

			// Overwrite values array.
			$data['values'] = $overrides;

		}

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
		$expired = array();

		// Get sync method.
		$method = $this->plugin->admin->setting_get_method();

		// Bail if something went wrong.
		if ( ! isset( $membership['membership_type_id'] ) ) return $expired;
		if ( ! isset( $membership['status_id'] ) ) return $expired;

		// Get membership type and status rule.
		$membership_type_id = $membership['membership_type_id'];
		$status_id = $membership['status_id'];

		// Get association rule for this membership type.
		$association_rule = $this->plugin->admin->rule_get_by_type( $membership_type_id, $method );

		// Bail if we have an error of some kind.
		if ( $association_rule === false ) return $expired;

		// Get status rules.
		$current_rule = $association_rule['current_rule'];
		$expiry_rule = $association_rule['expiry_rule'];

		// Always add an expired Status ID.
		$expired['status_id'] = array_pop( $expiry_rule );

		// Does the membership status match a current status rule?
		if ( array_search( $status_id, $current_rule ) ) {
			$expired['is_expired'] = false;
		} else {
			$expired['is_expired'] = true;
		}

		// --<
		return $expired;

	}

	/**
	 * Get membership records by CiviCRM contact ID.
	 *
	 * @since 0.1
	 *
	 * @param int $civi_contact_id The numerical CiviCRM contact ID.
	 * @return array $membership CiviCRM formatted membership data.
	 */
	public function membership_get_by_contact_id( $civi_contact_id ) {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return false;

		// Pass to centralised method.
		return $this->memberships_get( $offset = 0, $limit = 0, $civi_contact_id );

	}



	/**
	 * Get name of CiviCRM membership type by ID.
	 *
	 * @since 0.1
	 *
	 * @param int $type_id the numeric ID of the membership type.
	 * @return string $name The name of the membership type.
	 */
	public function membership_name_get_by_id( $type_id = 0 ) {

		// Sanity checks.
		if ( ! is_numeric( $type_id ) ) return false;
		if ( $type_id === 0 ) return false;

		// Init return.
		$name = '';

		// Get membership types.
		$membership_types = $this->types_get_all();

		// Sanity checks.
		if ( ! is_array( $membership_types ) ) return false;
		if ( count( $membership_types ) == 0 ) return false;

		// Flip for easier searching.
		$membership_types = array_flip( $membership_types );

		// Init current roles.
		$name = array_search( $type_id, $membership_types );

		// --<
		return $name;

	}



	/**
	 * Filter the Memberships to return only those for which a rule exists.
	 *
	 * @since 0.3.7
	 *
	 * @param array $memberships The memberships to inspect.
	 * @return array $filtered The memberships with a rule.
	 */
	public function memberships_filter( $memberships ) {

		// Init filtered array.
		$filtered = array(
			'is_error' => 0,
			'count' => 0,
			'version' => 3,
			'values' => array(),
		);

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return $filtered;

		// Bail if we didn't get memberships passed.
		if ( $memberships === false ) return $filtered;
		if ( empty( $memberships ) ) return $filtered;

		// Get sync method.
		$method = $this->plugin->admin->setting_get_method();

		// Loop through the supplied memberships.
		foreach( $memberships['values'] AS $membership ) {

			// Continue with next membership if something went wrong.
			if ( empty( $membership['membership_type_id'] ) ) continue;
			if ( ! isset( $membership['status_id'] ) ) continue;

			// Get membership type and status rule.
			$membership_type_id = $membership['membership_type_id'];
			$status_id = $membership['status_id'];

			// Get association rule for this membership type.
			$association_rule = $this->plugin->admin->rule_get_by_type( $membership_type_id, $method );

			// Continue with next membership if we have an error or no rule exists.
			if ( $association_rule === false ) continue;

			// Continue with next membership if something is wrong with rule.
			if ( empty( $association_rule['current_rule'] ) ) continue;
			if ( empty( $association_rule['expiry_rule'] ) ) continue;

			// Get status rules
			$current_rule = $association_rule['current_rule'];
			$expiry_rule = $association_rule['expiry_rule'];

			// Which sync method are we using?
			if ( $method == 'roles' ) {

				// Continue with next membership if something is wrong with rule.
				if ( empty( $association_rule['current_wp_role'] ) ) continue;
				if ( empty( $association_rule['expired_wp_role'] ) ) continue;

				// Make a copy, just to be safe.
				$filter = $membership;

				// Does the user's membership status match a current status rule?
				if ( isset( $status_id ) AND array_search( $status_id, $current_rule ) ) {
					$filter['member_sync'] = 'current';
				} else {
					$filter['member_sync'] = 'expired';
				}

				// Rule applies.
				$filtered['values'][] = $filter;

			} else {

				// Make a copy, just to be safe.
				$filter = $membership;

				// Does the user's membership status match a current status rule?
				if ( isset( $status_id ) AND array_search( $status_id, $current_rule ) ) {
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
	 * @return int $membership_count The number of memberships.
	 */
	public function memberships_get_count() {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return 0;

		// Get all CiviCRM memberships.
		$membership_count = civicrm_api( 'Membership', 'getcount', array(
			'version' => '3',
			'options' => array(
				'limit' => '0',
			),
		));

		// Sanity check in case of error.
		if ( ! is_numeric( $membership_count ) ) $membership_count = 0;

		// --<
		return $membership_count;

	}



	/**
	 * Get membership types.
	 *
	 * @since 0.1
	 *
	 * @return array $membership_type List of types, key is ID, value is name.
	 */
	public function types_get_all() {

		// Only calculate once.
		if ( isset( $this->membership_types ) ) { return $this->membership_types; }

		// Init return.
		$this->membership_types = array();

		// Return empty array if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return array();

		// Get all membership type details.
		$membership_type_details = civicrm_api( 'MembershipType', 'get', array(
			'version' => '3',
			'sequential' => '1',
			'options' => array(
				'limit' => '0',
			),
		));

		// Construct array of types.
		foreach( $membership_type_details['values'] AS $key => $values ) {
			$this->membership_types[$values['id']] = $values['name'];
		}

		// --<
		return $this->membership_types;

	}



	/**
	 * Get membership status rules.
	 *
	 * @since 0.1
	 *
	 * @return array $membership_status List of status rules, key is ID, value is name.
	 */
	public function status_rules_get_all() {

		// Only calculate once.
		if ( isset( $this->membership_status_rules ) ) { return $this->membership_status_rules; }

		// Init return.
		$this->membership_status_rules = array();

		// Return empty array if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return array();

		// Get all membership status details.
		$membership_status_details = civicrm_api( 'MembershipStatus', 'get', array(
			'version' => '3',
			'sequential' => '1',
			'options' => array(
				'limit' => '0',
			),
		));

		// Construct array of status rules.
		foreach( $membership_status_details['values'] AS $key => $values ) {
			$this->membership_status_rules[$values['id']] = $values['name'];
		}

		// --<
		return $this->membership_status_rules;

	}



	/**
	 * Get role/membership names.
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
		if ( $current_rules !== false AND is_array( $current_rules ) ) {

			// Separate with line break.
			$status_rules = implode( '<br>', $current_rules );

		}

		// --<
		return $status_rules;

	}



	/**
	 * Get membership status rules for a particular item.
	 *
	 * @since 0.1
	 *
	 * @param string $values Serialised array of status rule IDs.
	 * @return array $rules_array The list of membership status rules for this item.
	 */
	public function status_rules_get_current_array( $values ) {

		// Get membership status rules.
		$status_rules = $this->status_rules_get_all();

		// Sanity checks.
		if ( ! is_array( $status_rules ) ) return false;
		if ( count( $status_rules ) == 0 ) return false;

		// Flip for easier searching.
		$status_rules = array_flip( $status_rules );

		// Init return.
		$rules_array = array();

		// Init current rule.
		$current_rule = maybe_unserialize( $values );

		// Build rules array for this item.
		if ( ! empty( $current_rule ) ) {
			if ( is_array( $current_rule ) ) {
				foreach( $current_rule as $key => $value ) {
					$rules_array[] = array_search( $key, $status_rules );
				}
			}
		}

		// --<
		return $rules_array;

	}



} // Class ends.



