<?php
/**
 * BuddyPress compatibility class.
 *
 * Handles admin functionality.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.4.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress compatibility class.
 *
 * Class for encapsulating compatibility with the BuddyPress plugin.
 *
 * @since 0.4.7
 *
 * @package Civi_WP_Member_Sync
 */
class Civi_WP_Member_Sync_BuddyPress {

	/**
	 * Plugin object.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var Civi_WP_Member_Sync
	 */
	public $plugin;

	/**
	 * BuddyPress plugin enabled flag.
	 *
	 * True if BuddyPress is enabled, false otherwise.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * Constructor.
	 *
	 * @since 0.4.7
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Initialise.
		add_action( 'civi_wp_member_sync_initialised', [ $this, 'initialise' ], 20 );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.4.7
	 */
	public function initialise() {

		// Test for BuddyPress plugin on init.
		add_action( 'init', [ $this, 'register_hooks' ] );

	}

	/**
	 * Test if BuddyPress plugin is active.
	 *
	 * @since 0.4.7
	 *
	 * @return bool|object False if BuddyPress could not be found, BuddyPress reference if successful.
	 */
	public function is_active() {

		// Bail if no BuddyPress init function.
		if ( ! function_exists( 'buddypress' ) ) {
			return false;
		}

		// Bail if BuddyPress Groups component is not active.
		if ( ! bp_is_active( 'groups' ) ) {
			return false;
		}

		// Set enabled flag.
		$this->enabled = true;

		// Try and init BuddyPress.
		return buddypress();

	}

