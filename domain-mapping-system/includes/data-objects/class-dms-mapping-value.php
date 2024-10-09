<?php

namespace DMS\Includes\Data_Objects;

use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Factories\Wp_Object_Factory;
use DMS\Includes\Utils\Helper;
use WP_Post;
use WP_Term;

class Mapping_Value extends Data_Object {

	/**
	 * Database table name
	 */
	const TABLE = 'dms_mapping_values';

	/**
	 * Constant for object_type post
	 */
	const OBJECT_TYPE_POST = 'post';

	/**
	 * Constant for object_type term
	 */
	const OBJECT_TYPE_TERM = 'term';

	/**
	 * Constant for object_type posts_homepage
	 */
	const OBJECT_TYPE_POSTS_HOMEPAGE = 'posts_homepage';

	/**
	 * ID of the mapping value
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * Mapping id of the Mapping value
	 *
	 * @var null|int
	 */
	public ?int $mapping_id;

	/**
	 * Object ID of the mapping value
	 *
	 * @var null|int
	 */
	public ?int $object_id;

	/**
	 * Object type of the mapping value (term, post)
	 *
	 * @var string
	 */
	public string $object_type;

	/**
	 * Mapping value primary flag
	 *
	 * @var int
	 */
	public int $primary;

	/**
	 * Creates new mapping value
	 *
	 * @param array $data
	 *
	 * @return Mapping_Value
	 * @throws DMS_Exception
	 */
	public static function create( array $data ): Mapping_Value {
		return parent::wpdb_create( $data );
	}

	/**
	 * Finds Mapping value by ID
	 *
	 * @param null|int $id
	 *
	 * @return Mapping_Value
	 */
	public static function find( ?int $id ): Mapping_Value {
		return parent::wpdb_find( $id );
	}

	/**
	 * Updates mapping value by id and returns the updated mapping value
	 * The result mapping value needs to be fetched one more time because the wpdb_update
	 * Returns the Mapping_Value object the parameters of which are populated according to $data
	 *
	 * @param int $id The id of the mapping which must be updated
	 * @param array $data The fields which must be updated
	 *
	 * @return Mapping_Value|object
	 */
	public static function update( int $id, array $data ): Mapping_Value {
		parent::wpdb_update( $id, $data );

		return Mapping_Value::find( $id );
	}

	/**
	 * Deletes mapping value by value id
	 *
	 * @param int $id
	 *
	 * @return bool
	 * @throws DMS_Exception
	 */
	public static function delete( int $id ): bool {
		return parent::wpdb_delete( $id );
	}

	/**
	 * Get mapping values by given conditions
	 *
	 * @param array $conditions
	 * @param null|int $paged
	 * @param null|int $limit
	 * @param null|string $orderby
	 * @param null|string $order
	 *
	 * @return array
	 */
	public static function where( array $conditions = [], ?int $paged = null, ?int $limit = null, ?string $orderby = 'primary', ?string $order = 'DESC' ): array {
		return parent::wpdb_where( $conditions, $paged, $limit, $orderby, $order );
	}

	/**
	 * Mapping value getter
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Mapping id setter
	 *
	 * @param int $id
	 *
	 * @return void
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Mapping id getter
	 *
	 * @return int
	 */
	public function get_mapping_id(): int {
		return $this->mapping_id;
	}

	/**
	 * Mapping id setter
	 *
	 * @param int $mapping_id
	 *
	 * @return void
	 */
	public function set_mapping_id( int $mapping_id ): void {
		$this->mapping_id = $mapping_id;
	}

	/**
	 * Object ID getter
	 *
	 * @return null|int
	 */
	public function get_object_id(): ?int {
		return $this->object_id;
	}

	/**
	 * Object ID setter
	 *
	 * @param null|int $object_id
	 *
	 * @return void
	 */
	public function set_object_id( ?int $object_id ): void {
		$this->object_id = $object_id;
	}

	/**
	 * Object type getter
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		return $this->object_type;
	}

	/**
	 * Object type setter
	 *
	 * @param string $object_type The object type which must be set (term, post)
	 *
	 * @return void
	 */
	public function set_object_type( string $object_type ): void {
		$this->object_type = $object_type;
	}

	/**
	 * Getter primary value
	 *
	 * @return int
	 */
	public function get_primary(): ?int {
		return $this->primary;
	}

	/**
	 * Primary value setter
	 *
	 * @param null|int $primary
	 *
	 * @return void
	 */
	public function set_primary( ?int $primary ): void {
		$this->primary = $primary;
	}

	/**
	 * Check if object_type is post
	 *
	 * @return bool
	 */
	public function is_post():bool {
		return $this->object_type === self::OBJECT_TYPE_POST;
	}

	/**
	 * Check if object_type is term
	 * 
	 * @return bool
	 */
	public function is_term():bool {
		return $this->object_type === self::OBJECT_TYPE_TERM;
	}

	/**
	 * Check if object_type is posts_homepage
	 *
	 * @return bool
	 */
	public function is_posts_homepage():bool {
		return $this->object_type === self::OBJECT_TYPE_POSTS_HOMEPAGE;
	}

	/**
	 * Get wp object of mapping value
	 *
	 * @return Wp_Object|object
	 */
	public function get_wp_object(){
		return (new Wp_Object_Factory())->make($this->object_id, $this->object_type);
	}

	/**
	 * Get mapped link
	 * 
	 * @return string
	 */
	public function get_mapped_link():string {
		$mapping = Mapping::find( $this->mapping_id );
		$path    = trim( wp_parse_url( $this->get_wp_object()->get_link(), PHP_URL_PATH ), '/' );
		if ( $this->primary ) {
			$mapped_url = Helper::generate_url( $mapping->host, $mapping->path );
		} else {
			$path       = ! empty( $mapping->path ) ? $mapping->path . '/' . $path : $path;
			$mapped_url = Helper::generate_url( $mapping->host, $path );
		}

		return $mapped_url;
	}
}
