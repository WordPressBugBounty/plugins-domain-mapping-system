<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Data_Objects\Wp_Archive_Object_Group;
use DMS\Includes\Data_Objects\Wp_Homepage_Post_Object_Group;
use DMS\Includes\Data_Objects\Wp_Post_Object_Group;
use DMS\Includes\Data_Objects\WP_Term_Object_Group;
use DMS\Includes\Exceptions\DMS_Exception;

class Wp_Object_Group_Repository {

	/**
	 * Retrieve object groups
	 *
	 * @return array
	 * @throws DMS_Exception
	 */
	public function get_items(): array {
		$object_groups = [];
		$group_classes = array(
			Wp_Post_Object_Group::class,
			Wp_Term_Object_Group::class,
			Wp_Archive_Object_Group::class,
			Wp_Homepage_Post_Object_Group::class
		);

		$allowed_groups = apply_filters( 'dms_allowed_object_groups', $group_classes );

		foreach ( $allowed_groups as $group ) {
			$object_groups = array_merge( $object_groups, $group::all() );
		}

		return $object_groups;
	}
}
