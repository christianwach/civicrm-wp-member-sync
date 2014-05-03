<?php /* 
--------------------------------------------------------------------------------
Civi_WP_Member_Sync_Admin Class
--------------------------------------------------------------------------------
*/



/**
 * Class for encapsulating admin functionality
 */
class Civi_WP_Member_Sync_Admin {

	/** 
	 * Properties
	 */
	
	// parent object
	public $parent_obj;
	
	// migration object
	public $migrate;
	
	// admin pages
	public $settings_page;
	public $sync_page;
	public $rules_list_page;
	public $rule_add_edit_page;
	
	// settings
	public $settings = array();
	
	// form error messages
	public $error_strings;
	
	// errors in current submission
	public $errors;
	
	
	
	/** 
	 * Initialise this object
	 * @param object $parent_obj The parent object
	 * @return object
	 */
	function __construct( $parent_obj ) {
		
		// store reference to parent
		$this->parent_obj = $parent_obj;
	
		// define errors
		$this->error_strings = array(
			
			// update rules error strings
			1 => __( 'Please select a CiviCRM Membership Type', 'civi-wp-member-sync' ),
			2 => __( 'Please select a WordPress Role', 'civi-wp-member-sync' ),
			3 => __( 'Please select a Current Status', 'civi-wp-member-sync' ),
			4 => __( 'Please select an Expire Status', 'civi-wp-member-sync' ),
			5 => __( 'Please select a WordPress Expiry Role', 'civi-wp-member-sync' ),
			6 => __( 'You can not have the same Status Rule registered as both "Current" and "Expired"', 'civi-wp-member-sync' ),
			
			// delete rule error strings
			7 => __( 'Could not delete Association Rule', 'civi-wp-member-sync' ),
			
		);
	
		// load our Migration utility class
		require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'civi-wp-ms-migrate.php' );
		
		// instantiate
		$this->migrate = new Civi_WP_Member_Sync_Migrate( $this );
	
