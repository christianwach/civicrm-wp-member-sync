/**
 * CiviCRM WordPress Member Sync Rules Javascript.
 *
 * Implements sync functionality on the plugin's Add Rule and Edit Rule admin pages.
 *
 * @package Civi_WP_Member_Sync
 */

// defaults
var cwms_method = 'roles',
	cwms_mode = 'add';

// test for our localisation object
if ( 'undefined' !== typeof CiviCRM_WP_Member_Sync_Rules ) {

	// override var
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
	 * @param {Object} e The click event object
	 */
	$('.required-current').click( function(e) {

		var current_on,
			current_id,
			expire_id;

		// get checked
		current_on = $(this).prop( 'checked' );

		// get class
		current_class = $(this).prop( 'class' );

		// expire ID
		expire_last_class = current_class.split(' ')[1];
		expire_class = expire_last_class.split('-')[1];
		expire_target = '.expire-' + expire_class;

		// check required elements
		$(expire_target).prop( 'checked', !current_on );

	});

	/**
	 * Toggle matching checkbox in current array.
	 *
	 * @since 0.1
	 *
	 * @param {Object} e The click event object
	 */
	$('.required-expire').click( function(e) {

		var expire_on,
			expire_id,
			current_id;

		// get checked
		expire_on = $(this).prop( 'checked' );

		// get class
		expire_class = $(this).prop( 'class' );

		// current ID
		current_last_class = expire_class.split(' ')[1];
		current_class = current_last_class.split('-')[1];
		current_target = '.current-' + current_class;

		// check required elements
		$(current_target).prop( 'checked', !expire_on );

	});

	/**
	 * Basic error-checking on form submission.
	 *
	 * @since 0.1
	 *
	 * @param {Object} e The click event object
	 */
	$(':submit').click( function(e) {

		// init vars
		var passed = true,
			current_checked = false,
			expire_checked = false;

		// only check membership type if in add mode
		if ( cwms_mode == 'add' ) {

			// check required role elements
			$('.required-type').each( function() {

				// if it's empty
				if ( !$(this).attr( 'value' ) ) {

					// colour label red
					$(this).parent().prev().children().addClass( 'req' );

					// set flag
					passed = false;

				} else {

					// colour label black
					$(this).parent().prev().children().removeClass( 'req' );

				}

			});

		}

		// only check roles if that's our sync method
		if ( cwms_method == 'roles' ) {

			// check required role elements
			$('.required-role').each( function() {

				// if it's empty
				if ( !$(this).attr( 'value' ) ) {

					// colour label red
					$(this).parent().prev().children().addClass( 'req' );

					// set flag
					passed = false;

				} else {

					// colour label black
					$(this).parent().prev().children().removeClass( 'req' );

				}

			});

		}

		// check current checkboxes
		$('.required-current').each( function() {

			// if checked
			if ( $(this).prop( 'checked' ) ) {
				current_checked = true;
			}

		});

		// do we have a checked box for current?
		if ( !current_checked ) {
			$('label.current_label').addClass( 'req' );
		} else {
			$('label.current_label').removeClass( 'req' );
		}

		// check expire checkboxes
		$('.required-expire').each( function() {

			// if checked
			if ( $(this).prop( 'checked' ) ) {
				expire_checked = true;
			}

		});

		// do we have a checked box for expire?
		if ( !expire_checked ) {
			$('label.expire_label').addClass( 'req' );
		} else {
			$('label.expire_label').removeClass( 'req' );
		}

		// prevent form submission if any single check failed
		if ( !passed || !current_checked || !expire_checked ) {
			e.preventDefault();
		}

	});

});
