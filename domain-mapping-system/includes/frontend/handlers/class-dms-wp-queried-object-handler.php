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
                if ( method_exists( $this, 'run_unmapped_scenario__premium_only' ) ) {
                    $this->run_unmapped_scenario__premium_only( $wp_query );
                }
            } elseif ( $this->frontend->is_dms_hosted() && !$this->mapping_handler->mapped && (!is_null( $this->global_mapping_handler ) && $this->global_mapping_handler->mapped) ) {
                if ( $wp_query->is_404() ) {
                    if ( method_exists( $this, 'run_unmapped_scenario__premium_only' ) ) {
                        $this->run_unmapped_scenario__premium_only( $wp_query );
                    }
                }
            }
        } catch ( Exception $exception ) {
            // If error was thrown show 404 not found
            Helper::log( $exception, __METHOD__ );
            // Do nothing ...
        }
    }

}
