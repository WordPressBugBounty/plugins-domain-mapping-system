<?php

namespace DMS\Includes\Frontend\Handlers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Frontend\Frontend;
use DMS\Includes\Frontend\Mapper_Factory;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Utils\Helper;
use Exception;
class Mapping_Handler {
    /**
     * Matching mapping value
     *
     * @var Mapping_Value|null
     */
    public ?Mapping_Value $matching_mapping_value = null;

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
     * Flag for checking if there was mapping or not
     *
     * @var bool
     */
    public bool $mapped = false;

    /**
     * Matched mapping
     *
     * @var mixed
     */
    public ?Mapping $mapping;

    /**
     * Mapping values of the matching mapping
     *
     * @var array|null
     */
    public ?array $mapping_values;

    /**
     * Flag for checking if the domain and path matches with some mapping
     *
     * @var null|bool
     */
    public ?bool $domain_path_match = null;

    /**
     * URL to redirect to if a redirection is needed.
     *
     * @var string
     */
    public string $redirect_to = '';

    /**
     * Constructor
     *
     * @param Request_Params $request_params Request params instance
     * @param Frontend $frontend Frontend handler instance
     */
    public function __construct( Request_Params $request_params, Frontend $frontend ) {
        $this->frontend = $frontend;
        $this->request_params = $request_params;
        $this->define_hooks();
    }

    /**
     * Define hooks
     *
     * @return void
     */
    public function define_hooks() : void {
        add_action( 'pre_get_posts', array($this, 'run'), 9998 );
        add_action( 'redirect_canonical', array($this, 'prevent_canonical_redirection'), 9999 );
        add_filter(
            'wp_redirect',
            array($this, 'prevent_redirection'),
            9999,
            2
        );
    }

    /**
     * The main function which gets matching mapping and mapping value
     * During pre_get_posts hook, modifies the main query, and handles mapping
     *
     * @param $query
     *
     * @return object
     */
    public function run( $query ) : object {
        try {
            if ( $query->is_main_query() ) {
                // Prevent interference with Relevanssi search
                if ( $query->is_search() || !empty( $query->query_vars['relevanssi'] ) ) {
                    return $query;
                }
                $this->request_params->path = apply_filters( 'dms_request_params_path', $this->request_params->path );
                $this->mapping = Helper::matching_mapping_from_db( $this->request_params->get_domain(), $this->request_params->get_path() );
                $this->domain_path_match = $this->is_the_path_correct();
                if ( $this->domain_path_match ) {
                    $this->mapping_values = ( $this->mapping ? Mapping_Value::where( [
                        'mapping_id' => $this->mapping->id,
                    ] ) : [] );
                    if ( $this->mapping_values ) {
                        $request_params = apply_filters( 'dms_mapping_value_request_params', $this->request_params );
                        $mapping_value = $this->frontend->mapping_scenarios->run_object_mapped_scenario( $this, $request_params );
                        // Filter the mapping value
                        $mapping_value = apply_filters( 'dms_mapping_value', $mapping_value, $this->mapping );
                        if ( $mapping_value ) {
                            /**
                             * Extra check FS premium related
                             */
                            if ( !$this->frontend->get_fs_is_premium() && (!empty( $this->mapping->get_path() ) || empty( $mapping_value->get_primary() )) ) {
                                return $query;
                            }
                            /**
                             * Proceed with query overriding
                             */
                            $this->matching_mapping_value = $mapping_value;
                            $mapper = ( new Mapper_Factory() )->make( $this->matching_mapping_value, $query );
                            if ( !empty( $mapper ) ) {
                                $query = $mapper->get_query();
                                $this->mapped = true;
                                if ( method_exists( $this, 'add_customizations__premium_only' ) ) {
                                    $this->add_customizations__premium_only();
                                }
                            }
                        }
                    }
                }
            }
            return $query;
        } catch ( Exception $exception ) {
            Helper::log( $exception, __METHOD__ );
            return $query;
        }
    }

    /**
     * Check is the path incorrect, if so redirect to the right url
     *
     * @return bool
     */
    public function is_the_path_correct() {
        // Check if both mapping path and request path are not empty
        if ( !empty( $this->mapping->path ) && !empty( $this->request_params->path ) ) {
            $mapping_host = $this->mapping->host;
            $mapping_path = $this->mapping->path;
            $request_path = $this->request_params->path;
            // Check if the request path starts with the mapping path, case-insensitively
            if ( str_starts_with( strtolower( $request_path ), strtolower( $mapping_path ) ) ) {
                // Correct the case of the request path if necessary
                if ( !str_starts_with( $request_path, $mapping_path ) ) {
                    $corrected_path = str_replace( strtolower( $mapping_path ), $mapping_path, strtolower( $request_path ) );
                    $url = Helper::generate_url( $mapping_host, $corrected_path, $this->request_params->query_string );
                }
                // Redirect if the URL is set
                if ( !empty( $url ) ) {
                    $this->redirect_to = $url;
                    add_action( 'template_redirect', array($this, 'redirect_to_correct_url'), 1 );
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Redirect to the correct url
     *
     * @return void
     */
    public function redirect_to_correct_url() {
        if ( !empty( $this->redirect_to ) ) {
            Helper::redirect_to( $this->redirect_to );
        }
    }

    /**
     * Prepares value instance
     *
     * @param $id
     * @param $type
     *
     * @return Mapping_Value
     */
    public function prepare_value_instance( $id, $type ) : Mapping_Value {
        $data = array(
            'object_id'   => $id,
            'object_type' => $type,
        );
        return Mapping_Value::make( $data );
    }

    /**
     * Prevents redirection of the mapped page to canonical url
     *
     * @param $canonical
     *
     * @return false|mixed
     */
    public function prevent_canonical_redirection( $canonical ) : ?string {
        if ( $this->mapped || !empty( $this->frontend->global_mapping_handler ) && $this->frontend->global_mapping_handler->mapped ) {
            return null;
        }
        return $canonical;
    }

    /**
     * Prevent redirection added by other plugins
     *
     * @param $location
     * @param $status
     *
     * @return false|mixed
     */
    public function prevent_redirection( $location, $status ) {
        if ( $this->mapped || !empty( $this->frontend->global_mapping_handler ) && $this->frontend->global_mapping_handler->mapped ) {
            $should_redirect = apply_filters( 'dms_enable_redirect_for_mapped_pages', false, $location );
            if ( !$should_redirect ) {
                return false;
            }
        }
        return $location;
    }

}
