<?php

namespace DMS\Includes\Data_Objects;

use DMS\Includes\Exceptions\DMS_Exception;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

abstract class Data_Object implements JsonSerializable {

	/**
	 * Data of the object
	 *
	 * @var null|array
	 */
	protected ?array $data;

    protected static array $query_cache = [];

	/**
	 * Constructor
	 *
	 * @param null|array $instance
	 */
	function __construct( ?array $instance = null ) {
		$this->set_data( $instance );
		$this->hydrate();
	}

	/**
	 * Hydrate
	 *
	 * @return void
	 */
	public function hydrate(): void {

        if ( empty( $this->data ) ) {
            return;
        }

        foreach ( $this->data as $key => $value ) {
            if ( property_exists( $this, $key ) ) {
                $this->$key = $value;
            }
        }
        // Renoved the below because there are no setters so this was redundant.
		/*foreach ( $this->data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$setter = 'set' . '_' . strtolower( $key );
				if ( method_exists( $this, $setter ) ) {
					call_user_func( [ $this, $setter ], $value );
				} else {
					$this->$key = $value;
				}
			}
		}*/

	}

	/**
	 * Creates new data object
	 *
	 * @param array $data
	 *
	 * @return object
	 */
	abstract public static function create( array $data ): object;

	/**
	 * Finds data object
	 *
	 * @param null|int $id
	 *
	 * @return object|null
	 */
	abstract public static function find( ?int $id ): ?object;

	/**
	 * Creates new data object in db
	 *
	 * @param $data
	 *
	 * @return object
	 * @throws DMS_Exception
	 */
	public static function wpdb_create( $data ): object {
		global $wpdb;

		$result = $wpdb->insert( $wpdb->prefix . static::TABLE, $data );
		if ( empty( $result ) ) {
			$wpdb->show_errors = true;
			$db_error          = $wpdb->last_error ?? '';
			throw new DMS_Exception( 'error_on_save', __( 'Error on save.' . $db_error, 'domain-mapping-system' ) );
		}
		$inserted_id = $wpdb->insert_id;

		$data['id'] = $inserted_id;

        self::clear_query_cache();

		return self::make( $data );
	}

	/**
	 * Makes new object of Data object
	 *
	 * @param array $data the data
	 *
	 * @return object
	 */
	public static function make( array $data ): object {
		return new static( $data );
	}

    protected static function get_table(): string {
        if ( ! defined( static::class . '::TABLE' ) ) {
            return '';
        }

        return static::TABLE;
    }

	/**
	 * Gets data objects from db corresponding to conditions
	 *
	 * @param array $conditions
	 * @param null|int $paged
	 * @param null|int $limit
	 * @param string|null $order_by
	 * @param null|string $ordering
	 *
	 * @return array
	 */
	public static function wpdb_where( array $conditions, ?int $paged = null, ?int $limit = 1, ?string $order_by = 'id', ?string $ordering = 'ASC' ): array {
		
        global $wpdb;

        $table = self::get_table();

        if ( empty( $table ) ) {
            return [];
        }

        // Build cache key from all parameters
        $cache_key = $table . md5( serialize( [ $conditions, $paged, $limit, $order_by, $ordering ] ) );

        if ( !empty( self::$query_cache[ $table ] ) && isset( self::$query_cache[ $table ][ $cache_key ] ) ) {
            return self::$query_cache[ $table ][ $cache_key ];
        }

		$where_clause = '1';
		$values       = array();

		foreach ( $conditions as $key => $value ) {
			if ( is_array( $value ) ) {
				$placeholders = array();
				foreach ( $value as $val ) {
					$placeholders[] = is_int( $val ) ? '%d' : '%s';
					$values[]       = $val;
				}
				$where_clause .= " AND `$key` IN (" . implode( ', ', $placeholders ) . ")";
			} else {
				$where_clause .= " AND `$key` = " . ( is_int( $value ) ? '%d' : '%s' );
				$values[]     = $value;
			}
		}
		$order_by     = esc_sql( $order_by );
		$ordering     = ! empty( $ordering ) && strtoupper( $ordering ) === 'DESC' ? 'DESC' : 'ASC';
		$order_by_str = ! empty( $order_by ) ? "ORDER BY `$order_by` $ordering" : "";
		$query        = "SELECT * FROM `" . $wpdb->prefix . $table . "` WHERE $where_clause $order_by_str ";
		$query        = ! empty( $values ) ? $wpdb->prepare( $query, $values ) : $query;
		$paged        = is_null( $paged ) || $paged < 1 ? 1 : $paged;  // Ensure $paged is at least 1
		$limit        = (int) $limit;
		if ( $limit > 0 ) {
			// Calculate the offset
			$offset = ( $paged - 1 ) * $limit;
			$query  .= " LIMIT $offset, $limit";
		}

		// Execute the query and fetch results
		$result = $wpdb->get_results( $query, ARRAY_A );
		$data   = [];

		// Map the results using the make() method
		if ( ! empty( $result ) ) {
			foreach ( $result as $res ) {
				$mapping = self::make( $res );
				$data[]  = $mapping;
			}
		}

        if( ! isset( self::$query_cache[ $table ] ) ) {
            self::$query_cache[ $table ] = [];
        }

        self::$query_cache[ $table ][ $cache_key ] = $data;

		return $data;

	}

