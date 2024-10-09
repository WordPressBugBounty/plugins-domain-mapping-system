<?php

namespace DMS\Includes\Data_Objects;

class Wp_Object extends Data_Object {

	/**
	 * ID of the object
	 *
	 * @var
	 */
	public $id;

	/**
	 * Title of the object
	 *
	 * @var
	 */
	public $title;

	/**
	 * Link of the object
	 *
	 * @var
	 */
	public $link;

	/**
	 * The type of the object
	 *
	 * @var
	 */
	public $type;

	/**
	 * Create new instance
	 *
	 * @param array $data
	 *
	 * @return self
	 */
	public static function create( array $data ): object {
		return new self;
	}

	/**
	 * Find instance
	 *
	 * @param int|null $id
	 *
	 * @return self|null
	 */
	public static function find( ?int $id ): ?object {
		return new self;
	}

	/**
	 * Get the object id
	 *
	 * @return mixed
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the object id
	 *
	 * @param mixed $id
	 */
	public function set_id( $id ): void {
		$this->id = $id;
	}

	/**
	 * Get the title
	 *
	 * @return mixed
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Set the title
	 *
	 * @param mixed $title
	 */
	public function set_title( $title ): void {
		$this->title = $title;
	}

	/**
	 * Get the link
	 *
	 * @return mixed
	 */
	public function get_link() {
		return $this->link;
	}

	/**
	 * Set link
	 *
	 * @param mixed $link
	 */
	public function set_link( $link ): void {
		$this->link = $link;
	}

	/**
	 * Get object type
	 *
	 * @return mixed
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Set object type
	 *
	 * @param mixed $type
	 */
	public function set_type( $type ): void {
		$this->type = $type;
	}
}