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
	 * @var object $plugin The plugin object
	 */
	public $plugin;

	/**
	 * Migration object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $migrate The migration object
	 */
	public $migrate;

	/**
	 * Parent Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $parent_page The parent page
	 */
	public $parent_page;

	/**
	 * Settings Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $settings_page The settings page
	 */
	public $settings_page;

	/**
	 * Manual Sync Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $sync_page The manual sync page
	 */
	public $sync_page;

	/**
	 * List Association Rules Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $rules_list_page The list rules page
	 */
	public $rules_list_page;

	/**
	 * Add or edit Association Rule Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $rules_list_page The add/edit rules page
	 */
	public $rule_add_edit_page;

	/**
	 * Plugin version.
	 *
	 * @since 0.1
	 * @access public
	 * @var str $plugin_version The plugin version (numeric string)
	 */
	public $plugin_version;

	/**
	 * Plugin settings.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $settings The plugin settings
	 */
	public $settings = array();

	/**
	 * Form error messages.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $error_strings The form error messages
	 */
	public $error_strings;

	/**
	 * Errors in current form submission.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $error_strings The errors in current form submission
	 */
	public $errors;



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

		// define errors
		$this->error_strings = array(
			1 => __( 'Please select a CiviCRM Membership Type', 'civicrm-wp-member-sync' ),
			2 => __( 'Please select a WordPress Role', 'civicrm-wp-member-sync' ),
			3 => __( 'Please select a Current Status', 'civicrm-wp-member-sync' ),
			4 => __( 'Please select an Expire Status', 'civicrm-wp-member-sync' ),
			5 => __( 'Please select a WordPress Expiry Role', 'civicrm-wp-member-sync' ),
			6 => __( 'You can not have the same Status Rule registered as both "Current" and "Expired"', 'civicrm-wp-member-sync' ),
		);

		// test for constant
		if ( defined( 'CIVI_WP_MEMBER_SYNC_MIGRATE' ) AND CIVI_WP_MEMBER_SYNC_MIGRATE ) {

			// load our Migration utility class
			require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'civi-wp-ms-migrate.php' );

			// instantiate
			$this->migrate = new Civi_WP_Member_Sync_Migrate( $this );

		}

	}



	/**
	 * Perform activation tasks.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// store version for later reference
		$this->store_version();

		// add settings option only if it does not exist
		if ( 'fgffgs' == $this->option_get( 'civi_wp_member_sync_settings', 'fgffgs' ) ) {

			// store default settings
			$this->option_save( 'civi_wp_member_sync_settings', $this->settings_get_default() );

		}

	}



	/**
	 * Perform deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

		// we delete our options in uninstall.php

	}



	/**
	 * Test if this plugin is network activated.
	 *
	 * @since 0.2.7
	 *
	 * @return bool $is_network_active True if network activated, false otherwise
	 */
	public function is_network_activated() {

		// only need to test once
		static $is_network_active;

		// have we done this already?
		if ( isset( $is_network_active ) ) return $is_network_active;

		// if not multisite, it cannot be
		if ( ! is_multisite() ) {

			// set flag
			$is_network_active = false;

			// kick out
			return $is_network_active;

		}

		// make sure plugin file is included when outside admin
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		// get path from 'plugins' directory to this plugin
		$this_plugin = plugin_basename( CIVI_WP_MEMBER_SYNC_PLUGIN_FILE );

		// test if network active
		$is_network_active = is_plugin_active_for_network( $this_plugin );

		// --<
		return $is_network_active;

	}



	/**
	 * Initialise when CiviCRM initialises.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// load plugin version
		$this->plugin_version = $this->option_get( 'civi_wp_member_sync_version', false );

		// perform any upgrade tasks
		$this->upgrade_tasks();

		// upgrade version if needed
		if ( $this->plugin_version != CIVI_WP_MEMBER_SYNC_VERSION ) $this->store_version();

		// load settings array
		$this->settings = $this->option_get( 'civi_wp_member_sync_settings', $this->settings );

		// is this the back end?
		if ( is_admin() ) {

			// multisite and network-activated?
			if ( $this->is_network_activated() ) {

				// add admin page to Network Settings menu
				add_action( 'network_admin_menu', array( $this, 'admin_menu' ), 30 );

			} else {

				// add admin page to menu
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			}

		}

		// test for "Groups" plugin on init
		add_action( 'init', array( $this, 'groups_plugin_hooks' ) );

	}



	/**
	 * Perform upgrade tasks.
	 *
	 * @since 0.2.7
	 */
	public function upgrade_tasks() {

		// if the current version is less than 0.2.7 and we're upgrading to 0.2.7+
		if (
			version_compare( $this->plugin_version, '0.2.7', '<' ) AND
			version_compare( CIVI_WP_MEMBER_SYNC_VERSION, '0.2.7', '>=' )
		) {

			// check if this plugin is network-activated
			if ( $this->is_network_activated() ) {

				// get existing settings from local options
				$settings = get_option( 'civi_wp_member_sync_settings', array() );

				// what if we don't have any?
				if ( ! array_key_exists( 'data', $settings ) ) {
					return;
				}

				// migrate to network settings
				$this->settings = $settings;
				$this->settings_save();

				// delete local options
				delete_option( 'civi_wp_member_sync_version' );
				delete_option( 'civi_wp_member_sync_settings' );

			}

		}

	}



	/**
	 * Store the plugin version.
	 *
	 * @since 0.1
	 */
	public function store_version() {

		// store version
		$this->option_save( 'civi_wp_member_sync_version', CIVI_WP_MEMBER_SYNC_VERSION );

	}



	//##########################################################################



	/**
	 * Add this plugin's Settings Page to the WordPress admin menu.
	 *
	 * @since 0.1
	 */
	public function admin_menu() {

		// we must be network admin in multisite
		if ( is_multisite() AND ! is_super_admin() ) return false;

		// check user permissions
		if ( ! current_user_can( 'manage_options' ) ) return false;

		// multisite?
		if ( $this->is_network_activated() ) {

			// add settings page to the Network Settings menu
			$this->parent_page = add_submenu_page(
				'settings.php',
				__( 'CiviCRM WordPress Member Sync: Settings', 'civicrm-wp-member-sync' ), // page title
				__( 'CiviCRM WordPress Member Sync', 'civicrm-wp-member-sync' ), // menu title
				'manage_options', // required caps
				'civi_wp_member_sync_parent', // slug name
				array( $this, 'page_settings' ) // callback
			);

		} else {

			// add the settings page to the Settings menu
			$this->parent_page = add_options_page(
				__( 'CiviCRM WordPress Member Sync: Settings', 'civicrm-wp-member-sync' ), // page title
				__( 'CiviCRM WordPress Member Sync', 'civicrm-wp-member-sync' ), // menu title
				'manage_options', // required caps
				'civi_wp_member_sync_parent', // slug name
				array( $this, 'page_settings' ) // callback
			);

		}

		// add scripts and styles
		add_action( 'admin_print_styles-'.$this->parent_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-'.$this->parent_page, array( $this, 'admin_head' ), 50 );

		// add settings page
		$this->settings_page = add_submenu_page(
			'civi_wp_member_sync_parent', // parent slug
			__( 'CiviCRM WordPress Member Sync: Settings', 'civicrm-wp-member-sync' ), // page title
			__( 'Settings', 'civicrm-wp-member-sync' ), // menu title
			'manage_options', // required caps
			'civi_wp_member_sync_settings', // slug name
			array( $this, 'page_settings' ) // callback
		);

		// add scripts and styles
		add_action( 'admin_print_styles-'.$this->settings_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-'.$this->settings_page, array( $this, 'admin_head' ), 50 );
		add_action( 'admin_head-'.$this->settings_page, array( $this, 'admin_menu_highlight' ), 50 );

		// add manual sync page
		$this->sync_page = add_submenu_page(
			'civi_wp_member_sync_parent', // parent slug
			__( 'CiviCRM WordPress Member Sync: Manual Sync', 'civicrm-wp-member-sync' ), // page title
			__( 'Manual Sync', 'civicrm-wp-member-sync' ), // menu title
			'manage_options', // required caps
			'civi_wp_member_sync_manual_sync', // slug name
			array( $this, 'page_manual_sync' ) // callback
		);

		// add scripts and styles
		add_action( 'admin_print_scripts-'.$this->sync_page, array( $this, 'admin_js_sync_page' ) );
		add_action( 'admin_print_styles-'.$this->sync_page, array( $this, 'admin_css' ) );
		add_action( 'admin_print_styles-'.$this->sync_page, array( $this, 'admin_css_sync_page' ) );
		add_action( 'admin_head-'.$this->sync_page, array( $this, 'admin_head' ), 50 );
		add_action( 'admin_head-'.$this->sync_page, array( $this, 'admin_menu_highlight' ), 50 );

		// add rules listing page
		$this->rules_list_page = add_submenu_page(
			'civi_wp_member_sync_parent', // parent slug
			__( 'CiviCRM WordPress Member Sync: List Rules', 'civicrm-wp-member-sync' ), // page title
			__( 'List Rules', 'civicrm-wp-member-sync' ), // menu title
			'manage_options', // required caps
			'civi_wp_member_sync_list', // slug name
			array( $this, 'page_rules_list' ) // callback
		);

		// add scripts and styles
		add_action( 'admin_print_styles-'.$this->rules_list_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-'.$this->rules_list_page, array( $this, 'admin_head' ), 50 );
		add_action( 'admin_head-'.$this->rules_list_page, array( $this, 'admin_menu_highlight' ), 50 );

		// add rules page
		$this->rule_add_edit_page = add_submenu_page(
			'civi_wp_member_sync_parent', // parent slug
			__( 'CiviCRM WordPress Member Sync: Association Rule', 'civicrm-wp-member-sync' ), // page title
			__( 'Association Rule', 'civicrm-wp-member-sync' ), // menu title
			'manage_options', // required caps
			'civi_wp_member_sync_rules', // slug name
			array( $this, 'page_rule_add_edit' ) // callback
		);

		// add scripts and styles
		add_action( 'admin_print_scripts-'.$this->rule_add_edit_page, array( $this, 'admin_js_rules_page' ) );
		add_action( 'admin_print_styles-'.$this->rule_add_edit_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-'.$this->rule_add_edit_page, array( $this, 'admin_head' ), 50 );
		add_action( 'admin_head-'.$this->rule_add_edit_page, array( $this, 'admin_menu_highlight' ), 50 );

		// try and update options
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

		// define subpages
		$subpages = array(
		 	'civi_wp_member_sync_settings',
		 	'civi_wp_member_sync_manual_sync',
		 	'civi_wp_member_sync_list',
		 	'civi_wp_member_sync_rules',
		 );

		// This tweaks the Settings subnav menu to show only one menu item
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

		// there's a new screen object for help in 3.3
		$screen = get_current_screen();

		// use method in this class
		$this->admin_help( $screen );

	}



	/**
	 * Enqueue common stylesheet for this plugin's admin pages.
	 *
	 * @since 0.1
	 */
	public function admin_css() {

		// add admin stylesheet
		wp_enqueue_style(
			'civi_wp_member_sync_admin_css',
			plugins_url( 'assets/css/civi-wp-ms.css', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			false,
			CIVI_WP_MEMBER_SYNC_VERSION, // version
			'all' // media
		);

	}



	/**
	 * Enqueue stylesheet for this plugin's "Manual Sync" page.
	 *
	 * @since 0.2.8
	 */
	public function admin_css_sync_page() {

		// add manual sync stylesheet
		wp_enqueue_style(
			'civi_wp_member_sync_manual_sync_css',
			plugins_url( 'assets/css/civi-wp-ms-sync.css', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			false,
			CIVI_WP_MEMBER_SYNC_VERSION, // version
			'all' // media
		);

	}



	/**
	 * Enqueue required scripts on the Add Rule and Edit Rule pages.
	 *
	 * @since 0.1
	 */
	public function admin_js_rules_page() {

		// add javascript plus dependencies
		wp_enqueue_script(
			'civi_wp_member_sync_rules_js',
			plugins_url( 'assets/js/civi-wp-ms-rules.js', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			array( 'jquery', 'jquery-form' ),
			CIVI_WP_MEMBER_SYNC_VERSION // version
		);

		// set defaults
		$vars = array(
			'method' => $this->setting_get( 'method' ),
			'mode' => 'add',
		);

		// maybe override mode
		if( isset( $_GET['mode'] ) AND $_GET['mode'] == 'edit' ) {
			if ( isset( $_GET['type_id'] ) AND is_numeric( $_GET['type_id'] ) ) {
				$vars['mode'] = 'edit';
			}
		}

		// localize our script
		wp_localize_script(
			'civi_wp_member_sync_rules_js',
			'CiviCRM_WP_Member_Sync_Rules',
			$vars
		);

	}



	/**
	 * Enqueue required scripts on the Manual Sync page.
	 *
	 * @since 0.2.8
	 */
	public function admin_js_sync_page() {

		// enqueue javascript
		wp_enqueue_script(
			'civi_wp_member_sync_sync_js',
			plugins_url( 'assets/js/civi-wp-ms-sync.js', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-progressbar' ),
			CIVI_WP_MEMBER_SYNC_VERSION // version
		);

		// init localisation
		$localisation = array(
			'total' => __( '{{total}} memberships to sync...', 'civicrm-wp-member-sync' ),
			'current' => __( 'Processing memberships {{from}} to {{to}}', 'civicrm-wp-member-sync' ),
			'complete' => __( 'Processing memberships {{from}} to {{to}} complete', 'civicrm-wp-member-sync' ),
			'done' => __( 'All done!', 'civicrm-wp-member-sync' ),
		);

		// init settings
		$settings = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'total_memberships' => $this->plugin->members->memberships_get_count(),
		);

		// localisation array
		$vars = array(
			'localisation' => $localisation,
			'settings' => $settings,
		);

		// localise the WordPress way
		wp_localize_script(
			'civi_wp_member_sync_sync_js',
			'CiviCRM_WP_Member_Sync_Settings',
			$vars
		);

	}



	/**
	 * Adds help copy to admin page in WP3.3+.
	 *
	 * @since 0.1
	 *
	 * @param object $screen The existing WordPress screen object
	 * @return object $screen The amended WordPress screen object
	 */
	public function admin_help( $screen ) {

		// init suffix
		$page = '';

		// the page ID is different in multisite
		if ( $this->is_network_activated() ) {
			$page = '-network';
		}

		// init page IDs
		$pages = array(
			$this->settings_page . $page,
			$this->sync_page . $page,
			$this->rules_list_page . $page,
			$this->rule_add_edit_page . $page,
		);

		// kick out if not our screen
		if ( ! in_array( $screen->id, $pages ) ) { return $screen; }

		// add a tab - we can add more later
		$screen->add_help_tab( array(
			'id'      => 'civi_wp_member_sync',
			'title'   => __( 'CiviCRM WordPress Member Sync', 'civicrm-wp-member-sync' ),
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
	 * @return string $help Help formatted as HTML
	 */
	public function get_help() {

		// stub help text, to be developed further...
		$help = '<p>' . __( 'For further information about using CiviCRM WordPress Member Sync, please refer to the README.md that comes with this plugin.', 'civicrm-wp-member-sync' ) . '</p>';

		// --<
		return $help;

	}



	//##########################################################################



	/**
	 * Show settings page.
	 *
	 * @since 0.1
	 */
	public function page_settings() {

		// check user permissions
		if ( current_user_can( 'manage_options' ) ) {

			// get admin page URLs
			$urls = $this->page_get_urls();

			// do we have the legacy plugin?
			if ( isset( $this->migrate ) AND $this->migrate->legacy_plugin_exists() ) {

				// include template file
				include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/migrate.php' );

				// --<
				return;

			}

			// get our sync method
			$method = $this->setting_get( 'method' );

			// get all schedules
			$schedules = $this->plugin->schedule->intervals_get();

			// get our sync settings
			$login = absint( $this->setting_get( 'login' ) );
			$civicrm = absint( $this->setting_get( 'civicrm' ) );
			$schedule = absint( $this->setting_get( 'schedule' ) );

			// get our interval setting
			$interval = $this->setting_get( 'interval' );

			// include template file
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/settings.php' );

		}

	}



	/**
	 * Show manual sync page.
	 *
	 * @since 0.1
	 */
	public function page_manual_sync() {

		// check user permissions
		if ( current_user_can( 'manage_options' ) ) {

			// get admin page URLs
			$urls = $this->page_get_urls();

			// include template file
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/manual-sync.php' );

		}

	}



	/**
	 * Show rules list page.
	 *
	 * @since 0.1
	 */
	public function page_rules_list() {

		// check user permissions
		if ( current_user_can( 'manage_options' ) ) {

			// get admin page URLs
			$urls = $this->page_get_urls();

			// get method
			$method = $this->setting_get( 'method' );

			// get data
			$all_data = $this->setting_get( 'data' );

			// get data for this sync method
			$data = ( isset( $all_data[$method] ) ) ? $all_data[$method] : array();

			// get all membership types
			$membership_types = $this->plugin->members->types_get_all();

			// assume we don't have all types
			$have_all_types = false;

			// well, do we have all types populated?
			if ( count( $data ) === count( $membership_types ) ) {

				// we do
				$have_all_types = true;

			}

			// include per method
			if ( $method == 'roles' ) {

				// include template file
				include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/list_roles.php' );

			} else {

				// include template file
				include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/list_caps.php' );

			}

		}

	}



	/**
	 * Decide whether to show add or edit page.
	 *
	 * @since 0.1
	 */
	public function page_rule_add_edit() {

		// check user permissions
		if ( current_user_can( 'manage_options' ) ) {

			// default mode
			$mode = 'add';

			// do we want to populate the form?
			if ( isset( $_GET['mode'] ) AND $_GET['mode'] == 'edit' ) {
				if ( isset( $_GET['type_id'] ) AND is_numeric( $_GET['type_id'] ) ) {
					$mode = 'edit';
				}
			}

			// route by mode
			if ( $mode == 'add' ) {
				$this->page_rule_add();
			} elseif ( $mode == 'edit' ) {
				$this->page_rule_edit();
			}

		}

	}



	/**
	 * Show add rule page.
	 *
	 * @since 0.1
	 */
	private function page_rule_add() {

		// get admin page URLs
		$urls = $this->page_get_urls();

		// get all membership types
		$membership_types = $this->plugin->members->types_get_all();

		// get all membership status rules
		$status_rules = $this->plugin->members->status_rules_get_all();

		// get method
		$method = $this->setting_get( 'method' );

		// get rules
		$rules = $this->rules_get_by_method( $method );

		// if we get some...
		if ( $rules !== false AND is_array( $rules ) AND count( $rules ) > 0 ) {

			// get used membership type IDs
			$type_ids = array_keys( $rules );

			// loop and remove from membership_types array
			foreach( $type_ids AS $type_id ) {
				if ( isset( $membership_types[$type_id] ) ) {
					unset( $membership_types[$type_id] );
				}
			}

		}

		// well?
		if ( $method == 'roles' ) {

			// get filtered roles
			$roles = $this->plugin->users->wp_role_names_get_all();

			// include template file
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-role-add.php' );

		} else {

			// include template file
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-cap-add.php' );

		}

	}



	/**
	 * Show edit rule page.
	 *
	 * @since 0.1
	 */
	private function page_rule_edit() {

		// get admin page URLs
		$urls = $this->page_get_urls();

		// get all membership types
		$membership_types = $this->plugin->members->types_get_all();

		// get all membership status rules
		$status_rules = $this->plugin->members->status_rules_get_all();

		// get method
		$method = $this->setting_get( 'method' );

		// get requested membership type ID
		$civi_member_type_id = absint( $_GET['type_id'] );

		// get rule by type
		$selected_rule = $this->rule_get_by_type( $civi_member_type_id, $method );

		// set vars for populating form
		$current_rule = $selected_rule['current_rule'];
		$expiry_rule = $selected_rule['expiry_rule'];

		// get rules
		$rules = $this->rules_get_by_method( $method );

		// if we get some...
		if ( $rules !== false AND is_array( $rules ) AND count( $rules ) > 0 ) {

			// get used membership type IDs
			$type_ids = array_keys( $rules );

			// loop and remove from membership_types array
			foreach( $type_ids AS $type_id ) {
				if ( isset( $membership_types[$type_id] ) AND $civi_member_type_id != $type_id ) {
					unset( $membership_types[$type_id] );
				}
			}

		}

		// do we need roles?
		if ( $method == 'roles' ) {

			// get filtered roles
			$roles = $this->plugin->users->wp_role_names_get_all();

			// get stored roles
			$current_wp_role = $selected_rule['current_wp_role'];
			$expired_wp_role = $selected_rule['expired_wp_role'];

			// include template file
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-role-edit.php' );

		} else {

			// include template file
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-cap-edit.php' );

		}

	}



	//##########################################################################



	/**
	 * Get admin page URLs.
	 *
	 * @since 0.1
	 *
	 * @return array $admin_urls The array of admin page URLs
	 */
	public function page_get_urls() {

		// only calculate once
		if ( isset( $this->urls ) ) { return $this->urls; }

		// init return
		$this->urls = array();

		// multisite?
		if ( $this->is_network_activated() ) {

			// get admin page URLs via our adapted method
			$this->urls['settings'] = $this->network_menu_page_url( 'civi_wp_member_sync_settings', false );
			$this->urls['manual_sync'] = $this->network_menu_page_url( 'civi_wp_member_sync_manual_sync', false );
			$this->urls['list'] = $this->network_menu_page_url( 'civi_wp_member_sync_list', false );
			$this->urls['rules'] = $this->network_menu_page_url( 'civi_wp_member_sync_rules', false );

		} else {

			// get admin page URLs
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
	 * @param string $menu_slug The slug name to refer to this menu by (should be unique for this menu)
	 * @param bool $echo Whether or not to echo the url - default is true
	 * @return string $url The URL
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
	 * @return string $target_url The URL for the admin form action
	 */
	public function admin_form_url_get() {

		// sanitise admin page url
		$target_url = $_SERVER['REQUEST_URI'];
		$url_array = explode( '&', $target_url );
		if ( $url_array ) { $target_url = htmlentities( $url_array[0].'&updated=true' ); }

		// --<
		return $target_url;

	}



	//##########################################################################



	/**
	 * Route settings updates to relevant methods.
	 *
	 * @since 0.1
	 *
	 * @return bool $result True on success, false otherwise
	 */
	public function settings_update_router() {

		// init result
		$result = false;

		// was the "Migrate" form submitted?
		if( isset( $_POST['civi_wp_member_sync_migrate_submit'] ) ) {
			$result = $this->migrate->legacy_migrate();
		}

		// was the "Settings" form submitted?
		if( isset( $_POST['civi_wp_member_sync_settings_submit'] ) ) {
			$result = $this->settings_update();
		}

	 	// was the "Stop Sync" button pressed?
		if( isset( $_POST['civi_wp_member_sync_manual_sync_stop'] ) ) {
			delete_option( '_civi_wpms_memberships_offset' );
			return;
		}

		// was the "Manual Sync" form submitted?
		if( isset( $_POST['civi_wp_member_sync_manual_sync_submit'] ) ) {

			// check that we trust the source of the request
			check_admin_referer( 'civi_wp_member_sync_manual_sync_action', 'civi_wp_member_sync_nonce' );

			/**
			 * Let other plugins know that we're about to sync all users.
			 *
			 * @since 0.1
			 */
			do_action( 'civi_wp_member_sync_pre_sync_all' );

			// sync all memberships for *existing* WordPress users
			$result = $this->plugin->members->sync_all_civicrm_memberships();

			/**
			 * Let other plugins know that we've synced all users.
			 *
			 * @since 0.1
			 */
			do_action( 'civi_wp_member_sync_after_sync_all' );

		}

		// was the "Rule" form submitted?
		if( isset( $_POST['civi_wp_member_sync_rules_submit'] ) ) {
			$result = $this->rule_update();
		}

		// was a "Delete" link clicked?
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
	 * @return array $settings The array of settings, keyed by setting name
	 */
	public function settings_get_default() {

		// init return
		$settings = array();

		// set empty data arrays
		$settings['data']['roles'] = array();
		$settings['data']['capabilities'] = array();

		// set default method
		$settings['method'] = 'capabilities';

		// switch all sync settings on by default
		$settings['login'] = 1;
		$settings['civicrm'] = 1;
		$settings['schedule'] = 1;

		// set default schedule interval
		$settings['interval'] = 'daily';

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

		// check that we trust the source of the request
		check_admin_referer( 'civi_wp_member_sync_settings_action', 'civi_wp_member_sync_nonce' );



		// debugging switch for developers - if set, triggers do_debug() below
		if (
			defined( 'CIVI_WP_MEMBER_SYNC_DEBUG' ) AND
			CIVI_WP_MEMBER_SYNC_DEBUG AND
			isset( $_POST['civi_wp_member_sync_settings_debug'] )
		) {
			$settings_debug = absint( $_POST['civi_wp_member_sync_settings_debug'] );
			$debug = $settings_debug ? 1 : 0;
			if ( $debug ) { $this->do_debug(); }
			return;
		}



		// synchronization method
		$settings_method = 'capabilities';
		if ( isset( $_POST['civi_wp_member_sync_settings_method'] ) ) {
			$settings_method = trim( $_POST['civi_wp_member_sync_settings_method'] );
		}
		$this->setting_set( 'method', $settings_method );



		// login/logout sync enabled
		if ( isset( $_POST['civi_wp_member_sync_settings_login'] ) ) {
			$settings_login = absint( $_POST['civi_wp_member_sync_settings_login'] );
		} else {
			$settings_login = 0;
		}
		$this->setting_set( 'login', ( $settings_login ? 1 : 0 ) );



		// CiviCRM sync enabled
		if ( isset( $_POST['civi_wp_member_sync_settings_civicrm'] ) ) {
			$settings_civicrm = absint( $_POST['civi_wp_member_sync_settings_civicrm'] );
		} else {
			$settings_civicrm = 0;
		}
		$this->setting_set( 'civicrm', ( $settings_civicrm ? 1 : 0 ) );



		// get existing schedule
		$existing_schedule = $this->setting_get( 'schedule' );

		// schedule sync enabled
		if ( isset( $_POST['civi_wp_member_sync_settings_schedule'] ) ) {
			$settings_schedule = absint( $_POST['civi_wp_member_sync_settings_schedule'] );
		} else {
			$settings_schedule = 0;
		}
		$this->setting_set( 'schedule', ( $settings_schedule ? 1 : 0 ) );

		// is the schedule being deactivated?
		if ( $existing_schedule == 1 AND $settings_schedule === 0 ) {

			// clear current scheduled event
			$this->plugin->schedule->unschedule();

		}



		// schedule interval
		if ( isset( $_POST['civi_wp_member_sync_settings_interval'] ) ) {

			// get existing interval
			$existing_interval = $this->setting_get( 'interval' );

			// get value passed in
			$settings_interval = esc_sql( trim( $_POST['civi_wp_member_sync_settings_interval'] ) );

			// is the schedule active and has the interval changed?
			if ( $settings_schedule AND $settings_interval != $existing_interval ) {

				// clear current scheduled event
				$this->plugin->schedule->unschedule();

				// now add new scheduled event
				$this->plugin->schedule->schedule( $settings_interval );

			}

			// set new value whatever (for now)
			$this->setting_set( 'interval', $settings_interval );

		}



		// save settings
		$this->settings_save();

		// get admin URLs
		$urls = $this->page_get_urls();

		// redirect to settings page with message
		wp_redirect( $urls['settings'] . '&updated=true' );
		die();

	}



	/**
	 * Save the plugin's settings array.
	 *
	 * @since 0.1
	 *
	 * @return bool $result True if setting value has changed, false if not or if update failed
	 */
	public function settings_save() {

		// update WordPress option and return result
		return $this->option_save( 'civi_wp_member_sync_settings', $this->settings );

	}



	/**
	 * Return a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @return mixed $setting The value of the setting
	 */
	public function setting_get( $setting_name = '', $default = false ) {

		// sanity check
		if ( $setting_name == '' ) {
			wp_die( __( 'You must supply a setting to setting_get()', 'civicrm-wp-member-sync' ) );
		}

		// get setting
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[ $setting_name ] : $default;

	}



	/**
	 * Set a value for a specified setting.
	 *
	 * @since 0.1
	 */
	public function setting_set( $setting_name = '', $value = '' ) {

		// sanity check
		if ( $setting_name == '' ) {
			wp_die( __( 'You must supply a setting to setting_set()', 'civicrm-wp-member-sync' ) );
		}

		// set setting
		$this->settings[ $setting_name ] = $value;

	}



	//##########################################################################



	/**
	 * Get a WordPress option.
	 *
	 * @since 0.2.7
	 *
	 * @param string $key The option name
	 * @param mixed $default The default option value if none exists
	 * @return mixed $value
	 */
	public function option_get( $key, $default = null ) {

		// if multisite and network activated
		if ( $this->is_network_activated() ) {

			// get site option
			$value = get_site_option( $key, $default );

		} else {

			// get option
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
	 * @param string $key The option name
	 * @param mixed $value The value to save
	 */
	public function option_save( $key, $value ) {

		// if multisite and network activated
		if ( $this->is_network_activated() ) {

			// update site option
			update_site_option( $key, $value );

		} else {

			// update option
			update_option( $key, $value );

		}

	}



	/**
	 * Delete a WordPress option.
	 *
	 * @since 0.2.7
	 *
	 * @param string $key The option name
	 */
	public function option_delete( $key ) {

		// if multisite and network activated
		if ( $this->is_network_activated() ) {

			// delete site option
			delete_site_option( $key );

		} else {

			// delete option
			delete_option( $key );

		}

	}



	//##########################################################################



	/**
	 * Get all association rules by method.
	 *
	 * @since 0.1
	 *
	 * @param string $method The sync method (either 'roles' or 'capabilities')
	 * @return mixed $rule Array if successful, boolean false otherwise
	 */
	public function rules_get_by_method( $method = 'roles' ) {

		// get data
		$data = $this->setting_get( 'data' );

		// sanitize method
		$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

		// get subset by method
		$subset = ( isset( $data[$method] ) ) ? $data[$method] : false;

		// --<
		return $subset;

	}



	/**
	 * Get an association rule by membership type ID.
	 *
	 * @since 0.1
	 *
	 * @param int $type_id The numeric ID of the CiviCRM membership type
	 * @param string $method The sync method (either 'roles' or 'capabilities')
	 * @return mixed $rule Array if successful, boolean false otherwise
	 */
	public function rule_get_by_type( $type_id, $method = 'roles' ) {

		// get data
		$data = $this->setting_get( 'data' );

		// sanitize method
		$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

		// get subset by method
		$subset = ( isset( $data[$method] ) ) ? $data[$method] : false;

		// get data for this type_id
		$rule = ( isset( $subset[$type_id] ) ) ? $subset[$type_id] : false;

		// --<
		return $rule;

	}



	/**
	 * Update (or add) a membership rule.
	 *
	 * @since 0.1
	 *
	 * @return bool $success True if successful, false otherwise
	 */
	public function rule_update() {

		// check that we trust the source of the data
		check_admin_referer( 'civi_wp_member_sync_rule_action', 'civi_wp_member_sync_nonce' );

		// default mode to 'add'
		$mode = 'add';

		// test our hidden element
		if (
			isset( $_POST['civi_wp_member_sync_rules_mode'] ) AND
			$_POST['civi_wp_member_sync_rules_mode'] == 'edit'
		) {
			$mode = 'edit';
		}

		// get sync method and sanitize
		$method = $this->setting_get( 'method' );
		$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

		// init errors
		$this->errors = array();

		// check and sanitise CiviCRM Membership Type
		if(
			isset( $_POST['civi_member_type_id'] ) AND
			! empty( $_POST['civi_member_type_id'] ) AND
			is_numeric( $_POST['civi_member_type_id'] )
		) {
			$civi_member_type_id = absint( $_POST['civi_member_type_id'] );
		} else {
			$this->errors[] = 1;
		}

		// check and sanitise Current Status
		if (
			isset( $_POST['current'] ) AND
			is_array( $_POST['current'] ) AND
			! empty( $_POST['current'] )
		) {
			$current_rule = $_POST['current'];

		} else {
			$this->errors[] = 3;
		}

		// check and sanitise Expire Status
		if (
			isset( $_POST['expire'] ) AND
			is_array( $_POST['expire'] ) AND
			! empty( $_POST['expire'] )
		) {
			$expiry_rule = $_POST['expire'];
		} else {
			$this->errors[] = 4;
		}

		// init current-expire check (will end up true if there's a clash)
		$current_expire_clash = false;

		// do we have both arrays?
		if ( isset( $current_rule ) AND isset( $expiry_rule ) ) {

			// check 'current' array against 'expire' array
			$intersect = array_intersect_assoc( $current_rule, $expiry_rule );
			if ( ! empty( $intersect ) ) {
				$current_expire_clash = true;
			}

		}

		// do we want roles?
		if ( $method == 'roles' ) {

			// check and sanitise WordPress Role
			if(
				isset( $_POST['current_wp_role'] ) AND
				! empty( $_POST['current_wp_role'] )
			) {
				$current_wp_role = esc_sql( trim( $_POST['current_wp_role'] ) );
			} else {
				$this->errors[] = 2;
			}

			// check and sanitise Expiry Role
			if (
				isset( $_POST['expire_assign_wp_role'] ) AND
				! empty( $_POST['expire_assign_wp_role'] )
			) {
				$expired_wp_role = esc_sql( trim( $_POST['expire_assign_wp_role'] ) );
			} else {
				$this->errors[] = 5;
			}

		}

		// how did we do?
		if ( $current_expire_clash === false AND empty( $this->errors ) ) {

			// we're good - let's add/update this rule

			// get existing data
			$data = $this->setting_get( 'data' );

			// which sync method are we using?
			if ( $method == 'roles' ) {

				// insert/overwrite role item in data
				$data['roles'][$civi_member_type_id] = array(
					'current_rule' => $current_rule,
					'current_wp_role' => $current_wp_role,
					'expiry_rule' => $expiry_rule,
					'expired_wp_role' => $expired_wp_role,
				);

			} else {

				// insert/overwrite capability item in data
				$data['capabilities'][$civi_member_type_id] = array(
					'current_rule' => $current_rule,
					'expiry_rule' => $expiry_rule,
					'capability' => CIVI_WP_MEMBER_SYNC_CAP_PREFIX . $civi_member_type_id,
				);

			}

			/**
			 * Broadcast our association rule. This creates four possible actions:
			 *
			 * civi_wp_member_sync_rule_add_roles
			 * civi_wp_member_sync_rule_add_capabilities
			 * civi_wp_member_sync_rule_edit_roles
			 * civi_wp_member_sync_rule_edit_capabilities
			 *
			 * @param array The new or updated association rule
			 */
			do_action( 'civi_wp_member_sync_rule_'. $mode . '_' . $method, $data[$method][$civi_member_type_id] );

			// overwrite data
			$this->setting_set( 'data', $data );

			// save
			$this->settings_save();

			// get admin URLs
			$urls = $this->page_get_urls();

			// redirect to list page
			wp_redirect( $urls['list'] . '&syncrule=' . $mode );
			die();

		} else {

			// in addition, are there status clashes?
			if ($current_expire_clash === false ) {
				$this->errors[] = 6;
			}

			// sad face
			return false;

		}

	}



	/**
	 * Delete a membership rule.
	 *
	 * @since 0.1
	 *
	 * @return bool $success True if successful, false otherwise
	 */
	public function rule_delete() {

		// check nonce
		if (
			! isset( $_GET['civi_wp_member_sync_delete_nonce'] ) OR
			! wp_verify_nonce( $_GET['civi_wp_member_sync_delete_nonce'], 'civi_wp_member_sync_delete_link' )
		) {

			wp_die( __( 'Cheating, eh?', 'civicrm-wp-member-sync' ) );
			exit();

		}

		// get membership type
		$type_id = absint( $_GET['type_id'] );

		// sanity check
		if ( empty( $type_id ) ) return;

		// get method
		$method = $this->setting_get( 'method' );

		// sanitize method
		$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

		// get data
		$data = $this->setting_get( 'data' );

		// get subset by method
		$subset = ( isset( $data[$method] ) ) ? $data[$method] : false;

		// sanity check
		if ( ! $subset ) return;
		if ( ! isset( $subset[$type_id] ) ) return;

		/**
		 * Broadcast that we're deleting an association rule. This creates two
		 * actions, depending on the sync method:
		 *
		 * civi_wp_member_sync_rule_delete_roles
		 * civi_wp_member_sync_rule_delete_capabilities
		 *
		 * @param array The association rule we're going to delete
		 */
		do_action( 'civi_wp_member_sync_rule_delete_' . $method, $subset[$type_id] );

		// delete it
		unset( $subset[$type_id] );

		// update data
		$data[$method] = $subset;

		// overwrite data
		$this->setting_set( 'data', $data );

		// save
		$this->settings_save();

		// get admin URLs
		$urls = $this->page_get_urls();

		// redirect to list page with message
		wp_redirect( $urls['list'] . '&syncrule=delete' );
		die();

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
	 * they are not expired memberships - the exipred rule will therefore be
	 * removed from the user.
	 *
	 * It seems that what's needed is to parse the rules prior to applying them
	 * to determine the final set of roles that a user should have. These rules
	 * can then be applied in one go, thus avoiding the overrides resulting from
	 * the conflicting rules.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user WP_User object of the user in question
	 * @param array $memberships The memberships of the WordPress user in question
	 */
	public function rule_apply( $user, $memberships = false ) {

		// removed check for admin user - DO NOT call this for admins UNLESS
		// you're using a plugin that enables multiple roles

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return;

		// kick out if we didn't get memberships passed
		if ( $memberships === false ) return;

		// loop through the supplied memberships
		foreach( $memberships['values'] AS $membership ) {

			// continue if something went wrong
			if ( ! isset( $membership['membership_type_id'] ) ) continue;
			if ( ! isset( $membership['status_id'] ) ) continue;

			// get membership type and status rule
			$membership_type_id = $membership['membership_type_id'];
			$status_id = $membership['status_id'];

			// get sync method and sanitize
			$method = $this->setting_get( 'method' );
			$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

			// get association rule for this membership type
			$association_rule = $this->rule_get_by_type( $membership_type_id, $method );

			// continue with next rule if we have an error of some kind
			if ( $association_rule === false ) continue;

			// get status rules
			$current_rule = $association_rule['current_rule'];
			$expiry_rule = $association_rule['expiry_rule'];

			// which sync method are we using?
			if ( $method == 'roles' ) {

				// SYNC ROLES

				// get roles for this association rule
				$current_wp_role = $association_rule['current_wp_role'];
				$expired_wp_role = $association_rule['expired_wp_role'];

				// continue if something went wrong
				if ( empty( $association_rule['current_wp_role'] ) ) continue;
				if ( empty( $association_rule['expired_wp_role'] ) ) continue;

				// does the user's membership status match a current status rule?
				if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {

					// add current role if the user does not have it
					if ( ! $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
						$this->plugin->users->wp_role_add( $user, $current_wp_role );
					}

					// remove expired role if the user has it
					if ( $this->plugin->users->wp_has_role( $user, $expired_wp_role ) ) {
						$this->plugin->users->wp_role_remove( $user, $expired_wp_role );
					}

					// set flag for action
					$flag = 'current';

				} else {

					// remove current role if the user has it
					if ( $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
						$this->plugin->users->wp_role_remove( $user, $current_wp_role );
					}

					// add expired role if the user does not have it
					if ( ! $this->plugin->users->wp_has_role( $user, $expired_wp_role ) ) {
						$this->plugin->users->wp_role_add( $user, $expired_wp_role );
					}

					// set flag for action
					$flag = 'expired';

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
				 * @param WP_User $user The WordPress user object
				 * @param int $membership_type_id The ID of the CiviCRM membership type
				 * @param int $status_id The ID of the CiviCRM membership status
				 * @param array $association_rule The rule used to apply the changes
				 */
				do_action( 'civi_wp_member_sync_rule_apply_roles_' . $flag, $user, $membership_type_id, $status_id, $association_rule );

			} else {

				// SYNC CAPABILITY

				// construct membership type capability name
				$capability = CIVI_WP_MEMBER_SYNC_CAP_PREFIX . $membership_type_id;

				// construct membership status capability name
				$capability_status = $capability . '_' . $status_id;

				// does the user's membership status match a current status rule?
				if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {

					// do we have the "Members" plugin?
					if ( defined( 'MEMBERS_VERSION' ) ) {

						// add the plugin's custom capability
						$this->plugin->users->wp_cap_add( $user, 'restrict_content' );

					}

					// add type capability
					$this->plugin->users->wp_cap_add( $user, $capability );

					// clear status capabilities
					$this->plugin->users->wp_cap_remove_status( $user, $capability );

					// add status capability
					$this->plugin->users->wp_cap_add( $user, $capability_status );

					// set flag for action
					$flag = 'current';

				} else {

					// do we have the "Members" plugin?
					if ( defined( 'MEMBERS_VERSION' ) ) {

						// remove the plugin's custom capability
						$this->plugin->users->wp_cap_remove( $user, 'restrict_content' );

					}

					// remove type capability
					$this->plugin->users->wp_cap_remove( $user, $capability );

					// clear status capabilities
					$this->plugin->users->wp_cap_remove_status( $user, $capability );

					// set flag for action
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
				 *
				 * @param WP_User $user The WordPress user object
				 * @param int $membership_type_id The ID of the CiviCRM membership type
				 * @param int $status_id The ID of the CiviCRM membership status
				 * @param array $capability The membership type capability added or removed
				 */
				do_action( 'civi_wp_member_sync_rule_apply_caps_' . $flag, $user, $membership_type_id, $status_id, $capability );

			}

		}

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
	 * @param WP_User $user WP_User object of the user in question
	 * @param object $membership The membership details of the WordPress user in question
	 */
	public function rule_undo( $user, $membership = false ) {

		// get sync method and sanitize
		$method = $this->setting_get( 'method' );
		$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

		// which sync method are we using?
		if ( $method == 'roles' ) {

			/**
			 * When there are multiple memberships, remove both the current role
			 * and the expired role. If this is the only remaining membership
			 * that the user has, however, then simply switch the current role
			 * for the expired role. This is to prevent users ending up with no
			 * role whatsoever.
			 */

			// get association rule for this membership type
			$association_rule = $this->rule_get_by_type( $membership->membership_type_id, $method );

			// kick out if something went wrong
			if ( $association_rule === false ) return;
			if ( empty( $association_rule['current_wp_role'] ) ) return;
			if ( empty( $association_rule['expired_wp_role'] ) ) return;

			// get roles for this status rule
			$current_wp_role = $association_rule['current_wp_role'];
			$expired_wp_role = $association_rule['expired_wp_role'];

			// get remaining memberships for this user
			$memberships = $this->plugin->members->membership_get_by_contact_id( $membership->contact_id );

			// if this user has a remaining membership
			if (
				$memberships !== false AND
				$memberships['is_error'] == 0 AND
				isset( $memberships['values'] ) AND
				count( $memberships['values'] ) > 0
			) {

				/**
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

				// remove current role if the user has it
				if ( $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
					$this->plugin->users->wp_role_remove( $user, $current_wp_role );
				}

				// remove expired role if the user has it
				if ( $this->plugin->users->wp_has_role( $user, $expired_wp_role ) ) {
					$this->plugin->users->wp_role_remove( $user, $expired_wp_role );
				}

				// perform sync for this user
				$this->rule_apply( $user, $memberships );

			} else {

				// replace the current role with the expired role
				if ( $this->plugin->users->wp_has_role( $user, $current_wp_role ) ) {
					$this->plugin->users->wp_role_replace( $user, $current_wp_role, $expired_wp_role );
				}

			}

		} else {

			// construct capability name
			$capability = CIVI_WP_MEMBER_SYNC_CAP_PREFIX . $membership->membership_type_id;

			// remove capability
			$this->plugin->users->wp_cap_remove( $user, $capability );

			// remove status capability
			$this->plugin->users->wp_cap_remove_status( $user, $capability );

			// do we have the "Members" plugin?
			if ( defined( 'MEMBERS_VERSION' ) ) {

				// remove the custom capability
				$this->plugin->users->wp_cap_remove( $user, 'restrict_content' );

			}

		}

	}



	//##########################################################################



	/**
	 * Register "Groups" plugin hooks if it's present.
	 *
	 * @since 0.2.3
	 */
	public function groups_plugin_hooks() {

		// bail if we don't have the "Groups" plugin
		if ( ! defined( 'GROUPS_CORE_VERSION' ) ) return;

		// hook into rule add
		add_action( 'civi_wp_member_sync_rule_add_capabilities', array( $this, 'groups_add_cap' ) );

		// hook into rule edit
		add_action( 'civi_wp_member_sync_rule_edit_capabilities', array( $this, 'groups_edit_cap' ) );

		// hook into rule delete
		add_action( 'civi_wp_member_sync_rule_delete_capabilities', array( $this, 'groups_delete_cap' ) );

		// hook into manual sync process, before sync
		add_action( 'civi_wp_member_sync_pre_sync_all', array( $this, 'groups_pre_sync' ) );

		// hook into save post and auto-restrict (DISABLED)
		//add_action( 'save_post', array( $this, 'groups_intercept_save_post' ), 1, 2 );

	}



	/**
	 * When an association rule is created, add capability to "Groups" plugin.
	 *
	 * @since 0.2.3
	 *
	 * @param array $data The association rule data
	 */
	public function groups_add_cap( $data ) {

		// add it as "read post" capability
		$this->groups_read_cap_add( $data['capability'] );

		// get existing capability
		$capability = Groups_Capability::read_by_capability( $data['capability'] );

		// bail if it already exists
		if ( false !== $capability ) return;

		// create a new capability
		$capability_id = Groups_Capability::create( array( 'capability' => $data['capability'] ) );

	}



	/**
	 * When an association rule is edited, edit capability in "Groups" plugin.
	 *
	 * @since 0.2.3
	 *
	 * @param array $data The association rule data
	 */
	public function groups_edit_cap( $data ) {

		// same as add
		$this->groups_add_cap( $data );

	}



	/**
	 * When an association rule is deleted, delete capability from "Groups" plugin.
	 *
	 * @since 0.2.3
	 *
	 * @param array $data The association rule data
	 */
	public function groups_delete_cap( $data ) {

		// delete from "read post" capabilities
		$this->groups_read_cap_delete( $data['capability'] );

		// get existing
		$capability = Groups_Capability::read_by_capability( $data['capability'] );

		// bail if it doesn't exist
		if ( false === $capability ) return;

		// delete capability
		$capability_id = Groups_Capability::delete( $capability->capability_id );

	}



	/**
	 * Add "read post" capability to "Groups" plugin.
	 *
	 * @since 0.2.3
	 *
	 * @param array $capability The capability to add
	 */
	public function groups_read_cap_add( $capability ) {

		// init with Groups default
		$default_read_caps = array( Groups_Post_Access::READ_POST_CAPABILITY );

		// get current
		$current_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, $default_read_caps );

		// bail if we have it already
		if ( in_array( $capability, $current_read_caps ) ) return;

		// add the new capability
		$current_read_caps[] = $capability;

		// resave option
		Groups_Options::update_option( Groups_Post_Access::READ_POST_CAPABILITIES, $current_read_caps );

	}



	/**
	 * Delete "read post" capability from "Groups" plugin.
	 *
	 * @since 0.2.3
	 *
	 * @param array $capability The capability to delete
	 */
	public function groups_read_cap_delete( $capability ) {

		// init with Groups default
		$default_read_caps = array( Groups_Post_Access::READ_POST_CAPABILITY );

		// get current
		$current_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, $default_read_caps );

		// get key if capability is present
		$key = array_search( $capability, $current_read_caps );

		// bail if we don't have it
		if ( $key === false ) return;

		// delete the capability
		unset( $current_read_caps[$key] );

		// resave option
		Groups_Options::update_option( Groups_Post_Access::READ_POST_CAPABILITIES, $current_read_caps );

	}



	/**
	 * Before a manual sync, make sure "Groups" plugin is in sync.
	 *
	 * @since 0.2.3
	 */
	public function groups_pre_sync() {

		// get sync method and sanitize
		$method = $this->setting_get( 'method' );
		$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';

		// bail if we're not syncing capabilities
		if ( $method != 'capabilities' ) return;

		// get rules
		$rules = $this->rules_get_by_method( $method );

		// if we get some...
		if ( $rules !== false AND is_array( $rules ) AND count( $rules ) > 0 ) {

			// add capability to "Groups" plugin if not already present
			foreach( $rules AS $rule ) {
				$this->groups_add_cap( $rule );
			}

		}

	}



	/**
	 * Auto-restrict a post based on the post type.
	 *
	 * @since 0.2.3
	 *
	 * This is a placeholder in case we want to extend this plugin to handle
	 * automatic content restriction.
	 *
	 * @param int $post_id The numeric ID of the post
	 * @param object $post The WordPress post object
	 */
	public function groups_intercept_save_post( $post_id, $post ) {

		// bail if something went wrong
		if( ! is_object( $post ) OR ! isset( $post->post_type ) ) return;

		// do different things based on the post type
		switch($post->post_type) {

			case 'post':
				// add your default capabilities
				Groups_Post_Access::create( array( 'post_id'=>$post_id, 'capability'=>'Premium' ) );
				break;

			default:
				// do other stuff

		}

	}



	//##########################################################################



	/**
	 * General debugging utility.
	 *
	 * @since 0.1
	 */
	public function do_debug() {

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return;

		// get CiviCRM memberships
		$memberships = civicrm_api( 'Membership', 'getcount', array(
			'version' => '3',
			'options' => array(
				'limit' => '99999999',
			),
		));

		print_r( $memberships ); die();

		/*
		$date = CRM_Utils_Date::getToday();
		$current = CRM_Utils_Date::customFormat($date, '%Y-%m-%d');

		CRM_Member_BAO_Membership::getMembershipCount($key, $current);
		*/

	}



} // class ends



