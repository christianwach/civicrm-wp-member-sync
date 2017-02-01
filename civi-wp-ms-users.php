<?php

/**
 * CiviCRM WordPress Member Sync Users class.
 *
 * Class for encapsulating WordPress user functionality.
 *
 * @since 0.1
 *
 * @package Civi_WP_Member_Sync
 */
class Civi_WP_Member_Sync_Users {

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



	/**
	 * Initialise when CiviCRM initialises.
	 *
	 * @since 0.1
	 */
	public function initialise() {

	}



	//##########################################################################



	/**
	 * Check if a WordPress user has a particular role.
	 *
	 * @since 0.2.8
	 *
	 * @param WP_User $user WP_User object
	 * @param string $role WordPress role key
	 * @return bool $has_role True if this user has the supplied role, false otherwise
	 */
	public function wp_has_role( $user, $role ) {

		// kick out if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return false;

		// check via WordPress function
		$has_role = user_can( $user, $role );

		// --<
		return $has_role;

	}



	/**
	 * Get primary WordPress user role.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object
	 * @return string $role Primary WordPress role for this user
	 */
	public function wp_role_get( $user ) {

		// kick out if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return false;

		// only build role names array once, since this is called by the sync routine
		if ( ! isset( $this->role_names ) ) {

			// get role names array
			$this->role_names = $this->wp_role_names_get_all();

		}

		// init filtered as empty
		$filtered_roles = array_keys( $this->role_names );

		// roles is still an array
		foreach ( $user->roles AS $role ) {

			// return the first valid one
			if ( $role AND in_array( $role, $filtered_roles ) ) { return $role; }

		}

		// fallback
		return false;

	}



	/**
	 * Get all current roles for a WordPress user.
	 *
	 * The roles returned exclude any that are assigned by bbPress.
	 *
	 * @since 0.2.8
	 *
	 * @param WP_User $user WP_User object
	 * @return array $roles WordPress roles for this user
	 */
	public function wp_roles_get_all( $user ) {

		// kick out if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return false;

		// init return
		$user_roles = array();

		// only build role names array once
		if ( ! isset( $this->role_names ) ) {

			// get role names array
			$this->role_names = $this->wp_role_names_get_all();

		}

		// init filtered array in same format as $user->roles
		$filtered_roles = array_keys( $this->role_names );

		// check all user roles
		foreach ( $user->roles AS $role ) {

			// add role to return array if it's a "blog" role
			if ( $role AND in_array( $role, $filtered_roles ) ) {
				$user_roles[] = $role;
			}

		}

		// --<
		return $user_roles;

	}



	/**
	 * Add a role to a WordPress user.
	 *
	 * @since 0.2.8
	 *
	 * @param WP_User $user WordPress user object
	 * @param string $role WordPress role key
	 */
	public function wp_role_add( $user, $role ) {

		// kick out if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return;

		// sanity check param
		if ( empty( $role ) ) return;

		// add role to user
		$user->add_role( $role );

		/**
		 * Let other plugins know that a role has been added to a user.
		 *
		 * @param WP_User $user The WordPress user object
		 * @param string $role The new role added to the user
		 */
		do_action( 'civi_wp_member_sync_add_role', $user, $role );

	}



	/**
	 * Remove a role from a WordPress user.
	 *
	 * @since 0.2.8
	 *
	 * @param WP_User $user WordPress user object
	 * @param string $role WordPress role key
	 */
	public function wp_role_remove( $user, $role ) {

		// kick out if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return;

		// sanity check param
		if ( empty( $role ) ) return;

		// remove role from user
		$user->remove_role( $role );

		/**
		 * Let other plugins know that a role has been removed from a user.
		 *
		 * @param WP_User $user The WordPress user object
		 * @param string $role The role removed from the user
		 */
		do_action( 'civi_wp_member_sync_remove_role', $user, $role );

	}



	/**
	 * Replace a WordPress user role.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WordPress user object
	 * @param string $old_role Old WordPress role key
	 * @param string $new_role New WordPress role key
	 */
	public function wp_role_replace( $user, $old_role, $new_role ) {

		// kick out if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return;

		// sanity check params
		if ( empty( $old_role ) ) return;
		if ( empty( $new_role ) ) return;

		// Remove old role then add new role, so that we don't inadvertently
		// overwrite multiple roles, for example when bbPress is active

		// remove user's existing role
		$user->remove_role( $old_role );

		// add new role
		$user->add_role( $new_role );

		/**
		 * Let other plugins know that a user's role has been changed.
		 *
		 * @param object $user The WordPress user object
		 * @param string $new_role The new role that the user has
		 * @param string $old_role The role that the user had before
		 */
		do_action( 'civi_wp_member_sync_set_role', $user, $new_role, $old_role );

	}



