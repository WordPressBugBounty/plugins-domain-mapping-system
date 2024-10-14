<?php

namespace DMS\Includes\Frontend\Scenarios;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Handlers\Force_Redirection_Handler;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Utils\Helper;
class Simple_Object_Mapping implements Mapping_Scenario_Interface {
    /**
     * Check the scenario and return the corresponding mapping value
     * if not the following scenario return null
     *
     * @param Mapping_Handler $mapping_handler Mapping handler instance
     * @param Request_Params $request_params Request params instance
     *
     * @return null|Mapping_Value
     */
    public function object_mapped( Mapping_Handler $mapping_handler, Request_Params $request_params ) : ?Mapping_Value {
        $mapping = $mapping_handler->mapping;
        $matched_mapping_value = null;
        foreach ( $mapping_handler->mapping_values as $value ) {
            $value->object_id = (int) $value->object_id;
            $object_type = $value->object_type;
            $value_link = ( $object_type == 'post' ? get_permalink( $value->object_id ) : get_term_link( $value->object_id ) );
            if ( is_wp_error( $value_link ) ) {
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
        return $matched_mapping_value;
    }

    /**
     * Check if mapping value is match
     * 
     * @param  Request_Params  $request_params
     * @param  Mapping_Handler  $mapping_handler
     * @param  Mapping  $mapping
     * @param  Mapping_Value  $value
     * @param  string  $value_link
     *
     * @return bool
     */
    public function is_matched_mapping_value(
        Request_Params $request_params,
        Mapping_Handler $mapping_handler,
        Mapping $mapping,
        Mapping_Value $value,
        string $value_link
    ) {
        $value_path = wp_parse_url( $value_link, PHP_URL_PATH );
        $value_path = ( $value_path ? trim( wp_parse_url( $value_link, PHP_URL_PATH ), '/' ) : '' );
        $primary = $value->primary || count( $mapping_handler->mapping_values ) == 1;
        $path = trim( implode( '/', [$mapping->path, $value_path] ), '/' );
        if ( $primary && $request_params->path == $mapping->path ) {
            return true;
        } else {
            /**
             * If front page as a non-primary mapping then return false
             */
            if ( !empty( $value->get_object_id() ) && Helper::is_page_on_front( $value->get_object_id() ) ) {
                return false;
            }
            if ( strlen( $request_params->path ) === strlen( $path ) && Helper::path_starts_with( $request_params->path, $path ) ) {
                /**
                 * If the object is page, and it has parent page, then check for short slugs option. If enabled, then allow short child page scenario to handle this
                 */
                if ( get_post_type( $value->get_object_id() ) === 'page' && has_post_parent( $value->get_object_id() ) && !empty( $mapping_handler->frontend->short_child_page_urls ) ) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param  Force_Redirection_Handler  $force_redirection_handler
     * @param  Request_Params  $request_params
     *
     * @return bool
     */
    public function should_redirect( Force_Redirection_Handler $force_redirection_handler, Request_Params $request_params ) : bool {
        if ( is_null( $force_redirection_handler->object ) ) {
            return false;
        }
        /**
         * If the object is page, it has parent and short child mapping is active, then leave the scenario for short child mapping
         */
        if ( !empty( $force_redirection_handler->object->post_type ) && $force_redirection_handler->object->post_type === 'page' && !empty( $force_redirection_handler->object->post_parent ) && !empty( $force_redirection_handler->frontend->short_child_page_urls ) ) {
            return false;
        }
        return true;
    }

}
