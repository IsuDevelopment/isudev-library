<?php
/**
 * Server render for isudev/read-more.
 *
 * @package IsuDevLibrary\Blocks\ReadMore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused; this block has no InnerBlocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ReadMore;

defined( 'ABSPATH' ) || exit;

$link_data = isset( $attributes['link'] ) && \is_array( $attributes['link'] ) ? $attributes['link'] : array();
$url       = isset( $link_data['url'] ) ? (string) $link_data['url'] : '';

if ( '' === $url ) {
	return;
}

printf(
	'<a %1$s href="%2$s"></a>',
	\get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	\esc_url( $url )
);
