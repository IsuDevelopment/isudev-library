<?php
/**
 * Render helpers for the read-more card block.
 *
 * @package IsuDevLibrary\Blocks\ReadMore
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ReadMore;

defined( 'ABSPATH' ) || exit;

/**
 * Choose the card title from the available sources. Pure.
 *
 * A custom title that is present but blank falls through rather than emptying
 * the card, so switching the toggle on before typing is not destructive.
 *
 * @param bool   $has_custom Whether the custom-title toggle is on.
 * @param string $custom     The author's custom title.
 * @param string $post_title Title of the linked post, '' when none resolved.
 * @param string $link_title Label carried by the link value.
 * @param string $url        The link URL, used as a last resort.
 * @return string
 */
function pick_title( bool $has_custom, string $custom, string $post_title, string $link_title, string $url ): string {
	if ( $has_custom && '' !== \trim( $custom ) ) {
		return $custom;
	}

	if ( '' !== $post_title ) {
		return $post_title;
	}

	if ( '' !== $link_title ) {
		return $link_title;
	}

	return $url;
}

/**
 * Decide which image source wins, without rendering anything. Pure.
 *
 * @param array $media        The media attribute.
 * @param int   $thumbnail_id Featured image id of the linked post, 0 when none.
 * @return array{kind:string,id:int,url:string,alt:string}
 */
function pick_image( array $media, int $thumbnail_id ): array {
	$none = array(
		'kind' => 'none',
		'id'   => 0,
		'url'  => '',
		'alt'  => '',
	);

	$id  = isset( $media['id'] ) && \is_numeric( $media['id'] ) ? (int) $media['id'] : 0;
	$url = isset( $media['url'] ) && \is_string( $media['url'] ) ? $media['url'] : '';
	$alt = isset( $media['alt'] ) && \is_string( $media['alt'] ) ? $media['alt'] : '';

	if ( $id > 0 ) {
		return array(
			'kind' => 'attachment',
			'id'   => $id,
			'url'  => $url,
			'alt'  => $alt,
		);
	}

	if ( '' !== $url ) {
		return array(
			'kind' => 'url',
			'id'   => 0,
			'url'  => $url,
			'alt'  => $alt,
		);
	}

	if ( $thumbnail_id > 0 ) {
		return array(
			'kind' => 'attachment',
			'id'   => $thumbnail_id,
			'url'  => '',
			'alt'  => '',
		);
	}

	return $none;
}

/**
 * Build the wrapper class list. Pure, and deliberately unsanitized.
 *
 * The caller passes each name through sanitize_html_class(), which is a
 * WordPress function and would break this file's purity.
 *
 * @param bool   $has_image Whether an image will be rendered.
 * @param bool   $has_badge Whether the read-more badge is shown.
 * @param string $link_type Link entity type, '' when unknown.
 * @return array
 */
function card_classes( bool $has_image, bool $has_badge, string $link_type ): array {
	$classes = array();

	if ( $has_image ) {
		$classes[] = 'has-image';
	}

	if ( $has_badge ) {
		$classes[] = 'has-read-more-badge';
	}

	if ( '' !== $link_type ) {
		$classes[] = 'is-link-type-' . $link_type;
	}

	return $classes;
}

/**
 * Resolve the title element name. Pure.
 *
 * @param bool $render_as_heading Whether the title is a heading at all.
 * @param int  $level             Requested heading level.
 * @return string
 */
function heading_tag( bool $render_as_heading, int $level ): string {
	if ( ! $render_as_heading ) {
		return 'div';
	}

	return \in_array( $level, array( 2, 3, 4, 5, 6 ), true ) ? 'h' . $level : 'h3';
}
