<?php

namespace DMS\Includes\Admin;

use DMS\Includes\Admin\Handlers\Alias_Domain_Authentication_Handler;
use DMS\Includes\Admin\Handlers\Subdomain_Authentication_Handler;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Freemius;
use DMS\Includes\Integrations\Integrations;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Utils\Helper;

/**
 * Admin class which organizes all the admin related functionality
 */
class Admin {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	public string $plugin_name;

	/**
	 * Plugin path
	 *
	 * @var string
	 */
	public string $plugin_path;

	/**
	 * Plugin url
	 *
	 * @var string
	 */
	public string $plugin_url;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public string $version;

	/**
	 * Subdomain authentication handler
	 *
	 * @var Subdomain_Authentication_Handler
	 */
	public Subdomain_Authentication_Handler $subdomain_authentication_handler;

	/**
	 * Alias domain authentication handler
	 *
	 * @var Alias_domain_Authentication_Handler
	 */
	public Alias_Domain_Authentication_Handler $alias_domain_authentication_handler;

	/**
	 * Request params
	 *
	 * @var Request_Params
	 */
	public Request_Params $request_params;

	/**
	 * Freemius instance
	 */
	public ?\Freemius $fs;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name Plugin name
	 * @param string $plugin_path Plugin path
	 * @param string $plugin_url Plugin Url
	 * @param string $version Plugin version
	 */
	public function __construct( string $plugin_name, string $plugin_path, string $plugin_url, string $version ) {
		$this->plugin_name    = $plugin_name;
		$this->plugin_path    = $plugin_path;
		$this->plugin_url     = $plugin_url;
		$this->version        = $version;
		$this->fs             = Freemius::getInstance()->fs;
		$this->request_params = new Request_Params();
		$this->define_hooks();
		$this->inject_main_dependencies();
	}


	/**
	 * Define admin hooks
	 *
	 * @return void
	 */
	public function define_hooks(): void {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_post_save_dms_screen_options', array( $this, 'save_screen_options' ) );
		add_filter( 'posts_where', array( $this, 'search_object_by_title' ), 10, 2 );
		add_action( 'admin_init', array( 'DMS\Includes\Utils\Helper', 'sync_fs_license' ) );
		if ( $this->fs ) {
			$this->fs->add_action( 'after_uninstall', array( '\DMS\Includes\Uninstaller', 'uninstall' ) );
		}
	}

	/**
	 * Inject main dependencies
	 *
	 * @return void
	 */
	public function inject_main_dependencies(): void {
		if ( Helper::is_admin( $this->request_params->get_path() ) ) {
			$this->define_admin_handlers();
		}
	}

	/**
	 * Initialize admin handlers
	 *
	 * @return void
	 */
	private function define_admin_handlers() {
		if(!empty(Setting::find( 'dms_subdomain_authentication' )->get_value())) {
			$this->subdomain_authentication_handler = new Subdomain_Authentication_Handler( $this->request_params );
		}
		if(!empty(Setting::find( 'dms_alias_domain_authentication' )->get_value())) {
			$this->alias_domain_authentication_handler = new Alias_Domain_Authentication_Handler( $this->request_params );
		}
	}
	
	/**
	 * Add new menu and pages for DMS plugin
	 *
	 * @return void
	 */
	public function admin_menu(): void {
		$page = add_menu_page(
			__( 'Domain Mapping', $this->plugin_name, 'domain-mapping-system' ),
			__( 'Domain Mapping', $this->plugin_name, 'domain-mapping-system' ),
			'manage_options',
			'domain-mapping-system',
			array( $this, 'include_options' ),
			'dashicons-admin-site-alt3' );
		add_action( 'admin_print_styles-' . $page, array( $this, 'register_styles' ) );
		add_action( 'admin_print_scripts-' . $page, array( $this, 'register_scripts' ) );
	}

	/**
	 * Save screen options
	 *
	 * @return void
	 */
	public function save_screen_options(): void {
		$check_nonce            = check_admin_referer( 'save_dms_screen_options', 'save_dms_screen_options_nonce' );
		$referer                = wp_get_referer();
		$redirect_to_first_page = false;
		if ( $check_nonce ) {
			if ( ! empty( $_POST['dms_mappings_per_page'] ) ) {
				$per_page    = sanitize_text_field( $_POST['dms_mappings_per_page'] );
				$saved_value = Setting::find( 'dms_mappings_per_page' )->get_value();
				if ( $per_page != $saved_value ) {
					$redirect_to_first_page = true;
				}
			}

			$values_per_mapping = ! empty( $_POST['dms_values_per_mapping'] ) ? sanitize_text_field( $_POST['dms_values_per_mapping'] ) : 5;
			$mappings_per_page  = ! empty( $_POST['dms_mappings_per_page'] ) ? sanitize_text_field( $_POST['dms_mappings_per_page'] ) : 10;

			Setting::update( [ 'key' => 'dms_mappings_per_page', 'value' => $mappings_per_page ] );
			Setting::update( [ 'key' => 'dms_values_per_mapping', 'value' => $values_per_mapping ] );
		}
		$url = site_url() . $referer;
		if ( $redirect_to_first_page ) {
			$url = remove_query_arg( 'paged', $url );
		}
		wp_redirect( $url );
	}

