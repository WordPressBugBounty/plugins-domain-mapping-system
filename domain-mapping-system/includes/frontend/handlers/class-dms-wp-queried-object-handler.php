<?php

namespace DMS\Includes\Handlers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Frontend\Frontend;
use DMS\Includes\Frontend\Handlers\Global_Domain_Mapping_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Frontend\Services\Request_Params;
use DMS\Includes\Utils\Helper;
use Exception;
class WP_Queried_Object_Handler {
    /**
     * Throw 404 for unmapped pages
     */
    const UNMAPPED_PAGES_THROW_404 = 1;

    /**
     * Redirect to primary mapping
     */
    const UNMAPPED_PAGES_REDIRECT_TO_PRIMARY = 2;

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
     * @param Mapping_Handler $mapping_handler Mapping Handler
     * @param Request_Params $request_params Request params instance
     */
    public function __construct(
        Frontend $frontend,
        ?Global_Domain_Mapping_Handler $global_mapping_handler,
        Mapping_Handler $mapping_handler,
        Request_Params $request_params
    ) {
        $this->frontend = $frontend;
        $this->global_mapping_handler = $global_mapping_handler;
        $this->mapping_handler = $mapping_handler;
        $this->request_params = $request_params;
        add_action(
            'wp',
            array($this, 'catch_queried_object'),
            15,
            1
        );
    }

    /**
     * Catch queried object
     *
     *
     * @return void
     */
    public function catch_queried_object() : void {
        try {
            global $wp_query;
            $this->wp_query = $wp_query;
            if ( $this->frontend->is_dms_hosted() && !$this->mapping_handler->mapped && (!is_null( $this->global_mapping_handler ) && !$this->global_mapping_handler->mapped) ) {
                $this->run_unmapped_scenario( $wp_query );
            } elseif ( $this->frontend->is_dms_hosted() && !$this->mapping_handler->mapped && (!is_null( $this->global_mapping_handler ) && $this->global_mapping_handler->mapped) ) {
                if ( $wp_query->is_404() ) {
                    $this->run_unmapped_scenario( $wp_query );
                }
            }
        } catch ( Exception $exception ) {
            // If error was thrown show 404 not found
            Helper::log( $exception, __METHOD__ );
            // Do nothing ...
        }
    }

    /**
     * Case when dms hosted uri requested, but it's not actually mapped via dms.
     * 1. Throw 404
     * 2. Redirect to possible primary mapping
     */
    public function run_unmapped_scenario( $wp_query ) : void {
        $scenario_executed = false;
        if ( !empty( $this->frontend->unmapped_pages_handling ) ) {
            $unmapped_pages_handling_sc = Setting::find( 'dms_unmapped_pages_handling_sc' )->get_value();
            if ( (int) $unmapped_pages_handling_sc === self::UNMAPPED_PAGES_THROW_404 ) {
                if ( method_exists( $this, 'run_404_unmapped_scenario__premium_only' ) ) {
                    $scenario_executed = $this->run_404_unmapped_scenario__premium_only( $wp_query );
                }
            } elseif ( (int) $unmapped_pages_handling_sc === self::UNMAPPED_PAGES_REDIRECT_TO_PRIMARY ) {
                if ( method_exists( $this, 'run_redirection_unmapped_scenario__premium_only' ) ) {
                    $scenario_executed = $this->run_redirection_unmapped_scenario__premium_only( $wp_query );
                }
            }
        }
        if ( !$scenario_executed ) {
            // Run default unmapped scenario if unmapped page handling is not selected
            $this->run_default_unmapped_scenario();
        }
        // Do nothing ...
    }

    /**
     * Redirect to original host
     *
     * @return void
     */
    public function run_default_unmapped_scenario() : void {
        $original_host = Helper::get_base_host();
        $url = Helper::generate_url( $original_host, $this->request_params->path );
        Helper::redirect_to( $url );
        add_action( 'template_redirect', function () use($url) {
            Helper::redirect_to( $url );
        } );
    }

}
