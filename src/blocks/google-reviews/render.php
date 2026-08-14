<?php
/**
 * Server render for isudev/google-reviews.
 *
 * @package IsuDevLibrary\Blocks\GoogleReviews
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused; this block has no InnerBlocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\GoogleReviews;

use function IsuDevLibrary\GoogleReviews\find_visible;

defined( 'ABSPATH' ) || exit;

// The data source: this block has nothing to show without the plugin that owns it.
if ( ! \defined( 'WPREV_GOOGLE_PLUGIN_DIR' ) ) {
	return;
}

global $wpdb;

$limit       = \max( 1, \min( 100, isset( $attributes['reviewCount'] ) ? \absint( $attributes['reviewCount'] ) : 20 ) );
$text_limit  = \max( 50, \min( 2000, isset( $attributes['reviewTextLength'] ) ? \absint( $attributes['reviewTextLength'] ) : 220 ) );
$account_id  = isset( $attributes['accountId'] ) ? \sanitize_text_field( (string) $attributes['accountId'] ) : '';
$is_slider   = ! empty( $attributes['enableSlider'] );
$slider_mode = isset( $attributes['sliderMode'] ) && \in_array( $attributes['sliderMode'], array( 'continuous', 'classic' ), true )
	? (string) $attributes['sliderMode']
	: 'continuous';

$reviews = find_visible( $wpdb, $limit, $account_id );

if ( array() === $reviews ) {
	return;
}

$items = '';
foreach ( $reviews as $review ) {
	$items .= render_review( $review, $is_slider, $text_limit );
}

$controls = '';
if ( $is_slider ) {
	$controls = \sprintf(
		'<button class="swiper-button-prev" type="button" aria-label="%1$s"></button><button class="swiper-button-next" type="button" aria-label="%2$s"></button><div class="swiper-pagination"></div>',
		\esc_attr__( 'Previous review', 'isudev-library' ),
		\esc_attr__( 'Next review', 'isudev-library' )
	);
}

$viewport_attributes = \implode( ' ', \array_map( '\sanitize_html_class', viewport_classes( $is_slider, $slider_mode ) ) );
$viewport_data       = $is_slider
	? \sprintf( ' data-google-reviews-slider="true" data-slider-mode="%s"', \esc_attr( $slider_mode ) )
	: '';

$list_class = \implode( ' ', \array_map( '\sanitize_html_class', list_classes( $is_slider ) ) );

$wrapper_attributes = \get_block_wrapper_attributes( array( 'class' => 'isudev-google-reviews' ) );

$markup = \sprintf(
	'<section %1$s aria-label="%2$s"><div class="%3$s"%4$s><ul class="%5$s">%6$s</ul>%7$s</div></section>',
	$wrapper_attributes,
	\esc_attr__( 'Customer reviews', 'isudev-library' ),
	\esc_attr( $viewport_attributes ),
	$viewport_data,
	\esc_attr( $list_class ),
	$items,
	$controls
);

/*
 * $wrapper_attributes escapes its own output; every other piece is escaped
 * at its source inside render_review() and the sprintf() arguments above.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
