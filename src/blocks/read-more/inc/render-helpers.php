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

/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Preserve the native RichText highlight while rejecting all other markup.
 *
 * WordPress stores the core/text-color format as a mark element. The style
 * attribute is additionally filtered by WordPress' safe CSS allowlist.
 *
 * @param string $content RichText content.
 * @return string
 */
function sanitize_highlight( string $content ): string {
	return \wp_kses(
		$content,
		array(
			'mark' => array(
				'class' => true,
				'style' => true,
			),
		)
	);
}

/**
 * Resolve the linked post id, but only for a post this visitor may see.
 *
 * Without the visibility guard, linking a draft would leak its title onto a
 * public page through the title fallback.
 *
 * @param array $link_data The link attribute.
 * @return int Post id, or 0 when the link is not a viewable post.
 */
function resolve_link_post( array $link_data ): int {
	$kind = isset( $link_data['kind'] ) && \is_string( $link_data['kind'] ) ? $link_data['kind'] : '';
	$id   = isset( $link_data['id'] ) && \is_numeric( $link_data['id'] ) ? (int) $link_data['id'] : 0;

	if ( 'post-type' !== $kind || $id <= 0 ) {
		return 0;
	}

	$post = \get_post( $id );

	if ( ! $post instanceof \WP_Post || ! \is_post_publicly_viewable( $post ) ) {
		return 0;
	}

	return $id;
}

/**
 * Turn a pick_image() descriptor into figure markup.
 *
 * Reports whether an image was actually drawn, because an attachment id can
 * point at a deleted attachment and yield nothing. The caller needs that
 * answer for the has-image wrapper class and must not re-derive it by
 * searching the returned markup.
 *
 * @param array $picked Descriptor from pick_image().
 * @return array{html:string,has_image:bool}
 */
function render_image( array $picked ): array {
	$kind = isset( $picked['kind'] ) ? (string) $picked['kind'] : 'none';
	$html = '';

	if ( 'attachment' === $kind ) {
		$html = (string) \wp_get_attachment_image(
			(int) $picked['id'],
			'medium',
			false,
			array( 'sizes' => '(max-width: 600px) 100vw, 600px' )
		);
	}

	if ( 'url' === $kind ) {
		$html = \sprintf(
			'<img src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
			\esc_url( (string) $picked['url'] ),
			\esc_attr( (string) $picked['alt'] )
		);
	}

	if ( '' === $html ) {
		return array(
			'html'      => '<figure class="read-more-image no-image"></figure>',
			'has_image' => false,
		);
	}

	return array(
		'html'      => '<figure class="read-more-image">' . $html . '</figure>',
		'has_image' => true,
	);
}
