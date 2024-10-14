<?php

namespace DMS\Includes\Frontend\Scenarios;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Handlers\Force_Redirection_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Services\Request_Params;
class Mapping_Scenario {
    /**
     * The array of Scenario classes
     *
     * @var string[]
     */
    public array $list;

    /**
     * @var Mapping_Scenario_Interface
     */
    public Mapping_Scenario_Interface $matched_scenario;

    /**
     * Define mapping scenarios
     */
    public function __construct() {
        $this->list = array(
            'DMS\\Includes\\Frontend\\Scenarios\\Latest_Posts_Homepage_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Posts_Page_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Simple_Object_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Archive_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Global_Term_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Global_Archive_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Global_Parent_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Short_Child_Page_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Shop_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Global_Product_Mapping',
            'DMS\\Includes\\Frontend\\Scenarios\\Global_Mapping'
        );
    }

    /**
     * Loop through mapping scenarios
     *
     * @param  Mapping_Handler  $mapping_handler  Mapping handler instance
     * @param  Request_Params  $request_params  Request params instance
     *
     * @return null|Mapping_Value
     */
    public function run_object_mapped_scenario( Mapping_Handler $mapping_handler, Request_Params $request_params ) : ?Mapping_Value {
        $list = apply_filters( 'dms_mapping_scenarios_list', $this->list );
        $this->remove_uri_filters( $mapping_handler );
        foreach ( $list as $scenario ) {
            if ( class_exists( $scenario ) ) {
                $scenario_instance = new $scenario();
                if ( $mapping_value = $scenario_instance->object_mapped( $mapping_handler, $request_params ) ) {
                    $this->matched_scenario = $scenario_instance;
                    break;
                }
            }
        }
        $this->add_uri_filters( $mapping_handler );
        return $mapping_value ?? null;
    }

    /**
     * Tmp remove uri filters to easily work with permalinks methods, which could be overridden
     * 
     * @param  Mapping_Handler  $mapping_handler
     *
     * @return void
     */
    public function remove_uri_filters( Mapping_Handler $mapping_handler ) {
        if ( isset( $mapping_handler->frontend->uri_handler ) && method_exists( $mapping_handler->frontend->uri_handler, 'remove_uri_filters__premium_only' ) ) {
            $mapping_handler->frontend->uri_handler->remove_uri_filters__premium_only();
        }
    }

    /**
     * Add back the filter previously removed by self::remove_uri_filters_before_handling_scenarios() method
     * 
     * @param  Mapping_Handler  $mapping_handler
     *
     * @return void
     */
    public function add_uri_filters( Mapping_Handler $mapping_handler ) {
        if ( isset( $mapping_handler->frontend->uri_handler ) && method_exists( $mapping_handler->frontend->uri_handler, 'prepare_uri_filters__premium_only' ) ) {
            $mapping_handler->frontend->uri_handler->prepare_uri_filters__premium_only();
        }
    }

}