		// --<
		return $this;
		
	}
	
	
	
	/**
	 * Perform activation tasks
	 * @return nothing
	 */
	public function activate() {
		
		// store version for later reference
		add_option( 'civi_wp_member_sync_version', CIVI_MEMBER_SYNC_VERSION );
		
		// store default settings
		add_option( 'civi_wp_member_sync_settings', $this->settings_get_default() );
	
	}
	
	
	
	/**
	 * Perform deactivation tasks
	 * @return nothing
	 */
	public function deactivate() {
		
		// we delete our options in uninstall.php
		
	}
	
	
	
	/**
	 * Initialise when CiviCRM initialises
	 * @return nothing
	 */
	public function initialise() {
		
		// load settings array
		$this->settings = get_option( 'civi_wp_member_sync_settings', $this->settings );
		
		// is this the back end?
		if ( is_admin() ) {
		
			// multisite?
			if ( is_multisite() ) {
	
				// add admin page to Network menu
				add_action( 'network_admin_menu', array( $this, 'admin_menu' ), 30 );
			
			} else {
			
				// add admin page to menu
				add_action( 'admin_menu', array( $this, 'admin_menu' ) ); 
			
			}
			
		}
		
	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * Add this plugin's Settings Page to the WordPress admin menu
	 * @return nothing
	 */
	public function admin_menu() {
		
		// we must be network admin in multisite
		if ( is_multisite() AND !is_super_admin() ) { return false; }
		
		// check user permissions
		if ( !current_user_can('manage_options') ) { return false; }
		
		// multisite?
		if ( is_multisite() ) {
			
			// add settings page to the Network Settings menu
			$this->settings_page = add_submenu_page(
				'settings.php', 
				__( 'CiviCRM WordPress Member Sync Settings', 'civi-wp-member-sync' ), // page title
				__( 'CiviCRM WordPress Member Sync', 'civi-wp-member-sync' ), // menu title
				'manage_options', // required caps
				'civi_wp_member_sync_settings', // slug name
				array( $this, 'page_settings' ) // callback
			);
		
		} else {
		
			// add the settings page to the Settings menu
			$this->settings_page = add_options_page(
				__( 'CiviCRM WordPress Member Sync Settings', 'civi-wp-member-sync' ), // page title
				__( 'CiviCRM WordPress Member Sync', 'civi-wp-member-sync' ), // menu title
				'manage_options', // required caps
				'civi_wp_member_sync_settings', // slug name
				array( $this, 'page_settings' ) // callback
			);
		
		}
		
		// add scripts and styles
		add_action( 'admin_print_styles-'.$this->settings_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-'.$this->settings_page, array( $this, 'admin_head' ), 50 );
		
		// add manual sync page
		$this->sync_page = add_submenu_page(
			'civi_wp_member_sync_settings', // parent slug
			__( 'CiviCRM WordPress Member Sync: Manual Sync', 'civi-wp-member-sync' ), // page title
			__( 'Manual Sync', 'civi-wp-member-sync' ), // menu title
			'manage_options', // required caps
			'civi_wp_member_sync_manual_sync', // slug name
			array( $this, 'page_manual_sync' ) // callback
		);
		
		// add scripts and styles
		add_action( 'admin_print_styles-'.$this->sync_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-'.$this->sync_page, array( $this, 'admin_head' ), 50 );
		
		// add rules listing page
		$this->rules_list_page = add_submenu_page(
			'civi_wp_member_sync_settings', // parent slug
			__( 'CiviCRM WordPress Member Sync: List Rules', 'civi-wp-member-sync' ), // page title
			__( 'List Rules', 'civi-wp-member-sync' ), // menu title
			'manage_options', // required caps
			'civi_wp_member_sync_list', // slug name
			array( $this, 'page_rules_list' ) // callback
		);
		
		// add scripts and styles
		add_action( 'admin_print_styles-'.$this->rules_list_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-'.$this->rules_list_page, array( $this, 'admin_head' ), 50 );
		
		// add rules page
		$this->rule_add_edit_page = add_submenu_page(
			'civi_wp_member_sync_settings', // parent slug
			__( 'CiviCRM WordPress Member Sync: Association Rule', 'civi-wp-member-sync' ), // page title
			__( 'Association Rule', 'civi-wp-member-sync' ), // menu title
			'manage_options', // required caps
			'civi_wp_member_sync_rules', // slug name
			array( $this, 'page_rule_add_edit' ) // callback
		);
		
		// add scripts and styles
		add_action( 'admin_print_scripts-'.$this->rule_add_edit_page, array( $this, 'admin_js' ) );
		add_action( 'admin_print_styles-'.$this->rule_add_edit_page, array( $this, 'admin_css' ) );
		add_action( 'admin_head-'.$this->rule_add_edit_page, array( $this, 'admin_head' ), 50 );
		
		// try and update options
		$saved = $this->settings_update_router();
		
	}
	
	
	
	/** 
	 * Initialise plugin help
	 * @return nothing
	 */
	public function admin_head() {
		
		// there's a new screen object for help in 3.3
		$screen = get_current_screen();
		//print_r( $screen ); die();
		
		// use method in this class
		$this->admin_help( $screen );
		
	}
	
	
	
	/** 
	 * Enqueue plugin options page css
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
	 * Ensure jQuery and jQuery Form are available in WP admin
	 * @return nothing
	 */
	public function admin_js() {
		
		// add javascript plus dependencies
		wp_enqueue_script(
		
			'civi_wp_member_sync_admin_js', 
			plugins_url( 'assets/js/civi-wp-ms.js', CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ),
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
			'civi_wp_member_sync_admin_js', 
			'CiviWpMemberSyncSettings', 
			$vars 
		);
		
	}
	
	
	
	/** 
	 * @description: adds help copy to admin page in WP3.3+
	 * @todo: 
	 *
	 */
	public function admin_help( $screen ) {
	
		//print_r( $screen ); die();
		
		// kick out if not our screen
		if ( $screen->id != $this->rules_list_page ) { return; }
		
		// add a tab - we can add more later
		$screen->add_help_tab( array(
		
			'id'      => 'civi_wp_member_sync',
			'title'   => __( 'CiviCRM WordPress Member Sync', 'civi-wp-member-sync' ),
			'content' => $this->get_help(),
			
		));
		
		// --<
		return $screen;
		
	}
	
	
	
	/** 
	 * Get help text
	 * @return string $help Help formatted as HTML
	 */
	public function get_help() {
		
		// stub help text, to be developed further...
		$help = '<p>' . __( 'For further information about using CiviCRM WordPress Member Sync, please refer to the README.md that comes with this plugin.', 'civi-wp-member-sync' ) . '</p>';
		
		// --<
		return $help;
		
	}
	
	
	
	//##########################################################################
	
	
	
	/** 
	 * Show civi_wp_member_sync_settings admin page
	 * @return nothing
	 */
	public function page_settings() {
		
		// check user permissions
		if ( current_user_can('manage_options') ) {

			// get admin page URLs
			$urls = $this->page_get_urls();
			
			// do we have the legacy plugin?
			if ( $this->migrate->legacy_plugin_exists() ) {
			
				// include template file
				include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/migrate.php' );
				
				// --<
				return;
		
			}

			// get our sync method
			$method = $this->setting_get( 'method' );
			
			// get all schedules
			$schedules = $this->parent_obj->schedule->intervals_get();
			//print_r( $schedules ); die();
			
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
	 * Show civi_wp_member_sync_manual_sync admin page
	 * @return nothing
	 */
	public function page_manual_sync() {
		
		// check user permissions
		if ( current_user_can('manage_options') ) {

			// get admin page URLs
			$urls = $this->page_get_urls(); 

			// include template file
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/manual-sync.php' );
		
		}
		
	}
	
	
		
	/** 
	 * Show civi_wp_member_sync_settings admin page
	 * @return nothing
	 */
	public function page_rules_list() {
		
		// check user permissions
		if ( current_user_can('manage_options') ) {
		
			// get admin page URLs
			$urls = $this->page_get_urls(); 

			// get method
			$method = $this->setting_get( 'method' );
		
			// get data
			$all_data = $this->setting_get( 'data' );
		
			// get data for this sync method
			$data = ( isset( $all_data[$method] ) ) ? $all_data[$method] : array();
		
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
	 * Show civi_wp_member_sync_rules admin page
	 * @return nothing
	 */
	public function page_rule_add_edit() {
	
		// check user permissions
		if ( current_user_can('manage_options') ) {
			
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
	 * Show civi_wp_member_sync_rules admin page
	 * @return nothing
	 */
	private function page_rule_add() {

		// get admin page URLs
		$urls = $this->page_get_urls(); 
		
		// get all membership types
		$membership_types = $this->parent_obj->members->types_get_all();
	
		// get all membership status rules
		$status_rules = $this->parent_obj->members->status_rules_get_all();
		
		// get method
		$method = $this->setting_get( 'method' );
		
		// well?
		if ( $method == 'roles' ) {
		
			// get filtered roles
			$roles = $this->parent_obj->users->wp_role_names_get_all();
			//print_r( $roles ); die();
		
			// include template file
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-role-add.php' );
	
		} else {
		
			// include template file
			include( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'assets/templates/rule-cap-add.php' );
	
		}
		
	}
	
	
	
	/** 
	 * Show civi_wp_member_sync_rules admin page
	 * @return nothing
	 */
	private function page_rule_edit() {
	
		// get admin page URLs
		$urls = $this->page_get_urls(); 
		
		// get all membership types
		$membership_types = $this->parent_obj->members->types_get_all();
		
		// get all membership status rules
		$status_rules = $this->parent_obj->members->status_rules_get_all();
		
		// get method
		$method = $this->setting_get( 'method' );
		
		// get requested membership type ID
		$civi_member_type_id = absint( $_GET['type_id'] );

		// get rule by type
		$selected_rule = $this->rule_get_by_type( $civi_member_type_id, $method );
		
		// set vars for populating form
		$current_rule = $selected_rule['current_rule'];
		$expiry_rule = $selected_rule['expiry_rule'];
		
		// do we need roles?
		if ( $method == 'roles' ) {

			// get filtered roles
			$roles = $this->parent_obj->users->wp_role_names_get_all();
			//print_r( $roles ); die();
			
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
	 * Get admin page URLs
	 * @return array $admin_urls The array of admin page URLs
	 */
	public function page_get_urls() {
		
		// only calculate once
		if ( isset( $this->urls ) ) { return $this->urls; }
		
		// init return
		$this->urls = array();
		
		// multisite?
		if ( is_multisite() ) {
		
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
	 * If the slug hasn't been registered properly no url will be returned
	 * @param string $menu_slug The slug name to refer to this menu by (should be unique for this menu)
	 * @param bool $echo Whether or not to echo the url - default is true
	 * @return string the url
	 */
	public function network_menu_page_url($menu_slug, $echo = true) {
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
		
		$url = esc_url($url);
		
		if ( $echo ) echo $url;
		
		return $url;
	}
	
	
	
	/** 
	 * Get the URL for the form action
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
	 * Route settings updates to relevant methods
	 * @return bool $result True on success, false otherwise
	 */
	public function settings_update_router() {
	
		// init result
		$result = false;
		
		// was the Migrate form submitted?
		if( isset( $_POST[ 'civi_wp_member_sync_migrate_submit' ] ) ) {
			$result = $this->legacy_migrate();
		}
		
		// was the Settings form submitted?
		if( isset( $_POST[ 'civi_wp_member_sync_settings_submit' ] ) ) {
			$result = $this->settings_update();
		}
		
		// was the Manual Sync form submitted?
		if( isset( $_POST[ 'civi_wp_member_sync_manual_sync_submit' ] ) ) {

			// check that we trust the source of the request
			check_admin_referer( 'civi_wp_member_sync_manual_sync_action', 'civi_wp_member_sync_nonce' );
			
			// trace
			//print_r( $_POST ); die();
		
			// pass on
			$result = $this->members->sync_all();
			
		}
		
		// was the Rule form submitted?
		if( isset( $_POST[ 'civi_wp_member_sync_rules_submit' ] ) ) {
			$result = $this->rule_update();
		}
		
		// was a Delete Link clicked?
		if ( isset( $_GET['syncrule'] ) AND $_GET['syncrule'] == 'delete' ) {
			if ( ! empty( $_GET['type_id'] ) AND is_numeric( $_GET['type_id'] ) ) {
				$result = $this->rule_delete();
			}
		}
		
		// --<
		return $result;
		
	}
	
	
	
	/**
	 * Get default plugin settings
	 * @return array $settings The array of settings, keyed by setting name
	 */
	public function settings_get_default() {
	
		// init return
		$settings = array();
		
		// set empty data arrays
		$settings['data']['roles'] = array();
		$settings['data']['capabilities'] = array();

		// set default method
		$settings['method'] = 'roles';

		// switch all sync settings on by default
		$settings['login'] = 1;
		$settings['civicrm'] = 1;
		$settings['schedule'] = 1;
		
		// set default schedule interval
		$settings['interval'] = 'daily';
		
		// allow filtering
		return apply_filters( 'civi_wp_member_sync_default_settings', $settings );
	
	}
	
	
	
	/**
	 * Update plugin settings
	 * @return nothing
	 */
	public function settings_update() {
	
		// check that we trust the source of the request
		check_admin_referer( 'civi_wp_member_sync_settings_action', 'civi_wp_member_sync_nonce' );
		//print_r( $_POST ); die();
		
		
		
		// debugging switch for admins and network admins - if set, triggers do_debug() below
		if ( is_super_admin() AND isset( $_POST['civi_wp_member_sync_settings_debug'] ) ) {
			$settings_debug = absint( $_POST['civi_wp_member_sync_settings_debug'] );
			$debug = $settings_debug ? 1 : 0;
			if ( $debug ) { $this->do_debug(); }
			return;
		}
		
		
		
		// synchronization method
		$settings_method = 'roles';
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
		
		
		
		// civicrm sync enabled
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
			$this->parent_obj->schedule->unschedule();
			
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
				$this->parent_obj->schedule->unschedule();
				
				// now add new scheduled event
				$this->parent_obj->schedule->schedule( $settings_interval );
			
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
	 * Save the plugin's settings array
	 * @return bool $result True if setting value has changed, false if not or if update failed
	 */
	public function settings_save() {
		
		// update WordPress option and return result
		return update_option( 'civi_wp_member_sync_settings', $this->settings );
		
	}
	
	
	
	/** 
	 * Return a value for a specified setting
	 * @return mixed $setting The value of the setting
	 */
	public function setting_get( $setting_name = '', $default = false ) {
	
		// sanity check
		if ( $setting_name == '' ) {
			wp_die( __( 'You must supply a setting to setting_get()', 'civi-wp-member-sync' ) );
		}
		
		// get setting
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[ $setting_name ] : $default;
		
	}
	
	
	
	/** 
	 * Set a value for a specified setting
	 * @return nothing
	 */
	public function setting_set( $setting_name = '', $value = '' ) {
	
		// sanity check
		if ( $setting_name == '' ) {
			wp_die( __( 'You must supply a setting to setting_set()', 'civi-wp-member-sync' ) );
		}
		
		// set setting
		$this->settings[ $setting_name ] = $value;
		
	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * Get an association rule by membership type ID
	 * @param int $type_id The numeric ID of the Civi membership type
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
	 * Update (or add) a membership rule
	 * @return bool $success True if successful, false otherwise
	 */
	public function rule_update() {
		
		// check that we trust the source of the data
		check_admin_referer( 'civi_wp_member_sync_rule_action', 'civi_wp_member_sync_nonce' );
		
		/*
		print_r( array(
			'POST' => $_POST,
		) ); die();
		*/
		
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
				break;
			}
			
		}
		
		/*
		print_r( array(
			'POST' => $_POST,
			'current_rule' => $current_rule,
			'expiry_rule' => $expiry_rule,
			'intersect' => $intersect,
			'current_expire_clash' => ( $current_expire_clash ? 'y' : 'n' ),
		) ); die();
		*/
	
		// do we want roles?
		if ( $method == 'roles' ) {
			
			// check and sanitise WP Role
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
			
			/*
			print_r( array(
				'POST' => $_POST,
				'data' => $data,
			) ); die();
			*/
		
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
	 * Delete a membership rule
	 * @return bool $success True if successful, false otherwise
	 */
	public function rule_delete() {
		
		// check nonce
		if ( 
			! isset( $_GET['civi_wp_member_sync_delete_nonce'] ) OR 
			! wp_verify_nonce( $_GET['civi_wp_member_sync_delete_nonce'], 'civi_wp_member_sync_delete_link' )
		) {
		
			wp_die( __( 'Cheating, eh?', 'civi-wp-member-sync' ) );
			exit();
			
		}
		
		// access db object
		global $wpdb;
		
		// construct table name
		$table_name = $wpdb->prefix . 'civi_member_sync';
		
		// construct query
		$sql = $wpdb->prepare( "DELETE FROM $table_name WHERE `id` = %d", absint( $_GET['type_id'] ) );
		
		// do query
		if ( $wpdb->query( $sql ) ) {
			
			// get admin URLs
			$urls = $this->page_get_urls();
			
			// redirect to list page with message
			wp_redirect( $urls['list'] . '&syncrule=delete' );
			die();
			
		} else {
			
			// show error
			$this->errors[] = 7;
			
			// sad face
			return false;
			
		}
		
	}
	
	
	
	/**
	 * Assign WordPress role or capability based on membership status
	 * @param WP_User $user WP_User object of the user in question
	 * @param array $membership The membership details of the WordPress user in question
	 * @return bool True if successful, false otherwise
	 */
	public function rule_apply( $user, $membership = false ) {
		
		// disable
		return true;
		
		// removed check for admin user - DO NOT call this for admins UNLESS 
		// you're using a plugin that enables multiple roles
		
		// get primary WP role
		$user_role = $this->parent_obj->users->wp_role_get( $user );
	
		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) { return false; }
		
		// kick out if we didn't get membership details passed
		if ( $membership === false ) { return false; }
		
		// get membership type and status rule
		foreach( $membership['values'] AS $value ) {
			$membership_type_id = $value['membership_type_id'];
			$status_id = $value['status_id'];
		}
		//print_r( array( $membership_type_id, $status_id ) ); die();
	
		// kick out if something went wrong
		if ( ! isset( $membership_type_id ) ) { return false; }
		if ( ! isset( $status_id ) ) { return false; }
		
		// get sync method and sanitize
		$method = $this->setting_get( 'method' );
		$method = ( $method == 'roles' ) ? 'roles' : 'capabilities';
		
		// get association rule for this membership type
		$association_rule = $this->rule_get_by_type( $membership_type_id, $method );
		//print_r( $association_rule ); die();

		// kick out if we have an error of some kind
		if ( $association_rule === false ) { return false; }
		
		// get status rules
		$current_rule = $association_rule['current_rule'];
		$expiry_rule = $association_rule['expiry_rule'];
		
		// which sync method are we using?
		if ( $method == 'roles' ) {
			
			// SYNC ROLES
	
			///*
			print_r( array(
				'status_id' => $status_id,
				'current_rule' => $current_rule,
				'expiry_rule' => $expiry_rule,
				'user_role' => $user_role,
				'wp_role' => $association_rule['current_wp_role'],
			) ); die();
			//*/
			
			// does the user's membership status match a current status rule?
			if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {
			
				// yes - get role for current status rule
				$wp_role = $association_rule['current_wp_role'];
			
				// if we have one (we should) and the user has a different role...
				if (  ! empty( $wp_role ) AND $wp_role != $user_role ) {
				
					// no - set new role
					$this->parent_obj->users->wp_role_set( $user, $user_role, $wp_role );
				 
				}
		
			} else {
	
				// no - get role for expired status rule
				$expired_wp_role = $association_rule->expired_wp_role;
			
				// if we have one (we should) and the user has a different role...
				if ( ! empty( $expired_wp_role ) AND $expired_wp_role != $user_role ) {
			
					// switch user's role to the expired role
					$this->parent_obj->users->wp_role_set( $user, $user_role, $expired_wp_role );
				
				}
		
			}
		
			// --<
			return true;
	
		} else {
		
			// SYNC CAPABILITY
			
			// construct capability name
			$capability = CIVI_WP_MEMBER_SYNC_CAP_PREFIX . $membership_type_id;
		
			///*
			print_r( array(
				'status_id' => $status_id,
				'current_rule' => $current_rule,
				'expiry_rule' => $expiry_rule,
				'capability' => $capability,
			) ); die();
			//*/
		
			// does the user's membership status match a current status rule?
			if ( isset( $status_id ) && array_search( $status_id, $current_rule ) ) {
				
				// add capability
				$this->parent_obj->admin->wp_cap_add( $user, $capability );
			
			} else {
			
				// remove capability
				$this->parent_obj->admin->wp_cap_remove( $user, $capability );
			
			}
	
			// --<
			return true;
	
		}
	
		// --<
		return false;
		
	}
	
	
	
	//##########################################################################
	
	
	
	/** 
	 * General debugging utility
	 * @return nothing
	 */
	public function do_debug() {
		
		global $current_user;
		$me = new WP_User( $current_user->ID );
		print_r( array( 
			'current_user' => $current_user,
			'me' => $me, 
		) ); die();
		
		// get all WordPress users
		$users = get_users( array( 'all_with_meta' => true ) );
		print_r( $users ); die();
		
		global $wp_roles;
		$roles = $wp_roles->get_names();
		
		// get all role names
		$role_names = $this->parent_obj->users->wp_role_names_get_all();
		
		print_r( array( 
			'WP Roles' => $roles,
			'WP Role Names' => $role_names, 
		) ); die();
		
		if ( function_exists( 'bbp_get_blog_roles' ) ) {
			$bbpress_roles = bbp_get_blog_roles();
		}
		
	}
	
	
	
} // class ends



