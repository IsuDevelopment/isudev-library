<?php
/**
 * Server render for isudev/content-sidebar-main.
 *
 * @package IsuDevLibrary\Blocks\ContentSidebarMain
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ContentSidebarMain;

defined( 'ABSPATH' ) || exit;

$wrapper_attributes = \get_block_wrapper_attributes( array( 'class' => 'isudev-content-sidebar__main' ) );

$markup = \sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );

/*
 * $wrapper_attributes escapes its own output; $content is inner-block
 * markup, which WordPress already renders safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- See the comment above.
