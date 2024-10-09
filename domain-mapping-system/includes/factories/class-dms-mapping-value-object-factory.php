<?php

namespace DMS\Includes\Factories;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Utils\Helper;

class Mapping_Value_Object_Factory {

	/**
	 * Return mapping value _object array
	 *
	 * @param Mapping_Value $value
	 *
	 * @return array
	 */
	public function make( Mapping_Value $value ) {
		$link = '';
		$name = '';
		if ( $value->object_type == Mapping_Value::OBJECT_TYPE_POST ) {
			$name = get_post( $value->object_id )->post_title;
			$link = get_permalink( $value->object_id );
		} elseif ( $value->object_type == Mapping_Value::OBJECT_TYPE_TERM ) {
			$name = get_term( $value->object_id )->name;
			$link = get_term_link( $value->object_id );
		} elseif ( $value->object_type == Mapping_Value::OBJECT_TYPE_POSTS_HOMEPAGE ) {
			$name = __( 'Latest posts', 'domain-mapping-system' );
			$link = get_site_url();

		} elseif ( is_null( $value->object_id ) ) {
			$post_type_object = get_post_type_object( $value->object_type );
			$name             = ! empty( $post_type_object->label ) ? $post_type_object->label : $value->object_type;
			$link             = get_post_type_archive_link( $value->object_type );
		}
		$mapped_link = $this->get_mapped_link( $value, $link );
		$name        = apply_filters( 'dms_mapping_value_name', $name, $value );
		$link        = apply_filters( 'dms_mapping_value_link', $link, $value );
		$mapped_link = apply_filters( 'dms_mapping_value_mapped_link', $mapped_link );

		return [
			'object_name'        => $name,
			'object_link'        => $link,
			'object_mapped_link' => $mapped_link,
		];
	}

	/**
	 * Get the mapped link of the mapping value
	 *
	 * @param Mapping_Value $value
	 * @param $link
	 *
	 * @return string
	 */
	private function get_mapped_link( Mapping_Value $value, $link ) {
		$mapping = Mapping::find( $value->mapping_id );
		$path    = trim( wp_parse_url( $link, PHP_URL_PATH ), '/' );
		if ( $value->primary ) {
			$mapped_url = Helper::generate_url( $mapping->host, $mapping->path );
		} else {
			$path       = ! empty( $mapping->path ) ? $mapping->path . '/' . $path : $path;
			$mapped_url = Helper::generate_url( $mapping->host, $path );
		}

		return $mapped_url;
	}
}