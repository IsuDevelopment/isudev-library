<?php
/**
 * Checks for the pure parts of the Google Reviews blocks.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/google-reviews/render-helpers.php';
require_once dirname( __DIR__, 2 ) . '/src/blocks/google-reviews/inc/render-helpers.php';
require_once dirname( __DIR__, 2 ) . '/src/blocks/google-reviews-header/inc/render-helpers.php';

use function IsuDevLibrary\Blocks\GoogleReviews\initials;
use function IsuDevLibrary\Blocks\GoogleReviews\is_verified;
use function IsuDevLibrary\Blocks\GoogleReviews\item_classes;
use function IsuDevLibrary\Blocks\GoogleReviews\split_review_text;
use function IsuDevLibrary\Blocks\GoogleReviewsHeader\title_tag;
use function IsuDevLibrary\GoogleReviews\clamp_rating;
use function IsuDevLibrary\GoogleReviews\maps_search_query_args;
use function IsuDevLibrary\GoogleReviews\star_string;
use function IsuDevLibrary\GoogleReviews\write_review_query_args;

/*
 * clamp_rating(): stays within [0, 5], including out-of-range input from a
 * malformed database value.
 */
Checks::is( 'clamp_rating: an in-range value is unchanged', clamp_rating( 3.5 ), 3.5 );
Checks::is( 'clamp_rating: clamps above 5', clamp_rating( 7.2 ), 5.0 );
Checks::is( 'clamp_rating: clamps below 0', clamp_rating( -1.0 ), 0.0 );

/*
 * star_string(): five characters total, split at the rounded rating.
 */
Checks::is( 'star_string: a whole rating', star_string( 4.0 ), '★★★★☆' );
Checks::is( 'star_string: rounds up at the midpoint', star_string( 4.5 ), '★★★★★' );
Checks::is( 'star_string: zero is all empty stars', star_string( 0.0 ), '☆☆☆☆☆' );
Checks::is( 'star_string: five is all filled stars', star_string( 5.0 ), '★★★★★' );

/*
 * maps_search_query_args() / write_review_query_args(): pure data, no
 * escaping — the caller passes these to add_query_arg().
 */
Checks::is(
	'maps_search_query_args: builds the expected keys',
	maps_search_query_args( 'Acme Ltd', 'abc123' ),
	array(
		'api'            => '1',
		'query'          => 'Acme Ltd',
		'query_place_id' => 'abc123',
	)
);
Checks::is( 'write_review_query_args: builds the expected key', write_review_query_args( 'abc123' ), array( 'placeid' => 'abc123' ) );

/*
 * split_review_text(): short text is not truncated; long text breaks on a
 * word boundary and reports truncation.
 */
$short = split_review_text( 'Great service.', 220 );
Checks::is( 'split_review_text: short text is not truncated', $short['truncated'], false );
Checks::is( 'split_review_text: short text: excerpt is the whole text', $short['excerpt'], 'Great service.' );
Checks::is( 'split_review_text: short text: no remainder', $short['remainder'], '' );

$long_text = \str_repeat( 'word ', 100 );
$long      = split_review_text( $long_text, 50 );
Checks::true( 'split_review_text: long text is truncated', $long['truncated'] );
Checks::true( 'split_review_text: excerpt plus remainder reconstruct the source', $long['excerpt'] . $long['remainder'] === $long_text );
Checks::is( 'split_review_text: excerpt breaks exactly at a space in the source', \substr( $long_text, \strlen( $long['excerpt'] ), 1 ), ' ' );

/*
 * split_review_text(): the limit is clamped to [50, 2000] before it is applied.
 */
$clamped_low = split_review_text( \str_repeat( 'a', 30 ), 10 );
Checks::is( 'split_review_text: a limit below 50 is clamped up, so 30 chars is not truncated', $clamped_low['truncated'], false );

/*
 * is_verified(): only the recognized tokens count, case-insensitively; a
 * template-level setting is never treated as proof.
 */
Checks::true( 'is_verified: "1" is verified', is_verified( '1' ) );
Checks::true( 'is_verified: "YES" is verified regardless of case', is_verified( 'YES' ) );
Checks::is( 'is_verified: an empty value is not verified', is_verified( '' ), false );
Checks::is( 'is_verified: an unrecognized value is not verified', is_verified( 'maybe' ), false );

/*
 * initials(): one name yields one letter, two names yield the first and
 * last initials, and an empty name yields the placeholder.
 */
Checks::is( 'initials: a single name', initials( 'Madonna' ), 'M' );
Checks::is( 'initials: first and last name', initials( 'Jane Doe' ), 'JD' );
Checks::is( 'initials: a middle name is ignored', initials( 'Jane Q Doe' ), 'JD' );
Checks::is( 'initials: an empty name falls back to a placeholder', initials( '' ), '?' );
Checks::is( 'initials: a whitespace-only name falls back to a placeholder', initials( '   ' ), '?' );

/*
 * item_classes(): the base class is always present; the Swiper slide class
 * appears only when the carousel is enabled.
 */
Checks::is( 'item_classes: no carousel', item_classes( false ), array( 'isudev-google-reviews__item' ) );
Checks::is( 'item_classes: with carousel', item_classes( true ), array( 'isudev-google-reviews__item', 'swiper-slide' ) );

/*
 * title_tag() (header block): only a heading with a valid level resolves to
 * h2..h6; anything else falls back sensibly.
 */
Checks::is( 'title_tag: not a heading is a div even with a valid level', title_tag( false, 3 ), 'div' );
Checks::is( 'title_tag: a heading at level 4', title_tag( true, 4 ), 'h4' );
Checks::is( 'title_tag: a heading below the valid range falls back to h2', title_tag( true, 1 ), 'h2' );
Checks::is( 'title_tag: a heading above the valid range falls back to h2', title_tag( true, 7 ), 'h2' );
