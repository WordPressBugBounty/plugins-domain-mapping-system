<?php

namespace DMS\Includes\Integrations\WooCommerce;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Services\Unmapped_Scenario_Service;
use DMS\Includes\Utils\Helper;
class Woocommerce {
    /**
     * Instance of current class
     *
     * @var Woocommerce
     */
    public static Woocommerce $instance;

    /**
     * Flag for checking is the current page is account page
     *
     * @var bool
     */
    public bool $account_page = false;

    /**
     * Request params instance
     *
     * @var Request_Params
     */
    public Request_Params $request_params;

    /**
     * The mapping of current account page mapping
     *
     * @var Mapping|null
     */
    public ?Mapping $mapping = null;

    /**
     * The mapping value object of current account page mapping
     *
     * @var Mapping_Value|null
     */
    public ?Mapping_Value $mapping_value = null;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {
        $this->request_params = new Request_Params();
    }

    /**
     * Initializes the class and adds necessary filters.
     * If premium features are available, premium filters will also be added.
     *
     * @return void
     */
    public static function run() {
        $wc = self::get_instance();
        $wc->define_hooks();
        if ( method_exists( $wc, 'define_hooks__premium_only' ) ) {
            $wc->define_hooks__premium_only();
        }
    }

    /**
     * Retrieves the singleton instance of Woocommerce.
     *
     * @return Woocommerce
     */
    public static function get_instance() : Woocommerce {
        if ( !isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Adds core filters for mapping values.
     *
     * @return void
     */
    private function define_hooks() {
        add_filter(
            'dms_mapping_value',
            [$this, 'handle_prevent_mapping'],
            10,
            2
        );
    }

    /**
     * Check is mapping value primary
     *
     * @return bool
     */
    private function is_mapping_value_primary() : bool {
        // Return false if the mapping and mapping values are not set
        if ( empty( $this->mapping ) || empty( $this->mapping_value ) ) {
            return false;
        }
        // Return false when the mapping value is not the primary
        if ( !$this->mapping_value->get_primary() ) {
            return false;
        }
        // Return false if the path is empty
        if ( !empty( $this->mapping->get_path() ) ) {
            return false;
        }
        return true;
    }

    /**
     * Get whether the current page is account page
     *
     * @return bool
     */
    public function get_account_page() : bool {
        return $this->account_page;
    }

    /**
     * Set is account page
     *
     * @param bool $account_page
     *
     * @return void
     */
    public function set_account_page( bool $account_page ) : void {
        $this->account_page = $account_page;
    }

    /**
     * Checks if the current page is related to saving account details.
     *
     * @return bool
     */
    private function is_save_account_details_page() : bool {
        return !empty( $_POST['action'] ) && ($_POST['action'] == 'save_account_details' || $_POST['action'] == 'edit_address');
    }

    /**
     * Determines if the current page is the customer logout page based on the request path.
     *
     * @return bool
     */
    private function is_customer_logout_page() : bool {
        $substring = Setting::find( 'woocommerce_logout_endpoint' )->get_value() ?? 'customer-logout';
        return str_contains( $this->request_params->get_path(), $substring );
    }

    /**
     * Prevents mapping for account pages and processes scenarios.
     *
     * @param null|Mapping_Value $mapping_value
     * @param null|Mapping $mapping
     *
     * @return Mapping_Value|false
     */
    public function handle_prevent_mapping( ?Mapping_Value $mapping_value, ?Mapping $mapping ) {
        if ( empty( $mapping_value ) ) {
            return $mapping_value;
        }
        if ( Helper::is_account_page( $mapping_value ) ) {
            $this->set_account_page( true );
            $this->mapping = $mapping;
            $this->mapping_value = $mapping_value;
            if ( apply_filters( 'dms_wc_account_unmapped_scenario', true, $mapping ) ) {
                global $wp_query;
                add_filter( 'home_url', function () {
                    $base_host = Helper::get_base_host();
                    $base_path = Helper::get_base_path();
                    return Helper::generate_url( $base_host, $base_path );
                }, 10000 );
                Unmapped_Scenario_Service::get_instance()->process( $wp_query, true );
                return false;
            }
        }
        return $mapping_value;
    }

    /**
     * Check whether some form was submitted
     *
     * @return bool
     */
    private function is_form_submission() : bool {
        return !empty( $_POST['action'] ) || !empty( $_GET['action'] ) || !empty( $_POST['login'] );
    }

}
