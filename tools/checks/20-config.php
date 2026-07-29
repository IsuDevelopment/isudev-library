<?php
/**
 * Checks for the pure parts of IsuDevLibrary\Config.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/config.php';

use function IsuDevLibrary\Config\decode;
use function IsuDevLibrary\Config\extract_library;
use function IsuDevLibrary\Config\merge_configs;
use function IsuDevLibrary\Config\resolve_block_value;

// decode().
Checks::is( 'decode: valid object', decode( '{"library":{"a":1}}' ), array( 'library' => array( 'a' => 1 ) ) );
Checks::is( 'decode: malformed JSON yields empty array', decode( '{oops' ), array() );
Checks::is( 'decode: empty string yields empty array', decode( '' ), array() );
Checks::is( 'decode: scalar top level yields empty array', decode( '"a string"' ), array() );
Checks::is( 'decode: JSON null yields empty array', decode( 'null' ), array() );

// extract_library() — the shared-file contract: only the `library` key is ours.
$raw = array(
	'library'         => array( 'isudev/site-header' => array( 'enabled' => false ) ),
	'google-reviews'  => array( 'apiKey' => 'ignored' ),
);
Checks::is(
	'extract_library: returns only the library subtree',
	extract_library( $raw ),
	array( 'isudev/site-header' => array( 'enabled' => false ) )
);
Checks::is( 'extract_library: missing key yields empty array', extract_library( array( 'other' => 1 ) ), array() );
Checks::is( 'extract_library: non-array value yields empty array', extract_library( array( 'library' => 'nope' ) ), array() );

// merge_configs() — child theme overrides parent.
$parent = array(
	'isudev/site-header' => array(
		'enabled' => true,
		'sticky'  => true,
	),
);
$child = array(
	'isudev/site-header' => array( 'sticky' => false ),
);
Checks::is(
	'merge_configs: child overrides parent key, keeps siblings',
	merge_configs( $parent, $child ),
	array(
		'isudev/site-header' => array(
			'enabled' => true,
			'sticky'  => false,
		),
	)
);

// resolve_block_value() — variation beats block, block beats fallback.
$config = array(
	'isudev/site-header' => array(
		'sticky'     => true,
		'ariaLabel'  => 'Main',
		// Present at block level, holds null. Pins the sentinel in the block branch.
		'nullish'    => null,
		'variations' => array(
			'compact' => array(
				'sticky' => false,
				// Present at variation level, holds null. Pins the sentinel in the
				// variation branch: it must win over the block value below.
				'winner' => null,
			),
		),
		'winner'     => 'block-value',
	),
);
Checks::is( 'resolve: block-level value', resolve_block_value( $config, 'isudev/site-header', array( 'sticky' ), 'fb' ), true );
Checks::is( 'resolve: variation overrides block', resolve_block_value( $config, 'isudev/site-header', array( 'sticky' ), 'fb', 'compact' ), false );
Checks::is( 'resolve: variation falls back to block value', resolve_block_value( $config, 'isudev/site-header', array( 'ariaLabel' ), 'fb', 'compact' ), 'Main' );
Checks::is( 'resolve: unknown key returns fallback', resolve_block_value( $config, 'isudev/site-header', array( 'nope' ), 'fb' ), 'fb' );
Checks::is( 'resolve: unknown block returns fallback', resolve_block_value( $config, 'isudev/nope', array( 'sticky' ), 'fb' ), 'fb' );
Checks::is( 'resolve: unknown variation falls back to block value', resolve_block_value( $config, 'isudev/site-header', array( 'sticky' ), 'fb', 'ghost' ), true );

// The two checks below are why resolve_block_value() uses a sentinel object
// instead of `??` or a `!== null` test. Without them the sentinel could be
// removed and every other check in this file would still pass.
Checks::is( 'resolve: block key holding null returns null, not the fallback', resolve_block_value( $config, 'isudev/site-header', array( 'nullish' ), 'fb' ), null );
Checks::is( 'resolve: variation key holding null wins over the block value', resolve_block_value( $config, 'isudev/site-header', array( 'winner' ), 'fb', 'compact' ), null );
