<?php
/**
 * Render helpers for the toggle content block.
 *
 * Pure functions only — no get_block_config(), no wp_unique_id(), no other
 * side-effecting or config-reading calls. render.php gathers that context and
 * passes it in.
 *
 * @package IsuDevLibrary\Blocks\ToggleBlocks
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ToggleBlocks;

defined( 'ABSPATH' ) || exit;

/**
 * Default text for the toggle button. Pure.
 *
 * @return string
 */
function default_open_text(): string {
	return \__( 'Open content', 'isudev-library' );
}

/**
 * Default text for the toggle button once the content is open. Pure.
 *
 * @return string
 */
function default_close_text(): string {
	return \__( 'Close content', 'isudev-library' );
}

/**
 * Resolve the button placement, defaulting to bottom. Pure.
 *
 * @param string $value Candidate placement.
 * @return string 'top' or 'bottom'.
 */
function sanitize_placement( string $value ): string {
	return 'top' === $value ? 'top' : 'bottom';
}

/**
 * Resolve the icon position, defaulting to right. Pure.
 *
 * @param string $value Candidate position.
 * @return string 'left' or 'right'.
 */
function sanitize_icon_position( string $value ): string {
	return 'left' === $value ? 'left' : 'right';
}

/**
 * Build the outer wrapper's class list. Pure, and deliberately unsanitized.
 *
 * The caller passes each name through sanitize_html_class(), which is a
 * WordPress function and would break this file's purity.
 *
 * @param string $placement 'top' or 'bottom'.
 * @return array
 */
function wrapper_classes( string $placement ): array {
	return array(
		'isudev-toggle',
		'isudev-toggle--placement-' . $placement,
	);
}

/**
 * Build the button wrapper's class list. Pure, and deliberately unsanitized.
 *
 * @param string $placement    'top' or 'bottom'.
 * @param string $button_style Optional core/button style slug, e.g. 'outline'.
 * @return array
 */
function button_wrapper_classes( string $placement, string $button_style ): array {
	$classes = array(
		'isudev-toggle__button-wrapper',
		'wp-block-button',
		'isudev-toggle__button-wrapper--placement-' . $placement,
	);

	if ( '' !== $button_style ) {
		$classes[] = 'is-style-' . $button_style;
	}

	return $classes;
}

/**
 * Build the toggle button's class list. Pure, and deliberately unsanitized.
 *
 * @param string $icon_position 'left' or 'right'.
 * @return array
 */
function button_classes( string $icon_position ): array {
	return array(
		'isudev-toggle__button',
		'isudev-toggle__button--icon-' . $icon_position,
		'wp-block-button__link',
		'wp-element-button',
	);
}
