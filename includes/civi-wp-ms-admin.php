<?php
/**
 * Admin class.
 *
 * Handles admin functionality.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 *
 * Class for encapsulating admin functionality.
 *
 * @since 0.1
 *
 * @package Civi_WP_Member_Sync
 */
class Civi_WP_Member_Sync_Admin {

	/**
	 * Plugin object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Civi_WP_Member_Sync
	 */
	public $plugin;

	/**
	 * CiviCRM Admin Utilities compatibility object.
	 *
	 * @since 0.5
	 * @access public
	 * @var Civi_WP_Member_Sync_Admin_CAU
	 */
	public $cau;

	/**
	 * Parent Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	public $parent_page;

	/**
	 * Settings Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	public $settings_page;

	/**
	 * Manual Sync Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	public $sync_page;

	/**
	 * List Association Rules Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	public $rules_list_page;

	/**
	 * Add or edit Association Rule Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	public $rule_add_edit_page;

	/**
	 * Plugin version.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	public $plugin_version;

	/**
	 * Plugin settings.
	 *
	 * @since 0.1
	 * @access public
	 * @var array
	 */
	public $settings = [];

	/**
	 * Form error messages.
	 *
	 * @since 0.1
	 * @access public
	 * @var array
	 */
	public $error_strings;

	/**
	 * Errors in current form submission.
	 *
	 * @since 0.1
	 * @access public
	 * @var array
	 */
	public $errors;

	/**
	 * When Manual Sync runs, limit processing to this number per batch.
	 *
	 * @since 0.3.7
	 * @access public
	 * @var integer
	 */
	public $batch_count = 25;

	/**
	 * Select2 Javascript flag.
	 *
	 * True if Select2 library is enqueued, false otherwise.
	 *
	 * @since 0.4.2
	 * @access public
	 * @var bool
	 */
	public $select2 = false;

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

