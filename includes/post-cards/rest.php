<?php
/**
 * Editor preview: renders cards with the same PHP as the front end.
 *
 * @package IsuDevLibrary\PostCards
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\PostCards;

use const IsuDevLibrary\REST\NAMESPACE_V1;

defined( 'ABSPATH' ) || exit;

const PREVIEW_MAX_ITEMS = 100;

/**
 * Register the route.
 *
 * @return void
 */
function register_routes(): void {
	\register_rest_route(
		NAMESPACE_V1,
		'/post-cards/preview',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\preview',
			'permission_callback' => static function (): bool {
				return \current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'items' => array(
					'type'     => 'array',
					'required' => true,
					'maxItems' => PREVIEW_MAX_ITEMS,
					'items'    => array( 'type' => 'object' ),
				),
			),
		)
	);
}

/**
 * POST /post-cards/preview
 *
 * Each item: `{ key, attributes, context?, wrapper?, withTemplate? }`. `wrapper` is
 * `{ attributes, cards: [ { source, postId } ], index, currentPostId, also }` and is
 * needed for a query slot, whose post depends on its siblings.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function preview( \WP_REST_Request $request ): \WP_REST_Response {
	$items  = (array) $request->get_param( 'items' );
	$slots  = array();
	$result = array();

	foreach ( \array_slice( $items, 0, PREVIEW_MAX_ITEMS ) as $item ) {
		if ( ! \is_array( $item ) || ! isset( $item['key'] ) || ! \is_scalar( $item['key'] ) ) {
			continue;
		}

		$key        = (string) $item['key'];
		$attributes = isset( $item['attributes'] ) && \is_array( $item['attributes'] ) ? $item['attributes'] : array();
		$context    = preview_context( $item );

		if ( 'query' === ( $attributes['source'] ?? '' ) && isset( $item['wrapper'] ) && \is_array( $item['wrapper'] ) ) {
			$context['isudev/slotPostId'] = preview_slot_post( $item['wrapper'], $slots );
		}

		$card = resolve_card( $attributes, $context );

		if ( null === $card ) {
			$result[ $key ] = array( 'empty' => true );
			continue;
		}

		$result[ $key ] = array(
			'empty'    => false,
			'postId'   => $card['post']->ID,
			'postType' => $card['post']->post_type,
			'classes'  => card_classes( $card['post'], $card['variant'], $card['options'] ),
			'html'     => render_card_inner( $card['post'], $card['variant'], $card['options'], $attributes ),
		);

		if ( ! empty( $item['withTemplate'] ) ) {
			$template = card_template( $card['post'], $card['variant'], $card['options'], $attributes );

			// A PHP template has no block markup to copy into the card.
			$result[ $key ]['template']     = 'php' === $template['type'] ? '' : $template['content'];
			$result[ $key ]['templateType'] = $template['type'];
			$result[ $key ]['templateSlug'] = $template['slug'];
		}
	}

	return new \WP_REST_Response( array( 'items' => (object) $result ) );
}

/**
 * Context an item's card would receive on the front end.
 *
 * @param array $item Preview item.
 * @return array
 */
function preview_context( array $item ): array {
	$raw     = isset( $item['context'] ) && \is_array( $item['context'] ) ? $item['context'] : array();
	$context = array();

	foreach ( array( 'postId', 'queryId', 'isudev/cardsVariant' ) as $key ) {
		if ( isset( $raw[ $key ] ) && \is_scalar( $raw[ $key ] ) ) {
			$context[ $key ] = 'isudev/cardsVariant' === $key ? (string) $raw[ $key ] : (int) $raw[ $key ];
		}
	}

	if ( isset( $raw['isudev/cardsOptions'] ) && \is_array( $raw['isudev/cardsOptions'] ) ) {
		$context['isudev/cardsOptions'] = $raw['isudev/cardsOptions'];
	}

	return $context;
}

/**
 * The post a query slot gets, running each distinct wrapper query once.
 *
 * @param array $wrapper `{ attributes, cards, index, currentPostId, also }`.
 * @param array $slots   Per-request cache, by reference.
 * @return int
 */
function preview_slot_post( array $wrapper, array &$slots ): int {
	$attributes = isset( $wrapper['attributes'] ) && \is_array( $wrapper['attributes'] ) ? $wrapper['attributes'] : array();
	$cards      = isset( $wrapper['cards'] ) && \is_array( $wrapper['cards'] ) ? \array_values( \array_filter( $wrapper['cards'], 'is_array' ) ) : array();
	$current    = (int) ( $wrapper['currentPostId'] ?? 0 );
	$also       = \array_map( 'intval', isset( $wrapper['also'] ) && \is_array( $wrapper['also'] ) ? $wrapper['also'] : array() );
	$hash       = \md5( (string) \wp_json_encode( array( $attributes['query'] ?? array(), $cards, $current, $also ) ) );

	if ( ! isset( $slots[ $hash ] ) ) {
		$slots[ $hash ] = wrapper_assignments( $attributes, $cards, $current, $also );
	}

	return (int) ( $slots[ $hash ][ (int) ( $wrapper['index'] ?? -1 ) ] ?? 0 );
}

/**
 * Query-slot assignments for one wrapper.
 *
 * @param array   $attributes Wrapper attributes.
 * @param array[] $cards      Each `{ source, postId }`, in block order.
 * @param int     $current    Post being viewed or edited, 0 for none.
 * @param int[]   $also       More IDs to exclude ("No repeats on this page").
 * @return int[] Card index => post ID.
 */
function wrapper_assignments( array $attributes, array $cards, int $current, array $also = array() ): array {
	$query   = isset( $attributes['query'] ) && \is_array( $attributes['query'] ) ? $attributes['query'] : array();
	$exclude = \array_merge( picked_ids( $cards ), $also );

	if ( $current > 0 && normalize_query( $query )['excludeCurrent'] ) {
		$exclude[] = $current;
	}

	$count = \count( assign_slots( $cards, array() ) );

	return assign_slots( $cards, query_post_ids( $query, $exclude, $count, $attributes ) );
}
