<?php /*
--------------------------------------------------------------------------------
Civi_WP_Member_Sync_Members Class
--------------------------------------------------------------------------------
*/



/**
 * Class for encapsulating CiviMember functionality.
 */
class Civi_WP_Member_Sync_Members {



	/**
	 * Properties
	 */

	// parent object
	public $parent_obj;



	/**
	 * Initialise this object.
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



	//##########################################################################



	/**
	 * Register hooks when CiviCRM initialises.
	 *
	 * @return void
	 */
	public function initialise() {

		// get our login/logout sync setting
		$login = absint( $this->parent_obj->admin->setting_get( 'login' ) );

		// add hooks if set
		if ( $login === 1 ) {

			// add login check
			add_action( 'wp_login', array( $this, 'sync_to_user' ), 10, 2 );

			// add logout check (can't use 'wp_logout' action, as user no longer exists)
			add_action( 'clear_auth_cookie', array( $this, 'sync_on_logout' ) );

		}

		// get our CiviCRM sync setting
		$civicrm = absint( $this->parent_obj->admin->setting_get( 'civicrm' ) );

		// add hooks if set
		if ( $civicrm === 1 ) {

			// intercept CiviCRM membership add/edit form submission
			add_action( 'civicrm_postProcess', array( $this, 'membership_form_process' ), 10, 2 );

			// intercept before a CiviCRM membership update
			add_action( 'civicrm_pre', array( $this, 'membership_pre_update' ), 10, 4 );

			// intercept a CiviCRM membership update
			add_action( 'civicrm_post', array( $this, 'membership_updated' ), 10, 4 );

		}

	}



	//##########################################################################



	/**
	 * Sync all membership rules.
	 *
	 * @return bool $success True if successful, false otherwise
	 */
	public function sync_all() {

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
	 * @return void
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

		// return result but allow filtering by other plugins
		return apply_filters( 'civi_wp_member_sync_user_should_be_synced', $should_be_synced, $user );

	}



	/**
	 * Sync a user's role based on their membership record.
	 *
	 * @param string $user_login Logged in user's username
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return void
	 */
	public function sync_to_user( $user_login, $user ) {

		// should this user be synced?
		if ( ! $this->user_should_be_synced( $user ) ) return;

		// get CiviCRM contact ID
		$civi_contact_id = $this->parent_obj->users->civi_contact_id_get( $user );

		// bail if we don't have one
		if ( $civi_contact_id === false ) return;

		// get membership
		$membership = $this->membership_get_by_contact_id( $civi_contact_id );

		// update WP user
		$success = $this->parent_obj->admin->rule_apply( $user, $membership );
		// do we care about success?

	}



	/**
	 * Update a WP user role when a CiviCRM membership is updated.
	 *
	 * @param string $op the type of database operation
	 * @param string $objectName the type of object
	 * @param integer $objectId the ID of the object
	 * @param object $objectRef the object
	 * @return void
	 */
	public function membership_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// disable
		return;

		// target our object type
		if ( $objectName != 'Membership' ) return;

	}



	/**
	 * Update a WP user when a CiviCRM membership is updated.
	 *
	 * @param string $op the type of database operation
	 * @param string $objectName the type of object
	 * @param integer $objectId the ID of the object
	 * @param object $objectRef the object
	 * @return void
	 */
	public function membership_updated( $op, $objectName, $objectId, $objectRef ) {

		// target our object type
		if ( $objectName != 'Membership' ) return;

		// kick out if not membership object
		if ( ! ( $objectRef instanceof CRM_Member_BAO_Membership ) ) return;

		// kick out if we don't have a contact ID
		if ( ! isset( $objectRef->contact_id ) ) return;

		// get WordPress user for this contact ID
		$user = $this->parent_obj->users->wp_user_get_by_civi_id( $objectRef->contact_id );

		// if we don't receive a valid user
		if ( ! ( $user instanceof WP_User ) ) {

			// allow plugins to override this step with a filter
			if ( true === apply_filters( 'civi_wp_member_sync_auto_create_wp_user', true ) ) {

				// get CiviCRM contact
				$civi_contact = $this->parent_obj->users->civi_get_contact_by_contact_id( $objectRef->contact_id );

				// bail if something goes wrong
				if ( $civi_contact === false ) return;

				// create a WP user
				$user = $this->parent_obj->users->wp_create_user( $civi_contact );

				// bail if something goes wrong
				if ( ! ( $user instanceof WP_User ) ) return;

			} else {

				// --<
				return;

			}

		}

		// should this user be synced?
		if ( ! $this->user_should_be_synced( $user ) ) return;

		// catch create and edit operations
		if ( $op == 'edit' OR $op == 'create' ) {

			// reformat $objectRef as if it was an API return
			$membership = array(
				'is_error' => 0,
				'values' => array( (array) $objectRef ),
			);

			// update WP user by membership
			$success = $this->parent_obj->admin->rule_apply( $user, $membership );
			// do we care about success?

			// --<
			return;

		}

		// catch delete operation
		if ( $op == 'delete' ) {

			// undo WP user's membership
			$success = $this->parent_obj->admin->rule_undo( $user, $objectRef );
			// do we care about success?

			// --<
			return;

		}

	}



	/**
	 * Update a WordPress user role when a CiviCRM membership is added.
	 *
	 * @param string $formName the CiviCRM form name
	 * @param object $form the CiviCRM form object
	 * @return void
	 */
	public function membership_form_process( $formName, &$form ) {

		// kick out if not membership form
		if ( ! ( $form instanceof CRM_Member_Form_Membership ) ) return;

	}



	/**
	 * Get membership record by CiviCRM contact ID.
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

			// CiviCRM should return a 'values' array with just one element
			return $membership;

		}

		// fallback
		return false;

	}



	/**
	 * Get name of CiviCRM membership type by ID.
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
	 * Get membership types.
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