	//##########################################################################



	/**
	 * Get a WordPress role name by role key.
	 *
	 * @since 0.1
	 *
	 * @param string $key The machine-readable name of the WP_Role
	 * @return string $role_name The human-readable name of the WP_Role
	 */
	public function wp_role_name_get( $key ) {

		// only build role names array once, since this is called by the list page
		if ( ! isset( $this->role_names ) ) {

			// get role names array
			$this->role_names = $this->wp_role_names_get_all();

		}

		// get value by key
		$role_name = isset( $this->role_names[$key] ) ? $this->role_names[$key] : false;

		// --<
		return $role_name;

	}



	/**
	 * Get all WordPress role names.
	 *
	 * @since 0.1
	 *
	 * @return array $role_names An array of role names, keyed by role key
	 */
	public function wp_role_names_get_all() {

		// access roles global
		global $wp_roles;

		// load roles if not set
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		// get names
		$role_names = $wp_roles->get_names();

		// if we have bbPress active, filter out its custom roles
		if ( function_exists( 'bbp_get_blog_roles' ) ) {

			// get bbPress-filtered roles
			$bbp_roles = bbp_get_blog_roles();

			// init roles
			$role_names = array();

			// sanity check
			if ( ! empty( $bbp_roles ) ) {
				foreach( $bbp_roles AS $bbp_role => $bbp_role_data ) {

					// add to roles array
					$role_names[$bbp_role] = $bbp_role_data['name'];

				}
			}

		}

		// --<
		return $role_names;

	}



	//##########################################################################



	/**
	 * Add a capability to a WordPress user.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object
	 * @param string $capability Capability name
	 */
	public function wp_cap_add( $user, $capability ) {

		// kick out if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return;

		// sanity check params
		if ( empty( $capability ) ) return;

		// does this user have that capability?
		if ( ! $user->has_cap( $capability ) ) {

			// no, add it
			$user->add_cap( $capability );

			/**
			 * Let other plugins know that a capability has been added to a user.
			 *
			 * @param object $user The WordPress user object
			 * @param string $capability The name of the capability
			 */
			do_action( 'civi_wp_member_sync_add_cap', $user, $capability );

		}

	}



	/**
	 * Remove a capability from a WordPress user.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object
	 * @param string $capability Capability name
	 */
	public function wp_cap_remove( $user, $capability ) {

		// kick out if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return;

		// sanity check params
		if ( empty( $capability ) ) return;

		// does this user have that capability?
		if ( $user->has_cap( $capability ) ) {

			// yes, remove it
			$user->remove_cap( $capability );

			/**
			 * Let other plugins know that a capability has been removed from a user.
			 *
			 * @param object $user The WordPress user object
			 * @param string $capability The name of the capability
			 */
			do_action( 'civi_wp_member_sync_remove_cap', $user, $capability );

		}

	}



	/**
	 * Clear all status capabilities from a WordPress user, since we don't necessarily
	 * know which one the user had before the status change.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object
	 * @param string $capability Capability name
	 */
	public function wp_cap_remove_status( $user, $capability ) {

		// kick out if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) return;

		// sanity check params
		if ( empty( $capability ) ) return;

		// get membership status rules
		$status_rules = $this->plugin->members->status_rules_get_all();

		// sanity checks
		if ( ! is_array( $status_rules ) ) return;
		if ( count( $status_rules ) == 0 ) return;

		// get keys
		$status_rule_ids = array_keys( $status_rules );

		// loop through them
		foreach( $status_rule_ids AS $status_id ) {

			// construct membership status capability name
			$capability_status = $capability . '_' . $status_id;

			// use local remove method
			$this->wp_cap_remove( $user, $capability_status );

		}

	}



	//##########################################################################



	/**
	 * Get a WordPress user for a CiviCRM contact ID.
	 *
	 * @since 0.1
	 *
	 * @param int $contact_id The numeric CiviCRM contact ID
	 * @return WP_User $user WP_User object for the WordPress user
	 */
	public function wp_user_get_by_civi_id( $contact_id ) {

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return false;

		// make sure CiviCRM file is included
		require_once 'CRM/Core/BAO/UFMatch.php';

		// search using CiviCRM's logic
		$user_id = CRM_Core_BAO_UFMatch::getUFId( $contact_id );

		// kick out if we didn't get one
		if ( empty( $user_id ) ) return false;

		// get user object
		$user = new WP_User( $user_id );

		// --<
		return $user;

	}



