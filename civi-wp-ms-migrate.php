<?php /*
--------------------------------------------------------------------------------
Civi_WP_Member_Sync_Migrate Class
--------------------------------------------------------------------------------

Author note:

This class is written for me to migrate my existing data. You may need to amend
it for your needs if you want to migrate your data from either of the plugins
that this one has built on.

If you want to enable it, uncomment where CIVI_WP_MEMBER_SYNC_MIGRATE is defined
in the main plugin file.

--------------------------------------------------------------------------------
*/



/**
 * Class for encapsulating migration functionality
 */
class Civi_WP_Member_Sync_Migrate {

	/**
	 * Properties
	 */

	/**
	 * Initialise this object
	 * @param object $parent_obj The parent object
	 * @return object
	 */
	function __construct( $parent_obj ) {

		// store reference to parent
		$this->parent_obj = $parent_obj;

		// --<
		return $this;

	}



	//##########################################################################



	/**
	 * Check for legacy 'civi_member_sync' plugin
	 *
	 * @return boolean $result
	 */
	public function legacy_plugin_exists() {

		// disable
		return false;

		// grab default data to test for the default array
		$data = $this->parent_obj->setting_get( 'data' );

		// don't show migration if we have data
		if ( count( $data['roles'] ) > 0 ) return false;

		// can we detect the legacy plugin?
		if ( function_exists( 'tadms_install' ) ) return true;
		if ( defined( 'CIVI_MEMBER_SYNC_VERSION' ) ) return true;

		// not present
		return false;

	}



	/**
	 * Migrate from 'civi_member_sync' to this plugin
	 *
	 * @return void
	 */
	public function legacy_migrate() {

		// first, migrate data
		$this->legacy_data_migrate();

		// remove plugin options
		delete_option( 'jal_db_version' );
		delete_option( 'tadms_db_version' );
		delete_option( 'civi_member_sync_db_version' );
		delete_option( 'civi_member_sync_settings' );

		// now delete the database tables
		$this->legacy_table_delete( 'mtl_civi_member_sync' );
		$this->legacy_table_delete( 'civi_member_sync' );

	}



	/**
	 * Migrate 'civi_member_sync' data to our plugin settings
	 *
	 * @return boolean $result
	 */
	public function legacy_data_migrate() {

		// grab default data (there will only be the skeleton array)
		$data = $this->parent_obj->setting_get( 'data' );
		//print_r( $data ); die();

		// access database object
		global $wpdb;

		// get tabular data
		$table_name = $wpdb->prefix . 'civi_member_sync';
		$select = $wpdb->get_results( "SELECT * FROM $table_name" );

		// did we get any?
		if ( ! empty( $select ) ) {

			// looooooop...
			foreach( $select AS $item ) {

				// unpack arrays
				$current_rule = maybe_unserialize( $item->current_rule );
				$expiry_rule = maybe_unserialize( $item->expiry_rule );

				// add to roles data array, keyed by civi_member_type_id
				$data['roles'][$item->civi_mem_type] = array(
					'current_rule' => $current_rule,
					'current_wp_role' => $item->wp_role,
					'expiry_rule' => $expiry_rule,
					'expired_wp_role' => $item->expire_wp_role,
				);

			}

			//print_r( $data ); die();

			// overwrite existing data
			$this->parent_obj->setting_set( 'data', $data );

			// save
			$this->parent_obj->settings_save();

		}

	}



	/**
	 * Remove previous 'civi_member_sync' database tables
	 *
	 * @return boolean $result
	 */
	public function legacy_table_delete( $table_name = 'civi_member_sync' ) {

		// access database object
		global $wpdb;

		// our custom table name
		$table_name = $wpdb->prefix . $table_name;

		// drop our custom table
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		// check if we were successful
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
			return false;
		}

		// --<
		return true;

	}



} // class ends



