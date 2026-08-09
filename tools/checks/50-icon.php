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

$paths = default_icon_paths();

Checks::is( 'icons: chevronDown is registered', isset( $paths['chevronDown'] ), true );
Checks::is( 'icons: burger is registered', isset( $paths['burger'] ), true );
Checks::is( 'icons: close is registered', isset( $paths['close'] ), true );
Checks::is( 'icons: exactly three defaults', \count( $paths ), 3 );

Checks::is( 'build_svg: empty path yields empty string', build_svg( '', 24, 'x' ), '' );

$svg = build_svg( '<path d="M0 0"/>', 32, 'isudev-header__close-icon' );

Checks::is( 'build_svg: uses currentColor', \strpos( $svg, 'fill="currentColor"' ) !== false, true );
Checks::is( 'build_svg: is aria-hidden', \strpos( $svg, 'aria-hidden="true"' ) !== false, true );
Checks::is( 'build_svg: is not focusable', \strpos( $svg, 'focusable="false"' ) !== false, true );
Checks::is( 'build_svg: applies size to width and height', \strpos( $svg, 'width="32" height="32"' ) !== false, true );
Checks::is( 'build_svg: applies the class attribute', \strpos( $svg, 'class="isudev-header__close-icon"' ) !== false, true );
Checks::is( 'build_svg: embeds the path', \strpos( $svg, '<path d="M0 0"/>' ) !== false, true );
