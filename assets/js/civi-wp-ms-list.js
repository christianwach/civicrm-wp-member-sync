/**
 * List Rules page Javascript.
 *
 * Implements sync functionality on the plugin's List Rules page.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.4.2
 */

// Defaults.
var cwms_method = 'roles',
	cwms_dialog_text = '',
	cwms_dialog_text_all = '';

// Test for our localisation object.
if ( 'undefined' !== typeof CiviCRM_WP_Member_Sync_List ) {

	// Override vars.
	cwms_method = CiviCRM_WP_Member_Sync_List.method;
	cwms_dialog_text = CiviCRM_WP_Member_Sync_List.dialog_text;
	cwms_dialog_text_all = CiviCRM_WP_Member_Sync_List.dialog_text_all;

}

/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.4.2
 *
 * @param {Object} $ The jQuery object.
 */
jQuery(document).ready( function($) {

	/**
	 * Trigger an "Are you sure?" dialog on "Delete" links.
	 *
	 * @since 0.4.2
	 *
	 * @param {Object} event The click event object.
	 */
	$('.submitdelete').on( 'click', function(event) {

		// Open dialog.
		var dialog = confirm( cwms_dialog_text );

		// How did we do?
		if ( dialog == true ) {

			// Carry on.

		} else {

			// Don't!
			event.stopImmediatePropagation(event);
			event.stopPropagation(event);
			event.preventDefault(event);
			return false;

		}

	});

	/**
	 * Trigger an "Are you sure?" dialog on "Clear Association Rules" button.
	 *
	 * @since 0.4.2
	 *
	 * @param {Object} event The click event object.
	 */
	$('#civi_wp_member_sync_clear_submit').on( 'click', function(event) {

		// Open dialog.
		var dialog = confirm( cwms_dialog_text_all );

		// How did we do?
		if ( dialog == true ) {

			// Carry on.

		} else {

			// Don't!
			event.stopImmediatePropagation(event);
			event.stopPropagation(event);
			event.preventDefault(event);
			return false;

		}

	});

});
