<?php
/**
 * Server render for isudev/selling-point.
 *
 * Config keys read here are all under the parent's block name,
 * `isudev/selling-points`, not this block's own name — one feature, one
 * key, matching the isudev/social-share-network convention.
 *
 * @package IsuDevLibrary\Blocks\SellingPoint
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused; this block has no InnerBlocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\SellingPoint;

use function IsuDevLibrary\Config\get_block_config;
use function IsuDevLibrary\Utils\get_icon;

defined( 'ABSPATH' ) || exit;

$parent_block_name = 'isudev/selling-points';

$namespace = isset( $block->context['isudev/sellingPointsNamespace'] ) && \is_string( $block->context['isudev/sellingPointsNamespace'] )
	? $block->context['isudev/sellingPointsNamespace']
	: '';

$item_width = sanitize_item_width( isset( $attributes['itemWidth'] ) && \is_string( $attributes['itemWidth'] ) ? $attributes['itemWidth'] : '' );

$icon_name    = isset( $attributes['icon'] ) && \is_string( $attributes['icon'] ) ? $attributes['icon'] : '';
$show_icon    = ! empty( $attributes['showIcon'] );
$icon_enabled = (bool) get_block_config( $parent_block_name, 'features.hasIcon', true, $namespace );
$has_icon     = '' !== $icon_name && $icon_enabled && $show_icon;

$media         = isset( $attributes['media'] ) && \is_array( $attributes['media'] ) ? $attributes['media'] : array();
$media_id      = isset( $media['id'] ) && \is_numeric( $media['id'] ) ? (int) $media['id'] : 0;
$image_enabled = (bool) get_block_config( $parent_block_name, 'features.hasImage', false, $namespace );
$has_image     = $media_id > 0 && $image_enabled;

$link_data        = isset( $attributes['link'] ) && \is_array( $attributes['link'] ) ? $attributes['link'] : array();
$link_url         = isset( $link_data['url'] ) && \is_string( $link_data['url'] ) ? $link_data['url'] : '';
$has_link         = '' !== \trim( $link_url );
$opens_in_new_tab = ! empty( $link_data['opensInNewTab'] );
$is_nofollow      = ! empty( $link_data['nofollow'] );

$classes            = \array_map( '\sanitize_html_class', item_classes( $item_width, $has_icon, $has_image, $has_link ) );
$wrapper_attributes = \get_block_wrapper_attributes( array( 'class' => \implode( ' ', $classes ) ) );

$inner = '';

if ( $has_icon ) {
	$icon_size = isset( $block->context['isudev/sellingPointsIconSize'] ) && \is_numeric( $block->context['isudev/sellingPointsIconSize'] )
		? (int) $block->context['isudev/sellingPointsIconSize']
		: (int) get_block_config( $parent_block_name, 'iconSize', 28, $namespace );

	$inner .= \sprintf( '<div class="isudev-selling-point__icon">%s</div>', get_icon( $icon_name, array( 'size' => $icon_size ) ) );
}

$badge_text = isset( $attributes['badgeText'] ) && \is_string( $attributes['badgeText'] ) ? $attributes['badgeText'] : '';
$show_badge = ! empty( $attributes['showBadge'] );

if ( '' !== \trim( $badge_text ) && $show_badge ) {
	$inner .= \sprintf( '<span class="isudev-selling-point__badge">%s</span>', \esc_html( \wp_strip_all_tags( $badge_text ) ) );
}

if ( $has_image ) {
	$image_size = (string) get_block_config( $parent_block_name, 'imageSize', 'medium_large', $namespace );
	$inner     .= \sprintf( '<figure class="isudev-selling-point__figure">%s</figure>', \wp_get_attachment_image( $media_id, $image_size ) );
}

$point_title = isset( $attributes['title'] ) && \is_string( $attributes['title'] ) ? $attributes['title'] : '';

if ( '' !== \trim( $point_title ) ) {
	$render_as_heading = ! empty( $block->context['isudev/sellingPointsRenderTitlesAsHeadings'] );
	$heading_level     = isset( $block->context['isudev/sellingPointsHeadingLevel'] ) ? (int) $block->context['isudev/sellingPointsHeadingLevel'] : 2;
	$title_tag         = title_tag( $render_as_heading, $heading_level );

	// The RichText value already carries HTML entities, so wp_kses() is used instead of esc_html(), which would double-escape it.
	$inner .= \sprintf( '<%1$s class="isudev-selling-point__title">%2$s</%1$s>', $title_tag, \wp_kses( $point_title, array() ) );
}

$description = isset( $attributes['description'] ) && \is_string( $attributes['description'] ) ? $attributes['description'] : '';

if ( '' !== \trim( $description ) ) {
	$inner .= \sprintf( '<p class="isudev-selling-point__description">%s</p>', \wp_kses_post( $description ) );
}

$link_text      = isset( $attributes['linkText'] ) && \is_string( $attributes['linkText'] ) ? $attributes['linkText'] : '';
$show_link_text = ! empty( $attributes['showLinkText'] );

if ( $show_link_text && '' !== \trim( $link_text ) ) {
	$link_text_html = \wp_kses( $link_text, array() );

	/*
	 * Only wrapped in its own <a> when the item as a whole is not already a
	 * link — an <a> nested inside another <a> is invalid and unreachable by
	 * keyboard for the inner one.
	 */
	if ( ! $has_link ) {
		$inner .= \sprintf( '<div class="isudev-selling-point__link">%s</div>', $link_text_html );
	} else {
		$rel         = link_rel( $opens_in_new_tab, $is_nofollow );
		$target_attr = $opens_in_new_tab ? ' target="_blank"' : '';
		$rel_attr    = '' !== $rel ? \sprintf( ' rel="%s"', \esc_attr( $rel ) ) : '';
		$inner      .= \sprintf(
			'<div class="isudev-selling-point__link"><a href="%1$s"%2$s%3$s>%4$s</a></div>',
			\esc_url( $link_url ),
			$target_attr,
			$rel_attr,
			$link_text_html
		);
	}
}

if ( $has_link ) {
	$rel         = link_rel( $opens_in_new_tab, $is_nofollow );
	$target_attr = $opens_in_new_tab ? ' target="_blank"' : '';
	$rel_attr    = '' !== $rel ? \sprintf( ' rel="%s"', \esc_attr( $rel ) ) : '';

	$markup = \sprintf(
		'<a %1$s href="%2$s"%3$s%4$s>%5$s</a>',
		$wrapper_attributes,
		\esc_url( $link_url ),
		$target_attr,
		$rel_attr,
		$inner
	);
} else {
	$markup = \sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $inner );
}

/*
 * $wrapper_attributes escapes its own output; the icon comes from the static
 * icon registry, escaped at render time by get_icon() itself; badge and link
 * text go through wp_kses()/esc_html(); the title and description go through
 * wp_kses()/wp_kses_post() because their RichText values already carry HTML
 * entities, so esc_html() would double-escape them; the image comes from
 * wp_get_attachment_image(); href/target/rel are escaped with esc_url()/esc_attr().
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
