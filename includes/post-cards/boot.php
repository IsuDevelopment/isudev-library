<?php
/**
 * Post cards: hook registration. Every callback is a no-op while
 * `isudev/post-card` is disabled.
 *
 * Feature guide: guides/post-cards.md.
 *
 * @package IsuDevLibrary\PostCards
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\PostCards;

use IsuDevLibrary\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration.
 *
 * The theme.json filter is added here, not later: core caches theme data on
 * first read and never re-applies the filter.
 *
 * @return void
 */
function boot(): void {
	\add_filter( 'default_wp_template_part_areas', __NAMESPACE__ . '\\filter_part_areas' );
	\add_filter( 'wp_theme_json_data_theme', __NAMESPACE__ . '\\filter_theme_json' );
	\add_action( 'rest_api_init', __NAMESPACE__ . '\\maybe_register_routes' );
	\add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_data' );
	\add_filter( 'register_block_type_args', __NAMESPACE__ . '\\skip_inner_blocks', 10, 2 );
	\add_filter( 'render_block_data', __NAMESPACE__ . '\\enter_query_loop' );
	\add_filter( 'render_block_core/post-template', __NAMESPACE__ . '\\leave_query_loop' );
	\add_filter( 'render_block_context', __NAMESPACE__ . '\\flag_query_loop', 10, 2 );
}

/**
 * Query Loop depth: core/post-template renders its children without `queryId`.
 *
 * @param int $change +1, -1 or 0 to read.
 * @return int
 */
function query_loop_depth( int $change = 0 ): int {
	static $depth = 0;

	$depth = \max( 0, $depth + $change );

	return $depth;
}

/**
 * Count a core/post-template starting to render.
 *
 * @param array $parsed_block Parsed block.
 * @return array
 */
function enter_query_loop( $parsed_block ) {
	if ( \is_array( $parsed_block ) && 'core/post-template' === ( $parsed_block['blockName'] ?? '' ) ) {
		query_loop_depth( 1 );
	}

	return $parsed_block;
}

/**
 * Count a core/post-template finished.
 *
 * @param string $html Rendered block.
 * @return string
 */
function leave_query_loop( $html ) {
	query_loop_depth( -1 );

	return $html;
}

/**
 * Tell a card it sits in a Query Loop, so it may show the loop's post.
 *
 * @param array $context      Block context.
 * @param array $parsed_block Parsed block.
 * @return array
 */
function flag_query_loop( $context, $parsed_block ) {
	if ( \is_array( $context ) && query_loop_depth() > 0 && 'isudev/post-card' === ( $parsed_block['blockName'] ?? '' ) ) {
		$context[ CONTEXT_IN_LOOP ] = true;
	}

	return $context;
}

/**
 * Both blocks render their inner blocks themselves, with the card's post in
 * context; core pre-rendering them would run every card twice.
 *
 * @param array  $args Block type arguments.
 * @param string $name Block name.
 * @return array
 */
function skip_inner_blocks( $args, $name ) {
	if ( \is_array( $args ) && \in_array( $name, array( 'isudev/post-card', 'isudev/post-cards' ), true ) ) {
		$args['skip_inner_blocks'] = true;
	}

	return $args;
}

/**
 * Whether the feature is on.
 *
 * @return bool
 */
function enabled(): bool {
	return Registry::is_enabled( 'post-card' );
}

/**
 * Add the card template part area.
 *
 * @param array $areas Areas.
 * @return array
 */
function filter_part_areas( $areas ) {
	return enabled() && \is_array( $areas ) ? register_part_area( $areas ) : $areas;
}

/**
 * Declare theme card parts under the card area.
 *
 * @param \WP_Theme_JSON_Data $theme_json Theme origin data.
 * @return \WP_Theme_JSON_Data
 */
function filter_theme_json( $theme_json ) {
	return enabled() ? declare_theme_parts( $theme_json ) : $theme_json;
}

/**
 * Register REST routes.
 *
 * @return void
 */
function maybe_register_routes(): void {
	if ( enabled() ) {
		register_routes();
	}
}

/**
 * Publish option definitions and theme templates to the editor as
 * `window.isudevPostCards` (assigned: this plugin's own name).
 *
 * @return void
 */
function enqueue_editor_data(): void {
	if ( ! enabled() ) {
		return;
	}

	$handle = \generate_block_asset_handle( 'isudev/post-card', 'editorScript' );
	$json   = \wp_json_encode(
		array(
			'options'   => \array_values( option_definitions() ),
			'templates' => available_templates(),
		)
	);

	if ( \is_string( $json ) ) {
		\wp_add_inline_script( $handle, \sprintf( 'window.isudevPostCards = %s;', $json ), 'before' );
	}
}
