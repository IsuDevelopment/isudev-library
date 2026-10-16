<?php
/**
 * Disable emoji: unhook core's emoji detection script, styles and static replacements.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\DisableEmoji;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * Front-end, embed, feed and mail hooks come from default-filters.php, loaded before init.
 * The admin ones come from wp-admin/includes/admin-filters.php, loaded after init, so they
 * are removed on admin_init.
 *
 * @return void
 */
function boot(): void {
	\remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	\remove_action( 'embed_head', 'print_emoji_detection_script' );
	\remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	\remove_action( 'enqueue_embed_scripts', 'wp_enqueue_emoji_styles' );
	\remove_action( 'wp_print_styles', 'print_emoji_styles' );

	\remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	\remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	\remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	\add_action( 'admin_init', __NAMESPACE__ . '\\remove_admin_hooks' );
	\add_filter( 'tiny_mce_plugins', __NAMESPACE__ . '\\remove_tinymce_plugin' );
}

/**
 * Remove the admin emoji hooks.
 *
 * @return void
 */
function remove_admin_hooks(): void {
	\remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	\remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	\remove_action( 'admin_print_styles', 'print_emoji_styles' );
}

/**
 * Drop TinyMCE's `wpemoji` plugin (classic editor, classic widgets).
 *
 * @param array $plugins TinyMCE plugin names.
 * @return array
 */
function remove_tinymce_plugin( $plugins ): array {
	return \is_array( $plugins ) ? \array_values( \array_diff( $plugins, array( 'wpemoji' ) ) ) : array();
}
