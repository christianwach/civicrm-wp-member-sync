<?php /*
--------------------------------------------------------------------------------
Civi_WP_Member_Sync_Schedule Class
--------------------------------------------------------------------------------
*/



/**
 * Class for encapsulating WordPress scheduling functionality.
 *
 * @since 0.1
 */
class Civi_WP_Member_Sync_Schedule {

	/**
	 * Properties
	 */

	// parent object
	public $parent_obj;



	/**
	 * Initialise this object.
	 *
	 * @since 0.1
	 *
	 * @param object $parent_obj The parent object
	 */
	public function __construct( $parent_obj ) {

		// store reference to parent
		$this->parent_obj = $parent_obj;

	}



	/**
	 * Initialise when CiviCRM initialises.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function initialise() {

		// get our schedule sync setting
		$schedule = absint( $this->parent_obj->admin->setting_get( 'schedule' ) );

		// add schedule if set
		if ( $schedule === 1 ) {

			// get our interval setting
			$interval = $this->parent_obj->admin->setting_get( 'interval' );

			// sanity check
			if ( ! empty( $interval ) ) {

				// set schedule
				$this->schedule( $interval );

			}

			// add schedule callback action
			add_action( 'civi_wp_member_sync_refresh', array( $this, 'schedule_callback' ) );

		}

	}



	//##########################################################################



	/**
	 * Set up our scheduled event.
	 *
	 * @since 0.1
	 *
	 * @param string $interval One of the WordPress-defined intervals
	 * @return void
	 */
	public function schedule( $interval ) {

		// if not already present...
		if ( ! wp_next_scheduled( 'civi_wp_member_sync_refresh' ) ) {

			// add schedule
			wp_schedule_event(
				time(), // time when event fires
				$interval, // event interval
				'civi_wp_member_sync_refresh' // hook to fire
			);

		}

	}



	/**
	 * Clear our scheduled event.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function unschedule() {

		// get next scheduled event
		$timestamp = wp_next_scheduled( 'civi_wp_member_sync_refresh' );

		// unschedule it if we get one
		if ( $timestamp !== false ) {
			wp_unschedule_event( $timestamp, 'civi_wp_member_sync_refresh' );
		}

		// it's not clear whether wp_unschedule_event() clears everything,
		// so let's remove existing scheduled hook as well
		wp_clear_scheduled_hook( 'civi_wp_member_sync_refresh' );

	}



	/**
	 * Called when a scheduled event is triggered.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function schedule_callback() {

		// call sync all method
		$this->parent_obj->members->sync_all();

	}



	//##########################################################################



	/**
	 * Get schedule intervals.
	 *
	 * @since 0.1
	 *
	 * @return array $intervals Array of schedule interval arrays, keyed by interval slug
	 */
	public function intervals_get() {

		// just a wrapper...
		return wp_get_schedules();

	}



	//##########################################################################



	/**
	 * Clear our legacy_scheduled event.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function legacy_unschedule() {

		// get next scheduled event
		$timestamp = wp_next_scheduled( 'civi_member_sync_refresh' );

		// unschedule it if we get one
		if ( $timestamp !== false ) {
			wp_unschedule_event( $timestamp, 'civi_member_sync_refresh' );
		}

		// remove existing scheduled hook as well
		wp_clear_scheduled_hook( 'civi_member_sync_refresh' );

	}



} // class ends



