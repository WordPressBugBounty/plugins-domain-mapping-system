<?php

namespace DMS\Includes\Api\V1\Controllers;

use DMS\Includes\Services\Auth_Service;
use WP_REST_Controller;
use WP_REST_Request;

abstract class Rest_Controller extends WP_REST_Controller {
	/**
	 * Namespace of DMS Rest controller
	 *
	 * @var string
	 */
	protected $namespace = 'dms/v1';

	/**
	 * Authorize request
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function authorize_request( WP_REST_Request $request ): bool {
		return ( new Auth_Service( $request ) )->authorize();
	}
}