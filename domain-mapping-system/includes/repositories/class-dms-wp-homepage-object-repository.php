<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Wp_Object;

class Wp_Homepage_Object_Repository extends Wp_Object_Repository {
	/**
	 * Fetch Homepage objects
	 *
	 * @param $group_name
	 * @param $search_term
	 * @param $per_page
	 * @param $page
	 *
	 * @return array
	 */
	public function get_items( $group_name, $search_term, $per_page, $page ): array {
		$objects    = array();
		$objects [] = Wp_Object::make( [
				'id'    => null,
				'title' => __( 'Latest posts', 'domain-mapping-system' ),
				'link'  => get_home_url(),
				'type'  => Mapping_Value::OBJECT_TYPE_POSTS_HOMEPAGE,
			]
		);

		return $this->paginate( $objects, count( $objects ), $per_page, $page );
	}
}