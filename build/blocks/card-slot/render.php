<?php
/**
 * Server render for isudev/card-slot.
 *
 * @package IsuDevLibrary\Blocks\CardSlot
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\CardSlot;

defined( 'ABSPATH' ) || exit;

$name         = isset( $attributes['name'] ) && \is_string( $attributes['name'] ) ? \sanitize_key( $attributes['name'] ) : '';
$card_post_id = (int) ( $block->context['postId'] ?? 0 );
$card_post    = $card_post_id > 0 ? \get_post( $card_post_id ) : null;

if ( '' === $name || ! $card_post instanceof \WP_Post ) {
	return;
}

$options = (array) ( $block->context['isudev/cardOptions'] ?? array() );

/**
 * Fill a card slot. Return HTML; return '' to render nothing.
 *
 * The output is passed through wp_kses_post().
 *
 * @param string   $html       Default ''.
 * @param \WP_Post $card_post  Card post.
 * @param array    $options    Resolved card options.
 * @param array    $attributes Slot block attributes.
 */
$html = \wp_kses_post( (string) \apply_filters( "isudev_library/post_card/slot/{$name}", '', $card_post, $options, $attributes ) );

if ( '' === \trim( $html ) ) {
	return;
}

$slot_tag = isset( $attributes['tagName'] ) && \in_array( $attributes['tagName'], array( 'div', 'p', 'span' ), true ) ? $attributes['tagName'] : 'div';

\printf(
	'<%1$s %2$s>%3$s</%1$s>',
	\tag_escape( $slot_tag ),
	\get_block_wrapper_attributes( array( 'class' => 'isudev-card-slot isudev-card-slot--' . $name ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapes itself.
	$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post() above.
);
