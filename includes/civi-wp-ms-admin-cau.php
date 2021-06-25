<?php

/**
 * CiviCRM WordPress Member Sync Admin CAU compatibility class.
 *
 * Class for encapsulating Admin CAU compatibility functionality.
 *
 * @since 0.5
 *
 * @package Civi_WP_Member_Sync
 */
class Civi_WP_Member_Sync_Admin_CAU {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Admin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $admin The admin object.
	 */
	public $admin;

	/**
	 * UFMatch array.
	 *
	 * This is passed into this class in "items_filter_by_type()" through the
	 * CAU "query_args" filter. It's stored so it doesn't need to be re-queried
	 * in other methods in this class.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $ufmatch The UFMatch array.
	 */
	public $ufmatch = [];

	/**
	 * Queried IDs array.
	 *
	 * This is populated with just the Contact IDs that need to be shown in the
	 * rendered rows. It is keyed by User ID so that the relationship between
	 * Users and Contacts can be read.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $query_ids The Query IDs array.
	 */
	public $query_ids = [];

	/**
	 * Queried Memberships array.
	 *
	 * This is populated with just the Memberships that need to be shown in the
	 * rendered rows.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $query_memberships The Queried Memberships array.
	 */
	public $query_memberships = [];



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The calling object.
	 */
	public function __construct( $parent ) {

		// Store references.
		$this->admin = $parent;
		$this->plugin = $parent->plugin;

		// Initialise first.
		add_action( 'cwms/admin/loaded', [ $this, 'initialise' ], 1 );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Is this the back end?
		if ( is_admin() ) {

			// Filter the items in the CiviCRM Admin Utilities "Manage Users" table.
			add_filter( 'cau/single_users/user_table/query_args', [ $this, 'items_filter_by_type' ], 10, 2 );
			add_filter( 'cau/single_users/user_table/query_args', [ $this, 'items_filter_by_status' ], 10, 2 );
			add_filter( 'cau/single_users/user_table/query_args', [ $this, 'items_filter_by_members' ], 10, 2 );

			// Prepare our items when the CiviCRM Admin Utilities "Manage Users" table has done its query.
			add_action( 'cau/single_users/user_table/prepared_items', [ $this, 'items_prepare' ] );

			// Add our views to the CiviCRM Admin Utilities "Manage Users" table.
			add_filter( 'cau/single_users/user_table/get_views', [ $this, 'views_add' ], 10, 2 );

			// Add the Membership Type column to the CiviCRM Admin Utilities "Manage Users" table.
			add_filter( 'cau/single_users/user_table/columns', [ $this, 'add_type_column' ] );
			add_filter( 'cau/single_users/user_table/custom_column', [ $this, 'populate_type_cell' ], 10, 3 );

			// Add the Membership Status column to the CiviCRM Admin Utilities "Manage Users" table.
			add_filter( 'cau/single_users/user_table/columns', [ $this, 'add_status_column' ] );
			add_filter( 'cau/single_users/user_table/custom_column', [ $this, 'populate_status_cell' ], 10, 3 );

		}

	}



	/**
	 * Filter the CiviCRM Admin Utilities "Manage Users" table to include Users
	 * of a specified Membership Type.
	 *
	 * @since 0.5
	 *
	 * @param array $args The existing args passed to WP_User_Query.
	 * @param array $ufmatch The CiviCRM UFMatch query results.
	 * @return array $args The modified args passed to WP_User_Query.
	 */
	public function items_filter_by_type( $args, $ufmatch ) {

		// Always store UFMatch data.
		$this->ufmatch = $ufmatch;

		// Bail if there are no Contact IDs.
		if ( empty( $ufmatch ) ) {
			return $args;
		}

		// Bail if there is no requested Membership Type ID.
		if ( empty( $_REQUEST['cwms_type_id'] ) ) {
			return $args;
		}

		// Grab the Membership Type ID.
		$type_id = intval( $_REQUEST['cwms_type_id'] );

		// Grab the queried Contact IDs.
		$all_contact_ids = wp_list_pluck( $ufmatch, 'contact_id' );

		// Query for the Memberships with the selected Type ID.
		$memberships = $this->plugin->members->memberships_get( 0, 0, [ 'IN' => $all_contact_ids ], $type_id );
		if ( empty( $memberships['values'] ) ) {
			return $args;
		}

		// Extract the Contact IDs that have the Membership.
		$contact_ids = wp_list_pluck( $memberships['values'], 'contact_id' );
		if ( empty( $contact_ids ) ) {
			return $args;
		}

		// Build the list of User IDs with that Membership Type.
		$user_ids = [];
		foreach( $ufmatch AS $item ) {
			if ( in_array( $item['contact_id'], $contact_ids ) ) {
				$user_ids[] = $item['uf_id'];
			}
		}

		// Always overwrite the "include" array.
		$args['include'] = $user_ids;

		// --<
		return $args;

	}



