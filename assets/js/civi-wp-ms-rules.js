/**
 * CiviCRM Member Sync Rules Javascript.
 *
 * Implements sync functionality on the plugin's Add Rule and Edit Rule admin pages.
 *
 * @package Civi_WP_Member_Sync
 */

// Defaults.
var cwms_method = 'roles',
	cwms_mode = 'add',
	cwms_ajax_url = '',
	cwms_select2 = 'no',
	cwms_groups = 'no',
	cwms_buddypress = 'no';

// Test for our localisation object.
if ( 'undefined' !== typeof CiviCRM_WP_Member_Sync_Rules ) {

	// Override vars.
	cwms_method = CiviCRM_WP_Member_Sync_Rules.method;
	cwms_mode = CiviCRM_WP_Member_Sync_Rules.mode;
	cwms_ajax_url = CiviCRM_WP_Member_Sync_Rules.ajax_url;
	cwms_select2 = CiviCRM_WP_Member_Sync_Rules.select2;
	cwms_groups = CiviCRM_WP_Member_Sync_Rules.groups;
	cwms_buddypress = CiviCRM_WP_Member_Sync_Rules.buddypress;

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

		// Only check Membership Type if in add mode.
		if ( cwms_mode == 'add' ) {

			// Check required Role elements.
			$('.required-type').each( function() {

				// If it's not empty.
				if ( $(this).val() ) {

					// Colour label black.
					$(this).parent().prev().children().removeClass( 'req' );

				} else {

					// Colour label red.
					$(this).parent().prev().children().addClass( 'req' );

					// Set flag.
					passed = false;

				}

			});

		}

		// Only check Roles if that's our sync method.
		if ( cwms_method == 'roles' ) {

			// Check required Role elements.
			$('.required-role').each( function() {

				// If it's not empty.
				if ( $(this).val() ) {

					// Colour label black.
					$(this).parent().prev().children().removeClass( 'req' );

				} else {

					// Colour label red.
					$(this).parent().prev().children().addClass( 'req' );

					// Set flag.
					passed = false;

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

	// The following require Select2.
	if ( cwms_select2 === 'yes' ) {

		// Any pure Select2 stuff here.

	}

	// The following require Select2 and "Groups" Groups.
	if ( cwms_select2 === 'yes' && cwms_groups === 'yes' ) {

		/**
		 * Select2 init on Groups.
		 *
		 * @since 0.4
		 */
		$('#cwms_groups_select_current, #cwms_groups_select_expiry').select2({

			// Action.
			ajax: {
				method: 'POST',
				url: cwms_ajax_url,
				dataType: 'json',
				delay: 250,
				data: function( params ) {
					return {
						s: params.term, // Search term.
						action: 'civi_wp_member_sync_get_groups',
						exclude: cwms_groups_get_excludes( params, this ),
						page: params.page,
					};
				},
				processResults: function( data, page ) {
					// Parse the results into the format expected by Select2.
					// Since we are using custom formatting functions we do not need to
					// alter the remote JSON data.
					return {
						results: data
					};
				},
				cache: true
			},

			// Settings.
			escapeMarkup: function( markup ) { return markup; }, // Let our custom formatter work.
			minimumInputLength: 3,
			templateResult: cwms_groups_format_result,
			templateSelection: cwms_groups_format_response

		});

		/**
		 * Find the Groups to exclude from search.
		 *
		 * This is disabled at present because I can't decide whether or not Groups
		 * should be available in both 'current' and 'expiry' sections. I'm going to
		 * allow duplicates for now.
		 *
		 * @since 0.4
		 *
		 * @param {Object} params The Select2 params.
		 * @param {Object} obj The Select2 object calling this function.
		 * @return {String} excludes The comma-separated Group IDs to exclude from search.
		 */
		function cwms_groups_get_excludes( params, obj ) {

			// --<
			return '';

		}

		/**
		 * Select2 format results for display in dropdown.
		 *
		 * @since 0.4
		 *
		 * @param {Object} data The results data.
		 * @return {String} markup The results markup.
		 */
		function cwms_groups_format_result( data ) {

			// Bail if still loading.
			if ( data.loading ) return data.name;

			// Declare vars.
			var markup;

			// Construct basic Group info.
			markup = '<div style="clear:both;">' +
			'<div class="select2_results_group_name"><span style="font-weight:600;">' + data.name + '</span></div>' +
			'</div>';

			// --<
			return markup;

		}

		/**
		 * Select2 format response.
		 *
		 * @since 0.4
		 *
		 * @param {Object} data The results data.
		 * @return {String} The expected response.
		 */
		function cwms_groups_format_response( data ) {
			return data.name || data.text;
		}

	}

	// The following require Select2 and BuddyPress.
	if ( cwms_select2 === 'yes' && cwms_buddypress === 'yes' ) {

		/**
		 * Select2 init on BuddyPress Groups.
		 *
		 * @since 0.4.7
		 */
		$('#cwms_buddypress_select_current, #cwms_buddypress_select_expiry').select2({

			// Action.
			ajax: {
				method: 'POST',
				url: cwms_ajax_url,
				dataType: 'json',
				delay: 250,
				data: function( params ) {
					return {
						s: params.term, // Search term.
						action: 'civi_wp_member_sync_get_bp_groups',
						exclude: cwms_buddypress_get_excludes( params, this ),
						page: params.page,
					};
				},
				processResults: function( data, page ) {
					// Parse the results into the format expected by Select2.
					// Since we are using custom formatting functions we do not need to
					// alter the remote JSON data.
					return {
						results: data
					};
				},
				cache: true
			},

			// Settings.
			escapeMarkup: function( markup ) { return markup; }, // Let our custom formatter work.
			minimumInputLength: 3,
			templateResult: cwms_buddypress_format_result,
			templateSelection: cwms_buddypress_format_response

		});

		/**
		 * Find the BuddyPress Groups to exclude from search.
		 *
		 * This is disabled at present because I can't decide whether or not Groups
		 * should be available in both 'current' and 'expiry' sections. I'm going to
		 * allow duplicates for now.
		 *
		 * @since 0.4.7
		 *
		 * @param {Object} params The Select2 params.
		 * @param {Object} obj The Select2 object calling this function.
		 * @return {String} excludes The comma-separated Group IDs to exclude from search.
		 */
		function cwms_buddypress_get_excludes( params, obj ) {

			// --<
			return '';

		}

		/**
		 * Select2 format results for display in dropdown.
		 *
		 * @since 0.4.7
		 *
		 * @param {Object} data The results data.
		 * @return {String} markup The results markup.
		 */
		function cwms_buddypress_format_result( data ) {

			// Bail if still loading.
			if ( data.loading ) return data.name;

			// Declare vars.
			var markup;

			// Construct basic Group info.
			markup = '<div style="clear:both;">' +
			'<div class="select2_results_group_name"><span style="font-weight:600;">' + data.name + '</span></div>' +
			'</div>';

			// --<
			return markup;

		}

		/**
		 * Select2 format response.
		 *
		 * @since 0.4.7
		 *
		 * @param {Object} data The results data.
		 * @return {String} The expected response.
		 */
		function cwms_buddypress_format_response( data ) {
			return data.name || data.text;
		}

	}

});
