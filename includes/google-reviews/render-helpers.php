<?php
/**
 * Pure render helpers shared by all three Google Reviews blocks.
 *
 * No WordPress calls — see the note in repository.php for why this lives in
 * `includes/` rather than one block's own `inc/`.
 *
 * @package IsuDevLibrary\GoogleReviews
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\GoogleReviews;

defined( 'ABSPATH' ) || exit;

/**
 * Clamp a rating to the valid 0–5 range. Pure.
 *
 * @param float $rating Candidate rating.
 * @return float
 */
function clamp_rating( float $rating ): float {
	return \min( 5.0, \max( 0.0, $rating ) );
}

/**
 * Render a star string for a rating: filled stars, then empty stars. Pure.
 *
 * @param float $rating Rating from 0 to 5.
 * @return string Five characters, each '★' or '☆'.
 */
function star_string( float $rating ): string {
	$filled = (int) \round( clamp_rating( $rating ) );

	return \str_repeat( '★', $filled ) . \str_repeat( '☆', 5 - $filled );
}

/**
 * Build the query args for a Google Maps place search URL. Pure.
 *
 * The caller passes the result to `add_query_arg()`, which is a WordPress
 * function and would break this file's purity.
 *
 * @param string $account_name Google account (business) name.
 * @param string $place_id     Google `pageid` / place id.
 * @return array<string,string>
 */
function maps_search_query_args( string $account_name, string $place_id ): array {
	return array(
		'api'            => '1',
		'query'          => $account_name,
		'query_place_id' => $place_id,
	);
}

/**
 * Build the query args for a "write a Google review" URL. Pure.
 *
 * @param string $place_id Google `pageid` / place id.
 * @return array<string,string>
 */
function write_review_query_args( string $place_id ): array {
	return array( 'placeid' => $place_id );
}
