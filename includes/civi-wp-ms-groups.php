<?php
/**
 * "Groups" compatibility class.
 *
 * Handles compatibility with the "Groups" plugin.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.3.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * "Groups" compatibility class.
 *
 * Class for encapsulating compatibility with the "Groups" plugin.
 *
 * Groups version 2.8.0 changed the way that access restrictions are implemented
 * and switched from "access control based on Capabilities" to "access control
 * based on Group Membership". Furthermore, the legacy functionality does not
 * work as expected any more.
 *
 * As a result, the "groups_read_cap_add" and "groups_read_cap_delete" methods
 * used by this class cannot be relied upon any more.
 *
 * @since 0.3.9
 *
 * @package Civi_WP_Member_Sync
 */
class Civi_WP_Member_Sync_Groups {

	/**
	 * Plugin object.
	 *
	 * @since 0.3.9
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * "Groups" plugin enabled flag.
	 *
	 * True if "Groups" is enabled, false otherwise.
	 *
	 * @since 0.4.2
	 * @access public
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * Constructor.
	 *
	 * @since 0.3.9
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Initialise first.
		add_action( 'civi_wp_member_sync_initialised', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.3.9
	 */
	public function initialise() {

		// Test for "Groups" plugin on init.
		add_action( 'init', [ $this, 'register_hooks' ] );

	}

	/**
	 * Getter for the "enabled" flag.
	 *
	 * @since 0.4.2
	 *
	 * @return bool $enabled True if Groups is enabled, false otherwise.
	 */
	public function enabled() {

		// --<
		return $this->enabled;

	}

	// -------------------------------------------------------------------------

