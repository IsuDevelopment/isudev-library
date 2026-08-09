<?php
/**
 * Checks for IsuDevLibrary\Utils\array_get().
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/utils/array.php';

use function IsuDevLibrary\Utils\array_get;

$data = array(
	'library' => array(
		'isudev/site-header' => array(
			'enabled'    => false,
			'sticky'     => true,
			'variations' => array(
				'compact' => array( 'title' => 'Compact' ),
			),
		),
	),
	'scalar'  => 'not-an-array',
	// Present, but holds null. Pins array_key_exists() vs isset(): isset() is the
	// only case where a stored null is indistinguishable from a missing key.
	'nullish' => null,
);

Checks::is( 'array_get: empty path returns whole array', array_get( $data, array() ), $data );
Checks::is( 'array_get: single segment', array_get( $data, array( 'scalar' ) ), 'not-an-array' );
Checks::is( 'array_get: deep hit', array_get( $data, array( 'library', 'isudev/site-header', 'sticky' ) ), true );
Checks::is( 'array_get: deep hit on false value', array_get( $data, array( 'library', 'isudev/site-header', 'enabled' ) ), false );
Checks::is( 'array_get: nested variation', array_get( $data, array( 'library', 'isudev/site-header', 'variations', 'compact', 'title' ) ), 'Compact' );
Checks::is( 'array_get: missing key returns default', array_get( $data, array( 'library', 'nope' ), 'fallback' ), 'fallback' );
Checks::is( 'array_get: default is null when omitted', array_get( $data, array( 'nope' ) ), null );
Checks::is( 'array_get: traversing through a scalar returns fallback', array_get( $data, array( 'scalar', 'deeper' ), 'fallback' ), 'fallback' );
Checks::is( 'array_get: absent integer key returns fallback', array_get( $data, array( 0 ), 'fallback' ), 'fallback' );

// A key that EXISTS but holds null must return null, not the fallback. This is
// the whole reason the implementation uses array_key_exists() and not isset():
// swapping in isset() would still pass every other check in this file.
Checks::is( 'array_get: existing key holding null returns null, not fallback', array_get( $data, array( 'nullish' ), 'fallback' ), null );

// Exercises the segment type guard for real. An array is neither string nor int,
// so it must return the fallback rather than raising a PHP 8 TypeError inside
// array_key_exists(). Without this, the guard could be deleted and stay green.
Checks::is( 'array_get: array as a path segment returns fallback', array_get( $data, array( array( 'nope' ) ), 'fallback' ), 'fallback' );
