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
	 * @param object $plugin The plugin object
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
			'options' => array(
				'limit' => '5',
				'offset' => $memberships_offset,
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

				// get WordPress user
				$user = $this->plugin->users->wp_user_get_by_civi_id( $civi_contact_id );

				// if we don't have a valid user
				if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) {

					// create a WordPress user if asked to
					if ( $create_users ) {

						// maybe create WordPress user
						$user = $this->plugin->users->wp_user_create_from_contact_id( $civi_contact_id );

						// skip to next if something goes wrong
						if ( $user === false ) continue;
						if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) continue;

						// get sync method and sanitize
						$method = $this->plugin->admin->setting_get( 'method' );
						$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

						// when syncing roles, remove the default role from the
						// new user - rule_apply() will set a role when it runs
						if ( $method == 'roles' ) {
							$user->remove_role( get_option( 'default_role' ) );
						}

					}

				}

				// should this user be synced?
				if ( ! $this->user_should_be_synced( $user ) ) continue;

				/**
				 * The use of existing code here is not the most efficient way to
				 * sync each membership. However, given that in most cases there
				 * will only be one membership per contact, I think the overhead
				 * will be minimal. Moreover, this new chunked sync method limits
				 * the impact of a manual sync per request.
				 */

				// get *all* memberships for this contact
				$memberships = $this->membership_get_by_contact_id( $civi_contact_id );

				// apply rules for this WordPress user
				$this->plugin->admin->rule_apply( $user, $memberships );

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
	 * @return bool $success True if successful, false otherwise
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
	 * Check if a user's membership should by synced.
	 *
	 * @since 0.2.6
	 *
	 * @param object $user The WordPress user object
	 * @return bool $should_be_synced Whether or not the user should be synced
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
		 * @param bool $should_be_synced True if the user should be synced, false otherwise
		 * @param object $user The WordPress user object
		 * @param bool $should_be_synced The modified value of the sync flag
		 */
		return apply_filters( 'civi_wp_member_sync_user_should_be_synced', $should_be_synced, $user );

	}



	/**
	 * Sync a user's role based on their membership record.
	 *
	 * @since 0.1
	 *
	 * @param string $user_login Logged in user's username
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public function sync_to_user( $user_login, $user ) {

		// should this user be synced?
		if ( ! $this->user_should_be_synced( $user ) ) return;

		// get CiviCRM contact ID
		$civi_contact_id = $this->plugin->users->civi_contact_id_get( $user );

		// bail if we don't have one
		if ( $civi_contact_id === false ) return;

		// get membership
		$membership = $this->membership_get_by_contact_id( $civi_contact_id );

		// update WordPress user
		$this->plugin->admin->rule_apply( $user, $membership );

	}



	/**
	 * Update a WordPress user role when a CiviCRM membership is updated.
	 *
	 * @since 0.1
	 *
	 * @param string $op the type of database operation
	 * @param string $objectName the type of object
	 * @param integer $objectId the ID of the object
	 * @param object $objectRef the object
	 */
	public function membership_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// disable
		return;

		// target our object type
		if ( $objectName != 'Membership' ) return;

	}



	/**
	 * Update a WordPress user when a CiviCRM membership is updated.
	 *
	 * @since 0.1
	 *
	 * @param string $op the type of database operation
	 * @param string $objectName the type of object
	 * @param integer $objectId the ID of the object
	 * @param object $objectRef the object
	 */
	public function membership_updated( $op, $objectName, $objectId, $objectRef ) {

		// target our object type
		if ( $objectName != 'Membership' ) return;

		// kick out if we don't have a contact ID
		if ( ! isset( $objectRef->contact_id ) ) return;

		// only process create and edit operations
		if ( $op == 'edit' OR $op == 'create' ) {

			// get WordPress user for this contact ID
			$user = $this->plugin->users->wp_user_get_by_civi_id( $objectRef->contact_id );

			// if we don't receive a valid user
			if ( ! ( $user instanceof WP_User ) ) {

				// maybe create WordPress user
				$user = $this->plugin->users->wp_user_create_from_contact_id( $objectRef->contact_id );

				// bail if something goes wrong
				if ( $user === false ) return;

				// get sync method and sanitize
				$method = $this->plugin->admin->setting_get( 'method' );
				$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

				// when syncing roles, remove the default role from the
				// new user - rule_apply() will set a role when it runs
				if ( $method == 'roles' ) {
					$user->remove_role( get_option( 'default_role' ) );
				}

			}

			// should this user be synced?
			if ( ! $this->user_should_be_synced( $user ) ) return;

			// reformat $objectRef as if it was an API return
			$membership = array(
				'is_error' => 0,
				'values' => array( (array) $objectRef ),
			);

			// update WordPress user by membership
			$this->plugin->admin->rule_apply( $user, $membership );

		}

	}



	/**
	 * Update a WordPress user when a CiviCRM membership is deleted.
	 *
	 * @since 0.3
	 *
	 * @param string $op the type of database operation
	 * @param string $objectName the type of object
	 * @param integer $objectId the ID of the object
	 * @param object $objectRef the object
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
	 * @param string $formName the CiviCRM form name
	 * @param object $form the CiviCRM form object
	 */
	public function membership_form_process( $formName, &$form ) {

		// kick out if not membership form
		if ( ! ( $form instanceof CRM_Member_Form_Membership ) ) return;

	}



	/**
	 * Get membership record by CiviCRM contact ID.
	 *
	 * @since 0.1
	 *
	 * @param int $civi_contact_id The numerical CiviCRM contact ID
	 * @return array $membership CiviCRM formatted membership data
	 */
	public function membership_get_by_contact_id( $civi_contact_id ) {

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return false;

		// get CiviCRM membership details
		$membership = civicrm_api( 'Membership', 'get', array(
			'version' => '3',
			'page' => 'CiviCRM',
			'q' => 'civicrm/ajax/rest',
			'sequential' => '1',
			'contact_id' => $civi_contact_id,
		));

		// if we have membership details
		if (
			$membership['is_error'] == 0 AND
			isset( $membership['values'] ) AND
			count( $membership['values'] ) > 0
		) {

			// CiviCRM should return a 'values' array
			return $membership;

		}

		// fallback
		return false;

	}



	/**
	 * Get name of CiviCRM membership type by ID.
	 *
	 * @since 0.1
	 *
	 * @param int $type_id the numeric ID of the membership type
	 * @return string $name The name of the membership type
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
	 * @return int
	 */
	public function memberships_get_count() {

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return;

		// get CiviCRM memberships
		$membership_count = civicrm_api( 'Membership', 'getcount', array(
			'version' => '3',
			'options' => array(
				'limit' => '99999999',
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
	 * @return array $membership_type List of types, key is ID, value is name
	 */
	public function types_get_all() {

		// only calculate once
		if ( isset( $this->membership_types ) ) { return $this->membership_types; }

		// init return
		$this->membership_types = array();

		// return empty array if no CiviCRM
		if ( ! civi_wp()->initialize() ) return array();

		// get membership details
		$membership_type_details = civicrm_api( 'MembershipType', 'get', array(
			'version' => '3',
			'sequential' => '1',
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
	 * @return array $membership_status List of status rules, key is ID, value is name
	 */
	public function status_rules_get_all() {

		// only calculate once
		if ( isset( $this->membership_status_rules ) ) { return $this->membership_status_rules; }

		// init return
		$this->membership_status_rules = array();

		// return empty array if no CiviCRM
		if ( ! civi_wp()->initialize() ) return array();

		// get membership details
		$membership_status_details = civicrm_api( 'MembershipStatus', 'get', array(
			'version' => '3',
			'sequential' => '1',
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
	 * @param string $values Serialised array of status rule IDs
	 * @return string $status_rules The list of status rules, one per line
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
	 * @param string $values Serialised array of status rule IDs
	 * @return array $rules_array The list of membership status rules for this item
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



