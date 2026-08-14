<?php
/**
 * Server render for isudev/toggle-blocks.
 *
 * @package IsuDevLibrary\Blocks\ToggleBlocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the collapsible content).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ToggleBlocks;

use function IsuDevLibrary\Config\get_block_config;
use function IsuDevLibrary\Utils\get_icon;

defined( 'ABSPATH' ) || exit;

$block_name = 'isudev/toggle-blocks';

$open_text  = isset( $attributes['openText'] ) && \is_string( $attributes['openText'] ) && '' !== $attributes['openText']
	? $attributes['openText']
	: default_open_text();
$close_text = isset( $attributes['closeText'] ) && \is_string( $attributes['closeText'] ) && '' !== $attributes['closeText']
	? $attributes['closeText']
	: default_close_text();

$open_by_default   = ! empty( $attributes['openByDefault'] );
$scroll_to_content = ! empty( $attributes['scrollToContent'] );
$toggle_speed      = isset( $attributes['toggleSpeed'] ) ? \max( 0, (int) $attributes['toggleSpeed'] ) : 150;
$placement         = sanitize_placement( isset( $attributes['togglePlacement'] ) && \is_string( $attributes['togglePlacement'] ) ? $attributes['togglePlacement'] : 'bottom' );
$button_style      = isset( $attributes['buttonStyle'] ) && \is_string( $attributes['buttonStyle'] ) ? \sanitize_html_class( $attributes['buttonStyle'] ) : '';
$namespace         = isset( $attributes['_namespace'] ) && \is_string( $attributes['_namespace'] ) ? $attributes['_namespace'] : '';

$icon_position = sanitize_icon_position( (string) get_block_config( $block_name, 'iconPosition', 'right', $namespace ) );

$open_icon_name  = (string) get_block_config( $block_name, 'icons.open', '', $namespace );
$close_icon_name = (string) get_block_config( $block_name, 'icons.close', '', $namespace );

$open_icon_markup  = '' !== $open_icon_name ? get_icon( $open_icon_name ) : '';
$close_icon_markup = '' !== $close_icon_name ? get_icon( $close_icon_name ) : '';

/*
 * One configured icon covers both states: an open icon with no close icon
 * reuses itself, and vice versa. Only a theme configuring neither renders no
 * icon at all.
 */
if ( '' === $open_icon_markup && '' !== $close_icon_markup ) {
	$open_icon_markup = $close_icon_markup;
}
if ( '' === $close_icon_markup && '' !== $open_icon_markup ) {
	$close_icon_markup = $open_icon_markup;
}

$unique_id  = 'toggle-' . \wp_unique_id();
$button_id  = $unique_id . '-button';
$content_id = $unique_id . '-content';

$wrapper_attributes = \get_block_wrapper_attributes(
	array(
		'class' => \implode( ' ', \array_map( '\sanitize_html_class', wrapper_classes( $placement ) ) ),
		'style' => '--isudev-toggle-speed: ' . $toggle_speed . 'ms;',
	)
);

$button_wrapper_class = \implode( ' ', \array_map( '\sanitize_html_class', button_wrapper_classes( $placement, $button_style ) ) );
$button_class         = \implode( ' ', \array_map( '\sanitize_html_class', button_classes( $icon_position ) ) );

$icons_markup = '';
if ( '' !== $open_icon_markup || '' !== $close_icon_markup ) {
	$icons_markup = \sprintf(
		'<span class="isudev-toggle__icons" aria-hidden="true"><span class="isudev-toggle__icon isudev-toggle__icon--open">%1$s</span><span class="isudev-toggle__icon isudev-toggle__icon--close">%2$s</span></span>',
		$open_icon_markup,
		$close_icon_markup
	);
}

$button_text_default = $open_by_default ? $close_text : $open_text;

// The RichText value already carries HTML entities, so wp_kses() is used instead of esc_html(), which would double-escape it.
$button_text_markup = \sprintf( '<span class="isudev-toggle__text">%s</span>', \wp_kses( $button_text_default, array() ) );

$button_markup = \sprintf(
	'<div class="%1$s"><button id="%2$s" class="%3$s" type="button" aria-expanded="%4$s" aria-controls="%5$s" data-open-text="%6$s" data-close-text="%7$s">%8$s%9$s</button></div>',
	\esc_attr( $button_wrapper_class ),
	\esc_attr( $button_id ),
	\esc_attr( $button_class ),
	$open_by_default ? 'true' : 'false',
	\esc_attr( $content_id ),
	\esc_attr( $open_text ),
	\esc_attr( $close_text ),
	$button_text_markup,
	$icons_markup
);

$markup = \sprintf(
	'<div %1$s data-toggle-id="%2$s" data-scroll-to-content="%3$s">%4$s<div id="%5$s" class="isudev-toggle__content" role="region" aria-labelledby="%6$s"%7$s>%8$s</div>%9$s</div>',
	$wrapper_attributes,
	\esc_attr( $unique_id ),
	$scroll_to_content ? 'true' : 'false',
	'top' === $placement ? $button_markup : '',
	\esc_attr( $content_id ),
	\esc_attr( $button_id ),
	$open_by_default ? '' : ' hidden',
	$content,
	'bottom' === $placement ? $button_markup : ''
);

/*
 * $wrapper_attributes escapes its own output; $button_text_markup went
 * through wp_kses() above; $icons_markup comes from the static icon
 * registry, escaped at render time by get_icon() itself; $content is
 * inner-block markup, which WordPress already renders safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
