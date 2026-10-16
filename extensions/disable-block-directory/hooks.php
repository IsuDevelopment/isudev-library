<?php
/**
 * Disable the block directory: no WordPress.org block suggestions in the inserter, no search endpoint.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\DisableBlockDirectory;

defined( 'ABSPATH' ) || exit;

const ROUTE = '/wp/v2/block-directory/search';

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// Core: default-filters.php, priority 10.
	\remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
	\add_filter( 'rest_endpoints', __NAMESPACE__ . '\\remove_route' );
}

/**
 * Drop the block directory search route.
 *
 * @param array $endpoints Registered REST routes.
 * @return array
 */
function remove_route( $endpoints ): array {
	$endpoints = \is_array( $endpoints ) ? $endpoints : array();
	unset( $endpoints[ ROUTE ] );

	return $endpoints;
}
