<?php
/**
 * Render helpers for the Google Reviews list/carousel block.
 *
 * Pure functions only — no wp_kses()/esc_*()/wp_date(). render.php gathers
 * that context and passes it in.
 *
 * @package IsuDevLibrary\Blocks\GoogleReviews
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\GoogleReviews;

use function IsuDevLibrary\GoogleReviews\clamp_rating;
use function IsuDevLibrary\GoogleReviews\star_string;

use const IsuDevLibrary\URL;

defined( 'ABSPATH' ) || exit;

/**
 * Get a multibyte-safe text length when mbstring is available. Pure.
 *
 * @param string $text Text to measure.
 * @return int
 */
function text_length( string $text ): int {
	return \function_exists( 'mb_strlen' ) ? \mb_strlen( $text ) : \strlen( $text );
}

/**
 * Slice text without breaking multibyte characters when mbstring is available. Pure.
 *
 * @param string   $text   Text to slice.
 * @param int      $offset Start offset.
 * @param int|null $length Slice length, or null for "to the end".
 * @return string
 */
function text_slice( string $text, int $offset, ?int $length = null ): string {
	if ( \function_exists( 'mb_substr' ) ) {
		return null === $length ? \mb_substr( $text, $offset ) : \mb_substr( $text, $offset, $length );
	}

	return null === $length ? \substr( $text, $offset ) : \substr( $text, $offset, $length );
}

/**
 * Split review text into a visible excerpt and a hidden remainder. Pure.
 *
 * Prefers to break on a word boundary at or before the limit, falling back to
 * a hard cut when the text has no boundary that close.
 *
 * @param string $text       Full review text.
 * @param int    $text_limit Visible length before truncation, clamped to [50, 2000].
 * @return array{excerpt:string,remainder:string,truncated:bool}
 */
function split_review_text( string $text, int $text_limit ): array {
	$text_limit = \max( 50, \min( 2000, $text_limit ) );

	if ( text_length( $text ) <= $text_limit ) {
		return array(
			'excerpt'   => $text,
			'remainder' => '',
			'truncated' => false,
		);
	}

	$excerpt = text_slice( $text, 0, $text_limit );

	if ( \preg_match( '/^(.{1,' . $text_limit . '})(?=\s|$)/us', $text, $matches ) && isset( $matches[1] ) ) {
		$excerpt = (string) $matches[1];
	}

	return array(
		'excerpt'   => $excerpt,
		'remainder' => text_slice( $text, text_length( $excerpt ) ),
		'truncated' => true,
	);
}

/**
 * Whether a record-level verified value indicates a verified author. Pure.
 *
 * A template-level setting is not treated as proof; only the record's own
 * value is checked.
 *
 * @param string $value Raw `verified_order` field value.
 * @return bool
 */
function is_verified( string $value ): bool {
	return \in_array( \strtolower( \trim( $value ) ), array( '1', 'true', 'yes', 'yes1', 'verified' ), true );
}

/**
 * Get up to two initials for an avatar fallback. Pure.
 *
 * @param string $name Reviewer name.
 * @return string One or two uppercase letters, or '?' when the name is empty.
 */
function initials( string $name ): string {
	$name = \trim( $name );

	if ( '' === $name ) {
		return '?';
	}

	$parts = \preg_split( '/\s+/u', $name );

	if ( ! \is_array( $parts ) || array() === $parts ) {
		return '?';
	}

	$selected = 1 === \count( $parts ) ? array( $parts[0] ) : array( $parts[0], $parts[ \count( $parts ) - 1 ] );

	$initials = \implode(
		'',
		\array_map(
			static function ( string $part ): string {
				return text_slice( $part, 0, 1 );
			},
			$selected
		)
	);

	return \function_exists( 'mb_strtoupper' ) ? \mb_strtoupper( $initials ) : \strtoupper( $initials );
}

/**
 * Build the review item's class list. Pure, and deliberately unsanitized.
 *
 * The caller passes each name through sanitize_html_class(), which is a
 * WordPress function and would break this file's purity.
 *
 * @param bool $is_slider Whether the carousel is enabled.
 * @return array
 */
function item_classes( bool $is_slider ): array {
	$classes = array( 'isudev-google-reviews__item' );

	if ( $is_slider ) {
		$classes[] = 'swiper-slide';
	}

	return $classes;
}

/**
 * Build the reviews list's class list. Pure, and deliberately unsanitized.
 *
 * @param bool $is_slider Whether the carousel is enabled.
 * @return array
 */
function list_classes( bool $is_slider ): array {
	$classes = array( 'isudev-google-reviews__list' );

	if ( $is_slider ) {
		$classes[] = 'swiper-wrapper';
	}

	return $classes;
}

/**
 * Build the viewport's class list. Pure, and deliberately unsanitized.
 *
 * @param bool   $is_slider   Whether the carousel is enabled.
 * @param string $slider_mode 'continuous' or 'classic'; ignored unless `$is_slider`.
 * @return array
 */
function viewport_classes( bool $is_slider, string $slider_mode ): array {
	$classes = array( 'isudev-google-reviews__viewport' );

	if ( $is_slider ) {
		$classes[] = 'is-slider';
		$classes[] = 'is-mode-' . $slider_mode;
		$classes[] = 'swiper';
	}

	return $classes;
}

/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Render one review as a list item.
 *
 * @param array<string,mixed> $review     Raw review row from the database.
 * @param bool                $is_slider  Whether the carousel is enabled.
 * @param int                 $text_limit Visible review length before truncation.
 * @return string
 */