	/**
	 * Include plugin options page view
	 *
	 * @return void
	 */
	public function include_options(): void {
		$dms_fs = $this->fs;

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have the permissions to access this page.', 'domain-mapping-system' ) );
		}
		require_once $this->plugin_path . 'templates/option-page.php';
	}

	/**
	 * Register plugin styles
	 *
	 * @return void
	 */
	public function register_styles(): void {
		wp_register_style( 'dms-min-css', $this->plugin_url . 'assets/css/dms.min.css', array(), $this->version );
		wp_enqueue_style( 'dms-min-css' );
	}

	/**
	 * Get custom post types of the site
	 *
	 * @return array
	 */
	public function get_content_types(): array {
		$types = get_post_types( [ 'public' => true ], 'objects' );
		$clean_types = array();
		foreach ( $types as $singular => $item ) {
			$clean_type = array(
				'name'        => $singular,
				'label'       => $item->labels->name,
				'has_archive' => $item->has_archive,
			);
			if ( $this->fs && $this->fs->can_use_premium_code__premium_only() ) {
				$taxonomies = get_object_taxonomies( $singular, 'objects' );
				if ( ! empty( $taxonomies ) ) {
					foreach ( $taxonomies as $taxonomy ) {
						if ( $taxonomy->public && $taxonomy->publicly_queryable && $taxonomy->show_ui ) {
							$clean_type['taxonomies'][] = array(
								'name'  => $taxonomy->name,
								'label' => $taxonomy->label,
							);
						}
					}
				}
			}
			$clean_types[] = $clean_type;
		}

		return apply_filters( 'dms_available_content_types', $clean_types );
	}

	/**
	 * Apply title search only
	 *
	 * @param $where
	 * @param $wp_query
	 *
	 * @return mixed|string
	 */
	public function search_object_by_title( $where, $wp_query ) {
		global $wpdb;

		if ( $search_term = $wp_query->get( 'dms_object_by_title' ) ) {
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $search_term ) . '%\'';
		}

		return $where;
	}
	
	/**
	 * Get the corresponding rest structure according to permalink structure
	 *
	 * @return string
	 */
	public function get_rest_url() {
		return get_rest_url( null, 'dms/v1/' );
	}

	/**
	 * Register & enqueue JS
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		/**
		 * Collect data to localize
		 */
		$dms_data = array(
			'rest_nonce'         => wp_create_nonce( 'wp_rest' ),
			'site_url'           => home_url(),
			'rest_url'           => $this->get_rest_url(),
			'is_premium'         => (int) ( $this->fs ? $this->fs->can_use_premium_code__premium_only() : false ),
			'upgrade_url'        => 'https://domainmappingsystem.com/#pricing',
			'values_per_mapping' => Setting::find( 'dms_values_per_mapping' )->get_value() ?? 5,
			'mappings_per_page'  => Setting::find( 'dms_mappings_per_page' )->get_value() ?? 10,
			'paged'              => ! empty( $_GET['paged'] ) ? $_GET['paged'] : 1,
			'plugin_url'         => $this->plugin_url,
			'available_objects'  => $this->get_content_types(),
			'is_multilingual'    => Integrations::instance()->translate_press,
			'permalink_options'  => admin_url('options-permalink.php'),
		);
		if ( $this->fs && $this->fs->can_use_premium_code__premium_only() ) {
			// Load media library
			wp_enqueue_media();
		}

		// Dequeue Wc Vendors Pro js file to avoid from conflicts
		wp_dequeue_script( 'wcv-admin-js' );

		$dms_script_asset = include( $this->plugin_path . 'dist/js/admin/dms.asset.php' );
		wp_register_script( 'admin-dms-js', $this->plugin_url . 'dist/js/admin/dms.js', $dms_script_asset['dependencies'], $dms_script_asset['version'] );
		wp_enqueue_script( 'admin-dms-js' );
		wp_localize_script( 'admin-dms-js', 'dms_data', $dms_data );
	}
}