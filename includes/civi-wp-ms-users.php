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
	 * @var object $plugin The plugin object.
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

		// Initialise early.
		add_action( 'civi_wp_member_sync_initialised', [ $this, 'initialise' ], 3 );

	}



	/**
	 * Initialise this object.
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
	 * @param WP_User $user WP_User object.
	 * @param string $role WordPress role key.
	 * @return bool $has_role True if this user has the supplied role, false otherwise.
	 */
	public function wp_has_role( $user, $role ) {

		// Kick out if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) {
			return false;
		}

		// Check via WordPress function.
		$has_role = user_can( $user, $role );

		// --<
		return $has_role;

	}



	/**
	 * Get primary WordPress user role.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object.
	 * @return string $role Primary WordPress role for this user.
	 */
	public function wp_role_get( $user ) {

		// Kick out if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) {
			return false;
		}

		// Only build role names array once, since this is called by the sync routine.
		if ( ! isset( $this->role_names ) ) {

			// Get role names array.
			$this->role_names = $this->wp_role_names_get_all();

		}

		// Init filtered as empty.
		$filtered_roles = array_keys( $this->role_names );

		// Roles is still an array.
		foreach ( $user->roles AS $role ) {

			// Return the first valid one.
			if ( $role AND in_array( $role, $filtered_roles ) ) {
				return $role;
			}

		}

		// Fallback.
		return false;

	}



	/**
	 * Get all current roles for a WordPress user.
	 *
	 * The roles returned exclude any that are assigned by bbPress.
	 *
	 * @since 0.2.8
	 *
	 * @param WP_User $user WP_User object.
	 * @return array $roles WordPress roles for this user.
	 */
	public function wp_roles_get_all( $user ) {

		// Kick out if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) {
			return false;
		}

		// Init return.
		$user_roles = [];

		// Only build role names array once.
		if ( ! isset( $this->role_names ) ) {

			// Get role names array.
			$this->role_names = $this->wp_role_names_get_all();

		}

		// Init filtered array in same format as $user->roles.
		$filtered_roles = array_keys( $this->role_names );

		// Check all user roles.
		foreach( $user->roles AS $role ) {

			// Add role to return array if it's a "blog" role.
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
	 * @param WP_User $user WordPress user object.
	 * @param string $role WordPress role key.
	 */
	public function wp_role_add( $user, $role ) {

		// Kick out if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Sanity check param.
		if ( empty( $role ) ) {
			return;
		}

		// Add role to user.
		$user->add_role( $role );

		/**
		 * Let other plugins know that a role has been added to a user.
		 *
		 * @param WP_User $user The WordPress user object.
		 * @param string $role The new role added to the user.
		 */
		do_action( 'civi_wp_member_sync_add_role', $user, $role );

	}



	/**
	 * Remove a role from a WordPress user.
	 *
	 * @since 0.2.8
	 *
	 * @param WP_User $user WordPress user object.
	 * @param string $role WordPress role key.
	 */
	public function wp_role_remove( $user, $role ) {

		// Kick out if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Sanity check param.
		if ( empty( $role ) ) {
			return;
		}

		// Remove role from user.
		$user->remove_role( $role );

		/**
		 * Let other plugins know that a role has been removed from a user.
		 *
		 * @param WP_User $user The WordPress user object.
		 * @param string $role The role removed from the user.
		 */
		do_action( 'civi_wp_member_sync_remove_role', $user, $role );

	}



	/**
	 * Replace a WordPress user role.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WordPress user object.
	 * @param string $old_role Old WordPress role key.
	 * @param string $new_role New WordPress role key.
	 */
	public function wp_role_replace( $user, $old_role, $new_role ) {

		// Kick out if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Sanity check params.
		if ( empty( $old_role ) ) {
			return;
		}
		if ( empty( $new_role ) ) {
			return;
		}

		// Remove old role then add new role, so that we don't inadvertently
		// overwrite multiple roles, for example when bbPress is active.

		// Remove user's existing role.
		$user->remove_role( $old_role );

		// Add new role.
		$user->add_role( $new_role );

		/**
		 * Let other plugins know that a user's role has been changed.
		 *
		 * @param object $user The WordPress user object.
		 * @param string $new_role The new role that the user has.
		 * @param string $old_role The role that the user had before.
		 */
		do_action( 'civi_wp_member_sync_set_role', $user, $new_role, $old_role );

	}



	//##########################################################################



	/**
	 * Get a WordPress role name by role key.
	 *
	 * @since 0.1
	 *
	 * @param string $key The machine-readable name of the WP_Role.
	 * @return string $role_name The human-readable name of the WP_Role.
	 */
	public function wp_role_name_get( $key ) {

		// Only build role names array once, since this is called by the list page.
		if ( ! isset( $this->role_names ) ) {

			// Get role names array.
			$this->role_names = $this->wp_role_names_get_all();

		}

		// Get value by key.
		$role_name = isset( $this->role_names[$key] ) ? $this->role_names[$key] : false;

		// --<
		return $role_name;

	}



	/**
	 * Get all WordPress role names.
	 *
	 * @since 0.1
	 *
	 * @return array $role_names An array of role names, keyed by role key.
	 */
	public function wp_role_names_get_all() {

		// Access roles global.
		global $wp_roles;

		// Load roles if not set.
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		// Get names.
		$role_names = $wp_roles->get_names();

		// If we have bbPress active, filter out its custom roles.
		if ( function_exists( 'bbp_get_blog_roles' ) ) {

			// Get bbPress-filtered roles.
			$bbp_roles = bbp_get_blog_roles();

			// Init roles.
			$role_names = [];

			// Sanity check.
			if ( ! empty( $bbp_roles ) ) {
				foreach( $bbp_roles AS $bbp_role => $bbp_role_data ) {

					// Add to roles array.
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
	 * @param WP_User $user WP_User object.
	 * @param string $capability Capability name.
	 */
	public function wp_cap_add( $user, $capability ) {

		// Kick out if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Sanity check params.
		if ( empty( $capability ) ) {
			return;
		}

		// Does this user have that capability?
		if ( ! $user->has_cap( $capability ) ) {

			// No, add it.
			$user->add_cap( $capability );

			/**
			 * Let other plugins know that a capability has been added to a user.
			 *
			 * @param object $user The WordPress user object.
			 * @param string $capability The name of the capability.
			 */
			do_action( 'civi_wp_member_sync_add_cap', $user, $capability );

		}

	}



	/**
	 * Remove a capability from a WordPress user.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object.
	 * @param string $capability Capability name.
	 */
	public function wp_cap_remove( $user, $capability ) {

		// Kick out if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Sanity check params.
		if ( empty( $capability ) ) {
			return;
		}

		// Does this user have that capability?
		if ( $user->has_cap( $capability ) ) {

			// Yes, remove it.
			$user->remove_cap( $capability );

			/**
			 * Let other plugins know that a capability has been removed from a user.
			 *
			 * @param object $user The WordPress user object.
			 * @param string $capability The name of the capability.
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
	 * @param WP_User $user WP_User object.
	 * @param string $capability Capability name.
	 */
	public function wp_cap_remove_status( $user, $capability ) {

		// Kick out if we don't receive a valid user.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Sanity check params.
		if ( empty( $capability ) ) {
			return;
		}

		// Get membership status rules.
		$status_rules = $this->plugin->members->status_rules_get_all();

		// Sanity checks.
		if ( ! is_array( $status_rules ) ) {
			return;
		}
		if ( count( $status_rules ) == 0 ) {
			return;
		}

		// Get keys.
		$status_rule_ids = array_keys( $status_rules );

		// Loop through them.
		foreach( $status_rule_ids AS $status_id ) {

			// Construct membership status capability name.
			$capability_status = $capability . '_' . $status_id;

			// Use local remove method.
			$this->wp_cap_remove( $user, $capability_status );

		}

	}



	//##########################################################################



	/**
	 * Get a WordPress user for a CiviCRM contact ID.
	 *
	 * @since 0.1
	 *
	 * @param int $contact_id The numeric CiviCRM contact ID.
	 * @return WP_User $user WP_User object for the WordPress user.
	 */
	public function wp_user_get_by_civi_id( $contact_id ) {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return false;
		}

		// Make sure CiviCRM file is included.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Search using CiviCRM's logic.
		$user_id = CRM_Core_BAO_UFMatch::getUFId( $contact_id );

		// Kick out if we didn't get one.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Get user object.
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
	 * @return int $civi_contact_id The numerical CiviCRM contact ID.
	 */
	public function civi_contact_id_get( $user ) {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return false;
		}

		// Make sure CiviCRM file is included.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Do initial search.
		$civi_contact_id = CRM_Core_BAO_UFMatch::getContactId( $user->ID );
		if ( ! $civi_contact_id ) {

			// Sync this user.
			CRM_Core_BAO_UFMatch::synchronizeUFMatch(
				$user, // User object.
				$user->ID, // ID.
				$user->user_email, // Unique identifier.
				'WordPress', // CMS.
				null, // Status.
				'Individual', // Contact type.
				null // Is_login.
			);

			// Get the CiviCRM contact ID.
			$civi_contact_id = CRM_Core_BAO_UFMatch::getContactId( $user->ID );

			// Sanity check.
			if ( ! $civi_contact_id ) {
				return false;
			}

		}

		// --<
		return $civi_contact_id;

	}



	/**
	 * Get a CiviCRM Contact ID for a WordPress User ID.
	 *
	 * @since 0.5
	 *
	 * @param int $user_id The numeric ID of the WordPress User.
	 * @return int $contact_id The numeric ID CiviCRM Contact, or false on failure.
	 */
	public function civi_contact_id_get_by_user_id( $user_id ) {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return false;
		}

		// Make sure CiviCRM file is included.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Search for the Contact.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $user_id );

		// If not found, return false.
		if ( ! $contact_id ) {
			return false;
		}

		// --<
		return $contact_id;

	}



	/**
	 * Get CiviCRM contact data by contact ID.
	 *
	 * @since 0.1
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM contact.
	 * @return mixed $civi_contact The array of data for the CiviCRM Contact, or false if not found.
	 */
	public function civi_get_contact_by_contact_id( $contact_id ) {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return false;
		}

		// Bail if we don't get a valid contact ID.
		if ( empty( $contact_id ) OR ! is_numeric( $contact_id ) ) {
			return false;
		}

		// Get all contact data.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
		];

		// Use API.
		$contact_data = civicrm_api( 'contact', 'get', $params );

		// Bail if we get any errors.
		if ( $contact_data['is_error'] == 1 ) {
			return false;
		}
		if ( ! isset( $contact_data['values'] ) ) {
			return false;
		}
		if ( count( $contact_data['values'] ) === 0 ) {
			return false;
		}

		// Get contact.
		$contact = array_shift( $contact_data['values'] );

		/**
		 * Filter the retrieved Contact data.
		 *
		 * @since 0.4.3
		 *
		 * @param array $contact The retrieved Contact data.
		 * @return array $contact The modified Contact data.
		 */
		$contact = apply_filters( 'civi_wp_member_sync_contact_retrieved', $contact );

		// --<
		return $contact;

	}



	//##########################################################################



	/**
	 * Create a WordPress user for a given CiviCRM Contact ID.
	 *
	 * @since 0.2.8
	 *
	 * @param int $civi_contact_id The numerical CiviCRM contact ID.
	 * @return object $user The WordPress user object, false on error.
	 */
	public function wp_user_create_from_contact_id( $civi_contact_id ) {

		/**
		 * Let other plugins override whether a user should be created.
		 *
		 * @since 0.2
		 *
		 * @param bool True - users should be created by default.
		 * @param int $civi_contact_id The numeric ID of the CiviCRM Contact.
		 * @return bool True if users should be created, false otherwise.
		 */
		if ( true === apply_filters( 'civi_wp_member_sync_auto_create_wp_user', true, $civi_contact_id ) ) {

			// Get CiviCRM contact.
			$civi_contact = $this->civi_get_contact_by_contact_id( $civi_contact_id );

			// Bail if something goes wrong.
			if ( $civi_contact === false ) {
				return false;
			}

			// Get types setting.
			$types = absint( $this->plugin->admin->setting_get( 'types' ) );

			// If chosen, bail if this Contact is not an Individual.
			if ( $types AND $civi_contact['contact_type'] != 'Individual' ) {
				return;
			}

			// Create a WordPress user.
			$user = $this->wp_create_user( $civi_contact );

			// Bail if something goes wrong.
			if ( ! ( $user instanceof WP_User ) ) {
				return false;
			}

			// Return user.
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
	 * @param array $civi_contact The data for the CiviCRM contact.
	 * @return mixed $user WordPress user object or false on failure.
	 */
	public function wp_create_user( $civi_contact ) {

		// Bail if no email address.
		if ( ! isset( $civi_contact['email'] ) OR empty( $civi_contact['email'] ) ) {
			return false;
		}

		// Assume it's not a new User.
		$new_user = false;

		// Create username from display name.
		$user_name = sanitize_title( sanitize_user( $civi_contact['display_name'] ) );

		// Ensure username is unique.
		$user_name = $this->unique_username( $user_name, $civi_contact );

		/**
		 * Let plugins override the username.
		 *
		 * @since 0.1
		 *
		 * @param str $user_name The previously-generated WordPress username.
		 * @param array $civi_contact The CiviCRM contact data.
		 * @return str $user_name The modified WordPress username.
		 */
		$user_name = apply_filters( 'civi_wp_member_sync_new_username', $user_name, $civi_contact );

		// Check if we have a user with that username.
		$user_id = username_exists( $user_name );

		// If not, check against email address.
		if ( ! $user_id AND email_exists( $civi_contact['email'] ) == false ) {

			// Generate a random password.
			$random_password = wp_generate_password(
				$length = 12,
				$include_standard_special_chars = false
			);

			// Remove filters.
			$this->remove_filters();

			/**
			 * Let other plugins know that we're about to insert a user.
			 *
			 * @since 0.1
			 *
			 * @param array $civi_contact The CiviCRM contact object.
			 */
			do_action( 'civi_wp_member_sync_before_insert_user', $civi_contact );

			// Create the user.
			$user_id = wp_insert_user( [
				'user_login' => $user_name,
				'user_pass' => $random_password,
				'user_email' => $civi_contact['email'],
				'first_name' => $civi_contact['first_name'],
				'last_name' => $civi_contact['last_name'],
			] );

			// Create a UF Match record if the User was successfully created.
			if ( ! is_wp_error( $user_id ) AND isset( $civi_contact['contact_id'] ) ) {
				$this->ufmatch_create( $civi_contact['contact_id'], $user_id, $civi_contact['email'] );
			}

			/**
			 * Broadcast that we've inserted a user.
			 *
			 * This action fires before the hooks are re-added which can be useful
			 * if callbacks perform actions that in some way update the WordPress
			 * User, for example sending emails via `wp_new_user_notification()`.
			 *
			 * @see wp_new_user_notification()
			 *
			 * @since 0.4.4
			 *
			 * @param array $civi_contact The CiviCRM contact object.
			 * @param int $user_id The numeric ID of the WordPress user.
			 */
			do_action( 'civi_wp_member_sync_post_insert_user', $civi_contact, $user_id );

			// Re-add filters.
			$this->add_filters();

			/**
			 * Let other plugins know that we've inserted a user.
			 *
			 * @since 0.1
			 *
			 * @param array $civi_contact The CiviCRM contact object.
			 * @param int $user_id The numeric ID of the WordPress user.
			 */
			do_action( 'civi_wp_member_sync_after_insert_user', $civi_contact, $user_id );

			// It is a new User.
			$new_user = true;

		}

		// Sanity check.
		if ( is_numeric( $user_id ) AND $user_id ) {

			// Get the WordPress User object.
			$user = get_user_by( 'id', $user_id );

			// Add "New User" flag to User object.
			$user->user_is_new = $new_user;

			// Return WordPress user.
			return $user;

		}

		// Fallback.
		return false;

	}



	/**
	 * Generate a unique username for a WordPress user.
	 *
	 * @since 0.3.7
	 *
	 * @param str $username The previously-generated WordPress username.
	 * @param array $civi_contact The CiviCRM contact data.
	 * @return str $new_username The modified WordPress username.
	 */
	public function unique_username( $username, $civi_contact ) {

		// Bail if this is already unique.
		if ( ! username_exists( $username ) ) {
			return $username;
		}

		// Init flags.
		$count = 1;
		$user_exists = 1;

		do {

			// Construct new username with numeric suffix.
			$new_username = sanitize_title( sanitize_user( $civi_contact['display_name'] . ' ' . $count ) );

			// How did we do?
			$user_exists = username_exists( $new_username );

			// Try the next integer.
			$count++;

		} while ( $user_exists > 0 );

		// --<
		return $new_username;

	}



	/**
	 * Create a link between a WordPress User and a CiviCRM Contact.
	 *
	 * This method optionally allows a Domain ID to be specified.
	 *
	 * @since 0.4.7
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @param integer $user_id The numeric ID of the WordPress User.
	 * @param str $username The WordPress username.
	 * @param integer $domain_id The CiviCRM Domain ID (defaults to current Domain ID).
	 * @return array|bool The UFMatch data on success, or false on failure.
	 */
	public function ufmatch_create( $contact_id, $user_id, $username, $domain_id = '' ) {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return false;
		}

		// Sanity checks.
		if ( ! is_numeric( $contact_id ) OR ! is_numeric( $user_id ) ) {
			return false;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'uf_id' => $user_id,
			'uf_name' => $username,
			'contact_id' => $contact_id,
		];

		// Maybe add Domain ID.
		if ( ! empty( $domain_id ) ) {
			$params['domain_id'] = $domain_id;
		}

		// Create record via API.
		$result = civicrm_api( 'UFMatch', 'create', $params );

		// Log and bail on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			$e = new \Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $result;

	}



	/**
	 * Remove filters (that we know of) that will interfere with creating a WordPress user.
	 *
	 * @since 0.1
	 */
	private function remove_filters() {

		// Get CiviCRM instance.
		$civicrm = civi_wp();

		// Do we have the old-style plugin structure?
		if ( method_exists( $civicrm, 'update_user' ) ) {

			// Remove previous CiviCRM plugin filters.
			remove_action( 'user_register', [ $civicrm, 'update_user' ] );
			remove_action( 'profile_update', [ $civicrm, 'update_user' ] );

		} else {

			// Remove current CiviCRM plugin filters.
			remove_action( 'user_register', [ $civicrm->users, 'update_user' ] );
			remove_action( 'profile_update', [ $civicrm->users, 'update_user' ] );

		}

		// Remove CiviCRM WordPress Profile Sync filters.
		global $civicrm_wp_profile_sync;
		if ( is_object( $civicrm_wp_profile_sync ) ) {
			$civicrm_wp_profile_sync->hooks_wp_remove();
			$civicrm_wp_profile_sync->hooks_bp_remove();
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

		// Get CiviCRM instance.
		$civicrm = civi_wp();

		// Do we have the old-style plugin structure?
		if ( method_exists( $civicrm, 'update_user' ) ) {

			// Re-add previous CiviCRM plugin filters.
			add_action( 'user_register', [ $civicrm, 'update_user' ] );
			add_action( 'profile_update', [ $civicrm, 'update_user' ] );

		} else {

			// Re-add current CiviCRM plugin filters.
			add_action( 'user_register', [ $civicrm->users, 'update_user' ] );
			add_action( 'profile_update', [ $civicrm->users, 'update_user' ] );

		}

		// Re-add CiviCRM WordPress Profile Sync filters.
		global $civicrm_wp_profile_sync;
		if ( is_object( $civicrm_wp_profile_sync ) ) {
			$civicrm_wp_profile_sync->hooks_wp_add();
			$civicrm_wp_profile_sync->hooks_bp_add();
		}

		/**
		 * Let other plugins know that we're adding user actions.
		 *
		 * @since 0.1
		 */
		do_action( 'civi_wp_member_sync_add_filters' );

	}



} // Class ends.



