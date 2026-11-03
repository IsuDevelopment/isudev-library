<?php
/**
 * Make this plugin's agent guide discoverable in a consuming project.
 *
 * Usage, from the project root (or pass it as the first argument):
 *   php path/to/isudev-library/bin/agents-init.php [project-root]
 *   php path/to/isudev-library/bin/agents-init.php [project-root] --check
 *
 * Writes `.agents/vendor/isudev-library.md` (agents/README.md with links
 * pointing at the installed plugin) and upserts a marked block in AGENTS.md,
 * plus `.github/copilot-instructions.md` and `.cursor/rules/isudev-library.mdc`
 * when those directories already exist. Same contract as
 * `npx @isudev/gutenberg init`. Plain PHP: no WordPress, no database.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI tool, no WordPress loaded.

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

const ISUDEV_LIBRARY_SLUG  = 'isudev-library';
const ISUDEV_LIBRARY_BEGIN = '<!-- isudev-library:begin -->';
const ISUDEV_LIBRARY_END   = '<!-- isudev-library:end -->';

/**
 * Relative path from one directory to another, with forward slashes.
 *
 * @param string $from Absolute directory.
 * @param string $to   Absolute directory.
 * @return string
 */
function isudev_library_relative( string $from, string $to ): string {
	$from = explode( '/', trim( str_replace( '\\', '/', $from ), '/' ) );
	$to   = explode( '/', trim( str_replace( '\\', '/', $to ), '/' ) );

	while ( $from && $to && $from[0] === $to[0] ) {
		array_shift( $from );
		array_shift( $to );
	}

	return implode( '/', array_merge( array_fill( 0, count( $from ), '..' ), $to ) );
}

/**
 * Absolute path without resolving symlinks, so a symlinked plugin keeps the
 * path the project sees.
 *
 * @param string $path Path, absolute or relative to the working directory.
 * @return string
 */
function isudev_library_absolute( string $path ): string {
	$path  = str_replace( '\\', '/', $path );
	$path  = '/' === substr( $path, 0, 1 ) ? $path : str_replace( '\\', '/', (string) getcwd() ) . '/' . $path;
	$parts = array();

	foreach ( explode( '/', $path ) as $part ) {
		if ( '' === $part || '.' === $part ) {
			continue;
		}
		if ( '..' === $part ) {
			array_pop( $parts );
			continue;
		}
		$parts[] = $part;
	}

	return '/' . implode( '/', $parts );
}

/**
 * Replace the managed block, or append one.
 *
 * @param string $source Existing file contents.
 * @param string $block  Block body.
 * @return string
 */
function isudev_library_upsert( string $source, string $block ): string {
	$managed = ISUDEV_LIBRARY_BEGIN . "\n" . $block . "\n" . ISUDEV_LIBRARY_END;
	$start   = strpos( $source, ISUDEV_LIBRARY_BEGIN );
	$end     = strpos( $source, ISUDEV_LIBRARY_END );

	if ( false !== $start && false !== $end && $end > $start ) {
		return substr( $source, 0, $start ) . $managed . substr( $source, $end + strlen( ISUDEV_LIBRARY_END ) );
	}

	return rtrim( $source ) . ( '' === trim( $source ) ? '' : "\n\n" ) . $managed . "\n";
}

/**
 * Write a file, creating its directory.
 *
 * @param string $file     Absolute path.
 * @param string $contents Contents.
 * @return void
 */
function isudev_library_write( string $file, string $contents ): void {
	if ( ! is_dir( dirname( $file ) ) ) {
		mkdir( dirname( $file ), 0777, true );
	}
	file_put_contents( $file, $contents );
}

$isudev_library_args    = array_slice( $argv, 1 );
$isudev_library_check   = in_array( '--check', $isudev_library_args, true );
$isudev_library_paths   = array_values( array_filter( $isudev_library_args, static fn( $arg ) => '--' !== substr( $arg, 0, 2 ) ) );
$isudev_library_project = isudev_library_absolute( $isudev_library_paths[0] ?? (string) getcwd() );
$isudev_library_plugin  = dirname( isudev_library_absolute( $argv[0] ), 2 );

if ( ! is_dir( $isudev_library_project ) ) {
	fwrite( STDERR, "Project directory not found.\n" );
	exit( 1 );
}

preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', (string) file_get_contents( $isudev_library_plugin . '/isudev-library.php' ), $isudev_library_match );
$isudev_library_version = $isudev_library_match[1] ?? 'unknown';
$isudev_library_rel     = isudev_library_relative( $isudev_library_project, $isudev_library_plugin );
$isudev_library_vendor  = '.agents/vendor/' . ISUDEV_LIBRARY_SLUG . '.md';
$isudev_library_command = 'php ' . $isudev_library_rel . '/bin/agents-init.php';

// Links in agents/README.md are relative to agents/; from .agents/vendor/ they must reach the plugin.
$isudev_library_guide    = (string) file_get_contents( $isudev_library_plugin . '/agents/README.md' );
$isudev_library_guide    = (string) preg_replace_callback(
	'/\]\(\.\.\/([^)]+)\)/',
	static function ( array $link ) use ( $isudev_library_rel ): string {
		$target = '../../' . $isudev_library_rel . '/' . $link[1];
		return '](' . ( false === strpos( $target, ' ' ) ? $target : '<' . $target . '>' ) . ')';
	},
	$isudev_library_guide
);
$isudev_library_expected = implode(
	"\n",
	array(
		'<!-- Vendored from ' . ISUDEV_LIBRARY_SLUG . '@' . $isudev_library_version . ' (' . $isudev_library_rel . '). Do not edit.',
		'     Refresh with `' . $isudev_library_command . '` after updating the plugin. -->',
		'',
		$isudev_library_guide,
	)
);

$isudev_library_pointer = implode(
	"\n",
	array(
		'## IsuDev Library (WordPress plugin)',
		'',
		'This project uses the `isudev-library` plugin for blocks, card templates and site',
		'tweaks. Before changing anything that touches its blocks, `isudev.json`, card',
		'templates (`isudev/cards/`, `parts/card-*.html`) or its `isudev_library/*` hooks, read',
		'[`' . $isudev_library_vendor . '`](./' . $isudev_library_vendor . ') — rules, every block, extension and',
		'hook, and which README or guide explains it. Never edit the plugin itself.',
		'',
		'Vendored from ' . ISUDEV_LIBRARY_SLUG . '@' . $isudev_library_version . '. Refresh with `' . $isudev_library_command . '`.',
	)
);

$isudev_library_vendor_file = $isudev_library_project . '/' . $isudev_library_vendor;

if ( $isudev_library_check ) {
	if ( ! is_readable( $isudev_library_vendor_file ) || file_get_contents( $isudev_library_vendor_file ) !== $isudev_library_expected ) {
		fwrite( STDERR, $isudev_library_vendor . ' is missing or out of date with ' . ISUDEV_LIBRARY_SLUG . '@' . $isudev_library_version . '. Run `' . $isudev_library_command . "` and commit the result.\n" );
		exit( 1 );
	}

	echo $isudev_library_vendor . ' matches ' . ISUDEV_LIBRARY_SLUG . '@' . $isudev_library_version . ".\n";
	exit( 0 );
}

$isudev_library_written = array();

isudev_library_write( $isudev_library_vendor_file, $isudev_library_expected );
$isudev_library_written[] = $isudev_library_vendor;

$isudev_library_agents = $isudev_library_project . '/AGENTS.md';
isudev_library_write( $isudev_library_agents, isudev_library_upsert( is_readable( $isudev_library_agents ) ? (string) file_get_contents( $isudev_library_agents ) : '', $isudev_library_pointer ) );
$isudev_library_written[] = 'AGENTS.md';

// Tool-specific files only when the project already uses that tool.
if ( is_dir( $isudev_library_project . '/.github' ) ) {
	$isudev_library_copilot = $isudev_library_project . '/.github/copilot-instructions.md';
	isudev_library_write( $isudev_library_copilot, isudev_library_upsert( is_readable( $isudev_library_copilot ) ? (string) file_get_contents( $isudev_library_copilot ) : '', $isudev_library_pointer ) );
	$isudev_library_written[] = '.github/copilot-instructions.md';
}

if ( is_dir( $isudev_library_project . '/.cursor/rules' ) ) {
	isudev_library_write(
		$isudev_library_project . '/.cursor/rules/' . ISUDEV_LIBRARY_SLUG . '.mdc',
		"---\ndescription: How to use the isudev-library WordPress plugin: blocks, card templates, isudev.json and hooks.\nalwaysApply: false\n---\n\n" . $isudev_library_pointer . "\n"
	);
	$isudev_library_written[] = '.cursor/rules/' . ISUDEV_LIBRARY_SLUG . '.mdc';
}

echo ISUDEV_LIBRARY_SLUG . '@' . $isudev_library_version . " — wrote:\n  " . implode( "\n  ", $isudev_library_written ) . "\n";
