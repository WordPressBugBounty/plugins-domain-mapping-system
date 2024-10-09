<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Data_Objects\Wp_Object;
use WP_Query;

class Wp_Post_Object_Repository extends Wp_Object_Repository {

	/**
	 * Fetch the posts objects
	 *
	 * @param $group_name
	 * @param $search_term
	 * @param $per_page
	 * @param $page
	 *
	 * @return array
	 */
	public function get_items( $group_name, $search_term, $per_page, $page ): array {
		$paged_posts_args = [
			'post_type'      => $group_name,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,  // Apply the items per page
			'paged'          => $page,      // Set the current page
		];

		if ( ! empty( $search_term ) ) {
			$paged_posts_args['s'] = $search_term;  // Apply search if provided
		}

		// Perform the WP_Query
		$posts_query = new WP_Query( $paged_posts_args );

		// Map the posts into the required format
		$posts = array_map( function ( $post ) {
			return Wp_Object::make( [
				'id'    => $post->ID,
				'title' => $post->post_title,
				'link'  => get_permalink( $post->ID ),
				'type'  => 'post',
			] );
		}, $posts_query->posts );

		// Get the total number of posts from WP_Query
		$total_items = $posts_query->found_posts;

		return $this->paginate( $posts, $total_items, $per_page, $page );
	}
}