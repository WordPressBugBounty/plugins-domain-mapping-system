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
	 * @param  Mapping_Value  $mapping_value  Mapping value instance
	 * @param  WP_Query  $query  WP_Query instance
	 *
	 * @return mixed|null
	 */
	public function make( Mapping_Value $mapping_value, WP_Query $query ) {
		$object_id   = $mapping_value->object_id;
		$object_type = $mapping_value->object_type;

		// Check for matched scenario
		if ( ! isset( DMS()->frontend->mapping_scenarios->matched_scenario ) ) {
			return null;
		}

		$scenario = DMS()->frontend->mapping_scenarios->matched_scenario;
		$class    = $this->determine_mapper_class( $scenario, $object_id, $object_type );
		if ( $class && class_exists( $class ) ) {
			$instance = new $class( $mapping_value, $query );

			return apply_filters( 'dms_object_mapper', $instance, $mapping_value, $query );
		}

		return null;
	}

	/**
	 * Determine the appropriate mapper class based on the scenario, object ID, and object type.
	 *
	 * @param  object|null  $scenario  Mapping scenario instance
	 * @param  int|null  $object_id  Object ID
	 * @param  string|null  $object_type  Object type
	 *
	 * @return string|null
	 */
	private function determine_mapper_class( ?object $scenario, ?int $object_id, ?string $object_type ): ?string {
		// Handle predefined scenarios
		if ( $scenario instanceof Posts_Page_Mapping ||
		     $scenario instanceof Latest_Posts_Homepage_Mapping ||
		     $scenario instanceof Archive_Mapping ) {
			$mapper_classname = Helper::get_class_shortname( $scenario::$mapper );

			return 'DMS\\Includes\\Frontend\\Mapping_Objects\\' . $mapper_classname;
		}

		// Handle shop page mapping
		if ( Helper::get_shop_page_association() === $object_id && $object_type === 'post' ) {
			return 'DMS\\Includes\\Frontend\\Mapping_Objects\\Shop_Mapper';
		}

		// Determine mapper class based on object type
		$mapping_type = $this->resolve_mapping_type( $object_id, $object_type );

		return $mapping_type ? 'DMS\\Includes\\Frontend\\Mapping_Objects\\' . $mapping_type . '_Mapper' : null;
	}


	/**
	 * Resolve the mapping type based on the object ID and object type.
	 *
	 * @param  int|null  $object_id  Object ID
	 * @param  string|null  $object_type  Object type
	 *
	 * @return string|null
	 */
	private function resolve_mapping_type( ?int $object_id, ?string $object_type ): ?string {
		if ( $object_type === 'post' ) {
			$post = get_post( $object_id );
			if ( ! $post ) {
				return null; // Ensure valid post object
			}

			$post_type = $post->post_type;

			if ( ! in_array( $post_type, [ 'post', 'page' ] ) ) {
				return $this->prepare_mapping_type( $post_type );
			}

			return ucfirst( $object_type );
		}

		return $this->prepare_mapping_type( $object_type );
	}


	/**
	 * Prepares the mapping type by transforming the given type string.
	 *
	 * The method checks for delimiters ('_' or '-') in the type string and transforms it
	 * into a class name format. It then checks if a corresponding mapper class exists.
	 *
	 * @param  string|null  $type  The object type to be transformed.
	 *
	 * @return string  The prepared mapping type.
	 */
	private function prepare_mapping_type( ?string $type ): string {
		if ( empty( $type ) ) {
			$type = '';
		}
		$delimiter    = str_contains( $type, '_' ) ? '_' : ( str_contains( $type, '-' ) ? '-' : '' );
		$mapping_type = $delimiter ? Helper::prepare_class_name( $delimiter, $type ) : ucfirst( $type );

		return class_exists( 'DMS\\Includes\\Frontend\\Mapping_Objects\\' . $mapping_type . '_Mapper' )
			? $mapping_type
			: ucfirst( $type );
	}

}