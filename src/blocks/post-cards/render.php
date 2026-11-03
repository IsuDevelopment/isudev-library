<?php
/**
 * Server render for isudev/post-cards.
 *
 * Runs one query for all query slots, then renders each card with its post in
 * context. Inner blocks are not pre-rendered (`skip_inner_blocks`).
 *
 * @package IsuDevLibrary\Blocks\PostCards
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Empty: inner blocks are skipped and rendered here.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\PostCards;

use function IsuDevLibrary\PostCards\shown_ids;
use function IsuDevLibrary\PostCards\wrapper_assignments;
use function IsuDevLibrary\Utils\Slider\render as render_slider;

defined( 'ABSPATH' ) || exit;

$cards = array();

foreach ( $block->inner_blocks as $inner ) {
	$cards[] = array(
		'source' => (string) ( $inner->attributes['source'] ?? 'post' ),
		'postId' => (int) ( $inner->attributes['postId'] ?? 0 ),
	);
}

if ( array() === $cards ) {
	return;
}

$current     = \is_singular() ? (int) \get_queried_object_id() : 0;
$also        = empty( $attributes['uniqueOnPage'] ) ? array() : shown_ids();
$assignments = wrapper_assignments( $attributes, $cards, $current, $also );
$is_slider   = 'slider' === ( $attributes['layout'] ?? '' );
$rendered    = array();

foreach ( $block->inner_blocks as $index => $inner ) {
	// `context` is what the card declares in `usesContext`; `available_context` is protected.
	$context = \array_merge(
		$inner->context,
		array(
			'isudev/slotPostId' => $assignments[ $index ] ?? 0,
			'isudev/inList'     => ! $is_slider,
		)
	);

	$html = ( new \WP_Block( $inner->parsed_block, $context ) )->render();

	if ( '' !== \trim( $html ) ) {
		$rendered[] = $html;
	}
}

if ( array() === $rendered ) {
	return;
}

$columns = array(
	''        => (int) ( $attributes['columns'] ?? 3 ),
	'-tablet' => (int) ( $attributes['columnsTablet'] ?? 0 ),
	'-mobile' => (int) ( $attributes['columnsMobile'] ?? 0 ),
);
$style   = '';
$last    = 3;

foreach ( $columns as $suffix => $count ) {
	$last   = $count > 0 ? $count : $last;
	$style .= \sprintf( '--isudev-cards-columns%1$s:%2$d;', $suffix, $last );
}

$label = isset( $attributes['label'] ) && \is_string( $attributes['label'] ) ? \trim( $attributes['label'] ) : '';

$wrapper_attributes = \get_block_wrapper_attributes(
	array(
		'class' => 'isudev-post-cards is-layout-' . ( $is_slider ? 'slider' : 'grid' ),
		'style' => $style,
	)
);

if ( $is_slider ) {
	$inner_html = render_slider(
		$rendered,
		array(
			'label'           => $label,
			'class'           => 'isudev-post-cards__slider',
			'container_class' => 'isudev-post-cards__track',
			'options'         => array( 'loop' => \count( $rendered ) > 1 ),
		)
	);
} else {
	$inner_html = \sprintf(
		'<div class="isudev-post-cards__grid" role="list"%1$s>%2$s</div>',
		'' !== $label ? ' aria-label="' . \esc_attr( $label ) . '"' : '',
		\implode( '', $rendered )
	);
}

\printf(
	'<div %1$s>%2$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes.
	$inner_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered cards; render_slider() escapes what it adds.
);
