<?php

namespace DMS\Includes;

use DMS\Includes\Admin\Admin;
use DMS\Includes\Api\Server;
use DMS\Includes\Cron\Cron;
use DMS\Includes\Frontend\Frontend;
use DMS\Includes\Integrations\Integrations;
use DMS\Includes\Migrations\Migration;
/**
 * The file that defines the core plugin class
 *
 * @link
 *
 * @package    DMS
 * @subpackage DMS/includes
 */
/**
 * Main DMS class.
 *
 */
final class DMS {
    /**
     * The single instance of the class.
     */
    private static ?DMS $_instance = null;

    /**
     * The unique identifier of this plugin.
     *
     * @var      string $plugin_name
     */
    public string $plugin_name;

    /**
     * The current active plugin version folder/base_file
     *
     * @var      string $plugin_base_name
     */
    public string $plugin_base_name;

    /**
     * The unique identifier of this plugin's directory path.
     *
     * @var      string $plugin_dir_path
     */
    public string $plugin_dir_path;

    /**
     * The unique identifier of this plugin's directory url.
     *
     * @var      string $plugin_dir_url
     */
    public string $plugin_dir_url;

    /**
     * The current version of the plugin.
     *
     * @var      string $version
     */
    public string $version;

    /**
     * Admin class instance
     *
     * @var Admin
     */
    public Admin $admin;

    /**
     * Get debug value
     *
     * @var string|null
     */
    public static ?string $debug;

    /**
     * Frontend instance
     *
     * @var Frontend
     */
    public Frontend $frontend;

    /**
     * Array that keeps all the instances of the active integrations
     *
     * @var array $integrations
     */
    protected array $integrations;

    /**
     * Constructor
     *
     */
    private function __construct() {
        $this->set_params();
        $this->load_dependencies();
        if ( method_exists( $this, 'load_dependencies__premium_only' ) ) {
            $this->load_dependencies__premium_only();
        }
        $this->set_locale();
        $this->run_cron();
        $this->define_admin_classes();
        $this->run_integrations();
        $this->define_frontend();
        $this->run_migrations();
        $this->api_init();
    }

