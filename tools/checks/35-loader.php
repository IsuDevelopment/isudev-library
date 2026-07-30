<?php
/**
 * Checks for IsuDevLibrary\Loader::contained_path().
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/class-loader.php';

use IsuDevLibrary\Loader;

$plugin_root = dirname( __DIR__, 2 ) . '/';
$tools_dir   = $plugin_root . 'tools/';

Checks::is(
	'contained_path: a file inside the directory resolves to its real path',
	Loader::contained_path( $tools_dir, 'check.php' ),
	realpath( $tools_dir . 'check.php' )
);
Checks::is(
	'contained_path: a nested file inside the directory resolves',
	Loader::contained_path( $tools_dir, 'checks/10-array.php' ),
	realpath( $tools_dir . 'checks/10-array.php' )
);
Checks::is(
	'contained_path: a missing file returns empty string',
	Loader::contained_path( $tools_dir, 'does-not-exist.php' ),
	''
);

// The point of the function: a mistyped relative path must not silently load a
// file from somewhere else in the plugin.
Checks::is(
	'contained_path: parent traversal is refused even though the file exists',
	Loader::contained_path( $tools_dir, '../composer.json' ),
	''
);
Checks::is(
	'contained_path: traversal buried mid-path is refused',
	Loader::contained_path( $tools_dir, 'checks/../../composer.json' ),
	''
);
Checks::is(
	'contained_path: an absolute-looking path is still resolved under the root',
	Loader::contained_path( $tools_dir, '/check.php' ),
	realpath( $tools_dir . 'check.php' )
);
Checks::is(
	'contained_path: a nonexistent root returns empty string',
	Loader::contained_path( $plugin_root . 'no-such-dir/', 'check.php' ),
	''
);

/*
 * The separator in the prefix comparison is what stops a sibling directory whose
 * name merely starts with the root's name from passing. `site-header` and
 * `site-header-compact` are a plausible pair of block names in this library, so
 * this is worth pinning. Needs a fixture: no such pair exists in the repo, and
 * without it dropping DIRECTORY_SEPARATOR leaves every other check green.
 */
$fixture = \sys_get_temp_dir() . '/isudev-contained-path-check';
$inside  = $fixture . '/site-header';
$sibling = $fixture . '/site-header-evil';

@\mkdir( $inside, 0777, true );   // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Fixture setup; failure surfaces as a failed check below.
@\mkdir( $sibling, 0777, true );  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Fixture setup; failure surfaces as a failed check below.
\file_put_contents( $sibling . '/x.php', "<?php\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Local temp fixture, not a WP filesystem operation.

Checks::is(
	'contained_path: a sibling directory sharing the root name prefix is refused',
	Loader::contained_path( $inside . '/', '../site-header-evil/x.php' ),
	''
);

\unlink( $sibling . '/x.php' );
\rmdir( $sibling );
\rmdir( $inside );
\rmdir( $fixture );
