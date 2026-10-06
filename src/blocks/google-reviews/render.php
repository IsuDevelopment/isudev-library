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
use function IsuDevLibrary\Utils\Slider\render as render_slider;

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

$cards = array();
foreach ( $reviews as $review ) {
	$cards[] = render_review( $review, $text_limit );
}

$label           = \__( 'Customer reviews', 'isudev-library' );
$viewport_class  = \implode( ' ', \array_map( '\sanitize_html_class', viewport_classes( $is_slider, $slider_mode ) ) );
$is_continuous   = 'continuous' === $slider_mode;
$has_many_slides = \count( $cards ) > 1;

if ( $is_slider ) {
	// The slider root is the labelled carousel region, so the section stays unnamed (not a second landmark).
	$wrapper_attributes = \get_block_wrapper_attributes( array( 'class' => 'isudev-google-reviews' ) );
	$inner              = render_slider(
		$cards,
		array(
			'label'           => $label,
			'class'           => $viewport_class,
			'container_class' => 'isudev-google-reviews__list',
			'slide_class'     => 'isudev-google-reviews__item',
			'options'         => array(
				'loop'       => $has_many_slides,
				'align'      => $is_continuous ? 'center' : 'start',
				'autoScroll' => $is_continuous ? 0.5 : false,
			),
			'labels'          => array(
				'prev'  => \__( 'Previous review', 'isudev-library' ),
				'next'  => \__( 'Next review', 'isudev-library' ),
				'pause' => \__( 'Pause reviews', 'isudev-library' ),
				'play'  => \__( 'Play reviews', 'isudev-library' ),
			),
		)
	);
} else {
	$wrapper_attributes = \get_block_wrapper_attributes(
		array(
			'class'      => 'isudev-google-reviews',
			'aria-label' => $label,
		)
	);
	$inner              = \sprintf(
		'<div class="%1$s"><ul class="isudev-google-reviews__list"><li class="isudev-google-reviews__item">%2$s</li></ul></div>',
		\esc_attr( $viewport_class ),
		\implode( '</li><li class="isudev-google-reviews__item">', $cards )
	);
}

/*
 * $wrapper_attributes escapes its own output, render_slider() escapes what it
 * adds, and render_review() escapes every card at its source.
 */
echo '<section ' . $wrapper_attributes . '>' . $inner . '</section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
