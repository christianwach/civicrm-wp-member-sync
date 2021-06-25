/**
 * CiviCRM Member Sync Manual Sync Javascript.
 *
 * Implements sync functionality on the plugin's Manual Sync admin pages.
 *
 * @package Civi_WP_Member_Sync
 */

/**
 * Create CiviCRM Member Sync Manual Sync object.
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

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.8
		 */
		this.init = function() {

			// Init localisation.
			me.init_localisation();

			// Init settings.
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

		// Init localisation array
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
		 * @param {String} The identifier for the desired localisation string.
		 * @return {String} The localised string.
		 */
		this.get_localisation = function( identifier ) {
			return me.localisation[identifier];
		};

		// Init settings array.
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
		 * @param {String} The identifier for the desired setting.
		 * @return The value of the setting.
		 */
		this.get_setting = function( identifier ) {
			return me.settings[identifier];
		};

		/**
		 * Getter for retrieving the status of the "Create Users" checkbox.
		 *
		 * @since 0.2.8
		 *
		 * @return {String} The value of the checkbox ('y' or 'n').
		 */
		this.get_create_users = function() {

			// Get checked value.
			var checked = $('#civi_wp_member_sync_manual_sync_create').prop( 'checked' );

			// Well?
			if ( checked ) {
				return 'y';
			} else {
				return 'n';
			}

		};

		/**
		 * Getter for retrieving the status of the "Dry Run" checkbox.
		 *
		 * @since 0.5
		 *
		 * @return {String} The value of the checkbox ('y' or 'n').
		 */
		this.get_dry_run = function() {

			// Get checked value.
			var checked = $('#civi_wp_member_sync_manual_sync_dry_run').prop( 'checked' );

			// Well?
			if ( checked ) {
				return 'y';
			} else {
				return 'n';
			}

		};

		/**
		 * Getter for retrieving the status of the "From" field.
		 *
		 * @since 0.5.1
		 *
		 * @return {Integer} The value of the "From" field.
		 */
		this.get_from = function() {

			// Get value.
			var value = $('#civi_wp_member_sync_manual_sync_from').val();

			// Well?
			if ( value ) {
				return parseInt( value );
			} else {
				return 0;
			}

		};

		/**
		 * Getter for retrieving the status of the "To" field.
		 *
		 * @since 0.5.1
		 *
		 * @return {Integer} The value of the "To" field.
		 */
		this.get_to = function() {

			// Get value.
			var value = $('#civi_wp_member_sync_manual_sync_to').val();

			// Well?
			if ( value ) {
				return parseInt( value );
			} else {
				return 0;
			}

		};

	};

	/**
	 * Create Progress Bar Object.
	 *
	 * @since 0.2.8
	 */
	CiviCRM_WP_Member_Sync_Manual_Sync.progress_bar = new function() {

		// Prevent reference collisions.
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

			// Assign properties.
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

			// Declare vars.
			var button = $('#civi_wp_member_sync_manual_sync_submit'),
				boxes = $('#civi_wp_member_sync_manual_sync_create, #civi_wp_member_sync_manual_sync_dry_run'),
				from_to = $('#civi_wp_member_sync_manual_sync_from, #civi_wp_member_sync_manual_sync_to');

			/**
			 * Add a click event listener to start sync.
			 *
			 * @param {Object} event The event object.
			 */
			button.on( 'click', function( event ) {

				// Declare vars.
				var total, from, to;

				// Prevent form submission.
				if ( event.preventDefault ) {
					event.preventDefault();
				}

				// Get total.
				total = me.total
				from = CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_from();
				to = CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_to();
				if ( to !== 0 ) {
					total = to - from;
				}

				// Initialise progress bar.
				me.bar.progressbar({
					value: false,
					max: total
				});

				// Show Feedback area.
				$('#feedback').show();
				$('#feedback-results').show();
				$('#the-comment-list').empty();

				// Show progress bar if not already shown.
				me.bar.show();

				// Initialise progress bar label.
				me.label.html( me.label_init.replace( '{{total}}', total ) );

				// Send.
				me.send();

			});

			/**
			 * Listen for clicks on the checkboxes.
			 *
			 * @since 0.5
			 *
			 * @param {Object} event The event object.
			 */
			boxes.on( 'click', function( event ) {

				// Hide the progress bar.
				me.bar.hide();

				// Clear the table.
				$('#feedback-results').hide();
				$('#the-comment-list').empty();

			});

			/**
			 * Listen for clicks on the number fields.
			 *
			 * @since 0.5.1
			 *
			 * @param {Object} event The event object.
			 */
			from_to.on( 'click', function( event ) {

				// Hide the progress bar.
				me.bar.hide();

				// Clear the table.
				$('#feedback-results').hide();
				$('#the-comment-list').empty();

			});

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.2.8
		 *
		 * @param {Array} data The data received from the server.
		 */
		this.update = function( data ) {

			// Declare vars.
			var val, batch_count;

			// Are we still in progress?
			if ( data.finished == 'false' ) {

				// Get current value of progress bar.
				val = me.bar.progressbar( 'value' ) || 0;

				// Update progress bar label.
				me.label.html(
					me.label_complete.replace( '{{from}}', data.from ).replace( '{{to}}', data.to )
				);

				// Get number per batch.
				batch_count = parseInt( CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_setting( 'batch_count' ) );

				// Check if there are fewer in the received data.
				if ( batch_count > ( parseInt( data.to ) - parseInt( data.from ) ) ) {
					batch_count = val - ( parseInt( data.to ) - parseInt( data.from ) );
				}

				// Update progress bar.
				me.bar.progressbar( 'value', val + batch_count );

				// Maybe append data to Feedback table.
				if ( data.feedback ) {
					me.feedback_append( data.feedback );
				}

				// Trigger next batch.
				me.send();

			} else {

				// Update progress bar label.
				me.label.html( me.label_done );

				/*
				// Hide the progress bar.
				setTimeout(function () {
					me.bar.hide();
				}, 2000 );
				*/

			}

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.2.8
		 */
		this.send = function() {

			// Declare vars.
			var url, data;

			// URL to post to.
			url = CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_setting( 'ajax_url' ),

			// Data to send.
			data = {

				// Token received by WordPress.
				action: 'sync_memberships',

				// Send "Create Users" flag.
				civi_wp_member_sync_manual_sync_create: CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_create_users(),

				// Send "Dry Run" flag.
				civi_wp_member_sync_manual_sync_dry_run: CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_dry_run(),

				// Send "From" and "To" values.
				civi_wp_member_sync_manual_sync_from: CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_from(),
				civi_wp_member_sync_manual_sync_to: CiviCRM_WP_Member_Sync_Manual_Sync.settings.get_to()

			};

			// Use jQuery post.
			$.post( url, data,

				// Callback.
				function( response, textStatus ) {

					// If success.
					if ( textStatus == 'success' ) {

						// Update progress bar.
						me.update( response );

					} else {

						// Show error.
						if ( console.log ) {
							console.log( textStatus );
						}

					}

				},

				// Expected format.
				'json'

			);

		};

		/**
		 * Append the returned markup to the Feedback Table.
		 *
		 * @since 0.5
		 *
		 * @param {Array} feedback The feedback data received from the server.
		 */
		this.feedback_append = function( feedback ) {

			// Add rows to the feedback table.
			$(feedback).appendTo( '#the-comment-list' );

		};

	};

	// Init settings.
	CiviCRM_WP_Member_Sync_Manual_Sync.settings.init();

	// Init Progress Bar.
	CiviCRM_WP_Member_Sync_Manual_Sync.progress_bar.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2.8
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now.
	CiviCRM_WP_Member_Sync_Manual_Sync.settings.dom_ready();

	// The DOM is loaded now.
	CiviCRM_WP_Member_Sync_Manual_Sync.progress_bar.dom_ready();

}); // end document.ready()



