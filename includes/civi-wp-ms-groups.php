<?php

/**
 * CiviCRM WordPress Member Sync "Groups" compatibility class.
 *
 * Class for encapsulating compatibility with the "Groups" plugin.
 *
 * Groups version 2.8.0 changed the way that access restrictions are implemented
 * and switched from "access control based on capabilities" to "access control
 * based on group membership". Furthermore, the legacy functionality does not
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
	 * Plugin (calling) object.
	 *
	 * @since 0.3.9
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;



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
		add_action( 'civi_wp_member_sync_initialised', array( $this, 'initialise' ) );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.3.9
	 */
	public function initialise() {

		// Test for "Groups" plugin on init.
		add_action( 'init', array( $this, 'groups_plugin_hooks' ) );

	}



	//##########################################################################



	/**
	 * Register "Groups" plugin hooks if it's present.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 */
	public function groups_plugin_hooks() {

		// Bail if we don't have the "Groups" plugin.
		if ( ! defined( 'GROUPS_CORE_VERSION' ) ) return;

		// Hook into rule add.
		add_action( 'civi_wp_member_sync_rule_add_capabilities', array( $this, 'groups_add_cap' ) );

		// Hook into rule edit.
		add_action( 'civi_wp_member_sync_rule_edit_capabilities', array( $this, 'groups_edit_cap' ) );

		// Hook into rule delete.
		add_action( 'civi_wp_member_sync_rule_delete_capabilities', array( $this, 'groups_delete_cap' ) );

		// Hook into manual sync process, before sync.
		add_action( 'civi_wp_member_sync_pre_sync_all', array( $this, 'groups_pre_sync' ) );

		// Hook into save post and auto-restrict. (DISABLED)
		//add_action( 'save_post', array( $this, 'groups_intercept_save_post' ), 1, 2 );

	}



	/**
	 * When an association rule is created, add capability to "Groups" plugin.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * @param array $data The association rule data.
	 */
	public function groups_add_cap( $data ) {

		// Add it as "read post" capability.
		$this->groups_read_cap_add( $data['capability'] );

		// Get existing capability.
		$capability = Groups_Capability::read_by_capability( $data['capability'] );

		// Bail if it already exists.
		if ( false !== $capability ) return;

		// Create a new capability.
		$capability_id = Groups_Capability::create( array( 'capability' => $data['capability'] ) );

	}



	/**
	 * When an association rule is edited, edit capability in "Groups" plugin.
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
	 * When an association rule is deleted, delete capability from "Groups" plugin.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * @param array $data The association rule data.
	 */
	public function groups_delete_cap( $data ) {

		// Delete from "read post" capabilities.
		$this->groups_read_cap_delete( $data['capability'] );

		// Get existing.
		$capability = Groups_Capability::read_by_capability( $data['capability'] );

		// Bail if it doesn't exist.
		if ( false === $capability ) return;

		// Delete capability.
		$capability_id = Groups_Capability::delete( $capability->capability_id );

	}



	/**
	 * Add "read post" capability to "Groups" plugin.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * @param array $capability The capability to add.
	 */
	public function groups_read_cap_add( $capability ) {

		// Init with Groups default.
		$default_read_caps = array( Groups_Post_Access::READ_POST_CAPABILITY );

		// Get current.
		$current_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, $default_read_caps );

		// Bail if we have it already.
		if ( in_array( $capability, $current_read_caps ) ) return;

		// Add the new capability.
		$current_read_caps[] = $capability;

		// Resave option.
		Groups_Options::update_option( Groups_Post_Access::READ_POST_CAPABILITIES, $current_read_caps );

	}



	/**
	 * Delete "read post" capability from "Groups" plugin.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * @param array $capability The capability to delete.
	 */
	public function groups_read_cap_delete( $capability ) {

		// Init with Groups default.
		$default_read_caps = array( Groups_Post_Access::READ_POST_CAPABILITY );

		// Get current.
		$current_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, $default_read_caps );

		// Get key if capability is present.
		$key = array_search( $capability, $current_read_caps );

		// Bail if we don't have it.
		if ( $key === false ) return;

		// Delete the capability.
		unset( $current_read_caps[$key] );

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

		// Bail if we're not syncing capabilities.
		if ( $method != 'capabilities' ) return;

		// Get rules.
		$rules = $this->plugin->admin->rules_get_by_method( $method );

		// If we get some.
		if ( $rules !== false AND is_array( $rules ) AND count( $rules ) > 0 ) {

			// Add capability to "Groups" plugin if not already present.
			foreach( $rules AS $rule ) {
				$this->groups_add_cap( $rule );
			}

		}

	}



	/**
	 * Auto-restrict a post based on the post type.
	 *
	 * @since 0.2.3
	 * @since 0.3.9 Moved into this class.
	 *
	 * This is a placeholder in case we want to extend this plugin to handle
	 * automatic content restriction.
	 *
	 * @param int $post_id The numeric ID of the post.
	 * @param object $post The WordPress post object.
	 */
	public function groups_intercept_save_post( $post_id, $post ) {

		// Bail if something went wrong.
		if ( ! is_object( $post ) OR ! isset( $post->post_type ) ) return;

		// Do different things based on the post type.
		switch( $post->post_type ) {

			case 'post':
				// Add your default capabilities.
				Groups_Post_Access::create( array( 'post_id' => $post_id, 'capability' => 'Premium' ) );
				break;

			default:
				// Do other stuff.

		}

	}



} // Class ends.