    /**
     * Set plugin related parameters (path, url, name, version, debug mode)
     *
     * @return void
     */
    public function set_params() : void {
        $this->version = DMS_VERSION;
        self::$debug = get_option( 'DMS_debug', true );
        $this->plugin_dir_path = plugin_dir_path( dirname( __FILE__ ) );
        $this->plugin_dir_url = plugin_dir_url( dirname( __FILE__ ) );
        $plugin_base_folder_name = basename( $this->plugin_dir_path );
        // No matter free or pro. Plugin name should be domain-mapping-system
        $this->plugin_name = rtrim( $plugin_base_folder_name, '-pro' );
        // Based on free or pro
        $this->plugin_base_name = $plugin_base_folder_name . '/dms.php';
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @return void
     */
    private function load_dependencies() : void {
        /**
         * Integrations
         */
        require_once $this->plugin_dir_path . 'includes/integrations/class-dms-integrations.php';
        require_once $this->plugin_dir_path . 'includes/integrations/buddyboss/class-dms-buddy-boss-platform.php';
        require_once $this->plugin_dir_path . 'includes/integrations/divi/class-dms-divi.php';
        require_once $this->plugin_dir_path . 'includes/integrations/woocommerce/class-dms-woocommerce.php';
        /**
         * Utils
         */
        require_once $this->plugin_dir_path . 'includes/utils/class-dms-helper.php';
        /**
         * Exceptions
         */
        require_once $this->plugin_dir_path . 'includes/exceptions/class-dms-exception.php';
        /**
         * Rest Api
         */
        require_once $this->plugin_dir_path . 'includes/api/class-dms-server.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-rest-controller.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-mappings-controller.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-mapping-values-controller.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-settings-controller.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-wp-object-groups-controller.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-wp-objects-controller.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-languages-controller.php';
        require_once $this->plugin_dir_path . 'includes/api/v1/controllers/class-dms-mapping-metas-controller.php';
        /**
         * Repositories
         */
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-mapping-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-mapping-value-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-setting-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-wp-object-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-wp-object-group-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-wp-object-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-wp-archive-object-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-wp-homepage-object-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-wp-post-object-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-wp-term-object-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-language-repository.php';
        require_once $this->plugin_dir_path . 'includes/repositories/class-dms-mapping-meta-repository.php';
        /**
         * Data objects
         */
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-data-object.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-mapping.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-mapping-value.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-setting.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-wp-object.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-wp-object-group.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-wp-archive-object-group.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-wp-homepage-post-object-group.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-wp-post-object-group.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-wp-term-object-group.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-language.php';
        require_once $this->plugin_dir_path . 'includes/data-objects/class-dms-mapping-meta.php';
        /**
         * Admin Classes
         */
        require_once $this->plugin_dir_path . 'includes/admin/class-dms-admin.php';
        require_once $this->plugin_dir_path . 'includes/admin/handlers/class-dms-subdomain-authentication-handler.php';
        require_once $this->plugin_dir_path . 'includes/admin/handlers/class-dms-alias-domain-authentication-handler.php';
        /**
         * Freemius
         */
        require_once $this->plugin_dir_path . 'includes/class-dms-fs.php';
        /**
         * Services
         */
        require_once $this->plugin_dir_path . 'includes/services/class-dms-unmapped-scenario-service.php';
        require_once $this->plugin_dir_path . 'includes/services/class-dms-request-params.php';
        require_once $this->plugin_dir_path . 'includes/services/class-dms-auth-service.php';
        /**
         * Factories
         */
        require_once $this->plugin_dir_path . 'includes/factories/class-dms-wp-object-repository-factory.php';
        require_once $this->plugin_dir_path . 'includes/factories/class-dms-wp-object-factory.php';
        /**
         * Frontend
         */
        require_once $this->plugin_dir_path . 'includes/frontend/class-dms-frontend.php';
        require_once $this->plugin_dir_path . 'includes/frontend/handlers/class-dms-uri-handler.php';
        require_once $this->plugin_dir_path . 'includes/frontend/handlers/class-dms-mapping-handler.php';
        require_once $this->plugin_dir_path . 'includes/frontend/handlers/class-dms-wp-queried-object-handler.php';
        require_once $this->plugin_dir_path . 'includes/frontend/scenarios/class-dms-mapping-scenario.php';
        require_once $this->plugin_dir_path . 'includes/frontend/scenarios/class-dms-mapping-scenario-interface.php';
        require_once $this->plugin_dir_path . 'includes/frontend/scenarios/class-dms-simple-object-mapping.php';
        require_once $this->plugin_dir_path . 'includes/frontend/scenarios/class-dms-posts-page-mapping.php';
        require_once $this->plugin_dir_path . 'includes/frontend/scenarios/class-dms-latest-posts-homepage-mapping.php';
        require_once $this->plugin_dir_path . 'includes/frontend/scenarios/class-dms-archive-mapping.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-mapper-interface.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-posts-page-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-latest-posts-homepage-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-archive-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-post-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-term-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-product-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-shop-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-divi-shop-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-tribe-events-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-wp-manga-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/object-map/class-dms-wcfm-store-mapper.php';
        require_once $this->plugin_dir_path . 'includes/frontend/class-dms-mapper-factory.php';
        /**
         * Migrations
         */
        require_once $this->plugin_dir_path . 'includes/migrations/class-dms-migration.php';
        /**
         * Cron
         */
        require_once $this->plugin_dir_path . 'includes/cron/class-dms-cron.php';
        require_once $this->plugin_dir_path . 'includes/cron/class-dms-fs-check-cron.php';
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @return void
     */
    private function set_locale() : void {
        add_action( 'init', function () {
            load_plugin_textdomain( $this->plugin_name, false, basename( $this->plugin_dir_path ) . '/languages' );
        } );
    }

    /**
     * Define admin classes
     *
     * @return void
     */
    public function define_admin_classes() : void {
        $this->admin = new Admin(
            $this->plugin_name,
            $this->plugin_dir_path,
            $this->plugin_dir_url,
            $this->version
        );
    }

    /**
     * Run crons
     * 
     * @return void
     */
    public function run_cron() {
        Cron::get_instance()->run();
    }

    /**
     * Define Frontend
     *
     * @return void
     */
    public function define_frontend() : void {
        $this->frontend = Frontend::get_instance(
            $this->plugin_dir_url,
            $this->plugin_name,
            $this->version,
            $this->plugin_dir_path
        );
    }

    /**
     * Main DMS Instance.
     *
     * @return DMS - Main instance.
     * @static
     */
    public static function get_instance() : DMS {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Run migrations
     *
     * @return void
     */
    public function run_migrations() : void {
        new Migration($this->version, $this->plugin_dir_path);
    }

    /**
     * Define controllers
     *
     * @return void
     */
    private function api_init() : void {
        Server::get_instance()->init();
    }

    /**
     * Run premium__only integrations
     *
     * @return void
     */
    public function run_integrations() : void {
        Integrations::instance()->run();
    }

    /**
     * Get debug value
     *
     * @return string|null
     */
    public static function get_debug() : ?string {
        return self::$debug;
    }

    /**
     * Get the plugin name
     *
     * @return    string
     */
    public function get_plugin_name() : string {
        return $this->plugin_name;
    }

    /**
     * Get the plugin version
     *
     * @return    string
     */
    public function get_version() : string {
        return $this->version;
    }

    /**
     * Get the plugin path
     *
     * @return    string
     */
    public function get_plugin_dir_path() : string {
        return $this->plugin_dir_path;
    }

    /**
     * Get plugin dir url
     * 
     * @return string
     */
    public function get_plugin_dir_url() : string {
        return $this->plugin_dir_url;
    }

}
