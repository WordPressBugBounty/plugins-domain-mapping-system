<?php

namespace DMS\Includes\Integrations\BuddyBoss;

use WP_User;

class BuddyBoss_Platform {

	/**
	 * The cookie name where the current layouts are stored
	 */
	const COOKIE_NAME = 'bb_layout_view';

	/**
	 * Grid layout type
	 */
	const TYPE_GRID = 'grid';

	/**
	 * List layout type
	 */
	const TYPE_LIST = 'list';

	/**
	 * The key of the parameter storing the current component
	 */
	const KEY_OBJECT = 'object';

	/**
	 * Instance of the current class
	 *
	 * @var BuddyBoss_Platform
	 */
	private static BuddyBoss_Platform $instance;

	/**
	 * Initialize the integration
	 *
	 * @return void
	 */
	public static function run(): void {
		add_filter( 'bp_nouveau_get_loop_classes', array( self::get_instance(), 'add_loop_classes' ), 10, 2 );
	}

	/**
	 * Get the singleton instance
	 *
	 * @return BuddyBoss_Platform
	 */
	public static function get_instance(): BuddyBoss_Platform {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add the layout class to the list of classes on the mapped page
	 *
	 * @param $classes
	 * @param $component
	 *
	 * @return array
	 */
	public function add_loop_classes( $classes, $component ): array {
		// Do not modify if there is a logged-in user
		$user = wp_get_current_user();
		if ( ! empty( $user ) && ( $user instanceof WP_User ) && $user->exists() ) {
			return $classes;
		}
		
		// If not array, then do not modify
		if( ! is_array( $classes ) ) {
			return $classes;
		}

		// Retrieve the layout from the cookie
		$cookie = ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ? $_COOKIE[ self::COOKIE_NAME ] : null;
		if ( ! empty( $cookie ) ) {
			$cookie = json_decode( rawurldecode( $cookie ), true );
		}

		// If component is not set, retrieve it from the POST data
		if ( empty( $component ) ) {
			$component = ! empty( $_POST[ self::KEY_OBJECT ] ) ? sanitize_text_field( $_POST[ self::KEY_OBJECT ] ) : null;
		}

		// Filter out existing grid or list layout classes
		$classes = array_filter( $classes, array( $this, 'remove_grid_or_list' ) );

		// Add the appropriate layout class based on the cookie or default to grid layout
		if ( ! empty( $component ) && ! empty( $cookie[ $component ] ) ) {
			$classes[] = $cookie[ $component ];
		} else {
			$classes[] = self::TYPE_GRID;
		}

		return $classes;
	}

	/**
	 * Remove grid and list layouts from the array of classes
	 *
	 * @param $class
	 *
	 * @return bool
	 */
	private function remove_grid_or_list( $class ): bool {
		return ! ( $class === self::TYPE_GRID || $class === self::TYPE_LIST );
	}
}
