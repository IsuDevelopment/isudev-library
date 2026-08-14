<?php
/**
 * Checks for the pure parts of the toggle content block.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/src/blocks/toggle-blocks/inc/render-helpers.php';

use function IsuDevLibrary\Blocks\ToggleBlocks\button_classes;
use function IsuDevLibrary\Blocks\ToggleBlocks\button_wrapper_classes;
use function IsuDevLibrary\Blocks\ToggleBlocks\default_close_text;
use function IsuDevLibrary\Blocks\ToggleBlocks\default_open_text;
use function IsuDevLibrary\Blocks\ToggleBlocks\sanitize_icon_position;
use function IsuDevLibrary\Blocks\ToggleBlocks\sanitize_placement;
use function IsuDevLibrary\Blocks\ToggleBlocks\wrapper_classes;

/*
 * default_open_text() / default_close_text(): non-empty translated fallbacks,
 * used when the block's own attributes are missing or blank.
 */
Checks::is( 'default_open_text: non-empty', '' !== default_open_text(), true );
Checks::is( 'default_close_text: non-empty', '' !== default_close_text(), true );

/*
 * sanitize_placement(): only 'top' is recognized as top; everything else,
 * including a mistyped or missing value, falls back to 'bottom'.
 */
Checks::is( 'sanitize_placement: top stays top', sanitize_placement( 'top' ), 'top' );
Checks::is( 'sanitize_placement: bottom stays bottom', sanitize_placement( 'bottom' ), 'bottom' );
Checks::is( 'sanitize_placement: an unknown value falls back to bottom', sanitize_placement( 'middle' ), 'bottom' );
Checks::is( 'sanitize_placement: empty string falls back to bottom', sanitize_placement( '' ), 'bottom' );

/*
 * sanitize_icon_position(): only 'left' is recognized as left; everything
 * else falls back to 'right'.
 */
Checks::is( 'sanitize_icon_position: left stays left', sanitize_icon_position( 'left' ), 'left' );
Checks::is( 'sanitize_icon_position: right stays right', sanitize_icon_position( 'right' ), 'right' );
Checks::is( 'sanitize_icon_position: an unknown value falls back to right', sanitize_icon_position( 'center' ), 'right' );
Checks::is( 'sanitize_icon_position: empty string falls back to right', sanitize_icon_position( '' ), 'right' );

/*
 * wrapper_classes(): the root class plus a placement modifier, no more and
 * no less.
 */
Checks::is( 'wrapper_classes: top', wrapper_classes( 'top' ), array( 'isudev-toggle', 'isudev-toggle--placement-top' ) );
Checks::is( 'wrapper_classes: bottom', wrapper_classes( 'bottom' ), array( 'isudev-toggle', 'isudev-toggle--placement-bottom' ) );

/*
 * button_wrapper_classes(): the core/button wrapper class is always present;
 * an `is-style-*` class appears only when a button style is set.
 */
$classes_no_style = button_wrapper_classes( 'bottom', '' );
Checks::true( 'button_wrapper_classes: contains wp-block-button', in_array( 'wp-block-button', $classes_no_style, true ) );
Checks::true( 'button_wrapper_classes: contains the placement class', in_array( 'isudev-toggle__button-wrapper--placement-bottom', $classes_no_style, true ) );
Checks::is(
	'button_wrapper_classes: no is-style-* class when button_style is empty',
	count(
		array_filter(
			$classes_no_style,
			static function ( $class_name ) {
				return 0 === strpos( $class_name, 'is-style-' );
			}
		)
	),
	0
);

$classes_with_style = button_wrapper_classes( 'top', 'outline' );
Checks::true( 'button_wrapper_classes: contains is-style-outline when set', in_array( 'is-style-outline', $classes_with_style, true ) );

/*
 * button_classes(): the icon-position modifier reflects the argument passed
 * in, not a re-derivation of it.
 */
Checks::true( 'button_classes: contains the left icon modifier', in_array( 'isudev-toggle__button--icon-left', button_classes( 'left' ), true ) );
Checks::true( 'button_classes: contains the right icon modifier', in_array( 'isudev-toggle__button--icon-right', button_classes( 'right' ), true ) );
Checks::true( 'button_classes: contains wp-element-button', in_array( 'wp-element-button', button_classes( 'right' ), true ) );
