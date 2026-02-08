<?php

namespace DMS\Includes\Integrations\Elementor;

use DMS\Includes\Frontend\Frontend;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Utils\Helper;

class Elementor {

	/**
	 * Instance of the class
	 *
	 * @var Elementor
	 */
	private static Elementor $instance;

	/**
	 * Get Frontend for rewrite urls
	 *
	 * @var Frontend
	 */
	private Frontend $frontend;

	/**
	 * Get request params for detect Mapping
	 *
	 * @var Request_Params
	 */
	private Request_Params $request_params;

	/**
	 * Run the integration
	 *
	 * @return void
	 */
	public function __construct() {
		$this->request_params = new Request_Params();
		$this->frontend       = Frontend::get_instance();
	}

	/**
	 * Initializes the instance and registers a callback to modify the redirect URL
	 * when a new form record is created in Elementor Pro.
	 *
	 * @return void
	 */
	public static function run(): void {
		$instance = self::get_instance();
		if ( is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
			add_action( 'elementor_pro/forms/new_record', array( $instance, 'change_redirect_url' ), 10, 2 );
			add_filter( 'elementor_pro/theme_builder/get_location_templates/condition', array( $instance, 'fix_theme_builder_conditions' ), 10, 2 );
		}
		add_filter( 'elementor/frontend/before_render', array( $instance, 'ensure_correct_post_context' ), 10, 1 );
	}

	/**
	 * Ensure the correct post context for Elementor rendering
	 */
	public function ensure_correct_post_context( $element ) {
		if ( $this->frontend->mapping_handler->mapped && ! empty( $this->frontend->mapping_handler->matching_mapping_value ) ) {
			$object_id = $this->frontend->mapping_handler->matching_mapping_value->object_id;
			if ( $object_id && get_the_ID() !== (int) $object_id ) {
				global $post;
				if ( ! $post || $post->ID !== (int) $object_id ) {
					$post = get_post( $object_id );
					setup_postdata( $post );
				}
			}
		}
	}

	/**
	 * Fix Elementor Pro Theme Builder conditions on mapped domains
	 */
	public function fix_theme_builder_conditions( $condition, $args ) {
		if ( $this->frontend->mapping_handler->mapped ) {
			// If we are on a mapped page, we might want to force certain conditions to match
			// This is complex as conditions vary. 
			// But often, Elementor checks is_front_page() which might fail if home_url doesn't match.
		}
		return $condition;
	}

	/**
	 * Get the singleton instance
	 *
	 * @return Elementor
	 */
	public static function get_instance(): Elementor {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Modifies the redirect URL within the provided AJAX handler data by utilizing the frontend URI handler and applicable mappings.
	 *
	 * @param  mixed  $record  A record related to the operation, its significance is not defined explicitly in this context.
	 * @param  object  $ajax_handler  The AJAX handler containing data, specifically expecting a 'redirect_url' key within its data property to process.
	 *
	 * @return void
	 */
	public function change_redirect_url( $record, $ajax_handler ) {
		if ( ! empty( $ajax_handler->data ) && ! empty ( $ajax_handler->data['redirect_url'] ) ) {
			$this->frontend->handlers_init();
			$mapping    = Helper::matching_mapping_from_db( $this->request_params->get_domain(), $this->request_params->get_path() );
			if( empty( $mapping ) ) {
				return;
			}
			$url        = $this->frontend->uri_handler->get_rewritten_url( $mapping, null, $ajax_handler->data['redirect_url'] );
			$url        = apply_filters( 'dms_trp_translate_url', $url, $mapping );
			$ajax_handler->data['redirect_url'] = $url;
		}
	}


}
