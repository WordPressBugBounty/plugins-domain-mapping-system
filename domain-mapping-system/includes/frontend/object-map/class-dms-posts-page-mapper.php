<?php

namespace DMS\Includes\Frontend\Mapping_Objects;

use DMS\Includes\Data_Objects\Mapping_Value;
use WP_Query;

class Posts_Page_Mapper extends Mapper implements Mapper_Interface {

	/**
	 * Constructor
	 *
	 * @param Mapping_Value $value
	 * @param WP_Query $query
	 */
	public function __construct( Mapping_Value $value, WP_Query $query ) {
		parent::__construct( $value, $query );
		$this->object = get_post( $this->mapping_value->object_id );
		$this->define_query();
	}

	/**
	 * Modify the wp_query according to current object parameters
	 *
	 * @return void
	 */
	public function define_query(): void {
		$this->query->is_posts_page = true;
		$this->query->is_home = true;
		$this->query->is_404 = false;
		$this->query->query['page'] = '';
		$this->query->query['pagename']   = $this->object->post_name;
		$this->query->query_vars['page_id'] = 0;
	}
}