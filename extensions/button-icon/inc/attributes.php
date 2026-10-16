<?php
/**
 * Button icon: pure attribute and settings sanitising. No WordPress calls, so
 * tools/checks/240-button-icon.php can exercise it. `src/editor.js` mirrors
 * resolve() and must change with it.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\ButtonIcon;

defined( 'ABSPATH' ) || exit;

const MIN_SIZE         = 8;
const MAX_SIZE         = 128;
const DEFAULT_POSITION = 'after';

/**
 * An icon name from the registry, or '' for anything that cannot be one.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function sanitize_icon_name( $value ): string {
	if ( ! \is_string( $value ) ) {
		return '';
	}

	$value = \trim( $value );

	// Registry names are camelCase or dashed; T2 core-library names carry a `collection/` prefix.
	return 1 === \preg_match( '#^[A-Za-z0-9][A-Za-z0-9_/-]{0,63}$#', $value ) ? $value : '';
}

/**
 * `before` or `after`; T2's `left`/`right` map onto them. Null when unrecognised.
 *
 * @param mixed $value Raw value.
 * @return string|null
 */
function sanitize_position( $value ): ?string {
	$map = array(
		'before' => 'before',
		'after'  => 'after',
		'left'   => 'before',
		'right'  => 'after',
	);

	return \is_string( $value ) && isset( $map[ $value ] ) ? $map[ $value ] : null;
}

/**
 * Icon size in px, clamped to MIN_SIZE..MAX_SIZE. Null when not a number.
 *
 * @param mixed $value Raw value.
 * @return int|null
 */
function sanitize_size( $value ): ?int {
	if ( \is_string( $value ) ) {
		$value = \trim( $value );
		$value = \is_numeric( $value ) ? (float) $value : null;
	}

	if ( \is_float( $value ) ) {
		$value = \is_finite( $value ) ? (int) \round( $value ) : null;
	}

	if ( ! \is_int( $value ) ) {
		return null;
	}

	return \max( MIN_SIZE, \min( MAX_SIZE, $value ) );
}

/**
 * Strict boolean: only true, 1, '1' and 'true' count.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function sanitize_bool( $value ): bool {
	return true === $value || 1 === $value || '1' === $value || 'true' === $value;
}

/**
 * Normalise the theme settings from isudev.json and the PHP filter.
 *
 * @param mixed $icons    Allowed icon names; anything but a non-empty list means all.
 * @param mixed $position Default position.
 * @param mixed $size     Default size; null keeps the stylesheet default (1.25em).
 * @param mixed $read_t2  Whether T2's attribute names are read as a fallback.
 * @return array{icons: string[]|null, defaultPosition: string, defaultSize: int|null, readT2: bool}
 */
function normalize_settings( $icons, $position, $size, $read_t2 ): array {
	$allowed = null;

	if ( \is_array( $icons ) ) {
		$names   = \array_values( \array_unique( \array_filter( \array_map( __NAMESPACE__ . '\\sanitize_icon_name', $icons ) ) ) );
		$allowed = array() === $names ? null : $names;
	}

	return array(
		'icons'           => $allowed,
		'defaultPosition' => sanitize_position( $position ) ?? DEFAULT_POSITION,
		'defaultSize'     => sanitize_size( $size ),
		'readT2'          => false !== $read_t2,
	);
}

/**
 * Whether the block carries a value under this key (null counts as unset).
 *
 * @param array  $attrs Block attributes.
 * @param string $key   Attribute name.
 * @return bool
 */
function has_attr( array $attrs, string $key ): bool {
	return \array_key_exists( $key, $attrs ) && null !== $attrs[ $key ];
}

/**
 * Resolve what a button shows from its attributes.
 *
 * Own `isudev*` attributes win; with `readT2` T2's `icon`, `iconPosition`,
 * `iconSize` and `hideText` fill the gaps; settings defaults apply last.
 *
 * @param array $attrs    Raw parsed block attributes.
 * @param array $settings Output of normalize_settings().
 * @return array{icon: string, position: string, size: int|null, iconOnly: bool}
 */
function resolve( array $attrs, array $settings ): array {
	$legacy = ! empty( $settings['readT2'] );
	$pick   = static function ( string $own, string $t2 ) use ( $attrs, $legacy ) {
		if ( has_attr( $attrs, $own ) ) {
			return $attrs[ $own ];
		}

		return $legacy && has_attr( $attrs, $t2 ) ? $attrs[ $t2 ] : null;
	};

	$position = sanitize_position( $pick( 'isudevIconPosition', 'iconPosition' ) );
	$size     = sanitize_size( $pick( 'isudevIconSize', 'iconSize' ) );

	return array(
		'icon'     => sanitize_icon_name( $pick( 'isudevIcon', 'icon' ) ),
		'position' => $position ?? ( sanitize_position( $settings['defaultPosition'] ?? null ) ?? DEFAULT_POSITION ),
		'size'     => $size ?? sanitize_size( $settings['defaultSize'] ?? null ),
		'iconOnly' => sanitize_bool( $pick( 'isudevIconOnly', 'hideText' ) ),
	);
}

/**
 * Classes for the `.wp-block-button` wrapper. Same list in the editor canvas.
 *
 * @param array $resolved Output of resolve().
 * @return string[]
 */
function wrapper_classes( array $resolved ): array {
	$classes = array( 'has-isudev-icon', 'has-isudev-icon-' . $resolved['position'] );

	if ( $resolved['iconOnly'] ) {
		$classes[] = 'is-isudev-icon-only';
	}

	return $classes;
}

/**
 * Markup inserted after the link's opening tag and before its closing tag.
 *
 * @param string $icon     Rendered icon markup.
 * @param array  $resolved Output of resolve().
 * @return array{0: string, 1: string} Opening insert, closing insert.
 */
function inserts( string $icon, array $resolved ): array {
	if ( $resolved['iconOnly'] ) {
		return array( $icon . '<span class="isudev-visually-hidden">', '</span>' );
	}

	return 'before' === $resolved['position'] ? array( $icon, '' ) : array( '', $icon );
}

/**
 * Append the size property to an existing inline style, or '' when there is no size.
 *
 * @param string   $style Existing style attribute value.
 * @param int|null $size  Resolved size.
 * @return string Updated style, or '' when unchanged.
 */
function size_style( string $style, ?int $size ): string {
	if ( null === $size ) {
		return '';
	}

	$style = \trim( $style );

	if ( '' !== $style && ! \str_ends_with( $style, ';' ) ) {
		$style .= ';';
	}

	return $style . '--isudev-button-icon-size:' . $size . 'px;';
}
