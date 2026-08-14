<?php
/**
 * Server render for isudev/selling-points.
 *
 * @package IsuDevLibrary\Blocks\SellingPoints
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the selling points).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\SellingPoints;

use function IsuDevLibrary\Config\get_block_config;

defined( 'ABSPATH' ) || exit;

$block_name = 'isudev/selling-points';

if ( '' === \trim( $content ) ) {
	return;
}

$namespace = isset( $attributes['_namespace'] ) && \is_string( $attributes['_namespace'] ) ? $attributes['_namespace'] : '';

$default_span = sanitize_default_span( get_block_config( $block_name, 'defaultColumns', 12, $namespace ), 12 );
$animate_in   = ! empty( $attributes['animateIn'] );

$wrapper_attributes = \get_block_wrapper_attributes(
	array(
		'class' => \implode( ' ', \array_map( '\sanitize_html_class', wrapper_classes( $animate_in ) ) ),
		'style' => \sprintf( '--isudev-selling-point-default-span: %d;', $default_span ),
	)
);

$markup = \sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );

/*
 * $wrapper_attributes escapes its own output; $content is inner-block
 * markup, which WordPress already renders safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
