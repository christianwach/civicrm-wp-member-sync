<?php
/**
 * CiviCRM Member Sync
 *
 * Plugin Name:       CiviCRM Member Sync
 * Description:       Synchronize CiviCRM Memberships with WordPress User Roles or Capabilities.
 * Plugin URI:        https://github.com/christianwach/civicrm-wp-member-sync
 * GitHub Plugin URI: https://github.com/christianwach/civicrm-wp-member-sync
 * Version:           0.7.1a
 * Author:            Christian Wach
 * Author URI:        https://haystack.co.uk
 * Requires at least: 4.9
 * Requires PHP:      7.4
 * Text Domain:       civicrm-wp-member-sync
 * Domain Path:       /languages
 *
 * Thanks to:
 *
 * Jag Kandasamy <https://github.com/jeevajoy> for:
 * "Wordpress CiviMember Role Sync Plugin" <https://github.com/jeevajoy/Wordpress-CiviCRM-Member-Role-Sync>
 *
 * Tadpole Collective <https://tadpole.cc> for their fork:
 * "Tadpole CiviMember Role Synchronize" <https://github.com/tadpolecc/civi_member_sync>
 *
 * @package Civi_WP_Member_Sync
 * @link    https://github.com/christianwach/civicrm-wp-member-sync
 * @license GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Define Capability prefix.
if ( ! defined( 'CIVI_WP_MEMBER_SYNC_CAP_PREFIX' ) ) {
	define( 'CIVI_WP_MEMBER_SYNC_CAP_PREFIX', 'civimember_' );
}

// Define plugin version - bumping this will also refresh CSS and JS.
define( 'CIVI_WP_MEMBER_SYNC_VERSION', '0.7.1a' );

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

/*
 * Set production debug flag.
 *
 * Setting this to true will write to the log even when WP_DEBUG is off, for example in
 * production environments. This setting is ignored when WP_DEBUG is on.
 */
if ( ! defined( 'CIVI_WP_MEMBER_SYNC_DEBUG' ) ) {
	define( 'CIVI_WP_MEMBER_SYNC_DEBUG', true );
}

/**
 * Plugin class.
 *
 * A class for encapsulating plugin functionality.
 *
 * @since 0.1
 */
class Civi_WP_Member_Sync {

	/**
	 * WordPress Users object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Civi_WP_Member_Sync_Users
	 */
	public $users;

	/**
	 * WordPress Scheduled Events object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Civi_WP_Member_Sync_Schedule
	 */
	public $schedule;

	/**
	 * Admin object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Civi_WP_Member_Sync_Admin
	 */
	public $admin;

	/**
	 * CiviCRM Membership object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Civi_WP_Member_Sync_Members
	 */
	public $members;

	/**
	 * "Groups" compatibility object.
	 *
	 * @since 0.3.9
	 * @access public
	 * @var Civi_WP_Member_Sync_Groups
	 */
	public $groups;

