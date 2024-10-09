<?php

namespace DMS\Includes\Data_Objects;

class Wp_Post_Object_Group extends Wp_Object_Group {

	/**
	 * Retrieve all the post groups
	 *
	 * @return array
	 */
	public static function all(): array {
		$post_groups = [];
		foreach ( parent::get_post_types() as $post_type_key => $post_type_obj ) {
			// Check if the post type is enabled in settings
			if ( parent::is_enabled( $post_type_key ) ) {
				$post_groups[] = self::make( [
					'name'  => $post_type_key,
					'label' => $post_type_obj->label,
				] );
			}
		}

		return $post_groups;
	}
}