<?php
/**
 * Gravity Forms wrapper: give the form block an element to style against.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\GravityFormsWrapper;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_filter( 'render_block_gravityforms/form', __NAMESPACE__ . '\\wrap', 10, 1 );
}

/**
 * The wrapper's class attribute.
 *
 * @return string
 */
function wrapper_class(): string {
	/**
	 * Filters the class on the Gravity Forms block wrapper.
	 *
	 * @param string $class Default `wp-block-gravityforms-form`, the class the
	 *                      block would have carried had it rendered a wrapper.
	 */
	$class = \apply_filters( 'isudev_library/extensions/gravity_forms_wrapper/class', 'wp-block-gravityforms-form' );

	return \is_string( $class ) ? $class : 'wp-block-gravityforms-form';
}

/**
 * Wrap the rendered block.
 *
 * @param string $block_content Rendered block markup.
 * @return string
 */
function wrap( $block_content ): string {
	$block_content = (string) $block_content;

	// An empty render means no form was selected, or the form was deleted.
	// Wrapping nothing would leave an empty box in the layout.
	if ( '' === \trim( $block_content ) ) {
		return $block_content;
	}

	return \sprintf(
		'<div class="%1$s">%2$s</div>',
		\esc_attr( wrapper_class() ),
		$block_content
	);
}
