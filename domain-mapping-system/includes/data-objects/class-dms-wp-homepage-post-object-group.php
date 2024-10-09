<?php

namespace DMS\Includes\Data_Objects;

class Wp_Homepage_Post_Object_Group extends Wp_Object_Group {

	/**
	 * Retrieve homepage object groups
	 *
	 * @return array
	 */
	public static function all(): array {
		return array(
			self::make( [
				'name'  => 'homepage_posts',
				'label' => __( 'Homepage', 'domain-mapping-system' ),
			] )
		);
	}
}