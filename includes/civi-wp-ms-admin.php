<?php

/**
 * CiviCRM WordPress Member Sync Admin class.
 *
 * Class for encapsulating admin functionality.
 *
 * @since 0.1
 *
 * @package Civi_WP_Member_Sync
 */
class Civi_WP_Member_Sync_Admin {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Migration object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $migrate The migration object.
	 */
	public $migrate;

	/**
	 * Parent Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $parent_page The parent page.
	 */
	public $parent_page;

	/**
	 * Settings Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $settings_page The settings page.
	 */
	public $settings_page;

	/**
	 * Manual Sync Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $sync_page The manual sync page.
	 */
	public $sync_page;

	/**
	 * List Association Rules Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $rules_list_page The list rules page.
	 */
	public $rules_list_page;

	/**
	 * Add or edit Association Rule Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $rules_list_page The add/edit rules page.
	 */
	public $rule_add_edit_page;

	/**
	 * Plugin version.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $plugin_version The plugin version. (numeric string)
	 */
	public $plugin_version;

	/**
	 * Plugin settings.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $settings The plugin settings.
	 */
	public $settings = array();

	/**
	 * Form error messages.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $error_strings The form error messages.
	 */
	public $error_strings;

	/**
	 * Errors in current form submission.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $error_strings The errors in current form submission.
	 */
	public $errors;

	/**
	 * When Manual Sync runs, limit processing to this number per batch.
	 *
	 * @since 0.3.7
	 * @access public
	 * @var int $batch_count The number of memberships to process per batch.
	 */
	public $batch_count = 25;

