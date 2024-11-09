<?php

namespace DMS\Includes\Api\V1\Controllers;

use DMS\Includes\Repositories\Language_Repository;
use DMS\Includes\Utils\Helper;
use Exception;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;
use WP_REST_Server;

class Languages_Controller extends Rest_Controller {

	/**
	 * The rest base
	 *
	 * @var string
	 */
	protected $rest_base = 'languages';

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'authorize_request' ),
			),
		) );
	}

	/**
	 * Get items
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_items( $request ) {
		try {
			$languages = ( new Language_Repository() )->get_items();

			return rest_ensure_response( $languages );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}
}