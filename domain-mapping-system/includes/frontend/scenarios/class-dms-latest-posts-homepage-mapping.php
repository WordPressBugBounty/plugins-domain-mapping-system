<?php

namespace DMS\Includes\Frontend\Scenarios;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Handlers\Force_Redirection_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Frontend\Mapping_Objects\Latest_Posts_Homepage_Mapper;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Utils\Helper;
class Latest_Posts_Homepage_Mapping implements Mapping_Scenario_Interface {
    /**
     * @var string related Mapper classname
     */
    public static $mapper = Latest_Posts_Homepage_Mapper::class;

    /**
     * Check the scenario and return the corresponding mapping value
     * if not the following scenario return null
     *
     * @param  Mapping_Handler  $mapping_handler  Mapping handler instance
     * @param  Request_Params  $request_params  Request params instance
     *
     * @return null|Mapping_Value
     */
    function object_mapped( Mapping_Handler $mapping_handler, Request_Params $request_params ) : ?Mapping_Value {
        if ( !Helper::is_latest_posts_homepage_active() ) {
            // Posts page should be active to continue the checks
            return null;
        }
        $matched_mapping_value = null;
        $mapping = $mapping_handler->mapping;
        foreach ( $mapping_handler->mapping_values as $value ) {
            if ( empty( $value->get_primary() ) || $value->get_object_type() !== Mapping_Value::OBJECT_TYPE_POSTS_HOMEPAGE ) {
                // Value only should be -1 for homepage case and should be primary value
                continue;
            }
            $value_link = get_home_url();
            if ( empty( $value_link ) ) {
                continue;
            }
            $value_path = wp_parse_url( $value_link, PHP_URL_PATH );
            $value_path = ( !empty( $value_path ) ? trim( $value_path, '/' ) : '' );
            // If homepage selected as mapping value it should be primary only
            if ( $request_params->domain == $mapping->host && trim( $request_params->path, '/' ) == trim( $mapping->path . $value_path, '/' ) ) {
                $matched_mapping_value = $value;
                break;
            }
        }
        return $matched_mapping_value;
    }

}
