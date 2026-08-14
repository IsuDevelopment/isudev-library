<?php
/**
 * Render helpers for the bento grid wrapper block.
 *
 * Pure functions only — no WordPress calls. render.php gathers attributes
 * and passes them in.
 *
 * @package IsuDevLibrary\Blocks\BentoGrid
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\BentoGrid;

defined( 'ABSPATH' ) || exit;

const DEFAULT_COLUMNS = array(
	'desktop' => 6,
	'tablet'  => 4,
	'mobile'  => 2,
);

const DEFAULT_GAP = array(
	'desktop' => 16,
	'tablet'  => 12,
	'mobile'  => 8,
);

const DEFAULT_MIN_CARD_HEIGHT = 150;

/**
 * Read a per-device integer out of a columns/gap-shaped array. Pure.
 *
 * @param array  $values  Candidate per-device values.
 * @param string $device  'desktop', 'tablet' or 'mobile'.
 * @param int    $fallback Value used when the device key is missing or not numeric.
 * @return int
 */
function device_value( array $values, string $device, int $fallback ): int {
	return isset( $values[ $device ] ) && \is_numeric( $values[ $device ] ) ? (int) $values[ $device ] : $fallback;
}

/**
 * Build the grid wrapper's inline CSS custom properties. Pure.
 *
 * @param array $columns        Candidate `columns` attribute.
 * @param array $gap            Candidate `gap` attribute.
 * @param int   $min_card_height Minimum row track height, in pixels.
 * @return string A `--custom-property: value;` declaration list.
 */
function grid_style( array $columns, array $gap, int $min_card_height ): string {
	return \sprintf(
		'--bento-cols-desktop: %d; --bento-cols-tablet: %d; --bento-cols-mobile: %d; --bento-gap-desktop: %dpx; --bento-gap-tablet: %dpx; --bento-gap-mobile: %dpx; --bento-min-card-height: %dpx;',
		device_value( $columns, 'desktop', DEFAULT_COLUMNS['desktop'] ),
		device_value( $columns, 'tablet', DEFAULT_COLUMNS['tablet'] ),
		device_value( $columns, 'mobile', DEFAULT_COLUMNS['mobile'] ),
		device_value( $gap, 'desktop', DEFAULT_GAP['desktop'] ),
		device_value( $gap, 'tablet', DEFAULT_GAP['tablet'] ),
		device_value( $gap, 'mobile', DEFAULT_GAP['mobile'] ),
		$min_card_height
	);
}
