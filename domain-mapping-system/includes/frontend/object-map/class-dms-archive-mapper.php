<?php

namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Utils\Helper;
use WP_Query;

class Archive_Mapper extends Mapper implements Mapper_Interface {

	/**
	 * @var string|null
	 */
	public ?string $post_type;

	/**
	 * Constructor
	 *
	 * @param  Mapping_Value  $value
	 * @param  WP_Query  $query
	 */
	public function __construct( Mapping_Value $value, WP_Query $query ) {
		parent::__construct( $value, $query );
		$this->post_type = $value->object_type;
		$this->define_query();
	}

	/**
	 * Modify the wp_query according to current object parameters
	 *
	 * @return void
	 */
	public function define_query(): void {
		$this->query->query['post_type']      = $this->post_type;
		$this->query->query_vars['post_type'] = $this->post_type;
		$this->query->is_archive              = true;
		$this->query->is_post_type_archive    = true;
		$this->query->is_404                  = false;
		$this->query->is_home                 = false;
		$this->query->is_single               = false;
		$this->query->is_attachment           = false;
		$this->query->is_singular             = false;
		$this->query->is_page                 = false;
		unset( $this->query->query['pagename'] );
		unset( $this->query->query['page_id'] );
		unset( $this->query->query['error'] );
		unset( $this->query->query_vars['error'] );
		unset( $this->query->query_vars['pagename'] );
		unset( $this->query->query_vars['page_id'] );
		unset( $this->query->query['name'] );
		unset( $this->query->query_vars['name'] );
		unset( $this->query->query['attachment'] );
		unset( $this->query->query_vars['attachment'] );
	}
}