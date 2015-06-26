<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Member Sync
Plugin URI: https://github.com/christianwach/civicrm-wp-member-sync
Description: Synchronize CiviCRM memberships with WordPress user roles or capabilities
Author: Christian Wach
Version: 0.2.5
Author URI: http://haystack.co.uk
Text Domain: civicrm-wp-member-sync
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------

Thanks to:
Jag Kandasamy <http://www.orangecreative.net> for the
"Wordpress CiviMember Role Sync Plugin" <https://github.com/jeevajoy/Wordpress-CiviCRM-Member-Role-Sync>

Tadpole Collective <https://tadpole.cc> for their fork:
"Tadpole CiviMember Role Synchronize" <https://github.com/tadpolecc/civi_member_sync>

--------------------------------------------------------------------------------
*/



// define capability prefix
if ( ! defined( 'CIVI_WP_MEMBER_SYNC_CAP_PREFIX' ) ) {
	define( 'CIVI_WP_MEMBER_SYNC_CAP_PREFIX', 'civimember_' );
}

// define plugin version (bumping this will also refresh CSS and JS)
define( 'CIVI_WP_MEMBER_SYNC_VERSION', '0.2.4' );

// store reference to this file
define( 'CIVI_WP_MEMBER_SYNC_PLUGIN_FILE', __FILE__ );

// store URL to this plugin's directory
if ( ! defined( 'CIVI_WP_MEMBER_SYNC_PLUGIN_URL' ) ) {
	define( 'CIVI_WP_MEMBER_SYNC_PLUGIN_URL', plugin_dir_url( CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ) );
}

// store PATH to this plugin's directory
if ( ! defined( 'CIVI_WP_MEMBER_SYNC_PLUGIN_PATH' ) ) {
	define( 'CIVI_WP_MEMBER_SYNC_PLUGIN_PATH', plugin_dir_path( CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ) );
}

// debug flag for developers
//define( 'CIVI_WP_MEMBER_SYNC_DEBUG', true );

// migrate flag for developers (see civi-wp-ms-migrate.php)
//define( 'CIVI_WP_MEMBER_SYNC_MIGRATE', true );



/**
 * Class for encapsulating plugin functionality
 */
class Civi_WP_Member_Sync {

	/**
	 * Properties
	 */

	// Users utilities class
	public $users;

	// Schedule utilities class
	public $schedule;

	// Admin utilities class
	public $admin;

	// CiviMember utilities class
	public $members;



	/**
	 * Initialise this object
	 *
	 * @return object
	 */
	function __construct() {

		// use translation
		add_action( 'plugins_loaded', array( $this, 'translation' ) );

		// initialise plugin when CiviCRM initialises
		add_action( 'civicrm_instance_loaded', array( $this, 'initialise' ) );

		// load our Users utility class
		require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'civi-wp-ms-users.php' );

		// instantiate
		$this->users = new Civi_WP_Member_Sync_Users( $this );

		// load our Schedule utility class
		require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'civi-wp-ms-schedule.php' );

		// instantiate
		$this->schedule = new Civi_WP_Member_Sync_Schedule( $this );

		// load our Admin utility class
		require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'civi-wp-ms-admin.php' );

		// instantiate
		$this->admin = new Civi_WP_Member_Sync_Admin( $this );

		// load our CiviCRM utility class
		require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'civi-wp-ms-members.php' );

		// instantiate
		$this->members = new Civi_WP_Member_Sync_Members( $this );

		// --<
		return $this;

	}



	//##########################################################################



	/**
	 * Perform plugin activation tasks
	 *
	 * @return void
	 */
	public function activate() {

		// setup plugin admin
		$this->admin->activate();

	}



	/**
	 * Perform plugin deactivation tasks
	 *
	 * @return void
	 */
	public function deactivate() {

		// remove scheduled hook
		$this->schedule->unschedule();

	}



	/**
	 * Initialise objects when CiviCRM initialises
	 *
	 * @return void
	 */
	public function initialise() {

		// initialise users object
		$this->users->initialise();

		// initialise schedule object
		$this->schedule->initialise();

		// initialise Admin object
		$this->admin->initialise();

		// initialise CiviCRM object
		$this->members->initialise();

		// broadcast that we're up and running
		do_action( 'civi_wp_member_sync_initialised' );

	}



	//##########################################################################



	/**
	 * Load translation if present
	 */
	public function translation() {

		// only use, if we have it...
		if( function_exists( 'load_plugin_textdomain' ) ) {

			// there are no translations as yet, but they can now be added
			load_plugin_textdomain(

				// unique name
				'civicrm-wp-member-sync',

				// deprecated argument
				false,

				// relative path to directory containing translation files
				dirname( plugin_basename( CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ) ) . '/languages/'

			);

		}

	}



} // class ends



// declare as global for external reference
global $civi_wp_member_sync;

// init plugin
$civi_wp_member_sync = new Civi_WP_Member_Sync;

// plugin activation
register_activation_hook( __FILE__, array( $civi_wp_member_sync, 'activate' ) );

// plugin deactivation
register_deactivation_hook( __FILE__, array( $civi_wp_member_sync, 'deactivate' ) );

// uninstall uses the 'uninstall.php' method
// see: http://codex.wordpress.org/Function_Reference/register_uninstall_hook



/**
 * Add utility links to WordPress Plugin Listings Page
 *
 * @param array $links The existing list of plugin links
 * @return array $links The amended list of plugin links
 */
function civi_wp_member_sync_plugin_add_settings_link( $links ) {

	// access plugin
	global $civi_wp_member_sync;

	// get admin URLs
	$urls = $civi_wp_member_sync->admin->page_get_urls();

	// add courtesy link
	$links[] = '<a href="' . $urls['settings'] . '">' . __( 'Settings', 'civicrm-wp-member-sync' ) . '</a>';

	// --<
	return $links;

}

// contstriuct filter
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'civi_wp_member_sync_plugin_add_settings_link' );



