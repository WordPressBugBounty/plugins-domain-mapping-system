<?php

namespace DMS\Includes\Data_Objects;

use DMS\Includes\Factories\Wp_Object_Repository_Factory;

class Wp_Object_Group extends Data_Object {

	/**
	 * Group name
	 *
	 * @var
	 */
	public $name;

	/**
	 * Group label
	 *
	 * @var
	 */
	public $label;

	/**
	 * Create a new instance
	 *
	 * @param array $data
	 *
	 * @return self
	 */
	public static function create( array $data ): object {
		return new self;
	}

	/**
	 * Get all groups
	 *
	 * @return array
	 */
	public static function all(): array {
		return [];
	}

	/**
	 * Get the post types
	 *
	 * @return string[]|\WP_Post_Type[]
	 */
	protected static function get_post_types(){
		return get_post_types( [ 'public' => true ], 'objects' );
	}


	/**
	 * Check is the object group enabled
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	protected static function is_enabled( string $key ): bool {
		return Setting::find( 'dms_use_' . $key )->get_value() === 'on';
	}

	/**
	 * Find the instance
	 *
	 * @param int|null $id
	 *
	 * @return self|null
	 */
	public static function find( ?int $id ): ?object {
		return new self;
	}

	/**
	 * Get the group name
	 *
	 * @return mixed
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Set the group name
	 *
	 * @param mixed $label
	 */
	public function set_label( $label ): void {
		$this->label = $label;
	}

	/**
	 * Get the objects
	 *
	 * @return array
	 */
	public function get_objects( $search_term, $per_page, $page ) {
		$object_repository = ( new Wp_Object_Repository_Factory() )->make( $this->get_name() );

		return $object_repository->get_items( $this->get_name(), $search_term, $per_page, $page );
	}

	/**
	 * Get the group name
	 *
	 * @return mixed
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Set the group name
	 *
	 * @param mixed $name
	 */
	public function set_name( $name ): void {
		$this->name = $name;
	}
}