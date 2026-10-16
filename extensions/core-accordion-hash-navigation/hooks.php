<?php
/**
 * Accordion links: stable ids on core/accordion-item, and a view script that syncs them with the URL hash.
 *
 * Core gives every item a per-request `accordion-item-N` id (wp_unique_id) on the toggle,
 * the panel's aria wiring and the item's Interactivity context. This replaces all four
 * together, so core's store and aria stay consistent. Details in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\CoreAccordionHashNavigation;

use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const SCRIPT_HANDLE = 'isudev-library-core-accordion-hash-navigation';
const MAX_ID_LENGTH = 80;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\wp_register_script(
		SCRIPT_HANDLE,
		\plugins_url( 'view.js', __FILE__ ),
		array(),
		VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	// After core's render callback, which is what writes the ids replaced here.
	\add_filter( 'render_block_core/accordion-item', __NAMESPACE__ . '\\render_item', 10, 2 );
	\add_filter( 'render_block_core/accordion', __NAMESPACE__ . '\\enqueue_view', 10, 1 );
}

/**
 * An id usable in a URL fragment and an HTML id, or ''.
 *
 * @param mixed $value Candidate id.
 * @return string
 */
function valid_id( $value ): string {
	if ( ! \is_string( $value ) ) {
		return '';
	}

	$value = \trim( $value );

	if ( '' === $value || 1 === \preg_match( '/[\s#"\'<>]/u', $value ) ) {
		return '';
	}

	return \mb_substr( $value, 0, MAX_ID_LENGTH );
}

/**
 * Claim an id in a registry: same fingerprint, same id; a taken id gets -2, -3, …
 *
 * Keyed by fingerprint so a block rendered twice in one request (an excerpt, a
 * schema pre-render, then the page) keeps the id the visitor's link points at.
 *
 * @param array  $registry    `ids` => id => fingerprint, `fingerprints` => fingerprint => id. By reference.
 * @param string $base        Wanted id.
 * @param string $fingerprint Identity of the item.
 * @return string
 */
function claim( array &$registry, string $base, string $fingerprint ): string {
	if ( isset( $registry['fingerprints'][ $fingerprint ] ) ) {
		return $registry['fingerprints'][ $fingerprint ];
	}

	$id     = $base;
	$suffix = 2;

	while ( isset( $registry['ids'][ $id ] ) ) {
		$id = $base . '-' . $suffix;
		++$suffix;
	}

	$registry['ids'][ $id ]                   = $fingerprint;
	$registry['fingerprints'][ $fingerprint ] = $id;

	return $id;
}

/**
 * Claim in the request-wide registry.
 *
 * @param string $base        Wanted id.
 * @param string $fingerprint Identity of the item.
 * @return string
 */
function claim_for_request( string $base, string $fingerprint ): string {
	static $registry = array(
		'ids'          => array(),
		'fingerprints' => array(),
	);

	return claim( $registry, $base, $fingerprint );
}

/**
 * Plain text of a heading's title span (core: `.wp-block-accordion-heading__toggle-title`).
 *
 * @param string $html Heading markup.
 * @return string
 */