	/**
	 * Finds from database
	 *
	 * @param null|int $id
	 *
	 * @return object|null
	 */
	public static function wpdb_find( ?int $id ): ?object {
		global $wpdb;
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . static::TABLE . " WHERE id=%d", $id ), ARRAY_A );

		return !empty( $result ) ? self::make( $result ) : null;
	}

    private static function clear_query_cache(): void {

        $table = self::get_table();

        if( ! empty( $table ) && isset( self::$query_cache[ $table ] ) ) {
            self::$query_cache[ $table ] = [];
        }

    }

	/**
	 * Deletes data object
	 *
	 * @param null|int $id
	 * @param array $where
	 *
	 * @return true
	 * @throws DMS_Exception
	 */
	public static function wpdb_delete( ?int $id, array $where = [] ): bool {
		global $wpdb;
		$where  = ! empty( $where ) ? $where : [ 'id' => $id ];
		$result = $wpdb->delete( $wpdb->prefix . static::TABLE, $where );
		if ( $result === false ) {
			throw new DMS_Exception( 'not_found', __( 'Object not found', 'domain-mapping-system' ) );
		}

        self::clear_query_cache();

		return true;
	}

	/**
	 * Update data objects
	 *
	 * @param $id
	 * @param $data
	 * @param array $where
	 *
	 * @return object
	 */
	public static function wpdb_update( $id, $data, array $where = [] ): object {
		global $wpdb;
		$where = ! empty( $where ) ? $where : [ 'id' => (int) $id ];
		$wpdb->update( $wpdb->prefix . static::TABLE, $data, $where );

		$data = array_merge( $where, $data );
        
        self::clear_query_cache();

		return self::make( $data );
	}

	/**
	 * Finds setting
	 *
	 * @param string $key
	 *
	 * @return object
	 */
	public static function setting_find( string $key ): object {
		$option = get_option( $key, null );

		$data = [ 'key' => $key, 'value' => $option ];

		return self::make( $data );
	}

	/**
	 * Creates setting
	 *
	 * @param array $data
	 *
	 * @return object
	 */
	public static function setting_create( array $data ): object {
		update_option( $data['key'], $data['value'] );

		return self::make( $data );
	}

	/**
	 * Deletes setting
	 *
	 * @param mixed $key
	 *
	 * @return true
	 * @throws DMS_Exception
	 */
	public static function setting_delete( ?string $key ): bool {
		if ( ! delete_option( $key ) ) {
			throw new DMS_Exception( 'setting_not_found', __( 'Setting was not found', 'domain-mapping-system' ) );
		}

		return true;
	}

	/**
	 * Gets count of data object
	 *
	 * @param array $conditions
	 *
	 * @return string|null
	 */
	public static function count( array $conditions = [] ): ?string {
		global $wpdb;
		$where_clause = 'WHERE 1 ';
		$values       = [];
		foreach ( $conditions as $key => $value ) {
			if ( ! empty( $where_clause ) ) {
				$where_clause .= ' AND ';
			}
			if ( is_int( $value ) ) {
				$where_clause .= "$key = %d";
			} else {
				$where_clause .= "$key = %s";
			}
			$values[] = $value;
		}
		$where_clause = ! empty( $where_clause ) ? $wpdb->prepare( $where_clause, implode( ',', $values ) ) : $where_clause;

		return $wpdb->get_var( 'SELECT COUNT(id) FROM ' . $wpdb->prefix . static::TABLE . ' ' . $where_clause );
	}

	/**
	 * Data getter
	 *
	 * @return array|null
	 */
	public function get_data(): ?array {
		return $this->data;
	}

	/**
	 * Data setter
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function set_data( $data ): void {
		$this->data = $data;
	}

	/**
	 * Json serialize
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return $this->toArray();
	}

	/**
	 * To array
	 *
	 * @return array
	 */
	public function toArray(): array {
		$reflection = new ReflectionClass( $this );
		$array      = [];

		foreach ( $reflection->getProperties( ReflectionProperty::IS_PUBLIC ) as $property ) {
			$name = $property->getName();

			// Check if the property is initialized
			if ( $property->isInitialized( $this ) ) {
				$value = $property->getValue( $this );

				if ( is_object( $value ) && is_callable( [ $value, 'toArray' ] ) ) {
					$value = $value->toArray();
				} elseif ( is_array( $value ) ) {
					$sub_array = [];
					foreach ( $value as $sub_property => $sub_value ) {
						if ( is_object( $sub_value ) && is_callable( [ $sub_value, 'toArray' ] ) ) {
							$sub_value = $sub_value->toArray();
						}
						$sub_array[ $sub_property ] = $sub_value;
					}
					$value = $sub_array;
				}
				$array[ $name ] = $value;
			} else {
				// Optionally, handle uninitialized properties here
				$array[ $name ] = null;
			}
		}

		return $array;
	}
}