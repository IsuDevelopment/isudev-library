<?php
/**
 * Disable feeds: every core feed answers 404, and no feed is linked from <head>.
 * Details in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\DisableFeeds;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	/*
	 * Decided on the parsed query vars, before WP::send_headers(): that is where
	 * a feed gets its Content-Type and answers conditional requests with a 304,
	 * both of which a later 404 could no longer undo.
	 */
	\add_filter( 'request', __NAMESPACE__ . '\\refuse_feed_request', 99 );

	\remove_action( 'wp_head', 'feed_links', 2 );
	\remove_action( 'wp_head', 'feed_links_extra', 3 );

	// Backstop for a feed reached without the main request (a theme calling do_feed()).
	foreach ( types() as $type ) {
		\add_action( 'do_feed_' . $type, __NAMESPACE__ . '\\refuse', 0 );
	}
}

/**
 * Feed types switched off.
 *
 * @return array List of feed names.
 */
function types(): array {
	/**
	 * Filters the feed types answered with a 404.
	 *
	 * A feed a plugin adds with add_feed() (a podcast feed) keeps working unless
	 * it is added here.
	 *
	 * @param array $types Feed names. Default rdf, rss, rss2, atom.
	 */
	$types = \apply_filters( 'isudev_library/extensions/disable_feeds/types', array( 'rdf', 'rss', 'rss2', 'atom' ) );

	return \is_array( $types ) ? \array_values( \array_filter( $types, 'is_string' ) ) : array();
}

/**
 * The feed template a `feed` query var asks for, the way do_feed() resolves it. Pure.
 *
 * `/feed/` and `?feed=feed` mean the default feed; a `comments-` prefix and
 * leading underscores are dropped by WP_Query and do_feed() respectively.
 *
 * @param string $feed         Raw `feed` query var.
 * @param string $default_feed Result of get_default_feed().
 * @return string
 */
function feed_type( string $feed, string $default_feed ): string {
	$feed = \ltrim( \str_replace( 'comments-', '', $feed ), '_' );

	return ( '' === $feed || 'feed' === $feed ) ? $default_feed : $feed;
}

/**
 * Turn a request for a disabled feed into a plain 404 request. Pure apart from
 * the feed list and the default feed passed in.
 *
 * @param array  $query_vars   Parsed query vars.
 * @param array  $types        Disabled feed names.
 * @param string $default_feed Result of get_default_feed().
 * @return array
 */
function without_feed( array $query_vars, array $types, string $default_feed ): array {
	$feed = $query_vars['feed'] ?? '';

	if ( ! \is_string( $feed ) || '' === $feed || ! \in_array( feed_type( $feed, $default_feed ), $types, true ) ) {
		return $query_vars;
	}

	unset( $query_vars['feed'], $query_vars['withcomments'], $query_vars['withoutcomments'] );

	// WP::send_headers() sends 404 with an HTML Content-Type; WP_Query::parse_query() calls set_404().
	$query_vars['error'] = '404';

	return $query_vars;
}

/**
 * `request` filter: refuse disabled feeds.
 *
 * @param array $query_vars Parsed query vars.
 * @return array
 */
function refuse_feed_request( $query_vars ): array {
	return without_feed( \is_array( $query_vars ) ? $query_vars : array(), types(), (string) \get_default_feed() );
}

/**
 * End a feed request with a bare 404.
 *
 * @return void
 */
function refuse(): void {
	\status_header( 404 );
	\nocache_headers();
	\header( 'Content-Type: text/plain; charset=utf-8' );
	\header_remove( 'ETag' );
	echo 'Feeds are disabled.';
	exit;
}
