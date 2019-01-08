/**
 * CiviCRM WordPress Member Sync Rules Javascript.
 *
 * Implements sync functionality on the plugin's Add Rule and Edit Rule admin pages.
 *
 * @package Civi_WP_Member_Sync
 */

// Defaults.
var cwms_method = 'roles',
	cwms_mode = 'add';

// Test for our localisation object.
if ( 'undefined' !== typeof CiviCRM_WP_Member_Sync_Rules ) {

	// Override var.
	cwms_method = CiviCRM_WP_Member_Sync_Rules.method;
	cwms_mode = CiviCRM_WP_Member_Sync_Rules.mode;

}

/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.1
 *
 * @param {Object} $ The jQuery object.
 */
jQuery(document).ready( function($) {

	/**
	 * Toggle matching checkbox in expire array.
	 *
	 * @since 0.1
	 *
	 * @param {Object} e The click event object.
	 */
	$('.required-current').click( function(e) {

		var current_on,
			current_id,
			expire_id;

		// Get checked.
		current_on = $(this).prop( 'checked' );

		// Get class.
		current_class = $(this).prop( 'class' );

		// Expire ID.
		expire_last_class = current_class.split(' ')[1];
		expire_class = expire_last_class.split('-')[1];
		expire_target = '.expire-' + expire_class;

		// Check required elements.
		$(expire_target).prop( 'checked', !current_on );

	});

	/**
	 * Toggle matching checkbox in current array.
	 *
	 * @since 0.1
	 *
	 * @param {Object} e The click event object.
	 */
	$('.required-expire').click( function(e) {

		var expire_on,
			expire_id,
			current_id;

		// Get checked.
		expire_on = $(this).prop( 'checked' );

		// Get class.
		expire_class = $(this).prop( 'class' );

		// current ID
		current_last_class = expire_class.split(' ')[1];
		current_class = current_last_class.split('-')[1];
		current_target = '.current-' + current_class;

		// Check required elements.
		$(current_target).prop( 'checked', !expire_on );

	});

	/**
	 * Basic error-checking on form submission.
	 *
	 * @since 0.1
	 *
	 * @param {Object} e The click event object.
	 */
	$(':submit').click( function(e) {

		// Init vars.
		var passed = true,
			current_checked = false,
			expire_checked = false;

		// Only check membership type if in add mode.
		if ( cwms_mode == 'add' ) {

			// Check required role elements.
			$('.required-type').each( function() {

				// If it's empty.
				if ( !$(this).attr( 'value' ) ) {

					// Colour label red.
					$(this).parent().prev().children().addClass( 'req' );

					// Set flag.
					passed = false;

				} else {

					// Colour label black.
					$(this).parent().prev().children().removeClass( 'req' );

				}

			});

		}

		// Only check roles if that's our sync method.
		if ( cwms_method == 'roles' ) {

			// Check required role elements.
			$('.required-role').each( function() {

				// If it's empty.
				if ( !$(this).attr( 'value' ) ) {

					// Colour label red.
					$(this).parent().prev().children().addClass( 'req' );

					// Set flag.
					passed = false;

				} else {

					// Colour label black.
					$(this).parent().prev().children().removeClass( 'req' );

				}

			});

		}

		// Check current checkboxes.
		$('.required-current').each( function() {

			// If checked.
			if ( $(this).prop( 'checked' ) ) {
				current_checked = true;
			}

		});

		// Do we have a checked box for current?
		if ( !current_checked ) {
			$('label.current_label').addClass( 'req' );
		} else {
			$('label.current_label').removeClass( 'req' );
		}

		// Check expire checkboxes.
		$('.required-expire').each( function() {

			// If checked.
			if ( $(this).prop( 'checked' ) ) {
				expire_checked = true;
			}

		});

		// Do we have a checked box for expire?
		if ( !expire_checked ) {
			$('label.expire_label').addClass( 'req' );
		} else {
			$('label.expire_label').removeClass( 'req' );
		}

		// Prevent form submission if any single check failed.
		if ( !passed || !current_checked || !expire_checked ) {
			e.preventDefault();
		}

	});

});
