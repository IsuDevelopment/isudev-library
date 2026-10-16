<?php
/**
 * Disable XML-RPC: refuse xmlrpc.php outright and stop advertising it. Details in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\DisableXmlrpc;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	/*
	 * xmlrpc.php defines XMLRPC_REQUEST before loading WordPress, so the request
	 * is known here. Priority 1: this runs inside init 0, and themes' filters are loaded.
	 */
	\add_action( 'init', __NAMESPACE__ . '\\refuse_request', 1 );

	// For code that builds wp_xmlrpc_server itself. Order-independent with disable-comments and disable-posts.
	\add_filter( 'xmlrpc_methods', __NAMESPACE__ . '\\filter_methods', 100 );
	\add_filter( 'xmlrpc_enabled', __NAMESPACE__ . '\\filter_enabled', 100 );

	\add_filter( 'wp_headers', __NAMESPACE__ . '\\remove_pingback_header' );
	\remove_action( 'wp_head', 'rsd_link' );
}

/**
 * Whether XML-RPC is let through after all.
 *
 * @return bool
 */
function is_allowed(): bool {
	/**
	 * Filters whether an XML-RPC request is let through. Default false.
	 *
	 * @param bool $allowed Whether to serve it.
	 */
	return true === \apply_filters( 'isudev_library/extensions/disable_xmlrpc/allow_request', false );
}

/**
 * End an XML-RPC request with a bare 403. No wp_die() page: the clients are machines.
 *
 * @return void
 */
function refuse_request(): void {
	if ( ! \defined( 'XMLRPC_REQUEST' ) || ! XMLRPC_REQUEST || is_allowed() ) {
		return;
	}

	\status_header( 403 );
	\nocache_headers();
	\header( 'Content-Type: text/plain; charset=utf-8' );
	echo 'XML-RPC is disabled.';
	exit;
}

/**
 * No wp.*, blogger.*, metaWeblog.*, mt.* or pingback.* methods. IXR still adds
 * system.* (multicall included) itself; refuse_request() is what stops those.
 *
 * @param array $methods XML-RPC methods.
 * @return array
 */
function filter_methods( $methods ): array {
	if ( is_allowed() ) {
		return \is_array( $methods ) ? $methods : array();
	}

	return array();
}

/**
 * Core's own switch, which only covers methods that log in.
 *
 * @param bool $enabled Whether enabled.
 * @return bool
 */
function filter_enabled( $enabled ): bool {
	return is_allowed() ? (bool) $enabled : false;
}

/**
 * Drop the X-Pingback response header, which advertises xmlrpc.php.
 *
 * @param array $headers Response headers.
 * @return array
 */
function remove_pingback_header( $headers ): array {
	$headers = \is_array( $headers ) ? $headers : array();
	unset( $headers['X-Pingback'] );

	return $headers;
}
