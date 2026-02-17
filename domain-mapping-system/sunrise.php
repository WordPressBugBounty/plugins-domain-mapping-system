<?php

// PHP 7.4 compat.
if ( ! function_exists( 'str_contains' ) ) {
	function str_contains( string $haystack, string $needle ): bool {
		return $needle === '' || strpos( $haystack, $needle ) !== false;
	}
}

add_filter( 'pre_get_site_by_path', 'dms_get_site_by_host' );
/**
 * Finds blog in which mapped current domain if exist then returns blog
 * otherwise returns null
 *
 * @param $site
 *
 * @return mixed|null
 */
function dms_get_site_by_host( $site ) {
	global $wpdb;
	$current_path = $_SERVER['REQUEST_URI'];
	$is_admin = ( 
		str_contains( $current_path, 'wp-admin' ) || 
		str_contains( $current_path, 'wp-login' ) ||
		str_contains( $current_path, 'wp-json' ) ||
		str_contains( $current_path, 'wp-cron.php' ) ||
		str_contains( $current_path, 'wp-signup.php' ) ||
		str_contains( $current_path, 'wp-activate.php' )
	);

	if ( ! is_multisite() || $is_admin ) {
		return $site;
	}
	$domain       = strtolower( stripslashes( $_SERVER['HTTP_HOST'] ?? '' ) );

	// Check if the domain is natively associated with a site.
	$native_site = $wpdb->get_row( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} WHERE domain = %s AND path = %s", $domain, '/' ) );
	if ( $native_site ) {
		return $site; // Return original site to let WP handle its native mapping.
	}

	$query_string = '';
	foreach ( get_sites() as $blog ) {
		$prefix = $wpdb->get_blog_prefix( $blog->id );
		$result = $wpdb->get_row( "SHOW TABLES LIKE '" . $prefix . "dms_mappings'" );
		if ( ! empty( $result ) ) {
			$query_string .= $wpdb->prepare( "SELECT `host`, %d as `blog_id` FROM " . $prefix . "dms_mappings WHERE host=%s", $blog->id, $domain ) . " UNION ";
		}
	}
	$pos = strrpos( $query_string, 'UNION' );
	if ( $pos !== false ) {
		$query_string = substr_replace( $query_string, '', $pos, strlen( 'UNION' ) );
	}
	$query_string = trim( $query_string );
	$result       = $wpdb->get_row( $query_string );
	if ( ! empty( $result->blog_id ) ) {
		$blog_id = (int) trim( str_replace( 'id', '', $result->blog_id ) );
		$site    = get_site( $blog_id );
	}

	return $site;
}

?>