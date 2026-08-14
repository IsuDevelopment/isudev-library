<?php
/**
 * Render helpers for the selling points wrapper block.
 *
 * Pure functions only — no get_block_config(). render.php gathers that
 * context and passes it in.
 *
 * @package IsuDevLibrary\Blocks\SellingPoints
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\SellingPoints;

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the default column span, falling back when not numeric. Pure.
 *
 * @param mixed $value Candidate span, from config.
 * @param int   $fallback Value used when `$value` is not numeric.
 * @return int
 */
function sanitize_default_span( $value, int $fallback ): int {
	return \is_numeric( $value ) ? (int) $value : $fallback;
}

/**
 * Build the wrapper's class list. Pure, and deliberately unsanitized.
 *
 * The caller passes each name through sanitize_html_class(), which is a
 * WordPress function and would break this file's purity.
 *
 * @param bool $animate_in Whether items reveal on scroll.
 * @return array
 */
function wrapper_classes( bool $animate_in ): array {
	$classes = array( 'isudev-selling-points' );

	if ( $animate_in ) {
		$classes[] = 'has-animate-in';
	}

	return $classes;
}