	/**
	 * Register "Groups" plugin hooks if it's present.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 */
	public function register_hooks() {

		// Bail if we don't have the "Groups" plugin.
		if ( ! defined( 'GROUPS_CORE_VERSION' ) ) {
			return;
		}

		// Hook into rule add.
		add_action( 'civi_wp_member_sync_rule_add_capabilities', [ $this, 'groups_add_cap' ] );

		// Hook into rule edit.
		add_action( 'civi_wp_member_sync_rule_edit_capabilities', [ $this, 'groups_edit_cap' ] );

		// Hook into rule delete.
		add_action( 'civi_wp_member_sync_rule_delete_capabilities', [ $this, 'groups_delete_cap' ] );

		// Hook into manual sync process, before sync.
		add_action( 'civi_wp_member_sync_pre_sync_all', [ $this, 'groups_pre_sync' ] );

		/*
		// Hook into save post and auto-restrict.
		add_action( 'save_post', [ $this, 'groups_intercept_save_post' ], 1, 2 );
		*/

		// Bail if "Groups" is not version 2.8.0 or greater.
		if ( version_compare( GROUPS_CORE_VERSION, '2.8.0', '<' ) ) {
			return;
		}

		// Set enabled flag.
		$this->enabled = true;

		// Filter script dependencies on the "Add Rule" and "Edit Rule" pages.
		add_filter( 'civi_wp_member_sync_rules_css_dependencies', [ $this->plugin->admin, 'dependencies_css' ], 10, 1 );
		add_filter( 'civi_wp_member_sync_rules_js_dependencies', [ $this->plugin->admin, 'dependencies_js' ], 10, 1 );

		// Declare AJAX handlers.
		add_action( 'wp_ajax_civi_wp_member_sync_get_groups', [ $this, 'search_groups' ], 10 );

		// Hook into Rule Save process.
		add_action( 'civi_wp_member_sync_rule_pre_save', [ $this, 'rule_pre_save' ], 10, 4 );

		// Hook into Rule Apply process.
		add_action( 'civi_wp_member_sync_rule_apply_caps_current', [ $this, 'rule_apply_caps_current' ], 10, 5 );
		add_action( 'civi_wp_member_sync_rule_apply_caps_expired', [ $this, 'rule_apply_caps_expired' ], 10, 5 );
		add_action( 'civi_wp_member_sync_rule_apply_roles_current', [ $this, 'rule_apply_current' ], 10, 4 );
		add_action( 'civi_wp_member_sync_rule_apply_roles_expired', [ $this, 'rule_apply_expired' ], 10, 4 );

		// Hook into Capabilities and Roles lists.
		add_action( 'civi_wp_member_sync_list_caps_th_after_current', [ $this, 'list_current_header' ] );
		add_action( 'civi_wp_member_sync_list_caps_td_after_current', [ $this, 'list_current_row' ], 10, 2 );
		add_action( 'civi_wp_member_sync_list_caps_th_after_expiry', [ $this, 'list_expiry_header' ] );
		add_action( 'civi_wp_member_sync_list_caps_td_after_expiry', [ $this, 'list_expiry_row' ], 10, 2 );
		add_action( 'civi_wp_member_sync_list_roles_th_after_current', [ $this, 'list_current_header' ] );
		add_action( 'civi_wp_member_sync_list_roles_td_after_current', [ $this, 'list_current_row' ], 10, 2 );
		add_action( 'civi_wp_member_sync_list_roles_th_after_expiry', [ $this, 'list_expiry_header' ] );
		add_action( 'civi_wp_member_sync_list_roles_td_after_expiry', [ $this, 'list_expiry_row' ], 10, 2 );

		// Hook into Capabilities and Roles add screens.
		add_action( 'civi_wp_member_sync_cap_add_after_current', [ $this, 'rule_current_add' ], 10, 1 );
		add_action( 'civi_wp_member_sync_cap_add_after_expiry', [ $this, 'rule_expiry_add' ], 10, 1 );
		add_action( 'civi_wp_member_sync_role_add_after_current', [ $this, 'rule_current_add' ], 10, 1 );
		add_action( 'civi_wp_member_sync_role_add_after_expiry', [ $this, 'rule_expiry_add' ], 10, 1 );

		// Hook into Capabilities and Roles edit screens.
		add_action( 'civi_wp_member_sync_cap_edit_after_current', [ $this, 'rule_current_edit' ], 10, 2 );
		add_action( 'civi_wp_member_sync_cap_edit_after_expiry', [ $this, 'rule_expiry_edit' ], 10, 2 );
		add_action( 'civi_wp_member_sync_role_edit_after_current', [ $this, 'rule_current_edit' ], 10, 2 );
		add_action( 'civi_wp_member_sync_role_edit_after_expiry', [ $this, 'rule_expiry_edit' ], 10, 2 );

		// Hook into Rule Simulate process.
		add_action( 'cwms/manual_sync/feedback/th', [ $this, 'simulate_header' ] );
		add_action( 'cwms/manual_sync/feedback/td', [ $this, 'simulate_row' ], 10, 2 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Search for Groups on the "Add Rule" and "Edit Rule" pages.
	 *
	 * We still need to exclude Groups which are present in the "opposite"
	 * select - i.e. exclude current Groups from expiry and vice versa.
	 *
	 * @since 0.4
	 */
	public function search_groups() {

		// Go direct.
		global $wpdb;

		// Init data array.
		$json = [];

		// Since this is an AJAX request, check security.
		$result = check_ajax_referer( 'cwms_ajax_nonce', false, false );
		if ( false === $result ) {
			wp_send_json( $json );
		}

		// Grab search string.
		$search = '';
		$search_raw = filter_input( INPUT_POST, 's' );
		if ( ! empty( $search_raw ) ) {
			$search = trim( wp_unslash( $search_raw ) );
		}

		// Bail if the search is empty.
		if ( empty( $search ) ) {
			wp_send_json( $json );
		}

		// Grab comma-separated excludes.
		$exclude = '';
		$exclude_raw = filter_input( INPUT_POST, 'exclude' );
		if ( ! empty( $exclude_raw ) ) {
			$exclude = trim( wp_unslash( $exclude_raw ) );
		}

		// Parse excludes.
		$excludes = [];
		if ( ! empty( $exclude ) ) {
			$excludes = explode( ',', $exclude );
		}

		// Construct AND clause.
		$and = '';
		if ( ! empty( $excludes ) ) {
			$exclude = implode( ',', array_map( 'intval', array_map( 'trim', $excludes ) ) );
			if ( strlen( $exclude ) > 0 ) {
				$and = 'AND group_id NOT IN (' . $exclude . ')';
			}
		}

		// Do query.
		$group_table = _groups_get_tablename( 'group' );
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$groups = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $group_table WHERE name LIKE %s $and;", $like ) );

		// Add items to output array.
		foreach ( $groups as $group ) {
			$json[] = [
				'id'   => $group->group_id,
				'name' => esc_html( $group->name ),
			];
		}

		// Send data.
		wp_send_json( $json );

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept Rule Apply when method is "capabilities" and Membership is "current".
	 *
	 * We need this method because the two related actions have different
	 * signatures - `civi_wp_member_sync_rule_apply_caps_current` also passes
	 * the Capability, which we don't need.
	 *
	 * @since 0.4
	 *
	 * @param WP_User $user The WordPress User object.
	 * @param int     $membership_type_id The ID of the CiviCRM Membership Type.
	 * @param int     $status_id The ID of the CiviCRM Membership Status.
	 * @param array   $capability The Membership Type Capability added or removed.
	 * @param array   $association_rule The rule used to apply the changes.
	 */
	public function rule_apply_caps_current( $user, $membership_type_id, $status_id, $capability, $association_rule ) {

		// Pass through without Capability param.
		$this->rule_apply_current( $user, $membership_type_id, $status_id, $association_rule );

	}

	/**
	 * Intercept Rule Apply when method is "capabilities" and Membership is "expired".
	 *
	 * We need this method because the two related actions have different
	 * signatures - `civi_wp_member_sync_rule_apply_caps_current` also passes
	 * the Capability, which we don't need.
	 *
	 * @since 0.4
	 *
	 * @param WP_User $user The WordPress User object.
	 * @param int     $membership_type_id The ID of the CiviCRM Membership Type.
	 * @param int     $status_id The ID of the CiviCRM Membership Status.
	 * @param array   $capability The Membership Type Capability added or removed.
	 * @param array   $association_rule The rule used to apply the changes.
	 */
	public function rule_apply_caps_expired( $user, $membership_type_id, $status_id, $capability, $association_rule ) {

		// Pass through without Capability param.
		$this->rule_apply_expired( $user, $membership_type_id, $status_id, $association_rule );

	}

	/**
	 * Intercept Rule Apply when Membership is "current".
	 *
	 * @since 0.4
	 *
	 * @param WP_User $user The WordPress User object.
	 * @param int     $membership_type_id The ID of the CiviCRM Membership Type.
	 * @param int     $status_id The ID of the CiviCRM Membership Status.
	 * @param array   $association_rule The rule used to apply the changes.
	 */
	public function rule_apply_current( $user, $membership_type_id, $status_id, $association_rule ) {

		// Remove the User from the expired Groups.
		if ( ! empty( $association_rule['expiry_groups'] ) ) {
			foreach ( $association_rule['expiry_groups'] as $group_id ) {
				$this->group_member_delete( $user->ID, $group_id );
			}
		}

		// Add the User to the current Groups.
		if ( ! empty( $association_rule['current_groups'] ) ) {
			foreach ( $association_rule['current_groups'] as $group_id ) {
				$this->group_member_add( $user->ID, $group_id );
			}
		}

	}

	/**
	 * Intercept Rule Apply when Membership is "expired".
	 *
	 * @since 0.4
	 *
	 * @param WP_User $user The WordPress User object.
	 * @param int     $membership_type_id The ID of the CiviCRM Membership Type.
	 * @param int     $status_id The ID of the CiviCRM Membership Status.
	 * @param array   $association_rule The rule used to apply the changes.
	 */
	public function rule_apply_expired( $user, $membership_type_id, $status_id, $association_rule ) {

		// Remove the User from the current Groups.
		if ( ! empty( $association_rule['current_groups'] ) ) {
			foreach ( $association_rule['current_groups'] as $group_id ) {
				$this->group_member_delete( $user->ID, $group_id );
			}
		}

		// Add the User to the expired Groups.
		if ( ! empty( $association_rule['expiry_groups'] ) ) {
			foreach ( $association_rule['expiry_groups'] as $group_id ) {
				$this->group_member_add( $user->ID, $group_id );
			}
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Add a WordPress User to a "Groups" Group.
	 *
	 * @since 0.4
	 *
	 * @param int $user_id The ID of the WordPress User to add to the Group.
	 * @param int $group_id The ID of the "Groups" Group.
	 * @return bool $success True on success, false otherwise.
	 */
	public function group_member_add( $user_id, $group_id ) {

		// Bail if they are already a Group Member.
		if ( Groups_User_Group::read( $user_id, $group_id ) ) {
			return true;
		}

		// Add User to Group.
		$success = Groups_User_Group::create( [
			'user_id'  => $user_id,
			'group_id' => $group_id,
		] );

		// Maybe log on failure?
		if ( ! $success ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method'    => __METHOD__,
				'message'   => esc_html__( 'Could not add user to group.', 'civicrm-wp-member-sync' ),
				'user_id'   => $user_id,
				'group_id'  => $group_id,
				'backtrace' => $trace,
			], true ) );
		}

		// --<
		return $success;

	}

	/**
	 * Delete a WordPress User from a "Groups" Group.
	 *
	 * @since 0.4
	 *
	 * @param int $user_id The ID of the WordPress User to delete from the Group.
	 * @param int $group_id The ID of the "Groups" Group.
	 * @return bool $success True on success, false otherwise.
	 */
	public function group_member_delete( $user_id, $group_id ) {

		// Bail if they are not a Group Member.
		if ( ! Groups_User_Group::read( $user_id, $group_id ) ) {
			return true;
		}

		// Delete User from Group.
		$success = Groups_User_Group::delete( $user_id, $group_id );

		// Maybe log on failure?
		if ( ! $success ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method'    => __METHOD__,
				'message'   => esc_html__( 'Could not delete user from group.', 'civicrm-wp-member-sync' ),
				'user_id'   => $user_id,
				'group_id'  => $group_id,
				'backtrace' => $trace,
			], true ) );
		}

