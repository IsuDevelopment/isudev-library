<?php
/**
 * Checks for the icon registry and rendering helpers.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/utils/icon.php';

use function IsuDevLibrary\Utils\apply_root_attrs;
use function IsuDevLibrary\Utils\build_attrs;
use function IsuDevLibrary\Utils\default_icons;
use function IsuDevLibrary\Utils\get_icon;
use function IsuDevLibrary\Utils\normalize_icons;
use function IsuDevLibrary\Utils\normalize_size;
use function IsuDevLibrary\Utils\render_icon;

$icons = default_icons();

Checks::is( 'icons: exactly four defaults', count( $icons ), 4 );

$all_have_definitions = true;
foreach ( $icons as $definition ) {
	if ( ! isset( $definition['icon'], $definition['label'] ) || ! is_string( $definition['icon'] ) || '' === $definition['icon'] || ! is_string( $definition['label'] ) || '' === $definition['label'] ) {
		$all_have_definitions = false;
		break;
	}
}
Checks::is( 'icons: every default has icon markup and a label', $all_have_definitions, true );

$all_are_svg = true;
foreach ( $icons as $definition ) {
	if ( 0 !== strpos( $definition['icon'], '<svg' ) || false === strpos( $definition['icon'], 'viewBox=' ) ) {
		$all_are_svg = false;
		break;
	}
}
Checks::is( 'icons: every default is a complete SVG with a viewBox', $all_are_svg, true );

Checks::is( 'icons: arrowForward uses the 24 grid', strpos( $icons['arrowForward']['icon'], 'viewBox="0 0 24 24"' ) !== false, true );
Checks::is( 'icons: chevronDown uses the 600 grid', strpos( $icons['chevronDown']['icon'], 'viewBox="0 0 600 600"' ) !== false, true );

$normalized = normalize_icons(
	array(
		'fallback' => array( 'icon' => '<svg></svg>' ),
		'empty'    => array( 'icon' => '' ),
		'future'   => array(
			'icon'     => '<svg></svg>',
			'label'    => 'Future',
			'category' => 'ui',
		),
	)
);

Checks::is( 'normalize_icons: missing label falls back to name', $normalized['fallback']['label'] ?? '', 'fallback' );
Checks::is( 'normalize_icons: empty icon is dropped', isset( $normalized['empty'] ), false );
Checks::is( 'normalize_icons: unknown keys pass through', $normalized['future']['category'] ?? '', 'ui' );

Checks::is( 'normalize_size: scalar applies to both dimensions', normalize_size( 24 ), array( 24, 24 ) );
Checks::is( 'normalize_size: array preserves separate dimensions', normalize_size( array( 32, 16 ) ), array( 32, 16 ) );
Checks::is( 'normalize_size: non-positive scalar uses the default', normalize_size( 0 ), array( 24, 24 ) );

Checks::is( 'build_attrs: quoted values are escaped', build_attrs( array( 'title' => 'a "quoted" value' ) ), ' title="a &quot;quoted&quot; value"' );
Checks::is( 'build_attrs: event handlers are rejected', build_attrs( array( 'onclick' => 'x()' ) ), '' );
Checks::is( 'build_attrs: null removes an attribute', build_attrs( array( 'aria-hidden' => null ) ), '' );
Checks::is( 'build_attrs: empty alt text is retained', build_attrs( array( 'alt' => '' ) ), ' alt=""' );

$applied = apply_root_attrs( '<svg viewBox="0 0 24 24"><path/></svg>', array( 'width' => 20, 'class' => 'x' ) );
Checks::is( 'apply_root_attrs: adds width', strpos( $applied, 'width="20"' ) !== false, true );
Checks::is( 'apply_root_attrs: adds class', strpos( $applied, 'class="x"' ) !== false, true );
Checks::is( 'apply_root_attrs: preserves one viewBox', substr_count( $applied, 'viewBox=' ), 1 );

$overridden = apply_root_attrs( '<svg width="99" viewBox="0 0 24 24"><path/></svg>', array( 'width' => 20 ) );
Checks::is( 'apply_root_attrs: removes the existing width', substr_count( $overridden, 'width=' ), 1 );
Checks::is( 'apply_root_attrs: replaces width with the requested value', strpos( $overridden, 'width="20"' ) !== false, true );

/*
 * An attribute name build_attrs() refuses to write must not be stripped either.
 * Stripping without writing back would delete the glyph's own viewBox and put
 * nothing in its place, leaving an SVG with no coordinate system.
 */
$unwritable = apply_root_attrs( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path/></svg>', array( 'viewBox' => '0 0 48 48' ) );
Checks::is( 'apply_root_attrs: an unwritable name keeps the source viewBox', strpos( $unwritable, 'viewBox="0 0 24 24"' ) !== false, true );

$mixed_case = apply_root_attrs( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path/></svg>', array( 'XMLNS' => 'y' ) );
Checks::is( 'apply_root_attrs: a mixed-case name keeps the source xmlns', strpos( $mixed_case, 'xmlns="http://www.w3.org/2000/svg"' ) !== false, true );

$attempted_override = get_icon( 'arrowForward', array( 'attrs' => array( 'viewBox' => '0 0 48 48' ) ) );
Checks::is( 'get_icon: a viewBox override is ignored, not destructive', strpos( $attempted_override, 'viewBox="0 0 24 24"' ) !== false, true );

$image = render_icon( 'https://example.com/i.svg', array( 'width' => 24, 'alt' => '' ) );
Checks::is( 'render_icon: URL starts an image element', 0 === strpos( $image, '<img ' ), true );
Checks::is( 'render_icon: URL becomes the image source', strpos( $image, 'src="https://example.com/i.svg"' ) !== false, true );
Checks::is( 'render_icon: image keeps empty alt text', strpos( $image, 'alt=""' ) !== false, true );

Checks::is( 'get_icon: unknown name is silent', get_icon( 'nope' ), '' );

$rendered = get_icon(
	'arrowForward',
	array(
		'size'  => 20,
		'class' => 'c',
		'attrs' => array( 'aria-hidden' => null ),
	)
);
Checks::is( 'get_icon: applies width', strpos( $rendered, 'width="20" ' ) !== false, true );
Checks::is( 'get_icon: applies class', strpos( $rendered, 'class="c"' ) !== false, true );
Checks::is( 'get_icon: preserves viewBox', strpos( $rendered, 'viewBox="0 0 24 24"' ) !== false, true );
Checks::is( 'get_icon: keeps currentColor fill', strpos( $rendered, 'fill="currentColor"' ) !== false, true );
Checks::is( 'get_icon: null removes aria-hidden', strpos( $rendered, 'aria-hidden' ), false );
