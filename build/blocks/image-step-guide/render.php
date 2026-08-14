<?php
/**
 * Server render for isudev/image-step-guide.
 *
 * @package IsuDevLibrary\Blocks\ImageStepGuide
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the steps).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ImageStepGuide;

use function IsuDevLibrary\Config\get_block_config;

defined( 'ABSPATH' ) || exit;

$block_name = 'isudev/image-step-guide';

$namespace = isset( $attributes['_namespace'] ) && \is_string( $attributes['_namespace'] ) ? $attributes['_namespace'] : '';

$display_style_disabled   = (bool) get_block_config( $block_name, 'displayStyle.disable', false, $namespace );
$configured_display_style = sanitize_display_style( (string) get_block_config( $block_name, 'displayStyle.value', 'list', $namespace ) );
$attribute_display_style  = sanitize_display_style( isset( $attributes['displayStyle'] ) && \is_string( $attributes['displayStyle'] ) ? $attributes['displayStyle'] : 'list' );

$display_style = $display_style_disabled ? $configured_display_style : $attribute_display_style;

$lightbox_disabled = (bool) get_block_config( $block_name, 'imageLightbox.disable', false, $namespace );
$use_lightbox      = ! $lightbox_disabled && ! empty( $attributes['useImageLightbox'] );

$wrapper_args = array(
	'class' => \implode( ' ', \array_map( '\sanitize_html_class', wrapper_classes( $display_style, $use_lightbox ) ) ),
);

if ( $use_lightbox ) {
	// Read by view.js's lightbox close button; get_block_wrapper_attributes() escapes it.
	$wrapper_args['data-lightbox-close-label'] = \__( 'Close', 'isudev-library' );
}

$wrapper_attributes = \get_block_wrapper_attributes( $wrapper_args );

$markup = \sprintf(
	'<div %1$s><div class="isudev-image-step-guide__items">%2$s</div></div>',
	$wrapper_attributes,
	$content
);

/*
 * $wrapper_attributes escapes its own output; $content is inner-block
 * markup, which WordPress already renders safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
