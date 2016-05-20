/**
 * CiviCRM WordPress Member Sync Manual Sync Javascript.
 *
 * Implements sync functionality on the plugin's Manual Sync admin pages.
 *
 * @package Civi_WP_Member_Sync
 */

/**
 * Create CiviCRM WordPress Member Sync Manual Sync object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.2.8
 */
var CiviCRM_WP_Member_Sync_Manual_Sync = CiviCRM_WP_Member_Sync_Manual_Sync || {};



/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.2.8
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Create Settings Object.
	 *
	 * @since 0.2.8
	 */
	CiviCRM_WP_Member_Sync_Manual_Sync.settings = new function() {

		// prevent reference collisions
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.8
		 */
		this.init = function() {

			// init localisation
			me.init_localisation();

			// init settings
			me.init_settings();

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.8
		 */
		this.dom_ready = function() {

		};

		// init localisation array
		me.localisation = [];

		/**
		 * Init localisation from settings object.
		 *
		 * @since 0.2.8
		 */
		this.init_localisation = function() {
			if ( 'undefined' !== typeof CiviCRM_WP_Member_Sync_Settings ) {
				me.localisation = CiviCRM_WP_Member_Sync_Settings.localisation;
			}
		};

		/**
		 * Getter for localisation.
		 *
		 * @since 0.2.8
		 *
		 * @param {String} The identifier for the desired localisation string
		 * @return {String} The localised string
		 */
		this.get_localisation = function( identifier ) {
			return me.localisation[identifier];
		};

		// init settings array
		me.settings = [];

		/**
		 * Init settings from settings object.
		 *
		 * @since 0.2.8
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof CiviCRM_WP_Member_Sync_Settings ) {
				me.settings = CiviCRM_WP_Member_Sync_Settings.settings;
			}
		};

		/**
		 * Getter for retrieving a setting.
		 *
		 * @since 0.2.8
		 *
		 * @param {String} The identifier for the desired setting
		 * @return The value of the setting
		 */
		this.get_setting = function( identifier ) {
			return me.settings[identifier];
		};

		/**
		 * Getter for retrieving the status of the "Create Users" checkbox.
		 *
		 * @since 0.2.8
		 *
		 * @return {String} The value of the checkbox ('y' or 'n')
		 */
		this.get_create_users = function( identifier ) {

			// get checked value
			var checked = $('#civi_wp_member_sync_manual_sync_create').prop( 'checked' );

			// well?
			if ( checked ) {
				return 'y';
			} else {
				return 'n';
			}

		};

	};

	/**
	 * Create Progress Bar Object.
	 *
	 * @since 0.2.8
	 */
	CiviCRM_WP_Member_Sync_Manual_Sync.progress_bar = new function() {

		// prevent reference collisions
		var me = this;

		/**
		 * Initialise Progress Bar.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.8
		 */
		this.init = function() {

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.8
		 */
		this.dom_ready = function() {

			// set up instance
			me.setup();

			// enable listeners
			me.listeners();

		};

		/**
		 * Set up Progress Bar instance.
		 *
		 * @since 0.2.8
		 */
		this.setup = function() {

			// assign properties
			me.bar = $('#progress-bar');
			me.label = $('#progress-bar .progress-label');
			me.total = CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_setting( 'total_memberships' );
			me.label_init = CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_localisation( 'total' );
			me.label_current = CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_localisation( 'current' );
			me.label_complete = CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_localisation( 'complete' );
			me.label_done = CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_localisation( 'done' );

		};

		/**
		 * Initialise listeners.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.8
		 */
		this.listeners = function() {

			// declare vars
			var button = $('#civi_wp_member_sync_manual_sync_submit');

			/**
			 * Add a click event listener to start sync.
			 *
			 * @param {Object} event The event object
			 */
			button.on( 'click', function( event ) {

				// prevent form submission
				if ( event.preventDefault ) {
					event.preventDefault();
				}

				// initialise progress bar
				me.bar.progressbar({
					value: false,
					max: me.total
				});

				// show progress bar if not already shown
				me.bar.show();

				// initialise progress bar label
				me.label.html( me.label_init.replace( '{{total}}', me.total ) );

				// send
				me.send();

			});

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.2.8
		 *
		 * @param {Array} data The data received from the server
		 */
		this.update = function( data ) {

			// declare vars
			var val;

			// are we still in progress?
			if ( data.finished == 'false' ) {

				// get current value of progress bar
				val = me.bar.progressbar( 'value' ) || 0;

				// update progress bar label
				me.label.html(
					me.label_complete.replace( '{{from}}', data.from ).replace( '{{to}}', data.to )
				);

				// update progress bar
				me.bar.progressbar( 'value', val + 5 );

				// trigger next batch
				me.send();

			} else {

				// update progress bar label
				me.label.html( me.label_done );

				// hide the progress bar
				setTimeout(function () {
					me.bar.hide();
				}, 2000 );

			}

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.2.8
		 */
		this.send = function() {

			// use jQuery post
			$.post(

				// URL to post to
				CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_setting( 'ajax_url' ),

				{

					// token received by WordPress
					action: 'sync_memberships',

					// send "create users" flag
					civi_wp_member_sync_manual_sync_create: CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_create_users()

				},

				// callback
				function( data, textStatus ) {

					// if success
					if ( textStatus == 'success' ) {

						// update progress bar
						me.update( data );

					} else {

						// show error
						if ( console.log ) {
							console.log( textStatus );
						}

					}

				},

				// expected format
				'json'

			);

		};

	};

	// init settings
	CiviCRM_WP_Member_Sync_Manual_Sync.settings.init();

	// init Progress Bar
	CiviCRM_WP_Member_Sync_Manual_Sync.progress_bar.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2.8
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now
	CiviCRM_WP_Member_Sync_Manual_Sync.settings.dom_ready();

	// The DOM is loaded now
	CiviCRM_WP_Member_Sync_Manual_Sync.progress_bar.dom_ready();

}); // end document.ready()