	/**
	 * Get a CiviCRM contact ID for a WordPress user object.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return int $civi_contact_id The numerical CiviCRM contact ID
	 */
	public function civi_contact_id_get( $user ) {

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return false;

		// make sure CiviCRM file is included
		require_once 'CRM/Core/BAO/UFMatch.php';

		// do initial search
		$civi_contact_id = CRM_Core_BAO_UFMatch::getContactId( $user->ID );
		if ( ! $civi_contact_id ) {

			// sync this user
			CRM_Core_BAO_UFMatch::synchronizeUFMatch(
				$user, // user object
				$user->ID, // ID
				$user->user_email, // unique identifier
				'WordPress', // CMS
				null, // status
				'Individual', // contact type
				null // is_login
			);

			// get the CiviCRM contact ID
			$civi_contact_id = CRM_Core_BAO_UFMatch::getContactId( $user->ID );

			// sanity check
			if ( ! $civi_contact_id ) {
				return false;
			}

		}

		// --<
		return $civi_contact_id;

	}



	/**
	 * Get CiviCRM contact data by contact ID.
	 *
	 * @since 0.1
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM contact
	 * @return mixed $civi_contact The array of data for the CiviCRM Contact, or false if not found
	 */
	public function civi_get_contact_by_contact_id( $contact_id ) {

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return false;

		// get all contact data
		$params = array(
			'version' => 3,
			'contact_id' => $contact_id,
		);

		// use API
		$contact_data = civicrm_api( 'contact', 'get', $params );

		// bail if we get any errors
		if ( $contact_data['is_error'] == 1 ) return false;
		if ( ! isset( $contact_data['values'] ) ) return false;
		if ( count( $contact_data['values'] ) === 0 ) return false;

		// get contact
		$contact = array_shift( $contact_data['values'] );

		// --<
		return $contact;

	}



	//##########################################################################



	/**
	 * Create a WordPress user for a given CiviCRM Contact ID.
	 *
	 * @since 0.2.8
	 *
	 * @param int $civi_contact_id The numerical CiviCRM contact ID
	 * @return object $user The WordPress user object, false on error
	 */
	public function wp_user_create_from_contact_id( $civi_contact_id ) {

		/**
		 * Let other plugins override whether a user should be created.
		 *
		 * @since 0.2
		 *
		 * @param bool True - users should be created by default
		 * @return bool True if users should be created, false otherwise
		 */
		if ( true === apply_filters( 'civi_wp_member_sync_auto_create_wp_user', true ) ) {

			// get CiviCRM contact
			$civi_contact = $this->civi_get_contact_by_contact_id( $civi_contact_id );

			// bail if something goes wrong
			if ( $civi_contact === false ) return false;

			// create a WordPress user
			$user = $this->wp_create_user( $civi_contact );

			// bail if something goes wrong
			if ( ! ( $user instanceof WP_User ) ) return false;

			// return user
			return $user;

		} else {

			// --<
			return false;

		}

	}



