<?php

namespace DMS\Includes\Factories;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Wp_Object;

class Wp_Object_Factory {

	/**
	 * Return wp object
	 *
	 * @param $object_id
	 * @param $object_type
	 *
	 * @return Wp_Object|object
	 */
	public function make( $object_id, $object_type ) {
		$link  = '';
		$title = '';
		if ( $object_type == Mapping_Value::OBJECT_TYPE_POST ) {
			$post  = get_post( $object_id );
			$title = ! empty( $post ) ? $post->post_title : '';
			$link  = get_permalink( $object_id );
		} elseif ( $object_type == Mapping_Value::OBJECT_TYPE_TERM ) {
			$title = get_term( $object_id )->name;
			$link  = get_term_link( $object_id );
		} elseif ( $object_type == Mapping_Value::OBJECT_TYPE_POSTS_HOMEPAGE ) {
			$title = __( 'Latest posts', 'domain-mapping-system' );
			$link  = get_site_url();
		} elseif ( is_null( $object_id ) ) {
			$post_type_object = get_post_type_object( $object_type );
			$title            = ! empty( $post_type_object->label ) ? $post_type_object->label : $object_type;
			$link             = get_post_type_archive_link( $object_type );
		}
		$title = apply_filters( 'dms_wp_object_value_title', $title, $object_id, $object_type );
		$link  = apply_filters( 'dms_wp_object_value_link', $link, $object_id, $object_type );

		return Wp_Object::make( [
			'title' => $title,
			'link'  => $link,
			'id'    => $object_id,
			'type'  => $object_type
		] );
	}
}