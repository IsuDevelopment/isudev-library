<?php
/**
 * Checks for the pure parts of the icon registry.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/utils/icon.php';

use function IsuDevLibrary\Utils\build_svg;
use function IsuDevLibrary\Utils\default_icon_paths;
use function IsuDevLibrary\Utils\default_icon_view_boxes;

$paths = default_icon_paths();

Checks::is( 'icons: chevronDown is registered', isset( $paths['chevronDown'] ), true );
Checks::is( 'icons: burger is registered', isset( $paths['burger'] ), true );
Checks::is( 'icons: close is registered', isset( $paths['close'] ), true );
Checks::is( 'icons: exactly four defaults', \count( $paths ), 4 );

Checks::is( 'build_svg: empty path yields empty string', build_svg( '', 24, 'x' ), '' );

$svg = build_svg( '<path d="M0 0"/>', 32, 'isudev-header__close-icon' );

Checks::is( 'build_svg: uses currentColor', \strpos( $svg, 'fill="currentColor"' ) !== false, true );
Checks::is( 'build_svg: is aria-hidden', \strpos( $svg, 'aria-hidden="true"' ) !== false, true );
Checks::is( 'build_svg: is not focusable', \strpos( $svg, 'focusable="false"' ) !== false, true );
Checks::is( 'build_svg: applies size to width and height', \strpos( $svg, 'width="32" height="32"' ) !== false, true );
Checks::is( 'build_svg: applies the class attribute', \strpos( $svg, 'class="isudev-header__close-icon"' ) !== false, true );
Checks::is( 'build_svg: embeds the path', \strpos( $svg, '<path d="M0 0"/>' ) !== false, true );

$boxes = default_icon_view_boxes();

Checks::is( 'icons: arrowForward is registered', isset( $paths['arrowForward'] ), true );
Checks::is( 'icons: arrowForward declares its own viewBox', $boxes['arrowForward'] ?? '', '0 0 24 24' );

/*
 * The three original glyphs are authored on the 600 grid and must NOT appear
 * in the override map — an entry there would silently rescale them.
 */
Checks::is( 'icons: chevronDown has no viewBox override', isset( $boxes['chevronDown'] ), false );
Checks::is( 'icons: burger has no viewBox override', isset( $boxes['burger'] ), false );
Checks::is( 'icons: close has no viewBox override', isset( $boxes['close'] ), false );

$default_box = build_svg( '<path d="M0 0"/>', 24, 'x' );
Checks::is(
	'build_svg: defaults to the 600 grid when no viewBox is passed',
	\strpos( $default_box, 'viewBox="0 0 600 600"' ) !== false,
	true
);

$custom_box = build_svg( '<path d="M0 0"/>', 24, 'x', '0 0 24 24' );
Checks::is(
	'build_svg: honours an explicit viewBox',
	\strpos( $custom_box, 'viewBox="0 0 24 24"' ) !== false,
	true
);
Checks::is(
	'build_svg: an explicit viewBox replaces the default rather than adding one',
	\substr_count( $custom_box, 'viewBox=' ),
	1
);
Checks::is(
	'build_svg: an empty viewBox falls back to the default',
	\strpos( build_svg( '<path d="M0 0"/>', 24, 'x', '' ), 'viewBox="0 0 600 600"' ) !== false,
	true
);

// The glyph must be path markup only: the wrapper already sets fill, and a
// fill on the path would override it and break currentColor tinting.
Checks::is(
	'icons: arrowForward carries no fill of its own',
	\strpos( $paths['arrowForward'] ?? '', 'fill=' ),
	false
);
