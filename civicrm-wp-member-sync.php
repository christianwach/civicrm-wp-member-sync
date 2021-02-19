<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Member Sync
Plugin URI: https://github.com/christianwach/civicrm-wp-member-sync
Description: Synchronize CiviCRM memberships with WordPress user roles or capabilities.
Author: Christian Wach
Version: 0.5
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
define( 'CIVI_WP_MEMBER_SYNC_VERSION', '0.5' );

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
	 * CiviCRM "Groups" compatibility object.
	 *
	 * @since 0.3.9
	 * @access public
	 * @var object $groups The "Groups" compatibility object.
	 */
	public $groups;

	/**
	 * CiviCRM BuddyPress compatibility object.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var object $buddypress The BuddyPress compatibility object.
	 */
	public $buddypress;



	/**
	 * Initialise this object.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Use translation.
		add_action( 'plugins_loaded', [ $this, 'translation' ] );

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Initialise plugin when CiviCRM initialises during "plugins_loaded".
		add_action( 'civicrm_instance_loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Include files.
	 *
	 * @since 0.3.7
	 */
	public function include_files() {

		// Load our Users utility class.
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-users.php';

		// Load our Schedule utility class.
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-schedule.php';

		// Load our Admin utility class.
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-admin.php';

		// Load our CiviCRM utility class.
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-members.php';

		// Load our "Groups" compatibility class.
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-groups.php';

		// Load our BuddyPress compatibility class.
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-buddypress.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.3.7
	 */
	public function setup_objects() {

		// Instantiate Users object.
		$this->users = new Civi_WP_Member_Sync_Users( $this );

		// Instantiate Schedule object.
		$this->schedule = new Civi_WP_Member_Sync_Schedule( $this );

		// Instantiate Admin object.
		$this->admin = new Civi_WP_Member_Sync_Admin( $this );

		// Instantiate CiviCRM Members object.
		$this->members = new Civi_WP_Member_Sync_Members( $this );

		// Instantiate "Groups" compatibility object.
		$this->groups = new Civi_WP_Member_Sync_Groups( $this );

		// Instantiate BuddyPress compatibility object.
		$this->buddypress = new Civi_WP_Member_Sync_BuddyPress( $this );

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

		/**
		 * Broadcast that we're up and running.
		 *
		 * This action is used internally in order to trigger initialisation.
		 * There is a specific order to the callbacks:
		 *
		 * Civi_WP_Member_Sync_Admin - Priority 1
		 * Civi_WP_Member_Sync_Users - Priority 3
		 * Civi_WP_Member_Sync_Schedule - Priority 5
		 * Civi_WP_Member_Sync_Members - Priority 7
		 * Civi_WP_Member_Sync_Groups - Priority 10
		 * Civi_WP_Member_Sync_BuddyPress - Priority 20
		 *
		 * @since 0.1
		 * @since 0.3.9 All CWMS classes hook into this to trigger initialisation.
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
$civi_wp_member_sync = new Civi_WP_Member_Sync();

// Plugin activation.
register_activation_hook( __FILE__, [ $civi_wp_member_sync, 'activate' ] );

// Plugin deactivation.
register_deactivation_hook( __FILE__, [ $civi_wp_member_sync, 'deactivate' ] );

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
			$link = add_query_arg( [ 'page' => 'civi_wp_member_sync_parent' ], network_admin_url( 'settings.php' ) );
		} else {
			$link = add_query_arg( [ 'page' => 'civi_wp_member_sync_parent' ], admin_url( 'admin.php' ) );
		}

		// Add settings link.
		$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-wp-member-sync' ) . '</a>';

	}

	// --<
	return $links;

}

add_filter( 'network_admin_plugin_action_links', 'civi_wp_member_sync_plugin_add_settings_link', 10, 2 );
add_filter( 'plugin_action_links', 'civi_wp_member_sync_plugin_add_settings_link', 10, 2 );



