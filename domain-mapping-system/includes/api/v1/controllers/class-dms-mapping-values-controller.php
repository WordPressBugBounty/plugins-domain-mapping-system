<?php

namespace DMS\Includes\Api\V1\Controllers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Repositories\Mapping_Value_Repository;
use DMS\Includes\Utils\Helper;
use Exception;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;
use WP_REST_Server;

class Mapping_Values_Controller extends Rest_Controller {

	/**
	 * Rest endpoint
	 */
	const REST_ENDPOINT = 'values';

	/**
	 * Mapping rest base
	 *
	 * @var string
	 */
	protected string $mapping_rest_base = 'mappings/(?P<mapping_id>[\d]+)/values';

	/**
	 * Mapping generic rest base
	 *
	 * @var string
	 */
	protected string $generic_rest_base = 'mapping_values';

	/**
	 * Register rest routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route( $this->namespace, '/' . $this->generic_rest_base . '/batch/', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'batch' ),
				'permission_callback' => array( $this, 'authorize_request' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->mapping_rest_base . '/', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'authorize_request' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_items' ),
				'permission_callback' => array( $this, 'authorize_request' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'create_item' ),
				'args'                => $this->get_collection_params(),
				'permission_callback' => array( $this, 'authorize_request' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->generic_rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'authorize_request' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'authorize_request' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'args'                => $this->get_collection_params(),
				'permission_callback' => array( $this, 'authorize_request' ),
			),
		) );
	}

	/**
	 * Collection params
	 *
	 * @return array[]
	 */
	public function get_collection_params(): array {
		return array(
			'object_id'   => array(
				'description'       => 'ID of the mapped object (e.g., term or post).',
				'type'              => array( 'integer', 'null' ),
				// TODO understand WP rest following behaviour, when required is true and missing_params error is being thrown on null value. Even though the null type is allowed
				'required'          => false,
				'sanitize_callback' => array( $this, 'sanitize_object_id' ),
				'validate_callback' => array( $this, 'validate_object_id' ),
				'default'           => null,
			),
			'mapping_id'  => array(
				'description'       => 'ID of the mapped object (e.g., term or post).',
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_mapping_id' ),
			),
			'object_type' => array(
				'description'       => 'Type of the mapped object (e.g., term or post).',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_object_type' ),
			),
			'primary'     => array(
				'description'       => 'The type of mapping (primary or secondary)',
				'type'              => array( 'integer', 'null' ),
				'required'          => false,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Batch callback
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function batch( $request ) {
		try {
			$mapping_values = ( new Mapping_Value_Repository() )->batch( $request->get_params() );
			$mapping_values = $this->prepare_data( $mapping_values );

			return rest_ensure_response( $mapping_values );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Add mapping value Wp_Object, mapped_link to the response
	 *
	 * @param $values
	 *
	 * @return array
	 */
	private function prepare_data( $values ) {
		foreach ( $values as $key => $value ) {
			$values[ $key ]['data'] = $this->prepare_item( $value['data'], [ 'object', 'mapped_link' ] );
		}

		return $values;
	}

	/**
	 * Get Mapping Values
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_items( $request ) {
		try {
			$limit          = (int) $request->get_param( 'per_page' );
			$start          = (int) $request->get_param( 'paged' );
			$mapping_id     = $request->get_param( 'mapping_id' );
			$mapping_values = Mapping_Value::where( [ 'mapping_id' => $mapping_id ], $start, $limit );
			$include        = $request->get_param( 'include' );
			$mapping_values = $this->prepare_item( $mapping_values, $include );
			$mapping_values = $this->prepare_total_count( $mapping_values, $mapping_id );

			return rest_ensure_response( $mapping_values );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Prepare item object
	 *
	 * @param $mapping_values
	 * @param $include
	 *
	 * @return array
	 */
	public static function prepare_item( $mapping_values, $include ): array {
		$prepared_values = [];
		if ( $mapping_values instanceof Mapping_Value ) {
			$mapping_values = array( $mapping_values );
		}
		foreach ( $mapping_values as $value ) {
			if ( ! $value instanceof Mapping_Value ) {
				continue;
			}
			$item = array( 'value' => $value );
			if ( ! empty( $include ) && in_array( 'object', $include ) ) {
				$object          = $value->get_wp_object();
				$item['_object'] = $object;
			}
			if ( ! empty( $include ) && in_array( 'mapped_link', $include ) ) {
				$mapped_link          = $value->get_mapped_link();
				$item['_mapped_link'] = $mapped_link;
			}
			$prepared_values[] = $item;
		}

		return $prepared_values;
	}

	/**
	 * Prepare total count of mapping values
	 *
	 * @param array $mapping_values
	 * @param int $mapping_id
	 *
	 * @return array
	 */
	public static function prepare_total_count( array $mapping_values, int $mapping_id ): array {
		if ( Helper::is_dms_error( $mapping_values ) ) {
			return $mapping_values;
		}

		return [
			'items'  => $mapping_values,
			'_total' => Mapping_Value::count( [ 'mapping_id' => $mapping_id ] )
		];
	}

	/**
	 * Delete multiple mapping values by mapping id
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function delete_items( $request ) {
		try {
			$id    = $request->get_param( 'mapping_id' );
			$count = $request->get_param( 'count' );
			$res   = ( new Mapping_Value_Repository() )->delete_items( $id, $count );

			return rest_ensure_response( $res );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Create new mapping value
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function create_item( $request ) {
		try {
			$mapping_id    = $request->get_param( 'mapping_id' );
			$mapping_value = ( new Mapping_Value_Repository() )->create( $mapping_id, $request->get_json_params() );

			return rest_ensure_response( $mapping_value );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Get mapping value
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_item( $request ) {
		try {
			$id            = $request->get_param( 'id' );
			$mapping_value = Mapping_Value::find( $id );

			return rest_ensure_response( $mapping_value );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Delete mapping value
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function delete_item( $request ) {
		try {
			$id            = $request->get_param( 'id' );
			$mapping_value = Mapping_Value::delete( $id );

			return rest_ensure_response( $mapping_value );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Update mapping value
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function update_item( $request ) {
		try {
			$id            = $request->get_param( 'id' );
			$mapping_value = Mapping_Value::update( $id, $request->get_json_params() );

			return rest_ensure_response( $mapping_value );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Validate mapping id
	 *
	 * @param int $value
	 *
	 * @return WP_Error|int
	 * @throws DMS_Exception
	 */
	public function validate_mapping_id( int $value ) {
		if ( ! Mapping::find( $value ) instanceof Mapping ) {
			return new WP_Error( 'rest_object_id_error', 'Mapping does not exist', [ 'status' => 400 ] );
		}

		return $value;
	}

	/**
	 * Validate object id
	 *
	 * @param int|null $value
	 *
	 * @return int|null|WP_Error
	 */
	public function validate_object_id( ?int $value ) {
		if ( ! is_null( $value ) && ! is_numeric( $value ) ) {
			return new WP_Error( 'rest_invalid_object_id', 'Invalid object ID', [ 'status' => 400 ] );
		}
		$is_validated = term_exists( $value, get_taxonomies() ) || ! empty( get_post( $value ) );
		if ( ! apply_filters( 'dms_validate_object_id', $is_validated, $value ) ) {
			return new WP_Error( 'rest_object_not_found', 'Object not found', [ 'status' => 400 ] );
		}

		return $value;
	}

	/**
	 * Sanitize object id
	 *
	 * @param int|null $value
	 *
	 * @return int|null
	 */
	public function sanitize_object_id( ?int $value ): ?int {
		return is_numeric( $value ) ? (int) $value : null;
	}

	/**
	 * Validate object type
	 *
	 * @param string $value
	 * @param $request
	 *
	 * @return WP_Error|string
	 */
	public function validate_object_type( string $value, $request ) {
		$allowed_values = apply_filters( 'dms_allowed_object_types', [ 'term', 'post', 'posts_homepage' ] );
		if ( ! in_array( $value, $allowed_values ) ) {
			if ( ! empty( get_post_type_object( $value ) ) && ! empty( Setting::find( 'dms_use_' . $value . '_archive' )->get_value() ) ) {
				return $value;
			}

			return new WP_Error( 'rest_invalid_object_type', 'Invalid object type', [ 'status' => 400 ] );
		}

		return $value;
	}

	/**
	 * Item schema
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the mapping value.', 'domain-mapping-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'mapping_id'  => array(
					'description' => __( 'ID of the associated mapping.', 'domain-mapping-system' ),
					'type'        => 'integer',
					'context'     => array( 'edit' ),
					'required'    => true,
				),
				'object_id'   => array(
					'description'       => __( 'ID of the mapped object (e.g., term or post).', 'domain-mapping-system' ),
					'type'              => array( 'integer', 'null' ),
					'context'           => array( 'edit' ),
					'required'          => false,
					'sanitize_callback' => array( $this, 'sanitize_object_id' ),
					'default'           => null,
				),
				'object_type' => array(
					'description'       => __( 'Type of the mapped object (e.g., term or post).', 'domain-mapping-system' ),
					'type'              => 'string',
					'context'           => array( 'edit' ),
					'required'          => true,
					'enum'              => array( 'term', 'post' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'primary'     => array(
					'description'       => __( 'Type of mapping (primary or secondary).', 'domain-mapping-system' ),
					'type'              => array( 'integer', 'null' ),
					'context'           => array( 'edit' ),
					'sanitize_callback' => 'absint',
				),
			),
		);
	}
}