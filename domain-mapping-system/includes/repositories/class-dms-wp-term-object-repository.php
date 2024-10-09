<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Data_Objects\Wp_Object;
use WP_Term_Query;

class Wp_Term_Object_Repository extends Wp_Object_Repository {

	/**
	 * Fetch term objects
	 *
	 * @param $group_name
	 * @param $search_term
	 * @param $per_page
	 * @param $page
	 *
	 * @return array
	 */
	public function get_items( $group_name, $search_term, $per_page, $page ): array {
		$taxonomy = str_replace( 'taxonomy_', '', $group_name );
		// First, get the total number of terms without limiting
		$total_terms_args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => 0, // 0 means no limit, fetch all terms for the count
		];

		if ( ! empty( $search ) ) {
			$total_terms_args['search'] = $search;
		}

		// Get all terms to calculate total items
		$total_terms_query = new WP_Term_Query( $total_terms_args );
		$total_items       = count( $total_terms_query->get_terms() );

		// Now, apply pagination to the actual query
		$paged_terms_args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => $per_page, // Apply per_page limit
			'offset'     => ( $page - 1 ) * $per_page, // Calculate the offset based on the current page
		];

		if ( ! empty( $search ) ) {
			$paged_terms_args['search'] = $search;
		}

		// Fetch the paginated terms
		$terms_query = new WP_Term_Query( $paged_terms_args );
		$terms       = array_map( function ( $item ) {
			return Wp_Object::make( [
				'id'    => $item->term_id,
				'title' => $item->name,
				'link'  => get_term_link( $item->term_id ),
				'type'  => 'term',
			] );
		}, $terms_query->get_terms() );

		return $this->paginate( $terms, $total_items, $per_page, $page );
	}
}