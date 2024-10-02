<?php

namespace DMS\Includes\Cron;

use DMS\Includes\Utils\Helper;

class Fs_Check_Cron {

	/**
	 * Event name
	 */
	const EVENT_NAME = 'dms_fs_is_premium_check_event';

	/**
	 * @var Fs_Check_Cron|null
	 */
	private static ?Fs_Check_Cron $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
	}
	
	public function run() {
		add_action( 'init', [ $this, 'schedule_event' ] );
		add_action( self::EVENT_NAME, [ $this, 'update_option' ] );
	}

	/**
	 * @return Fs_Check_Cron|null
	 */
	public static function get_instance(): ?Fs_Check_Cron {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return void
	 */
	public function schedule_event() {
		if ( ! wp_next_scheduled( self::EVENT_NAME ) ) {
			wp_schedule_event( time(), 'daily', self::EVENT_NAME );
		}
	}

	/**
	 * @return void
	 */
	public function update_option() {
		Helper::sync_fs_license();
	}

	/**
	 * @return void
	 */
	public function deactivate() {
		$timestamp = wp_next_scheduled( self::EVENT_NAME );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::EVENT_NAME );
		}
	}
}