	/**
	 * Creates a WordPress User given a CiviCRM contact.
	 *
	 * @since 0.1
	 *
	 * @param array $civi_contact The data for the CiviCRM contact
	 * @return mixed $user WordPress user object or false on failure
	 */
	public function wp_create_user( $civi_contact ) {

		// bail if no email address
		if ( ! isset( $civi_contact['email'] ) OR empty( $civi_contact['email'] ) ) {
			return false;
		}

		// create username from display name
		$user_name = sanitize_title( sanitize_user( $civi_contact['display_name'] ) );

		/**
		 * Let plugins override the username.
		 *
		 * @since 0.1
		 *
		 * @param str $user_name The previously-generated WordPress username
		 * @param array $civi_contact The CiviCRM contact data
		 * @return str $user_name The modified WordPress username
		 */
		$user_name = apply_filters( 'civi_wp_member_sync_new_username', $user_name, $civi_contact );

		// check if we have a user with that username
		$user_id = username_exists( $user_name );

		// if not, check against email address
		if ( ! $user_id AND email_exists( $civi_contact['email'] ) == false ) {

			// generate a random password
			$random_password = wp_generate_password(
				$length = 12,
				$include_standard_special_chars = false
			);

			// remove filters
			$this->remove_filters();

			/**
			 * Let other plugins know that we're about to insert a user.
			 *
			 * @since 0.1
			 *
			 * @param array $civi_contact The CiviCRM contact object
			 */
			do_action( 'civi_wp_member_sync_before_insert_user', $civi_contact );

			// create the user
			$user_id = wp_insert_user( array(
				'user_login' => $user_name,
				'user_pass' => $random_password,
				'user_email' => $civi_contact['email'],
				'first_name' => $civi_contact['first_name'],
				'last_name' => $civi_contact['last_name'],
			) );

			// create UF Match
			if ( ! is_wp_error( $user_id ) AND isset( $civi_contact['contact_id'] ) ) {

				$transaction = new CRM_Core_Transaction();

				// create the UF Match record
				$ufmatch             = new CRM_Core_DAO_UFMatch();
				$ufmatch->domain_id  = CRM_Core_Config::domainID();
				$ufmatch->uf_id      = $user_id;
				$ufmatch->contact_id = $civi_contact['contact_id'];
				$ufmatch->uf_name    = $civi_contact['email'];

				if ( ! $ufmatch->find( true ) ) {
					$ufmatch->save();
					$ufmatch->free();
					$transaction->commit();
				}

			}

			// re-add filters
			$this->add_filters();

			/**
			 * Let other plugins know that we've inserted a user.
			 *
			 * @since 0.1
			 *
			 * @param array $civi_contact The CiviCRM contact object
			 * @param int $user_id The numeric ID of the WordPress user
			 */
			do_action( 'civi_wp_member_sync_after_insert_user', $civi_contact, $user_id );

		}

		// sanity check
		if ( is_numeric( $user_id ) AND $user_id ) {

			// return WordPress user
			return get_user_by( 'id', $user_id );

		}

		// return error
		return false;

	}



	/**
	 * Remove filters (that we know of) that will interfere with creating a WordPress user.
	 *
	 * @since 0.1
	 */
	private function remove_filters() {

		// get CiviCRM instance
		$civi = civi_wp();

		// do we have the old-style plugin structure?
		if ( method_exists( $civi, 'update_user' ) ) {

			// remove previous CiviCRM plugin filters
			remove_action( 'user_register', array( civi_wp(), 'update_user' ) );
			remove_action( 'profile_update', array( civi_wp(), 'update_user' ) );

		} else {

			// remove current CiviCRM plugin filters
			remove_action( 'user_register', array( civi_wp()->users, 'update_user' ) );
			remove_action( 'profile_update', array( civi_wp()->users, 'update_user' ) );

		}

		// remove CiviCRM WordPress Profile Sync filters
		global $civicrm_wp_profile_sync;
		if ( is_object( $civicrm_wp_profile_sync ) ) {
			remove_action( 'user_register', array( $civicrm_wp_profile_sync, 'wordpress_contact_updated' ), 100 );
			remove_action( 'profile_update', array( $civicrm_wp_profile_sync, 'wordpress_contact_updated' ), 100 );
		}

		/**
		 * Let other plugins know that we're removing user actions.
		 *
		 * @since 0.1
		 */
		do_action( 'civi_wp_member_sync_remove_filters' );

	}



	/**
	 * Add filters (that we know of) after creating a WordPress user.
	 *
	 * @since 0.1
	 */
	private function add_filters() {

		// get CiviCRM instance
		$civi = civi_wp();

		// do we have the old-style plugin structure?
		if ( method_exists( $civi, 'update_user' ) ) {

			// re-add previous CiviCRM plugin filters
			add_action( 'user_register', array( civi_wp(), 'update_user' ) );
			add_action( 'profile_update', array( civi_wp(), 'update_user' ) );

		} else {

			// re-add current CiviCRM plugin filters
			add_action( 'user_register', array( civi_wp()->users, 'update_user' ) );
			add_action( 'profile_update', array( civi_wp()->users, 'update_user' ) );

		}

		// re-add CiviCRM WordPress Profile Sync filters
		global $civicrm_wp_profile_sync;
		if ( is_object( $civicrm_wp_profile_sync ) ) {
			add_action( 'user_register', array( $civicrm_wp_profile_sync, 'wordpress_contact_updated' ), 100, 1 );
			add_action( 'profile_update', array( $civicrm_wp_profile_sync, 'wordpress_contact_updated' ), 100, 1 );
		}

		/**
		 * Let other plugins know that we're adding user actions.
		 *
		 * @since 0.1
		 */
		do_action( 'civi_wp_member_sync_add_filters' );

	}



} // class ends



