<?php
/**
 * Server render for isudev/card-title.
 *
 * @package IsuDevLibrary\Blocks\CardTitle
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\CardTitle;

defined( 'ABSPATH' ) || exit;

$card_post_id = (int) ( $block->context['postId'] ?? 0 );

if ( $card_post_id < 1 ) {
	return;
}

$options    = (array) ( $block->context['isudev/cardOptions'] ?? array() );
$card_title = isset( $options['customTitle'] ) && \is_string( $options['customTitle'] ) && '' !== $options['customTitle']
	? \esc_html( $options['customTitle'] )
	: \wp_kses_post( \get_the_title( $card_post_id ) );

if ( '' === \trim( \wp_strip_all_tags( $card_title ) ) ) {
	return;
}

$level     = (int) ( $attributes['level'] ?? 3 );
$title_tag = $level >= 1 && $level <= 6 ? 'h' . $level : 'p';

if ( ! isset( $attributes['isLink'] ) || $attributes['isLink'] ) {
	$card_title = \sprintf(
		'<a class="%1$s" href="%2$s">%3$s</a>',
		! isset( $attributes['stretchLink'] ) || $attributes['stretchLink'] ? 'isudev-card-title__link isudev-post-card__link' : 'isudev-card-title__link',
		\esc_url( (string) \get_permalink( $card_post_id ) ),
		$card_title
	);
}

\printf(
	'<%1$s %2$s>%3$s</%1$s>',
	\tag_escape( $title_tag ),
	\get_block_wrapper_attributes( array( 'class' => 'isudev-card-title' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapes itself.
	$card_title // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
);
