<?php
/**
 * Server render for isudev/card-image.
 *
 * @package IsuDevLibrary\Blocks\CardImage
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\CardImage;

defined( 'ABSPATH' ) || exit;

$card_post_id = (int) ( $block->context['postId'] ?? 0 );

if ( $card_post_id < 1 ) {
	return;
}

$options  = (array) ( $block->context['isudev/cardOptions'] ?? array() );
$custom   = isset( $options['customImage'] ) && \is_array( $options['customImage'] ) ? $options['customImage'] : array();
$size     = isset( $attributes['sizeSlug'] ) && \is_string( $attributes['sizeSlug'] ) ? $attributes['sizeSlug'] : 'medium_large';
$image    = '';
$focal    = isset( $custom['focalPoint'] ) && \is_array( $custom['focalPoint'] ) ? $custom['focalPoint'] : null;
$img_attr = array( 'class' => 'isudev-card-image__img' );

if ( null !== $focal ) {
	$img_attr['style'] = \sprintf( 'object-position:%1$s%% %2$s%%;', \round( (float) $focal['x'] * 100, 2 ), \round( (float) $focal['y'] * 100, 2 ) );
}

if ( ! empty( $custom['id'] ) ) {
	if ( isset( $custom['alt'] ) && '' !== $custom['alt'] ) {
		$img_attr['alt'] = (string) $custom['alt'];
	}
	$image = \wp_get_attachment_image( (int) $custom['id'], $size, false, $img_attr );
} elseif ( ! empty( $custom['url'] ) ) {
	$image = \sprintf(
		'<img class="isudev-card-image__img" src="%1$s" alt="%2$s" loading="lazy" decoding="async"%3$s />',
		\esc_url( (string) $custom['url'] ),
		\esc_attr( (string) ( $custom['alt'] ?? '' ) ),
		isset( $img_attr['style'] ) ? ' style="' . \esc_attr( $img_attr['style'] ) . '"' : ''
	);
} elseif ( \has_post_thumbnail( $card_post_id ) ) {
	// The title carries the link and the name; an empty alt keeps the image from repeating it.
	$img_attr['alt'] = '';
	$image           = (string) \get_the_post_thumbnail( $card_post_id, $size, $img_attr );
}

if ( '' === $image ) {
	return;
}

$ratio = isset( $attributes['aspectRatio'] ) && \is_string( $attributes['aspectRatio'] ) && 1 === \preg_match( '/^\d+(\.\d+)?\s*\/\s*\d+(\.\d+)?$/', $attributes['aspectRatio'] )
	? 'aspect-ratio:' . $attributes['aspectRatio'] . ';'
	: '';

$wrapper_attributes = \get_block_wrapper_attributes(
	\array_filter(
		array(
			'class' => 'isudev-card-image',
			'style' => $ratio,
		)
	)
);

\printf(
	'<figure %1$s>%2$s</figure>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes.
	$image // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
);
