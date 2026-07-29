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
);

Checks::is( 'array_get: empty path returns whole array', array_get( $data, array() ), $data );
Checks::is( 'array_get: single segment', array_get( $data, array( 'scalar' ) ), 'not-an-array' );
Checks::is( 'array_get: deep hit', array_get( $data, array( 'library', 'isudev/site-header', 'sticky' ) ), true );
Checks::is( 'array_get: deep hit on false value', array_get( $data, array( 'library', 'isudev/site-header', 'enabled' ) ), false );
Checks::is( 'array_get: nested variation', array_get( $data, array( 'library', 'isudev/site-header', 'variations', 'compact', 'title' ) ), 'Compact' );
Checks::is( 'array_get: missing key returns default', array_get( $data, array( 'library', 'nope' ), 'fallback' ), 'fallback' );
Checks::is( 'array_get: default is null when omitted', array_get( $data, array( 'nope' ) ), null );
Checks::is( 'array_get: traversing through a scalar returns default', array_get( $data, array( 'scalar', 'deeper' ), 'fallback' ), 'fallback' );
Checks::is( 'array_get: non-string segment returns default', array_get( $data, array( 0 ), 'fallback' ), 'fallback' );
