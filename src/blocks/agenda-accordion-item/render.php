<?php
/**
 * Server render for isudev/agenda-accordion-item.
 *
 * Config keys read here are all under the parent's block name,
 * `isudev/agenda-accordion`, not this block's own name — one feature, one
 * key, matching the isudev/social-share-network convention.
 *
 * @package IsuDevLibrary\Blocks\AgendaAccordionItem
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the item's own content).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\AgendaAccordionItem;

use function IsuDevLibrary\Config\get_block_config;
use function IsuDevLibrary\Utils\get_icon;

defined( 'ABSPATH' ) || exit;

$parent_block_name = 'isudev/agenda-accordion';

$item_title = isset( $attributes['title'] ) && \is_string( $attributes['title'] ) ? $attributes['title'] : '';

// An item with no title has nothing for the trigger to announce.
if ( '' === \trim( $item_title ) ) {
	return;
}

$namespace = isset( $block->context['isudev/agendaAccordionNamespace'] ) && \is_string( $block->context['isudev/agendaAccordionNamespace'] )
	? $block->context['isudev/agendaAccordionNamespace']
	: '';

$agenda_date         = isset( $attributes['agendaDate'] ) && \is_string( $attributes['agendaDate'] ) ? $attributes['agendaDate'] : '';
$excerpt             = isset( $attributes['excerpt'] ) && \is_string( $attributes['excerpt'] ) ? $attributes['excerpt'] : '';
$level               = heading_tag( isset( $attributes['level'] ) ? (int) $attributes['level'] : 3 );
$allow_tags_in_title = ! empty( $attributes['allowTagsInTitle'] );
$default_open        = ! empty( $attributes['defaultOpen'] );

$has_content = '' !== \trim( \wp_strip_all_tags( $content ) );

$excerpt_inside_trigger = (bool) get_block_config( $parent_block_name, 'excerpt.insideTrigger', false, $namespace );

$icon_closed = (string) get_block_config( $parent_block_name, 'icons.closed', 'chevronDown', $namespace );
$icon_open   = (string) get_block_config( $parent_block_name, 'icons.open', 'chevronUp', $namespace );
$icon_size   = (int) get_block_config( $parent_block_name, 'icons.size', 24, $namespace );

$string_for_id = \sanitize_title( $item_title );
$string_for_id = '' !== $string_for_id ? $string_for_id : 'agenda-item';

$button_id = \wp_unique_id( $string_for_id . '-trigger-' );
$panel_id  = \wp_unique_id( $string_for_id . '-panel-' );

$excerpt_html = '' !== \trim( $excerpt )
	? \sprintf( '<div class="isudev-agenda-accordion__excerpt">%s</div>', \wp_kses_post( $excerpt ) )
	: '';

$icons_html = '';
if ( $has_content ) {
	$icons_html = \sprintf(
		'<span class="isudev-agenda-accordion-icon is-closed-icon">%1$s</span><span class="isudev-agenda-accordion-icon is-open-icon">%2$s</span>',
		get_icon( $icon_closed, array( 'size' => $icon_size ) ),
		get_icon( $icon_open, array( 'size' => $icon_size ) )
	);
}

$date_html = '' !== \trim( $agenda_date )
	? \sprintf( '<span class="isudev-agenda-accordion__date">%s</span>', render_label( $agenda_date, $allow_tags_in_title ) )
	: '';

$item_title_inner_class = $has_content ? 'has-trigger' : 'no-trigger';

$item_title_inner_html = \sprintf(
	'<div class="isudev-agenda-accordion__title-inner %1$s">%2$s<span class="isudev-agenda-accordion__title">%3$s</span></div>',
	\esc_attr( $item_title_inner_class ),
	$date_html,
	render_label( $item_title, $allow_tags_in_title )
);

$trigger_html = \sprintf(
	'<%1$s class="isudev-agenda-accordion__title"><button aria-controls="%2$s" aria-expanded="%3$s" class="isudev-agenda-accordion__trigger" id="%4$s" type="button"%5$s>%6$s%7$s%8$s</button></%1$s>',
	\esc_html( $level ),
	\esc_attr( $panel_id ),
	$default_open ? 'true' : 'false',
	\esc_attr( $button_id ),
	$has_content ? '' : ' disabled',
	$item_title_inner_html,
	$icons_html,
	$excerpt_inside_trigger ? $excerpt_html : ''
);

if ( ! $excerpt_inside_trigger ) {
	$trigger_html .= $excerpt_html;
}

$wrapper_attributes = \get_block_wrapper_attributes(
	array( 'class' => \implode( ' ', \array_map( '\sanitize_html_class', item_classes( $has_content ) ) ) )
);

if ( ! $has_content ) {
	$markup = \sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $trigger_html );
} else {
	$panel_html = \sprintf(
		'<div aria-labelledby="%1$s" class="isudev-agenda-accordion-item__inner-container" id="%2$s" role="region"%3$s>%4$s</div>',
		\esc_attr( $button_id ),
		\esc_attr( $panel_id ),
		$default_open ? '' : ' hidden',
		$content
	);

	$markup = \sprintf( '<div %1$s>%2$s%3$s</div>', $wrapper_attributes, $trigger_html, $panel_html );
}

/*
 * $wrapper_attributes escapes its own output; $trigger_html is built from
 * render_label() (wp_kses()/esc_html()), get_icon() (escaped at its own
 * source) and esc_attr()/esc_html() on every dynamic value; $excerpt_html
 * uses wp_kses_post(); $content is inner-block markup, already rendered
 * safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
