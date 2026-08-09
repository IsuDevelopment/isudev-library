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
use function IsuDevLibrary\Config\is_list_array;
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
// Lists are replaced wholesale so a child theme can RESTRICT one. Under
// array_replace_recursive() these three would merge index-by-index and the
// child could never shorten allowedBlocks or template — the main reason
// isudev.json exists. Each of these fails against array_replace_recursive().
Checks::is(
	'merge_configs: child list replaces the parent list wholesale',
	merge_configs(
		array( 'b' => array( 'allowedBlocks' => array( 'core/paragraph', 'core/image', 'core/button' ) ) ),
		array( 'b' => array( 'allowedBlocks' => array( 'core/paragraph' ) ) )
	),
	array( 'b' => array( 'allowedBlocks' => array( 'core/paragraph' ) ) )
);
Checks::is(
	'merge_configs: child empty list clears the parent list',
	merge_configs(
		array( 'b' => array( 'template' => array( array( 'core/heading' ), array( 'core/paragraph' ) ) ) ),
		array( 'b' => array( 'template' => array() ) )
	),
	array( 'b' => array( 'template' => array() ) )
);
Checks::is(
	'merge_configs: child scalar replaces a parent array',
	merge_configs(
		array( 'b' => array( 'sticky' => array( 'desktop' => true ) ) ),
		array( 'b' => array( 'sticky' => false ) )
	),
	array( 'b' => array( 'sticky' => false ) )
);

// Regression protection for merge paths that are correct today but untested.
// merge_configs() is hand-written logic and Tasks 4-9 build on it.
Checks::is(
	'merge_configs: recurses more than one level deep',
	merge_configs(
		array( 'a' => array( 'b' => array( 'c' => 1, 'd' => 2 ) ) ),
		array( 'a' => array( 'b' => array( 'c' => 3 ) ) )
	),
	array( 'a' => array( 'b' => array( 'c' => 3, 'd' => 2 ) ) )
);
Checks::is(
	'merge_configs: child-only key is added',
	merge_configs( array( 'a' => 1 ), array( 'b' => 2 ) ),
	array( 'a' => 1, 'b' => 2 )
);
Checks::is(
	'merge_configs: child null replaces a parent array',
	merge_configs( array( 'a' => array( 'x' => 1 ) ), array( 'a' => null ) ),
	array( 'a' => null )
);
Checks::is(
	'merge_configs: child assoc replaces a parent list',
	merge_configs( array( 'a' => array( 'x', 'y' ) ), array( 'a' => array( 'k' => 'v' ) ) ),
	array( 'a' => array( 'k' => 'v' ) )
);
Checks::is(
	'merge_configs: child list replaces a parent assoc',
	merge_configs( array( 'a' => array( 'k' => 'v' ) ), array( 'a' => array( 'x', 'y' ) ) ),
	array( 'a' => array( 'x', 'y' ) )
);

// is_list_array() — the predicate the merge depends on.
// The empty-array guard in is_list_array() is load-bearing, not defensive:
// range( 0, -1 ) counts down and yields array( 0, -1 ), so without the guard
// is_list_array( array() ) would return false.
Checks::true( 'is_list_array: empty array is a list', is_list_array( array() ) );
Checks::true( 'is_list_array: sequential from zero is a list', is_list_array( array( 'a', 'b' ) ) );
Checks::is( 'is_list_array: string keys are not a list', is_list_array( array( 'k' => 'v' ) ), false );
Checks::is( 'is_list_array: gap in integer keys is not a list', is_list_array( array( 0 => 'a', 2 => 'b' ) ), false );

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