		// Initialise first.
		add_action( 'civi_wp_member_sync_initialised', [ $this, 'initialise' ], 1 );

	}

	/**
	 * Perform activation tasks.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// Store version for later reference.
		$this->store_version();

		// Store default settings option only if it does not exist.
		if ( 'fgffgs' === $this->option_get( 'civi_wp_member_sync_settings', 'fgffgs' ) ) {
			$this->option_save( 'civi_wp_member_sync_settings', $this->settings_get_default() );
		}

	}

	/**
	 * Perform deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {
		// We delete our options in uninstall.php.
	}

	/**
	 * Test if this plugin is network activated.
	 *
	 * @since 0.2.7
	 *
	 * @return bool $is_network_active True if network activated, false otherwise.
	 */
	public function is_network_activated() {

		// Only need to test once.
		static $is_network_active;
		if ( isset( $is_network_active ) ) {
			return $is_network_active;
		}

		// If not multisite, set flag and bail.
		if ( ! is_multisite() ) {
			$is_network_active = false;
			return $is_network_active;
		}

		// Make sure plugin file is included when outside admin.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Get path from 'plugins' directory to this plugin.
		$this_plugin = plugin_basename( CIVI_WP_MEMBER_SYNC_PLUGIN_FILE );

		// Test if network active.
		$is_network_active = is_plugin_active_for_network( $this_plugin );

		// --<
		return $is_network_active;

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Init settings.
		$this->initialise_settings();

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Register hooks.
		$this->register_hooks();

		/**
		 * Fires when this class is loaded.
		 *
		 * @since 0.5
		 */
		do_action( 'cwms/admin/loaded' );

	}

	/**
	 * Initialise settings.
	 *
	 * @since 0.5
	 */
	public function initialise_settings() {

		// Load plugin version.
		$this->plugin_version = $this->option_get( 'civi_wp_member_sync_version', false );

		// Perform any upgrade tasks.
		$this->upgrade_tasks();

		// Upgrade version if needed.
		if ( CIVI_WP_MEMBER_SYNC_VERSION !== $this->plugin_version ) {
			$this->store_version();
		}

		// Load settings array.
		$this->settings = $this->option_get( 'civi_wp_member_sync_settings', $this->settings );

		// Settings upgrade tasks.
		$this->upgrade_settings();

	}

	/**
	 * Include files.
	 *
	 * @since 0.5
	 */
	public function include_files() {

		// Include CiviCRM Admin Utilities compatibility class.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-admin-cau.php';

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.5
	 */
	public function setup_objects() {

		// Instantiate CiviCRM Admin Utilities compatibility object.
		$this->cau = new Civi_WP_Member_Sync_Admin_CAU( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Is this the back end?
		if ( is_admin() ) {

			// Multisite and network-activated?
			if ( $this->is_network_activated() ) {
				add_action( 'network_admin_menu', [ $this, 'admin_menu' ], 30 );
			} else {
				add_action( 'admin_menu', [ $this, 'admin_menu' ], 25 );
			}

		}

	}

	/**
	 * Perform upgrade tasks.
	 *
	 * @since 0.2.7
	 */
	public function upgrade_tasks() {

		// If the current version is less than 0.2.7 and we're upgrading to 0.2.7+.
		if (
			version_compare( $this->plugin_version, '0.2.7', '<' ) &&
			version_compare( CIVI_WP_MEMBER_SYNC_VERSION, '0.2.7', '>=' )
		) {

			// Check if this plugin is network-activated.
			if ( $this->is_network_activated() ) {

				// Get existing settings from local options.
				$settings = get_option( 'civi_wp_member_sync_settings', [] );
				if ( ! array_key_exists( 'data', $settings ) ) {
					return;
				}

				// Migrate to network settings.
				$this->settings = $settings;
				$this->settings_save();

				// Delete local options.
				delete_option( 'civi_wp_member_sync_version' );
				delete_option( 'civi_wp_member_sync_settings' );

			}

		}

	}

	/**
	 * Utility to do stuff when a settings upgrade is required.
	 *
	 * @since 0.3.6
	 */
	public function upgrade_settings() {

		// The "types" setting may not exist.
		if ( ! $this->setting_exists( 'types' ) ) {

			// Add them from defaults.
			$settings = $this->settings_get_default();
			$this->setting_set( 'types', $settings['types'] );
			$this->settings_save();

		}

	}

	/**
	 * Store the plugin version.
	 *
	 * @since 0.1
	 */
	public function store_version() {

		// Store version.
		$this->option_save( 'civi_wp_member_sync_version', CIVI_WP_MEMBER_SYNC_VERSION );

	}

	/**
	 * Builds the error strings for admin screens.
	 *
	 * @since 0.5.5
	 */
	public function error_strings_build() {

		// Define error strings.
		$this->error_strings = [
			'type'           => esc_html__( 'Please select a CiviCRM Membership Type', 'civicrm-wp-member-sync' ),
			'current-role'   => esc_html__( 'Please select a WordPress Current Role', 'civicrm-wp-member-sync' ),
			'current-status' => esc_html__( 'Please select a Current Status', 'civicrm-wp-member-sync' ),
			'expire-status'  => esc_html__( 'Please select an Expire Status', 'civicrm-wp-member-sync' ),
			'expire-role'    => esc_html__( 'Please select a WordPress Expiry Role', 'civicrm-wp-member-sync' ),
			'clash-status'   => esc_html__( 'You can not have the same Status Rule registered as both "Current" and "Expired"', 'civicrm-wp-member-sync' ),
		];

		/**
		 * Allows error strings to be filtered.
		 *
		 * @since 0.3.9
		 *
		 * @param array $error_strings The existing array of error strings.
		 */
		$this->error_strings = apply_filters( 'civi_wp_member_sync_error_strings', $this->error_strings );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Add this plugin's Settings Page to the WordPress admin menu.
	 *
	 * @since 0.1
	 */
	public function admin_menu() {

		// We must be network admin in multisite.
		if ( is_multisite() && ! is_super_admin() ) {
			return false;
		}

		// Check User permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Multisite?
		if ( $this->is_network_activated() ) {

			// Add settings page to the Network Settings menu.
			$this->parent_page = add_submenu_page(
				'settings.php',
				__( 'CiviCRM Member Sync: Settings', 'civicrm-wp-member-sync' ), // Page title.
				__( 'CiviCRM Member Sync', 'civicrm-wp-member-sync' ), // Menu title.
				'manage_options', // Required caps.
				'civi_wp_member_sync_parent', // Slug name.
				[ $this, 'page_settings' ] // Callback.
			);

		} else {

			// Add the settings page to the CiviCRM menu.
			$this->parent_page = add_submenu_page(
				'CiviCRM', // Parent slug.
				__( 'CiviCRM Member Sync: Settings', 'civicrm-wp-member-sync' ), // Page title.
				__( 'Member Sync', 'civicrm-wp-member-sync' ), // Menu title.
				'manage_options', // Required caps.
				'civi_wp_member_sync_parent', // Slug name.
				[ $this, 'page_settings' ] // Callback.
			);

		}

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->parent_page, [ $this, 'admin_css' ] );
		add_action( 'admin_head-' . $this->parent_page, [ $this, 'admin_head' ], 50 );

		// Add settings page.
		$this->settings_page = add_submenu_page(
			'civi_wp_member_sync_parent', // Parent slug.
			__( 'CiviCRM Member Sync: Settings', 'civicrm-wp-member-sync' ), // Page title.
			__( 'Settings', 'civicrm-wp-member-sync' ), // Menu title.
			'manage_options', // Required caps.
			'civi_wp_member_sync_settings', // Slug name.
			[ $this, 'page_settings' ] // Callback.
		);

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->settings_page, [ $this, 'admin_css' ] );
		add_action( 'admin_head-' . $this->settings_page, [ $this, 'admin_head' ], 50 );
		add_action( 'admin_head-' . $this->settings_page, [ $this, 'admin_menu_highlight' ], 50 );

		// Add manual sync page.
		$this->sync_page = add_submenu_page(
			'civi_wp_member_sync_parent', // Parent slug.
			__( 'CiviCRM Member Sync: Manual Sync', 'civicrm-wp-member-sync' ), // Page title.
			__( 'Manual Sync', 'civicrm-wp-member-sync' ), // Menu title.
			'manage_options', // Required caps.
			'civi_wp_member_sync_manual_sync', // Slug name.
			[ $this, 'page_manual_sync' ] // Callback.
		);

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->sync_page, [ $this, 'admin_js_sync_page' ] );
		add_action( 'admin_print_styles-' . $this->sync_page, [ $this, 'admin_css' ] );
		add_action( 'admin_print_styles-' . $this->sync_page, [ $this, 'admin_css_sync_page' ] );
		add_action( 'admin_head-' . $this->sync_page, [ $this, 'admin_head' ], 50 );
		add_action( 'admin_head-' . $this->sync_page, [ $this, 'admin_menu_highlight' ], 50 );

		// Add rules listing page.
		$this->rules_list_page = add_submenu_page(
			'civi_wp_member_sync_parent', // Parent slug.
			__( 'CiviCRM Member Sync: List Rules', 'civicrm-wp-member-sync' ), // Page title.
			__( 'List Rules', 'civicrm-wp-member-sync' ), // Menu title.
			'manage_options', // Required caps.
			'civi_wp_member_sync_list', // Slug name.
			[ $this, 'page_rules_list' ] // Callback.
		);

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->rules_list_page, [ $this, 'admin_js_list_page' ] );
		add_action( 'admin_print_styles-' . $this->rules_list_page, [ $this, 'admin_css' ] );
		add_action( 'admin_head-' . $this->rules_list_page, [ $this, 'admin_head' ], 50 );
		add_action( 'admin_head-' . $this->rules_list_page, [ $this, 'admin_menu_highlight' ], 50 );

		// Add rules page.
		$this->rule_add_edit_page = add_submenu_page(
			'civi_wp_member_sync_parent', // Parent slug.
			__( 'CiviCRM Member Sync: Association Rule', 'civicrm-wp-member-sync' ), // Page title.
			__( 'Association Rule', 'civicrm-wp-member-sync' ), // Menu title.
			'manage_options', // Required caps.
			'civi_wp_member_sync_rules', // Slug name.
			[ $this, 'page_rule_add_edit' ] // Callback.
		);

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->rule_add_edit_page, [ $this, 'admin_js_rules_page' ] );
		add_action( 'admin_print_styles-' . $this->rule_add_edit_page, [ $this, 'admin_css_rules_page' ] );
		add_action( 'admin_head-' . $this->rule_add_edit_page, [ $this, 'admin_head' ], 50 );
		add_action( 'admin_head-' . $this->rule_add_edit_page, [ $this, 'admin_menu_highlight' ], 50 );

		// Try and update options.
		$saved = $this->settings_update_router();

	}

	/**
	 * Tell WordPress to highlight the plugin's menu item, regardless of which
	 * actual admin screen we are on.
	 *
	 * @since 0.1
	 *
	 * @global string $plugin_page
	 * @global array $submenu
	 */
	public function admin_menu_highlight() {

		global $plugin_page, $submenu_file;

		// Define subpages.
		$subpages = [
			'civi_wp_member_sync_settings',
			'civi_wp_member_sync_manual_sync',
			'civi_wp_member_sync_list',
			'civi_wp_member_sync_rules',
		];

		// This tweaks the Settings subnav menu to show only one menu item.
		if ( in_array( $plugin_page, $subpages ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$plugin_page = 'civi_wp_member_sync_parent';
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$submenu_file = 'civi_wp_member_sync_parent';
		}

	}

	/**
	 * Initialise plugin help.
	 *
	 * @since 0.1
	 */
	public function admin_head() {

		// Get screen object.
		$screen = get_current_screen();

		// Use method in this class.
		$this->admin_help( $screen );

	}

	/**
	 * Enqueue common stylesheet for this plugin's admin pages.
	 *
	 * @since 0.1
	 *
	 * @param array $dependencies The CSS script dependencies.
	 */
	public function admin_css( $dependencies = [] ) {

		// Add admin stylesheet.
		wp_enqueue_style(
			'civi_wp_member_sync_admin_css',
			plugins_url( 'assets/css/civi-wp-ms.css', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			$dependencies,
			CIVI_WP_MEMBER_SYNC_VERSION, // Version.
			'all' // Media.
		);

	}

	/**
	 * Enqueue stylesheet for this plugin's "Manual Sync" page.
	 *
	 * @since 0.2.8
	 */
	public function admin_css_sync_page() {

		// Add manual sync stylesheet.
		wp_enqueue_style(
			'civi_wp_member_sync_manual_sync_css',
			plugins_url( 'assets/css/civi-wp-ms-sync.css', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			false,
			CIVI_WP_MEMBER_SYNC_VERSION, // Version.
			'all' // Media.
		);

	}

	/**
	 * Enqueue required scripts on the Manual Sync page.
	 *
	 * @since 0.2.8
	 */
	public function admin_js_sync_page() {

		// Enqueue javascript.
		wp_enqueue_script(
			'civi_wp_member_sync_sync_js',
			plugins_url( 'assets/js/civi-wp-ms-sync.js', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			[ 'jquery', 'jquery-ui-core', 'jquery-ui-progressbar' ],
			CIVI_WP_MEMBER_SYNC_VERSION, // Version.
			true // In footer.
		);

		// Init localisation.
		$localisation = [
			'total'    => esc_html__( '{{total}} memberships to sync...', 'civicrm-wp-member-sync' ),
			'current'  => esc_html__( 'Processing memberships {{from}} to {{to}}', 'civicrm-wp-member-sync' ),
			'complete' => esc_html__( 'Processing memberships {{from}} to {{to}} complete', 'civicrm-wp-member-sync' ),
			'done'     => esc_html__( 'All done!', 'civicrm-wp-member-sync' ),
		];

		// Init settings.
		$settings = [
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'total_memberships' => $this->plugin->members->memberships_get_count(),
			'batch_count'       => $this->setting_get_batch_count(),
		];

		// Localisation array.
		$vars = [
			'localisation' => $localisation,
			'settings'     => $settings,
		];

		// Localise the WordPress way.
		wp_localize_script(
			'civi_wp_member_sync_sync_js',
			'CiviCRM_WP_Member_Sync_Settings',
			$vars
		);

	}

	/**
	 * Enqueue stylesheets for this plugin's "Add Rule" and "Edit Rule" page.
	 *
	 * @since 0.4
	 */
	public function admin_css_rules_page() {

		// Define base dependencies.
		$dependencies = [];

		/**
		 * Allows CSS dependencies to be injected.
		 *
		 * @since 0.4
		 *
		 * @param array $dependencies The existing dependencies.
		 */
		$dependencies = apply_filters( 'civi_wp_member_sync_rules_css_dependencies', $dependencies );

		// Add common CSS.
		$this->admin_css( $dependencies );

	}

	/**
	 * Enqueue required scripts on the Add Rule and Edit Rule pages.
	 *
	 * @since 0.1
	 */
	public function admin_js_rules_page() {

		// Define base dependencies.
		$dependencies = [ 'jquery', 'jquery-form' ];

		/**
		 * Filters the Javascript dependencies for the Rules screen.
		 *
		 * @since 0.4
		 *
		 * @param array $dependencies The existing dependencies.
		 */
		$dependencies = apply_filters( 'civi_wp_member_sync_rules_js_dependencies', $dependencies );

		// Add JavaScript plus dependencies.
		wp_enqueue_script(
			'civi_wp_member_sync_rules_js',
			plugins_url( 'assets/js/civi-wp-ms-rules.js', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			$dependencies,
			CIVI_WP_MEMBER_SYNC_VERSION, // Version.
			true // In footer.
		);

		// Set defaults.
		$vars = [
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'cwms_ajax_nonce' ),
			'method'     => $this->setting_get_method(),
			'mode'       => 'add',
			'select2'    => 'no',
			'groups'     => 'no',
			'buddypress' => 'no',
		];

		// Maybe override select2.
		if ( in_array( 'civi_wp_member_sync_select2_js', $dependencies ) ) {
			$vars['select2'] = 'yes';
		}

		// Maybe override Groups.
		if ( $this->plugin->groups->enabled() ) {
			$vars['groups'] = 'yes';
		}

		// Maybe override BuddyPress.
		if ( $this->plugin->buddypress->enabled() ) {
			$vars['buddypress'] = 'yes';
		}

		// Get mode query var.
		$mode     = '';
		$mode_raw = filter_input( INPUT_GET, 'mode' );
		if ( ! empty( $mode_raw ) ) {
			$mode = trim( wp_unslash( $mode_raw ) );
		}

		// Maybe override mode.
		if ( 'edit' === $mode ) {

			// Get Type ID query var.
			$type_id     = 0;
			$type_id_raw = filter_input( INPUT_GET, 'type_id' );
			if ( ! empty( $type_id_raw ) ) {
				$type_id = (int) trim( wp_unslash( $type_id_raw ) );
			}

			// We need a Type ID for edit mode.
			if ( ! empty( $type_id ) && is_numeric( $type_id ) ) {
				$vars['mode'] = 'edit';
			}

		}

		// Localize our script.
		wp_localize_script(
			'civi_wp_member_sync_rules_js',
			'CiviCRM_WP_Member_Sync_Rules',
			$vars
		);

	}

	/**
	 * Enqueue required scripts on the List Rules page.
	 *
	 * @since 0.4.2
	 */
	public function admin_js_list_page() {

		// Define base dependencies.
		$dependencies = [ 'jquery', 'jquery-form' ];

		/**
		 * Filters the Javascript dependencies for the List screen.
		 *
		 * @since 0.4.2
		 *
		 * @param array $dependencies The existing dependencies.
		 */
		$dependencies = apply_filters( 'civi_wp_member_sync_list_js_dependencies', $dependencies );

		// Add JavaScript plus dependencies.
		wp_enqueue_script(
			'civi_wp_member_sync_list_js',
			plugins_url( 'assets/js/civi-wp-ms-list.js', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			$dependencies,
			CIVI_WP_MEMBER_SYNC_VERSION, // Version.
			true // In footer.
		);

		// Set defaults.
		$vars = [
			'method'          => $this->setting_get_method(),
			'dialog_text'     => esc_html__( 'Delete this Association Rule?', 'civicrm-wp-member-sync' ),
			'dialog_text_all' => esc_html__( 'Delete all Association Rules?', 'civicrm-wp-member-sync' ),
		];

		// Localize our script.
		wp_localize_script(
			'civi_wp_member_sync_list_js',
			'CiviCRM_WP_Member_Sync_List',
			$vars
		);

	}

	/**
	 * Adds help copy to admin page in WP3.3+.
	 *
	 * @since 0.1
	 *
	 * @param object $screen The existing WordPress screen object.
	 * @return object $screen The amended WordPress screen object.
	 */
	public function admin_help( $screen ) {

		// Init suffix.
		$page = '';

		// The page ID is different in multisite.
		if ( $this->is_network_activated() ) {
			$page = '-network';
		}

		// Init page IDs.
		$pages = [
			$this->settings_page . $page,
			$this->sync_page . $page,
			$this->rules_list_page . $page,
			$this->rule_add_edit_page . $page,
		];

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages ) ) {
			return $screen;
		}

		// Add a tab - we can add more later.
		$screen->add_help_tab( [
			'id'      => 'civi_wp_member_sync',
			'title'   => esc_html__( 'CiviCRM Member Sync', 'civicrm-wp-member-sync' ),
			'content' => $this->get_help(),
		] );

		// --<
		return $screen;

	}

	/**
	 * Get help text.
	 *
	 * @since 0.1
	 *
	 * @return string $help Help formatted as HTML.
	 */
	public function get_help() {

		// Stub help text, to be developed further.
		$help = '<p>' . esc_html__( 'For further information about using CiviCRM Member Sync, please refer to the README.md that comes with this plugin.', 'civicrm-wp-member-sync' ) . '</p>';

		// --<
		return $help;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Filter CSS dependencies on the "Add Rule" and "Edit Rule" pages.
	 *
	 * @since 0.4
	 * @since 0.4.2 Moved into this class.
	 *
	 * @param array $dependencies The existing dependencies.
	 * @return array $dependencies The modified dependencies.
	 */
	public function dependencies_css( $dependencies ) {

		// Store instance in static variable.
		static $dependencies_done = false;
		if ( true === $dependencies_done ) {
			return $dependencies;
		}

		// Define our handle.
		$handle = 'civi_wp_member_sync_select2_css';

		// Register Select2 styles.
		wp_register_style(
			$handle,
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' ),
			null,
			CIVI_WP_MEMBER_SYNC_VERSION // Version.
		);

		// Enqueue styles.
		wp_enqueue_style( $handle );

		// Add to dependencies.
		$dependencies[] = $handle;

		// Set flags.
		$dependencies_done = true;
		$this->select2     = true;

		// --<
		return $dependencies;

	}

	/**
	 * Filter script dependencies on the "Add Rule" and "Edit Rule" pages.
	 *
	 * @since 0.4
	 * @since 0.4.2 Moved into this class.
	 *
	 * @param array $dependencies The existing dependencies.
	 * @return array $dependencies The modified dependencies.
	 */
	public function dependencies_js( $dependencies ) {

		// Store instance in static variable.
		static $dependencies_done = false;
		if ( true === $dependencies_done ) {
			return $dependencies;
		}

		// Define our handle.
		$handle = 'civi_wp_member_sync_select2_js';

		// Register Select2.
		wp_register_script(
			$handle,
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js' ),
			[ 'jquery' ],
			CIVI_WP_MEMBER_SYNC_VERSION, // Version.
			true // In footer.
		);

		// Enqueue script.
		wp_enqueue_script( $handle );

		// Add to dependencies.
		$dependencies[] = $handle;

		// Set flags.
		$dependencies_done = true;
		$this->select2     = true;

		// --<
		return $dependencies;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Show settings page.
	 *
	 * @since 0.1
	 */
	public function page_settings() {

		// Check User permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->page_get_urls();

		// Get our sync method.
		$method = $this->setting_get_method();

		// Get all schedules.
		$schedules = $this->plugin->schedule->intervals_get();

		// Get our sync settings.
		$login    = (int) $this->setting_get( 'login' );
		$civicrm  = (int) $this->setting_get( 'civicrm' );
		$schedule = (int) $this->setting_get( 'schedule' );

		// Get our interval setting.
		$interval = $this->setting_get( 'interval' );

		// Get our types setting.
		$types = (int) $this->setting_get( 'types' );

		// Check if CiviCRM Admin Utilities has been installed.
		$cau_present = $this->cau_activated();

		// Check the version of CiviCRM Admin Utilities is okay.
		$cau_version_ok = $this->cau_version_ok();

		// Check if CiviCRM Admin Utilities has been configured.
		$cau_configured = $this->cau_configured();

		// Get Settings page link.
		$cau_link = $this->cau_page_get_url();

		// Build error strings for admin screens.
		$this->error_strings_build();

		// Get updated query var.
		$updated     = '';
		$updated_raw = filter_input( INPUT_GET, 'updated' );
		if ( ! empty( $updated_raw ) ) {
			$updated = trim( wp_unslash( $updated_raw ) );
		}

		// Include template file.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/settings.php';

	}

	/**
	 * Show manual sync page.
	 *
	 * @since 0.1
	 */
	public function page_manual_sync() {

		// Check User permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->page_get_urls();

		// Build error strings for admin screens.
		$this->error_strings_build();

		// Include template file.
		include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/manual-sync.php';

	}

	/**
	 * Show rules list page.
	 *
	 * @since 0.1
	 */
	public function page_rules_list() {

		// Check User permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->page_get_urls();

		// Get method.
		$method = $this->setting_get_method();

		// Get data.
		$all_data = $this->setting_get( 'data' );

		// Get data for this sync method.
		$data = ( isset( $all_data[ $method ] ) ) ? $all_data[ $method ] : [];

		// Get all Membership Types.
		$membership_types = $this->plugin->members->types_get_all();

		// Assume we don't have all types.
		$have_all_types = false;

		// Override if we have all types populated.
		if ( count( $data ) === count( $membership_types ) ) {
			$have_all_types = true;
		}

		// Get sync rule.
		$sync_rule     = '';
		$sync_rule_raw = filter_input( INPUT_GET, 'syncrule' );
		if ( ! empty( $sync_rule_raw ) ) {
			$sync_rule = trim( wp_unslash( $sync_rule_raw ) );
		}

		// Build error strings for admin screens.
		$this->error_strings_build();

		// Construct messages based on error code(s).
		$error_messages = '';
		if ( ! empty( $this->errors ) ) {
			$errors = [];
			foreach ( $this->errors as $error_code ) {
				$errors[] = $this->error_strings[ $error_code ];
			}
			$error_messages = implode( '<br>', $errors );
		}

		// Include per method.
		if ( 'roles' === $method ) {
			include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/list_roles.php';
		} else {
			include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/list_caps.php';
		}

	}

	/**
	 * Decide whether to show add or edit page.
	 *
	 * @since 0.1
	 */
	public function page_rule_add_edit() {

		// Check User permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get mode query var.
		$mode     = 'add';
		$mode_raw = filter_input( INPUT_GET, 'mode' );
		if ( ! empty( $mode_raw ) ) {
			$mode = trim( wp_unslash( $mode_raw ) );
		}

		// Bail if mode is missing.
		if ( empty( $mode ) ) {
			return;
		}

		// Bail if mode is not one of our values.
		if ( 'add' !== $mode && 'edit' !== $mode ) {
			return;
		}

		// Are we populating the form?
		if ( 'edit' === $mode ) {

			// Get Type ID query var.
			$type_id     = 0;
			$type_id_raw = filter_input( INPUT_GET, 'type_id' );
			if ( ! empty( $type_id_raw ) ) {
				$type_id = (int) trim( wp_unslash( $type_id_raw ) );
			}

			// Bail if there's no Type ID.
			if ( empty( $type_id ) || ! is_numeric( $type_id ) ) {
				return;
			}

		}

		// Build error strings for admin screens.
		$this->error_strings_build();

		// Route by mode.
		if ( 'add' === $mode ) {
			$this->page_rule_add();
		} elseif ( 'edit' === $mode ) {
			$this->page_rule_edit();
		}

	}

	/**
	 * Show add rule page.
	 *
	 * @since 0.1
	 */
	private function page_rule_add() {

		// Get admin page URLs.
		$urls = $this->page_get_urls();

		// Get all Membership Types.
		$membership_types = $this->plugin->members->types_get_all();

		// Get all Membership Status rules.
		$status_rules = $this->plugin->members->status_rules_get_all();

		// Get method.
		$method = $this->setting_get_method();

		// Get rules.
		$rules = $this->rules_get_by_method( $method );

		// If we get some.
		if ( false !== $rules && is_array( $rules ) && count( $rules ) > 0 ) {

			// Get used Membership Type IDs.
			$type_ids = array_keys( $rules );

			// Loop and remove from Membership_types array.
			foreach ( $type_ids as $type_id ) {
				if ( isset( $membership_types[ $type_id ] ) ) {
					unset( $membership_types[ $type_id ] );
				}
			}

		}

		// Build error strings for admin screens.
		$this->error_strings_build();

		// Construct messages based on error code(s).
		$error_messages = '';
		if ( ! empty( $this->errors ) ) {
			$errors = [];
			foreach ( $this->errors as $error_code ) {
				$errors[] = $this->error_strings[ $error_code ];
			}
			$error_messages = implode( '<br>', $errors );
		}

		// Do we need Roles?
		if ( 'roles' === $method ) {

			// Get filtered Roles.
			$roles = $this->plugin->users->wp_role_names_get_all();

			// Include template file.
			include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-role-add.php';

		} else {

			// Include template file.
			include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-cap-add.php';

		}

	}

	/**
	 * Show edit rule page.
	 *
	 * @since 0.1
	 */
	private function page_rule_edit() {

		// Get admin page URLs.
		$urls = $this->page_get_urls();

		// Get all Membership Types.
		$membership_types = $this->plugin->members->types_get_all();

		// Get all Membership Status rules.
		$status_rules = $this->plugin->members->status_rules_get_all();

		// Get method.
		$method = $this->setting_get_method();

		// Get requested Membership Type ID.
		// TODO: Protect against malformed or missing Type ID.
		$civi_member_type_id = 0;
		$type_id_raw         = filter_input( INPUT_GET, 'type_id' );
		if ( ! empty( $type_id_raw ) ) {
			$civi_member_type_id = (int) trim( wp_unslash( $type_id_raw ) );
		}

		// Get rule by type.
		$selected_rule = $this->rule_get_by_type( $civi_member_type_id, $method );

		// Set vars for populating form.
		$current_rule = $selected_rule['current_rule'];
		$expiry_rule  = $selected_rule['expiry_rule'];

		// Get rules.
		$rules = $this->rules_get_by_method( $method );

		// If we get some.
		if ( false !== $rules && is_array( $rules ) && count( $rules ) > 0 ) {

			// Get used Membership Type IDs.
			$type_ids = array_keys( $rules );

			// Loop and remove from membership_types array.
			foreach ( $type_ids as $type_id ) {
				if ( isset( $membership_types[ $type_id ] ) && (int) $civi_member_type_id !== (int) $type_id ) {
					unset( $membership_types[ $type_id ] );
				}
			}

		}

		// Build error strings for admin screens.
		$this->error_strings_build();

		// Construct messages based on error code(s).
		$error_messages = '';
		if ( ! empty( $this->errors ) ) {
			$errors = [];
			foreach ( $this->errors as $error_code ) {
				$errors[] = $this->error_strings[ $error_code ];
			}
			$error_messages = implode( '<br>', $errors );
		}

		// Do we need Roles?
		if ( 'roles' === $method ) {

			// Get filtered Roles.
			$roles = $this->plugin->users->wp_role_names_get_all();

			// Get stored Roles.
			$current_wp_role = $selected_rule['current_wp_role'];
			$expired_wp_role = $selected_rule['expired_wp_role'];

			// Include template file.
			include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-role-edit.php';

		} else {

			// Include template file.
			include CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-cap-edit.php';

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get admin page URLs.
	 *
	 * @since 0.1
	 *
	 * @return array $admin_urls The array of admin page URLs.
	 */
	public function page_get_urls() {

		// Only calculate once.
		if ( isset( $this->urls ) ) {
			return $this->urls;
		}

		// Init return.
		$this->urls = [];

		// Multisite?
		if ( $this->is_network_activated() ) {

			// Get admin page URLs via our adapted method.
			$this->urls['settings']    = $this->network_menu_page_url( 'civi_wp_member_sync_settings', false );
			$this->urls['manual_sync'] = $this->network_menu_page_url( 'civi_wp_member_sync_manual_sync', false );
			$this->urls['list']        = $this->network_menu_page_url( 'civi_wp_member_sync_list', false );
			$this->urls['rules']       = $this->network_menu_page_url( 'civi_wp_member_sync_rules', false );

		} else {

			// Get admin page URLs.
			$this->urls['settings']    = menu_page_url( 'civi_wp_member_sync_settings', false );
			$this->urls['manual_sync'] = menu_page_url( 'civi_wp_member_sync_manual_sync', false );
			$this->urls['list']        = menu_page_url( 'civi_wp_member_sync_list', false );
			$this->urls['rules']       = menu_page_url( 'civi_wp_member_sync_rules', false );

		}

		// --<
		return $this->urls;

	}

	/**
	 * Get the url to access a particular menu page based on the slug it was registered with.
	 * If the slug hasn't been registered properly no url will be returned.
	 *
	 * @since 0.1
	 *
	 * @param string $menu_slug The slug name to refer to this menu by (should be unique for this menu).
	 * @param bool   $echo Whether or not to echo the url - default is true.
	 * @return string $url The URL.
	 */
	public function network_menu_page_url( $menu_slug, $echo = true ) {
		global $_parent_pages;

		if ( isset( $_parent_pages[ $menu_slug ] ) ) {
			$parent_slug = $_parent_pages[ $menu_slug ];
			if ( $parent_slug && ! isset( $_parent_pages[ $parent_slug ] ) ) {
				$url = network_admin_url( add_query_arg( 'page', $menu_slug, $parent_slug ) );
			} else {
				$url = network_admin_url( 'admin.php?page=' . $menu_slug );
			}
		} else {
			$url = '';
		}

		$url = esc_url( $url );

		if ( $echo ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $url;
		}

		// --<
		return $url;

	}

	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.1
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function admin_form_url_get() {

		// Sanitise admin page url.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$target_url = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( ! empty( $target_url ) ) {
			$url_array = explode( '&', $target_url );
			if ( $url_array ) {
				$target_url = htmlentities( $url_array[0] . '&updated=true' );
			}
		}

		// --<
		return $target_url;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Route settings updates to relevant methods.
	 *
	 * @since 0.1
	 *
	 * @return bool $result True on success, false otherwise.
	 */
	public function settings_update_router() {

		// Init result.
		$result = false;

		// Was the "Settings" form submitted?
		$settings_submit = filter_input( INPUT_POST, 'civi_wp_member_sync_settings_submit' );
		if ( ! empty( $settings_submit ) ) {
			$result = $this->settings_update();
		}

		// Was the "Stop Sync" button pressed?
		$manual_sync_stop = filter_input( INPUT_POST, 'civi_wp_member_sync_manual_sync_stop' );
		if ( ! empty( $manual_sync_stop ) ) {
			delete_option( '_civi_wpms_memberships_offset' );
			return;
		}

		// Was the "Manual Sync" form submitted?
		$manual_sync_submit = filter_input( INPUT_POST, 'civi_wp_member_sync_manual_sync_submit' );
		if ( ! empty( $manual_sync_submit ) ) {

			// Check that we trust the source of the request.
			check_admin_referer( 'civi_wp_member_sync_manual_sync_action', 'civi_wp_member_sync_nonce' );

			/**
			 * Fires just before syncing all Users.
			 *
			 * @since 0.1
			 */
			do_action( 'civi_wp_member_sync_pre_sync_all' );

			// Sync all Memberships for *existing* WordPress Users.
			$result = $this->plugin->members->sync_all_civicrm_memberships();

			/**
			 * Fires just after syncing all Users.
			 *
			 * @since 0.1
			 */
			do_action( 'civi_wp_member_sync_after_sync_all' );

		}

		// Was the "Rule" form submitted?
		$rules_submit = filter_input( INPUT_POST, 'civi_wp_member_sync_rules_submit' );
		if ( ! empty( $rules_submit ) ) {
			$result = $this->rule_update();
		}

		// Was the "Clear Association Rules" form submitted?
		$clear_submit = filter_input( INPUT_POST, 'civi_wp_member_sync_clear_submit' );
		if ( ! empty( $clear_submit ) ) {
			$result = $this->rules_clear();
		}

		// Get sync rule.
		$sync_rule     = '';
		$sync_rule_raw = filter_input( INPUT_GET, 'syncrule' );
		if ( ! empty( $sync_rule_raw ) ) {
			$sync_rule = trim( wp_unslash( $sync_rule_raw ) );
		}

		// Was a "Delete" link clicked?
		if ( 'delete' === $sync_rule ) {

			// Get Type ID query var.
			$type_id     = 0;
			$type_id_raw = filter_input( INPUT_GET, 'type_id' );
			if ( ! empty( $type_id_raw ) ) {
				$type_id = (int) trim( wp_unslash( $type_id_raw ) );
			}

			// Maybe delete rule.
			if ( ! empty( $type_id ) && is_numeric( $type_id ) ) {
				$result = $this->rule_delete();
			}

		}

		// --<
		return $result;

	}

	/**
	 * Get default plugin settings.
	 *
	 * @since 0.1
	 *
	 * @return array $settings The array of settings, keyed by setting name.
	 */
	public function settings_get_default() {

		// Init return.
		$settings = [];

		// Set empty data arrays.
		$settings['data']['roles']        = [];
		$settings['data']['capabilities'] = [];

		// Set default method.
		$settings['method'] = 'capabilities';

		// Set initial sync settings.
		$settings['login']    = 1;
		$settings['civicrm']  = 1;
		$settings['schedule'] = 0;

		// Set default schedule interval.
		$settings['interval'] = 'daily';

		// Sync only the "Individual" Contact Type by default.
		$settings['types'] = 1;

		/**
		 * Allows the plugin settings to be filtered.
		 *
		 * @since 0.1
		 *
		 * @param array $settings The default settings for this plugin
		 */
		return apply_filters( 'civi_wp_member_sync_default_settings', $settings );

	}

	/**
	 * Update plugin settings.
	 *
	 * @since 0.1
	 */
	public function settings_update() {

		// Check that we trust the source of the request.
		check_admin_referer( 'civi_wp_member_sync_settings_action', 'civi_wp_member_sync_nonce' );

		// Synchronization method.
		$settings_method     = 'capabilities';
		$settings_method_raw = filter_input( INPUT_POST, 'civi_wp_member_sync_settings_method' );
		if ( ! empty( $settings_method_raw ) ) {
			$settings_method = trim( wp_unslash( $settings_method_raw ) );
		}
		$this->setting_set( 'method', $settings_method );

		// Login/logout sync enabled.
		$settings_login     = 0;
		$settings_login_raw = filter_input( INPUT_POST, 'civi_wp_member_sync_settings_login' );
		if ( ! empty( $settings_login_raw ) ) {
			$settings_login = (int) trim( wp_unslash( $settings_login_raw ) );
		}
		$this->setting_set( 'login', ( $settings_login ? 1 : 0 ) );

		// CiviCRM sync enabled.
		$settings_civicrm     = 0;
		$settings_civicrm_raw = filter_input( INPUT_POST, 'civi_wp_member_sync_settings_civicrm' );
		if ( ! empty( $settings_civicrm_raw ) ) {
			$settings_civicrm = (int) trim( wp_unslash( $settings_civicrm_raw ) );
		}
		$this->setting_set( 'civicrm', ( $settings_civicrm ? 1 : 0 ) );

		// Get existing schedule.
		$existing_schedule = $this->setting_get( 'schedule' );

		// Schedule sync enabled.
		$settings_schedule     = 0;
		$settings_schedule_raw = filter_input( INPUT_POST, 'civi_wp_member_sync_settings_schedule' );
		if ( ! empty( $settings_schedule_raw ) ) {
			$settings_schedule = (int) trim( wp_unslash( $settings_schedule_raw ) );
		}
		$this->setting_set( 'schedule', ( $settings_schedule ? 1 : 0 ) );

		// Is the schedule being deactivated?
		if ( 1 === (int) $existing_schedule && 0 === $settings_schedule ) {

			// Clear current scheduled event.
			$this->plugin->schedule->unschedule();

		}

		// Schedule interval.
		$settings_interval_raw = filter_input( INPUT_POST, 'civi_wp_member_sync_settings_interval' );
		if ( ! empty( $settings_interval_raw ) ) {

			// Get existing interval.
			$existing_interval = $this->setting_get( 'interval' );

			// Get value passed in.
			$settings_interval = esc_sql( trim( wp_unslash( $settings_interval_raw ) ) );

			// Is the schedule active and has the interval changed?
			if ( $settings_schedule && $settings_interval !== $existing_interval ) {

				// Clear current scheduled event.
				$this->plugin->schedule->unschedule();

				// Now add new scheduled event.
				$this->plugin->schedule->schedule( $settings_interval );

			}

			// Set new value whatever (for now).
			$this->setting_set( 'interval', $settings_interval );

		}

		// Sync restricted to Individuals?
		$settings_types     = 0;
		$settings_types_raw = filter_input( INPUT_POST, 'civi_wp_member_sync_settings_types' );
		if ( ! empty( $settings_types_raw ) ) {
			$settings_types = (int) wp_unslash( $settings_types_raw );
		}
		$this->setting_set( 'types', ( $settings_types ? 1 : 0 ) );

		// Save settings.
		$this->settings_save();

		// Get admin URLs.
		$urls = $this->page_get_urls();

		// Redirect to settings page with message.
		wp_safe_redirect( $urls['settings'] . '&updated=true' );
		exit();

	}

	/**
	 * Save the plugin's settings array.
	 *
	 * @since 0.1
	 *
	 * @return bool $result True if setting value has changed, false if not or if update failed.
	 */
	public function settings_save() {

		// Update WordPress option and return result.
		return $this->option_save( 'civi_wp_member_sync_settings', $this->settings );

	}

	/**
	 * Check whether a specified setting exists.
	 *
	 * @since 0.3.6
	 *
	 * @param string $setting_name The name of the setting.
	 * @return bool Whether or not the setting exists.
	 */
	public function setting_exists( $setting_name ) {

		// Get existence of setting in array.
		return array_key_exists( $setting_name, $this->settings );

	}

	/**
	 * Return a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param str   $setting_name The name of the setting.
	 * @param mixed $default The default return value if no value exists.
	 * @return mixed $setting The value of the setting.
	 */
	public function setting_get( $setting_name, $default = false ) {

		// Get setting.
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[ $setting_name ] : $default;

	}

	/**
	 * Set a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param str   $setting_name The name of the setting.
	 * @param mixed $value The value to set.
	 */
	public function setting_set( $setting_name, $value = '' ) {

		// Set setting.
		$this->settings[ $setting_name ] = $value;

	}

	/**
	 * Return the value for the 'method' setting.
	 *
	 * Added as a separate method to ensure that only one of two values is returned.
	 *
	 * @since 0.3.6
	 *
	 * @return str $method The value of the 'method' setting.
	 */
	public function setting_get_method() {

		// Get sync method and sanitize.
		$method = $this->setting_get( 'method' );
		$method = ( 'roles' === $method ) ? 'roles' : 'capabilities';

		// --<
		return $method;

	}

	/**
	 * Return the value for the batch count.
	 *
	 * Added as a separate method to allow filtering.
	 *
	 * @since 0.3.7
	 *
	 * @return int $count The value of the batch count.
	 */
	public function setting_get_batch_count() {

		// Get property.
		$count = $this->batch_count;

		/**
		 * Filters the batch count.
		 *
		 * Overriding this value allows the batch process to be controlled such
		 * that installs with large numbers of Memberships running on faster
		 * machines can reduce the time taken to perform the sync process.
		 *
		 * @since 0.3.7
		 *
		 * @param int $count The default number of Memberships to process per batch.
		 */
		$count = apply_filters( 'civi_wp_member_sync_get_batch_count', $count );

		// --<
		return $count;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get a WordPress option.
	 *
	 * @since 0.2.7
	 *
	 * @param string $key The option name.
	 * @param mixed  $default The default option value if none exists.
	 * @return mixed $value The option value.
	 */
	public function option_get( $key, $default = null ) {

		// If multisite and network activated.
		if ( $this->is_network_activated() ) {
			$value = get_site_option( $key, $default );
		} else {
			$value = get_option( $key, $default );
		}

		// --<
		return $value;

	}

	/**
	 * Save a WordPress option.
	 *
	 * @since 0.2.7
	 *
	 * @param string $key The option name.
	 * @param mixed  $value The value to save.
	 */
	public function option_save( $key, $value ) {

		// If multisite and network activated.
		if ( $this->is_network_activated() ) {
			update_site_option( $key, $value );
		} else {
			update_option( $key, $value );
		}

	}

	/**
	 * Delete a WordPress option.
	 *
	 * @since 0.2.7
	 *
	 * @param string $key The option name.
	 */
	public function option_delete( $key ) {

		// If multisite and network activated.
		if ( $this->is_network_activated() ) {
			delete_site_option( $key );
		} else {
			delete_option( $key );
		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get all association rules by method.
	 *
	 * @since 0.1
	 *
	 * @param string $method The sync method (either 'roles' or 'capabilities').
	 * @return mixed $rule Array if successful, boolean false otherwise.
	 */
	public function rules_get_by_method( $method = 'roles' ) {

		// Get data.
		$data = $this->setting_get( 'data' );

		// Sanitize method.
		$method = ( 'roles' === $method ) ? 'roles' : 'capabilities';

		// Get subset by method.
		$subset = ( isset( $data[ $method ] ) ) ? $data[ $method ] : false;

		// --<
		return $subset;

	}

	/**
	 * Clear all association rules for the current method.
	 *
	 * @since 0.4.2
	 */
	public function rules_clear() {

		// Get method.
		$method = $this->setting_get_method();

		// Get data.
		$data = $this->setting_get( 'data' );

		// Get subset by method.
		$subset = ( isset( $data[ $method ] ) ) ? $data[ $method ] : false;
		if ( ! $subset ) {
			return;
		}

		// Loop through them.
		foreach ( $subset as $type_id => $rule ) {

			/**
			 * Fires just before deleting an association rule.
			 *
			 * This creates two actions, depending on the sync method:
			 *
			 * * `civi_wp_member_sync_rule_delete_roles`
			 * * `civi_wp_member_sync_rule_delete_capabilities`
			 *
			 * @param array $rule The association rule we're going to delete.
			 */
			do_action( 'civi_wp_member_sync_rule_delete_' . $method, $rule );

		}

		// Update data.
		$data[ $method ] = [];

		// Overwrite data.
		$this->setting_set( 'data', $data );

		// Save.
		$this->settings_save();

		// Get admin URLs.
		$urls = $this->page_get_urls();

		// Redirect to list page with message.
		wp_safe_redirect( $urls['list'] . '&syncrule=delete-all' );
		exit();

	}

	/**
	 * Get an association rule by Membership Type ID.
	 *
	 * @since 0.1
	 *
	 * @param int    $type_id The numeric ID of the CiviCRM Membership Type.
	 * @param string $method The sync method (either 'roles' or 'capabilities').
	 * @return mixed $rule Array if successful, boolean false otherwise.
	 */
	public function rule_get_by_type( $type_id, $method = 'roles' ) {

		// Get data.
		$data = $this->setting_get( 'data' );

		// Sanitize method.
		$method = ( 'roles' === $method ) ? 'roles' : 'capabilities';

		// Get subset by method.
		$subset = ( isset( $data[ $method ] ) ) ? $data[ $method ] : false;

		// Get data for this type_id.
		$rule = ( isset( $subset[ $type_id ] ) ) ? $subset[ $type_id ] : false;

		// --<
		return $rule;

	}

	/**
	 * Update (or add) a Membership rule.
	 *
	 * @since 0.1
	 *
	 * @return bool $success True if successful, false otherwise.
	 */
	public function rule_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'civi_wp_member_sync_rule_action', 'civi_wp_member_sync_nonce' );

		// Default mode to 'add'.
		$mode = 'add';

		// Test our hidden "mode" element.
		$rules_mode_raw = filter_input( INPUT_POST, 'civi_wp_member_sync_rules_mode' );
		if ( ! empty( $rules_mode_raw ) ) {
			$rules_mode = trim( wp_unslash( $rules_mode_raw ) );
		}

		// Maybe apply edit mode.
		if ( ! empty( $rules_mode ) && 'edit' === $rules_mode ) {
			$mode = 'edit';
		}

		// Get sync method and sanitize.
		$method = $this->setting_get_method();

		// Default "multiple" to false.
		$multiple = false;

		// Test our hidden "multiple" element.
		$rules_multiple     = '';
		$rules_multiple_raw = filter_input( INPUT_POST, 'civi_wp_member_sync_rules_multiple' );
		if ( ! empty( $rules_multiple_raw ) ) {
			$rules_multiple = trim( wp_unslash( $rules_multiple_raw ) );
		}

		// Maybe apply multiple mode.
		if ( ! empty( $rules_multiple ) && 'yes' === $rules_multiple ) {
			$multiple = true;
		}

		// Init errors.
		$this->errors = [];

		// Depending on the "multiple" flag, validate Membership Types.
		if ( true === $multiple ) {

			// Check and sanitise CiviCRM Membership Types.
			$civi_member_type_ids = filter_input( INPUT_POST, 'civi_member_type_id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			if ( ! empty( $civi_member_type_ids ) ) {

				// Sanitise array contents.
				array_walk(
					$civi_member_type_ids,
					function( &$item ) {
						$item = (int) trim( $item );
					}
				);

			} else {
				$this->errors[] = 'type';
			}

		} else {

			// Check and sanitise CiviCRM Membership Type.
			$civi_member_type_id = filter_input( INPUT_POST, 'civi_member_type_id' );
			if ( ! empty( $civi_member_type_id ) && is_numeric( $civi_member_type_id ) ) {
				$civi_member_type_id = (int) trim( wp_unslash( $civi_member_type_id ) );
			} else {
				$this->errors[] = 'type';
			}

		}

		// Check and sanitise Current Status.
		$current_rule = filter_input( INPUT_POST, 'current', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $current_rule ) ) {

			// Sanitise array contents.
			array_walk(
				$current_rule,
				function( &$item ) {
					$item = (int) trim( $item );
				}
			);

		} else {
			$this->errors[] = 'current-status';
		}

		// Check and sanitise Expire Status.
		$expiry_rule = filter_input( INPUT_POST, 'expire', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $expiry_rule ) ) {

			// Sanitise array contents.
			array_walk(
				$expiry_rule,
				function( &$item ) {
					$item = (int) trim( $item );
				}
			);

		} else {
			$this->errors[] = 'expire-status';
		}

		// Init current-expire check (will end up true if there's a clash).
		$current_expire_clash = false;

		// Do we have both arrays?
		if ( isset( $current_rule ) && isset( $expiry_rule ) ) {

			// Check 'current' array against 'expire' array.
			$intersect = array_intersect_assoc( $current_rule, $expiry_rule );
			if ( ! empty( $intersect ) ) {
				$current_expire_clash = true;
			}

		}

		// Do we want Roles?
		if ( 'roles' === $method ) {

			// Check and sanitise WordPress Role.
			$current_wp_role     = '';
			$current_wp_role_raw = filter_input( INPUT_POST, 'current_wp_role' );
			if ( ! empty( $current_wp_role_raw ) ) {
				$current_wp_role = esc_sql( trim( wp_unslash( $current_wp_role_raw ) ) );
			}

			if ( empty( $current_wp_role ) ) {
				$this->errors[] = 'current-role';
			}

			// Check and sanitise Expiry Role.
			$expired_wp_role     = '';
			$expired_wp_role_raw = filter_input( INPUT_POST, 'expire_assign_wp_role' );
			if ( ! empty( $expired_wp_role_raw ) ) {
				$expired_wp_role = esc_sql( trim( wp_unslash( $expired_wp_role_raw ) ) );
			}

			if ( empty( $expired_wp_role ) ) {
				$this->errors[] = 'expire-role';
			}

		}

		// How did we do?
		if ( false === $current_expire_clash && empty( $this->errors ) ) {

			// Depending on the "multiple" flag, create rules.
			if ( true === $multiple ) {

				// Save each rule in turn.
				foreach ( $civi_member_type_ids as $civi_member_type_id ) {

					// Which sync method are we using?
					if ( 'roles' === $method ) {

						// Combine rule data into array.
						$rule_data = [
							'current_rule'    => $current_rule,
							'current_wp_role' => $current_wp_role,
							'expiry_rule'     => $expiry_rule,
							'expired_wp_role' => $expired_wp_role,
						];

						// Get formatted array.
						$rule = $this->rule_create_array( $method, $rule_data );

						// Apply rule data and save.
						$this->rule_save( $rule, $mode, $method, $civi_member_type_id );

					} else {

						// Combine rule data into array.
						$rule_data = [
							'current_rule'        => $current_rule,
							'expiry_rule'         => $expiry_rule,
							'civi_member_type_id' => $civi_member_type_id,
						];

						// Get formatted array.
						$rule = $this->rule_create_array( $method, $rule_data );

						// Apply rule data and save.
						$this->rule_save( $rule, $mode, $method, $civi_member_type_id );

					}

				}

			} else {

				// Which sync method are we using?
				if ( 'roles' === $method ) {

					// Combine rule data into array.
					$rule_data = [
						'current_rule'    => $current_rule,
						'current_wp_role' => $current_wp_role,
						'expiry_rule'     => $expiry_rule,
						'expired_wp_role' => $expired_wp_role,
					];

					// Get formatted array.
					$rule = $this->rule_create_array( $method, $rule_data );

					// Apply rule data and save.
					$this->rule_save( $rule, $mode, $method, $civi_member_type_id );

				} else {

					// Combine rule data into array.
					$rule_data = [
						'current_rule'        => $current_rule,
						'expiry_rule'         => $expiry_rule,
						'civi_member_type_id' => $civi_member_type_id,
					];

					// Get formatted array.
					$rule = $this->rule_create_array( $method, $rule_data );

					// Apply rule data and save.
					$this->rule_save( $rule, $mode, $method, $civi_member_type_id );

				}

			}

			// Get admin URLs.
			$urls = $this->page_get_urls();

			// Redirect to list page.
			wp_safe_redirect( $urls['list'] . '&syncrule=' . $mode );
			exit();

		} else {

			// Are there status clashes?
			if ( false === $current_expire_clash ) {
				$this->errors[] = 'clash-status';
			}

			// Sad face.
			// TODO: Redirect to originating screen.
			return false;

		}

	}

	/**
	 * Create a Membership rule array.
	 *
	 * @since 0.4.2
	 *
	 * @param str   $method The sync method.
	 * @param array $params The params to build the array from.
	 * @return array $rule The constructed rule array.
	 */
	public function rule_create_array( $method, $params ) {

		// Which sync method are we using?
		if ( 'roles' === $method ) {

			// Construct Role rule.
			$rule = [
				'current_rule'    => $params['current_rule'],
				'current_wp_role' => $params['current_wp_role'],
				'expiry_rule'     => $params['expiry_rule'],
				'expired_wp_role' => $params['expired_wp_role'],
			];

		} else {

			// Construct Capability rule.
			$rule = [
				'current_rule' => $params['current_rule'],
				'expiry_rule'  => $params['expiry_rule'],
				'capability'   => CIVI_WP_MEMBER_SYNC_CAP_PREFIX . $params['civi_member_type_id'],
			];

		}

		// --<
		return $rule;

	}

	/**
	 * Save a Membership rule.
	 *
	 * @since 0.4.2
	 *
	 * @param array $rule The new or updated association rule.
	 * @param str   $mode The mode ('add' or 'edit').
	 * @param str   $method The sync method.
	 * @param int   $civi_member_type_id The numeric ID of the Membership Type.
	 */
	public function rule_save( $rule, $mode, $method, $civi_member_type_id ) {

		// Get existing data.
		$data = $this->setting_get( 'data' );

		/**
		 * Filter our association rule before it is saved.
		 *
		 * @since 0.4
		 *
		 * @param array $rule The new or updated association rule.
		 * @param array $data The complete set of association rule.
		 * @param str $mode The mode ('add' or 'edit').
		 * @param str $method The sync method.
		 */
		$rule = apply_filters( 'civi_wp_member_sync_rule_pre_save', $rule, $data, $mode, $method );

		/**
		 * Fires just before an association rule is saved.
		 *
		 * This creates four possible actions:
		 *
		 * * `civi_wp_member_sync_rule_add_roles`
		 * * `civi_wp_member_sync_rule_add_capabilities`
		 * * `civi_wp_member_sync_rule_edit_roles`
		 * * `civi_wp_member_sync_rule_edit_capabilities`
		 *
		 * @since 0.2.3
		 *
		 * @param array $rule The new or updated association rule.
		 */
		do_action( 'civi_wp_member_sync_rule_' . $mode . '_' . $method, $rule );

		// Insert/overwrite item in data array.
		$data[ $method ][ $civi_member_type_id ] = $rule;

		// Overwrite data.
		$this->setting_set( 'data', $data );

		// Save.
		$this->settings_save();

		/**
		 * Fires just after an association rule has been saved.
		 *
		 * This creates four possible actions:
		 *
		 * * `civi_wp_member_sync_rule_add_roles_saved`
		 * * `civi_wp_member_sync_rule_add_capabilities_saved`
		 * * `civi_wp_member_sync_rule_edit_roles_saved`
		 * * `civi_wp_member_sync_rule_edit_capabilities_saved`
		 *
		 * @since 0.3.9
		 *
		 * @param array $rule The new or updated association rule.
		 * @param str $method The sync method.
		 * @param int $civi_member_type_id The numeric ID of the CiviCRM Membership Type.
		 */
		do_action( 'civi_wp_member_sync_rule_' . $mode . '_' . $method . '_saved', $rule, $method, $civi_member_type_id );

	}

	/**
	 * Delete a Membership rule.
	 *
	 * @since 0.1
	 *
	 * @return bool $success True if successful, false otherwise.
	 */
	public function rule_delete() {

		// Check nonce.
		$nonce = filter_input( INPUT_GET, 'civi_wp_member_sync_delete_nonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'civi_wp_member_sync_delete_link' ) ) {
			wp_die( esc_html__( 'Cheating, eh?', 'civicrm-wp-member-sync' ) );
			exit();
		}

		// Get Membership Type ID.
		$type_id = filter_input( INPUT_GET, 'type_id' );
		if ( empty( $type_id ) ) {
			return;
		} else {
			$type_id = (int) trim( wp_unslash( $type_id ) );
		}

		// Get method.
		$method = $this->setting_get_method();

		// Get data.
		$data = $this->setting_get( 'data' );

		// Get subset by method.
		$subset = ( isset( $data[ $method ] ) ) ? $data[ $method ] : false;
		if ( ! $subset ) {
			return;
		}
		if ( ! isset( $subset[ $type_id ] ) ) {
			return;
		}

		/**
		 * Fires just before deleting an association rule.
		 *
		 * This creates two actions, depending on the sync method:
		 *
		 * * `civi_wp_member_sync_rule_delete_roles`
		 * * `civi_wp_member_sync_rule_delete_capabilities`
		 *
		 * @param array The association rule we're going to delete.
		 */
		do_action( 'civi_wp_member_sync_rule_delete_' . $method, $subset[ $type_id ] );

		// Delete it.
		unset( $subset[ $type_id ] );

		// Update data.
		$data[ $method ] = $subset;

		// Overwrite data.
		$this->setting_set( 'data', $data );

		// Save.
		$this->settings_save();

		// Get admin URLs.
		$urls = $this->page_get_urls();

		// Redirect to list page with message.
		wp_safe_redirect( $urls['list'] . '&syncrule=delete' );
		exit();

	}

	/**
	 * Check if there is at least one rule applied to a set of Memberships.
	 *
	 * The reason for this method is, as @andymyersau points out, that Users
	 * should not be created unless there is an Association Rule that applies
	 * to them. This method therefore checks for the existence of at least one
	 * applicable rule for a given set of Memberships.
	 *
	 * @since 0.3.7
	 *
	 * @param array $memberships The Memberships to analyse.
	 * @return bool $has_rule True if a rule applies, false otherwise.
	 */
	public function rule_exists( $memberships = false ) {

		// Assume no rule applies.
		$has_rule = false;

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return $has_rule;
		}

		// Bail if we didn't get Memberships passed.
		if ( false === $memberships ) {
			return $has_rule;
		}
		if ( empty( $memberships ) ) {
			return $has_rule;
		}

		// Get sync method.
		$method = $this->setting_get_method();

		// Loop through the supplied Memberships.
		foreach ( $memberships['values'] as $membership ) {

			// Continue with next Membership if something went wrong.
			if ( empty( $membership['membership_type_id'] ) ) {
				continue;
			}

			// Get Membership Type.
			$membership_type_id = $membership['membership_type_id'];

			// Get association rule for this Membership Type.
			$association_rule = $this->rule_get_by_type( $membership_type_id, $method );

			// Continue with next Membership if we have an error or no rule exists.
			if ( false === $association_rule ) {
				continue;
			}

			// Continue with next Membership if something is wrong with rule.
			if ( empty( $association_rule['current_rule'] ) ) {
				continue;
			}
			if ( empty( $association_rule['expiry_rule'] ) ) {
				continue;
			}

			// Which sync method are we using?
			if ( 'roles' === $method ) {

				// Continue with next Membership if something is wrong with rule.
				if ( empty( $association_rule['current_wp_role'] ) ) {
					continue;
				}
				if ( empty( $association_rule['expired_wp_role'] ) ) {
					continue;
				}

				// Rule applies.
				$has_rule = true;
				break;

			} else {

				// Rule applies.
				$has_rule = true;
				break;

			}

		}

		// --<
		return $has_rule;

	}

	/**
	 * Manage WordPress Roles or Capabilities based on the status of a User's Memberships.
	 *
	 * The following notes are to describe how this method should be enhanced:
	 *
	 * There are sometimes situations - when there are multiple Roles assigned via
	 * multiple Memberships - where a Role may be incorrectly removed (and perhaps
	 * added but I haven't tested that fully yet) if the association rules share a
	 * common "expired" Role, such as "Anonymous User".
	 *
	 * The current logic may remove the expired Role because other rules may be
	 * applied after the rule which assigns the expired Role. If they are - and
	 * they are not expired Memberships - the expired rule will therefore be
	 * removed from the User.
	 *
	 * It seems that what's needed is to parse the rules prior to applying them
	 * to determine the final set of Roles that a User should have. These rules
	 * can then be applied in one go, thus avoiding the overrides resulting from
	 * the conflicting rules.
	 *
	 * Note: the check for an admin User has been Removed - DO NOT call this for
	 * admins UNLESS you are using a plugin that enables multiple Roles.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object of the User in question.
	 * @param array   $memberships The Memberships of the WordPress User in question.
	 * @return array $result Results of applying the rule.
	 */
	public function rule_apply( $user, $memberships = false ) {

		// Init return array.
		$result = [];

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return $result;
		}

		// Kick out if we didn't get Memberships passed.
		if ( false === $memberships ) {
			return $result;
		}

		// Get sync method.
		$method = $this->setting_get_method();

		// Loop through the supplied Memberships.
		foreach ( $memberships['values'] as $membership ) {

			// Continue if something went wrong.
			if ( ! isset( $membership['membership_type_id'] ) ) {
				continue;
			}
			if ( ! isset( $membership['status_id'] ) ) {
				continue;
			}

			// Get Membership Type and status rule.
			$membership_type_id = $membership['membership_type_id'];
			$status_id          = $membership['status_id'];

			// Get association rule for this Membership Type.
			$association_rule = $this->rule_get_by_type( $membership_type_id, $method );

			// Continue with next rule if we have an error of some kind.
			if ( false === $association_rule ) {
				continue;
			}

			// Get status rules.
			$current_rule = $association_rule['current_rule'];
			$expiry_rule  = $association_rule['expiry_rule'];

			// Which sync method are we using?
			if ( 'roles' === $method ) {

				// SYNC ROLES.

				// Continue if something went wrong.
				if ( empty( $association_rule['current_wp_role'] ) ) {
					continue;
				}
				if ( empty( $association_rule['expired_wp_role'] ) ) {
					continue;
				}

				// Get Roles for this association rule.
				$current_wp_role = $association_rule['current_wp_role'];
				$expired_wp_role = $association_rule['expired_wp_role'];

				// Does the User's Membership Status match a current status rule?
				if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {

					// Add current Role if the User does not have it.
					if ( ! $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
						$this->plugin->users->wp_role_add( $user, $current_wp_role );
					}

					// Remove expired Role if the User has it.
					if ( $this->plugin->users->wp_has_role( $user, $expired_wp_role ) ) {
						$this->plugin->users->wp_role_remove( $user, $expired_wp_role );
					}

					// Set flag for action.
					$flag = 'current';

				} else {

					// Remove current Role if the User has it.
					if ( $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
						$this->plugin->users->wp_role_remove( $user, $current_wp_role );
					}

					// Add expired Role if the User does not have it.
					if ( ! $this->plugin->users->wp_has_role( $user, $expired_wp_role ) ) {
						$this->plugin->users->wp_role_add( $user, $expired_wp_role );
					}

					// Set flag for action.
					$flag = 'expired';

				}

				/**
				 * Fires after application of rule to User when syncing Roles.
				 *
				 * This creates two possible actions:
				 *
				 * * `civi_wp_member_sync_rule_apply_roles_current`
				 * * `civi_wp_member_sync_rule_apply_roles_expired`
				 *
				 * @since 0.3.2
				 *
				 * @param WP_User $user The WordPress User object.
				 * @param int $membership_type_id The ID of the CiviCRM Membership Type.
				 * @param int $status_id The ID of the CiviCRM Membership Status.
				 * @param array $association_rule The rule used to apply the changes.
				 */
				do_action( 'civi_wp_member_sync_rule_apply_roles_' . $flag, $user, $membership_type_id, $status_id, $association_rule );

			} else {

				// SYNC CAPABILITY.

				// Construct Membership Type Capability name.
				$capability = CIVI_WP_MEMBER_SYNC_CAP_PREFIX . $membership_type_id;

				// Construct Membership Status Capability name.
				$capability_status = $capability . '_' . $status_id;

				// Does the User's Membership Status match a current status rule?
				if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {

					// Maybe add the "Members" plugin's custom Capability.
					if ( defined( 'MEMBERS_VERSION' ) ) {
						$this->plugin->users->wp_cap_add( $user, 'restrict_content' );
					}

					// Add type Capability.
					$this->plugin->users->wp_cap_add( $user, $capability );

					// Clear status Capabilities.
					$this->plugin->users->wp_cap_remove_status( $user, $capability );

					// Add status Capability.
					$this->plugin->users->wp_cap_add( $user, $capability_status );

					// Set flag for action.
					$flag = 'current';

				} else {

					// Maybe remove the "Members" plugin's custom Capability.
					if ( defined( 'MEMBERS_VERSION' ) ) {
						$this->plugin->users->wp_cap_remove( $user, 'restrict_content' );
					}

					// Remove type Capability.
					$this->plugin->users->wp_cap_remove( $user, $capability );

					// Clear status Capabilities.
					$this->plugin->users->wp_cap_remove_status( $user, $capability );

					// Set flag for action.
					$flag = 'expired';

				}

				/**
				 * Fires after application of rule to User when syncing Capabilities.
				 *
				 * This creates two possible actions:
				 *
				 * * `civi_wp_member_sync_rule_apply_caps_current`
				 * * `civi_wp_member_sync_rule_apply_caps_expired`
				 *
				 * The status Capability can be derived from the combination of
				 * $capability and $status_id and is therefore not needed when
				 * firing this action.
				 *
				 * @since 0.3.2
				 * @since 0.4 Added association rule parameter.
				 *
				 * @param WP_User $user The WordPress User object.
				 * @param int $membership_type_id The ID of the CiviCRM Membership Type.
				 * @param int $status_id The ID of the CiviCRM Membership Status.
				 * @param array $capability The Membership Type Capability added or removed.
				 * @param array $association_rule The rule used to apply the changes.
				 */
				do_action( 'civi_wp_member_sync_rule_apply_caps_' . $flag, $user, $membership_type_id, $status_id, $capability, $association_rule );

			}

			// Gather data.
			$user_data = [
				'is_new'             => empty( $user->user_is_new ) ? false : true,
				'display_name'       => $user->display_name,
				'link'               => '',
				'username'           => $user->user_login,
				'membership_type_id' => $membership_type_id,
				'status_id'          => $status_id,
				'membership_name'    => $this->plugin->members->membership_name_get_by_id( $membership_type_id ),
				'membership_status'  => $this->plugin->members->status_name_get_by_id( $status_id ),
				'flag'               => $flag,
				'association_rule'   => $association_rule,
			];

			// Check with CiviCRM that this Contact can be viewed.
			$allowed = CRM_Contact_BAO_Contact_Permission::allow( $membership['contact_id'], CRM_Core_Permission::VIEW );
			if ( $allowed ) {
				$user_data['link'] = $this->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $membership['contact_id'] );
			}

			// Append to return array.
			$result[] = $user_data;

		}

		/**
		 * Fires after the application of all rules to a User's Memberships.
		 *
		 * @since 0.3.6
		 *
		 * @param WP_User $user The WordPress User object.
		 * @param array $memberships The Memberships of the WordPress User in question.
		 * @param str $method The sync method - either 'caps' or 'roles'.
		 */
		do_action( 'civi_wp_member_sync_rules_applied', $user, $memberships, $method );

		// --<
		return $result;

	}

	/**
	 * Simulate the application of "rule_apply".
	 *
	 * Adding a new param to "rule_apply" would still fire the actions in that
	 * method - which could have unforeseen consequences. This method simply
	 * duplicates the logic without the actions which avoids that danger.
	 *
	 * @since 0.5
	 *
	 * @param WP_User $user WP_User object of the User in question.
	 * @param array   $memberships The Memberships of the WordPress User in question.
	 * @return array $result Results of applying the rule.
	 */
	public function rule_simulate( $user, $memberships = false ) {

		// Init return array.
		$result = [];

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return $result;
		}

		// Kick out if we didn't get Memberships passed.
		if ( false === $memberships ) {
			return $result;
		}

		// Get sync method.
		$method = $this->setting_get_method();

		// Loop through the supplied Memberships.
		foreach ( $memberships['values'] as $membership ) {

			// Continue if something went wrong.
			if ( ! isset( $membership['membership_type_id'] ) ) {
				continue;
			}
			if ( ! isset( $membership['status_id'] ) ) {
				continue;
			}

			// Get Membership Type and status rule.
			$membership_type_id = $membership['membership_type_id'];
			$status_id          = $membership['status_id'];

			// Get association rule for this Membership Type.
			$association_rule = $this->rule_get_by_type( $membership_type_id, $method );

			// Continue with next rule if we have an error of some kind.
			if ( false === $association_rule ) {
				continue;
			}

			// Get status rules.
			$current_rule = $association_rule['current_rule'];
			$expiry_rule  = $association_rule['expiry_rule'];

			// Which sync method are we using?
			if ( 'roles' === $method ) {

				// SYNC ROLES.

				// Continue if something went wrong.
				if ( empty( $association_rule['current_wp_role'] ) ) {
					continue;
				}
				if ( empty( $association_rule['expired_wp_role'] ) ) {
					continue;
				}

				// Does the User's Membership Status match a current status rule?
				if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {
					$flag = 'current';
				} else {
					$flag = 'expired';
				}

			} else {

				// SYNC CAPABILITY.

				// Does the User's Membership Status match a current status rule?
				if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {
					$flag = 'current';
				} else {
					$flag = 'expired';
				}

			}

			// Gather data.
			$user_data = [
				'is_new'             => ( PHP_INT_MAX === $user->ID ),
				'display_name'       => $user->display_name,
				'link'               => '',
				'username'           => $user->user_login,
				'membership_type_id' => $membership_type_id,
				'status_id'          => $status_id,
				'membership_name'    => $this->plugin->members->membership_name_get_by_id( $membership_type_id ),
				'membership_status'  => $this->plugin->members->status_name_get_by_id( $status_id ),
				'flag'               => $flag,
				'association_rule'   => $association_rule,
			];

			// Check with CiviCRM that this Contact can be viewed.
			$allowed = CRM_Contact_BAO_Contact_Permission::allow( $membership['contact_id'], CRM_Core_Permission::VIEW );
			if ( $allowed ) {
				$user_data['link'] = $this->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $membership['contact_id'] );
			}

			// Append to return array.
			$result[] = $user_data;

		}

		/**
		 * Filter the return array.
		 *
		 * @since 0.5
		 *
		 * @param WP_User $user The WordPress User object.
		 * @param array $memberships The Memberships of the WordPress User in question.
		 * @param str $method The sync method - either 'caps' or 'roles'.
		 */
		$result = apply_filters( 'cwms/admin/rule_simulate/applied', $result, $user, $memberships, $method );

		// --<
		return $result;

	}

	/**
	 * Remove WordPress Role or Capability when a Membership is deleted.
	 *
	 * This method is called:
	 *
	 * * When a Membership is removed from a User in the CiviCRM admin.
	 * * When an existing Membership has its Membership Type changed.
	 *
	 * The Membership details passed to this method will only ever be for a
	 * single Membership.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object of the User in question.
	 * @param object  $membership The Membership details of the WordPress User in question.
	 */
	public function rule_undo( $user, $membership = false ) {

		// Get sync method.
		$method = $this->setting_get_method();

		// Get Association Rule for this Membership Type.
		$association_rule = $this->rule_get_by_type( $membership->membership_type_id, $method );

		// Bail if there isn't one.
		if ( false === $association_rule ) {
			return;
		}

		// Get remaining Memberships for this Contact.
		$memberships = $this->plugin->members->membership_get_by_contact_id( $membership->contact_id );

		// Which sync method are we using?
		if ( 'roles' === $method ) {

			/*
			 * When there are multiple Memberships, remove both the current Role
			 * and the expired Role. If this is the only remaining Membership
			 * that the User has, however, then simply switch the current Role
			 * for the expired Role. This is to prevent Users ending up with no
			 * Role whatsoever.
			 */

			// Bail if something went wrong.
			if ( empty( $association_rule['current_wp_role'] ) ) {
				return;
			}
			if ( empty( $association_rule['expired_wp_role'] ) ) {
				return;
			}

			// Get Roles for this status rule.
			$current_wp_role = $association_rule['current_wp_role'];
			$expired_wp_role = $association_rule['expired_wp_role'];

			// If this User has a remaining Membership.
			if (
				false !== $memberships &&
				0 === (int) $memberships['is_error'] &&
				isset( $memberships['values'] ) &&
				count( $memberships['values'] ) > 0
			) {

				/*
				 * There's a special case here where the Membership being removed
				 * may cause the User to be left with no Role at all if both the
				 * current and expired Roles are removed.
				 *
				 * Additionally, Roles defined by other rules may be affected by
				 * removing the Roles associated with this Membership.
				 *
				 * The logic adopted here is that we still go ahead and remove
				 * both Roles and then perform a rule sync so that the remaining
				 * rules are applied.
				 */

				// Remove current Role if the User has it.
				if ( $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
					$this->plugin->users->wp_role_remove( $user, $current_wp_role );
				}

				// Remove expired Role if the User has it.
				if ( $this->plugin->users->wp_has_role( $user, $expired_wp_role ) ) {
					$this->plugin->users->wp_role_remove( $user, $expired_wp_role );
				}

				// Perform sync for this User if there are applicable rules.
				if ( $this->rule_exists( $memberships ) ) {
					$this->rule_apply( $user, $memberships );
				}

			} else {

				// Replace the current Role with the expired Role.
				if ( $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
					$this->plugin->users->wp_role_replace( $user, $current_wp_role, $expired_wp_role );
				}

			}

			/**
			 * Fires after undoing a rule for a User when syncing Roles.
			 *
			 * @since 0.5.2
			 *
			 * @param WP_User $user The WordPress User object.
			 * @param object $membership The CiviCRM Membership data object.
			 * @param array $association_rule The rule used to apply the changes.
			 * @param array $memberships The array of remaining CiviCRM Memberships.
			 */
			do_action( 'civi_wp_member_sync_rule_undo_roles', $user, $membership, $association_rule, $memberships );

		} else {

			// Construct Capability name.
			$capability = CIVI_WP_MEMBER_SYNC_CAP_PREFIX . $membership->membership_type_id;

			// Remove Capability.
			$this->plugin->users->wp_cap_remove( $user, $capability );

			// Remove status Capability.
			$this->plugin->users->wp_cap_remove_status( $user, $capability );

			// Do we have the "Members" plugin?
			if ( defined( 'MEMBERS_VERSION' ) ) {

				// Remove the custom Capability.
				$this->plugin->users->wp_cap_remove( $user, 'restrict_content' );

			}

			/**
			 * Fires after undoing a rule for a User when syncing Capabilities.
			 *
			 * @since 0.5.2
			 *
			 * @param WP_User $user The WordPress User object.
			 * @param object $membership The CiviCRM Membership data object.
			 * @param array $association_rule The rule used to apply the changes.
			 * @param array $memberships The array of remaining CiviCRM Memberships.
			 * @param array $capability The Membership Type Capability removed.
			 */
			do_action( 'civi_wp_member_sync_rule_undo_caps', $user, $membership, $association_rule, $memberships, $capability );

		}

		/**
		 * Fires after the undoing a rule for a User's Membership.
		 *
		 * @since 0.5.2
		 *
		 * @param WP_User $user The WordPress User object.
		 * @param object $membership The CiviCRM Membership data object.
		 * @param str $method The sync method - either 'caps' or 'roles'.
		 * @param array $memberships The array of remaining CiviCRM Memberships.
		 */
		do_action( 'civi_wp_member_sync_rule_undone', $user, $membership, $method, $memberships );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get a CiviCRM admin link.
	 *
	 * @since 0.5
	 *
	 * @param str $path The CiviCRM path.
	 * @param str $params The CiviCRM parameters.
	 * @return string $link The URL of the CiviCRM page.
	 */
	public function get_link( $path = '', $params = null ) {

		// Init link.
		$link = '';

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return $link;
		}

		// Use CiviCRM to construct link.
		$link = CRM_Utils_System::url(
			$path,
			$params,
			true,
			null,
			true,
			false,
			true
		);

		// --<
		return $link;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Check if CiviCRM Admin Utilities has been installed and activated.
	 *
	 * @since 0.7
	 *
	 * @return bool True if CiviCRM Admin Utilities is activated, false otherwise.
	 */
	public function cau_activated() {

		// Missing if version constant not defined.
		if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_VERSION' ) ) {
			return false;
		}

		// We're good!
		return true;

	}

	/**
	 * Check if the CiviCRM Admin Utilities version meets the requirements.
	 *
	 * @since 0.7
	 *
	 * @return bool True if CiviCRM Admin Utilities meets the requirements, false otherwise.
	 */
	public function cau_version_ok() {

		// Bail if not installed.
		if ( ! $this->cau_activated() ) {
			return false;
		}

		// Fail if version is less than 0.6.8.
		if ( version_compare( CIVICRM_ADMIN_UTILITIES_VERSION, '0.6.8', '<' ) ) {
			return false;
		}

		// We're good!
		return true;

	}

	/**
	 * Check if CiviCRM Admin Utilities has been properly configured.
	 *
	 * @since 0.7
	 *
	 * @return bool True if CiviCRM Admin Utilities is configured, false otherwise.
	 */
	public function cau_configured() {

		// Fail if version is less than 0.6.8.
		if ( ! $this->cau_version_ok() ) {
			return false;
		}

		// Not configured if "Fix Soft Delete" setting is not set.
		if ( '0' === civicrm_au()->single->setting_get( 'fix_soft_delete', '0' ) ) {
			return false;
		}

		// We're good!
		return true;

	}

	/**
	 * Get the link to the CiviCRM Admin Utilities "Settings" page.
	 *
	 * @since 0.7
	 *
	 * @return str The link to the CAU "Settings" page, or empty if not found.
	 */
	public function cau_page_get_url() {

		// Fail if version is less than 0.6.8.
		if ( ! $this->cau_version_ok() ) {
			return false;
		}

		// Get all site URLs.
		$urls = civicrm_au()->single->page_get_urls();

		// Return settings page URL if it exists.
		if ( ! empty( $urls['settings'] ) ) {
			return $urls['settings'];
		}

		// Fallback.
		return '';

	}

}
