<?php

/**
 * Plugin Name: Domain Mapping System
 * Plugin URI: https://domainmappingsystem.com/
 * Description: Domain Mapping System is the most powerful way to manage alias domains and map them to any published resource - creating Microsites with ease!
 * Version: 2.2.5.1
 * Author: Domain Mapping System
 * Author URI: https://domainmappingsystem.com/
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
if ( ! function_exists( 'DMS' ) ) {
    /**
     * Plugin version.
     * Used SemVer - https://semver.org
     */
    define( 'DMS_VERSION', '2.2.5.1' );

    /**
     * Load activate/Deactivate files
     */
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-dms-activator.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-dms-deactivator.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-dms-uninstaller.php';

    /**
     * The core plugin class
     */
    require plugin_dir_path( __FILE__ ) . 'includes/class-dms.php';
    /**
     * Returns the main instance of DMS.
     *
     * @return DMS\Includes\DMS
     * @since  1.0.0
     */
    function DMS()
    {
        return DMS\Includes\DMS::get_instance();
    }

    /**
     * Begins execution of the plugin.
     */
    DMS();
}

/**
 * Activate/Deactivate hooks
 */
register_activation_hook( __FILE__, array ( ( new \DMS\Includes\Activator() ), 'activate' ) );
register_deactivation_hook( __FILE__, array ( ( new \DMS\Includes\Deactivator() ), 'deactivate' ) );
