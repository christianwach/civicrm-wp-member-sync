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
	 * Initialise this object.
	 *
	 * @since 0.1
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// store reference to plugin
		$this->plugin = $plugin;

	}



	//##########################################################################



	/**
	 * Register hooks when CiviCRM initialises.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// get our login/logout sync setting
		$login = absint( $this->plugin->admin->setting_get( 'login' ) );

		// add hooks if set
		if ( $login === 1 ) {

			// add login check
			add_action( 'wp_login', array( $this, 'sync_to_user' ), 10, 2 );

			// add logout check (can't use 'wp_logout' action, as user no longer exists)
			add_action( 'clear_auth_cookie', array( $this, 'sync_on_logout' ) );

		}

		// get our CiviCRM sync setting
		$civicrm = absint( $this->plugin->admin->setting_get( 'civicrm' ) );

		// add hooks if set
		if ( $civicrm === 1 ) {

			// intercept CiviCRM membership add/edit form submission
			add_action( 'civicrm_postProcess', array( $this, 'membership_form_process' ), 10, 2 );

			// intercept before a CiviCRM membership update
			add_action( 'civicrm_pre', array( $this, 'membership_pre_update' ), 10, 4 );

			// intercept a CiviCRM membership update
			add_action( 'civicrm_post', array( $this, 'membership_updated' ), 10, 4 );

			// intercept a CiviCRM membership deletion
			add_action( 'civicrm_post', array( $this, 'membership_deleted' ), 10, 4 );

		}

		// is this the back end?
		if ( is_admin() ) {

			// add AJAX handler
			add_action( 'wp_ajax_sync_memberships', array( $this, 'sync_all_civicrm_memberships' ) );

		}

	}



	//##########################################################################



	/**
	 * Sync membership rules for all CiviCRM Memberships.
	 *
	 * @since 0.2.8
	 */
	public function sync_all_civicrm_memberships() {

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return;

		// init AJAX return
		$data = array();

		// assume not creating users
		$create_users = false;

		// override "create users" flag if chosen
		if (
			isset( $_POST['civi_wp_member_sync_manual_sync_create'] ) AND
			$_POST['civi_wp_member_sync_manual_sync_create'] == 'y'
		) {
			$create_users = true;
		}

		// if the memberships offset value doesn't exist
		if ( 'fgffgs' == get_option( '_civi_wpms_memberships_offset', 'fgffgs' ) ) {

			// start at the beginning
			$memberships_offset = 0;
			add_option( '_civi_wpms_memberships_offset', '0' );

		} else {

			// use the existing value
			$memberships_offset = intval( get_option( '_civi_wpms_memberships_offset', '0' ) );

		}

		// get CiviCRM memberships
		$memberships = civicrm_api( 'Membership', 'get', array(
			'version' => '3',
			'sequential' => '1',
			'status_id.is_current_member' => array(
				'IS NOT NULL' => 1
			),
			'options' => array(
				'limit' => '5',
				'offset' => $memberships_offset,
				'sort' => 'contact_id, status_id.is_current_member ASC, end_date',
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
		));

		// if we have membership details
		if (
			$memberships['is_error'] == 0 AND
			isset( $memberships['values'] ) AND
			count( $memberships['values'] ) > 0
		) {

			// set finished flag
			$data['finished'] = 'false';

			// set from and to flags
			$data['from'] = intval( $memberships_offset );
			$data['to'] = $data['from'] + 5;

			// loop through memberships
			foreach( $memberships['values'] AS $membership ) {

				// get contact ID
				$civi_contact_id = isset( $membership['contact_id'] ) ? $membership['contact_id'] : false;

				// sanity check
				if ( $civi_contact_id === false ) continue;

				/*
				 * The use of existing code here is not the most efficient way to
				 * sync each membership. However, given that in most cases there
				 * will only be one membership per contact, I think the overhead
				 * will be minimal. Moreover, this new chunked sync method limits
				 * the impact of a manual sync per request.
				 */

				// get *all* memberships for this contact
				$all_memberships = $this->membership_get_by_contact_id( $civi_contact_id );

				// continue if there are no applicable rules for these memberships
				if ( ! $this->plugin->admin->rule_exists( $all_memberships ) ) continue;

				// get WordPress user
				$user = $this->plugin->users->wp_user_get_by_civi_id( $civi_contact_id );

				// if we don't have a valid user
				if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) {

					// create a WordPress user if asked to
					if ( $create_users ) {

						// create WordPress user and prepare for sync
						$user = $this->user_prepare_for_sync( $civi_contact_id );

						// skip to next if something went wrong
						if ( $user === false ) continue;
						if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) continue;

					}

				}

				// should this user be synced?
				if ( ! $this->user_should_be_synced( $user ) ) continue;

				// apply rules for this WordPress user
				$this->plugin->admin->rule_apply( $user, $all_memberships );

			}

			// increment memberships offset option
			update_option( '_civi_wpms_memberships_offset', (string) $data['to'] );

		} else {

			// delete the option to start from the beginning
			delete_option( '_civi_wpms_memberships_offset' );

			// set finished flag
			$data['finished'] = 'true';

		}

		// is this an AJAX request?
		if ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) {

			// set reasonable headers
			header('Content-type: text/plain');
			header("Cache-Control: no-cache");
			header("Expires: -1");

			// echo
			echo json_encode( $data );

			// die
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

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return;

		// make sure CiviCRM file is included
		require_once 'CRM/Core/BAO/UFMatch.php';

		// get all WordPress users
		$users = get_users( array( 'all_with_meta' => true ) );

		// loop through all users
		foreach( $users AS $user ) {

			// skip if we don't have a valid user
			if ( ! ( $user instanceof WP_User ) ) { continue; }
			if ( ! $user->exists() ) { continue; }

			// call login method
			$this->sync_to_user( $user->user_login, $user );

		}

	}



	/**
	 * Check user's membership record during logout.
	 *
	 * @since 0.1
	 */
	public function sync_on_logout() {

		// get user
		$user = wp_get_current_user();
		$user_login = $user->user_login;

		// call login method
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

		// maybe create WordPress user
		$user = $this->plugin->users->wp_user_create_from_contact_id( $civi_contact_id );

		// bail if something goes wrong
		if ( $user === false ) return false;
		if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) return false;

		// get sync method
		$method = $this->plugin->admin->setting_get_method();

		// when syncing roles, remove the default role from the
		// new user - rule_apply() will set a role when it runs
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

		// kick out if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return false;
		if ( ! $user->exists() ) return false;

		// assume user should be synced
		$should_be_synced = true;

		// exclude admins by default
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

		// should this user be synced?
		if ( ! $this->user_should_be_synced( $user ) ) return;

		// get CiviCRM contact ID
		$civi_contact_id = $this->plugin->users->civi_contact_id_get( $user );

		// bail if we don't have one
		if ( $civi_contact_id === false ) return;

		// get memberships
		$memberships = $this->membership_get_by_contact_id( $civi_contact_id );

		// bail if there are no applicable rules for these memberships
		if ( ! $this->plugin->admin->rule_exists( $memberships ) ) return;

		// update WordPress user
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

		// target our object type
		if ( $objectName != 'Membership' ) return;

		// only process edit operations
		if ( $op != 'edit' ) return;

		// get details of CiviCRM membership
		$membership = civicrm_api( 'Membership', 'get', array(
			'version' => '3',
			'sequential' => 1,
			'id' => $objectId,
		));

		// sanity check
		if (
			$membership['is_error'] == 0 AND
			isset( $membership['values'] ) AND
			count( $membership['values'] ) > 0
		) {

			// store in property for later inspection
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

		// target our object type
		if ( $objectName != 'Membership' ) return;

		// kick out if we don't have a contact ID
		if ( ! isset( $objectRef->contact_id ) ) return;

		// only process create and edit operations
		if ( ! in_array( $op, array( 'create', 'edit' ) ) ) return;

		// init previous membership
		$previous_membership = null;

		// for edit operations, we first need to check for renewals
		if ( $op == 'edit' AND isset( $this->membership_pre ) AND isset( $objectRef->membership_type_id ) ) {

			// make sure we're comparing like with like
			$previous_type_id = absint( $this->membership_pre['values'][0]['membership_type_id'] );
			$current_type_id = absint( $objectRef->membership_type_id );

			// do we have different CiviCRM membership types?
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

				// cast as object for processing below
				$previous_membership = (object) $this->membership_pre['values'][0];

			}

		}

		// if there is an applicable rule for the previous membership
		if ( $this->plugin->admin->rule_exists( $previous_membership ) ) {

			// get WordPress user for this contact ID
			$user = $this->plugin->users->wp_user_get_by_civi_id( $objectRef->contact_id );

			// if we don't receive a valid user
			if ( ! ( $user instanceof WP_User ) ) {

				// create WordPress user and prepare for sync
				$user = $this->user_prepare_for_sync( $objectRef->contact_id );

				// bail if something went wrong
				if ( $user === false ) return;
				if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) return;

			}

			// bail if this user should not be synced
			if ( ! $this->user_should_be_synced( $user ) ) return;

			// update WordPress user as if the membership has been deleted
			$this->plugin->admin->rule_undo( $user, $previous_membership );

		}

		// get all the memberships for this contact
		$memberships = $this->membership_get_by_contact_id( $objectRef->contact_id );

		// bail if there are no applicable rules for these memberships
		if ( ! $this->plugin->admin->rule_exists( $memberships ) ) return;

		// if we didn't get a WordPress user previously
		if ( ! isset( $user ) ) {

			// get WordPress user for this contact ID
			$user = $this->plugin->users->wp_user_get_by_civi_id( $objectRef->contact_id );

			// if we don't receive a valid user
			if ( ! ( $user instanceof WP_User ) ) {

				// create WordPress user and prepare for sync
				$user = $this->user_prepare_for_sync( $objectRef->contact_id );

				// bail if something went wrong
				if ( $user === false ) return;
				if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) return;

			}

			// bail if this user should not be synced
			if ( ! $this->user_should_be_synced( $user ) ) return;

		}

		// update WordPress user
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

		// target our object type
		if ( $objectName != 'Membership' ) return;

		// kick out if we don't have a contact ID
		if ( ! isset( $objectRef->contact_id ) ) return;

		// only process delete operations
		if ( $op != 'delete' ) return;

		// get WordPress user for this contact ID
		$user = $this->plugin->users->wp_user_get_by_civi_id( $objectRef->contact_id );

		// bail if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return;

		// should this user be synced?
		if ( ! $this->user_should_be_synced( $user ) ) return;

		// undo WordPress user's membership
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

		// kick out if not membership form
		if ( ! ( $form instanceof CRM_Member_Form_Membership ) ) return;

	}



	/**
	 * Get membership records by CiviCRM contact ID.
	 *
	 * This method has been refined to get the Memberships ordered by end date.
	 * The reason for this is that Civi_WP_Member_Sync_Admin::rule_apply() has
	 * an implicit expectation of membership sequence because subsequent checks
	 * override those that come before. For further info, refer to the docblock
	 * for rule_apply(). This has been further refined to sort the returned data
	 * such that current memberships come at the end of the array. Props @axaak.
	 *
	 * @since 0.1
	 *
	 * @param int $civi_contact_id The numerical CiviCRM contact ID.
	 * @return array $membership CiviCRM formatted membership data.
	 */
	public function membership_get_by_contact_id( $civi_contact_id ) {

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return false;

		// get details of CiviCRM memberships
		$memberships = civicrm_api( 'Membership', 'get', array(
			'version' => '3',
			'sequential' => 1,
			'contact_id' => $civi_contact_id,
			'status_id.is_current_member' => array(
				'IS NOT NULL' => 1
			),
			'options' => array(
				'limit' => 0,
				'sort' => 'status_id.is_current_member ASC, end_date',
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
		));

		// if we have membership details
		if (
			$memberships['is_error'] == 0 AND
			isset( $memberships['values'] ) AND
			count( $memberships['values'] ) > 0
		) {

			// CiviCRM API data contains a 'values' array
			return $memberships;

		}

		// fallback
		return false;

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

		// sanity checks
		if ( ! is_numeric( $type_id ) ) return false;
		if ( $type_id === 0 ) return false;

		// init return
		$name = '';

		// get membership types
		$membership_types = $this->types_get_all();

		// sanity checks
		if ( ! is_array( $membership_types ) ) return false;
		if ( count( $membership_types ) == 0 ) return false;

		// flip for easier searching
		$membership_types = array_flip( $membership_types );

		// init current roles
		$name = array_search( $type_id, $membership_types );

		// --<
		return $name;

	}



	/**
	 * Retrieve the number of Memberships.
	 *
	 * @since 0.2.8
	 *
	 * @return int $membership_count The number of memberships.
	 */
	public function memberships_get_count() {

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return 0;

		// get all CiviCRM memberships
		$membership_count = civicrm_api( 'Membership', 'getcount', array(
			'version' => '3',
			'options' => array(
				'limit' => '0',
			),
		));

		// sanity check in case of error
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

		// only calculate once
		if ( isset( $this->membership_types ) ) { return $this->membership_types; }

		// init return
		$this->membership_types = array();

		// return empty array if no CiviCRM
		if ( ! civi_wp()->initialize() ) return array();

		// get all membership type details
		$membership_type_details = civicrm_api( 'MembershipType', 'get', array(
			'version' => '3',
			'sequential' => '1',
			'options' => array(
				'limit' => '0',
			),
		));

		// construct array of types
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

		// only calculate once
		if ( isset( $this->membership_status_rules ) ) { return $this->membership_status_rules; }

		// init return
		$this->membership_status_rules = array();

		// return empty array if no CiviCRM
		if ( ! civi_wp()->initialize() ) return array();

		// get all membership status details
		$membership_status_details = civicrm_api( 'MembershipStatus', 'get', array(
			'version' => '3',
			'sequential' => '1',
			'options' => array(
				'limit' => '0',
			),
		));

		// construct array of status rules
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

		// init return
		$status_rules = '';

		// get current rules for this item
		$current_rules = $this->status_rules_get_current_array( $values );

		// if there are some...
		if ( $current_rules !== false AND is_array( $current_rules ) ) {

			// separate with line break
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

		// get membership status rules
		$status_rules = $this->status_rules_get_all();

		// sanity checks
		if ( ! is_array( $status_rules ) ) return false;
		if ( count( $status_rules ) == 0 ) return false;

		// flip for easier searching
		$status_rules = array_flip( $status_rules );

		// init return
		$rules_array = array();

		// init current rule
		$current_rule = maybe_unserialize( $values );

		// build rules array for this item
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



} // class ends



