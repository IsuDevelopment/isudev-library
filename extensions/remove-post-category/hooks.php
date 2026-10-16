<?php
/**
 * Remove post categories: detach `category` from `post`, and hide the taxonomy
 * when nothing else uses it. Details in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\RemovePostCategory;

defined( 'ABSPATH' ) || exit;

const TAXONOMY = 'category';
const BLOCK    = 'core/categories';

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// Late: after themes and plugins attach the taxonomy to their own post types (init 10).
	\add_action( 'init', __NAMESPACE__ . '\\detach', 100 );

	// Before redirect_canonical (10), which would otherwise redirect ?cat= to a pretty URL.
	\add_action( 'template_redirect', __NAMESPACE__ . '\\block_archives', 1 );

	\add_filter( 'pre_render_block', __NAMESPACE__ . '\\short_circuit_block', 10, 2 );
	\add_filter( 'wp_sitemaps_taxonomies', __NAMESPACE__ . '\\remove_sitemap_taxonomy' );
	\add_filter( 'wpseo_sitemap_exclude_taxonomy', __NAMESPACE__ . '\\yoast_exclude_taxonomy', 10, 2 );
	\add_filter( 'feed_links_extra_show_category_feed', __NAMESPACE__ . '\\show_feed_link' );
}

/**
 * Whether the taxonomy is attached to no post type at all.
 *
 * @return bool
 */
function is_orphaned(): bool {
	$taxonomy = \get_taxonomy( TAXONOMY );

	return $taxonomy instanceof \WP_Taxonomy && array() === \array_values( (array) $taxonomy->object_type );
}

/**
 * Detach the taxonomy from posts; when nothing else uses it, hide it everywhere
 * and take its block out of the inserter.
 *
 * @return void
 */
function detach(): void {
	\unregister_taxonomy_for_object_type( TAXONOMY, 'post' );

	if ( ! is_orphaned() ) {
		return;
	}

	$taxonomy = \get_taxonomy( TAXONOMY );

	$taxonomy->public             = false;
	$taxonomy->publicly_queryable = false;
	$taxonomy->show_ui            = false;
	$taxonomy->show_in_menu       = false;
	$taxonomy->show_in_nav_menus  = false;
	$taxonomy->show_tagcloud      = false;
	$taxonomy->show_in_rest       = false;
	$taxonomy->rewrite            = false;
	$taxonomy->query_var          = false;

	// Core blocks registered on init 10; the editor reads supports from this object later.
	$block = \WP_Block_Type_Registry::get_instance()->get_registered( BLOCK );

	if ( $block instanceof \WP_Block_Type ) {
		$block->supports             = \is_array( $block->supports ) ? $block->supports : array();
		$block->supports['inserter'] = false;
	}
}

/**
 * Send category archives (also ?cat=, ?category_name= and their feeds) to the theme's 404.
 *
 * @return void
 */
function block_archives(): void {
	global $wp_query;

	if ( ! $wp_query instanceof \WP_Query || ! $wp_query->is_category() || ! is_orphaned() ) {
		return;
	}

	$wp_query->set_404();

	// set_404() keeps is_feed, and template-loader.php would still call do_feed().
	$wp_query->is_feed = false;

	\status_header( 404 );
	\nocache_headers();

	if ( ! \headers_sent() ) {
		// WP::send_headers() already queued a feed's Content-Type and ETag.
		\header( 'Content-Type: ' . \get_option( 'html_type' ) . '; charset=' . \get_option( 'blog_charset' ) );
		\header_remove( 'ETag' );
	}
}

/**
 * Render Categories List blocks that list categories as nothing.
 *
 * @param string|null $pre_render   Short-circuited output.
 * @param array       $parsed_block Parsed block.
 * @return string|null
 */
function short_circuit_block( $pre_render, $parsed_block ) {
	if ( null !== $pre_render || ! \is_array( $parsed_block ) || BLOCK !== ( $parsed_block['blockName'] ?? '' ) ) {
		return $pre_render;
	}

	$taxonomy = $parsed_block['attrs']['taxonomy'] ?? TAXONOMY;

	return TAXONOMY === $taxonomy && is_orphaned() ? '' : $pre_render;
}

/**
 * Keep the hidden taxonomy out of the core sitemap.
 *
 * @param array $taxonomies Taxonomy objects keyed by name.
 * @return array
 */
function remove_sitemap_taxonomy( $taxonomies ): array {
	$taxonomies = \is_array( $taxonomies ) ? $taxonomies : array();

	if ( is_orphaned() ) {
		unset( $taxonomies[ TAXONOMY ] );
	}

	return $taxonomies;
}

/**
 * Yoast SEO: no sitemap for the hidden taxonomy.
 *
 * @param bool   $exclude  Whether excluded.
 * @param string $taxonomy Taxonomy.
 * @return bool
 */
function yoast_exclude_taxonomy( $exclude, $taxonomy ): bool {
	return TAXONOMY === $taxonomy && is_orphaned() ? true : (bool) $exclude;
}

/**
 * No category feed link while the taxonomy is hidden.
 *
 * @param bool $show Whether to print it.
 * @return bool
 */
function show_feed_link( $show ): bool {
	return is_orphaned() ? false : (bool) $show;
}
