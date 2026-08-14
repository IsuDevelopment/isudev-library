<?php
/**
 * Server render for isudev/google-reviews-header.
 *
 * @package IsuDevLibrary\Blocks\GoogleReviewsHeader
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused; this block has no InnerBlocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\GoogleReviewsHeader;

use function IsuDevLibrary\GoogleReviews\clamp_rating;
use function IsuDevLibrary\GoogleReviews\find_account_summary;
use function IsuDevLibrary\GoogleReviews\maps_search_query_args;
use function IsuDevLibrary\GoogleReviews\star_string;
use function IsuDevLibrary\GoogleReviews\write_review_query_args;

defined( 'ABSPATH' ) || exit;

// The data source: this block has nothing to show without the plugin that owns it.
if ( ! \defined( 'WPREV_GOOGLE_PLUGIN_DIR' ) ) {
	return;
}

global $wpdb;

$account_id = isset( $attributes['accountId'] ) ? \sanitize_text_field( (string) $attributes['accountId'] ) : '';
$account    = find_account_summary( $wpdb, $account_id );

$header          = sanitize_inline_content( isset( $attributes['header'] ) && \is_string( $attributes['header'] ) ? $attributes['header'] : '' );
$description     = sanitize_inline_content( isset( $attributes['description'] ) && \is_string( $attributes['description'] ) ? $attributes['description'] : '' );
$button_text     = isset( $attributes['buttonText'] ) && \is_string( $attributes['buttonText'] ) && '' !== $attributes['buttonText']
	? \sanitize_text_field( $attributes['buttonText'] )
	: \__( 'Rate us', 'isudev-library' );
$logo            = isset( $attributes['logo'] ) && \is_array( $attributes['logo'] ) ? $attributes['logo'] : array();
$logo_id         = isset( $logo['id'] ) && \is_numeric( $logo['id'] ) ? (int) $logo['id'] : 0;
$logo_url        = isset( $logo['url'] ) && \is_string( $logo['url'] ) ? $logo['url'] : '';
$logo_alt        = isset( $logo['alt'] ) && \is_string( $logo['alt'] ) ? $logo['alt'] : '';
$logo_max_height = \max( 1, isset( $attributes['logoMaxHeight'] ) ? \absint( $attributes['logoMaxHeight'] ) : 64 );
$is_heading      = ! isset( $attributes['renderAsHeading'] ) || true === $attributes['renderAsHeading'];
$heading_level   = \max( 2, \min( 6, isset( $attributes['headingLevel'] ) ? \absint( $attributes['headingLevel'] ) : 2 ) );

if ( '' === \trim( \wp_strip_all_tags( $header ) ) && \is_array( $account ) ) {
	$header = \esc_html( $account['name'] );
}

$place_url = '';
if ( \is_array( $account ) ) {
	$place_url = \add_query_arg(
		maps_search_query_args( $account['name'], $account['id'] ),
		'https://www.google.com/maps/search/'
	);
}

$logo_html = '';
if ( $logo_id > 0 ) {
	$logo_html = (string) \wp_get_attachment_image(
		$logo_id,
		'medium',
		false,
		array(
			'class'    => 'isudev-google-reviews-header__logo-image',
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);
} elseif ( '' !== $logo_url ) {
	$logo_html = \sprintf(
		'<img class="isudev-google-reviews-header__logo-image" src="%1$s" alt="%2$s" loading="lazy" decoding="async">',
		\esc_url( $logo_url ),
		\esc_attr( $logo_alt )
	);
}

$logo_markup = '';
if ( '' !== $logo_html ) {
	if ( '' !== $place_url ) {
		$logo_markup = \sprintf(
			'<a class="isudev-google-reviews-header__logo" href="%1$s" target="_blank" rel="nofollow noopener noreferrer" aria-label="%2$s">%3$s</a>',
			\esc_url( $place_url ),
			\esc_attr(
				\sprintf(
					/* translators: %s: Google account name. */
					\__( 'Open %s in Google Maps', 'isudev-library' ),
					$account['name']
				)
			),
			$logo_html
		);
	} else {
		$logo_markup = '<div class="isudev-google-reviews-header__logo">' . $logo_html . '</div>';
	}
}

$title_markup = '';
if ( '' !== \trim( \wp_strip_all_tags( $header ) ) ) {
	$title_tag    = title_tag( $is_heading, $heading_level );
	$title_markup = \sprintf( '<%1$s class="isudev-google-reviews-header__title">%2$s</%1$s>', $title_tag, $header );
}

$description_markup = '';
if ( '' !== \trim( \wp_strip_all_tags( $description ) ) ) {
	$description_markup = '<p class="isudev-google-reviews-header__description">' . $description . '</p>';
}

$rating_markup = '';
$button_markup = '';
if ( \is_array( $account ) ) {
	$average_rating = clamp_rating( (float) $account['averageRating'] );
	$rating_count   = \absint( $account['totalReviewCount'] );
	$rating_markup  = \sprintf(
		'<div class="isudev-google-reviews-header__rating"><strong>%1$s</strong><span class="isudev-google-reviews-header__stars" aria-label="%2$s">%3$s</span><span class="isudev-google-reviews-header__count">%4$s</span></div>',
		\esc_html( \number_format_i18n( $average_rating, 1 ) ),
		\esc_attr(
			\sprintf(
				/* translators: %s: average Google rating from 0 to 5. */
				\__( 'Average rating: %s out of 5', 'isudev-library' ),
				\number_format_i18n( $average_rating, 1 )
			)
		),
		\esc_html( star_string( $average_rating ) ),
		\esc_html(
			\sprintf(
				/* translators: %s: total number of Google ratings. */
				\__( 'Based on %s reviews', 'isudev-library' ),
				\number_format_i18n( $rating_count )
			)
		)
	);

	if ( '' !== $button_text ) {
		$review_url    = \add_query_arg( write_review_query_args( $account['id'] ), 'https://search.google.com/local/writereview' );
		$button_markup = \sprintf(
			'<a class="isudev-google-reviews-header__button" href="%1$s" target="_blank" rel="nofollow noopener noreferrer">%2$s<span aria-hidden="true">→</span></a>',
			\esc_url( $review_url ),
			\esc_html( $button_text )
		);
	}
}

$powered_markup = '';
if ( empty( $attributes['hidePoweredByGoogle'] ) ) {
	$powered_markup = \sprintf(
		'<span class="isudev-google-reviews-header__powered"><span>%1$s</span><img src="%2$s" alt="" width="16" height="16"><strong>Google</strong></span>',
		\esc_html__( 'powered by', 'isudev-library' ),
		\esc_url( \IsuDevLibrary\URL . 'assets/google-logo.svg' )
	);
}

$markup = \sprintf(
	'<section %1$s>%2$s<div class="isudev-google-reviews-header__main">%3$s%4$s%5$s</div><div class="isudev-google-reviews-header__actions">%6$s%7$s</div></section>',
	\get_block_wrapper_attributes(
		array(
			'class' => 'isudev-google-reviews-header',
			'style' => \sprintf( '--isudev-google-reviews-header-logo-max-height: %dpx;', $logo_max_height ),
		)
	),
	$logo_markup,
	$title_markup,
	$description_markup,
	$rating_markup,
	$powered_markup,
	$button_markup
);

/*
 * $wrapper_attributes (built inline above) escapes its own output; $header
 * and $description already went through sanitize_inline_content()
 * (wp_kses()); every other dynamic value is escaped at its own point above.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
