<?php
/**
 * Render helpers for the agenda accordion item block.
 *
 * @package IsuDevLibrary\Blocks\AgendaAccordionItem
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\AgendaAccordionItem;

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the trigger's heading tag, defaulting to h3. Pure.
 *
 * @param int $level Candidate heading level.
 * @return string
 */
function heading_tag( int $level ): string {
	return \in_array( $level, array( 1, 2, 3, 4, 5, 6 ), true ) ? 'h' . $level : 'h3';
}

/**
 * Build the item's own class list. Pure, and deliberately unsanitized.
 *
 * The caller passes each name through sanitize_html_class(), which is a
 * WordPress function and would break this file's purity.
 *
 * @param bool $has_content Whether the item has any inner content to expand.
 * @return array
 */
function item_classes( bool $has_content ): array {
	$classes = array( 'isudev-agenda-accordion-item' );

	if ( ! $has_content ) {
		$classes[] = 'has-no-content';
	}

	return $classes;
}

/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Render a title or date value, honouring the item's own tag allowance.
 *
 * `allow_tags` covers only `<span>`, matching the trigger's own markup
 * structure — enough for a RichText author to nest a highlight span, not
 * enough to break the accordion's layout.
 *
 * @param string $value      Raw attribute value.
 * @param bool   $allow_tags Whether to allow the limited `<span>` markup.
 * @return string Escaped markup, safe to echo.
 */
function render_label( string $value, bool $allow_tags ): string {
	if ( $allow_tags ) {
		return \wp_kses( $value, array( 'span' => array() ) );
	}

	return \esc_html( \wp_strip_all_tags( $value ) );
}
