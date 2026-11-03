<?php
/**
 * Query slots: one query per wrapper, posts handed out in slot order.
 *
 * @package IsuDevLibrary\PostCards
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\PostCards;

defined( 'ABSPATH' ) || exit;

const ORDER_BY = array( 'date', 'modified', 'title', 'menu_order', 'rand', 'comment_count' );

/**
 * Normalise the wrapper's `query` attribute. Pure.
 *
 * @param array $query Raw attribute.
 * @return array{postType:string,taxonomy:string,terms:int[],orderBy:string,order:string,offset:int,excludeCurrent:bool}
 */
function normalize_query( array $query ): array {
	$post_type = isset( $query['postType'] ) && \is_string( $query['postType'] ) ? $query['postType'] : 'post';
	$taxonomy  = isset( $query['taxonomy'] ) && \is_string( $query['taxonomy'] ) ? $query['taxonomy'] : '';
	$terms     = \array_values( \array_filter( \array_map( 'intval', (array) ( $query['terms'] ?? array() ) ) ) );

	return array(
		'postType'       => 1 === \preg_match( '/^[a-z0-9_-]+$/', $post_type ) ? $post_type : 'post',
		'taxonomy'       => 1 === \preg_match( '/^[a-z0-9_-]+$/', $taxonomy ) ? $taxonomy : '',
		'terms'          => $terms,
		'orderBy'        => isset( $query['orderBy'] ) && \in_array( $query['orderBy'], ORDER_BY, true ) ? $query['orderBy'] : 'date',
		'order'          => isset( $query['order'] ) && 'asc' === \strtolower( (string) $query['order'] ) ? 'ASC' : 'DESC',
		'offset'         => isset( $query['offset'] ) ? \max( 0, (int) $query['offset'] ) : 0,
		'excludeCurrent' => ! isset( $query['excludeCurrent'] ) || (bool) $query['excludeCurrent'],
	);
}

/**
 * WP_Query arguments for a wrapper's query slots. Pure.
 *
 * @param array $query   Output of normalize_query().
 * @param int[] $exclude Post IDs that may not appear.
 * @param int   $count   Number of query slots.
 * @return array
 */
function query_args( array $query, array $exclude, int $count ): array {
	$args = array(
		'post_type'           => $query['postType'],
		'post_status'         => 'publish',
		'posts_per_page'      => \max( 1, $count ),
		'offset'              => $query['offset'],
		'orderby'             => $query['orderBy'],
		'order'               => $query['order'],
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'fields'              => 'ids',
	);

	$exclude = \array_values( \array_unique( \array_filter( \array_map( 'intval', $exclude ) ) ) );

	if ( array() !== $exclude ) {
		$args['post__not_in'] = $exclude;
	}

	if ( '' !== $query['taxonomy'] && array() !== $query['terms'] ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the editor picked these terms.
			array(
				'taxonomy' => $query['taxonomy'],
				'field'    => 'term_id',
				'terms'    => $query['terms'],
			),
		);
	}

	return $args;
}

/**
 * Hand query results to query slots in order. Pure.
 *
 * @param array[] $cards     Each `{ source, postId }`, in block order.
 * @param int[]   $query_ids Query results.
 * @return int[] Card index => post ID; 0 for a query slot left without a post.
 *     Hand-picked cards are not in the map.
 */
function assign_slots( array $cards, array $query_ids ): array {
	$assigned  = array();
	$query_ids = \array_values( $query_ids );
	$next      = 0;

	foreach ( \array_values( $cards ) as $index => $card ) {
		if ( 'query' !== ( $card['source'] ?? '' ) ) {
			continue;
		}

		$assigned[ $index ] = (int) ( $query_ids[ $next ] ?? 0 );
		++$next;
	}

	return $assigned;
}

/**
 * IDs of hand-picked cards. Pure.
 *
 * @param array[] $cards Each `{ source, postId }`.
 * @return int[]
 */
function picked_ids( array $cards ): array {
	$ids = array();

	foreach ( $cards as $card ) {
		if ( 'query' !== ( $card['source'] ?? '' ) && ! empty( $card['postId'] ) ) {
			$ids[] = (int) $card['postId'];
		}
	}

	return $ids;
}

/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Run a wrapper's query.
 *
 * @param array $query   Raw `query` attribute.
 * @param int[] $exclude Post IDs that may not appear.
 * @param int   $count   Number of query slots.
 * @param array $wrapper Wrapper attributes, passed to the filter.
 * @return int[]
 */
function query_post_ids( array $query, array $exclude, int $count, array $wrapper = array() ): array {
	if ( $count < 1 ) {
		return array();
	}

	$query = normalize_query( $query );

	if ( ! \post_type_exists( $query['postType'] ) || ! \is_post_type_viewable( $query['postType'] ) ) {
		return array();
	}

	if ( '' !== $query['taxonomy'] && ! \is_object_in_taxonomy( $query['postType'], $query['taxonomy'] ) ) {
		$query['taxonomy'] = '';
	}

	/**
	 * Filter the query behind a Post Cards block's query slots.
	 *
	 * Runs once per wrapper. `fields` must stay `ids`.
	 *
	 * @param array $args    WP_Query arguments.
	 * @param array $query   Normalised `query` attribute.
	 * @param array $wrapper Wrapper block attributes.
	 */
	$args           = (array) \apply_filters( 'isudev_library/post_cards/query_args', query_args( $query, $exclude, $count ), $query, $wrapper );
	$args['fields'] = 'ids';
	$wp_query       = new \WP_Query( $args );

	return \array_map( 'intval', (array) $wp_query->posts );
}

/**
 * Remember posts shown on this request, for wrappers with "No repeats on this page".
 *
 * @param int[]|null $add IDs to add; null to only read.
 * @return int[]
 */
function shown_ids( ?array $add = null ): array {
	static $shown = array();

	if ( null !== $add ) {
		$shown = \array_values( \array_unique( \array_merge( $shown, \array_map( 'intval', $add ) ) ) );
	}

	return $shown;
}
