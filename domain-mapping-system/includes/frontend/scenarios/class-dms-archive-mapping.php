<?php

namespace DMS\Includes\Frontend\Scenarios;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Handlers\Force_Redirection_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Frontend\Mapping_Objects\Archive_Mapper;
use DMS\Includes\Frontend\Services\Request_Params;
use DMS\Includes\Utils\Helper;
class Archive_Mapping extends Simple_Object_Mapping implements Mapping_Scenario_Interface {
    /**
     * @var string related Mapper class
     */
    public static $mapper = Archive_Mapper::class;

    /**
     * Check the scenario and return the corresponding mapping value
     * if not the following scenario return null
     *
     * @param  Mapping_Handler  $mapping_handler  Mapping handler instance
     * @param  Request_Params  $request_params  Request params instance
     *
     * @return null|Mapping_Value
     */
    public function object_mapped( Mapping_Handler $mapping_handler, Request_Params $request_params ) : ?Mapping_Value {
        $mapping = $mapping_handler->mapping;
        $matched_mapping_value = null;
        $custom_post_types = Helper::get_custom_post_types();
        foreach ( $mapping_handler->mapping_values as $value ) {
            if ( empty( get_post_type_object( $value->object_type ) ) || !empty( $value->object_id ) || !in_array( $value->object_type, $custom_post_types ) ) {
                continue;
            }
            $post_type = $value->object_type;
            $archive_link = get_post_type_archive_link( $post_type );
            if ( empty( $archive_link ) ) {
                continue;
            }
            if ( $this->is_matched_mapping_value(
                $request_params,
                $mapping_handler,
                $mapping,
                $value,
                $archive_link
            ) ) {
                $matched_mapping_value = $value;
                break;
            }
        }
        return $matched_mapping_value;
    }

}
