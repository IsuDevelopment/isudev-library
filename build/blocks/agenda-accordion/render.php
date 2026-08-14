<?php
/**
 * Server render for isudev/agenda-accordion.
 *
 * @package IsuDevLibrary\Blocks\AgendaAccordion
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the accordion items).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\AgendaAccordion;

use function IsuDevLibrary\Config\get_block_config;

defined( 'ABSPATH' ) || exit;

$block_name = 'isudev/agenda-accordion';

/*
 * Every item rendered nothing, or there are none — an accordion with no
 * openable content is not worth the wrapper markup.
 */
if ( '' === \trim( $content ) ) {
	return;
}

$namespace = isset( $attributes['_namespace'] ) && \is_string( $attributes['_namespace'] ) ? $attributes['_namespace'] : '';

$allow_multiple = (bool) get_block_config( $block_name, 'allowMultiple', true, $namespace );
$allow_toggle   = (bool) get_block_config( $block_name, 'allowToggle', true, $namespace );

$wrapper_attributes = \get_block_wrapper_attributes(
	\array_merge(
		array( 'class' => 'isudev-agenda-accordion' ),
		behavior_attributes( $allow_multiple, $allow_toggle )
	)
);

$markup = \sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );

/*
 * $wrapper_attributes escapes its own output; $content is inner-block
 * markup, which WordPress already renders safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
