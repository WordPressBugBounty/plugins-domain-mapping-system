<?php

namespace DMS\Includes\Utils;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Freemius;
use DMS\Includes\Frontend\Handlers\Custom_Body_Handler;
use DMS\Includes\Frontend\Handlers\Favicon_Handler;
use DMS\Includes\Frontend\Handlers\Map_Html_Handler;
use DMS\Includes\Integrations\SEO\Yoast\Seo_Yoast;
use Exception;
use WP_Error;
use WP_Post;
class Helper {
    /**
     * Check is an AI_Exception instance or not
     *
     * @param Exception|DMS_Exception|mixed $e
     *
     * @return bool
     */
    public static function is_dms_error( $e ) : bool {
        return $e instanceof DMS_Exception;
    }

    /**
     * If debug mode is turned on log the data
     *
     * @param Exception|DMS_Exception $data
     * @param string $key
     *
     * @return void
     */
    public static function log( $data, string $key = 'log' ) {
        $debug = DMS()::get_debug();
        if ( $debug ) {
            if ( $data instanceof DMS_Exception ) {
                $data_to_log = $data->get_error_data();
            } else {
                $data_to_log = $data;
            }
            if ( $data instanceof Exception ) {
                $message = $data->getMessage() . ':  ';
            }
            error_log( DMS()->get_plugin_name() . '-debug: [' . $key . ']: ' . ($message ?? '') . print_r( $data_to_log, true ) );
        }
    }

    /**
     * Get the WP error instance based on the message
     *
     * @param Exception|DMS_Exception $e
     *
     * @return WP_Error
     */
    public static function get_wp_error( $e ) : WP_Error {
        if ( $e instanceof DMS_Exception ) {
            return new WP_Error($e->get_error_code(), $e->getMessage(), $e->get_error_data());
        }
        return new WP_Error('technical_error', __( 'Technical error', 'domain-mapping-system' ), [
            'http_status' => 400,
        ]);
    }

    /**
     * Get custom taxonomies
     *
     * @param $object_type
     *
     * @return array
     */
    public static function get_custom_taxonomies( $object_type ) : array {
        $taxonomies = get_taxonomies( array(
            'object_type' => array($object_type),
        ), 'objects' );
        return array_map( function ( $taxonomy ) {
            return array(
                'name'  => $taxonomy->name,
                'label' => $taxonomy->label,
            );
        }, $taxonomies );
    }

    /**
     * Get scheme
     *
     * @return string
     */
    public static function get_scheme() : string {
        return trim( wp_parse_url( get_site_url(), PHP_URL_SCHEME ) );
    }

    /**
     * Check is the host valid
     *
     * @param string $host
     *
     * @return bool
     */
    public static function is_valid_host( string $host ) : bool {
        if ( strpos( $host, '.' ) ) {
            return true;
        }
        return false;
    }

    /**
     * Get base host
     *
     * @return string
     */
    public static function get_base_host() : string {
        $site_link = get_option( 'siteurl' );
        return trim( wp_parse_url( $site_link, PHP_URL_HOST ) );
    }

    /**
     * Get base scheme
     *
     * @return string
     */
    public static function get_base_scheme() : string {
        $site_link = get_option( 'siteurl' );
        return trim( wp_parse_url( $site_link, PHP_URL_SCHEME ) );
    }

    /**
     * @param string|null $link
     *
     * @return string
     */
    public static function get_link_scheme( ?string $link ) : string {
        preg_match( "~^(https?://)~i", $link, $matches );
        return ( !empty( $matches[0] ) ? $matches[0] : '' );
    }

    /**
     * Check is subdirectory install
     *
     * @return bool
     */
    public static function is_sub_directory_install() : bool {
        return !empty( self::get_base_path() );
    }

    /**
     * Get base path
     *
     * @return array|string|string[]|null
     */
    public static function get_base_path() {
        $path = wp_parse_url( get_site_url(), PHP_URL_PATH );
        return ( !empty( $path ) ? preg_replace(
            '/\\//',
            '',
            trim( $path ),
            1
        ) : '' );
    }

    /**
     * Check is bedrock structure
     *
     * @return bool
     */
    public static function check_if_bedrock() : bool {
        $separators = explode( '/', WP_CONTENT_DIR );
        if ( $separators[count( $separators ) - 1] == 'app' && $separators[count( $separators ) - 2] == 'web' ) {
            return true;
        }
        return false;
    }

