<?php

namespace DMS\Includes\Frontend\Scenarios;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Frontend\Handlers\Force_Redirection_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Frontend\Mapping_Objects\Posts_Page_Mapper;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Utils\Helper;
class Posts_Page_Mapping extends Simple_Object_Mapping implements Mapping_Scenario_Interface {
    /**
     * @var string related Mapper classname
     */
    public static $mapper = Posts_Page_Mapper::class;

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
        if ( !Helper::is_posts_page_active() ) {
            // Posts page should be active to continue the checks
            return null;
        }
        $matched_mapping_value = null;
        $posts_page = Setting::find( 'page_for_posts' )->get_value();
        if ( !empty( $posts_page ) ) {
            $mapping = $mapping_handler->mapping;
            foreach ( $mapping_handler->mapping_values as $value ) {
                if ( $value->is_term() ) {
                    // Term case not needed at this stage
                    continue;
                }
                if ( $value->get_object_id() != $posts_page ) {
                    // Object id does not match with $posts_page
                    continue;
                }
                $value_link = get_permalink( $value->object_id );
                if ( empty( $value_link ) ) {
                    continue;
                }
                if ( $this->is_matched_mapping_value(
                    $request_params,
                    $mapping_handler,
                    $mapping,
                    $value,
                    $value_link
                ) ) {
                    $matched_mapping_value = $value;
                    break;
                }
            }
        }
        return $matched_mapping_value;
    }

}
