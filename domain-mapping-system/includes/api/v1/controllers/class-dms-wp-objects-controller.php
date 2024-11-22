<?php

namespace DMS\Includes\Api\V1\Controllers;

use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Factories\Wp_Object_Repository_Factory;
use DMS\Includes\Utils\Helper;
use Exception;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;
use WP_REST_Server;

class WP_Objects_Controller extends Rest_Controller {

	/**
	 * REST base
	 *
	 * @var string
	 */
	protected $rest_base = 'object_groups/(?P<group_name>[a-zA-Z0-9_.-]+)/objects';

	/**
	 * Register REST routes for the objects endpoint.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Route for retrieving all objects
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			'callback'            => array( $this, 'get_items' ),
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => array( $this, 'authorize_request' ),
		) );
	}

	/**
	 * Retrieve list of items
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_items( $request ) {
		try {
			$params            = $request->get_json_params();
			$group_name        = ! empty( $params['group_name'] ) ? sanitize_text_field( $params['group_name'] ) : '';
			$search            = ! empty( $params['s'] ) ? sanitize_text_field( $params['s'] ) : '';
			$per_page          = ! empty( $params['per_page'] ) ? (int) $params['per_page'] : Setting::find( 'dms_values_per_mapping' )->get_value();
			$page              = ! empty( $params['page'] ) ? (int) $params['page'] : 1;
			$object_repository = ( new Wp_Object_Repository_Factory )->make( $group_name );

			// Fetch and return the object groups based on the provided parameters
			$objects = $object_repository->get_items( $group_name, $search, $per_page, $page );

			return rest_ensure_response( $objects );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}
}
