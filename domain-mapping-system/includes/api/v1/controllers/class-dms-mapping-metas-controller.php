<?php

namespace DMS\Includes\Api\V1\Controllers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Repositories\Mapping_Meta_Repository;
use DMS\Includes\Utils\Helper;
use Exception;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;
use WP_REST_Server;

class Mapping_Metas_Controller extends Rest_Controller {

	/**
	 * Rest base
	 *
	 * @var string
	 */
	public $rest_base = 'mappings/(?P<mapping_id>[\d]+)/mapping_metas';

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/batch/', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'batch' ),
				'permission_callback' => array( $this, 'authorize_request' ),
				'args'                => $this->get_collection_params()
			),
		) );
	}

	/**
	 * Get collection params
	 *
	 * @return array[]
	 */
	public function get_collection_params() {
		return array(
			'mapping_id' => array(
				'required'          => true,
				'description'       => 'Mapping id',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_mapping_id' ),
			)
		);
	}

	/**
	 * Validate mapping id
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function validate_mapping_id( $value ) {
		return ! empty( Mapping::find( $value ) );
	}

	/**
	 * Batch mapping metas
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function batch( $request ) {
		try {
			$params     = $request->get_json_params();
			$mapping_id = $request->get_param( 'mapping_id' );
			$mappings   = ( new Mapping_Meta_Repository() )->batch( $mapping_id, $params );

			return rest_ensure_response( $mappings );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}
}