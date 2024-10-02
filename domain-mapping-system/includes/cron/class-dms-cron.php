<?php

namespace DMS\Includes\Cron;

class Cron {

	/**
	 * @var Cron|null
	 */
	private static ?Cron $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
	}

	/**
	 * @return Cron|null
	 */
	public static function get_instance(): ?Cron {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Run the cron
	 * 
	 * @return void
	 */
	public function run() {
		Fs_Check_Cron::get_instance()->run();
		// Add more cron classes as needed in the future
	}

	/**
	 * @return void
	 */
	public function deactivate() {
		Fs_Check_Cron::get_instance()->deactivate();
		// Add future cron deactivation here
	}
}