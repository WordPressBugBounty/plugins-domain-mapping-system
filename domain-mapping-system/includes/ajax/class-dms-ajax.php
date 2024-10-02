<?php

namespace DMS\Includes\Ajax;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Utils\Helper;

/**
 * Ajax class for organizing callbacks for ajax requests
 */
class Ajax {

	/**
	 * Ajax actions
	 *
	 * @var string[]
	 */
	protected static array $actions
		= [
			'mapping_values_search' => 'load_more_mapping_options',
		];

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public static function init(): void {
		self::define_hooks();
	}

	/**
	 * Define hooks
	 *
	 * @return void
	 */
	private static function define_hooks(): void {
		add_action( 'wp_ajax_mapping_values_search', array( 'DMS\Includes\Ajax\Ajax', 'load_more_mapping_options' ) );
	}

	/**
	 * Load more options
	 *
	 * Fires when 'Load More' button is clicked from admin side
	 *
	 * @return void
	 */
	public static function load_more_mapping_options(): void {
		$search_term = ! empty( $_GET['search_term'] ) ? sanitize_text_field( $_GET['search_term'] ) : '';
		$options     = self::search_select_values( $search_term );

		$results = [];
		foreach ( $options as $key_inner => $optgroup ) {
			$group = [ 'text' => $key_inner, 'children' => [] ];
			foreach ( $optgroup as $option ) {
				$group['children'][] = [ 'id' => $option['id'], 'text' => $option['title'] ];
			}
			$results[] = $group;
		}

		wp_send_json( $results );
	}

	/**
	 * Search select values
	 *
	 * Fires when User does search on the mapping select box
	 *
	 * @param string $search
	 *
	 * @return array
	 */
	public static function search_select_values( string $search = '' ): array {
		$posts = [];

		// Get custom post types
		$custom_post_types = Helper::get_custom_post_types( 'objects' );

		// Include native post types (Posts and Pages)
		$native_post_types = [
			'post' => 'Posts',
			'page' => 'Pages'
		];

		// Merge custom and native post types
		$post_types = array_merge( $custom_post_types, $native_post_types );

		// Retrieve blog categories if enabled
		$useCats = get_option( 'dms_use_categories' );
		if ( $useCats === 'on' ) {
			$catArgs = [
				'hide_empty' => false,
				'number'     => ( $search ? - 1 : 5 )
			];
			if ( ! empty( $search ) ) {
				$catArgs['search'] = $search;
			}
			$cats = get_categories( $catArgs );
			if ( ! empty( $cats ) ) {
				$posts['Blog Categories'] = [];
				foreach ( $cats as $cat ) {
					$posts['Blog Categories'][] = [
						'title'       => $cat->name,
						'id'          => 'term_' . $cat->term_id,
						'object_type' => 'term'
					];
				}
			}
		}

		// Loop through each post type to retrieve posts and taxonomies connected with the current post type
		foreach ( $post_types as $post_type => $label ) {
			$label      = is_string( $label ) ? $label : $label->label;
			$useArchive = get_option( 'dms_use_' . $post_type . '_archive' );
			$usePosts   = get_option( 'dms_use_' . $post_type );
			if ( $usePosts === 'on' ) {
				$postArgs = [
					'posts_per_page' => ( $search ? - 1 : 5 ),
					'post_type'      => $post_type
				];
				if ( ! empty( $search ) ) {
					$postArgs['dms_object_by_title'] = $search;
				}
				$query     = new \WP_Query($postArgs);
				$blogPosts = $query->get_posts();
				$query->reset_postdata();
				
				if ( ! empty( $blogPosts ) ) {
					$posts[ ucfirst( $label ) ] = [];
					foreach ( $blogPosts as $post ) {
						$posts[ ucfirst( $label ) ][] = [
							'id'          => $post->ID,
							'object_type' => 'post',
							'title'       => $post->post_title,
							'link'        => get_permalink( $post->ID )
						];
					}
				}
			}
			// CPT archive collect 
			if( $useArchive === 'on' ) {
				if( empty( $search ) || str_contains( strtolower( $label ), strtolower( $search ) ) ) {
					$posts[ 'Archives' ][] = [
						'id'          => $post_type,
						'title'       => $label,
						'object_type' => $post_type,
						'link'        => get_post_type_archive_link($post_type) 
					];
				}
			}
			// Retrieve custom taxonomies connected with this post
			if( $post_type === 'post' ) {
				continue;
			}
			$postTaxonomies = get_object_taxonomies( $post_type, 'objects' );
			foreach ( $postTaxonomies as $taxonomy ) {
				$useTax = get_option( 'dms_use_cat_' . $post_type . '_' . $taxonomy->name );
				if ( $useTax === 'on' ) {
					$taxonomyArgs = [
						'taxonomy'   => $taxonomy->name,
						'hide_empty' => false,
						'number'     => ( $search ? - 1 : 5 )
					];
					if ( ! empty( $search ) ) {
						$taxonomyArgs['search'] = $search;
					}
					$terms = get_terms( $taxonomyArgs );
					if ( ! empty( $terms ) ) {
						$posts[ ucfirst( $taxonomy->label ) ] = [];
						foreach ( $terms as $term ) {
							$posts[ ucfirst( $taxonomy->label ) ][] = [
								'title'       => $term->name,
								'id'          => 'term_' . $term->term_id,
								'permalink'   => get_term_link( $term->term_id, $taxonomy->name ),
								'object_type' => 'term'
							];
						}
					}
				}
			}
		}
		
		// Posts homepage
		if( empty( $search ) || str_contains( strtolower( 'Latest posts' ), strtolower( $search ) ) ) {
			$posts[ __( 'Homepage', 'domain-mapping-system' ) ][] = [
				'id'          => Mapping_Value::OBJECT_TYPE_POSTS_HOMEPAGE,
				'title'       => __( 'Latest posts', 'domain-mapping-system' ),
				'permalink'   => get_home_url(),
				'object_type' => Mapping_Value::OBJECT_TYPE_POSTS_HOMEPAGE,
			];
		}

		// Exclude the mapped once
		if ( ! empty( $_GET['mapping'] ) ) {
			$mapping        = (int) sanitize_text_field($_GET['mapping']);
			$values_ids     = [];
			$mapping_values = Mapping_Value::where( [ 'mapping_id' => $mapping ] );
			foreach ( $mapping_values as $value ) {
				$object_id = $value->object_id;
				if ( $value->object_type == 'term' ) {
					$object_id = $value->object_type . '_' . $value->object_id;
				}
				$values_ids[] = $object_id;
			}

			foreach ( $posts as $type => $post_group ) {
				foreach ( $post_group as $key => $post ) {
					if ( in_array( $post['id'], $values_ids, true ) ) {
						unset( $posts[ $type ][ $key ] );
					}
				}
			}
		}

		return apply_filters( 'dms_search_select_values', $posts, $search );
	}

}