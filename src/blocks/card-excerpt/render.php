<?php
/**
 * Server render for isudev/card-excerpt.
 *
 * @package IsuDevLibrary\Blocks\CardExcerpt
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\CardExcerpt;

defined( 'ABSPATH' ) || exit;

$card_post_id = (int) ( $block->context['postId'] ?? 0 );
$options      = (array) ( $block->context['isudev/cardOptions'] ?? array() );

if ( $card_post_id < 1 || ! empty( $options['hideExcerpt'] ) ) {
	return;
}

$words = \max( 1, (int) ( $attributes['words'] ?? 25 ) );

if ( isset( $options['customExcerpt'] ) && \is_string( $options['customExcerpt'] ) && '' !== $options['customExcerpt'] ) {
	$excerpt = $options['customExcerpt'];
} else {
	$card_post = \get_post( $card_post_id );
	$excerpt   = $card_post instanceof \WP_Post && '' !== \trim( $card_post->post_excerpt )
		? $card_post->post_excerpt
		: \wp_trim_words( \excerpt_remove_blocks( \strip_shortcodes( (string) ( $card_post->post_content ?? '' ) ) ), $words );
}

/**
 * Filter a card excerpt before output.
 *
 * @param string   $excerpt    Plain-text excerpt.
 * @param int      $card_post_id    Card post ID.
 * @param array    $options    Resolved card options.
 * @param array    $attributes Block attributes.
 */
$excerpt = (string) \apply_filters( 'isudev_library/post_card/excerpt', \wp_strip_all_tags( (string) $excerpt ), $card_post_id, $options, $attributes );

if ( '' === \trim( $excerpt ) ) {
	return;
}

\printf(
	'<p %1$s>%2$s</p>',
	\get_block_wrapper_attributes( array( 'class' => 'isudev-card-excerpt' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapes itself.
	\esc_html( $excerpt )
);
