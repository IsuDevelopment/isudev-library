<?php
/**
 * Server render for isudev/social-share-network.
 *
 * Config keys read here are all under the parent's block name,
 * `isudev/social-share`, not this block's own name — the source plugin used a
 * single `library.json` key for both the wrapper and its network buttons, and
 * this keeps that one-key-per-feature shape instead of splitting it in two.
 *
 * @package IsuDevLibrary\Blocks\SocialShareNetwork
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused; this block has no InnerBlocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\SocialShareNetwork;

use function IsuDevLibrary\Config\get_block_config;
use function IsuDevLibrary\Utils\get_icon;

defined( 'ABSPATH' ) || exit;

/*
 * A local variable, not a file-level const: render.php is require()'d fresh
 * for every instance of this block on a page (see wp-includes/blocks.php),
 * and re-declaring a namespaced const on a second require() would fatal.
 */
$parent_block_name = 'isudev/social-share';

$network         = isset( $attributes['network'] ) && \is_string( $attributes['network'] ) && \in_array( $attributes['network'], NETWORKS, true )
	? $attributes['network']
	: 'facebook';
$label_attr      = isset( $attributes['label'] ) && \is_string( $attributes['label'] ) ? $attributes['label'] : '';
$show_label_attr = ! empty( $attributes['showLabel'] );
$label_position  = isset( $attributes['labelPosition'] ) && \in_array( $attributes['labelPosition'], array( 'before', 'after' ), true )
	? $attributes['labelPosition']
	: 'after';

/*
 * The variation namespace belongs to the parent block, so it arrives through
 * block context rather than this block's attributes. Without it, variation-
 * scoped config would apply to the wrapper's keys and be silently ignored for
 * every key read here.
 */
$namespace = isset( $block->context['isudev/socialShareNamespace'] ) && \is_string( $block->context['isudev/socialShareNamespace'] )
	? $block->context['isudev/socialShareNamespace']
	: '';

$permalink  = (string) \get_permalink();
$post_title = (string) \get_the_title();

/*
 * No permalink means no post context — an archive, a 404, a template part
 * outside the loop. A share control with an empty target shares nothing, so
 * render nothing, the way read-more drops a card with no destination.
 */
if ( '' === $permalink && needs_permalink( $network ) ) {
	return;
}

$label_disabled = (bool) get_block_config( $parent_block_name, 'networkLabel.disable', false, $namespace );
$show_label     = ! $label_disabled && $show_label_attr;

$label_text = '' !== \trim( $label_attr ) ? $label_attr : default_label( $network );

$icons_size = (int) get_block_config( $parent_block_name, 'iconsSize', 24, $namespace );
$overrides  = get_block_config( $parent_block_name, 'icons', array(), $namespace );
$overrides  = \is_array( $overrides ) ? $overrides : array();

$email_config = get_block_config( $parent_block_name, 'email', array(), $namespace );
$email_config = \is_array( $email_config ) ? $email_config : array();

$link_config = get_block_config( $parent_block_name, 'link', array(), $namespace );
$link_config = \is_array( $link_config ) ? $link_config : array();

$copy_text_template = isset( $link_config['copyText'] ) && \is_string( $link_config['copyText'] ) ? $link_config['copyText'] : '%url%';
$copy_text          = \str_replace( array( '%title%', '%url%' ), array( $post_title, $permalink ), $copy_text_template );

$url = share_url(
	$network,
	$permalink,
	$post_title,
	array(
		'subject' => $email_config['subject'] ?? null,
		'body'    => $email_config['body'] ?? null,
	)
);

$is_action   = is_action_network( $network );
$element_tag = $is_action ? 'button' : 'a';

$classes            = \array_map( '\sanitize_html_class', network_classes( $network, $label_position, $show_label ) );
$wrapper_attributes = \get_block_wrapper_attributes( array( 'class' => \implode( ' ', $classes ) ) );

/*
 * No `title` attribute, unlike the source: it duplicated `aria-label` on
 * every button. `aria-label` is added only when the label is not visible —
 * a visible label span already provides the accessible name, and a
 * duplicate aria-label would silently override it with the same words.
 */
$extra_attributes = \sprintf( ' data-network="%s"', \esc_attr( $network ) );

if ( ! $show_label ) {
	$extra_attributes .= \sprintf( ' aria-label="%s"', \esc_attr( $label_text ) );
}

if ( 'link' === $network ) {
	$extra_attributes .= \sprintf(
		' data-message="%s" data-copy-text="%s"',
		\esc_attr__( 'The link was copied to your clipboard', 'isudev-library' ),
		\esc_attr( $copy_text )
	);
} elseif ( 'system' === $network ) {
	$extra_attributes .= \sprintf( ' data-message="%s"', \esc_attr__( 'Link copied to clipboard', 'isudev-library' ) );
}

$href_attribute = $is_action ? '' : \sprintf( ' href="%s"', \esc_url( $url ) );

// The RichText value already carries HTML entities, so wp_kses() is used instead of esc_html(), which would double-escape it.
$label_markup = \sprintf( '<span class="isudev-share__network-label">%s</span>', \wp_kses( $label_text, array() ) );

$icon_markup = \sprintf(
	'<span class="isudev-share__network-icon">%s</span>',
	get_icon( icon_name( $network, $overrides ), array( 'size' => $icons_size ) )
);

$inner = $icon_markup;

if ( $show_label && 'before' === $label_position ) {
	$inner = $label_markup . $inner;
} elseif ( $show_label && 'after' === $label_position ) {
	$inner .= $label_markup;
}

$markup = \sprintf(
	'<%1$s %2$s%3$s%4$s>%5$s</%1$s>',
	$element_tag,
	$wrapper_attributes,
	$href_attribute,
	$extra_attributes,
	$inner
);

/*
 * $wrapper_attributes and esc_url()/esc_attr() escape their own output;
 * $label_markup went through wp_kses() above; the icon comes from the
 * static icon registry, escaped at render time by get_icon() itself.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
