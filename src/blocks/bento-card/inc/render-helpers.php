<?php
/**
 * Render helpers for the bento card block.
 *
 * Pure functions only — no WordPress calls. render.php gathers attributes
 * and passes them in.
 *
 * @package IsuDevLibrary\Blocks\BentoCard
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\BentoCard;

defined( 'ABSPATH' ) || exit;

/**
 * Build the card's inline `grid-column` / `grid-row` custom properties. Pure.
 *
 * The stylesheet's `var()` calls resolve to the complete `span N` value, so
 * the unit ("span") lives here rather than being reconstructed in CSS.
 *
 * @param array $grid_column Candidate `gridColumn` attribute.
 * @param array $grid_row    Candidate `gridRow` attribute.
 * @return string A `--custom-property: value;` declaration list.
 */
function card_style( array $grid_column, array $grid_row ): string {
	$declarations = array();

	foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
		$column_span = $grid_column[ $device ]['span'] ?? null;
		$row_span    = $grid_row[ $device ]['span'] ?? null;

		if ( \is_numeric( $column_span ) ) {
			$declarations[] = \sprintf( '--grid-col-%s: span %d', $device, (int) $column_span );
		}

		if ( \is_numeric( $row_span ) ) {
			$declarations[] = \sprintf( '--grid-row-%s: span %d', $device, (int) $row_span );
		}
	}

	return \implode( '; ', $declarations );
}
