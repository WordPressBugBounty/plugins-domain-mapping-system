<?php

namespace DMS\Includes\Frontend\Handlers;

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Data_Objects\Setting;
use DMS\Includes\Frontend\Frontend;
use DMS\Includes\Frontend\Scenarios\Global_Archive_Mapping;
use DMS\Includes\Frontend\Scenarios\Global_Product_Mapping;
use DMS\Includes\Services\Request_Params;
use DMS\Includes\Utils\Helper;
use Exception;
use WP_Scripts;
use WP_Term;
class URI_Handler {
    /**
     * Global url rewriting constant
     */
    const REWRITING_GLOBAL = 1;

    /**
     * Selective url rewriting constant
     */
    const REWRITING_SELECTIVE = 2;

    /**
     * The url rewriting setting value
     *
     * @var string|null
     */
    public ?string $url_rewrite;

    /**
     * Rewrite scenario (global, selective)
     *
     * @var mixed
     */
    public ?string $rewrite_scenario = null;

    /**
     * Mapping handler instance
     *
     * @var Mapping_Handler
     */
    public Mapping_Handler $mapping_handler;

    /**
     * Frontend instance
     *
     * @var Frontend
     */
    public Frontend $frontend;

    /**
     * Request Params instance
     *
     * @var Request_Params
     */
    public Request_Params $request_params;

    /**
     * Global domain mapping handler instance
     *
     * @var mixed
     */
    public ?Global_Domain_Mapping_Handler $global_mapping_handler;

