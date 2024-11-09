<?php

namespace DMS\Includes;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Meta;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;
use wpdb;

class Uninstaller {

	/**
	 * WPDB instance
	 *
	 * @var wpdb
	 */
	private static $wpdb;

	/**
	 * Flag indicating whether to remove plugin data upon uninstall.
	 *
	 * @var bool
	 */
	private static bool $remove_upon_uninstall = false;

	/**
	 * Executes the uninstallation procedure.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		global $wpdb;

		self::$wpdb = $wpdb;
		self::initialize_removal_flag();

		if ( self::should_remove_data_upon_uninstall() ) {
			if ( is_multisite() ) {
				self::delete_multisite_data();
			} else {
				self::delete_database_tables();
				self::delete_settings();
			}
		}
	}

	/**
	 * Initializes the removal flag based on settings.
	 *
	 * @return void
	 */
	private static function initialize_removal_flag(): void {
		self::$remove_upon_uninstall = ! empty( Setting::find( 'dms_delete_upon_uninstall' )->get_value() );
	}

	/**
	 * Checks if the removal of data is enabled upon uninstallation.
	 *
	 * @return bool True if data removal is enabled, false otherwise.
	 */
	private static function should_remove_data_upon_uninstall(): bool {
		return self::$remove_upon_uninstall;
	}

	/**
	 * Deletes the plugin's database tables for all sites in a multisite network.
	 *
	 * @return void
	 */
	private static function delete_multisite_data(): void {
		// Get all blog IDs
		$blog_ids = self::$wpdb->get_col( "SELECT blog_id FROM " . self::$wpdb->blogs );

		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			self::delete_database_tables();
			self::delete_settings();
			restore_current_blog();
		}
	}

	/**
	 * Deletes the plugin's database tables.
	 *
	 * @return void
	 */
	private static function delete_database_tables(): void {
		$tables = [ Mapping::TABLE, Mapping_Meta::TABLE, Mapping_Value::TABLE ];

		foreach ( $tables as $table ) {
			$table_name = self::$wpdb->prefix . $table;
			error_log( "Attempting to drop table: {$table_name}" ); // Log the table being dropped
			self::$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		}
	}

	/**
	 * Deletes all plugin-related options from the wp_options table.
	 *
	 * @return void
	 */
	private static function delete_settings(): void {
		self::$wpdb->query( "DELETE FROM " . self::$wpdb->options . " WHERE option_name LIKE 'dms_%'" );
	}
}
