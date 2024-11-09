<?php

namespace DMS\Includes;

use DMS\Includes\Cron\Cron;
use DMS\Includes\Utils\Helper;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @link       https://limb.dev
 * @since      1.0.0
 */
class Deactivator {
	
	public function __construct() {
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public function deactivate() {
		Cron::get_instance()->deactivate();
	}
}