    /**
     * Constructor
     *
     * @param  Request_Params  $request_params  Request params instance
     * @param  Mapping_Handler  $mapping_handler  Mapping Handler instance
     */
    public function __construct( Request_Params $request_params, Mapping_Handler $mapping_handler, ?Global_Domain_Mapping_Handler $global_mapping_handler ) {
        $this->request_params = $request_params;
        $this->mapping_handler = $mapping_handler;
        $this->frontend = $mapping_handler->frontend;
        $this->global_mapping_handler = $global_mapping_handler;
        $this->init();
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init() : void {
        $this->define_rewrite_options();
        $this->prepare_assets_uri_filters();
        if ( method_exists( $this, 'prepare_uri_filters__premium_only' ) ) {
            $this->prepare_uri_filters__premium_only();
        }
    }

    /**
     * Define rewrite options
     *
     * @return void
     */
    public function define_rewrite_options() : void {
        $this->url_rewrite = Setting::find( 'dms_rewrite_urls_on_mapped_page' )->get_value();
        if ( !empty( $this->url_rewrite ) ) {
            $rewrite_scenario = Setting::find( 'dms_rewrite_urls_on_mapped_page_sc' )->get_value();
            $this->rewrite_scenario = ( !empty( $rewrite_scenario ) && in_array( $rewrite_scenario, [self::REWRITING_GLOBAL, self::REWRITING_SELECTIVE] ) ? $rewrite_scenario : self::REWRITING_GLOBAL );
        }
    }

    /**
     * Prepare uri filters
     *
     * @return void
     */
    public function prepare_assets_uri_filters() : void {
        add_filter(
            'plugins_url',
            array($this, 'rewrite_plugins_url'),
            99,
            3
        );
        add_filter(
            'rest_url',
            array($this, 'rewrite_rest_url'),
            99,
            2
        );
        add_filter(
            'script_loader_src',
            array($this, 'replace_script_style_src'),
            10,
            2
        );
        add_filter(
            'style_loader_src',
            array($this, 'replace_script_style_src'),
            10,
            2
        );
        add_filter( 'upload_dir', array($this, 'rewrite_upload_dir'), 99 );
        add_filter(
            'admin_url',
            array($this, 'rewrite_admin_url'),
            999,
            4
        );
        add_filter(
            'script_module_loader_src',
            array($this, 'rewrite_script_modules_src'),
            10,
            2
        );
        add_filter(
            'wp_get_attachment_image_src',
            array($this, 'rewrite_attachment_src'),
            10,
            4
        );
        add_filter(
            'get_header_image_tag',
            array($this, 'rewrite_header_image_markup'),
            10,
            3
        );
        add_filter(
            'wp_calculate_image_srcset',
            array($this, 'rewrite_image_srcset'),
            10,
            5
        );
        add_filter(
            'elementor/frontend/the_content',
            array($this, 'rewrite_the_content'),
            10,
            1
        );
        add_filter(
            'template_directory_uri',
            array($this, 'rewrite_template_uri'),
            10,
            3
        );
        add_filter(
            'stylesheet_directory_uri',
            array($this, 'rewrite_stylesheet_uri'),
            10,
            3
        );
        add_filter(
            'wp_resource_hints',
            array($this, 'rewrite_hints'),
            10,
            2
        );
        add_filter(
            'feed_link',
            array($this, 'rewrite_feeds'),
            10,
            2
        );
        add_filter(
            'get_shortlink',
            array($this, 'rewrite_feeds'),
            10,
            2
        );
        // Action for rewriting other urls
        do_action( 'dms_rewrite_uris' );
    }

    /**
     * Flag to allow links rewriting
     *
     * @return bool
     */
    public function is_allowed_to_rewrite_links() {
        return !empty( $this->url_rewrite );
    }

    /**
     * Rewrite upload dir url
     *
     * @param $upload_dir
     *
     * @return mixed
     */
    public function rewrite_upload_dir( $upload_dir ) {
        if ( !empty( $upload_dir['url'] ) ) {
            $upload_dir['url'] = self::replace_host_occurrence( $upload_dir['url'] );
            $upload_dir['baseurl'] = self::replace_host_occurrence( $upload_dir['baseurl'] );
        }
        return $upload_dir;
    }

    /**
     * Rename modules src
     *
     * @param $url
     *
     * @return mixed|string
     */
    public function rewrite_script_modules_src( $url ) {
        return $this->replace_script_style_src( $url );
    }

    /**
     * Replace host occurrence
     *
     * @param $data
     *
     * @return string
     */
    public function replace_host_occurrence( $data ) : string {
        $host = $this->request_params->get_base_host();
        return preg_replace_callback(
            '/(https?:\\/\\/)(' . $host . ')([^"\'\\s<>]*)/i',
            array($this, 'actual_host_replace'),
            $data,
            -1
        ) ?? $data;
    }

    /**
     * Replace href occurrence
     *
     * @param $data
     *
     * @return string
     */
    public function replace_href_occurrence( $data ) : string {
        return preg_replace_callback(
            '/href="(?!http|#)([^"]+)"/i',
            function ( $item ) {
                if ( empty( $item ) ) {
                    return '';
                }
                if ( !empty( $item[0] ) && empty( $item[1] ) ) {
                    return $item[0];
                }
                if ( !empty( $item[1] ) ) {
                    if ( $item[1] == '#' ) {
                        return $item[0];
                    }
                    $rewritten = apply_filters( 'dms_rewritten_url', $item[1], $this->rewrite_scenario );
                    // Don't append trailing slashes to assets or files
                    $is_asset = false;
                    $asset_indicators = [
                        'wp-includes',
                        'wp-content',
                        'wp-admin',
                        'wp-json',
                        '/js/',
                        '/css/',
                        'thickbox'
                    ];
                    foreach ( $asset_indicators as $indicator ) {
                        if ( str_contains( $item[1], $indicator ) ) {
                            $is_asset = true;
                            break;
                        }
                    }
                    if ( !$is_asset && preg_match( '/\\.(js|css|png|jpg|jpeg|gif|svg|woff2?|otf|ttf|ico)($|\\?)/i', $item[1] ) ) {
                        $is_asset = true;
                    }
                    if ( $is_asset ) {
                        $href = '/' . ltrim( $rewritten, '/' );
                    } else {
                        $href = '/' . trim( $rewritten, '/' ) . '/';
                    }
                    return 'href="' . $href . '"';
                }
                return $item[0];
            },
            $data,
            -1
        ) ?? $data;
    }

    /**
     * Processes the HTML head section of the page and replaces any occurrences of the
     * current host with the mapped host.
     *
     * @param string $data The HTML of the page.
     *
     * @return string The modified HTML with occurrences of the current host replaced.
     */
    public function process_head_section( $data ) : string {
        // Match the content within the <head> tag
        if ( preg_match( '/<head.*?>(.*?)<\\/head>/is', $data, $matches ) ) {
            $headContent = $matches[1];
            $updatedHeadContent = self::replace_host_occurrence( $headContent );
            return str_replace( $matches[1], $updatedHeadContent, $data );
        }
        return $data;
    }

    /**
     * Rewrite rest url
     *
     * @param $url
     * @param $path
     *
     * @return array|mixed|string|string[]
     */
    public function rewrite_rest_url( $url, $path ) : string {
        return $this->actual_host_replace( $url );
    }

    /**
     * Rewrite plugins_url filter
     *
     * @param $url
     * @param $path
     * @param $plugin
     *
     * @return string
     */
    public function rewrite_plugins_url( $url, $path, $plugin ) {
        return $this->actual_host_replace( $url );
    }

    /**
     * Get final rewritten url
     *
     * @param  Mapping|null  $mapping
     * @param  Mapping_Value|null  $mapping_value
     * @param  string|null  $link
     *
     * @return string|null
     */
    public function get_rewritten_url( ?Mapping $mapping, ?Mapping_Value $mapping_value, ?string $link ) : ?string {
        if ( $this->rewrite_scenario == self::REWRITING_SELECTIVE ) {
            $mapping = Mapping::find( $mapping_value->mapping_id );
            $url = $this->get_selective_rewritten_url( $mapping ?? null, $mapping_value ?? null, $link );
        } else {
            $url = $this->get_global_rewritten_url( $mapping ?? null, $mapping_value ?? null, $link );
        }
        return apply_filters( 'dms_rewritten_url', $url, $this->rewrite_scenario );
    }

    /**
     * Gets selective rewritten url
     *
     * @param  Mapping|null  $mapping
     * @param  Mapping_Value|null  $mapping_value
     * @param  string|null  $link
     *
     * @return array|string|string[]|null
     */
    public function get_selective_rewritten_url( ?Mapping $mapping, ?Mapping_Value $mapping_value, ?string $link ) : ?string {
        try {
            $host = $this->request_params->get_base_host();
            if ( empty( $mapping ) ) {
                // Check global domain case
                if ( !is_null( $this->global_mapping_handler ) && !empty( $this->global_mapping_handler->get_main_mapping() ) ) {
                    $mapping = $this->global_mapping_handler->get_main_mapping();
                }
            }
            if ( !empty( $mapping ) ) {
                $replace_with = $mapping->host . (( !empty( $mapping->path ) ? '/' . $mapping->path : '' ));
                if ( !empty( $mapping_value ) && !empty( $mapping_value->get_primary() ) ) {
                    return Helper::get_link_scheme( $link ) . $replace_with;
                }
                $link_without_scheme = preg_replace( "~^(https?://)~i", '', $link );
                if ( !str_starts_with( $link_without_scheme, $replace_with ) ) {
                    $mapped_link = str_ireplace( $host, $replace_with, $link );
                }
            }
            if ( !empty( $mapped_link ) ) {
                return $mapped_link;
            }
            return null;
        } catch ( Exception $e ) {
            Helper::log( $e, __METHOD__ );
            return null;
        }
    }

    /**
     * Get global rewritten url
     *
     * @param  Mapping|null  $mapping
     * @param  Mapping_Value|null  $mapping_value
     * @param  string|null  $link
     *
     * @return array|string|string[]|null
     */
    public function get_global_rewritten_url( ?Mapping $mapping, ?Mapping_Value $mapping_value, ?string $link ) : ?string {
        if ( empty( $link ) ) {
            return null;
        }
        $selective_rewritten_link = $this->get_selective_rewritten_url( $mapping ?? null, $mapping_value ?? null, $link );
        // Anyway give priority to selective rewriting
        if ( empty( $selective_rewritten_link ) ) {
            $host = $this->request_params->get_base_host();
            $link_without_scheme = preg_replace( "~^(https?://)~i", '', $link );
            if ( !str_starts_with( $link_without_scheme, $this->request_params->domain ) ) {
                $rewrite_link = str_ireplace( $host, $this->request_params->domain, $link );
            }
            if ( !empty( $rewrite_link ) ) {
                return $rewrite_link;
            }
            return null;
        } else {
            return $selective_rewritten_link;
        }
    }

    /**
     * Rewrite stylesheet uri
     *
     * @param  string  $stylesheet_dir_uri
     *
     * @return string
     */
    public function rewrite_stylesheet_uri( string $stylesheet_dir_uri ) : string {
        return $this->actual_host_replace( $stylesheet_dir_uri );
    }

    /**
     * Rewrites content
     *
     * @param $content
     *
     * @return string
     */
    public function rewrite_the_content( $content ) : string {
        $content = self::replace_href_occurrence( $content );
        return self::replace_host_occurrence( $content );
    }

    /**
     * Rewrites template uri
     *
     * @param $template_dir_uri
     *
     * @return string
     */
    public function rewrite_template_uri( $template_dir_uri ) : string {
        return $this->actual_host_replace( $template_dir_uri );
    }

    /**
     * Replace script style source
     *
     * @param $src
     *
     * @return string
     */
    public function replace_script_style_src( $src ) : string {
        // First, safely swap the host without touching query strings
        $src = $this->actual_host_replace( $src );
        // Strip any TranslatePress language slug that may have been prefixed to asset URLs
        if ( !empty( $src ) ) {
            $parsed = wp_parse_url( $src );
            $path = ( isset( $parsed['path'] ) ? $parsed['path'] : '' );
            if ( !empty( $path ) ) {
                // Collapse accidental double slashes and ensure leading slash
                $path = preg_replace( '#/{2,}#', '/', $path );
                if ( $path[0] !== '/' ) {
                    $path = '/' . $path;
                }
                // If TranslatePress is active, get its published language slugs
                $trp_settings = \DMS\Includes\Data_Objects\Setting::find( 'trp_settings' )->get_value();
                $slugs = [];
                if ( is_array( $trp_settings ) && !empty( $trp_settings['url-slugs'] ) && is_array( $trp_settings['url-slugs'] ) ) {
                    $slugs = array_values( $trp_settings['url-slugs'] );
                }
                // Remove a leading language slug only when it is immediately followed by an assets directory
                if ( !empty( $slugs ) ) {
                    foreach ( $slugs as $slug ) {
                        $pattern = '#^/' . preg_quote( $slug, '#' ) . '/+(?=(wp-includes|wp-content|wp-admin|wp-json|js|css)/)#i';
                        $new_path = preg_replace(
                            $pattern,
                            '/',
                            $path,
                            1
                        );
                        if ( $new_path !== null && $new_path !== $path ) {
                            $path = $new_path;
                            break;
                        }
                    }
                }
                $query = ( isset( $parsed['query'] ) ? '?' . $parsed['query'] : '' );
                $fragment = ( isset( $parsed['fragment'] ) ? '#' . $parsed['fragment'] : '' );
                // Rebuild the URL if we parsed a host
                if ( !empty( $parsed ) && isset( $parsed['host'] ) ) {
                    $src = (( isset( $parsed['scheme'] ) ? $parsed['scheme'] . '://' : '' )) . $parsed['host'] . (( isset( $parsed['port'] ) ? ':' . $parsed['port'] : '' )) . $path . $query . $fragment;
                } else {
                    $src = $path . $query . $fragment;
                }
                $src = \DMS\Includes\Utils\Helper::normalise_url_slashes( $src );
            }
        }
        if ( Helper::check_if_bedrock() ) {
            $src = str_replace( $this->request_params->domain, $this->request_params->domain . '/wp', $src );
        }
        return $src;
    }

    /**
     * Replace actual host
     *
     * @param $input
     *
     * @return string
     */
    public function actual_host_replace( $input ) : string {
        if ( is_array( $input ) ) {
            $input = $input[0];
        }
        $host = $this->request_params->get_base_host();
        $path = $this->request_params->get_base_path();
        if ( !empty( $path ) ) {
            $url = str_ireplace( '://' . $host . '/' . $path . '/', '://' . $this->request_params->domain . '/', $input );
            $url = str_ireplace( '://' . $host . '/' . $path, '://' . $this->request_params->domain, $url );
            return apply_filters( 'dms_rewritten_url', $url, $this->rewrite_scenario );
        }
        return apply_filters( 'dms_rewritten_url', str_ireplace( '://' . $host, '://' . $this->request_params->domain, $input ), $this->rewrite_scenario );
    }

    /**
     * Rewrites admin url
     *
     * @param $url
     * @param $path
     *
     * @return string
     */
    public function rewrite_admin_url( $url, $path ) : string {
        if ( $path == 'admin-ajax.php' ) {
            $url = self::replace_host_occurrence( $url );
        }
        return $url;
    }

    /**
     * Rewrite attachment sources
     *
     * @param $image
     *
     * @return array|bool
     */
    public function rewrite_attachment_src( $image ) {
        if ( !empty( $image[0] ) ) {
            $image[0] = self::replace_host_occurrence( $image[0] );
        }
        return $image;
    }

    /**
     * Rewrite header image markup
     *
     * @param $html
     *
     * @return string
     */
    public function rewrite_header_image_markup( $html ) : string {
        if ( !empty( $html ) ) {
            $html = self::replace_host_occurrence( $html );
        }
        return $html;
    }

    /**
     * Rewrite image srcset
     *
     * @param $sources
     *
     * @return array
     */
    public function rewrite_image_srcset( $sources ) : array {
        if ( !empty( $sources ) ) {
            foreach ( $sources as $key => $val ) {
                $sources[$key]['url'] = self::replace_host_occurrence( $val['url'] );
            }
        }
        return $sources;
    }

    /**
     * Ensures the buffered content is sent to the browser after modifications.
     *
     */
    public function clean_buffer_and_show() {
        if ( ob_get_length() ) {
            ob_end_flush();
        }
    }

    /**
     * Rewrite WordPress generated resource
     *
     * @param $hints
     * @param $rel
     *
     * @return mixed
     */
    public function rewrite_hints( $hints, $rel ) {
        $base_domain = $this->request_params->base_host;
        $mapped_domain = $this->request_params->domain;
        // Mapped domain
        // Replace domain in dns-prefetch hints
        foreach ( $hints as &$hint ) {
            if ( is_array( $hint ) ) {
                continue;
            }
            if ( strpos( $hint, $base_domain ) !== false ) {
                $hint = str_replace( $base_domain, $mapped_domain, $hint );
            }
        }
        return $hints;
    }

    /**
     * Rewrite WordPress feeds
     *
     * @param $hints
     * @param $rel
     *
     * @return mixed
     */
    public function rewrite_feeds( $output, $feed ) {
        $base_domain = $this->request_params->base_host;
        $mapped_domain = $this->request_params->domain;
        // Mapped domain
        // Replace domain in dns-prefetch hints
        $output = str_replace( $base_domain, $mapped_domain, $output );
        return $output;
    }

}