	/**
	 * Select2 Javascript flag.
	 *
	 * @since 0.4.2
	 * @access public
	 * @var bool $multiple True if Select2 library is enqueued, false otherwise.
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

		// Define errors.
		$this->error_strings = array(
			'type' => esc_html__( 'Please select a CiviCRM Membership Type', 'civicrm-wp-member-sync' ),
			'current-role' => esc_html__( 'Please select a WordPress Current Role', 'civicrm-wp-member-sync' ),
			'current-status' => esc_html__( 'Please select a Current Status', 'civicrm-wp-member-sync' ),
			'expire-status' => esc_html__( 'Please select an Expire Status', 'civicrm-wp-member-sync' ),
			'expire-role' => esc_html__( 'Please select a WordPress Expiry Role', 'civicrm-wp-member-sync' ),
			'clash-status' => esc_html__( 'You can not have the same Status Rule registered as both "Current" and "Expired"', 'civicrm-wp-member-sync' ),
		);

		// Test for constant.
		if ( defined( 'CIVI_WP_MEMBER_SYNC_MIGRATE' ) AND CIVI_WP_MEMBER_SYNC_MIGRATE ) {

			// Load our Migration utility class.
			require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-migrate.php' );

			// Instantiate.
			$this->migrate = new Civi_WP_Member_Sync_Migrate( $this );

		}

		// Initialise first.
		add_action( 'civi_wp_member_sync_initialised', array( $this, 'initialise' ), 1 );

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
		if ( 'fgffgs' == $this->option_get( 'civi_wp_member_sync_settings', 'fgffgs' ) ) {
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

		// Have we done this already?
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
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
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

		// Load plugin version.
		$this->plugin_version = $this->option_get( 'civi_wp_member_sync_version', false );

		// Perform any upgrade tasks.
		$this->upgrade_tasks();

		// Upgrade version if needed.
		if ( $this->plugin_version != CIVI_WP_MEMBER_SYNC_VERSION ) $this->store_version();

		// Load settings array.
		$this->settings = $this->option_get( 'civi_wp_member_sync_settings', $this->settings );

		// Settings upgrade tasks.
		$this->upgrade_settings();

		// Is this the back end?
		if ( is_admin() ) {

			// Multisite and network-activated?
			if ( $this->is_network_activated() ) {

				// Add admin page to Network Settings menu.
				add_action( 'network_admin_menu', array( $this, 'admin_menu' ), 30 );

			} else {

				// Add admin page to menu.
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			}

		}

	}



	/**
	 * Perform upgrade tasks.
	 *
	 * @since 0.2.7
	 */
	public function upgrade_tasks() {

		// If the current version is less than 0.2.7 and we're upgrading to 0.2.7+
		if (
			version_compare( $this->plugin_version, '0.2.7', '<' ) AND
			version_compare( CIVI_WP_MEMBER_SYNC_VERSION, '0.2.7', '>=' )
		) {

			// Check if this plugin is network-activated.
			if ( $this->is_network_activated() ) {

				// Get existing settings from local options.
				$settings = get_option( 'civi_wp_member_sync_settings', array() );

				// What if we don't have any?
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



	//##########################################################################



	/**
	 * Add this plugin's Settings Page to the WordPress admin menu.
	 *
	 * @since 0.1
	 */
	public function admin_menu() {

		// We must be network admin in multisite.
		if ( is_multisite() AND ! is_super_admin() ) return false;

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) return false;

		// Multisite?
		if ( $this->is_network_activated() ) {

			// Add settings page to the Network Settings menu.
			$this->parent_page = add_submenu_page(
				'settings.php',
				__( 'CiviCRM WordPress Member Sync: Settings', 'civicrm-wp-member-sync' ), // Page title.
				__( 'CiviCRM WordPress Member Sync', 'civicrm-wp-member-sync' ), // Menu title.
				'manage_options', // Required caps.
				'civi_wp_member_sync_parent', // Slug name.
				array( $this, 'page_settings' ) // Callback.
			);

		} else {

			// Add the settings page to the Settings menu.
			$this->parent_page = add_options_page(
				__( 'CiviCRM WordPress Member Sync: Settings', 'civicrm-wp-member-sync' ), // Page title.
				__( 'CiviCRM WordPress Member Sync', 'civicrm-wp-member-sync' ), // Menu title.
				'manage_options', // Required caps.
				'civi_wp_member_sync_parent', // Slug name.
				array( $this, 'page_settings' ) // Callback.
			);

		}

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->parent_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-' . $this->parent_page, array( $this, 'admin_head' ), 50 );

		// Add settings page.
		$this->settings_page = add_submenu_page(
			'civi_wp_member_sync_parent', // Parent slug.
			__( 'CiviCRM WordPress Member Sync: Settings', 'civicrm-wp-member-sync' ), // Page title.
			__( 'Settings', 'civicrm-wp-member-sync' ), // Menu title.
			'manage_options', // Required caps.
			'civi_wp_member_sync_settings', // Slug name.
			array( $this, 'page_settings' ) // Callback.
		);

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $this->settings_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-' . $this->settings_page, array( $this, 'admin_head' ), 50 );
		add_action( 'admin_head-' . $this->settings_page, array( $this, 'admin_menu_highlight' ), 50 );

		// Add manual sync page.
		$this->sync_page = add_submenu_page(
			'civi_wp_member_sync_parent', // Parent slug.
			__( 'CiviCRM WordPress Member Sync: Manual Sync', 'civicrm-wp-member-sync' ), // Page title.
			__( 'Manual Sync', 'civicrm-wp-member-sync' ), // Menu title.
			'manage_options', // Required caps.
			'civi_wp_member_sync_manual_sync', // Slug name.
			array( $this, 'page_manual_sync' ) // Callback.
		);

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->sync_page, array( $this, 'admin_js_sync_page' ) );
		add_action( 'admin_print_styles-' . $this->sync_page, array( $this, 'admin_css' ) );
		add_action( 'admin_print_styles-' . $this->sync_page, array( $this, 'admin_css_sync_page' ) );
		add_action( 'admin_head-' . $this->sync_page, array( $this, 'admin_head' ), 50 );
		add_action( 'admin_head-' . $this->sync_page, array( $this, 'admin_menu_highlight' ), 50 );

		// Add rules listing page.
		$this->rules_list_page = add_submenu_page(
			'civi_wp_member_sync_parent', // Parent slug.
			__( 'CiviCRM WordPress Member Sync: List Rules', 'civicrm-wp-member-sync' ), // Page title.
			__( 'List Rules', 'civicrm-wp-member-sync' ), // Menu title.
			'manage_options', // Required caps.
			'civi_wp_member_sync_list', // Slug name.
			array( $this, 'page_rules_list' ) // Callback.
		);

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->rules_list_page, array( $this, 'admin_js_list_page' ) );
		add_action( 'admin_print_styles-' . $this->rules_list_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-' . $this->rules_list_page, array( $this, 'admin_head' ), 50 );
		add_action( 'admin_head-' . $this->rules_list_page, array( $this, 'admin_menu_highlight' ), 50 );

		// Add rules page.
		$this->rule_add_edit_page = add_submenu_page(
			'civi_wp_member_sync_parent', // Parent slug.
			__( 'CiviCRM WordPress Member Sync: Association Rule', 'civicrm-wp-member-sync' ), // Page title.
			__( 'Association Rule', 'civicrm-wp-member-sync' ), // Menu title.
			'manage_options', // Required caps.
			'civi_wp_member_sync_rules', // Slug name.
			array( $this, 'page_rule_add_edit' ) // Callback.
		);

		// Add scripts and styles.
		add_action( 'admin_print_scripts-' . $this->rule_add_edit_page, array( $this, 'admin_js_rules_page' ) );
		add_action( 'admin_print_styles-' . $this->rule_add_edit_page, array( $this, 'admin_css_rules_page' ) );
		add_action( 'admin_head-' . $this->rule_add_edit_page, array( $this, 'admin_head' ), 50 );
		add_action( 'admin_head-' . $this->rule_add_edit_page, array( $this, 'admin_menu_highlight' ), 50 );

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
		$subpages = array(
		 	'civi_wp_member_sync_settings',
		 	'civi_wp_member_sync_manual_sync',
		 	'civi_wp_member_sync_list',
		 	'civi_wp_member_sync_rules',
		 );

		// This tweaks the Settings subnav menu to show only one menu item.
		if ( in_array( $plugin_page, $subpages ) ) {
			$plugin_page = 'civi_wp_member_sync_parent';
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
	public function admin_css( $dependencies = array() ) {

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
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-progressbar' ),
			CIVI_WP_MEMBER_SYNC_VERSION // Version.
		);

		// Init localisation.
		$localisation = array(
			'total' => esc_html__( '{{total}} memberships to sync...', 'civicrm-wp-member-sync' ),
			'current' => esc_html__( 'Processing memberships {{from}} to {{to}}', 'civicrm-wp-member-sync' ),
			'complete' => esc_html__( 'Processing memberships {{from}} to {{to}} complete', 'civicrm-wp-member-sync' ),
			'done' => esc_html__( 'All done!', 'civicrm-wp-member-sync' ),
		);

		// Init settings.
		$settings = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'total_memberships' => $this->plugin->members->memberships_get_count(),
			'batch_count' => $this->setting_get_batch_count(),
		);

		// Localisation array.
		$vars = array(
			'localisation' => $localisation,
			'settings' => $settings,
		);

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
		$dependencies = array();

		/**
		 * Allow CSS dependencies to be injected.
		 *
		 * @since 0.4
		 *
		 * @param array $dependencies The existing dependencies.
		 * @return array $dependencies The modified dependencies.
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
		$dependencies = array( 'jquery', 'jquery-form' );

		/**
		 * Filter dependencies.
		 *
		 * @since 0.4
		 *
		 * @param array $dependencies The existing dependencies.
		 * @return array $dependencies The modified dependencies.
		 */
		$dependencies = apply_filters( 'civi_wp_member_sync_rules_js_dependencies', $dependencies );

		// Add JavaScript plus dependencies.
		wp_enqueue_script(
			'civi_wp_member_sync_rules_js',
			plugins_url( 'assets/js/civi-wp-ms-rules.js', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			$dependencies,
			CIVI_WP_MEMBER_SYNC_VERSION // Version.
		);

		// Set defaults.
		$vars = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'method' => $this->setting_get_method(),
			'mode' => 'add',
			'select2' => 'no',
			'groups' => 'no',
		);

		// Maybe override select2.
		if ( in_array( 'civi_wp_member_sync_select2_js', $dependencies ) ) {
			$vars['select2'] = 'yes';
		}

		// Maybe override groups.
		if ( $this->plugin->groups->enabled() ) {
			$vars['groups'] = 'yes';
		}

		// Maybe override mode.
		if ( isset( $_GET['mode'] ) AND $_GET['mode'] == 'edit' ) {
			if ( isset( $_GET['type_id'] ) AND is_numeric( $_GET['type_id'] ) ) {
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
		$dependencies = array( 'jquery', 'jquery-form' );

		/**
		 * Filter dependencies.
		 *
		 * @since 0.4.2
		 *
		 * @param array $dependencies The existing dependencies.
		 * @return array $dependencies The modified dependencies.
		 */
		$dependencies = apply_filters( 'civi_wp_member_sync_list_js_dependencies', $dependencies );

		// Add JavaScript plus dependencies.
		wp_enqueue_script(
			'civi_wp_member_sync_list_js',
			plugins_url( 'assets/js/civi-wp-ms-list.js', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			$dependencies,
			CIVI_WP_MEMBER_SYNC_VERSION // Version.
		);

		// Set defaults.
		$vars = array(
			'method' => $this->setting_get_method(),
			'dialog_text' => esc_html__( 'Delete this Association Rule?', 'civicrm-wp-member-sync' ),
			'dialog_text_all' => esc_html__( 'Delete all Association Rules?', 'civicrm-wp-member-sync' ),
		);

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
		$pages = array(
			$this->settings_page . $page,
			$this->sync_page . $page,
			$this->rules_list_page . $page,
			$this->rule_add_edit_page . $page,
		);

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages ) ) { return $screen; }

		// Add a tab - we can add more later.
		$screen->add_help_tab( array(
			'id'      => 'civi_wp_member_sync',
			'title'   => esc_html__( 'CiviCRM WordPress Member Sync', 'civicrm-wp-member-sync' ),
			'content' => $this->get_help(),
		));

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
		$help = '<p>' . esc_html__( 'For further information about using CiviCRM WordPress Member Sync, please refer to the README.md that comes with this plugin.', 'civicrm-wp-member-sync' ) . '</p>';

		// --<
		return $help;

	}



	//##########################################################################



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

		// Bail if done.
		if ( true === $dependencies_done ) {
			return $dependencies;
		}

		// Define our handle.
		$handle = 'civi_wp_member_sync_select2_css';

		// Register Select2 styles.
		wp_register_style(
			$handle,
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/css/select2.min.css' )
		);

		// Enqueue styles.
		wp_enqueue_style( $handle );

		// Add to dependencies.
		$dependencies[] = $handle;

		// Set flags.
		$dependencies_done = true;
		$this->select2 = true;

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

		// Bail if done.
		if ( true === $dependencies_done ) {
			return $dependencies;
		}

		// Define our handle.
		$handle = 'civi_wp_member_sync_select2_js';

		// Register Select2.
		wp_register_script(
			$handle,
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js' ),
			array( 'jquery' )
		);

		// Enqueue script.
		wp_enqueue_script( $handle );

		// Add to dependencies.
		$dependencies[] = $handle;

		// Set flags.
		$dependencies_done = true;
		$this->select2 = true;

		// --<
		return $dependencies;

	}



	//##########################################################################



	/**
	 * Show settings page.
	 *
	 * @since 0.1
	 */
	public function page_settings() {

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->page_get_urls();

		// If we have the legacy plugin, include template file and bail.
		if ( isset( $this->migrate ) AND $this->migrate->legacy_plugin_exists() ) {
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/migrate.php' );
			return;
		}

		// Get our sync method.
		$method = $this->setting_get_method();

		// Get all schedules.
		$schedules = $this->plugin->schedule->intervals_get();

		// Get our sync settings.
		$login = absint( $this->setting_get( 'login' ) );
		$civicrm = absint( $this->setting_get( 'civicrm' ) );
		$schedule = absint( $this->setting_get( 'schedule' ) );

		// Get our interval setting.
		$interval = $this->setting_get( 'interval' );

		// Get our types setting.
		$types = absint( $this->setting_get( 'types' ) );

		// Check if CiviCRM Admin Utilities has been installed.
		$cau_present = $this->cau_activated();

		// Check the version of CiviCRM Admin Utilities is okay.
		$cau_version_ok = $this->cau_version_ok();

		// Check if CiviCRM Admin Utilities has been configured.
		$cau_configured = $this->cau_configured();

		// Get Settings page link.
		$cau_link = $this->cau_page_get_url();
		
		// Get list of WordPress roles.
		$roles = $this->plugin->users->wp_role_names_get_all();

		// Include template file.
		include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/settings.php' );

	}



	/**
	 * Show manual sync page.
	 *
	 * @since 0.1
	 */
	public function page_manual_sync() {

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->page_get_urls();

		// Include template file.
		include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/manual-sync.php' );

	}



	/**
	 * Show rules list page.
	 *
	 * @since 0.1
	 */
	public function page_rules_list() {

		// Check user permissions.
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
		$data = ( isset( $all_data[$method] ) ) ? $all_data[$method] : array();

		// Get all membership types.
		$membership_types = $this->plugin->members->types_get_all();

		// Assume we don't have all types.
		$have_all_types = false;

		// Override if we have all types populated.
		if ( count( $data ) === count( $membership_types ) ) {
			$have_all_types = true;
		}

		/**
		 * Allow error strings to be filtered.
		 *
		 * @since 0.3.9
		 *
		 * @param array $error_strings The existing array of error strings.
		 * @return array $error_strings The modified array of error strings.
		 */
		$this->error_strings = apply_filters( 'civi_wp_member_sync_error_strings', $this->error_strings );

		// Include per method.
		if ( $method == 'roles' ) {
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/list_roles.php' );
		} else {
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/list_caps.php' );
		}

	}



	/**
	 * Decide whether to show add or edit page.
	 *
	 * @since 0.1
	 */
	public function page_rule_add_edit() {

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Default mode.
		$mode = 'add';

		// Do we want to populate the form?
		if ( isset( $_GET['mode'] ) AND $_GET['mode'] == 'edit' ) {
			if ( isset( $_GET['type_id'] ) AND is_numeric( $_GET['type_id'] ) ) {
				$mode = 'edit';
			}
		}

		/**
		 * Allow error strings to be filtered.
		 *
		 * @since 0.3.9
		 *
		 * @param array $error_strings The existing array of error strings.
		 * @return array $error_strings The modified array of error strings.
		 */
		$this->error_strings = apply_filters( 'civi_wp_member_sync_error_strings', $this->error_strings );

		// Route by mode.
		if ( $mode == 'add' ) {
			$this->page_rule_add();
		} elseif ( $mode == 'edit' ) {
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

		// Get all membership types.
		$membership_types = $this->plugin->members->types_get_all();

		// Get all membership status rules.
		$status_rules = $this->plugin->members->status_rules_get_all();

		// Get method.
		$method = $this->setting_get_method();

		// Get rules.
		$rules = $this->rules_get_by_method( $method );

		// If we get some.
		if ( $rules !== false AND is_array( $rules ) AND count( $rules ) > 0 ) {

			// Get used membership type IDs.
			$type_ids = array_keys( $rules );

			// Loop and remove from membership_types array.
			foreach( $type_ids AS $type_id ) {
				if ( isset( $membership_types[$type_id] ) ) {
					unset( $membership_types[$type_id] );
				}
			}

		}

		// Convert select to multi-select.
		$multiple = '';
		if ( defined( 'CIVI_WP_MEMBER_SYNC_MULTIPLE' ) AND CIVI_WP_MEMBER_SYNC_MULTIPLE === true ) {
			$multiple = ' multiple="multiple" style="min-width: 240px;"';
		}

		// Well?
		if ( $method == 'roles' ) {

			// Get filtered roles.
			$roles = $this->plugin->users->wp_role_names_get_all();

			// Include template file.
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-role-add.php' );

		} else {

			// Include template file.
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-cap-add.php' );

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

		// Get all membership types.
		$membership_types = $this->plugin->members->types_get_all();

		// Get all membership status rules.
		$status_rules = $this->plugin->members->status_rules_get_all();

		// Get method.
		$method = $this->setting_get_method();

		// Get requested membership type ID.
		$civi_member_type_id = absint( $_GET['type_id'] );

		// Get rule by type.
		$selected_rule = $this->rule_get_by_type( $civi_member_type_id, $method );

		// Set vars for populating form.
		$current_rule = $selected_rule['current_rule'];
		$expiry_rule = $selected_rule['expiry_rule'];

		// Get rules.
		$rules = $this->rules_get_by_method( $method );

		// If we get some.
		if ( $rules !== false AND is_array( $rules ) AND count( $rules ) > 0 ) {

			// Get used membership type IDs.
			$type_ids = array_keys( $rules );

			// Loop and remove from membership_types array.
			foreach( $type_ids AS $type_id ) {
				if ( isset( $membership_types[$type_id] ) AND $civi_member_type_id != $type_id ) {
					unset( $membership_types[$type_id] );
				}
			}

		}

		// Do we need roles?
		if ( $method == 'roles' ) {

			// Get filtered roles.
			$roles = $this->plugin->users->wp_role_names_get_all();

			// Get stored roles.
			$current_wp_role = $selected_rule['current_wp_role'];
			$expired_wp_role = $selected_rule['expired_wp_role'];

			// Include template file.
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-role-edit.php' );

		} else {

			// Include template file.
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-cap-edit.php' );

		}

	}



	//##########################################################################



	/**
	 * Get admin page URLs.
	 *
	 * @since 0.1
	 *
	 * @return array $admin_urls The array of admin page URLs.
	 */
	public function page_get_urls() {

		// Only calculate once.
		if ( isset( $this->urls ) ) { return $this->urls; }

		// Init return.
		$this->urls = array();

		// Multisite?
		if ( $this->is_network_activated() ) {

			// Get admin page URLs via our adapted method.
			$this->urls['settings'] = $this->network_menu_page_url( 'civi_wp_member_sync_settings', false );
			$this->urls['manual_sync'] = $this->network_menu_page_url( 'civi_wp_member_sync_manual_sync', false );
			$this->urls['list'] = $this->network_menu_page_url( 'civi_wp_member_sync_list', false );
			$this->urls['rules'] = $this->network_menu_page_url( 'civi_wp_member_sync_rules', false );

		} else {

			// Get admin page URLs.
			$this->urls['settings'] = menu_page_url( 'civi_wp_member_sync_settings', false );
			$this->urls['manual_sync'] = menu_page_url( 'civi_wp_member_sync_manual_sync', false );
			$this->urls['list'] = menu_page_url( 'civi_wp_member_sync_list', false );
			$this->urls['rules'] = menu_page_url( 'civi_wp_member_sync_rules', false );

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
	 * @param bool $echo Whether or not to echo the url - default is true.
	 * @return string $url The URL.
	 */
	public function network_menu_page_url( $menu_slug, $echo = true ) {
		global $_parent_pages;

		if ( isset( $_parent_pages[$menu_slug] ) ) {
			$parent_slug = $_parent_pages[$menu_slug];
			if ( $parent_slug && ! isset( $_parent_pages[$parent_slug] ) ) {
				$url = network_admin_url( add_query_arg( 'page', $menu_slug, $parent_slug ) );
			} else {
				$url = network_admin_url( 'admin.php?page=' . $menu_slug );
			}
		} else {
			$url = '';
		}

		$url = esc_url( $url );

		if ( $echo ) echo $url;

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
		$target_url = $_SERVER['REQUEST_URI'];
		$url_array = explode( '&', $target_url );
		if ( $url_array ) {
			$target_url = htmlentities( $url_array[0] . '&updated=true' );
		}

		// --<
		return $target_url;

	}



	//##########################################################################



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

		// Was the "Migrate" form submitted?
		if ( isset( $_POST['civi_wp_member_sync_migrate_submit'] ) ) {
			$result = $this->migrate->legacy_migrate();
		}

		// Was the "Settings" form submitted?
		if ( isset( $_POST['civi_wp_member_sync_settings_submit'] ) ) {
			$result = $this->settings_update();
		}

	 	// Was the "Stop Sync" button pressed?
		if ( isset( $_POST['civi_wp_member_sync_manual_sync_stop'] ) ) {
			delete_option( '_civi_wpms_memberships_offset' );
			return;
		}

		// Was the "Manual Sync" form submitted?
		if ( isset( $_POST['civi_wp_member_sync_manual_sync_submit'] ) ) {

			// Check that we trust the source of the request.
			check_admin_referer( 'civi_wp_member_sync_manual_sync_action', 'civi_wp_member_sync_nonce' );

			/**
			 * Let other plugins know that we're about to sync all users.
			 *
			 * @since 0.1
			 */
			do_action( 'civi_wp_member_sync_pre_sync_all' );

			// Sync all memberships for *existing* WordPress users.
			$result = $this->plugin->members->sync_all_civicrm_memberships();

			/**
			 * Let other plugins know that we've synced all users.
			 *
			 * @since 0.1
			 */
			do_action( 'civi_wp_member_sync_after_sync_all' );

		}

		// Was the "Rule" form submitted?
		if ( isset( $_POST['civi_wp_member_sync_rules_submit'] ) ) {
			$result = $this->rule_update();
		}

		// Was the "Clear Association Rules" form submitted?
		if ( isset( $_POST['civi_wp_member_sync_clear_submit'] ) ) {
			$result = $this->rules_clear();
		}

		// Was a "Delete" link clicked?
		if ( isset( $_GET['syncrule'] ) AND $_GET['syncrule'] == 'delete' ) {
			if ( ! empty( $_GET['type_id'] ) AND is_numeric( $_GET['type_id'] ) ) {
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
		$settings = array();

		// Set empty data arrays.
		$settings['data']['roles'] = array();
		$settings['data']['capabilities'] = array();

		// Set default method.
		$settings['method'] = 'capabilities';

		// Switch all sync settings on by default.
		$settings['login'] = 1;
		$settings['civicrm'] = 1;
		$settings['schedule'] = 1;

		// Set default schedule interval.
		$settings['interval'] = 'daily';

		// Sync only the Individual contact type by default.
		$settings['types'] = 1;

		/**
		 * Allow settings to be filtered.
		 *
		 * @since 0.1
		 *
		 * @param array $settings The default settings for this plugin
		 * @return array $settings The modified default settings for this plugin
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
		$settings_method = 'capabilities';
		if ( isset( $_POST['civi_wp_member_sync_settings_method'] ) ) {
			$settings_method = trim( $_POST['civi_wp_member_sync_settings_method'] );
		}
		$this->setting_set( 'method', $settings_method );
		
		// Synchronize the default role.
		$settings_default_wp_role = null;
		if ( isset( $_POST['civi_wp_member_sync_settings_default_wp_role'] ) ) {
			$settings_default_wp_role = trim ( $_POST['civi_wp_member_sync_settings_default_wp_role'] );
		}
		$this->setting_set ( 'default_wp_role', $settings_default_wp_role );

		// Login/logout sync enabled.
		if ( isset( $_POST['civi_wp_member_sync_settings_login'] ) ) {
			$settings_login = absint( $_POST['civi_wp_member_sync_settings_login'] );
		} else {
			$settings_login = 0;
		}
		$this->setting_set( 'login', ( $settings_login ? 1 : 0 ) );

		// CiviCRM sync enabled.
		if ( isset( $_POST['civi_wp_member_sync_settings_civicrm'] ) ) {
			$settings_civicrm = absint( $_POST['civi_wp_member_sync_settings_civicrm'] );
		} else {
			$settings_civicrm = 0;
		}
		$this->setting_set( 'civicrm', ( $settings_civicrm ? 1 : 0 ) );

		// Get existing schedule.
		$existing_schedule = $this->setting_get( 'schedule' );

		// Schedule sync enabled.
		if ( isset( $_POST['civi_wp_member_sync_settings_schedule'] ) ) {
			$settings_schedule = absint( $_POST['civi_wp_member_sync_settings_schedule'] );
		} else {
			$settings_schedule = 0;
		}
		$this->setting_set( 'schedule', ( $settings_schedule ? 1 : 0 ) );

		// Is the schedule being deactivated?
		if ( $existing_schedule == 1 AND $settings_schedule === 0 ) {

			// Clear current scheduled event.
			$this->plugin->schedule->unschedule();

		}

		// Schedule interval.
		if ( isset( $_POST['civi_wp_member_sync_settings_interval'] ) ) {

			// Get existing interval.
			$existing_interval = $this->setting_get( 'interval' );

			// Get value passed in.
			$settings_interval = esc_sql( trim( $_POST['civi_wp_member_sync_settings_interval'] ) );

			// Is the schedule active and has the interval changed?
			if ( $settings_schedule AND $settings_interval != $existing_interval ) {

				// Clear current scheduled event.
				$this->plugin->schedule->unschedule();

				// Now add new scheduled event.
				$this->plugin->schedule->schedule( $settings_interval );

			}

			// Set new value whatever (for now).
			$this->setting_set( 'interval', $settings_interval );

		}

		// Sync restricted to Individuals?
		if ( isset( $_POST['civi_wp_member_sync_settings_types'] ) ) {
			$settings_types = absint( $_POST['civi_wp_member_sync_settings_types'] );
		} else {
			$settings_types = 0;
		}
		$this->setting_set( 'types', ( $settings_types ? 1 : 0 ) );

		// Save settings.
		$this->settings_save();

		// Get admin URLs.
		$urls = $this->page_get_urls();

		// Redirect to settings page with message.
		wp_redirect( $urls['settings'] . '&updated=true' );
		die();

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
	public function setting_exists( $setting_name = '' ) {

		// Sanity check.
		if ( $setting_name == '' ) {
			die( esc_html__( 'You must supply a setting to setting_exists()', 'civicrm-wp-member-sync' ) );
		}

		// Get existence of setting in array.
		return array_key_exists( $setting_name, $this->settings );

	}



	/**
	 * Return a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @return mixed $setting The value of the setting.
	 */
	public function setting_get( $setting_name = '', $default = false ) {

		// Sanity check.
		if ( $setting_name == '' ) {
			die( esc_html__( 'You must supply a setting to setting_get()', 'civicrm-wp-member-sync' ) );
		}

		// Get setting.
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[$setting_name] : $default;

	}



	/**
	 * Set a value for a specified setting.
	 *
	 * @since 0.1
	 */
	public function setting_set( $setting_name = '', $value = '' ) {

		// Sanity check.
		if ( $setting_name == '' ) {
			die( esc_html__( 'You must supply a setting to setting_set()', 'civicrm-wp-member-sync' ) );
		}

		// Set setting.
		$this->settings[$setting_name] = $value;

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
		$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

		// --<
		return $method;

	}
	
	

	/**
	 * Return the value for the 'default_wp_role' setting.
	 *
	 * Added as a separate method to for consistency with seting_get_method.
	 * Also allows for future sanitization rules.
	 * 
	 * @since TBD
	 *
	 * @return str $default_wp_role The value of the 'default_wp_role' setting.
	 */
	public function setting_get_default_wp_role() {
		
		if (setting_exists('default_wp_role') {
			// Get default WordPress role.
			$default_wp_role = $this->setting_get( 'default_wp_role' );
		} else {
			$default_wp_role = null;
		}

		// --<
		return $default_wp_role;

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
		 * Filter the batch count.
		 *
		 * Overriding this value allows the batch process to be controlled such
		 * that installs with large numbers of memberships running on faster
		 * machines can reduce the time taken to perform the sync process.
		 *
		 * @since 0.3.7
		 *
		 * @param int $count The default number of memberships to process per batch.
		 * @param int $count The modified number of memberships to process per batch.
		 */
		$count = apply_filters( 'civi_wp_member_sync_get_batch_count', $count );

		// --<
		return $count;

	}



	//##########################################################################



	/**
	 * Get a WordPress option.
	 *
	 * @since 0.2.7
	 *
	 * @param string $key The option name.
	 * @param mixed $default The default option value if none exists.
	 * @return mixed $value The option value.
	 */
	public function option_get( $key, $default = null ) {

		// If multisite and network activated.
		if ( $this->is_network_activated() ) {

			// Get site option.
			$value = get_site_option( $key, $default );

		} else {

			// Get option.
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
	 * @param mixed $value The value to save.
	 */
	public function option_save( $key, $value ) {

		// If multisite and network activated.
		if ( $this->is_network_activated() ) {

			// Update site option.
			update_site_option( $key, $value );

		} else {

			// Update option.
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

			// Delete site option.
			delete_site_option( $key );

		} else {

			// Delete option.
			delete_option( $key );

		}

	}



	//##########################################################################



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
		$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

		// Get subset by method.
		$subset = ( isset( $data[$method] ) ) ? $data[$method] : false;

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
		$subset = ( isset( $data[$method] ) ) ? $data[$method] : false;

		// Sanity check.
		if ( ! $subset ) return;

		// Loop through them.
		foreach( $subset AS $type_id => $rule ) {

			/**
			 * Broadcast that we're deleting an association rule. This creates two
			 * actions, depending on the sync method:
			 *
			 * civi_wp_member_sync_rule_delete_roles
			 * civi_wp_member_sync_rule_delete_capabilities
			 *
			 * @param array $rule The association rule we're going to delete.
			 */
			do_action( 'civi_wp_member_sync_rule_delete_' . $method, $rule );

		}

		// Update data.
		$data[$method] = array();

		// Overwrite data.
		$this->setting_set( 'data', $data );

		// Save.
		$this->settings_save();

		// Get admin URLs.
		$urls = $this->page_get_urls();

		// Redirect to list page with message.
		wp_redirect( $urls['list'] . '&syncrule=delete-all' );
		die();

	}



	/**
	 * Get an association rule by membership type ID.
	 *
	 * @since 0.1
	 *
	 * @param int $type_id The numeric ID of the CiviCRM membership type.
	 * @param string $method The sync method (either 'roles' or 'capabilities').
	 * @return mixed $rule Array if successful, boolean false otherwise.
	 */
	public function rule_get_by_type( $type_id, $method = 'roles' ) {

		// Get data.
		$data = $this->setting_get( 'data' );

		// Sanitize method.
		$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

		// Get subset by method.
		$subset = ( isset( $data[$method] ) ) ? $data[$method] : false;

		// Get data for this type_id.
		$rule = ( isset( $subset[$type_id] ) ) ? $subset[$type_id] : false;

		// --<
		return $rule;

	}



	/**
	 * Update (or add) a membership rule.
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
		if (
			isset( $_POST['civi_wp_member_sync_rules_mode'] ) AND
			$_POST['civi_wp_member_sync_rules_mode'] == 'edit'
		) {
			$mode = 'edit';
		}

		// Get sync method and sanitize.
		$method = $this->setting_get_method();

		// Default "multiple" to false.
		$multiple = false;

		// Test our hidden "multiple" element.
		if (
			isset( $_POST['civi_wp_member_sync_rules_multiple'] ) AND
			trim( $_POST['civi_wp_member_sync_rules_multiple'] ) == 'yes'
		) {
			$multiple = true;
		}

		// Init errors.
		$this->errors = array();

		// Depending on the "multiple" flag, validate membership types.
		if ( $multiple === true ) {

			// Check and sanitise CiviCRM Membership Types.
			if(
				isset( $_POST['civi_member_type_id'] ) AND
				! empty( $_POST['civi_member_type_id'] ) AND
				is_array( $_POST['civi_member_type_id'] )
			) {

				// Grab array from POST.
				$civi_member_type_ids = $_POST['civi_member_type_id'];

				// Sanitise array contents.
				array_walk(
					$civi_member_type_ids,
					function( &$item ) {
						$item = intval( trim( $item ) );
					}
				);

			} else {
				$this->errors[] = 'type';
			}

		} else {

			// Check and sanitise CiviCRM Membership Type.
			if(
				isset( $_POST['civi_member_type_id'] ) AND
				! empty( $_POST['civi_member_type_id'] ) AND
				is_numeric( $_POST['civi_member_type_id'] )
			) {
				$civi_member_type_id = absint( $_POST['civi_member_type_id'] );
			} else {
				$this->errors[] = 'type';
			}

		}

		// Check and sanitise Current Status.
		if (
			isset( $_POST['current'] ) AND
			is_array( $_POST['current'] ) AND
			! empty( $_POST['current'] )
		) {

			// Grab array from POST.
			$current_rule = $_POST['current'];

			// Sanitise array contents.
			array_walk(
				$current_rule,
				function( &$item ) {
					$item = intval( trim( $item ) );
				}
			);

		} else {
			$this->errors[] = 'current-status';
		}

		// Check and sanitise Expire Status.
		if (
			isset( $_POST['expire'] ) AND
			is_array( $_POST['expire'] ) AND
			! empty( $_POST['expire'] )
		) {

			// Grab array from POST.
			$expiry_rule = $_POST['expire'];

			// Sanitise array contents.
			array_walk(
				$expiry_rule,
				function( &$item ) {
					$item = intval( trim( $item ) );
				}
			);

		} else {
			$this->errors[] = 'expire-status';
		}

		// Init current-expire check (will end up true if there's a clash).
		$current_expire_clash = false;

		// Do we have both arrays?
		if ( isset( $current_rule ) AND isset( $expiry_rule ) ) {

			// Check 'current' array against 'expire' array.
			$intersect = array_intersect_assoc( $current_rule, $expiry_rule );
			if ( ! empty( $intersect ) ) {
				$current_expire_clash = true;
			}

		}

		// Do we want roles?
		if ( $method == 'roles' ) {

			// Check and sanitise WordPress Role.
			if(
				isset( $_POST['current_wp_role'] ) AND
				! empty( $_POST['current_wp_role'] )
			) {
				$current_wp_role = esc_sql( trim( $_POST['current_wp_role'] ) );
			} else {
				$this->errors[] = 'current-role';
			}

			// Check and sanitise Expiry Role.
			if (
				isset( $_POST['expire_assign_wp_role'] ) AND
				! empty( $_POST['expire_assign_wp_role'] )
			) {
				$expired_wp_role = esc_sql( trim( $_POST['expire_assign_wp_role'] ) );
			} else {
				$this->errors[] = 'expire-role';
			}

		}

		// How did we do?
		if ( $current_expire_clash === false AND empty( $this->errors ) ) {

			// Depending on the "multiple" flag, create rules.
			if ( $multiple === true ) {

				// Save each rule in turn.
				foreach( $civi_member_type_ids AS $civi_member_type_id ) {

					// Which sync method are we using?
					if ( $method == 'roles' ) {

						// Combine rule data into array.
						$rule_data = array(
							'current_rule' => $current_rule,
							'current_wp_role' => $current_wp_role,
							'expiry_rule' => $expiry_rule,
							'expired_wp_role' => $expired_wp_role,
						);

						// Get formatted array.
						$rule = $this->rule_create_array( $method, $rule_data );

						// Apply rule data and save.
						$this->rule_save( $rule, $mode, $method, $civi_member_type_id );

					} else {

						// Combine rule data into array.
						$rule_data = array(
							'current_rule' => $current_rule,
							'expiry_rule' => $expiry_rule,
							'civi_member_type_id' =>  $civi_member_type_id,
						);

						// Get formatted array.
						$rule = $this->rule_create_array( $method, $rule_data );

						// Apply rule data and save.
						$this->rule_save( $rule, $mode, $method, $civi_member_type_id );

					}

				}

			} else {

				// Which sync method are we using?
				if ( $method == 'roles' ) {

					// Combine rule data into array.
					$rule_data = array(
						'current_rule' => $current_rule,
						'current_wp_role' => $current_wp_role,
						'expiry_rule' => $expiry_rule,
						'expired_wp_role' => $expired_wp_role,
					);

					// Get formatted array.
					$rule = $this->rule_create_array( $method, $rule_data );

					// Apply rule data and save.
					$this->rule_save( $rule, $mode, $method, $civi_member_type_id );

				} else {

					// Combine rule data into array.
					$rule_data = array(
						'current_rule' => $current_rule,
						'expiry_rule' => $expiry_rule,
						'civi_member_type_id' =>  $civi_member_type_id,
					);

					// Get formatted array.
					$rule = $this->rule_create_array( $method, $rule_data );

					// Apply rule data and save.
					$this->rule_save( $rule, $mode, $method, $civi_member_type_id );

				}

			}

			// Get admin URLs.
			$urls = $this->page_get_urls();

			// Redirect to list page.
			wp_redirect( $urls['list'] . '&syncrule=' . $mode );
			die();

		} else {

			// Are there status clashes?
			if ( $current_expire_clash === false ) {
				$this->errors[] = 'clash-status';
			}

			// Sad face.
			return false;

		}

	}



	/**
	 * Create a membership rule array.
	 *
	 * @since 0.4.2
	 *
	 * @param str $method The sync method.
	 * @param array $params The params to build the array from.
	 * @return array $rule The constructed rule array.
	 */
	public function rule_create_array( $method, $params ) {

		// Which sync method are we using?
		if ( $method == 'roles' ) {

			// Construct role rule.
			$rule = array(
				'current_rule' => $params['current_rule'],
				'current_wp_role' => $params['current_wp_role'],
				'expiry_rule' => $params['expiry_rule'],
				'expired_wp_role' => $params['expired_wp_role'],
			);

		} else {

			// Construct capability rule.
			$rule = array(
				'current_rule' => $params['current_rule'],
				'expiry_rule' => $params['expiry_rule'],
				'capability' => CIVI_WP_MEMBER_SYNC_CAP_PREFIX . $params['civi_member_type_id'],
			);

		}

		// --<
		return $rule;

	}



	/**
	 * Save a membership rule.
	 *
	 * @since 0.4.2
	 *
	 * @param array $rule The new or updated association rule.
	 * @param str $mode The mode ('add' or 'edit').
	 * @param str $method The sync method.
	 * @param int $$civi_member_type_id The numeric ID of the membership type.
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
		 * Broadcast our association rule before it is saved.
		 *
		 * This creates four possible actions:
		 *
		 * civi_wp_member_sync_rule_add_roles
		 * civi_wp_member_sync_rule_add_capabilities
		 * civi_wp_member_sync_rule_edit_roles
		 * civi_wp_member_sync_rule_edit_capabilities
		 *
		 * @since 0.2.3
		 *
		 * @param array $rule The new or updated association rule.
		 */
		do_action( 'civi_wp_member_sync_rule_' . $mode . '_' . $method, $rule );

		// Insert/overwrite item in data array.
		$data[$method][$civi_member_type_id] = $rule;

		// Overwrite data.
		$this->setting_set( 'data', $data );

		// Save.
		$this->settings_save();

		/**
		 * Broadcast that we have saved our association rule.
		 *
		 * This creates four possible actions:
		 *
		 * civi_wp_member_sync_rule_add_roles_saved
		 * civi_wp_member_sync_rule_add_capabilities_saved
		 * civi_wp_member_sync_rule_edit_roles_saved
		 * civi_wp_member_sync_rule_edit_capabilities_saved
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
	 * Delete a membership rule.
	 *
	 * @since 0.1
	 *
	 * @return bool $success True if successful, false otherwise.
	 */
	public function rule_delete() {

		// Check nonce.
		if (
			! isset( $_GET['civi_wp_member_sync_delete_nonce'] ) OR
			! wp_verify_nonce( $_GET['civi_wp_member_sync_delete_nonce'], 'civi_wp_member_sync_delete_link' )
		) {

			wp_die( esc_html__( 'Cheating, eh?', 'civicrm-wp-member-sync' ) );
			exit();

		}

		// Get membership type.
		$type_id = absint( $_GET['type_id'] );

		// Sanity check.
		if ( empty( $type_id ) ) return;

		// Get method.
		$method = $this->setting_get_method();

		// Get data.
		$data = $this->setting_get( 'data' );

		// Get subset by method.
		$subset = ( isset( $data[$method] ) ) ? $data[$method] : false;

		// Sanity check.
		if ( ! $subset ) return;
		if ( ! isset( $subset[$type_id] ) ) return;

		/**
		 * Broadcast that we're deleting an association rule. This creates two
		 * actions, depending on the sync method:
		 *
		 * civi_wp_member_sync_rule_delete_roles
		 * civi_wp_member_sync_rule_delete_capabilities
		 *
		 * @param array The association rule we're going to delete.
		 */
		do_action( 'civi_wp_member_sync_rule_delete_' . $method, $subset[$type_id] );

		// Delete it.
		unset( $subset[$type_id] );

		// Update data.
		$data[$method] = $subset;

		// Overwrite data.
		$this->setting_set( 'data', $data );

		// Save.
		$this->settings_save();

		// Get admin URLs.
		$urls = $this->page_get_urls();

		// Redirect to list page with message.
		wp_redirect( $urls['list'] . '&syncrule=delete' );
		die();

	}



	/**
	 * Check if there is at least one rule applied to a set of memberships.
	 *
	 * The reason for this method is, as @andymyersau points out, that users
	 * should not be created unless there is an Association Rule that applies
	 * to them. This method therefore checks for the existence of at least one
	 * applicable rule for a given set of memberships.
	 *
	 * @since 0.3.7
	 *
	 * @param array $memberships The memberships to analyse.
	 * @return bool $has_rule True if a rule applies, false otherwise.
	 */
	public function rule_exists( $memberships = false ) {

		// Assume no rule applies.
		$has_rule = false;

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return $has_rule;

		// Bail if we didn't get memberships passed.
		if ( $memberships === false ) return $has_rule;
		if ( empty( $memberships ) ) return $has_rule;

		// Get sync method.
		$method = $this->setting_get_method();

		// Loop through the supplied memberships.
		foreach( $memberships['values'] AS $membership ) {

			// Continue with next membership if something went wrong.
			if ( empty( $membership['membership_type_id'] ) ) continue;

			// Get membership type.
			$membership_type_id = $membership['membership_type_id'];

			// Get association rule for this membership type.
			$association_rule = $this->rule_get_by_type( $membership_type_id, $method );

			// Continue with next membership if we have an error or no rule exists.
			if ( $association_rule === false ) continue;

			// Continue with next membership if something is wrong with rule.
			if ( empty( $association_rule['current_rule'] ) ) continue;
			if ( empty( $association_rule['expiry_rule'] ) ) continue;

			// Which sync method are we using?
			if ( $method == 'roles' ) {

				// Continue with next membership if something is wrong with rule.
				if ( empty( $association_rule['current_wp_role'] ) ) continue;
				if ( empty( $association_rule['expired_wp_role'] ) ) continue;

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
	 * Manage WordPress roles or capabilities based on the status of a user's memberships.
	 *
	 * The following notes are to describe how this method should be enhanced:
	 *
	 * There are sometimes situations - when there are multiple roles assigned via
	 * multiple memberships - where a role may be incorrectly removed (and perhaps
	 * added but I haven't tested that fully yet) if the association rules share a
	 * common "expired" role, such as "Anonymous User".
	 *
	 * The current logic may remove the expired role because other rules may be
	 * applied after the rule which assigns the expired role. If they are - and
	 * they are not expired memberships - the expired rule will therefore be
	 * removed from the user.
	 *
	 * It seems that what's needed is to parse the rules prior to applying them
	 * to determine the final set of roles that a user should have. These rules
	 * can then be applied in one go, thus avoiding the overrides resulting from
	 * the conflicting rules.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object of the user in question.
	 * @param array $memberships The memberships of the WordPress user in question.
	 */
	public function rule_apply( $user, $memberships = false ) {

		// Removed check for admin user - DO NOT call this for admins UNLESS
		// You're using a plugin that enables multiple roles.

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return;

		// Kick out if we didn't get memberships passed.
		if ( $memberships === false ) return;

		// Get sync method.
		$method = $this->setting_get_method();
		
		// Get default role. This hasn't been defined anywhere yet, so we will have to write it.
		$default_wp_role = $this->setting_get_default_wp_role();

		// Loop through the supplied memberships.
		foreach( $memberships['values'] AS $membership ) {

			// Continue if something went wrong.
			if ( ! isset( $membership['membership_type_id'] ) ) continue;
			if ( ! isset( $membership['status_id'] ) ) continue;

			// Get membership type and status rule.
			$membership_type_id = $membership['membership_type_id'];
			$status_id = $membership['status_id'];

			// Get association rule for this membership type.
			$association_rule = $this->rule_get_by_type( $membership_type_id, $method );

			// Continue with next rule if we have an error of some kind.
			if ( $association_rule === false ) continue;

			// Get status rules.
			$current_rule = $association_rule['current_rule'];
			$expiry_rule = $association_rule['expiry_rule'];

			// Which sync method are we using?
			if ( $method == 'roles' ) {

				// SYNC ROLES

				// Continue if something went wrong.
				if ( empty( $association_rule['current_wp_role'] ) ) continue;
				if ( empty( $association_rule['expired_wp_role'] ) ) continue;

				// Get roles for this association rule.
				$current_wp_role = $association_rule['current_wp_role'];
				$expired_wp_role = $association_rule['expired_wp_role'];

				// Does the user's membership status match a current status rule?
				if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {

					// Add current role if the user does not have it.
					if ( ! $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
						$this->plugin->users->wp_role_add( $user, $current_wp_role );
					}

					// Remove expired role if the user has it.
					if ( $this->plugin->users->wp_has_role( $user, $expired_wp_role ) ) {
						$this->plugin->users->wp_role_remove( $user, $expired_wp_role );
					}

					// Set flag for action.
					$flag = 'current';
					
				// Does the user's membership status match an expired status rule?
				} else if ( ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) ||
					  is_null ( $default_wp_role ) ){

					// Remove current role if the user has it.
					if ( $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
						$this->plugin->users->wp_role_remove( $user, $current_wp_role );
					}

					// Add expired role if the user does not have it.
					if ( ! $this->plugin->users->wp_has_role( $user, $expired_wp_role ) ) {
						$this->plugin->users->wp_role_add( $user, $expired_wp_role );
					}

					// Set flag for action.
					$flag = 'expired';
					
				// The membership didn't match a current or expired status rule
				} else {
					
					// Remove current role if the user has it.
					if ( $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
						$this->plugin->users->wp_role_remove( $user, $current_wp_role );
					}
					
					// Remove expired role if the user has it.
					if ( $this->plugin->users->wp_has_role( $user, $expired_wp_role ) ) {
						$this->plugin->users->wp_role_remove( $user, $expired_wp_role );
					}
					
					// Add default role if the user does not have it.
					if ( ! $this->plugin->users->wp_has_role( $user, $default_wp_role ) ) {
						$this->plugin->users->wp_role_add( $user, $default_wp_role );
					}
				}
				

				/**
				 * Fires after application of rule to user when syncing roles.
				 *
				 * This creates two possible actions:
				 *
				 * civi_wp_member_sync_rule_apply_roles_current
				 * civi_wp_member_sync_rule_apply_roles_expired
				 *
				 * @since 0.3.2
				 *
				 * @param WP_User $user The WordPress user object.
				 * @param int $membership_type_id The ID of the CiviCRM membership type.
				 * @param int $status_id The ID of the CiviCRM membership status.
				 * @param array $association_rule The rule used to apply the changes.
				 */
				do_action( 'civi_wp_member_sync_rule_apply_roles_' . $flag, $user, $membership_type_id, $status_id, $association_rule );

			} else {

				// SYNC CAPABILITY

				// Construct membership type capability name.
				$capability = CIVI_WP_MEMBER_SYNC_CAP_PREFIX . $membership_type_id;

				// Construct membership status capability name.
				$capability_status = $capability . '_' . $status_id;

				// Does the user's membership status match a current status rule?
				if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {

					// Do we have the "Members" plugin?
					if ( defined( 'MEMBERS_VERSION' ) ) {

						// Add the plugin's custom capability.
						$this->plugin->users->wp_cap_add( $user, 'restrict_content' );

					}

					// Add type capability.
					$this->plugin->users->wp_cap_add( $user, $capability );

					// Clear status capabilities.
					$this->plugin->users->wp_cap_remove_status( $user, $capability );

					// Add status capability.
					$this->plugin->users->wp_cap_add( $user, $capability_status );

					// Set flag for action.
					$flag = 'current';

				} else {

					// Do we have the "Members" plugin?
					if ( defined( 'MEMBERS_VERSION' ) ) {

						// Remove the plugin's custom capability.
						$this->plugin->users->wp_cap_remove( $user, 'restrict_content' );

					}

					// Remove type capability.
					$this->plugin->users->wp_cap_remove( $user, $capability );

					// Clear status capabilities.
					$this->plugin->users->wp_cap_remove_status( $user, $capability );

					// Set flag for action.
					$flag = 'expired';

				}

				/**
				 * Fires after application of rule to user when syncing capabilities.
				 *
				 * This creates two possible actions:
				 *
				 * civi_wp_member_sync_rule_apply_caps_current
				 * civi_wp_member_sync_rule_apply_caps_expired
				 *
				 * The status capability can be derived from the combination of
				 * $capability and $status_id and is therefore not needed when
				 * firing this action.
				 *
				 * @since 0.3.2
				 * @since 0.4 Added association rule parameter.
				 *
				 * @param WP_User $user The WordPress user object.
				 * @param int $membership_type_id The ID of the CiviCRM membership type.
				 * @param int $status_id The ID of the CiviCRM membership status.
				 * @param array $capability The membership type capability added or removed.
				 * @param array $association_rule The rule used to apply the changes.
				 */
				do_action( 'civi_wp_member_sync_rule_apply_caps_' . $flag, $user, $membership_type_id, $status_id, $capability, $association_rule );

			}

		}

		/**
		 * Fires after the application of all rules to a user's memberships.
		 *
		 * @since 0.3.6
		 *
		 * @param WP_User $user The WordPress user object.
		 * @param array $memberships The memberships of the WordPress user in question.
		 * @param str $method The sync method - either 'caps' or 'roles'.
		 */
		do_action( 'civi_wp_member_sync_rules_applied', $user, $memberships, $method );

	}



	/**
	 * Remove WordPress role or capability when a membership is deleted.
	 *
	 * This method is only called when a Membership is removed from a user in
	 * the CiviCRM admin. The membership details passed to this method will
	 * therefore only ever be for a single membership.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object of the user in question.
	 * @param object $membership The membership details of the WordPress user in question.
	 */
	public function rule_undo( $user, $membership = false ) {

		// Get sync method.
		$method = $this->setting_get_method();

		// Which sync method are we using?
		if ( $method == 'roles' ) {

			/*
			 * When there are multiple memberships, remove both the current role
			 * and the expired role. If this is the only remaining membership
			 * that the user has, however, then simply switch the current role
			 * for the expired role. This is to prevent users ending up with no
			 * role whatsoever.
			 */

			// Get association rule for this membership type.
			$association_rule = $this->rule_get_by_type( $membership->membership_type_id, $method );

			// Kick out if something went wrong.
			if ( $association_rule === false ) return;
			if ( empty( $association_rule['current_wp_role'] ) ) return;
			if ( empty( $association_rule['expired_wp_role'] ) ) return;

			// Get roles for this status rule.
			$current_wp_role = $association_rule['current_wp_role'];
			$expired_wp_role = $association_rule['expired_wp_role'];

			// Get remaining memberships for this user.
			$memberships = $this->plugin->members->membership_get_by_contact_id( $membership->contact_id );

			// If this user has a remaining membership.
			if (
				$memberships !== false AND
				$memberships['is_error'] == 0 AND
				isset( $memberships['values'] ) AND
				count( $memberships['values'] ) > 0
			) {

				/*
				 * There's a special case here where the membership being removed
				 * may cause the user to be left with no role at all if both the
				 * current and expired roles are removed.
				 *
				 * Additionally, roles defined by other rules may be affected by
				 * removing the roles associated with this membership.
				 *
				 * The logic adopted here is that we still go ahead and remove
				 * both roles and then perform a rule sync so that the remaining
				 * rules are applied.
				 */

				// Remove current role if the user has it.
				if ( $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
					$this->plugin->users->wp_role_remove( $user, $current_wp_role );
				}

				// Remove expired role if the user has it.
				if ( $this->plugin->users->wp_has_role( $user, $expired_wp_role ) ) {
					$this->plugin->users->wp_role_remove( $user, $expired_wp_role );
				}

				// Perform sync for this user if there are applicable rules.
				if ( $this->rule_exists( $memberships ) ) {
					$this->rule_apply( $user, $memberships );
				}

			} else {

				// Replace the current role with the expired role.
				if ( $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
					$this->plugin->users->wp_role_replace( $user, $current_wp_role, $expired_wp_role );
				}

			}

		} else {

			// Construct capability name.
			$capability = CIVI_WP_MEMBER_SYNC_CAP_PREFIX . $membership->membership_type_id;

			// Remove capability.
			$this->plugin->users->wp_cap_remove( $user, $capability );

			// Remove status capability.
			$this->plugin->users->wp_cap_remove_status( $user, $capability );

			// Do we have the "Members" plugin?
			if ( defined( 'MEMBERS_VERSION' ) ) {

				// Remove the custom capability.
				$this->plugin->users->wp_cap_remove( $user, 'restrict_content' );

			}

		}

	}



	//##########################################################################



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
		if ( civicrm_au()->single->setting_get( 'fix_soft_delete', '0' ) == '0' ) {
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

		// Sanity check.
		if ( ! $this->cau_configured() ) {
			return '';
		}

		// Get all site URLs.
		$urls = civicrm_au()->single->page_get_urls();

		// Return settings page URL if it exists.
		if ( ! empty( $urls['settings'] ) ) {
			return $urls['settings'];
		}

		// Fallback
		return '';

	}



} // Class ends.



