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
        add_filter(
            'allowed_redirect_hosts',
            [$this, 'allow_mapped_redirect_hosts'],
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
     * Allows WordPress safe redirects to target mapped domains managed by DMS.
     *
     * WooCommerce validates login redirects with wp_safe_redirect(). Without the mapped host
     * in this list, a valid checkout redirect on the mapped domain can fall back to My Account.
     *
     * @param array  $hosts Allowed redirect hosts.
     * @param string $host  Requested redirect host.
     *
     * @return array
     */
    public function allow_mapped_redirect_hosts( array $hosts, string $host ) : array {
        $host = $this->normalize_redirect_host( $host );
        if ( !empty( $host ) && $this->is_mapped_redirect_host( $host ) ) {
            $hosts[] = $host;
        }
        return array_values( array_unique( array_filter( $hosts ) ) );
    }

    /**
     * Checks whether the requested redirect host is managed by DMS.
     *
     * @param string $host Redirect host.
     *
     * @return bool
     */
    private function is_mapped_redirect_host( string $host ) : bool {
        if ( $host === $this->normalize_redirect_host( $this->request_params->get_base_host() ) ) {
            return true;
        }
        return !empty( Mapping::where( [
            'host' => $host,
        ], null, 1 ) );
    }

    /**
     * Normalizes a host for allowed_redirect_hosts comparison.
     *
     * @param string|null $host Host value.
     *
     * @return string
     */
    private function normalize_redirect_host( ?string $host ) : string {
        $host = strtolower( trim( (string) $host ) );
        if ( empty( $host ) ) {
            return '';
        }
        $parsed_host = wp_parse_url( ( str_contains( $host, '://' ) ? $host : 'http://' . $host ), PHP_URL_HOST );
        return ( !empty( $parsed_host ) ? $parsed_host : preg_replace( '/:\\d+$/', '', $host ) );
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
     * Checks if the current request is a WooCommerce login form submission with an explicit redirect.
     *
     * @return bool
     */
    private function is_login_redirect_submission() : bool {
        return !empty( $_POST['login'] ) && (!empty( $_POST['redirect'] ) || !empty( $_POST['redirect_to'] ));
    }

    /**
     * Checks if the current request is a WooCommerce login redirect to an allowed location.
     *
     * @param string $location Redirect location.
     *
     * @return bool
     */
    private function is_allowed_login_redirect_submission( string $location ) : bool {
        if ( !$this->is_login_redirect_submission() ) {
            return false;
        }
        $host = $this->get_redirect_location_host( $location );
        return empty( $host ) || $this->is_mapped_redirect_host( $host );
    }

    /**
     * Gets the host from a redirect location.
     *
     * @param string $location Redirect location.
     *
     * @return string
     */
    private function get_redirect_location_host( string $location ) : string {
        $location = trim( $location );
        if ( empty( $location ) ) {
            return '';
        }
        $host = wp_parse_url( $location, PHP_URL_HOST );
        return ( !empty( $host ) ? $this->normalize_redirect_host( $host ) : '' );
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