function title_text( string $html ): string {
	$processor = new \WP_HTML_Tag_Processor( $html );

	if ( ! $processor->next_tag( array( 'class_name' => 'wp-block-accordion-heading__toggle-title' ) ) ) {
		return '';
	}

	$text  = '';
	$depth = 0;

	while ( $processor->next_token() ) {
		$type = $processor->get_token_type();

		if ( '#text' === $type ) {
			$text .= $processor->get_modifiable_text();
			continue;
		}

		if ( '#tag' !== $type || 'SPAN' !== $processor->get_tag() ) {
			continue;
		}

		if ( ! $processor->is_tag_closer() ) {
			++$depth;
		} elseif ( 0 === $depth-- ) {
			break;
		}
	}

	return \trim( (string) \preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * The item's own heading block: its HTML anchor (the `id` on the heading element) and title.
 *
 * Read from the saved markup, not attributes: core's `anchor` support sources it from `id`.
 *
 * @param array $block Parsed core/accordion-item.
 * @return array{anchor: string, title: string}
 */
function heading_parts( array $block ): array {
	foreach ( (array) ( $block['innerBlocks'] ?? array() ) as $inner ) {
		if ( ! \is_array( $inner ) || 'core/accordion-heading' !== ( $inner['blockName'] ?? '' ) ) {
			continue;
		}

		$html      = (string) ( $inner['innerHTML'] ?? '' );
		$processor = new \WP_HTML_Tag_Processor( $html );
		$anchor    = $processor->next_tag( array( 'class_name' => 'wp-block-accordion-heading' ) ) ? $processor->get_attribute( 'id' ) : '';

		return array(
			'anchor' => \is_string( $anchor ) ? \trim( $anchor ) : '',
			'title'  => title_text( $html ),
		);
	}

	return array(
		'anchor' => '',
		'title'  => '',
	);
}

/**
 * Replace core's id on the item context, toggle and panel; all or nothing.
 *
 * Scoped by core's own id: the toggle whose id and the panel whose aria-labelledby
 * equal the item's context id are this item's, never a nested accordion's.
 *
 * @param string $html Rendered item.
 * @param string $id   New id.
 * @return string Unchanged when any piece of core's wiring is missing.
 */
function rewrite_ids( string $html, string $id ): string {
	$processor = new \WP_HTML_Tag_Processor( $html );

	if ( ! $processor->next_tag() || true !== $processor->has_class( 'wp-block-accordion-item' ) ) {
		return $html;
	}

	$context = \json_decode( (string) $processor->get_attribute( 'data-wp-context' ), true );
	$core_id = \is_array( $context ) && \is_string( $context['id'] ?? null ) ? $context['id'] : '';

	if ( '' === $core_id || $core_id === $id ) {
		return $html;
	}

	$context['id'] = $id;
	$json          = \wp_json_encode( $context );

	if ( ! \is_string( $json ) ) {
		return $html;
	}

	$processor->set_attribute( 'data-wp-context', $json );

	$toggle = false;

	while ( $processor->next_tag() ) {
		if ( ! $toggle && $core_id === $processor->get_attribute( 'id' ) && true === $processor->has_class( 'wp-block-accordion-heading__toggle' ) ) {
			$processor->set_attribute( 'id', $id );
			$processor->set_attribute( 'aria-controls', $id . '-panel' );
			$toggle = true;
			continue;
		}

		if ( $toggle && $core_id === $processor->get_attribute( 'aria-labelledby' ) && true === $processor->has_class( 'wp-block-accordion-panel' ) ) {
			$processor->set_attribute( 'id', $id . '-panel' );
			$processor->set_attribute( 'aria-labelledby', $id );

			return $processor->get_updated_html();
		}
	}

	return $html;
}

/**
 * Drop the HTML anchor from the item's heading element once the toggle carries it.
 *
 * @param string $html   Rendered item.
 * @param string $anchor Anchor to remove.
 * @return string
 */
function move_anchor( string $html, string $anchor ): string {
	$processor = new \WP_HTML_Tag_Processor( $html );

	// The item's own heading is its first: core's item template is heading, then panel.
	if ( $processor->next_tag( array( 'class_name' => 'wp-block-accordion-heading' ) ) && $anchor === $processor->get_attribute( 'id' ) ) {
		$processor->remove_attribute( 'id' );

		return $processor->get_updated_html();
	}

	return $html;
}

/**
 * Filter callback for render_block_core/accordion-item.
 *
 * @param string $content Rendered item.
 * @param array  $block   Parsed block.
 * @return string
 */
function render_item( $content, $block ): string {
	$content = (string) $content;

	if ( '' === $content || ! \is_array( $block ) ) {
		return $content;
	}

	$parts  = heading_parts( $block );
	$anchor = valid_id( $parts['anchor'] );
	$base   = '' !== $anchor ? $anchor : valid_id( \rawurldecode( \sanitize_title( $parts['title'] ) ) );

	/**
	 * Filters the id an accordion item is linked by, before it is made unique.
	 *
	 * @param string $id    The heading's HTML anchor, else the title as a slug; '' keeps core's id.
	 * @param string $title Heading text.
	 * @param array  $block Parsed core/accordion-item.
	 */
	$base = valid_id( \apply_filters( 'isudev_library/extensions/core_accordion_hash_navigation/id', $base, $parts['title'], $block ) );

	if ( '' === $base ) {
		return $content;
	}

	$fingerprint = \md5( (string) \wp_json_encode( $block ) );
	$updated     = rewrite_ids( $content, claim_for_request( $base, $fingerprint ) );

	if ( $updated !== $content && '' !== $anchor ) {
		$updated = move_anchor( $updated, $parts['anchor'] );
	}

	return $updated;
}

/**
 * Enqueue the view script once an accordion renders.
 *
 * @param string $content Rendered accordion.
 * @return string Unchanged.
 */
function enqueue_view( $content ) {
	if ( '' !== (string) $content ) {
		\wp_enqueue_script( SCRIPT_HANDLE );
	}

	return $content;
}
