<?php

namespace DMS\Includes\Admin\Handlers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Services\Unmapped_Scenario_Service;
use DMS\Includes\Utils\Helper;
class Alias_Domain_Authentication_Handler {
    /**
     * Holds the request parameters.
     *
     * @var Request_Params
     */
    public Request_Params $request_params;

    /**
     * Flag indicating if the current page is an unmapped scenario.
     *
     * @var bool
     */
    public bool $is_unmapped_scenario = false;

    /**
     * Base host.
     *
     * @var string
     */
    public string $original_host;

    /**
     * Constructor.
     *
     * @param Request_Params $request_params
     */
    public function __construct( Request_Params $request_params ) {
        $this->request_params = $request_params;
        $this->original_host = Helper::get_home_host();
        // Initialize premium hooks if they exist.
        if ( method_exists( $this, 'initialize_hooks__premium_only' ) ) {
            $this->initialize_hooks__premium_only();
        }
        // Initialize standard hooks.
        $this->initialize_hooks();
    }

    /**
     * Initializes standard hooks.
     *
     * @return void
     */
    public function initialize_hooks() : void {
        add_action( 'init', [$this, 'restrict_admin_access'] );
    }

    /**
     * Restricts admin access based on unmapped scenario logic.
     *
     * @return void
     */
    public function restrict_admin_access() : void {
        global $wp_query;
        if ( $this->is_unmapped_scenario = $this->is_unmapped_scenario() ) {
            Unmapped_Scenario_Service::get_instance()->process( $wp_query, true );
        }
    }

    /**
     * Determines whether the current request is an unmapped scenario.
     *
     * @return bool
     */
    public function is_unmapped_scenario() : bool {
        $is_unmapped_scenario = $this->original_host !== $this->request_params->get_domain();
        if ( $is_unmapped_scenario ) {
            return apply_filters( 'dms_is_unmapped_scenario', $is_unmapped_scenario );
        }
        return false;
    }

    public function is_alias_domain( $check_domain, $base_domain ) {
        // Check if domains are exactly the same
        if ( $base_domain === $check_domain ) {
            return false;
        }
        // Split domains into parts
        $base_domain_parts = explode( '.', $base_domain );
        $check_domain_parts = explode( '.', $check_domain );
        // Get the minimum number of parts between the two domains
        $minParts = min( count( $base_domain_parts ), count( $check_domain_parts ) );
        // Compare the last parts of both domains, starting from the root (TLD)
        for ($i = 1; $i <= $minParts; $i++) {
            if ( $base_domain_parts[count( $base_domain_parts ) - $i] !== $check_domain_parts[count( $check_domain_parts ) - $i] ) {
                return true;
                // The domains do not share the same base, so they are distinct
            }
        }
        return false;
    }

}
