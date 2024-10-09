<?php

namespace DMS\Includes\Api\V1\Controllers;

use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Repositories\Wp_Object_Group_Repository;
use DMS\Includes\Utils\Helper;
use Exception;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;
use WP_REST_Server;

class Wp_Object_Groups_Controller extends Rest_Controller {

	/**
	 * Rest base
	 *
	 * @var string
	 */
	protected $rest_base = 'object_groups';

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			'callback'            => array( $this, 'get_items' ),
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => array( $this, 'nonce_is_verified' ),
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
			$params = $request->get_params();
			$groups = ( new Wp_Object_Group_Repository() )->get_items( $params );
			if ( ! empty( $params['include'] ) && in_array( 'objects', $params['include'] ) ) {
				$groups = $this->include_objects( $groups, $params );
			}

			return rest_ensure_response( $groups );
		} catch ( Exception $e ) {
			Helper::log( $e, __METHOD__ );

			return Helper::get_wp_error( $e );
		}
	}

	/**
	 * Include objects
	 *
	 * @param $groups
	 * @param $params
	 *
	 * @return array
	 */
	public function include_objects( $groups, $params ) {
		$res         = [];
		$search_term = ! empty( $params['s'] ) ? sanitize_text_field( $params['s'] ) : '';
		$per_page    = ! empty( $params['per_page'] ) ? sanitize_text_field( $params['per_page'] ) : Setting::find( 'dms_values_per_mapping' )->get_value();
		$page        = ! empty( $params['page'] ) ? (int) $params['page'] : 1;

		foreach ( $groups as $group ) {
			$res[] = [
				'object_group' => $group,
				'_objects'     => $group->get_objects( $search_term, $per_page, $page )
			];
		}

		return $res;
	}
}
