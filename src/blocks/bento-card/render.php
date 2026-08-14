<?php
/**
 * Server render for isudev/bento-card.
 *
 * @package IsuDevLibrary\Blocks\BentoCard
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the card's own content).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\BentoCard;

defined( 'ABSPATH' ) || exit;

$grid_column = isset( $attributes['gridColumn'] ) && \is_array( $attributes['gridColumn'] ) ? $attributes['gridColumn'] : array();
$grid_row    = isset( $attributes['gridRow'] ) && \is_array( $attributes['gridRow'] ) ? $attributes['gridRow'] : array();

$wrapper_attributes = \get_block_wrapper_attributes(
	array(
		'class' => 'isudev-bento-card',
		'style' => card_style( $grid_column, $grid_row ),
	)
);

$markup = \sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );

/*
 * $wrapper_attributes escapes its own output; $content is inner-block
 * markup, which WordPress already renders safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