    /**
     * Replace substring
     *
     * @param string $str_pattern
     * @param string $str_replacement
     * @param string $string
     *
     * @return array|string|string[]
     */
    public static function str_replace_once( string $str_pattern, string $str_replacement, string $string ) {
        $str_pos = ( !empty( $str_pattern ) ? strpos( $string, $str_pattern ) : 0 );
        if ( str_contains( $string, $str_pattern ) ) {
            return substr_replace(
                $string,
                $str_replacement,
                $str_pos,
                strlen( $str_pattern )
            );
        }
        return $string;
    }

    /**
     * Get host plus path
     *
     * @param Mapping $mapping
     *
     * @return string
     */
    public static function get_host_plus_path( Mapping $mapping ) : string {
        $mapping = [$mapping->host, $mapping->path];
        return trim( implode( '/', $mapping ), '/' );
    }

    /**
     * Check is string ends with substring
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function ends_with( string $haystack, string $needle ) : bool {
        $length = strlen( $needle );
        if ( $length == 0 ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }

    /**
     * Get the shop page id
     *
     * @return int|null
     */
    public static function get_shop_page_association() : ?int {
        return ( function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : null );
    }

    /**
     * Checks is the mapping value object is wc account page
     *
     * @param Mapping_Value $mapping_value
     *
     * @return bool
     */
    public static function is_account_page( Mapping_Value $mapping_value ) : bool {
        if ( !function_exists( 'wc_get_page_id' ) ) {
            return false;
        }
        if ( $mapping_value->get_object_type() !== Mapping_Value::OBJECT_TYPE_POST ) {
            return false;
        }
        if ( empty( $mapping_value->get_object_id() ) ) {
            return false;
        }
        return wc_get_page_id( 'myaccount' ) == $mapping_value->get_object_id();
    }

