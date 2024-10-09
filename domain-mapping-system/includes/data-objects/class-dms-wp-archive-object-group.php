<?php

namespace DMS\Includes\Data_Objects;


class Wp_Archive_Object_Group extends Wp_Object_Group {

	/**
	 * Retrieve archive object groups
	 *
	 * @return array
	 */
	public static function all(): array {
		$enabled = false;
		foreach ( parent::get_post_types() as $post_type_key => $post_type ) {
			if ( self::is_enabled( $post_type_key . '_archive' ) ) {
				$enabled = true;
				break;
			}
		}

		return $enabled ? array(
			self::make( [
				'name'  => 'archives',
				'label' => 'Archives'
			] )
		) : [];
	}
}