	/**
	 * Filter the CiviCRM Admin Utilities "Manage Users" table to include Users
	 * of a specified Membership Status.
	 *
	 * @since 0.5
	 *
	 * @param array $args The existing args passed to WP_User_Query.
	 * @param array $ufmatch The CiviCRM UFMatch query results.
	 * @return array $args The modified args passed to WP_User_Query.
	 */
	public function items_filter_by_status( $args, $ufmatch ) {

		// Bail if there are no Contact IDs.
		if ( empty( $ufmatch ) ) {
			return $args;
		}

		// Bail if there is no requested Membership Type ID.
		if ( empty( $_REQUEST['cwms_status_id'] ) ) {
			return $args;
		}

		// Grab the Membership Status ID.
		$status_id = intval( $_REQUEST['cwms_status_id'] );

		// Grab the queried Contact IDs.
		$all_contact_ids = wp_list_pluck( $ufmatch, 'contact_id' );

		// Query for the Memberships with the selected Type ID.
		$memberships = $this->plugin->members->memberships_get( 0, 0, [ 'IN' => $all_contact_ids ], 0, $status_id );
		if ( empty( $memberships['values'] ) ) {
			return $args;
		}

		// Extract the Contact IDs that have the Membership.
		$contact_ids = wp_list_pluck( $memberships['values'], 'contact_id' );
		if ( empty( $contact_ids ) ) {
			return $args;
		}

		// Build the list of User IDs with that Membership Type.
		$user_ids = [];
		foreach( $ufmatch AS $item ) {
			if ( in_array( $item['contact_id'], $contact_ids ) ) {
				$user_ids[] = $item['uf_id'];
			}
		}

		// Always overwrite the "include" array.
		$args['include'] = $user_ids;

		// --<
		return $args;

	}



	/**
	 * Filter the CiviCRM Admin Utilities "Manage Users" table to include Users
	 * with or without a Membership.
	 *
	 * @since 0.5
	 *
	 * @param array $args The existing args passed to WP_User_Query.
	 * @param array $ufmatch The CiviCRM UFMatch query results.
	 * @return array $args The modified args passed to WP_User_Query.
	 */
	public function items_filter_by_members( $args, $ufmatch ) {

		// Bail if there are no Contact IDs.
		if ( empty( $ufmatch ) ) {
			return $args;
		}

		// Bail if there is no request.
		if ( empty( $_REQUEST['user_status'] ) ) {
			return $args;
		}

		// Get the views param.
		$member_status = $_REQUEST['user_status'];
		if ( ! in_array( $member_status, [ 'members', 'non_members' ] ) ) {
			return $args;
		}

		// We need all Contact IDs to query by.
		$all_contact_ids = wp_list_pluck( $ufmatch, 'contact_id' );

		// Query for Memberships of all Types for these Contacts.
		$memberships = $this->plugin->members->memberships_get( 0, 0, [ 'IN' => $all_contact_ids ] );
		if ( empty( $memberships['values'] ) ) {
			return $args;
		}

		// Extract the Contact IDs that have a Membership.
		$contact_ids = wp_list_pluck( $memberships['values'], 'contact_id' );

		// Build the lists of User IDs with and without Membership.
		$member_user_ids = [];
		$non_member_user_ids = [];
		foreach( $ufmatch AS $item ) {
			if ( in_array( $item['contact_id'], $contact_ids ) ) {
				$member_user_ids[] = $item['uf_id'];
			} else {
				$non_member_user_ids[] = $item['uf_id'];
			}
		}

		// Overwrite either the "include" or "exclude" array.
		if ( $member_status === 'members' ) {
			$args['include'] = $member_user_ids;
		} else {
			$args['exclude'] = $member_user_ids;
		}

		// --<
		return $args;

	}



