<?php

namespace DMS\Includes\Data_Objects;

class WP_Term_Object_Group extends Wp_Object_Group {

	/**
	 * Retrieve all the term groups
	 *
	 * @return array
	 */
	public static function all(): array {
		$term_objects = [];
		foreach ( parent::get_post_types() as $post_type_key => $post_type_obj ) {

			$post_taxonomies = get_object_taxonomies( $post_type_key, 'objects' );
			foreach ( $post_taxonomies as $taxonomy ) {
				$taxonomy_key = self::get_taxonomy_key( $post_type_key, $taxonomy->name );

				if ( parent::is_enabled( $taxonomy_key ) ) {
					$object_group = self::make( [
						'name'  => 'taxonomy_' . $taxonomy->name,
						'label' => $taxonomy->label,
					] );
					if ( ! in_array( $object_group, $term_objects ) ) {
						$term_objects[] = $object_group;
					}
				}
			}
		}

		return $term_objects;
	}

	/**
	 * Get taxonomy key
	 *
	 * @param string $post_type
	 * @param string $taxonomy_name
	 *
	 * @return string
	 */
	private static function get_taxonomy_key( string $post_type, string $taxonomy_name ): string {
		return $taxonomy_name === 'category' ? 'categories' : "cat_{$post_type}_{$taxonomy_name}";
	}
}