    /**
     * Check is mapped cpt or not
     *
     * @param $key
     *
     * @return null|Mapping
     */
    public static function is_mapped_cpt( $key ) : ?Mapping {
        $taxonomies = get_object_taxonomies( get_post_type( $key ), 'objects' );
        if ( !empty( $taxonomies ) ) {
            foreach ( $taxonomies as $taxonomy ) {
                $terms = get_the_terms( $key, $taxonomy->name );
                if ( !empty( $terms ) ) {
                    foreach ( $terms as $term ) {
                        $mappings = Mapping::get_by_mapping_value( 'term', $term->term_id );
                        if ( !empty( $mappings ) ) {
                            return $mappings[0];
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * Redirect to specified url
     *
     * @param string|null $url
     *
     * @return false
     */
    public static function redirect_to( ?string $url ) : bool {
        if ( empty( $url ) || class_exists( 'DMS\\Includes\\Integrations\\Seo_Yoast' ) && Seo_Yoast::get_instance()->is_sitemap_requested() ) {
            return false;
        }
        wp_redirect( $url );
        exit;
    }

    /**
     * Generates url from host and path
     *
     * @param string|null $host
     * @param string|null $path
     *
     * @return string
     */
    public static function generate_url( ?string $host, ?string $path ) : string {
        $scheme = ( is_ssl() ? 'https://' : 'http://' );
        $path = ( !empty( $path ) ? $path . '/' : '' );
        return $scheme . $host . '/' . $path;
    }

    /**
     * Prepares class name
     *
     * @param $separator
     * @param $name
     *
     * @return string
     */
    public static function prepare_class_name( $separator, $name ) {
        $name = explode( $separator, $name );
        $name = array_map( 'ucfirst', $name );
        return implode( '_', $name );
    }

    /**
     * @param string $uri
     *
     * @return false|mixed|string
     */
    public static function get_last_path_segment_from_uri( string $uri ) {
        $path = parse_url( $uri, PHP_URL_PATH );
        $path = ( !empty( $path ) ? rtrim( $path, '/' ) : '' );
        $segments = explode( '/', $path );
        return end( $segments );
    }

    /**
     * Checks if active theme is Divi
     * 
     * @return bool
     */
    public static function active_theme_is_divi() {
        return function_exists( 'wp_get_theme' ) && (wp_get_theme()->get( 'Name' ) === 'Divi' || wp_get_theme()->parent() && wp_get_theme()->parent()->get( 'Name' ) === 'Divi');
    }

    /**
     * Checks whether "Posts page" is active
     * 
     * @return bool
     */
    public static function is_posts_page_active() : bool {
        return Setting::find( 'show_on_front' )->get_value() === 'page';
    }

    /**
     * Checks whether latest posts homepage is active
     * 
     * @return bool
     */
    public static function is_latest_posts_homepage_active() : bool {
        return Setting::find( 'show_on_front' )->get_value() == 'posts';
    }

    /**
     * Checks whether static page is homepage
     *
     * @return bool
     */
    public static function is_static_page_homepage_active() : bool {
        return Setting::find( 'show_on_front' )->get_value() == 'page';
    }

    /**
     * Checks if the passed page is the "Posts page"
     * 
     * @param  int|null  $page_id
     *
     * @return bool
     */
    public static function is_posts_page( ?int $page_id ) : bool {
        $setting = Setting::find( 'page_for_posts' )->get_value();
        return !empty( $page_id ) && !empty( $setting ) && (int) $setting === $page_id;
    }

    /**
     * Checks if the passed page is the static homepage
     *
     * @param  int|null  $page_id
     *
     * @return bool
     */
    public static function is_page_on_front( ?int $page_id ) : bool {
        // TODO could be cached
        $setting = Setting::find( 'page_on_front' )->get_value();
        return !empty( $page_id ) && !empty( $setting ) && (int) $setting === $page_id;
    }

    /**
     * Get class shortname
     * 
     * @param string|object $className
     * @param bool $lowercase
     *
     * @return false|mixed|string|null
     */
    public static function get_class_shortname( $className, bool $lowercase = false ) {
        try {
            if ( is_object( $className ) ) {
                $reflection = new \ReflectionClass($className);
                $name = $reflection->getShortName();
            } else {
                $className = ( is_object( $className ) ? get_class( $className ) : $className );
                $className = explode( '\\', $className );
                $name = end( $className );
            }
            if ( $lowercase ) {
                $name = strtolower( $name );
            }
            return $name;
        } catch ( \Exception $e ) {
            return null;
        }
    }

    /**
     * Checks if $path starts with $sub_path. 
     * Basically this relates to URI paths
     * 
     * @param  string  $path
     * @param  string  $sub_path
     *
     * @return bool
     */
    public static function path_starts_with( string $path, string $sub_path ) {
        return !empty( $sub_path ) && !empty( $path ) && str_starts_with( $path, $sub_path ) && (strlen( $path ) === strlen( $sub_path ) || $path[strlen( $sub_path )] === '/');
    }

    /**
     * @param $path
     *
     * @return false|mixed|string
     */
    public static function get_path_last_dir( $path ) {
        $path = ( !empty( $path ) ? trim( $path, '/' ) : '' );
        $path_array = explode( '/', $path );
        return end( $path_array );
    }

    /**
     * Gets the top level parent of the page
     *
     * @param int $post_id The id of the page
     *
     * @return WP_Post|array|null
     */
    public static function get_top_level_parent( int $post_id ) : WP_Post {
        $post = get_post( $post_id );
        if ( $post && $post->post_parent ) {
            return self::get_top_level_parent( $post->post_parent );
        }
        return $post;
    }

    /**
     * Gets the top level parent of the page
     *
     * @param int $page_id The id of the page
     *
     * @return array|null
     */
    public static function get_all_parents( int $page_id ) : array {
        $parent_ids = [];
        while ( $page_id ) {
            $parent = get_post_parent( $page_id );
            if ( !empty( $parent ) ) {
                $parent_ids[] = $parent->ID;
                $page_id = $parent->ID;
            } else {
                break;
            }
        }
        return $parent_ids;
    }

    /**
     * Sync the freemius license
     *
     * @return void
     */
    public static function sync_fs_license() {
        $fs_instance = Freemius::getInstance()->fs;
        $is_premium_flag = $fs_instance->can_use_premium_code() && $fs_instance->is__premium_only();
        update_option( Freemius::FS_IS_PREMIUM_OPTION_KEY, (string) $is_premium_flag );
    }

    /**
     * @param $output
     *
     * @return string[]|\WP_Post_Type[]
     */
    public static function get_custom_post_types( $output = 'names' ) {
        return get_post_types( [
            '_builtin' => false,
            'public'   => true,
        ], $output );
    }

    /**
     * Return home url
     *
     * @return array|false|mixed|null
     */
    public static function get_home_host() {
        return wp_parse_url( get_home_url(), PHP_URL_HOST );
    }

    /**
     * Get matching mapping with given host and path
     * @param $host
     * @param $path
     *
     * @return Mapping|null
     */
    public static function matching_mapping_from_db( $host, $path ) : ?Mapping {
        try {
            $all_mappings = Mapping::where(
                [
                    'host' => $host,
                ],
                null,
                null,
                'path',
                'ASC'
            );
            if ( empty( $all_mappings ) ) {
                return null;
            }
            if ( !empty( $path ) ) {
                // Check maybe there is mapping with the requested url path
                $mappings = array_values( array_filter( $all_mappings, function ( $item ) use($path) {
                    return strtolower( $path ) === strtolower( $item->path ) && !empty( $item->path );
                } ) );
                // Check the mapping the path of which is contained in the requested url path
                if ( empty( $mappings ) ) {
                    $mappings = array_values( array_filter( $all_mappings, function ( $item ) use($path) {
                        return Helper::path_starts_with( strtolower( $path ), strtolower( $item->path ) );
                    } ) );
                }
                // Empty path in mapping
                if ( empty( $mappings ) ) {
                    $mappings = array_values( array_filter( $all_mappings, function ( $item ) {
                        return empty( $item->path );
                    } ) );
                }
            } else {
                $mappings = array_values( array_filter( $all_mappings, function ( $item ) {
                    return empty( $item->path );
                } ) );
            }
            if ( empty( $mappings ) ) {
                return null;
            }
            return $mappings[0];
        } catch ( Exception $exception ) {
            Helper::log( $exception, __METHOD__ );
            return null;
        }
    }

    /**
     * Checks whether frontend was visited
     *
     * @param $path
     *
     * @return bool
     */
    public static function is_frontend( $path ) : bool {
        return !is_admin() && empty( $_GET['elementor-preview'] ) && empty( $_GET['preview_id'] ) && (empty( $_GET['action'] ) || $_GET['action'] !== 'elementor') && !str_contains( $path, 'cornerstone' ) && !str_contains( $path, 'themeco' ) && !str_contains( $path, 'wp-json' ) && !str_contains( $path, 'wp-login' ) && !str_contains( $path, 'store-manager' );
    }

    /**
     * Checks whether admin was visited
     *
     * @param $path
     *
     * @return bool
     */
    public static function is_admin( $path ) : bool {
        return !str_contains( $path, 'admin-ajax.php' ) && !str_contains( $path, 'admin-post.php' ) && (is_admin() || is_login());
    }

    /**
     * Get lowercases from string (e.g., 'en_US' -> 'en')
     *
     * @param $string
     *
     * @return string
     */
    public static function get_lowercases_from_string( $string ) : string {
        return preg_replace( '/[^a-z]/', '', $string );
    }

    /**
     * Returns allowed tags
     *
     * @return string[]
     */
    public static function get_allowed_tags() : array {
        return array(
            'title',
            'base',
            'link',
            'meta',
            'style',
            'script',
            'noscript'
        );
    }

    /**
     * Get lang slug by lang code.
     *
     * @param  string  $lang  The original lang code.
     *
     * @return string || null
     */
    public static function get_lang_slug( string $lang ) : ?string {
        $setting = Setting::find( 'trp_settings' )->get_value();
        if ( empty( $setting ) ) {
            return null;
        }
        $lang_slugs = $setting['url-slugs'];
        if ( empty( $lang_slugs[$lang] ) ) {
            return self::get_lowercases_from_string( $lang );
        }
        return $lang_slugs[$lang];
    }

    /**
     * Retrieves a value from an array using an encoded key.
     *
     * This function takes an encoded key, decodes it, and searches for a matching
     * key in the provided array. If a match is found, the corresponding value is returned.
     *
     * @param string $key   The encoded key to search for.
     * @param array  $array The array to search within.
     *
     * @return string|null The value associated with the decoded key, or null if not found.
     */
    public static function get_array_value_encoded_key( $key, $array ) : ?string {
        $value = '';
        $key = urldecode( $key );
        foreach ( $array as $encoded_key => $val ) {
            if ( urldecode( $encoded_key ) === $key ) {
                $value = $val;
                break;
            }
        }
        return $value;
    }

}
