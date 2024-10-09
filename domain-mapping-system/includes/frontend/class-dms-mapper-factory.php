<?php

namespace DMS\Includes\Frontend;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Scenarios\Archive_Mapping;
use DMS\Includes\Frontend\Scenarios\Latest_Posts_Homepage_Mapping;
use DMS\Includes\Frontend\Scenarios\Posts_Page_Mapping;
use DMS\Includes\Utils\Helper;
use WP_Query;

class Mapper_Factory {

	/**
	 * Create Mapper instance by mapping value
	 *
	 * @param Mapping_Value $mapping_value Mapping value instance
	 * @param WP_Query $query WP_Query instance
	 *
	 * @return mixed|null
	 */
	public function make( Mapping_Value $mapping_value, WP_Query $query ) {
		$object_id   = $mapping_value->object_id;
		$object_type = $mapping_value->object_type;
		
		// Check if matched scenario exists and create the related mapper right away
		if( isset( DMS()->frontend->mapping_scenarios->matched_scenario ) ) {
			$scenario = DMS()->frontend->mapping_scenarios->matched_scenario;
			if( $scenario instanceof Posts_Page_Mapping || $scenario instanceof Latest_Posts_Homepage_Mapping || $scenario instanceof Archive_Mapping) {
				$mapper_classname = Helper::get_class_shortname( $scenario::$mapper );
				$class            = 'DMS\\Includes\\Frontend\\Mapping_Objects\\' . $mapper_classname;
			} elseif ( Helper::get_shop_page_association() === $object_id && $object_type === 'post' ) {
				$class = 'DMS\\Includes\\Frontend\\Mapping_Objects\\Shop_Mapper';
			} else {
				if ( $object_type === 'post' ) {
					$post_type = get_post( $object_id )->post_type;
					if ( ! in_array( $post_type, [ 'post', 'page' ] ) ) {
						if ( str_contains( $post_type, '_' ) || str_contains( $post_type, '-' ) ) {
							$delimiter    = str_contains( $post_type, '_' ) ? '_' : '-';
							$mapping_type = Helper::prepare_class_name( $delimiter, $post_type );
						} else {
							$mapping_type = ucfirst( $object_type );
						}
						$mapping_type = class_exists( 'DMS\\Includes\\Frontend\\Mapping_Objects\\' . $mapping_type . '_Mapper' ) ? $mapping_type : ucfirst( $object_type );
					} else {
						$mapping_type = ucfirst( $object_type );
					}
				} else {
					$delimiter    = str_contains( $object_type, '_' ) ? '_' : '-';
					$mapping_type = Helper::prepare_class_name( $delimiter, $object_type );

					$mapping_type = class_exists( 'DMS\\Includes\\Frontend\\Mapping_Objects\\' . $mapping_type . '_Mapper' ) ? $mapping_type : ucfirst( $object_type );
				}
				$class = 'DMS\\Includes\\Frontend\\Mapping_Objects\\' . $mapping_type . '_Mapper';
			}
			
			if ( class_exists( $class ) ) {
				$class = new $class( $mapping_value, $query );

				return apply_filters( 'dms_object_mapper', $class, $mapping_value, $query );
			}
		}
		
		return null;
	}
}