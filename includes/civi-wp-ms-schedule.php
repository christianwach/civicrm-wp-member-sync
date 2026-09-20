<?php
/**
 * Schedule class.
 *
 * Handles WordPress scheduling functionality.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Schedule class.
 *
 * Class for encapsulating WordPress scheduling functionality.
 *
 * @since 0.1
 */
class Civi_WP_Member_Sync_Schedule {

	/**
	 * Plugin object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Civi_WP_Member_Sync
	 */
	public $plugin;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param Civi_WP_Member_Sync $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Initialise early.
		add_action( 'civi_wp_member_sync_initialised', [ $this, 'initialise' ], 5 );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Remove scheduled hook.
		$this->unschedule();

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Sets up a scheduled event.
	 *
	 * Leave here for now, just in case there is a call to create the schedule.
	 *
	 * @since 0.1
	 *
	 * @param string $interval One of the WordPress-defined intervals.
	 */
	public function schedule( $interval ) {}

	/**
	 * Clears the scheduled event.
	 *
	 * @since 0.1
	 */
	public function unschedule() {

		// Bail if it's not necessary to clear the existing scheduled hook.
		$setting = $this->plugin->admin->setting_get( 'schedule', '' );
		if ( 'deleted' === $setting ) {
			return;
		}

		// Clear the existing scheduled hook - also clears all future scheduled events.
		wp_clear_scheduled_hook( 'civi_wp_member_sync_refresh' );

		// Set the "schedule" setting to a value that flags that it is no longer used.
		$this->plugin->admin->setting_set( 'schedule', 'deleted' );
		$this->plugin->admin->settings_save();

	}

	/**
	 * Performs tasks when a scheduled event is triggered.
	 *
	 * Leave here in case there is an existing schedule that hasn't yet been unscheduled.
	 *
	 * @since 0.1
	 */
	public function schedule_callback() {}

}
