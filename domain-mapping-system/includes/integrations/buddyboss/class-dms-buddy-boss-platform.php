<?php

namespace DMS\Includes\Integrations\BuddyBoss;

use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Utils\Helper;
use WP_User;
use DMS\Includes\Services\Request_Params;

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
	 * Request params instance
	 *
	 * @var Request_Params
	 */
	public static Request_Params $request_params;

	/**
	 * Initialize the integration
	 *
	 * @return void
	 */
	public static function run(): void {
		$instance = self::get_instance();
		self::$request_params = new Request_Params();
		add_filter( 'bp_nouveau_get_loop_classes', array( $instance, 'add_loop_classes' ), 10, 2 );
		add_filter( 'home_url', array( $instance, 'change_home_url' ), 9999, 2 );
		add_filter( 'dms_trp_prevent_redirection', '__return_false' );
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
			$option = get_user_meta( $user->ID, 'bb_layout_view', true );
			$cookie = ! empty( $option ) ? $option : null;
		} else {
			$cookie = ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ? $_COOKIE[ self::COOKIE_NAME ] : null;
			if ( ! empty( $cookie ) ) {
				$cookie = json_decode( rawurldecode( $cookie ), true );
			}
		}

		// If not array, then do not modify
		if ( ! is_array( $classes ) ) {
			return $classes;
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


	/**
	 * Modifies the home URL during AJAX requests based on specific settings.
	 *
	 * @param  string  $url  The original URL.
	 * @param  string|null  $path  The ID of the post.
	 *
	 * @return string The potentially modified URL.
	 */
	public function change_home_url( $url, $path ) {

		if(is_user_logged_in()){
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				$setting_rewriting = Setting::find( 'dms_rewrite_urls_on_mapped_page_sc' )->get_value();
				if ( ! empty( $setting_rewriting ) && $setting_rewriting === '1' ) {
					$current_domain = self::$request_params->get_domain();
					return trim( Helper::generate_url( $current_domain,'' ), '/' );
				}
			}

			$is_global_mapping_active = Setting::find( 'dms_global_mapping' )->get_value();
			if(empty($is_global_mapping_active)){
				return $url;
			}

			$main_mapping_ids = Setting::find('dms_main_mapping');
			$current_mapping = Helper::matching_mapping_from_db(self::$request_params->get_domain(), self::$request_params->get_path());

			if(empty($current_mapping) || empty($main_mapping_ids)){
				return $url;
			}

			if(!empty($main_mapping_ids->value) && !in_array($current_mapping->get_id(), $main_mapping_ids->value)){
				return $url;
			}


			$current_domain = self::$request_params->get_domain();
			return trim( Helper::generate_url( $current_domain, trim($current_mapping->get_path().'/'.$path, '/')), '/' );
		}

		return $url;
	}
}
