<?php
/**
 * REST controller for block toggles and diagnostics.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\REST;

use IsuDevLibrary\Admin;
use IsuDevLibrary\Config;
use IsuDevLibrary\Registry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

use const IsuDevLibrary\PATH;
use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const NAMESPACE_V1 = 'isudev-library/v1';

/**
 * Hook registration.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );
}

/**
 * Whether the current user may read or write the library configuration.
 *
 * Mirrors the admin panel gate: hiding the panel also closes the endpoints, so
 * `show_admin => false` is not merely cosmetic.
 *
 * @return bool
 */
function permission_check(): bool {
	return Admin\show_admin();
}

/**
 * Register the routes.
 *
 * @return void
 */
function register_routes(): void {
	\register_rest_route(
		NAMESPACE_V1,
		'/blocks',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => __NAMESPACE__ . '\\get_blocks',
			'permission_callback' => __NAMESPACE__ . '\\permission_check',
		)
	);

	\register_rest_route(
		NAMESPACE_V1,
		'/blocks/(?P<slug>[a-z0-9-]+)',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\update_block',
			'permission_callback' => __NAMESPACE__ . '\\permission_check',
			'args'                => array(
				'enabled' => array(
					'type'     => 'boolean',
					'required' => true,
				),
			),
		)
	);
}

/**
 * Metadata from the compiled manifest, keyed by block directory name.
 *
 * The block type registry only holds blocks that were registered, and a disabled
 * block is never registered, so this is the only place its real title, icon and
 * description can come from.
 *
 * @return array slug => decoded block.json contents.
 */
function manifest_metadata(): array {
	static $manifest = null;

	if ( null === $manifest ) {
		$file     = PATH . 'build/blocks-manifest.php';
		$manifest = \is_readable( $file ) ? (array) require $file : array();
	}

	return $manifest;
}

/**
 * Shape one block for the REST response.
 *
 * @param array $block Decorated descriptor from Registry::blocks().
 * @return array
 */
function prepare_block( array $block ): array {
	$type = \WP_Block_Type_Registry::get_instance()->get_registered( $block['name'] );
	$meta = manifest_metadata()[ $block['slug'] ] ?? array();

	/*
	 * Prefer the registered type, which reflects anything a filter changed at
	 * registration time. Fall back to the manifest, because a disabled block is
	 * never registered and would otherwise report its slug as its title — the
	 * panel would lose the name of every block the user just switched off.
	 */
	$title       = $type && $type->title ? $type->title : ( $meta['title'] ?? $block['slug'] );
	$description = $type && $type->description ? $type->description : ( $meta['description'] ?? '' );
	$icon        = $type && \is_string( $type->icon ) ? $type->icon : $meta['icon'] ?? 'block-default';

	return array(
		'slug'        => $block['slug'],
		'name'        => $block['name'],
		'title'       => (string) $title,
		'description' => (string) $description,
		'icon'        => \is_string( $icon ) ? $icon : 'block-default',
		'enabled'     => (bool) $block['enabled'],
		'source'      => (string) $block['source'],
		'locked'      => (bool) $block['locked'],
		'requires'    => \array_values( $block['requires'] ),
		'dependents'  => \array_values( $block['dependents'] ),
	);
}

/**
 * Diagnostics payload for the Settings tab.
 *
 * @return array
 */
function diagnostics(): array {
	$blocks  = Registry::blocks();
	$sources = Config\config_sources();

	$registered = 0;
	foreach ( $blocks as $block ) {
		if ( $block['enabled'] ) {
			++$registered;
		}
	}

	return array(
		'version'      => VERSION,
		'discovered'   => \count( $blocks ),
		'registered'   => $registered,
		'configParent' => $sources['parent'],
		'configChild'  => $sources['child'],
	);
}

/**
 * GET /blocks
 *
 * @return WP_REST_Response
 */
function get_blocks(): WP_REST_Response {
	$blocks = array();

	foreach ( Registry::blocks() as $block ) {
		$blocks[] = prepare_block( $block );
	}

	return new WP_REST_Response(
		array(
			'blocks'      => $blocks,
			'diagnostics' => diagnostics(),
		),
		200
	);
}

/**
 * POST /blocks/<slug>
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function update_block( WP_REST_Request $request ) {
	$slug   = (string) $request->get_param( 'slug' );
	$blocks = Registry::blocks();

	if ( ! isset( $blocks[ $slug ] ) ) {
		return new WP_Error(
			'isudev_library_unknown_block',
			\__( 'Unknown block.', 'isudev-library' ),
			array( 'status' => 404 )
		);
	}

	if ( $blocks[ $slug ]['locked'] ) {
		return new WP_Error(
			'isudev_library_block_locked',
			\__( 'This block is managed in code and cannot be toggled here.', 'isudev-library' ),
			array( 'status' => 403 )
		);
	}

	$option = \get_option( Registry::OPTION, array() );
	$option = \is_array( $option ) ? $option : array();

	$option[ $slug ] = (bool) $request->get_param( 'enabled' );

	\update_option( Registry::OPTION, $option );
	Registry::flush();

	$refreshed = Registry::blocks();

	return new WP_REST_Response( prepare_block( $refreshed[ $slug ] ), 200 );
}