function render_review( array $review, bool $is_slider, int $text_limit ): string {
	$name        = \sanitize_text_field( (string) ( $review['reviewer_name'] ?? '' ) );
	$text        = \trim( (string) ( $review['review_text'] ?? '' ) );
	$rating      = clamp_rating( (float) ( $review['rating'] ?? 0 ) );
	$timestamp   = \absint( $review['created_time_stamp'] ?? 0 );
	$avatar_url  = '' !== (string) ( $review['userpic_small'] ?? '' ) ? (string) $review['userpic_small'] : (string) ( $review['userpic'] ?? '' );
	$review_url  = '' !== (string) ( $review['from_url_review'] ?? '' ) ? (string) $review['from_url_review'] : (string) ( $review['from_url'] ?? '' );
	$is_verified = is_verified( (string) ( $review['verified_order'] ?? '' ) );

	$avatar = \sprintf(
		'<span class="isudev-google-reviews__avatar is-fallback" aria-hidden="true">%s</span>',
		\esc_html( initials( $name ) )
	);

	if ( '' !== $avatar_url ) {
		$avatar = \sprintf(
			'<img class="isudev-google-reviews__avatar" src="%s" alt="" width="64" height="64" loading="lazy" decoding="async">',
			\esc_url( $avatar_url )
		);
	}

	$date = '';
	if ( $timestamp > 0 ) {
		$date = \sprintf(
			'<time class="isudev-google-reviews__date" datetime="%1$s" title="%2$s">%3$s</time>',
			\esc_attr( \gmdate( 'Y-m-d', $timestamp ) ),
			\esc_attr( \wp_date( (string) \get_option( 'date_format' ), $timestamp ) ),
			\esc_html(
				\sprintf(
					/* translators: %s: human-readable time difference, for example "2 months". */
					\__( '%s ago', 'isudev-library' ),
					\human_time_diff( $timestamp, \time() )
				)
			)
		);
	}

	$rating_markup = '';
	if ( $rating > 0 ) {
		$rating_decimals = 0.0 === \fmod( $rating, 1.0 ) ? 0 : 1;
		$rating_markup   = \sprintf(
			'<span class="isudev-google-reviews__rating" aria-label="%1$s"><span aria-hidden="true">%2$s</span></span>',
			\esc_attr(
				\sprintf(
					/* translators: %s: review rating from 0 to 5. */
					\__( 'Rating: %s out of 5', 'isudev-library' ),
					\number_format_i18n( $rating, $rating_decimals )
				)
			),
			\esc_html( star_string( $rating ) )
		);
	}

	$source_link = '';
	if ( '' !== $review_url ) {
		$source_link = \sprintf(
			'<a class="isudev-google-reviews__source" href="%1$s" target="_blank" rel="nofollow noopener noreferrer" aria-label="%2$s"><img src="%3$s" alt="" width="32" height="32"></a>',
			\esc_url( $review_url ),
			\esc_attr__( 'View the original review on Google', 'isudev-library' ),
			\esc_url( URL . 'assets/google-logo.svg' )
		);
	}

	$verified_markup = '';
	if ( $is_verified ) {
		$verified_markup = \sprintf(
			'<img class="isudev-google-reviews__verified" src="%1$s" alt="%2$s" width="18" height="18">',
			\esc_url( URL . 'assets/verified-rounded.svg' ),
			\esc_attr__( 'Verified author', 'isudev-library' )
		);
	}

	$text_markup = render_review_text( $text, $text_limit );

	$item_class = \implode( ' ', \array_map( '\sanitize_html_class', item_classes( $is_slider ) ) );

	return \sprintf(
		'<li class="%1$s"><article class="isudev-google-reviews__review"><div class="isudev-google-reviews__top">%2$s%3$s</div><div class="isudev-google-reviews__content"><blockquote class="isudev-google-reviews__quote">%4$s</blockquote>%5$s</div><footer class="isudev-google-reviews__footer">%6$s<div class="isudev-google-reviews__person"><span class="isudev-google-reviews__author-row"><span class="isudev-google-reviews__author">%7$s</span>%8$s</span>%9$s</div></footer></article></li>',
		\esc_attr( $item_class ),
		$rating_markup,
		$source_link,
		$text_markup['quote'],
		$text_markup['control'],
		$avatar,
		\esc_html( $name ),
		$verified_markup,
		$date
	);
}

/**
 * Render review text with an accessible expandable remainder.
 *
 * @param string $text       Full review text.
 * @param int    $text_limit Visible length before truncation.
 * @return array{quote:string,control:string}
 */
function render_review_text( string $text, int $text_limit ): array {
	$split = split_review_text( $text, $text_limit );

	if ( ! $split['truncated'] ) {
		return array(
			'quote'   => '<p>' . \nl2br( \esc_html( $split['excerpt'] ) ) . '</p>',
			'control' => '',
		);
	}

	$remainder_id   = \wp_unique_id( 'isudev-google-review-rest-' );
	$expand_label   = \__( 'Read more', 'isudev-library' );
	$collapse_label = \__( 'Collapse', 'isudev-library' );

	return array(
		'quote'   => \sprintf(
			'<p>%1$s<span class="isudev-google-reviews__ellipsis" aria-hidden="true">…</span><span id="%2$s" class="isudev-google-reviews__remainder" hidden>%3$s</span></p>',
			\nl2br( \esc_html( $split['excerpt'] ) ),
			\esc_attr( $remainder_id ),
			\nl2br( \esc_html( $split['remainder'] ) )
		),
		'control' => \sprintf(
			'<button class="isudev-google-reviews__read-more" type="button" aria-expanded="false" aria-controls="%1$s" data-google-review-toggle data-label-expand="%2$s" data-label-collapse="%3$s">%4$s</button>',
			\esc_attr( $remainder_id ),
			\esc_attr( $expand_label ),
			\esc_attr( $collapse_label ),
			\esc_html( $expand_label )
		),
	);
}
