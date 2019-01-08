<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Member Sync
Plugin URI: https://github.com/christianwach/civicrm-wp-member-sync
Description: Synchronize CiviCRM memberships with WordPress user roles or capabilities.
Author: Christian Wach
Version: 0.3.7
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



// Define capability prefix.
if ( ! defined( 'CIVI_WP_MEMBER_SYNC_CAP_PREFIX' ) ) {
	define( 'CIVI_WP_MEMBER_SYNC_CAP_PREFIX', 'civimember_' );
}

// Define plugin version - bumping this will also refresh CSS and JS.
define( 'CIVI_WP_MEMBER_SYNC_VERSION', '0.3.7' );

// Store reference to this file.
define( 'CIVI_WP_MEMBER_SYNC_PLUGIN_FILE', __FILE__ );

// Store URL to this plugin's directory.
if ( ! defined( 'CIVI_WP_MEMBER_SYNC_PLUGIN_URL' ) ) {
	define( 'CIVI_WP_MEMBER_SYNC_PLUGIN_URL', plugin_dir_url( CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ) );
}

// Store PATH to this plugin's directory.
if ( ! defined( 'CIVI_WP_MEMBER_SYNC_PLUGIN_PATH' ) ) {
	define( 'CIVI_WP_MEMBER_SYNC_PLUGIN_PATH', plugin_dir_path( CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ) );
}

// Migrate flag for developers (see civi-wp-ms-migrate.php)
//define( 'CIVI_WP_MEMBER_SYNC_MIGRATE', true );



/**
 * CiviCRM WordPress Member Sync class.
 *
 * A class for encapsulating plugin functionality.
 *
 * @since 0.1
 */
class Civi_WP_Member_Sync {

	/**
	 * WordPress Users utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $user The users utilities object.
	 */
	public $users;

	/**
	 * WordPress Scheduled Events utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $schedule The scheduled events utilities object.
	 */
	public $schedule;

	/**
	 * Admin utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $admin The admin utilities object.
	 */
	public $admin;

	/**
	 * CiviCRM Membership utilities object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $members The membership utilities object.
	 */
	public $members;



	/**
	 * Initialise this object.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Use translation.
		add_action( 'plugins_loaded', array( $this, 'translation' ) );

		// Initialise plugin when CiviCRM initialises.
		add_action( 'civicrm_instance_loaded', array( $this, 'initialise' ) );

		// Load our Users utility class.
		require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'civi-wp-ms-users.php' );

		// Instantiate.
		$this->users = new Civi_WP_Member_Sync_Users( $this );

		// Load our Schedule utility class.
		require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'civi-wp-ms-schedule.php' );

		// Instantiate.
		$this->schedule = new Civi_WP_Member_Sync_Schedule( $this );

		// Load our Admin utility class.
		require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'civi-wp-ms-admin.php' );

		// Instantiate.
		$this->admin = new Civi_WP_Member_Sync_Admin( $this );

		// Load our CiviCRM utility class.
		require( CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'civi-wp-ms-members.php' );

		// Instantiate.
		$this->members = new Civi_WP_Member_Sync_Members( $this );

	}



	//##########################################################################



	/**
	 * Perform plugin activation tasks.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// Setup plugin admin.
		$this->admin->activate();

	}



	/**
	 * Perform plugin deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

		// Remove scheduled hook.
		$this->schedule->unschedule();

	}



	/**
	 * Initialise objects when CiviCRM initialises.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Initialise Admin object.
		$this->admin->initialise();

		// Initialise users object.
		$this->users->initialise();

		// Initialise schedule object.
		$this->schedule->initialise();

		// Initialise CiviCRM object.
		$this->members->initialise();

		/**
		 * Broadcast that we're up and running.
		 *
		 * @since 0.1
		 */
		do_action( 'civi_wp_member_sync_initialised' );

	}



	//##########################################################################



	/**
	 * Load translations.
	 *
	 * @since 0.1
	 */
	public function translation() {

		// Load translations.
		load_plugin_textdomain(

			// Unique name.
			'civicrm-wp-member-sync',

			// Deprecated argument.
			false,

			// Relative path to directory containing translation files.
			dirname( plugin_basename( CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ) ) . '/languages/'

		);

	}



} // Class ends.



// Declare as global for external reference.
global $civi_wp_member_sync;

// Init plugin.
$civi_wp_member_sync = new Civi_WP_Member_Sync;

// Plugin activation.
register_activation_hook( __FILE__, array( $civi_wp_member_sync, 'activate' ) );

// Plugin deactivation.
register_deactivation_hook( __FILE__, array( $civi_wp_member_sync, 'deactivate' ) );

// Uninstall uses the 'uninstall.php' method.
// See: http://codex.wordpress.org/Function_Reference/register_uninstall_hook



/**
 * Utility for retrieving a reference to this plugin.
 *
 * @since 0.2.7
 *
 * @return object $civi_wp_member_sync The plugin reference.
 */
function civicrm_wpms() {

	// Return reference.
	global $civi_wp_member_sync;
	return $civi_wp_member_sync;

}



/**
 * Add courtesy links on WordPress plugin listings pages.
 *
 * @since 0.1
 *
 * @param array $links The existing list of plugin links.
 * @param str $file The name of the plugin file.
 * @return array $links The amended list of plugin links.
 */
function civi_wp_member_sync_plugin_add_settings_link( $links, $file ) {

	// Maybe add settings link.
	if ( $file == plugin_basename( dirname( __FILE__ ) . '/civicrm-wp-member-sync.php' ) ) {

		// Is this Network Admin? Also check sub-site listings (since WordPress 4.4) and show for network admins.
		if (
			is_network_admin() OR
			( is_super_admin() AND civicrm_wpms()->admin->is_network_activated() )
		) {
			$link = add_query_arg( array( 'page' => 'civi_wp_member_sync_parent' ), network_admin_url( 'settings.php' ) );
		} else {
			$link = add_query_arg( array( 'page' => 'civi_wp_member_sync_parent' ), admin_url( 'options-general.php' ) );
		}

		// Add settings link.
		$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-wp-member-sync' ) . '</a>';

	}

	// --<
	return $links;

}

add_filter( 'network_admin_plugin_action_links', 'civi_wp_member_sync_plugin_add_settings_link', 10, 2 );
add_filter( 'plugin_action_links', 'civi_wp_member_sync_plugin_add_settings_link', 10, 2 );