		// --<
		return $success;

	}

	// -------------------------------------------------------------------------

	/**
	 * Amend the association rule that is about to be saved.
	 *
	 * @since 0.4
	 *
	 * @param array $rule The new or updated association rule.
	 * @param array $data The complete set of association rule.
	 * @param str   $mode The mode ('add' or 'edit').
	 * @param str   $method The sync method.
	 */
	public function rule_pre_save( $rule, $data, $mode, $method ) {

		// Get the "current" Groups.
		$current = filter_input( INPUT_POST, 'cwms_groups_select_current', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $current ) ) {

			// Sanitise array items.
			array_walk( $current, function( &$item ) {
				$item = (int) trim( $item );
			});

		} else {
			$current = [];
		}

		// Get the "expiry" Groups.
		$expiry = filter_input( INPUT_POST, 'cwms_groups_select_expiry', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $expiry ) ) {

			// Sanitise array items.
			array_walk( $expiry, function( &$item ) {
				$item = (int) trim( $item );
			});

		} else {
			$expiry = [];
		}

		// Add to the rule.
		$rule['current_groups'] = $current;
		$rule['expiry_groups'] = $expiry;

		// --<
		return $rule;

	}

	// -------------------------------------------------------------------------

	/**
	 * Show the Current Group header.
	 *
	 * @since 0.4
	 */
	public function list_current_header() {

		// Echo markup.
		echo '<th>' . esc_html__( 'Current "Groups" Group(s)', 'civicrm-wp-member-sync' ) . '</th>';

	}

	/**
	 * Show the Current Groups.
	 *
	 * @since 0.4
	 *
	 * @param int   $key The current key (type ID).
	 * @param array $item The current item.
	 */
	public function list_current_row( $key, $item ) {

		// Build list.
		$markup = '&mdash;';
		if ( ! empty( $item['current_groups'] ) ) {
			$markup = $this->markup_get_list_items( $item['current_groups'] );
		}

		// Echo markup.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<td>' . $markup . '</td>';

	}

	/**
	 * Show the Current Group.
	 *
	 * @since 0.4
	 *
	 * @param array $status_rules The status rules.
	 */
	public function rule_current_add( $status_rules ) {

		// Include template file.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/groups-add-current.php';

	}

	/**
	 * Show the Current Group.
	 *
	 * @since 0.4
	 *
	 * @param array $status_rules The status rules.
	 * @param array $selected_rule The rule being edited.
	 */
	public function rule_current_edit( $status_rules, $selected_rule ) {

		// Build options.
		$options_html = '';
		if ( ! empty( $selected_rule['current_groups'] ) ) {
			$options_html = $this->markup_get_options( $selected_rule['current_groups'] );
		}

		// Include template file.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/groups-edit-current.php';

	}

	// -------------------------------------------------------------------------

	/**
	 * Show the Expired Group header.
	 *
	 * @since 0.4
	 */
	public function list_expiry_header() {

		// Echo markup.
		echo '<th>' . esc_html__( 'Expiry "Groups" Group(s)', 'civicrm-wp-member-sync' ) . '</th>';

	}

	/**
	 * Show the Expired Groups.
	 *
	 * @since 0.4
	 *
	 * @param int   $key The current key (type ID).
	 * @param array $item The current item.
	 */
	public function list_expiry_row( $key, $item ) {

		// Build list.
		$markup = '&mdash;';
		if ( ! empty( $item['expiry_groups'] ) ) {
			$markup = $this->markup_get_list_items( $item['expiry_groups'] );
		}

		// Echo markup.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<td>' . $markup . '</td>';

	}

	/**
	 * Show the Expired Group.
	 *
	 * @since 0.4
	 *
	 * @param array $status_rules The status rules.
	 */
	public function rule_expiry_add( $status_rules ) {

		// Include template file.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/groups-add-expiry.php';

	}

	/**
	 * Show the Expired Group.
	 *
	 * @since 0.4
	 *
	 * @param array $status_rules The status rules.
	 * @param array $selected_rule The rule being edited.
	 */
	public function rule_expiry_edit( $status_rules, $selected_rule ) {

		// Build options.
		$options_html = '';
		if ( ! empty( $selected_rule['expiry_groups'] ) ) {
			$options_html = $this->markup_get_options( $selected_rule['expiry_groups'] );
		}

		// Include template file.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/groups-edit-expiry.php';

	}

	// -------------------------------------------------------------------------

	/**
	 * Show the Simulate header.
	 *
	 * @since 0.5
	 */
	public function simulate_header() {

		// Echo markup.
		echo '<th>' . esc_html__( '"Groups" Group(s)', 'civicrm-wp-member-sync' ) . '</th>';

	}

	/**
	 * Show the Groups.
	 *
	 * @since 0.5
	 *
	 * @param int   $key The current key (type ID).
	 * @param array $item The current item.
	 */
	public function simulate_row( $key, $item ) {

		// Build list.
		$markup = '&mdash;';
		if ( 'current' === $item['flag'] && ! empty( $item['association_rule']['current_groups'] ) ) {
			$markup = $this->markup_get_list_items( $item['association_rule']['current_groups'] );
		}
		if ( 'expired' === $item['flag'] && ! empty( $item['association_rule']['expiry_groups'] ) ) {
			$markup = $this->markup_get_list_items( $item['association_rule']['expiry_groups'] );
		}

		// Echo markup.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<td>' . $markup . '</td>';

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the markup for a pseudo-list generated from a list of Groups data.
	 *
	 * @since 0.4
	 *
	 * @param array $group_ids The array of Group IDs.
	 */
	public function markup_get_list_items( $group_ids ) {

		// Init options.
		$options_html = '';
		$options = [];

		if ( ! empty( $group_ids ) ) {

			// Get the Groups.
			$groups = Groups_Group::get_groups( [
				'order_by' => 'name',
				'order'    => 'ASC',
				'include'  => $group_ids,
			] );

			// Add options to build array.
			foreach ( $groups as $group ) {
				$options[] = esc_html( $group->name );
			}

			// Construct markup.
			$options_html = implode( "<br />\n", $options );

		}

		// --<
		return $options_html;

	}

	/**
	 * Get the markup for options generated from a list of Groups data.
	 *
	 * @since 0.4
	 *
	 * @param array $group_ids The array of Group IDs.
	 */
	public function markup_get_options( $group_ids ) {

		// Init options.
		$options_html = '';
		$options = [];

		if ( ! empty( $group_ids ) ) {

			// Get the Groups.
			$groups = Groups_Group::get_groups( [
				'order_by' => 'name',
				'order'    => 'ASC',
				'include'  => $group_ids,
			] );

			// Add options to build array.
			foreach ( $groups as $group ) {
				$options[] = '<option value="' . esc_attr( $group->group_id ) . '" selected="selected">' . esc_html( $group->name ) . '</option>';
			}

			// Construct markup.
			$options_html = implode( "\n", $options );

		}

		// --<
		return $options_html;

	}

	// -------------------------------------------------------------------------

	/**
	 * When an association rule is created, add Capability to "Groups" plugin.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * @param array $data The association rule data.
	 */
	public function groups_add_cap( $data ) {

		// Add it as "read post" Capability.
		$this->groups_read_cap_add( $data['capability'] );

		// Get existing Capability.
		$capability = Groups_Capability::read_by_capability( $data['capability'] );
		if ( false !== $capability ) {
			return;
		}

		// Create a new Capability.
		$capability_id = Groups_Capability::create( [ 'capability' => $data['capability'] ] );

	}

	/**
	 * When an association rule is edited, edit Capability in "Groups" plugin.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * @param array $data The association rule data.
	 */
	public function groups_edit_cap( $data ) {

		// Same as add.
		$this->groups_add_cap( $data );

	}

	/**
	 * When an association rule is deleted, delete Capability from "Groups" plugin.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * @param array $data The association rule data.
	 */
	public function groups_delete_cap( $data ) {

		// Delete from "read post" Capabilities.
		$this->groups_read_cap_delete( $data['capability'] );

		// Get existing.
		$capability = Groups_Capability::read_by_capability( $data['capability'] );
		if ( false === $capability ) {
			return;
		}

		// Delete Capability.
		$capability_id = Groups_Capability::delete( $capability->capability_id );

	}

	/**
	 * Add "read post" Capability to "Groups" plugin.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * @param array $capability The Capability to add.
	 */
	public function groups_read_cap_add( $capability ) {

		// Init with Groups default.
		$default_read_caps = [ Groups_Post_Access::READ_POST_CAPABILITY ];

		// Get current.
		$current_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, $default_read_caps );
		if ( in_array( $capability, $current_read_caps ) ) {
			return;
		}

		// Add the new Capability.
		$current_read_caps[] = $capability;

		// Resave option.
		Groups_Options::update_option( Groups_Post_Access::READ_POST_CAPABILITIES, $current_read_caps );

	}

	/**
	 * Delete "read post" Capability from "Groups" plugin.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * @param array $capability The Capability to delete.
	 */
	public function groups_read_cap_delete( $capability ) {

		// Init with Groups default.
		$default_read_caps = [ Groups_Post_Access::READ_POST_CAPABILITY ];

		// Get current.
		$current_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, $default_read_caps );

		// Get key if Capability is present.
		$key = array_search( $capability, $current_read_caps );
		if ( false === $key ) {
			return;
		}

		// Delete the Capability.
		unset( $current_read_caps[ $key ] );

		// Resave option.
		Groups_Options::update_option( Groups_Post_Access::READ_POST_CAPABILITIES, $current_read_caps );

	}

	/**
	 * Before a manual sync, make sure "Groups" plugin is in sync.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 */
	public function groups_pre_sync() {

		// Get sync method.
		$method = $this->plugin->admin->setting_get_method();

		// Bail if we're not syncing Capabilities.
		if ( 'capabilities' !== $method ) {
			return;
		}

		// Get rules.
		$rules = $this->plugin->admin->rules_get_by_method( $method );

		// If we get some.
		if ( false !== $rules && is_array( $rules ) && count( $rules ) > 0 ) {

			// Add Capability to "Groups" plugin if not already present.
			foreach ( $rules as $rule ) {
				$this->groups_add_cap( $rule );
			}

		}

	}

	/**
	 * Auto-restrict a Post based on the Post Type.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * This is a placeholder in case we want to extend this plugin to handle
	 * automatic content restriction.
	 *
	 * @param int    $post_id The numeric ID of the Post.
	 * @param object $post The WordPress Post object.
	 */
	public function groups_intercept_save_post( $post_id, $post ) {

		// Bail if something went wrong.
		if ( ! is_object( $post ) || ! isset( $post->post_type ) ) {
			return;
		}

		// Do different things based on the Post Type.
		switch ( $post->post_type ) {

			case 'post':
				// Add your default Capabilities.
				Groups_Post_Access::create( [
					'post_id'    => $post_id,
					'capability' => 'Premium',
				] );
				break;

			default:
				// Do other stuff.

		}

	}

}