	/**
	 * Add Membership Type column to the CiviCRM Admin Utilities "Manage Users" table.
	 *
	 * @since 0.5
	 *
	 * @param array $args The data that the table has queried for.
	 */
	public function items_prepare( $args ) {

		// Bail if there are no items.
		if ( empty( $args['items'] ) ) {
			return;
		}

		// Bail if there are no Contact IDs.
		if ( empty( $args['ufmatch_all'] ) ) {
			return;
		}

		// Get the User IDs.
		$user_ids = array_keys( $args['items'] );

		// Strip out just the Contact IDs that are shown.
		$this->query_ids = [];
		foreach( $args['ufmatch_all'] AS $ufmatch ) {
			if ( ! in_array( $ufmatch['uf_id'], $user_ids ) ) {
				continue;
			}
			$this->query_ids[$ufmatch['uf_id']] = $ufmatch['contact_id'];
		}

		// Query for the Memberships of these Contacts.
		$memberships = $this->plugin->members->memberships_get( 0, 0, [ 'IN' => $this->query_ids ] );

		// Store in a property for use when generating rows.
		$this->query_memberships = [];
		if ( ! empty( $memberships['values'] ) ) {
			$this->query_memberships = $memberships['values'];
		}

	}



	/**
	 * Add views to the CiviCRM Admin Utilities "Manage Users" table.
	 *
	 * @since 0.5
	 *
	 * @param string $url_base The current URL base for view.
	 * @param CAU_Single_Users_List_Table $table The table object.
	 */
	public function views_add( $url_base, $table ) {

		// Default number with CiviCRM Membership.
		$member_count = 0;

		/*
		 * We can't rely on the total number of Users reported by WordPress here
		 * because plugins like BuddyPress may skew the totals in order to
		 * implement their "moderation queue". We use our two calculated values
		 * instead to derive the default number without CiviCRM Membership.
		 */
		$non_member_count = $table->user_counts['in_civicrm'] + $table->user_counts['not_in_civicrm'];

		// Query for all Memberships.
		$memberships = $this->plugin->members->memberships_get( 0, 0 );

		// No need to calculate if there are no Memberships.
		if ( ! empty( $memberships['values'] ) ) {

			// Extract the Contact IDs.
			$contact_ids = wp_list_pluck( $memberships['values'], 'contact_id' );

			// Make unique (there may be multiple Memberships per Contact).
			$unique_contact_ids = array_unique( $contact_ids );

			// No need to calculate if there are no Contacts.
			if ( ! empty( $unique_contact_ids ) ) {

				// Grab the list of all Contact IDs.
				$ufmatch_ids = wp_list_pluck( $this->ufmatch, 'contact_id' );

				// Make unique for safety.
				$unique_ufmatch_ids = array_unique( $ufmatch_ids );

				// Remove the Contacts in UFMatch that have Membership.
				$diff = array_diff( $unique_ufmatch_ids, $unique_contact_ids );

				// Build the list of User IDs with Membership.
				$member_ids = [];
				foreach( $this->ufmatch AS $item ) {
					if ( ! in_array( $item['contact_id'], $diff ) ) {
						$member_ids[] = $item['uf_id'];
					}
				}

				// Assign final count.
				$member_count = count( $member_ids );

				// Assign final count.
				$non_member_count = $non_member_count - $member_count;

			}

		}

		// Get the views param if present.
		$user_status = isset( $_REQUEST['user_status'] ) ? $_REQUEST['user_status'] : '';

		// Include views template.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/cau-user-views.php';

	}



