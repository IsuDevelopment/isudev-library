<?php
/**
 * Render helpers for the content-with-sidebar wrapper block.
 *
 * Pure functions only — no get_block_config(). render.php gathers that
 * context and passes it in.
 *
 * @package IsuDevLibrary\Blocks\ContentSidebar
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ContentSidebar;

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the desktop sidebar side, defaulting to right. Pure.
 *
 * @param mixed $value Candidate value.
 * @return string 'left' or 'right'.
 */
function sanitize_sidebar_position( $value ): string {
	return 'left' === $value ? 'left' : 'right';
}

/**
 * Resolve where the sidebar sits once the columns stack, defaulting to bottom. Pure.
 *
 * @param mixed $value Candidate value.
 * @return string 'top' or 'bottom'.
 */
function sanitize_mobile_position( $value ): string {
	return 'top' === $value ? 'top' : 'bottom';
}

/**
 * Pick the effective value of a lockable setting. Pure.
 *
 * Mirrors the `<setting>.disable` / `<setting>.value` isudev.json convention:
 * a disabled control means the configured value wins over the saved attribute.
 *
 * @param bool  $disabled   Whether isudev.json disables the control.
 * @param mixed $configured The `<setting>.value` from isudev.json.
 * @param mixed $attribute  The block's saved attribute.
 * @return mixed
 */
function effective_value( bool $disabled, $configured, $attribute ) {
	return $disabled ? $configured : $attribute;
}

/**
 * Build the wrapper's class list. Pure, and deliberately unsanitized.
 *
 * The caller passes each name through sanitize_html_class(), a WordPress
 * function that would break this file's purity.
 *
 * @param string $sidebar_position 'left' or 'right'.
 * @param string $mobile_position  'top' or 'bottom'.
 * @param bool   $sticky           Whether the sidebar sticks on wide screens.
 * @return array
 */
function wrapper_classes( string $sidebar_position, string $mobile_position, bool $sticky ): array {
	$classes = array(
		'isudev-content-sidebar',
		'is-sidebar-' . sanitize_sidebar_position( $sidebar_position ),
		'is-mobile-sidebar-' . sanitize_mobile_position( $mobile_position ),
	);

	if ( $sticky ) {
		$classes[] = 'has-sticky-sidebar';
	}

	return $classes;
}
