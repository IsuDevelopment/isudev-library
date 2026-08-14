<?php
/**
 * Render helpers for the selling point block.
 *
 * Pure functions only — no get_block_config(), no wp_get_attachment_image().
 * render.php gathers that context and passes it in.
 *
 * @package IsuDevLibrary\Blocks\SellingPoint
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\SellingPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Every recognized item width. Pure.
 *
 * @var string[]
 */
const ITEM_WIDTHS = array( 'size-20', 'size-25', 'size-33', 'size-50', 'size-75', 'size-100' );

/**
 * Resolve the item width, defaulting to ''. Pure.
 *
 * An empty width means the grid's own `defaultColumns` span applies instead
 * of one of the fixed size classes.
 *
 * @param string $value Candidate item width.
 * @return string One of ITEM_WIDTHS, or ''.
 */
function sanitize_item_width( string $value ): string {
	return \in_array( $value, ITEM_WIDTHS, true ) ? $value : '';
}

/**
 * Resolve the title element name, defaulting to a non-heading div. Pure.
 *
 * @param bool $render_as_heading Whether the title should be a heading at all.
 * @param int  $level             Requested heading level.
 * @return string
 */
function title_tag( bool $render_as_heading, int $level ): string {
	if ( ! $render_as_heading ) {
		return 'div';
	}

	return $level >= 2 && $level <= 6 ? 'h' . $level : 'h2';
}

/**
 * Build the item's own class list. Pure, and deliberately unsanitized.
 *
 * The caller passes each name through sanitize_html_class(), which is a
 * WordPress function and would break this file's purity.
 *
 * @param string $item_width One of ITEM_WIDTHS, or ''.
 * @param bool   $has_icon   Whether the icon renders.
 * @param bool   $has_image  Whether the image renders.
 * @param bool   $has_link   Whether the whole item is a link.
 * @return array
 */
function item_classes( string $item_width, bool $has_icon, bool $has_image, bool $has_link ): array {
	$classes = array( 'isudev-selling-point' );

	if ( '' !== $item_width ) {
		$classes[] = 'is-' . $item_width;
	}

	if ( $has_icon ) {
		$classes[] = 'has-icon';
	}

	if ( $has_image ) {
		$classes[] = 'has-image';
	}

	if ( $has_link ) {
		$classes[] = 'has-link';
	}

	return $classes;
}

/**
 * Build the `rel` attribute value for a link, or '' when neither flag is set. Pure.
 *
 * @param bool $opens_in_new_tab Whether the link opens in a new tab.
 * @param bool $is_nofollow      Whether the link is marked nofollow.
 * @return string
 */
function link_rel( bool $opens_in_new_tab, bool $is_nofollow ): string {
	return \trim( ( $opens_in_new_tab ? 'noopener noreferrer' : '' ) . ( $is_nofollow ? ' nofollow' : '' ) );
}
