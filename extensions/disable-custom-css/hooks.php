<?php
/**
 * Disable custom CSS: deny `edit_css` and stop printing CSS saved by users.
 *
 * User-origin CSS only — Customizer Additional CSS, Global Styles (custom origin) and the
 * per-block `style.css` attribute. Theme-origin theme.json CSS is the theme's code and stays.
 * Details in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\DisableCustomCss;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// Before core's wp_custom_css_kses_init() (init 20) asks for the capability.
	\add_filter( 'map_meta_cap', __NAMESPACE__ . '\\deny_capability', 10, 2 );

	\add_filter( 'wp_get_custom_css', '__return_empty_string', 999 );
	\add_filter( 'wp_theme_json_data_user', __NAMESPACE__ . '\\strip_user_global_styles_css', 999 );

	// Before core's wp_render_custom_css_support_styles (render_block_data, 10).
	\add_filter( 'render_block_data', __NAMESPACE__ . '\\drop_block_css', 9, 1 );

	// Global styles may already have been resolved (and cached) before init 0.
	if ( \function_exists( 'wp_clean_theme_json_cache' ) ) {
		\wp_clean_theme_json_cache();
	}
}

/**
 * Nobody may edit CSS, administrators and super admins included.
 *
 * @param array  $caps Primitive capabilities.
 * @param string $cap  Meta capability.
 * @return array
 */
function deny_capability( $caps, $cap ): array {
	if ( 'edit_css' === $cap ) {
		return array( 'do_not_allow' );
	}

	return \is_array( $caps ) ? $caps : array();
}

/**
 * Remove every `css` key from a theme.json `styles` tree: root, blocks, variations,
 * elements and breakpoints. Pure.
 *
 * @param array $styles theme.json `styles` subtree.
 * @return array
 */
function strip_css_keys( array $styles ): array {
	unset( $styles['css'] );

	foreach ( $styles as $key => $value ) {
		if ( \is_array( $value ) ) {
			$styles[ $key ] = strip_css_keys( $value );
		}
	}

	return $styles;
}

/**
 * Drop custom CSS from the user (Global Styles) origin before it is merged and printed.
 *
 * @param \WP_Theme_JSON_Data $theme_json User theme.json data.
 * @return \WP_Theme_JSON_Data
 */
function strip_user_global_styles_css( $theme_json ) {
	if ( ! $theme_json instanceof \WP_Theme_JSON_Data ) {
		return $theme_json;
	}

	$data = $theme_json->get_data();

	if ( ! isset( $data['styles'] ) || ! \is_array( $data['styles'] ) ) {
		return $theme_json;
	}

	$data['styles'] = strip_css_keys( $data['styles'] );

	// update_with() merges and cannot remove a key, so build a fresh object.
	return new \WP_Theme_JSON_Data( $data, 'custom' );
}

/**
 * Remove a block's saved `style.css` before core turns it into a stylesheet and a class.
 *
 * @param array $parsed_block Parsed block.
 * @return array
 */
function drop_block_css( $parsed_block ): array {
	$parsed_block = \is_array( $parsed_block ) ? $parsed_block : array();

	if ( isset( $parsed_block['attrs']['style'] ) && \is_array( $parsed_block['attrs']['style'] ) ) {
		unset( $parsed_block['attrs']['style']['css'] );
	}

	return $parsed_block;
}
