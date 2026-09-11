<?php
/**
 * Gravity Forms theme lock: one form theme, no picker.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\GravityFormsThemeLock;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_filter( 'gform_form_block_attributes', __NAMESPACE__ . '\\set_default_theme' );
	\add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\hide_theme_panel' );
}

/**
 * The form theme every block defaults to.
 *
 * @return string
 */
function theme(): string {
	/**
	 * Filters the Gravity Forms theme the block is pinned to.
	 *
	 * @param string $theme Default `gravity-theme`, the plugin's own unstyled base.
	 */
	$theme = \apply_filters( 'isudev_library/extensions/gravity_forms_theme_lock/theme', 'gravity-theme' );

	return \is_string( $theme ) && '' !== $theme ? $theme : 'gravity-theme';
}

/**
 * Set the block attribute default.
 *
 * Only the default: a block already saved with another theme keeps it until it
 * is re-inserted, because the attribute is serialised into post content.
 *
 * @param array $attributes Block attribute definitions.
 * @return array
 */
function set_default_theme( $attributes ): array {
	$attributes = \is_array( $attributes ) ? $attributes : array();

	if ( isset( $attributes['theme'] ) && \is_array( $attributes['theme'] ) ) {
		$attributes['theme']['default'] = theme();
	}

	return $attributes;
}

/**
 * Hide the block's theme panel in the editor.
 *
 * Gravity Forms strips the block wrapper's own classes, so there is nothing
 * block-specific to target; the panels themselves are. The last panel holds the
 * form selector and stays.
 *
 * @return void
 */
function hide_theme_panel(): void {
	\wp_add_inline_style(
		'wp-edit-blocks',
		'.gform-block__panel:not(:last-child) { display: none !important; }'
	);
}