	/**
	 * Add Membership Type column to the CiviCRM Admin Utilities "Manage Users" table.
	 *
	 * @since 0.5
	 *
	 * @param array $columns The Manage Users table columns.
	 * @return array $columns
	 */
	public function add_type_column( $columns = [] ) {

		// Add column header.
		$columns['membership_type'] = _x( 'Membership Type', 'Label for the CiviCRM Admin Utilities "Manage Users" table "Membership Type" column', 'civicrm-wp-member-sync' );

		// --<
		return $columns;

	}



	/**
	 * Return Membership Type for display.
	 *
	 * We could make this a link to filter Users by.
	 *
	 * @since 0.5
	 *
	 * @param string $retval The markup to display.
	 * @param string $column_name The table column name.
	 * @param WP_User $user The WordPress User object.
	 * @return string Membership Type as a link to filter all Users.
	 */
	public function populate_type_cell( $retval = '', $column_name = '', $user = null ) {

		// Only looking for Membership Type column.
		if ( 'membership_type' !== $column_name ) {
			return $retval;
		}

		// Get the Contact ID for this User.
		$contact_id = ! empty( $this->query_ids[$user->ID] ) ? $this->query_ids[$user->ID] : false;
		if ( $contact_id === false ) {
			return $retval;
		}

		// Get the Memberships for this Contact ID.
		$memberships = wp_list_filter( $this->query_memberships, [ 'contact_id' => $contact_id ] );
		if ( empty( $memberships ) ) {
			return $retval;
		}

		// Process them.
		$names = [];
		foreach( $memberships AS $membership ) {

			// Get Membership name.
			$name = $this->plugin->members->membership_name_get_by_id( $membership['membership_type_id'] );

			// Add our param and remove pagination.
			$url = add_query_arg( [ 'cwms_type_id' => $membership['membership_type_id'] ] );
			$url = remove_query_arg( 'paged', $url );

			// Make sure the "user_status" param is removed.
			$url = remove_query_arg( 'user_status', $url );

			$names[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $url ),
				esc_html( $name )
			);

		}

		// Make readable.
		$retval = implode( ', ', $names );

		// --<
		return $retval;

	}



	/**
	 * Add Membership Status column to the CiviCRM Admin Utilities "Manage Users" table.
	 *
	 * @since 0.5
	 *
	 * @param array $columns The Manage Users table columns.
	 * @return array $columns
	 */
	public function add_status_column( $columns = [] ) {

		// Add column header.
		$columns['membership_status'] = _x( 'Membership Status', 'Label for the CiviCRM Admin Utilities "Manage Users" table "Membership Status" column', 'civicrm-wp-member-sync' );

		// --<
		return $columns;

	}



	/**
	 * Return Membership Status for display.
	 *
	 * @since 0.5
	 *
	 * @param string $retval The markup to display.
	 * @param string $column_name The table column name.
	 * @param WP_User $user The WordPress User object.
	 * @return string The Membership Status.
	 */
	public function populate_status_cell( $retval = '', $column_name = '', $user = null ) {

		// Only looking for Membership Type column.
		if ( 'membership_status' !== $column_name ) {
			return $retval;
		}

		// Get the Contact ID for this User.
		$contact_id = ! empty( $this->query_ids[$user->ID] ) ? $this->query_ids[$user->ID] : false;
		if ( $contact_id === false ) {
			return $retval;
		}

		// Get the Memberships for this Contact ID.
		$memberships = wp_list_filter( $this->query_memberships, [ 'contact_id' => $contact_id ] );
		if ( empty( $memberships ) ) {
			return $retval;
		}

		// Process them.
		$names = [];
		foreach( $memberships AS $membership ) {

			// Get Membership name.
			$name = $this->plugin->members->status_name_get_by_id( $membership['status_id'] );

			// Add our param and remove pagination.
			$url = add_query_arg( [ 'cwms_status_id' => $membership['status_id'] ] );
			$url = remove_query_arg( 'paged', $url );

			// Make sure the "user_status" param is removed.
			$url = remove_query_arg( 'user_status', $url );

			$names[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $url ),
				esc_html( $name )
			);

		}

		// Make readable.
		$retval = implode( ', ', $names );

		// --<
		return $retval;

	}



} // Class ends.
