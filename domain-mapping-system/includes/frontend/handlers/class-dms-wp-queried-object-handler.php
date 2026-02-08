<?php

namespace DMS\Includes\Handlers;

use DMS\Includes\Frontend\Frontend;
use DMS\Includes\Frontend\Handlers\Global_Domain_Mapping_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Services\Unmapped_Scenario_Service;
use DMS\Includes\Utils\Helper;
use Exception;

class WP_Queried_Object_Handler {

	/**
	 * Frontend instance
	 *
	 * @var Frontend
	 */
	public Frontend $frontend;

	/**
	 * Global domain mapping handler instance
	 *
	 * @var mixed
	 */
	public ?object $global_mapping_handler;

	/**
	 * Mapping handler instance
	 *
	 * @var Mapping_Handler
	 */
	public Mapping_Handler $mapping_handler;

	/**
	 * WP query instance
	 *
	 * @var mixed
	 */
	public ?object $wp_query;

	/**
	 * The current object id
	 *
	 * @var null|int
	 */
	public ?int $object_id = null;

	/**
	 * The current object type
	 *
	 * @var null|string
	 */
	public ?string $object_type = null;

	/**
	 * Request params instance
	 *
	 * @var Request_Params
	 */
	public Request_Params $request_params;

	/**
	 * Constructor
	 *
	 * @param Frontend $frontend Frontend instance
	 * @param null|Global_Domain_Mapping_Handler $global_mapping_handler Global domain mapping instance on premium version
	 * @param null|Mapping_Handler $mapping_handler Mapping Handler
	 * @param Request_Params $request_params Request params instance
	 */
	public function __construct( Frontend $frontend, ?Global_Domain_Mapping_Handler $global_mapping_handler, ?Mapping_Handler $mapping_handler, Request_Params $request_params ) {
		$this->frontend               = $frontend;
		$this->global_mapping_handler = $global_mapping_handler;
		$this->mapping_handler        = $mapping_handler;
		$this->request_params         = $request_params;
		add_action( 'wp', array( $this, 'catch_queried_object' ), 15, 1 );
	}

	/**
	 * Catch queried object
	 *
	 *
	 * @return void
	 */
	public function catch_queried_object(): void {
		try {
			global $wp_query;
			$this->wp_query    = $wp_query;
			$unmapped_scenario = false;

			// Checks if dms hosted and the mapping handler didn't handle any mapping
			if ( $this->frontend->is_dms_hosted() && ! $this->mapping_handler->mapped ) {
				// Check global domain mapping emptiness
				if ( ! is_null( $this->global_mapping_handler ) ) {
					// Global mapping is incomplete
					if ( ! $this->global_mapping_handler->mapped ) {
						$unmapped_scenario = true;
					} //Global mapping is complete, check if 404 page
					elseif ( $wp_query->is_404() ) {
						$unmapped_scenario = true;
					}
				} else {
					$unmapped_scenario = true;
				}
			}

			$unmapped_scenario = apply_filters( 'dms_unmapped_scenario', $unmapped_scenario, $wp_query );

			if ( $unmapped_scenario ) {
				Unmapped_Scenario_Service::get_instance()->process( $wp_query );
			}
		} catch ( Exception $exception ) {
			// If error was thrown show 404 not found
			Helper::log( $exception, __METHOD__ );
			// Do nothing ...
		}
	}
}