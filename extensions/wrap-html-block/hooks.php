<?php
/**
 * Custom HTML block wrapper: give `core/html` the element every other block has.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\WrapHtmlBlock;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_filter( 'render_block_core/html', __NAMESPACE__ . '\\wrap', 10, 1 );
}

/**
 * The wrapper's class attribute.
 *
 * @return string
 */
function wrapper_class(): string {
	/**
	 * Filters the class on the Custom HTML block wrapper.
	 *
	 * @param string $class Default `wp-block-html`, the class the block would
	 *                      have carried had it rendered a wrapper.
	 */
	$class = \apply_filters( 'isudev_library/extensions/wrap_html_block/class', 'wp-block-html' );

	return \is_string( $class ) ? $class : 'wp-block-html';
}

/**
 * Wrap the rendered block.
 *
 * @param string $block_content Rendered block markup.
 * @return string
 */
function wrap( $block_content ): string {
	$block_content = (string) $block_content;

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
