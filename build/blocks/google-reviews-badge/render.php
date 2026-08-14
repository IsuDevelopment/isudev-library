<?php
/**
 * Server render for isudev/google-reviews-badge.
 *
 * @package IsuDevLibrary\Blocks\GoogleReviewsBadge
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused; this block has no InnerBlocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\GoogleReviewsBadge;

use function IsuDevLibrary\GoogleReviews\clamp_rating;
use function IsuDevLibrary\GoogleReviews\find_account_summary;
use function IsuDevLibrary\GoogleReviews\maps_search_query_args;
use function IsuDevLibrary\GoogleReviews\star_string;

defined( 'ABSPATH' ) || exit;

// The data source: this block has nothing to show without the plugin that owns it.
if ( ! \defined( 'WPREV_GOOGLE_PLUGIN_DIR' ) ) {
	return;
}

global $wpdb;

$account_id = isset( $attributes['accountId'] ) ? \sanitize_text_field( (string) $attributes['accountId'] ) : '';
$account    = find_account_summary( $wpdb, $account_id );

if ( ! \is_array( $account ) ) {
	return;
}

$average_rating = clamp_rating( (float) $account['averageRating'] );
$rating_count   = \absint( $account['totalReviewCount'] );
$block_class    = 'isudev-google-reviews-badge';
$place_url      = \add_query_arg(
	maps_search_query_args( $account['name'], $account['id'] ),
	'https://www.google.com/maps/search/'
);

if ( ! empty( $attributes['useLightText'] ) ) {
	$block_class .= ' has-light-text';
}

$label = \sprintf(
	/* translators: 1: account name, 2: average rating, 3: total number of ratings. */
	\__( 'Open %1$s in Google Maps. Rating: %2$s out of 5 based on %3$s reviews', 'isudev-library' ),
	$account['name'],
	\number_format_i18n( $average_rating, 1 ),
	\number_format_i18n( $rating_count )
);

$markup = \sprintf(
	'<a %1$s aria-label="%2$s"><span class="isudev-google-reviews-badge__google-logo" aria-hidden="true"></span><span class="isudev-google-reviews-badge__content"><span class="isudev-google-reviews-badge__rating"><strong>%3$s</strong><span class="isudev-google-reviews-badge__stars" aria-hidden="true">%4$s</span></span><span class="isudev-google-reviews-badge__count">%5$s</span></span></a>',
	\get_block_wrapper_attributes(
		array(
			'class'  => $block_class,
			'href'   => \esc_url( $place_url ),
			'target' => '_blank',
			'rel'    => 'nofollow noopener noreferrer',
		)
	),
	\esc_attr( $label ),
	\esc_html( \number_format_i18n( $average_rating, 1 ) ),
	\esc_html( star_string( $average_rating ) ),
	\esc_html(
		\sprintf(
			/* translators: %s: total number of Google ratings. */
			\__( '%s reviews on Google', 'isudev-library' ),
			\number_format_i18n( $rating_count )
		)
	)
);

/*
 * $wrapper_attributes (built inline above) escapes its own output; every
 * other dynamic value is escaped at its own point above.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