	/**
	 * BuddyPress compatibility object.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var Civi_WP_Member_Sync_BuddyPress
	 */
	public $buddypress;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Maybe include WP-CLI command.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/wp-cli/wp-cli-cvwpms.php';
		}

		// Bootstrap plugin.
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

	}

	/**
	 * Includes plugin files.
	 *
	 * @since 0.3.7
	 */
	private function include_files() {

		// Load our class files.
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-users.php';
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-schedule.php';
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-admin.php';
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-members.php';
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-groups.php';
		require CIVI_WP_MEMBER_SYNC_PLUGIN_PATH . 'includes/civi-wp-ms-buddypress.php';

	}

	/**
	 * Sets up this plugin's objects.
	 *
	 * @since 0.3.7
	 */
	private function setup_objects() {

		// Instantiate our objects.
		$this->users      = new Civi_WP_Member_Sync_Users( $this );
		$this->schedule   = new Civi_WP_Member_Sync_Schedule( $this );
		$this->admin      = new Civi_WP_Member_Sync_Admin( $this );
		$this->members    = new Civi_WP_Member_Sync_Members( $this );
		$this->groups     = new Civi_WP_Member_Sync_Groups( $this );
		$this->buddypress = new Civi_WP_Member_Sync_BuddyPress( $this );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 0.6.2
	 */
	private function register_hooks() {

		// Initialise plugin when CiviCRM initialises during "plugins_loaded".
		add_action( 'civicrm_instance_loaded', [ $this, 'initialise' ] );

		// Use translation.
		add_action( 'init', [ $this, 'translation' ] );

		// Add settings link.
		add_filter( 'network_admin_plugin_action_links', [ $this, 'plugin_action_links' ], 10, 2 );
		add_filter( 'plugin_action_links', [ $this, 'plugin_action_links' ], 10, 2 );

	}

	/**
	 * Initialises plugin objects when CiviCRM initialises.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		/**
		 * Bootstraps this plugin.
		 *
		 * This action is used internally in order to trigger initialisation.
		 * There is a specific order to the callbacks:
		 *
		 * * Civi_WP_Member_Sync_Admin::initialise() - Priority 1
		 * * Civi_WP_Member_Sync_Users::initialise() - Priority 3
		 * * Civi_WP_Member_Sync_Schedule::initialise() - Priority 5
		 * * Civi_WP_Member_Sync_Members::initialise() - Priority 7
		 * * Civi_WP_Member_Sync_Groups::initialise() - Priority 10
		 * * Civi_WP_Member_Sync_BuddyPress::initialise() - Priority 20
		 *
		 * @since 0.1
		 * @since 0.3.9 All CWMS classes hook into this to trigger initialisation.
		 */
		do_action( 'civi_wp_member_sync_initialised' );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Performs plugin activation tasks.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// Setup plugin admin.
		$this->admin->activate();

	}

	/**
	 * Performs plugin deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

		// Remove scheduled hook.
		$this->schedule->unschedule();

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Loads plugin translations.
	 *
	 * @since 0.1
	 */
	public function translation() {

		// Load translations.
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'civicrm-wp-member-sync', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( CIVI_WP_MEMBER_SYNC_PLUGIN_FILE ) ) . '/languages/' // Relative path to files.
		);

	}

	/**
	 * Utility to add link to settings page.
	 *
	 * @since 0.6.2
	 *
	 * @param array  $links The existing links array.
	 * @param string $file The name of the plugin file.
	 * @return array $links The modified links array.
	 */
	public function plugin_action_links( $links, $file ) {

		// Maybe add settings link.
		if ( plugin_basename( __DIR__ . '/civicrm-wp-member-sync.php' ) !== $file ) {
			return $links;
		}

		// Is this Network Admin? Also check sub-site listings (since WordPress 4.4) and show for network admins.
		if ( is_network_admin() || ( is_super_admin() && civicrm_wpms()->admin->is_network_activated() ) ) {
			$link = add_query_arg( [ 'page' => 'civi_wp_member_sync_parent' ], network_admin_url( 'settings.php' ) );
		} else {
			$link = add_query_arg( [ 'page' => 'civi_wp_member_sync_parent' ], admin_url( 'admin.php' ) );
		}

		// Add settings link.
		$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'civicrm-wp-member-sync' ) . '</a>';

		// Add Paypal link.
		$paypal  = 'https://www.paypal.me/interactivist';
		$links[] = '<a href="' . esc_url( $paypal ) . '" target="_blank">' . esc_html__( 'Donate!', 'civicrm-wp-member-sync' ) . '</a>';

		// --<
		return $links;

	}

	/**
	 * Writes to the error log.
	 *
	 * @since 0.6.2
	 *
	 * @param array $data The data to write to the log file.
	 */
	public function log_error( $data = [] ) {

		// Skip if not debugging in production, but allow if WP_DEBUG is on.
		$wp_debugging = defined( 'WP_DEBUG' ) && WP_DEBUG;
		if ( CIVI_WP_MEMBER_SYNC_DEBUG === false && ! $wp_debugging ) {
			return;
		}

		// Skip if empty.
		if ( empty( $data ) ) {
			return;
		}

		// Format data.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$error = print_r( $data, true );

		// Write to log file.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $error );

	}

}

/**
 * Utility for retrieving a reference to this plugin.
 *
 * @since 0.2.7
 *
 * @return Civi_WP_Member_Sync $civi_wp_member_sync The plugin reference.
 */
function civicrm_wpms() {

	// Maybe bootstrap plugin.
	global $civi_wp_member_sync;
	if ( ! isset( $civi_wp_member_sync ) ) {
		$civi_wp_member_sync = new Civi_WP_Member_Sync();
	}

	// --<
	return $civi_wp_member_sync;

}

// Init plugin.
civicrm_wpms();

// Plugin activation.
register_activation_hook( __FILE__, [ civicrm_wpms(), 'activate' ] );

// Plugin deactivation.
register_deactivation_hook( __FILE__, [ civicrm_wpms(), 'deactivate' ] );

/*
 * Uninstall uses the 'uninstall.php' method.
 * @see https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */
