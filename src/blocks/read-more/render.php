<?php
/**
 * Server render for isudev/read-more.
 *
 * @package IsuDevLibrary\Blocks\ReadMore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused; this block has no InnerBlocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ReadMore;

use function IsuDevLibrary\Utils\icon;

defined( 'ABSPATH' ) || exit;

$link_data = isset( $attributes['link'] ) && \is_array( $attributes['link'] ) ? $attributes['link'] : array();
$url       = isset( $link_data['url'] ) && \is_string( $link_data['url'] ) ? $link_data['url'] : '';

// A card with no destination is not a card.
if ( '' === \trim( $url ) ) {
	return;
}

$media             = isset( $attributes['media'] ) && \is_array( $attributes['media'] ) ? $attributes['media'] : array();
$show_image        = ! isset( $attributes['showFeaturedImage'] ) || (bool) $attributes['showFeaturedImage'];
$show_badge        = ! empty( $attributes['showReadMoreBadge'] );
$show_additional   = ! empty( $attributes['showAdditionalText'] );
$has_custom_title  = ! empty( $attributes['hasCustomTitle'] );
$custom_title      = isset( $attributes['customTitle'] ) && \is_string( $attributes['customTitle'] ) ? $attributes['customTitle'] : '';
$additional_text   = isset( $attributes['additionalText'] ) && \is_string( $attributes['additionalText'] ) ? $attributes['additionalText'] : '';
$read_more_text    = isset( $attributes['readMoreText'] ) && \is_string( $attributes['readMoreText'] ) ? $attributes['readMoreText'] : '';
$render_as_heading = ! isset( $attributes['renderAsHeading'] ) || (bool) $attributes['renderAsHeading'];
$heading_level     = isset( $attributes['headingLevel'] ) ? (int) $attributes['headingLevel'] : 3;
$link_type         = isset( $link_data['type'] ) && \is_string( $link_data['type'] ) ? $link_data['type'] : '';
$opens_in_new_tab  = ! empty( $link_data['opensInNewTab'] );
$is_nofollow       = ! empty( $link_data['nofollow'] );

$linked_post_id = resolve_link_post( $link_data );
$post_title     = $linked_post_id > 0 ? (string) \get_the_title( $linked_post_id ) : '';
$link_title     = isset( $link_data['title'] ) && \is_string( $link_data['title'] ) ? $link_data['title'] : '';

$card_title = pick_title( $has_custom_title, $custom_title, $post_title, $link_title, $url );
$title_tag  = heading_tag( $render_as_heading, $heading_level );

$thumbnail_id = $linked_post_id > 0 ? (int) \get_post_thumbnail_id( $linked_post_id ) : 0;
$picked       = pick_image( $media, $thumbnail_id );

/*
 * render_image() reports whether it drew anything, rather than the caller
 * sniffing its markup for 'no-image' — an attachment id can resolve to
 * nothing, so the descriptor alone does not answer this.
 */
$figure    = '';
$has_image = false;

if ( $show_image ) {
	$rendered  = render_image( $picked );
	$figure    = $rendered['html'];
	$has_image = $rendered['has_image'];
}

$badge = $show_badge
	? \sprintf(
		'<span class="wp-block-button__read_more">%s</span>',
		sanitize_highlight( '' !== $read_more_text ? $read_more_text : \__( 'Read more', 'isudev-library' ) )
	)
	: '';

$additional = $show_additional && '' !== $additional_text
	? \sprintf( '<p class="read-more-additional-text">%s</p>', sanitize_highlight( $additional_text ) )
	: '';

$arrow = \sprintf(
	'<span class="read-more-arrow" aria-hidden="true">%s</span>',
	icon( 'arrowForward', 24, 'read-more-arrow__icon' )
);

$inner = \sprintf(
	'<div class="read-more-inner">%1$s<div class="read-more-content">%2$s<%3$s class="read-more-title">%4$s</%3$s>%5$s</div>%6$s</div>',
	$figure,
	$badge,
	$title_tag,
	\esc_html( $card_title ),
	$additional,
	$arrow
);

$classes = \array_map( '\sanitize_html_class', card_classes( $has_image, $show_badge, $link_type ) );

$wrapper = \get_block_wrapper_attributes(
	array( 'class' => \implode( ' ', $classes ) )
);

/*
 * Rebuild target and rel server-side. The editor normalizes link.rel, but an
 * attribute is author-supplied data and is not evidence of anything.
 */
$target_attr = $opens_in_new_tab ? ' target="_blank"' : '';
$rel_value   = \trim( ( $opens_in_new_tab ? 'noopener noreferrer' : '' ) . ( $is_nofollow ? ' nofollow' : '' ) );
$rel_attr    = '' !== $rel_value ? \sprintf( ' rel="%s"', \esc_attr( $rel_value ) ) : '';

/*
 * Every part above is escaped at its own boundary: esc_html on the title,
 * esc_url on the href, wp_kses on both RichText fields, esc_attr on rel, and
 * the arrow comes from the static icon registry. get_block_wrapper_attributes()
 * escapes its own output.
 */
$markup = \sprintf(
	'<a %1$s href="%2$s"%3$s%4$s>%5$s</a>',
	$wrapper,
	\esc_url( $url ),
	$target_attr,
	$rel_attr,
	$inner
);

echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
