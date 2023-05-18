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
	 * Plugin (calling) object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

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

		// Initialise early.
		add_action( 'civi_wp_member_sync_initialised', [ $this, 'initialise' ], 5 );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Get our schedule sync setting.
		$schedule = absint( $this->plugin->admin->setting_get( 'schedule' ) );

		// Add schedule if set.
		if ( 1 === $schedule ) {

			// Get our interval setting.
			$interval = $this->plugin->admin->setting_get( 'interval' );

			// Set schedule to setting.
			if ( ! empty( $interval ) ) {
				$this->schedule( $interval );
			}

			// Add schedule callback action.
			add_action( 'civi_wp_member_sync_refresh', [ $this, 'schedule_callback' ] );

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Set up our scheduled event.
	 *
	 * @since 0.1
	 *
	 * @param string $interval One of the WordPress-defined intervals.
	 */
	public function schedule( $interval ) {

		// If not already present.
		if ( ! wp_next_scheduled( 'civi_wp_member_sync_refresh' ) ) {

			// Add schedule.
			wp_schedule_event(
				time(), // Time when event fires.
				$interval, // Event interval.
				'civi_wp_member_sync_refresh' // Hook to fire.
			);

		}

	}

	/**
	 * Clear our scheduled event.
	 *
	 * @since 0.1
	 */
	public function unschedule() {

		// Get next scheduled event.
		$timestamp = wp_next_scheduled( 'civi_wp_member_sync_refresh' );

		// Unschedule it if we get one.
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, 'civi_wp_member_sync_refresh' );
		}

		// It's not clear whether wp_unschedule_event() clears everything,
		// so let's remove existing scheduled hook as well.
		wp_clear_scheduled_hook( 'civi_wp_member_sync_refresh' );

	}

	/**
	 * Called when a scheduled event is triggered.
	 *
	 * @since 0.1
	 */
	public function schedule_callback() {

		// Call sync all method.
		$this->plugin->members->sync_all_wp_user_memberships();

	}

	// -------------------------------------------------------------------------

	/**
	 * Get schedule intervals.
	 *
	 * @since 0.1
	 *
	 * @return array $intervals Array of schedule interval arrays, keyed by interval slug.
	 */
	public function intervals_get() {

		// Just a wrapper.
		return wp_get_schedules();

	}

	// -------------------------------------------------------------------------

	/**
	 * Clear our legacy_scheduled event.
	 *
	 * @since 0.1
	 */
	public function legacy_unschedule() {

		// Get next scheduled event.
		$timestamp = wp_next_scheduled( 'civi_member_sync_refresh' );

		// Unschedule it if we get one.
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, 'civi_member_sync_refresh' );
		}

		// Remove existing scheduled hook as well.
		wp_clear_scheduled_hook( 'civi_member_sync_refresh' );

	}

}
