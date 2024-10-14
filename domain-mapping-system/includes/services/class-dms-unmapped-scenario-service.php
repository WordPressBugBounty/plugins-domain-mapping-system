<?php

namespace DMS\Includes\Services;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Frontend\Frontend;
use DMS\Includes\Utils\Helper;
class Unmapped_Scenario_Service {
    /**
     * Throw 404 for unmapped pages
     */
    const UNMAPPED_PAGES_THROW_404 = 1;

    /**
     * Redirect to primary mapping
     */
    const UNMAPPED_PAGES_REDIRECT_TO_PRIMARY = 2;

    /**
     * Self instance
     *
     * @var
     */
    public static $_instance;

    /**
     * Request params instance
     *
     * @var Request_Params
     */
    public Request_Params $request_params;

    /**
     * Frontend instance
     *
     * @var Frontend
     */
    public Frontend $frontend;

    /**
     * Constructor
     */
    public function __construct() {
        $this->frontend = Frontend::get_instance();
        $this->request_params = new Request_Params();
    }

    /**
     * Singleton pattern
     *
     * @return self
     */
    public static function get_instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Case when dms hosted uri requested, but it's not actually mapped via dms.
     * 1. Throw 404
     * 2. Redirect to possible primary mapping
     */
    public function process( $wp_query, $is_admin = false ) : void {
        $scenario_executed = false;
        if ( !empty( $this->frontend->unmapped_pages_handling ) ) {
            $unmapped_pages_handling_sc = Setting::find( 'dms_unmapped_pages_handling_sc' )->get_value();
            if ( (int) $unmapped_pages_handling_sc === self::UNMAPPED_PAGES_THROW_404 ) {
                if ( method_exists( $this, 'run_404_unmapped_scenario__premium_only' ) ) {
                    $scenario_executed = $this->run_404_unmapped_scenario__premium_only( $wp_query, $is_admin );
                }
            } elseif ( (int) $unmapped_pages_handling_sc === self::UNMAPPED_PAGES_REDIRECT_TO_PRIMARY ) {
                if ( method_exists( $this, 'run_redirection_unmapped_scenario__premium_only' ) ) {
                    $scenario_executed = $this->run_redirection_unmapped_scenario__premium_only( $is_admin );
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
        $original_host = Helper::get_home_host();
        if ( $original_host !== $this->request_params->get_domain() ) {
            $url = Helper::generate_url( $original_host, $this->request_params->path );
            Helper::redirect_to( $url );
        }
    }

}
