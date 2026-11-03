<?php
/**
 * Server render for isudev/post-card.
 *
 * Template resolution, options and hooks: guides/post-cards.md.
 *
 * @package IsuDevLibrary\Blocks\PostCard
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused; inner blocks of a custom layout are rendered with the card's context.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\PostCard;

use function IsuDevLibrary\PostCards\card_classes;
use function IsuDevLibrary\PostCards\card_spans;
use function IsuDevLibrary\PostCards\render_card_inner;
use function IsuDevLibrary\PostCards\resolve_card;
use function IsuDevLibrary\PostCards\shown_ids;
use function IsuDevLibrary\PostCards\span_style;

defined( 'ABSPATH' ) || exit;

$card = resolve_card( $attributes, $block->context );

if ( null === $card ) {
	return;
}

$inner_blocks = 'custom' === ( $attributes['layout'] ?? '' ) ? (array) ( $block->parsed_block['innerBlocks'] ?? array() ) : array();
$inner        = render_card_inner( $card['post'], $card['variant'], $card['options'], $attributes, $inner_blocks );

if ( '' === \trim( $inner ) ) {
	return;
}

shown_ids( array( $card['post']->ID ) );

$wrapper_attributes = \get_block_wrapper_attributes(
	\array_filter(
		array(
			'class' => \implode( ' ', card_classes( $card['post'], $card['variant'], $card['options'] ) ),
			'style' => span_style( card_spans( $attributes, $block->context ) ),
			'role'  => empty( $block->context['isudev/inList'] ) ? '' : 'listitem',
		)
	)
);

\printf(
	'<div %1$s>%2$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes.
	$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered blocks or the theme's template.
);
