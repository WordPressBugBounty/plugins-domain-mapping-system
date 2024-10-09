<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Data_Objects\Wp_Object;

class Wp_Archive_Object_Repository extends Wp_Object_Repository {

	/**
	 * Fetch Archive objects
	 *
	 * @param $group_name
	 * @param $search_term
	 * @param $per_page
	 * @param $page
	 *
	 * @return array
	 */
	public function get_items( $group_name, $search_term, $per_page, $page ): array {
		$post_types_with_archives = get_post_types( [ 'public' => true, 'has_archive' => true ], 'objects' );

		// Filter post types based on search
		if ( ! empty( $search_term ) ) {
			$post_types_with_archives = array_filter( $post_types_with_archives, function ( $post_type_obj ) use ( $search_term ) {
				return str_contains( strtolower( $post_type_obj->label ), strtolower( $search_term ) );
			} );
		}

		// Pagination logic
		$total_items              = count( $post_types_with_archives );
		$offset                   = ( $page - 1 ) * $per_page;
		$post_types_with_archives = array_slice( $post_types_with_archives, $offset, $per_page );

		// Map post types into archive objects
		$archives = array_map( function ( $post_type_obj ) {
			return Wp_Object::make( [
				'id'    => null,
				'title' => $post_type_obj->label,
				'link'  => get_post_type_archive_link( $post_type_obj->name ),
				'type'  => $post_type_obj->name,
			] );
		}, $post_types_with_archives );

		return $this->paginate( $archives, $total_items, $per_page, $page );
	}
}