	/**
	 * Getter for the "enabled" flag.
	 *
	 * @since 0.4.7
	 *
	 * @return bool $enabled True if BuddyPress is enabled, false otherwise.
	 */
	public function enabled() {

		// --<
		return $this->enabled;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Register BuddyPress plugin hooks if it's present.
	 *
	 * @since 0.4.7
	 */
	public function register_hooks() {

		// Bail if we don't have the BuddyPress plugin.
		if ( ! $this->is_active() ) {
			return;
		}

		// Filter script dependencies on the "Add Rule" and "Edit Rule" pages.
		add_filter( 'civi_wp_member_sync_rules_css_dependencies', [ $this->plugin->admin, 'dependencies_css' ], 10, 1 );
		add_filter( 'civi_wp_member_sync_rules_js_dependencies', [ $this->plugin->admin, 'dependencies_js' ], 10, 1 );

		// Declare AJAX handlers.
		add_action( 'wp_ajax_civi_wp_member_sync_get_bp_groups', [ $this, 'search_groups' ], 10 );

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

	// -----------------------------------------------------------------------------------

	/**
	 * Search for BuddyPress Groups on the "Add Rule" and "Edit Rule" pages.
	 *
	 * We still need to exclude Groups which are present in the "opposite"
	 * select - i.e. exclude current Groups from expiry and vice versa.
	 *
	 * @since 0.4.7
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
		$search     = '';
		$search_raw = filter_input( INPUT_POST, 's' );
		if ( ! empty( $search_raw ) ) {
			$search = trim( wp_unslash( $search_raw ) );
		}

		// Bail if there's no search string.
		if ( empty( $search ) ) {
			wp_send_json( $json );
		}

		// Grab comma-separated excludes.
		$exclude     = '';
		$exclude_raw = filter_input( INPUT_POST, 'exclude' );
		if ( ! empty( $exclude_raw ) ) {
			$exclude = trim( wp_unslash( $exclude_raw ) );
		}

		// Parse excludes.
		$excludes = [];
		if ( ! empty( $exclude ) ) {
			$excludes = explode( ',', $exclude );
		}

		// Build query to get Groups this User can see for this search.
		$args = [
			'user_id'         => is_super_admin() ? 0 : bp_loggedin_user_id(),
			'search_terms'    => $search,
			'show_hidden'     => true,
			'populate_extras' => false,
			'exclude'         => $excludes,
		];

		// Do the query.
		$groups = groups_get_groups( $args );

		// Add items to output array.
		$json = [];
		foreach ( $groups['groups'] as $group ) {
			$json[] = [
				'id'   => $group->id,
				'name' => stripslashes( $group->name ),
			];
		}

		// Send data.
		wp_send_json( $json );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Intercept Rule Apply when method is "capabilities" and Membership is "current".
	 *
	 * We need this method because the two related actions have different
	 * signatures - `civi_wp_member_sync_rule_apply_caps_current` also passes
	 * the Capability, which we don't need.
	 *
	 * @since 0.4.7
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
	 * @since 0.4.7
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
	 * @since 0.4.7
	 *
	 * @param WP_User $user The WordPress User object.
	 * @param int     $membership_type_id The ID of the CiviCRM Membership Type.
	 * @param int     $status_id The ID of the CiviCRM Membership Status.
	 * @param array   $association_rule The rule used to apply the changes.
	 */
	public function rule_apply_current( $user, $membership_type_id, $status_id, $association_rule ) {

		// Remove the User from the expired Groups.
		if ( ! empty( $association_rule['expiry_buddypress'] ) ) {
			foreach ( $association_rule['expiry_buddypress'] as $group_id ) {
				$this->group_member_delete( $user->ID, $group_id );
			}
		}

		// Add the User to the current Groups.
		if ( ! empty( $association_rule['current_buddypress'] ) ) {
			foreach ( $association_rule['current_buddypress'] as $group_id ) {
				$this->group_member_add( $user->ID, $group_id );
			}
		}

	}

	/**
	 * Intercept Rule Apply when Membership is "expired".
	 *
	 * @since 0.4.7
	 *
	 * @param WP_User $user The WordPress User object.
	 * @param int     $membership_type_id The ID of the CiviCRM Membership Type.
	 * @param int     $status_id The ID of the CiviCRM Membership Status.
	 * @param array   $association_rule The rule used to apply the changes.
	 */
	public function rule_apply_expired( $user, $membership_type_id, $status_id, $association_rule ) {

		// Remove the User from the current Groups.
		if ( ! empty( $association_rule['current_buddypress'] ) ) {
			foreach ( $association_rule['current_buddypress'] as $group_id ) {
				$this->group_member_delete( $user->ID, $group_id );
			}
		}

		// Add the User to the expired Groups.
		if ( ! empty( $association_rule['expiry_buddypress'] ) ) {
			foreach ( $association_rule['expiry_buddypress'] as $group_id ) {
				$this->group_member_add( $user->ID, $group_id );
			}
		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Add a WordPress User to a BuddyPress Group.
	 *
	 * @since 0.4.7
	 *
	 * @param int $user_id The ID of the WordPress User to add to the Group.
	 * @param int $group_id The ID of the BuddyPress Group.
	 * @return bool $success True on success, false otherwise.
	 */
	public function group_member_add( $user_id, $group_id ) {

		// Bail if USer is already a Member.
		if ( groups_is_user_member( $user_id, $group_id ) ) {
			return true;
		}

		// Add to BuddyPress Group.
		$success = groups_join_group( $group_id, $user_id );

		// Maybe log on failure?
		if ( ! $success ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => esc_html__( 'Could not add user to group.', 'civicrm-wp-member-sync' ),
				'user_id'   => $user_id,
				'group_id'  => $group_id,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
		}

		// --<
		return $success;

	}

	/**
	 * Delete a WordPress User from a BuddyPress Group.
	 *
	 * We cannot use 'groups_remove_member()' because the logged in User may not
	 * pass the 'bp_is_item_admin()' check in that function.
	 *
	 * @since 0.4.7
	 *
	 * @param int $user_id The ID of the WordPress User to delete from the Group.
	 * @param int $group_id The ID of the BuddyPress Group.
	 * @return bool $success True on success, false otherwise.
	 */
	public function group_member_delete( $user_id, $group_id ) {

		// Bail if User is not a Member.
		if ( ! groups_is_user_member( $user_id, $group_id ) ) {
			return false;
		}

		// Set up object.
		$member = new BP_Groups_Member( $user_id, $group_id );

		/**
		 * Fires the BuddyPress action.
		 *
		 * @since 0.4.7
		 *
		 * @param int $group_id The numeric ID of the Group.
		 * @param int $user_id The numeric ID of the User.
		 */
		do_action( 'groups_remove_member', $group_id, $user_id );

		// Remove Member.
		$success = $member->remove();

		// --<
		return $success;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Amend the association rule that is about to be saved.
	 *
	 * @since 0.4.7
	 *
	 * @param array $rule The new or updated association rule.
	 * @param array $data The complete set of association rule.
	 * @param str   $mode The mode ('add' or 'edit').
	 * @param str   $method The sync method.
	 */
	public function rule_pre_save( $rule, $data, $mode, $method ) {

		// Get the "current" Groups.
		$current = filter_input( INPUT_POST, 'cwms_buddypress_select_current', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $current ) ) {

			// Sanitise array items.
			array_walk(
				$current,
				function( &$item ) {
					$item = (int) trim( $item );
				}
			);

		} else {
			$current = [];
		}

		// Get the "expiry" Groups.
		$expiry = filter_input( INPUT_POST, 'cwms_buddypress_select_expiry', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $expiry ) ) {

			// Sanitise array items.
			array_walk(
				$expiry,
				function( &$item ) {
					$item = (int) trim( $item );
				}
			);

		} else {
			$expiry = [];
		}

		// Add to the rule.
		$rule['current_buddypress'] = $current;
		$rule['expiry_buddypress']  = $expiry;

		// --<
		return $rule;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Show the Current Group header.
	 *
	 * @since 0.4.7
	 */
	public function list_current_header() {

		// Echo markup.
		echo '<th>' . esc_html__( 'Current BuddyPress Group(s)', 'civicrm-wp-member-sync' ) . '</th>';

	}

	/**
	 * Show the Current BuddyPress Groups.
	 *
	 * @since 0.4.7
	 *
	 * @param int   $key The current key (type ID).
	 * @param array $item The current item.
	 */
	public function list_current_row( $key, $item ) {

		// Build list.
		$markup = '&mdash;';
		if ( ! empty( $item['current_buddypress'] ) ) {
			$markup = $this->markup_get_list_items( $item['current_buddypress'] );
		}

		// Echo markup.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<td>' . $markup . '</td>';

	}

	/**
	 * Show the Current Group.
	 *
	 * @since 0.4.7
	 *
	 * @param array $status_rules The status rules.
	 */
	public function rule_current_add( $status_rules ) {

		// Include template file.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/buddypress-add-current.php';

	}

	/**
	 * Show the Current Group.
	 *
	 * @since 0.4.7
	 *
	 * @param array $status_rules The status rules.
	 * @param array $selected_rule The rule being edited.
	 */
	public function rule_current_edit( $status_rules, $selected_rule ) {

		// Build options.
		$options_html = '';
		if ( ! empty( $selected_rule['current_buddypress'] ) ) {
			$options_html = $this->markup_get_options( $selected_rule['current_buddypress'] );
		}

		// Include template file.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/buddypress-edit-current.php';

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Show the Expired Group header.
	 *
	 * @since 0.4.7
	 */
	public function list_expiry_header() {

		// Echo markup.
		echo '<th>' . esc_html__( 'Expiry BuddyPress Group(s)', 'civicrm-wp-member-sync' ) . '</th>';

	}

	/**
	 * Show the Expired BuddyPress Groups.
	 *
	 * @since 0.4.7
	 *
	 * @param int   $key The current key (type ID).
	 * @param array $item The current item.
	 */
	public function list_expiry_row( $key, $item ) {

		// Build list.
		$markup = '&mdash;';
		if ( ! empty( $item['expiry_buddypress'] ) ) {
			$markup = $this->markup_get_list_items( $item['expiry_buddypress'] );
		}

		// Echo markup.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<td>' . $markup . '</td>';

	}

	/**
	 * Show the Expired Group.
	 *
	 * @since 0.4.7
	 *
	 * @param array $status_rules The status rules.
	 */
	public function rule_expiry_add( $status_rules ) {

		// Include template file.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/buddypress-add-expiry.php';

	}

	/**
	 * Show the Expired Group.
	 *
	 * @since 0.4.7
	 *
	 * @param array $status_rules The status rules.
	 * @param array $selected_rule The rule being edited.
	 */
	public function rule_expiry_edit( $status_rules, $selected_rule ) {

		// Build options.
		$options_html = '';
		if ( ! empty( $selected_rule['expiry_buddypress'] ) ) {
			$options_html = $this->markup_get_options( $selected_rule['expiry_buddypress'] );
		}

		// Include template file.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/buddypress-edit-expiry.php';

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Show the Simulate header.
	 *
	 * @since 0.5
	 */
	public function simulate_header() {

		// Echo markup.
		echo '<th>' . esc_html__( 'BuddyPress Group(s)', 'civicrm-wp-member-sync' ) . '</th>';

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
		if ( 'current' === $item['flag'] && ! empty( $item['association_rule']['current_buddypress'] ) ) {
			$markup = $this->markup_get_list_items( $item['association_rule']['current_buddypress'] );
		}
		if ( 'expired' === $item['flag'] && ! empty( $item['association_rule']['expiry_buddypress'] ) ) {
			$markup = $this->markup_get_list_items( $item['association_rule']['expiry_buddypress'] );
		}

		// Echo markup.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<td>' . $markup . '</td>';

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the markup for a pseudo-list generated from a list of Groups data.
	 *
	 * @since 0.4.7
	 *
	 * @param array $group_ids The array of Group IDs.
	 */
	public function markup_get_list_items( $group_ids ) {

		// Init options.
		$options_html = '';
		$options      = [];

		if ( ! empty( $group_ids ) ) {

			// Build args.
			$args = [
				'order_by' => 'name',
				'order'    => 'ASC',
				'include'  => $group_ids,
			];

			// Get the Groups.
			$groups = groups_get_groups( $args );

			// Add options to build array.
			foreach ( $groups['groups'] as $group ) {
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
	 * @since 0.4.7
	 *
	 * @param array $group_ids The array of Group IDs.
	 */
	public function markup_get_options( $group_ids ) {

		// Init options.
		$options_html = '';
		$options      = [];

		if ( ! empty( $group_ids ) ) {

			// Build args.
			$args = [
				'order_by'    => 'name',
				'order'       => 'ASC',
				'show_hidden' => true,
				'include'     => $group_ids,
			];

			// Get the Groups.
			$groups = groups_get_groups( $args );

			// Add options to build array.
			foreach ( $groups['groups'] as $group ) {
				$options[] = '<option value="' . esc_attr( $group->id ) . '" selected="selected">' . esc_html( $group->name ) . '</option>';
			}

			// Construct markup.
			$options_html = implode( "\n", $options );

		}

		// --<
		return $options_html;

	}

}
