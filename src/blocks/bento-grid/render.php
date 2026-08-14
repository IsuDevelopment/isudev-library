<?php
/**
 * Server render for isudev/bento-grid.
 *
 * @package IsuDevLibrary\Blocks\BentoGrid
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the cards).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\BentoGrid;

defined( 'ABSPATH' ) || exit;

/*
 * Every card rendered nothing, or none was ever inserted (the variation
 * picker is skipped in some automated content-creation flow) — an empty
 * grid is not worth the wrapper markup.
 */
if ( '' === \trim( $content ) ) {
	return;
}

$columns         = isset( $attributes['columns'] ) && \is_array( $attributes['columns'] ) ? $attributes['columns'] : array();
$gap             = isset( $attributes['gap'] ) && \is_array( $attributes['gap'] ) ? $attributes['gap'] : array();
$min_card_height = isset( $attributes['minCardHeight'] ) ? \absint( $attributes['minCardHeight'] ) : DEFAULT_MIN_CARD_HEIGHT;

$wrapper_attributes = \get_block_wrapper_attributes(
	array(
		'class' => 'isudev-bento-grid',
		'style' => grid_style( $columns, $gap, $min_card_height ),
	)
);

$markup = \sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );

/*
 * $wrapper_attributes escapes its own output; $content is inner-block
 * markup, which WordPress already renders safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
