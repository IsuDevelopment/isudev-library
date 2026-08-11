<?php
/**
 * Server render for isudev/social-share.
 *
 * @package IsuDevLibrary\Blocks\SocialShare
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the network buttons).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\SocialShare;

use function IsuDevLibrary\Config\get_block_config;

defined( 'ABSPATH' ) || exit;

$content_alignment = isset( $attributes['contentAlignment'] ) && \is_string( $attributes['contentAlignment'] ) ? $attributes['contentAlignment'] : 'none';
$prefix_text       = isset( $attributes['prefixText'] ) && \is_string( $attributes['prefixText'] ) ? $attributes['prefixText'] : '';
$show_prefix_attr  = ! empty( $attributes['showPrefix'] );
$prefix_position   = isset( $attributes['prefixPosition'] ) && \is_string( $attributes['prefixPosition'] ) ? $attributes['prefixPosition'] : 'before';
$namespace         = isset( $attributes['_namespace'] ) && \is_string( $attributes['_namespace'] ) ? $attributes['_namespace'] : '';

// prefix.disable overrides all prefix settings when true, read from the library config.
$prefix_disabled = (bool) get_block_config( 'isudev/social-share', 'prefix.disable', false, $namespace );
$show_prefix     = ! $prefix_disabled && $show_prefix_attr && '' !== \trim( $prefix_text );

$prefix_markup = '' !== $prefix_text ? \wp_kses( $prefix_text, array() ) : '';

$class_names = array(
	'isudev-share',
	'isudev-share--align-' . $content_alignment,
	'isudev-share--prefix-' . $prefix_position,
);

$wrapper_attributes = \get_block_wrapper_attributes(
	array( 'class' => \implode( ' ', \array_map( '\sanitize_html_class', $class_names ) ) )
);

$prefix_above  = $show_prefix && 'above' === $prefix_position
	? \sprintf( '<p class="isudev-share__prefix isudev-share__prefix--above">%s</p>', $prefix_markup )
	: '';
$prefix_before = $show_prefix && 'before' === $prefix_position
	? \sprintf( '<span class="isudev-share__prefix isudev-share__prefix--before">%s</span>', $prefix_markup )
	: '';
$prefix_after  = $show_prefix && 'after' === $prefix_position
	? \sprintf( '<span class="isudev-share__prefix isudev-share__prefix--after">%s</span>', $prefix_markup )
	: '';

$markup = \sprintf(
	'<div %1$s><div class="isudev-share__content">%2$s<div class="isudev-share__row">%3$s<div class="isudev-share__items">%4$s</div>%5$s</div></div></div>',
	$wrapper_attributes,
	$prefix_above,
	$prefix_before,
	$content,
	$prefix_after
);

/*
 * $wrapper_attributes escapes its own output; $prefix_markup went through
 * wp_kses() above (the RichText value already carries HTML entities, so
 * esc_html() would double-escape it); $content is inner-block markup, which
 * WordPress already renders safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
