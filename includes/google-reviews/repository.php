<?php
/**
 * Read access to reviews stored by the WP Google Review Slider plugin.
 *
 * Shared by all three Google Reviews blocks. Lives in `includes/`, not in any
 * one block's own `inc/`, because IsuDevLibrary\Loader only allows a block's
 * `bootstrap` files to live inside that block's own directory — there is no
 * mechanism for one block to load a file from a sibling block's directory.
 * See guides/google-reviews.md.
 *
 * @package IsuDevLibrary\GoogleReviews
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\GoogleReviews;

defined( 'ABSPATH' ) || exit;

/**
 * Table suffix owned by the WP Google Review Slider plugin.
 *
 * @var string
 */
const REVIEWS_TABLE_SUFFIX = 'wpfb_reviews';

/**
 * Upper bound on how many reviews a single query may return.
 *
 * @var int
 */
const MAX_REVIEW_LIMIT = 100;

/**
 * Resolve the reviews table name for a database connection. Pure.
 *
 * @param \wpdb $db WordPress database connection.
 * @return string
 */
function table_name( \wpdb $db ): string {
	return $db->prefix . REVIEWS_TABLE_SUFFIX;
}

/**
 * Whether a table exists in the database.
 *
 * @param \wpdb  $db         WordPress database connection.
 * @param string $table_name Table name to look up.
 * @return bool
 */
function table_exists( \wpdb $db, string $table_name ): bool {
	$query = $db->prepare( 'SHOW TABLES LIKE %s', $table_name );

	if ( ! \is_string( $query ) ) {
		return false;
	}

	return $table_name === $db->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Get visible Google reviews that have review text.
 *
 * @param \wpdb  $db         WordPress database connection.
 * @param int    $limit      Maximum number of reviews, clamped to [1, MAX_REVIEW_LIMIT].
 * @param string $account_id Restrict to one Google account (its `pageid`); '' for all accounts.
 * @return array<int, array<string, mixed>>
 */
function find_visible( \wpdb $db, int $limit = 20, string $account_id = '' ): array {
	$table = table_name( $db );

	if ( ! table_exists( $db, $table ) ) {
		return array();
	}

	$limit      = \max( 1, \min( MAX_REVIEW_LIMIT, $limit ) );
	$where      = '';
	$query_args = array( 'Google', 'yes' );

	if ( '' !== $account_id ) {
		$where        = ' AND pageid = %s';
		$query_args[] = $account_id;
	}

	$query_args[] = $limit;

	$query = $db->prepare(
		"SELECT id, reviewer_name, rating, review_text, created_time_stamp, userpic_small, userpic, type, from_name, from_url_review, from_url, verified_order
		FROM {$table}
		WHERE type = %s
			AND hide != %s
			AND review_text != ''{$where}
		ORDER BY sort_weight DESC, created_time_stamp DESC
		LIMIT %d",
		...$query_args
	);

	if ( ! \is_string( $query ) ) {
		return array();
	}

	$reviews = $db->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return \is_array( $reviews ) ? $reviews : array();
}

/**
 * Get every Google account with review counts and its average rating.
 *
 * @param \wpdb $db WordPress database connection.
 * @return array<int, array{id: string, name: string, reviewCount: int, totalReviewCount: int, averageRating: float}>
 */
function find_accounts( \wpdb $db ): array {
	$table = table_name( $db );

	if ( ! table_exists( $db, $table ) ) {
		return array();
	}

	$query = $db->prepare(
		"SELECT pageid,
			MAX(pagename) AS pagename,
			SUM(CASE WHEN review_text != '' THEN 1 ELSE 0 END) AS review_count,
			COUNT(*) AS total_review_count,
			AVG(CAST(NULLIF(rating, '') AS DECIMAL(3, 2))) AS average_rating
		FROM {$table}
		WHERE type = %s
			AND hide != %s
			AND pageid != ''
		GROUP BY pageid
		ORDER BY pagename ASC",
		'Google',
		'yes'
	);

	if ( ! \is_string( $query ) ) {
		return array();
	}

	$accounts = $db->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( ! \is_array( $accounts ) ) {
		return array();
	}

	return \array_map(
		static function ( array $account ): array {
			return array(
				'id'               => \sanitize_text_field( (string) $account['pageid'] ),
				'name'             => \sanitize_text_field( (string) $account['pagename'] ),
				'reviewCount'      => \absint( $account['review_count'] ),
				'totalReviewCount' => \absint( $account['total_review_count'] ),
				'averageRating'    => (float) $account['average_rating'],
			);
		},
		$accounts
	);
}

/**
 * Get aggregate rating data for one Google account.
 *
 * @param \wpdb  $db         WordPress database connection.
 * @param string $account_id The account's `pageid`.
 * @return array{id: string, name: string, totalReviewCount: int, averageRating: float}|null
 */
function find_account_summary( \wpdb $db, string $account_id ): ?array {
	$table      = table_name( $db );
	$account_id = \sanitize_text_field( $account_id );

	if ( '' === $account_id || ! table_exists( $db, $table ) ) {
		return null;
	}

	$query = $db->prepare(
		"SELECT pageid,
			MAX(pagename) AS pagename,
			COUNT(*) AS total_review_count,
			AVG(CAST(NULLIF(rating, '') AS DECIMAL(3, 2))) AS average_rating
		FROM {$table}
		WHERE type = %s
			AND hide != %s
			AND pageid = %s
		GROUP BY pageid
		LIMIT 1",
		'Google',
		'yes',
		$account_id
	);

	if ( ! \is_string( $query ) ) {
		return null;
	}

	$account = $db->get_row( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( ! \is_array( $account ) ) {
		return null;
	}

	return array(
		'id'               => \sanitize_text_field( (string) $account['pageid'] ),
		'name'             => \sanitize_text_field( (string) $account['pagename'] ),
		'totalReviewCount' => \absint( $account['total_review_count'] ),
		'averageRating'    => (float) $account['average_rating'],
	);
}
