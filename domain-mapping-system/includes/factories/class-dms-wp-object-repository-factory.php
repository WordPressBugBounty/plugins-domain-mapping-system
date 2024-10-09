<?php

namespace DMS\Includes\Factories;

use DMS\Includes\Repositories\Wp_Archive_Object_Repository;
use DMS\Includes\Repositories\Wp_Homepage_Object_Repository;
use DMS\Includes\Repositories\Wp_Post_Object_Repository;
use DMS\Includes\Repositories\Wp_Term_Object_Repository;

class Wp_Object_Repository_Factory {

	/**
	 * Get the object name from group_name
	 *
	 * @param $group_name
	 *
	 * @return null|object
	 */
	public function make( $group_name ): ?object {
		$repository = null;
		if ( str_starts_with( $group_name, 'taxonomy_' ) ) {
			$repository = new Wp_Term_Object_Repository();
		} elseif ( $group_name === 'archives' ) {
			$repository = new Wp_Archive_Object_Repository();
		} elseif ( post_type_exists( $group_name ) ) {
			$repository = new Wp_Post_Object_Repository();
		} elseif ( $group_name === 'homepage_posts' ) {
			$repository = new Wp_Homepage_Object_Repository();
		}

		return apply_filters( 'dms_wp_object_repository', $repository, $group_name );
	}
}