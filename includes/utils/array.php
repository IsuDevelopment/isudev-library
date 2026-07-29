<?php
/**
 * Array helpers. Pure functions — no WordPress calls.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Read a value from a nested array by path.
 *
 * Replacement for the private core function `_wp_array_get()`.
 *
 * @param array $data    Source array.
 * @param array $path    Ordered list of keys to walk.
 * @param mixed $fallback Value returned when the path does not resolve.
 * @return mixed Resolved value, or $fallback.
 */
function array_get( array $data, array $path, $fallback = null ) {
	$current = $data;

	foreach ( $path as $segment ) {
		if ( ! \is_string( $segment ) && ! \is_int( $segment ) ) {
			return $fallback;
		}

		if ( ! \is_array( $current ) || ! \array_key_exists( $segment, $current ) ) {
			return $fallback;
		}

		$current = $current[ $segment ];
	}

	return $current;
}
