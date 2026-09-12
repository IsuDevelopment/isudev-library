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
	'uninstall.php',
	// Linked from render.php through IsuDevLibrary\URL, so these ship or the
	// Google Reviews blocks render a broken image.
	'assets/google-logo.svg',
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

/*
 * The release workflow builds the zip that the update checker hands to every
 * self-updating site. It used to carry its own inline exclude list, which is how
 * `src` — runtime code — came to be excluded from it while `.distignore` stayed
 * correct and checked. These assert the two lists stayed merged into one.
 */
$workflow = $plugin_root . '.github/workflows/release.yml';
$yaml     = \is_readable( $workflow ) ? (string) \file_get_contents( $workflow ) : '';

Checks::true( 'dist: the release workflow is readable', '' !== $yaml );

Checks::true(
	'dist: the release zip is packed from .distignore, not a second list',
	false !== \strpos( $yaml, '--exclude-from=.distignore' )
);

Checks::true(
	'dist: the release workflow carries no second inline rsync exclude list',
	false === \strpos( $yaml, '--exclude-from=-' )
);

/*
 * The one documented exception to that single list: the zip needs vendor/, which
 * .distignore drops. is_readable( vendor/autoload.php ) is how the plugin tells a
 * release-zip install from a Composer-managed one, and only the former enables
 * the update checker — without this the published plugin cannot self-update.
 *
 * Its position matters as much as its presence: rsync takes the first matching
 * rule, so the include has to precede --exclude-from, which is also what shields
 * files inside vendor/ from .distignore's unanchored root-file patterns.
 */
$include_at = \strpos( $yaml, "--include='/vendor/***'" );
$exclude_at = \strpos( $yaml, '--exclude-from=.distignore' );

Checks::true(
	'dist: the release zip re-includes vendor/, which enables self-updates',
	false !== $include_at
);

Checks::true(
	'dist: the vendor/ include precedes --exclude-from, so it wins',
	false !== $include_at && false !== $exclude_at && $include_at < $exclude_at
);

// The rules must still do their job: dev-only trees have to be excluded.
$dev_only = array(
	'e2e/panel.spec.js',
	'tools/check.php',
	'node_modules/x/index.js',
	'AGENTS.md',
	'CLAUDE.md',
	'.agents/vendor/isudev-gutenberg.md',
	'.claude/settings.json',
	'docs/superpowers/specs/x.md',
	'guides/bento-grid.md',
	'composer.json',
	'package.json',
	'webpack.config.js',
	'playwright.config.js',
);

foreach ( $dev_only as $relative ) {
	Checks::true(
		'dist: dev-only file is excluded — ' . $relative,
		isudev_dist_excluded( $rules, $relative )
	);
}
