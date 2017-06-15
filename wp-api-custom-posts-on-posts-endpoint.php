<?php namespace WpApiCustomPostsOnPostsEndpoint;
/**
 * Plugin Name: WP API Custom posts on posts endpoint
 * Plugin URI:  http://shortlist.studio/
 * Description: Adding custom post types to post endpoint
 * Version:     20160911
 * Author:      Shortlist Digital
 * Author URI:  https://developer.wordpress.org/
 */

function getVisiblePostTypes() {
	$post_types = get_post_types();
	foreach ( $post_types as $index => $post_type ) {
		if ( in_array( $post_type, [ 'attachment', 'redirect_rule' ] ) ) {
			unset( $post_types[ $index ] );
			continue;
		}
		$post_types[ $index ] = get_post_type_object( $post_type );
	}
	$post_types = array_filter( $post_types, function ( $p ) {
		return $p->publicly_queryable;
	} );
	$post_types = array_map( function ( $p ) {
		return $p->name;
	}, $post_types );

	return $post_types;
}

/**
 * Rewrites list endpoint
 */
add_action( 'rest_api_init', function () {
	add_filter( "rest_post_query", function ( $args ) {
		$args['post_type'] = getVisiblePostTypes();

		return $args;
	}, 10, 2 );

} );

/**
 * Rewrites single endpoint
 */
add_filter( 'rest_pre_dispatch', function ( $none, $server, \WP_REST_Request $request ) {
	$route = rtrim( $request->get_route(), '/' );
	if ( preg_match( '/^\/wp\/v2\/posts\/\d+$/', $route ) ) {
		$id = str_replace( '/wp/v2/posts/', '', $route );
		$p  = get_post( (int) $id );
		if ( $p && in_array( $p->post_type, getVisiblePostTypes() ) ) {
			$request->set_route( str_replace( 'wp/v2/posts', 'wp/v2/' . $p->post_type, $route ) );

			return rest_do_request( $request );
		}
	}


}, 10, 3 );