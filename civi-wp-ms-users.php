<?php /* 
--------------------------------------------------------------------------------
Civi_WP_Member_Sync_Users Class
--------------------------------------------------------------------------------
*/



/**
 * Class for encapsulating WordPress user functionality
 */
class Civi_WP_Member_Sync_Users {

	/** 
	 * Properties
	 */
	
	// parent object
	public $parent_obj;
	
	
	
	/** 
	 * Initialise this object
	 * 
	 * @param object $parent_obj The parent object
	 * @return object
	 */
	function __construct( $parent_obj ) {
		
		// store reference to parent
		$this->parent_obj = $parent_obj;
	
		// --<
		return $this;
		
	}
	
	
	
	/**
	 * Initialise when CiviCRM initialises
	 * 
	 * @return void
	 */
	public function initialise() {
		
	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * Get WordPress user role
	 * 
	 * @param WP_User $user WP_User object
	 * @return string $role Primary WordPress role for this user
	 */
	public function wp_role_get( $user ) {
	
		// kick out if we don't receive a valid user
		if ( ! is_a( $user, 'WP_User' ) ) return false;
		
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
	 * Set WordPress user role
	 * 
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @param string $old_role Old WordPress role key
	 * @param string $new_role New WordPress role key
	 * @return void
	 */
	public function wp_role_set( $user, $old_role, $new_role ) {
		
		// kick out if we don't receive a valid user
		if ( ! is_a( $user, 'WP_User' ) ) return;
		
		// sanity check params
		if ( empty( $old_role ) ) return;
		if ( empty( $new_role ) ) return;
		
		// Remove old role then add new role, so that we don't inadventently 
		// overwrite multiple roles, for example when bbPress is active
		
		// remove user's existing role
		$user->remove_role( $old_role );
		 
		// add new role
		$user->add_role( $new_role );
		 
	}
	
	
		
	//##########################################################################
	
	
	
	/**
	 * Get a WordPress role name by role key
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
	 * Get all WordPress role names
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
		
		//print_r( $role_names ); die();
		
		// --<
		return $role_names;
		
	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * Add a capability to a WordPress user
	 * 
	 * @param WP_User $user WP_User object
	 * @param string $capability Capability name
	 * @return void
	 */
	public function wp_cap_add( $user, $capability ) {
		
		// kick out if we don't receive a valid user
		if ( ! is_a( $user, 'WP_User' ) ) return;
		
		// sanity check params
		if ( empty( $capability ) ) return;
		
		// does this user have that capability?
		if ( ! $user->has_cap( $capability ) ) {
		
			// no, add it
			$user->add_cap( $capability );
		
		}
	
	}
	
	
		
	/**
	 * Remove a capability from a WordPress user
	 * 
	 * @param WP_User $user WP_User object
	 * @param string $capability Capability name
	 * @return void
	 */
	public function wp_cap_remove( $user, $capability ) {
		
		// kick out if we don't receive a valid user
		if ( ! is_a( $user, 'WP_User' ) ) return;
		
		// sanity check params
		if ( empty( $capability ) ) return;
		
		// does this user have that capability?
		if ( $user->has_cap( $capability ) ) {
		
			// yes, remove it
			$user->remove_cap( $capability );
		
		}
		
	}
	
	
		
	/**
	 * Clear all status capabilities from a WordPress user, since we don't necessarily
	 * know which one the user had before the status change.
	 * 
	 * @param WP_User $user WP_User object
	 * @param string $capability Capability name
	 * @return void
	 */
	public function wp_cap_remove_status( $user, $capability ) {
		
		// kick out if we don't receive a valid user
		if ( ! is_a( $user, 'WP_User' ) ) return;
		
		// sanity check params
		if ( empty( $capability ) ) return;
		
		// get membership status rules
		$status_rules = $this->parent_obj->members->status_rules_get_all();
		
		// sanity checks
		if ( ! is_array( $status_rules ) ) return;
		if ( count( $status_rules ) == 0 ) return;
		
		// get keys
		$status_rule_ids = array_keys( $status_rules );
		
		// loop through them
		foreach( $status_rule_ids AS $status_id ) {
		
			// construct membership status capability name
			$capability_status = $capability . '_' . $status_id;
		
			// does this user have that capability?
			if ( $user->has_cap( $capability_status ) ) {
		
				// yes, remove it
				$user->remove_cap( $capability_status );
		
			}
		
		}
		
	}
	
	
		
	//##########################################################################
	
	
	
	/**
	 * Get a WordPress user for a Civi contact ID
	 * 
	 * @param int $contact_id The numeric CiviCRM contact ID
	 * @return WP_User $user WP_User object for the WordPress user
	 */
	public function wp_user_get_by_civi_id( $contact_id ) {
		
		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return false;
		
		// make sure Civi file is included
		require_once 'CRM/Core/BAO/UFMatch.php';
			
		// search using Civi's logic
		$user_id = CRM_Core_BAO_UFMatch::getUFId( $contact_id );
		
		// kick out if we didn't get one
		if ( empty( $user_id ) ) { return false; }
		
		// get user object
		$user = new WP_User( $user_id );
		
		// --<
		return $user;
		
	}
	
	
	
	/**
	 * Get a Civi contact ID for a WordPress user object
	 * 
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return int $civi_contact_id The numerical CiviCRM contact ID
	 */
	public function civi_contact_id_get( $user ) {
	
		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return false;
		
		// make sure Civi file is included
		require_once 'CRM/Core/BAO/UFMatch.php';
			
		// do initial search
		$civi_contact_id = CRM_Core_BAO_UFMatch::getContactId( $user->ID );
		if ( ! $civi_contact_id ) {
			
			// sync this user
			CRM_Core_BAO_UFMatch::synchronizeUFMatch(
				$user, // user object
				$user->ID, // ID
				$user->user_mail, // unique identifier
				'WordPress', // CMS
				null, // status
				'Individual', // contact type
				null // is_login
			);
			
			// get the Civi contact ID
			$civi_contact_id = CRM_Core_BAO_UFMatch::getContactId( $user->id );
			
			// sanity check
			if ( ! $civi_contact_id ) {
				return false;
			}
		
		}
		
		// --<
		return $civi_contact_id;
		
	}
	
	
	
	//##########################################################################
	
	
	
	/*
	 * Creates a WordPress User given a Civi contact
	 * 
	 * @param array $civi_contact The data for the Civi contact
	 * @return mixed $user WP user object or false on failure
	 */
	public function wp_create_user( $civi_contact ) {
	
		// create username from display name
		$user_name = sanitize_title( $civi_contact['display_name'] );
		
		// check if we have a user with that username
		$user_id = username_exists( $user_name );
		
		/*
		print_r( array(
			'in' => 'wordpress_create_user',
			'civi_contact' => $civi_contact,
			'user_name' => $user_name,
			'user_id' => $user_id,
		) ); die();
		*/
		
		// if not, check against email address
		if ( ! $user_id AND email_exists( $civi_contact['email'] ) == false ) {
			
			// generate a random password
			$random_password = wp_generate_password( 
			
				$length = 12, 
				$include_standard_special_chars = false 
				
			);
			
			// remove filters
			$this->remove_filters();
			
			// allow other plugins to be aware of what we're doing
			do_action( 'civi_wp_member_sync_before_insert_user', $civi_contact );
			
			// create the user
			$user_id = wp_insert_user( array(
			
				'user_login' => $user_name, 
				'user_pass' => $random_password, 
				'user_email' => $civi_contact['email'],
				'first_name' => $civi_contact['first_name'],
				'last_name' => $civi_contact['last_name'],
				
			) );
			
			// re-add filters
			$this->add_filters();
			
			// allow other plugins to be aware of what we've done
			do_action( 'civi_wp_member_sync_after_insert_user', $civi_contact, $user_id );
			
		}
		
		// sanity check
		if ( is_numeric( $user_id ) AND $user_id ) {
		
			// return WP user
			return get_user_by( 'id', $user_id );
			
		}
	
		// return error
		return false;
		
	}
	
	
	
	/*
	 * Remove filters (that we know of) that will interfere with creating a WordPress user
	 * 
	 * @return void
	 */
	private function remove_filters() {
		
		// remove Civi plugin filters
		remove_action( 'user_register', array( civi_wp(), 'update_user' ) );
		remove_action( 'profile_update', array( civi_wp(), 'update_user' ) );
		
		// remove CiviCRM WordPress Profile Sync filters
		global $civicrm_wp_profile_sync;
		if ( is_object( $civicrm_wp_profile_sync ) ) {
			remove_action( 'user_register', array( $civicrm_wp_profile_sync, 'wordpress_contact_updated' ), 100 );
			remove_action( 'profile_update', array( $civicrm_wp_profile_sync, 'wordpress_contact_updated' ), 100 );
		}
	
	}
	
	
	
	/*
	 * Add filters (that we know of) after creating a WordPress user
	 * 
	 * @return void
	 */
	private function add_filters() {
		
		// re-add Civi plugin filters
		add_action( 'user_register', array( civi_wp(), 'update_user' ) );
		add_action( 'profile_update', array( civi_wp(), 'update_user' ) );
		
		// re-add CiviCRM WordPress Profile Sync filters
		global $civicrm_wp_profile_sync;
		if ( is_object( $civicrm_wp_profile_sync ) ) {
			add_action( 'user_register', array( $civicrm_wp_profile_sync, 'wordpress_contact_updated' ), 100, 1 );
			add_action( 'profile_update', array( $civicrm_wp_profile_sync, 'wordpress_contact_updated' ), 100, 1 );
		}
	
	}
	
	
	
} // class ends



