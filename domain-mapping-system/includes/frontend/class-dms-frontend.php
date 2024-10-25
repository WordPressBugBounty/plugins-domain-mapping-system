<?php

namespace DMS\Includes\Frontend;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Freemius;
use DMS\Includes\Frontend\Handlers\Mapping_Handler;
use DMS\Includes\Frontend\Handlers\URI_Handler;
use DMS\Includes\Frontend\Scenarios\Mapping_Scenario;
use DMS\Includes\Handlers\WP_Queried_Object_Handler;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Utils\Helper;
class Frontend {
    /**
     * Frontend instance
     *
     * @var Frontend|null
     */
    private static ?Frontend $_instance = null;

    /**
     * Keeps global domain mapping setting value
     *
     * @var string|null
     */
    public ?string $global_domain_mapping;

    /**
     * Keeps global archive mapping setting value
     *
     * @var string|null
     */
    public ?string $global_archive_mapping;

    /**
     * Keeps force site visitors setting value
     *
     * @var string|null
     */
    public ?string $force_site_visitors;

    /**
     * Unmapped pages handling
     *
     * @var string|null
     */
    public ?string $unmapped_pages_handling;

    /**
     * Keeps short child page urls setting value
     *
     * @var string|null
     */
    public ?string $short_child_page_urls;

    /**
     * Keeps global parent page mapping setting value
     *
     * @var string|null
     */
    public ?string $global_parent_mapping;

    /**
     * Keeps global shop mapping setting value
     *
     * @var string|null
     */
    public ?string $global_shop_mapping;

    /**
     * Keeps Request Params instance
     *
     * @var Request_Params
     */
    public Request_Params $request_params;

    /**
     * Keeps Mapping handler instance
     *
     * @var Mapping_Handler
     */
    public Mapping_Handler $mapping_handler;

    /**
     * Keeps URI_Handler instance
     *
     * @var URI_Handler
     */
    public URI_Handler $uri_handler;

    /**
     * Keeps Force Redirection Handler instance
     *
     * @var \DMS\Includes\Frontend\Handlers\Force_Redirection_Handler|null
     */
    public ?\DMS\Includes\Frontend\Handlers\Force_Redirection_Handler $force_redirection_handler = null;

    /**
     * Keeps Global Domain Mapping handler instance
     *
     * @var \DMS\Includes\Frontend\Handlers\Global_Domain_Mapping_Handler|null
     */
    public ?\DMS\Includes\Frontend\Handlers\Global_Domain_Mapping_Handler $global_mapping_handler = null;

    /**
     * Mapping Scenarios instance
     *
     * @var Mapping_Scenario
     */
    public Mapping_Scenario $mapping_scenarios;

    /**
     * Keeps Queried object handler instance
     *
     * @var WP_Queried_Object_Handler
     */
    public WP_Queried_Object_Handler $wp_queried_object_handler;

    /**
     * Plugin url
     *
     * @var string
     */
    public string $plugin_url;

    /**
     * Plugin name
     *
     * @var string
     */
    public string $plugin_name;

    /**
     * Plugin version
     *
     * @var string
     */
    public string $version;

    /**
     * Plugin path
     *
     * @var string
     */
    public string $plugin_dir_path;

    /**
     * Flag to know whether the current domain is mapped by DMS
     *
     * @var bool
     */
    public bool $dms_hosted = false;

    /**
     * @var bool 
     */
    private bool $fs_is_premium = false;

    /**
     * Constructor
     *
     * @param string $plugin_url Plugin url
     * @param string $plugin_name Plugin name
     * @param string $version Plugin version
     * @param string $plugin_dir_path Plugin dir path
     */
    public function __construct(
        string $plugin_url,
        string $plugin_name,
        string $version,
        string $plugin_dir_path
    ) {
        $this->set_plugin_params(
            $plugin_url,
            $plugin_name,
            $version,
            $plugin_dir_path
        );
        $this->set_options();
        $this->define_fs_premium_flag();
        $this->define_request_params();
        $this->define_whether_is_dms_hosted();
        $this->inject_main_dependencies();
    }

