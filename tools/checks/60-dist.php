<?php
/**
 * Checks that nothing can strip a file the plugin loads at runtime.
 *
 * Blocks are discovered from `src/blocks/<slug>/block.php` and their bootstrap
 * files are required from `src/blocks/<slug>/`, so `src/` is runtime code, not
 * build input. Excluding it produced a zip that registered zero blocks while
 * every unit check, e2e test and linter stayed green. Extensions are discovered
 * from `extensions/<slug>/extension.php` the same way.
 *
 * TWO lists can do that, and both are checked here. `.distignore` is one. The
 * other is the exclude list inlined in .github/workflows/release.yml, which
 * builds the zip attached to a GitHub release — the file the update checker
 * hands to every self-updating site. It cannot simply read `.distignore`,
 * because the zip needs the `vendor/` that `.distignore` drops, so it keeps its
 * own list — and a second list nobody checks is how `src` came to be on it.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

$plugin_root = dirname( __DIR__, 2 ) . '/';

/**
 * Parse a `.distignore` into its effective rules.
 *
 * @param string $path Absolute path to the ignore file.
 * @return array List of raw rule strings, comments and blanks removed.
 */
function isudev_dist_rules( string $path ): array {
	$lines = \is_readable( $path ) ? \file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) : array();
	$lines = \is_array( $lines ) ? $lines : array();
	$rules = array();

	foreach ( $lines as $line ) {
		$line = \trim( $line );

		if ( '' === $line || 0 === \strpos( $line, '#' ) ) {
			continue;
		}

		$rules[] = $line;
	}

	return $rules;
}

/**
 * Decide whether a plugin-relative path is removed by the ignore rules.
 *
 * Deliberately stricter than `wp dist-archive`: it may flag a path the real
 * packer would keep, but it can never miss one the packer would drop. For a
 * guard rail that is the safe direction to be wrong in.
 *
 * @param array  $rules    Rules from isudev_dist_rules().
 * @param string $relative Plugin-relative path, forward slashes, no leading slash.
 * @return bool
 */
function isudev_dist_excluded( array $rules, string $relative ): bool {
	foreach ( $rules as $rule ) {
		$anchored = 0 === \strpos( $rule, '/' );
		$rule     = \trim( $rule, '/' );

		if ( '' === $rule ) {
			continue;
		}

		if ( $relative === $rule || 0 === \strpos( $relative, $rule . '/' ) ) {
			return true;
		}

		if ( \fnmatch( $rule, $relative ) || \fnmatch( $rule . '/*', $relative ) ) {
			return true;
		}

		// An unanchored bare name matches at any depth, as in .gitignore.
		if ( ! $anchored && false === \strpos( $rule, '/' ) && \fnmatch( $rule, \basename( $relative ) ) ) {
			return true;
		}
	}

	return false;
}

$rules = isudev_dist_rules( $plugin_root . '.distignore' );

Checks::true(
	'dist: .distignore is readable and has rules',
	\count( $rules ) > 0
);

/*
 * Derive the runtime file list from the repo rather than hardcoding it, so a
 * block added later is covered without touching this file.
 */
$descriptors = \glob( $plugin_root . 'src/blocks/*/block.php' );
$descriptors = \is_array( $descriptors ) ? $descriptors : array();

Checks::true(
	'dist: at least one block descriptor exists to check',
	\count( $descriptors ) > 0
);

$runtime = array(
	'isudev-library.php',
	'build/blocks-manifest.php',
);

foreach ( $descriptors as $descriptor_file ) {
	$slug      = \basename( \dirname( $descriptor_file ) );
	$runtime[] = 'src/blocks/' . $slug . '/block.php';
	$runtime[] = 'build/blocks/' . $slug . '/block.json';

	$descriptor = require $descriptor_file;
	$bootstrap  = ( \is_array( $descriptor ) && isset( $descriptor['bootstrap'] ) && \is_array( $descriptor['bootstrap'] ) )
		? $descriptor['bootstrap']
		: array();

	foreach ( $bootstrap as $bootstrap_file ) {
		$runtime[] = 'src/blocks/' . $slug . '/' . \ltrim( (string) $bootstrap_file, '/' );
	}
}

/*
 * Extensions are discovered from `extensions/<slug>/extension.php` and their
 * bootstrap files are required from the same directory, so that tree is runtime
 * code too — the same trap `src/` fell into.
 */
$extensions = \glob( $plugin_root . 'extensions/*/extension.php' );
$extensions = \is_array( $extensions ) ? $extensions : array();

Checks::true(
	'dist: at least one extension descriptor exists to check',
	\count( $extensions ) > 0
);

foreach ( $extensions as $extension_file ) {
	$slug      = \basename( \dirname( $extension_file ) );
	$runtime[] = 'extensions/' . $slug . '/extension.php';

	$descriptor = require $extension_file;
	$bootstrap  = ( \is_array( $descriptor ) && isset( $descriptor['bootstrap'] ) && \is_array( $descriptor['bootstrap'] ) )
		? $descriptor['bootstrap']
		: array();

	foreach ( $bootstrap as $bootstrap_file ) {
		$runtime[] = 'extensions/' . $slug . '/' . \ltrim( (string) $bootstrap_file, '/' );
	}
}

$includes = \glob( $plugin_root . 'includes/*.php' );
$includes = \is_array( $includes ) ? $includes : array();

foreach ( $includes as $include_file ) {
	$runtime[] = 'includes/' . \basename( $include_file );
}

foreach ( $runtime as $relative ) {
	Checks::true(
		'dist: runtime file survives .distignore — ' . $relative,
		\file_exists( $plugin_root . $relative ) && ! isudev_dist_excluded( $rules, $relative )
	);
}

/**
 * Extract the rsync exclude list inlined in the release workflow.
 *
 * @param string $path Absolute path to the workflow file.
 * @return array List of raw rule strings.
 */
function isudev_release_rules( string $path ): array {
	$yaml  = \is_readable( $path ) ? (string) \file_get_contents( $path ) : '';
	$rules = array();

	if ( ! \preg_match( "/<<'EOF'\n(.*?)\n\s*EOF\n/s", $yaml, $matches ) ) {
		return $rules;
	}

	foreach ( \explode( "\n", $matches[1] ) as $line ) {
		$line = \trim( $line );

		if ( '' !== $line && 0 !== \strpos( $line, '#' ) ) {
			$rules[] = $line;
		}
	}

	return $rules;
}

$release_rules = isudev_release_rules( $plugin_root . '.github/workflows/release.yml' );

Checks::true(
	'dist: the release workflow exclude list was found',
	\count( $release_rules ) > 0
);

foreach ( $runtime as $relative ) {
	Checks::true(
		'dist: runtime file survives the release zip — ' . $relative,
		! isudev_dist_excluded( $release_rules, $relative )
	);
}

/*
 * The zip needs vendor/ even though .distignore drops it: is_readable() on
 * vendor/autoload.php is how the plugin tells a release-zip install from a
 * Composer-managed one, and only the former enables the update checker.
 */
Checks::true(
	'dist: the release zip keeps vendor/, which enables self-updates',
	! isudev_dist_excluded( $release_rules, 'vendor/autoload.php' )
);

// The rules must still do their job: dev-only trees have to be excluded.
foreach ( array( 'e2e/panel.spec.js', 'tools/check.php', 'node_modules/x/index.js', 'AGENTS.md' ) as $relative ) {
	Checks::true(
		'dist: dev-only file is excluded — ' . $relative,
		isudev_dist_excluded( $rules, $relative )
	);
}
