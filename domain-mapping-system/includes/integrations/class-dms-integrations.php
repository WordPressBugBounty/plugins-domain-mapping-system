<?php

namespace DMS\Includes\Integrations;

use DMS\Includes\Integrations\BuddyBoss\BuddyBoss_Platform;
use DMS\Includes\Integrations\Divi\Divi;
use DMS\Includes\Integrations\SEO\Yoast\Seo_Yoast;
use DMS\Includes\Integrations\WCFM\WCFM;
use DMS\Includes\Utils\Helper;
class Integrations {
    /**
     * Keeps the instance of current class
     *
     * @var Integrations
     */
    private static Integrations $instance;

    /**
     * Indicates whether the yoast seo integration active
     *
     * @var bool
     */
    public bool $yoast_seo;

    /**
     * Indicates whether the wcfm integration active
     *
     * @var bool
     */
    public bool $wcfm;

    /**
     * Indicates whether the buddy boss integration active
     * @var bool
     */
    public bool $buddy_boss;

    /**
     * Divi instance
     *
     * @var
     */
    public $divi;

    /**
     * Singleton pattern
     *
     * @return Integrations
     */
    public static function instance() : Integrations {
        if ( !isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize integrations
     *
     * @return void
     */
    public function initialize_integrations() : void {
        // Initialize free integrations
        $this->buddy_boss = $this->initialize_buddypboss_integration();
        $this->divi = $this->initialize_divi_integration();
        // Initialize premium integrations
        if ( method_exists( $this, 'initialize_seo_yoast__premium_only' ) ) {
            if ( !function_exists( 'is_plugin_active' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $this->yoast_seo = $this->initialize_seo_yoast__premium_only();
            $this->wcfm = $this->initialize_wcfm__premium_only();
        }
    }

    /**
     * Initializes the BuddyBoss integration if the BuddyBoss plugin is active
     *
     * @return bool
     */
    public function initialize_buddypboss_integration() : bool {
        if ( is_plugin_active( 'buddyboss-platform/bp-loader.php' ) ) {
            BuddyBoss_Platform::run();
            return true;
        }
        return false;
    }

    /**
     * Hooks the 'plugins_loaded' action to initialize integrations after all plugins have been loaded.
     *
     * @return void
     */
    public function run() : void {
        add_action( 'plugins_loaded', array($this, 'initialize_integrations') );
    }

    /**
     * Divi integration
     *
     * @return bool
     */
    public function initialize_divi_integration() : bool {
        if ( is_plugin_active( 'divi-builder/divi-builder.php' ) || Helper::active_theme_is_divi() ) {
            Divi::run();
            return true;
        }
        return false;
    }

}
