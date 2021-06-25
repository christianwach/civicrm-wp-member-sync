<!-- assets/templates/migrate.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM Member Sync', 'civicrm-wp-member-sync' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; ?>" class="nav-tab"><?php esc_html_e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; ?>" class="nav-tab"><?php esc_html_e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h2>

	<?php

	// If we've updated, show message.
	if ( isset( $_GET['updated'] ) AND $_GET['updated'] == 'true' ) {
		echo '<div id="message" class="updated"><p>' . esc_html__( 'Migration complete. You can now deactivate the old plugin.', 'civicrm-wp-member-sync' ) . '</p></div>';
	}

	?>

	<form method="post" id="civi_wp_member_sync_migrate_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civi_wp_member_sync_migrate_action', 'civi_wp_member_sync_nonce' ); ?>

		<h3><?php esc_html_e( 'Legacy civi_member_sync plugin detected', 'civicrm-wp-member-sync' ); ?></h3>

		<p><?php esc_html_e( 'A version of the civi_member_sync plugin has been detected.', 'civicrm-wp-member-sync' ); ?></p>

		<p><?php esc_html_e( 'Click the "Migrate Data Now" button below to import all association rules into CiviCRM Member Sync.', 'civicrm-wp-member-sync' ); ?></p>

		<p class="submit"><input class="button-primary" type="submit" id="civi_wp_member_sync_migrate_submit" name="civi_wp_member_sync_migrate_submit" value="<?php esc_attr_e( 'Migrate Data Now', 'civicrm-wp-member-sync' ); ?>" /></p>

	</form>

</div><!-- /.wrap -->



