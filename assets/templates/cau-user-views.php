<!-- assets/templates/cau-user-views.php -->
<li class="members">
	| <a href="<?php echo esc_url( add_query_arg( 'user_status', 'members', $url_base ) ); ?>" class="<?php if ( 'members' === $user_status ) echo 'current'; ?>">
		<?php printf(
			/* translators: %s is the placeholder for the count html `<span class="count"/>` */
			_n( 'Members %s', 'Members %s', $member_count, 'civicrm-wp-member-sync' ),
			sprintf(
				'<span class="count">(%s)</span>',
				number_format_i18n( $member_count )
			)
		); ?>
	</a>
</li>

<li class="non-members">
	| <a href="<?php echo esc_url( add_query_arg( 'user_status', 'non_members', $url_base ) ); ?>" class="<?php if ( 'non_members' === $user_status ) echo 'current'; ?>">
		<?php printf(
			/* translators: %s is the placeholder for the count html `<span class="count"/>` */
			_n( 'Non Members %s', 'Non Members %s', $non_member_count, 'civicrm-wp-member-sync' ),
			sprintf(
				'<span class="count">(%s)</span>',
				number_format_i18n( $non_member_count )
			)
		); ?>
	</a>
</li>

