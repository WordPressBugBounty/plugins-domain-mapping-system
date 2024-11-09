<?php

namespace DMS\Includes\Migrations;

use DMS\Includes\Activator;
use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Utils\Helper;

class Migration {
	public $version;
	public $plugin_dir_path;

	public function __construct( $version, $plugin_path ) {
		$this->plugin_dir_path = $plugin_path;
		$this->version         = (int) str_replace( '.', '', $version );
		add_action( 'wp_loaded', array( $this, 'run' ), 999 );
	}

	public function run() {
		global $wpdb;
		$this->run_migration_200( $wpdb );
		$this->run_migration_209();
		$this->run_migration_212( $wpdb );
	}
	
	protected function table_column_exists( \wpdb $wpdb, string $table_name, string $column_name ) {
		return ! empty( $wpdb->get_var( "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . esc_sql( $table_name ) . "' AND COLUMN_NAME = '" . esc_sql( $column_name ) . "'" ) );
	}

	protected function table_exists( \wpdb $wpdb, string $table_name ) {
		return ! empty( $wpdb->get_var( "SHOW TABLES LIKE '" . esc_sql( $table_name ) . "'" ) );
	}

	public function run_migration_200( \wpdb $wpdb ) {
		if ( ! $this->table_column_exists( $wpdb, $wpdb->prefix . 'dms_mapping_values', 'object_id' ) ) {
			error_log( 'DMS-MIGRATION-DEBUGGING-START' );
			$main_mapping            = null;
			$mappings_sql            = 'SELECT * FROM ' . $wpdb->prefix . 'dms_mappings';
			$mapping_values_sql      = 'SELECT * FROM ' . $wpdb->prefix . 'dms_mapping_values';
			$old_mappings            = $wpdb->get_results( $mappings_sql );
			$old_mapping_values      = $wpdb->get_results( $mapping_values_sql );
			$new_mappings            = [];
			$old_mappings_json       = json_encode( $old_mappings );
			$old_mapping_values_json = json_encode( $old_mapping_values );
			Setting::create( [ 'key' => 'dms-old-mappings', 'value' => $old_mappings_json ] );
			Setting::create( [ 'key' => 'dms-old-mapping_values', 'value' => $old_mapping_values_json ] );
			foreach ( $old_mappings as $mapping ) {
				$new_mapping = [
					'host'          => $mapping->host,
					'path'          => $mapping->path,
					'attachment_id' => $mapping->attachment_id,
					'custom_html'   => $mapping->custom_html,
				];
				if ( $mapping->main ) {
					$main_mapping = $mapping;
				}
				$mapping_values     = array_filter( $old_mapping_values, function ( $item ) use ( $mapping ) {
					return $item->host_id == $mapping->id;
				} );
				$new_mapping_values = [];
				foreach ( $mapping_values as $value ) {
					$id   = null;
					$type = null;
					if ( ! isset( $value->value ) ) {
						continue;
					}
					if ( str_starts_with( $value->value, 'category-' ) ) {
						$slug     = str_replace( 'category-', '', $value->value );
						$category = get_category_by_slug( $slug );
						if ( ! empty( $category ) ) {
							$id   = $category->term_id;
							$type = 'term';
						}
					} elseif ( str_starts_with( $value->value, 'term_' ) ) {
						$id   = (int) str_replace( 'term_', '', $value->value );
						$type = 'term';
					} elseif ( is_numeric( $value->value ) ) {
						$id   = (int) $value->value;
						$type = 'post';
					}
					if ( ! empty( $id ) && ! empty( $type ) ) {
						$mapping_value        = [
							'object_id'   => $id,
							'object_type' => $type,
							'primary'     => $value->primary,
							'mapping_id'  => $mapping->id,
						];
						$new_mapping_values[] = $mapping_value;
					}
				}
				$new_mapping['mapping_values'] = $new_mapping_values;
				$new_mappings[]                = $new_mapping;
			}
			error_log( 'DMS-MIGRATION-DEBUGGING-DROP-TABLES' );
			error_log( "DROP TABLE IF EXISTS `{$wpdb->prefix}dms_mapping_values`" );
			error_log( "DROP TABLE IF EXISTS `{$wpdb->prefix}dms_mappings`" );
			$drop_1 = $wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}dms_mapping_values`" );
			$drop_2 = $wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}dms_mappings`" );
			error_log( print_r( $drop_1, true ) );
			error_log( print_r( $drop_2, true ) );
			require_once $this->plugin_dir_path . 'includes/class-dms-activator.php';
			error_log( 'DMS-MIGRATION-DEBUGGING-CREATE-TABLES' );
			( new Activator() )->create_tables();
			if ( ! empty( $new_mappings ) ) {
				$wpdb->query( 'START TRANSACTION' );
				try {
					foreach ( $new_mappings as $new_mapping ) {
						$mapping_values = $new_mapping['mapping_values'];
						unset( $new_mapping['mapping_values'] );
						$new_mapping = Mapping::create( $new_mapping );
						if ( ! empty( $main_mapping ) ) {
							if ( $main_mapping->host == $new_mapping->host && $main_mapping->path == $new_mapping->path ) {
								Setting::create( [ 'key' => 'dms_main_mapping', 'value' => $new_mapping->id ] );
							}
						}
						error_log( 'DMS-MIGRATION-DEBUGGING-INSERT-TABLES' );
						foreach ( $mapping_values as $mapping_value ) {
							$mapping_value['mapping_id'] = $new_mapping->id;
							if ( empty( Mapping_Value::where( $mapping_value ) ) ) {
								Mapping_Value::create( $mapping_value );
							}
						}
					}
					error_log( 'DMS-MIGRATION-DEBUGGING-SETTING-CREATE' );
					$wpdb->query( 'COMMIT' );
					error_log( 'DMS-MIGRATION-DEBUGGING-END' );
				} catch ( \Exception $e ) {
					$wpdb->query( 'ROLLBACK' );
					Helper::log( $e, __METHOD__ );
				}
			}
		}
	}

	public function run_migration_209() {
		$setting = Setting::find( 'dms_main_mapping' )->get_value();
		if ( ! empty( $setting ) && ! is_array( $setting ) ) {
			Setting::update( [ 'key' => 'dms_main_mapping', 'value' => array( $setting ) ] );
		}
	}

	public function run_migration_212( \wpdb $wpdb ) {
		$table_name      = $wpdb->prefix . 'dms_mapping_metas';
		if ( empty( $this->table_exists( $wpdb, $table_name ) ) ) {
			  // Add prefix for WordPress table naming convention.
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            mapping_id bigint(20) NOT NULL,
            `key` varchar(255) NOT NULL,
            `value` text NOT NULL,
            PRIMARY KEY (id),
            INDEX (`key`)
        ) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}
}