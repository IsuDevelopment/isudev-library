<?php
/**
 * Render helpers for the image step guide wrapper block.
 *
 * Pure functions only — no get_block_config(), no other side-effecting or
 * config-reading calls. render.php gathers that context and passes it in.
 *
 * @package IsuDevLibrary\Blocks\ImageStepGuide
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ImageStepGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the display style, defaulting to list. Pure.
 *
 * @param string $value Candidate display style.
 * @return string 'list' or 'grid'.
 */
function sanitize_display_style( string $value ): string {
	return 'grid' === $value ? 'grid' : 'list';
}

/**
 * Build the wrapper's class list. Pure, and deliberately unsanitized.
 *
 * The caller passes each name through sanitize_html_class(), which is a
 * WordPress function and would break this file's purity.
 *
 * @param string $display_style 'list' or 'grid'.
 * @param bool   $use_lightbox  Whether steps enlarge their image on click.
 * @return array
 */
function wrapper_classes( string $display_style, bool $use_lightbox ): array {
	$classes = array(
		'isudev-image-step-guide',
		'is-display-' . $display_style,
	);

	if ( $use_lightbox ) {
		$classes[] = 'has-lightbox-images';
	}

	return $classes;
}