    /**
     * Inject our main dependencies.
     * Main logic is based on whether we are on a public side of the website.
     * Secondary logic is based whether requested domain is DMS hosted or is native.
     * 
     * @return void
     */
    public function inject_main_dependencies() {
        if ( Helper::is_frontend( $this->request_params->path ) ) {
            if ( $this->is_dms_hosted() ) {
                if ( method_exists( $this, 'handlers_init__premium_only' ) ) {
                    $this->handlers_init__premium_only();
                }
                $this->handlers_init();
            } else {
                if ( method_exists( $this, 'force_redirection_handler_init__premium_only' ) ) {
                    $this->force_redirection_handler_init__premium_only();
                }
            }
            $this->define_mapping_scenarios();
        }
    }

    /**
     * Sets plugin params
     * @param $plugin_url
     * @param $plugin_name
     * @param $version
     * @param $plugin_dir_path
     *
     * @return void
     */
    private function set_plugin_params(
        $plugin_url,
        $plugin_name,
        $version,
        $plugin_dir_path
    ) : void {
        $this->plugin_url = $plugin_url;
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->plugin_dir_path = $plugin_dir_path;
    }

    /**
     * @return void
     */
    private function define_fs_premium_flag() : void {
        $dms_fs_is_premium = Setting::find( Freemius::FS_IS_PREMIUM_OPTION_KEY )->get_value();
        if ( !isset( $dms_fs_is_premium ) ) {
            Helper::sync_fs_license();
            $dms_fs_is_premium = Setting::find( Freemius::FS_IS_PREMIUM_OPTION_KEY )->get_value();
        }
        $this->set_fs_is_premium( (bool) $dms_fs_is_premium );
    }

    /**
     * @param bool $fs_is_premium
     * @return void
     */
    public function set_fs_is_premium( bool $fs_is_premium ) {
        $this->fs_is_premium = $fs_is_premium;
    }

    /**
     * @return bool
     */
    public function get_fs_is_premium() : bool {
        return $this->fs_is_premium;
    }

    /**
     * Set options
     *
     * @return void
     */
    public function set_options() : void {
        $this->force_site_visitors = Setting::find( 'dms_force_site_visitors' )->get_value();
        $this->unmapped_pages_handling = Setting::find( 'dms_unmapped_pages_handling' )->get_value();
        $this->global_domain_mapping = Setting::find( 'dms_global_mapping' )->get_value();
        $this->global_archive_mapping = Setting::find( 'dms_archive_global_mapping' )->get_value();
        $this->short_child_page_urls = Setting::find( 'dms_remove_parent_page_slug_from_child' )->get_value();
        $this->global_parent_mapping = Setting::find( 'dms_global_parent_page_mapping' )->get_value();
        $this->global_shop_mapping = Setting::find( 'dms_woo_shop_global_mapping' )->get_value();
    }

    /**
     * Define request params
     *
     * @return void
     */
    public function define_request_params() : void {
        $this->request_params = new Request_Params();
    }

    /**
     * Define whether current host is mapped by DMS
     * 
     * @return void
     */
    private function define_whether_is_dms_hosted() {
        $mapping = Mapping::where( [
            'host' => $this->request_params->get_domain(),
        ] );
        $this->dms_hosted = !empty( $mapping[0] );
    }

    /**
     * Define Handlers
     *
     * @return void
     */
    public function handlers_init() : void {
        $this->mapping_handler = new Mapping_Handler($this->request_params, $this);
        $this->uri_handler = new URI_Handler($this->request_params, $this->mapping_handler, $this->global_mapping_handler);
        $this->wp_queried_object_handler = new WP_Queried_Object_Handler(
            $this,
            $this->global_mapping_handler,
            $this->mapping_handler,
            $this->request_params
        );
    }

    /**
     * Define mapping scenarios
     *
     * @return void
     */
    public function define_mapping_scenarios() : void {
        $this->mapping_scenarios = new Mapping_Scenario();
    }

    /**
     * Singleton
     *
     * @param string $plugin_url
     * @param string $plugin_name
     * @param string $version
     * @param string $plugin_dir_path
     *
     * @return Frontend
     */
    public static function get_instance(
        string $plugin_url = '',
        string $plugin_name = '',
        string $version = '',
        string $plugin_dir_path = ''
    ) : Frontend {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self(
                $plugin_url,
                $plugin_name,
                $version,
                $plugin_dir_path
            );
        }
        return self::$_instance;
    }

    /**
     * Checks whether the domain is DMS hosted or no
     * 
     * @return bool
     */
    public function is_dms_hosted() : bool {
        return $this->dms_hosted;
    }

}
