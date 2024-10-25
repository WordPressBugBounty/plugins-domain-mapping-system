<?php

namespace DMS\Includes\Data_Objects;

use DMS\Includes\Exceptions\DMS_Exception;

class Mapping_Meta extends Data_Object {

	/**
	 * Table name
	 */
	const TABLE = 'dms_mapping_metas';

	/**
	 * The id of the meta
	 *
	 * @var
	 */
	public $id;

	/**
	 * Mapping id
	 *
	 * @var
	 */
	public $mapping_id;

	/**
	 * Meta key
	 *
	 * @var
	 */
	public $key;

	/**
	 * Meta value
	 *
	 * @var
	 */
	public $value;

	/**
	 * Create mapping meta
	 *
	 * @param array $data
	 *
	 * @return object|Mapping_Meta
	 * @throws DMS_Exception
	 */
	public static function create( array $data ): object {
		return parent::wpdb_create( $data );
	}

	/**
	 * Find mapping meta
	 *
	 * @param int|null $id
	 *
	 * @return object|Mapping_Meta|null
	 */
	public static function find( ?int $id ): ?object {
		return parent::wpdb_find( $id );
	}

	/**
	 * Update mapping meta
	 *
	 * @param string $key
	 * @param int $mapping_id
	 * @param array $data
	 *
	 * @return Mapping_Meta|object
	 */
	public static function update( string $key, int $mapping_id, array $data ) {
		return parent::wpdb_update( null, $data, [ 'key' => $key, 'mapping_id' => $mapping_id ] );
	}

	/**
	 * Mapping meta where
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public static function where( $data ) {
		return parent::wpdb_where( $data );
	}

	/**
	 * Delete mapping meta
	 *
	 * @param string $key
	 * @param int $mapping_id
	 *
	 * @return bool
	 * @throws DMS_Exception
	 */
	public static function delete( string $key, int $mapping_id ) {
		return parent::wpdb_delete( null, [ 'key' => $key, 'mapping_id' => $mapping_id ] );
	}

	/**
	 * Get id
	 *
	 * @return mixed
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the id
	 *
	 * @param mixed $id
	 */
	public function set_id( $id ): void {
		$this->id = $id;
	}

	/**
	 * Get the mapping id
	 *
	 * @return mixed
	 */
	public function get_mapping_id() {
		return $this->mapping_id;
	}

	/**
	 * Set mapping id
	 *
	 * @param mixed $mapping_id
	 */
	public function set_mapping_id( $mapping_id ): void {
		$this->mapping_id = $mapping_id;
	}

	/**
	 * Get meta key
	 *
	 * @return mixed
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Set meta key
	 *
	 * @param mixed $key
	 */
	public function set_key( $key ): void {
		$this->key = $key;
	}

	/**
	 * Get meta value
	 *
	 * @return mixed
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Set meta value
	 *
	 * @param mixed $value
	 */
	public function set_value( $value ): void {
		$this->value = $value;
	}
}