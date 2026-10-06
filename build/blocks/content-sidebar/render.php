<?php
/**
 * Server render for isudev/content-sidebar.
 *
 * @package IsuDevLibrary\Blocks\ContentSidebar
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (main column and sidebar).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ContentSidebar;

use function IsuDevLibrary\Config\get_block_config;

defined( 'ABSPATH' ) || exit;

$block_name = 'isudev/content-sidebar';

if ( '' === \trim( $content ) ) {
	return;
}

$sidebar_position = sanitize_sidebar_position(
	effective_value(
		(bool) get_block_config( $block_name, 'sidebarPosition.disable', false ),
		get_block_config( $block_name, 'sidebarPosition.value', 'right' ),
		$attributes['sidebarPosition'] ?? 'right'
	)
);

$mobile_position = sanitize_mobile_position(
	effective_value(
		(bool) get_block_config( $block_name, 'mobilePosition.disable', false ),
		get_block_config( $block_name, 'mobilePosition.value', 'bottom' ),
		$attributes['mobilePosition'] ?? 'bottom'
	)
);

$sticky = (bool) effective_value(
	(bool) get_block_config( $block_name, 'sticky.disable', false ),
	get_block_config( $block_name, 'sticky.value', true ),
	$attributes['sticky'] ?? true
);

$wrapper_attributes = \get_block_wrapper_attributes(
	array(
		'class' => \implode( ' ', \array_map( '\sanitize_html_class', wrapper_classes( $sidebar_position, $mobile_position, $sticky ) ) ),
	)
);

$markup = \sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );

/*
 * $wrapper_attributes escapes its own output; $content is inner-block
 * markup, which WordPress already renders safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- See the comment above.
