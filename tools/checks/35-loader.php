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
