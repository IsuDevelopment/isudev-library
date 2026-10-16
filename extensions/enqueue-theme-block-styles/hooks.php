<?php
/**
 * Enqueue theme block styles: `<theme>/<dir>/<namespace>/<block>.css` → wp_enqueue_block_style().
 *
 * Port of T2's enqueue-theme-block-styles. Runs once at init 0 (Extensions::load()), after
 * after_setup_theme. Details in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\EnqueueThemeBlockStyles;

use IsuDevLibrary\Extensions;

defined( 'ABSPATH' ) || exit;

const SLUG           = 'enqueue-theme-block-styles';
const DEFAULT_DIR    = 'build/blocks';
const DEFAULT_PREFIX = 'isudev-theme-block-styles-';

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	$dir = sanitize_dir( Extensions::setting( SLUG, 'dir', DEFAULT_DIR ) );

	// Parent first, so the child's stylesheet for the same block prints after it.
	if ( \is_child_theme() ) {
		enqueue_from( \get_template_directory(), \get_template_directory_uri(), $dir, true );
	}

	enqueue_from( \get_stylesheet_directory(), \get_stylesheet_directory_uri(), $dir, false );
}

/**
 * A theme-relative directory: no leading or trailing slash, no `..`. Pure.
 *
 * @param mixed $dir Raw setting.
 * @return string
 */
function sanitize_dir( $dir ): string {
	if ( ! \is_string( $dir ) ) {
		return DEFAULT_DIR;
	}

	$dir = \trim( \str_replace( '\\', '/', $dir ), "/ \t\n\r\0\x0B" );

	if ( '' === $dir || 1 !== \preg_match( '#^[A-Za-z0-9_\-./]+$#', $dir ) || \in_array( '..', \explode( '/', $dir ), true ) ) {
		return DEFAULT_DIR;
	}

	return $dir;
}

/**
 * Block stylesheets under a directory: `<namespace>/<block>.css`, RTL variants skipped
 * (core derives those from `path`). Pure apart from the filesystem; cached per request.
 *
 * @param string $base Absolute directory, without trailing slash.
 * @return array List of array{namespace:string,block:string,file:string}, file relative to $base.
 */
function discover( string $base ): array {
	static $cache = array();

	if ( isset( $cache[ $base ] ) ) {
		return $cache[ $base ];
	}

	$found = array();
	$dirs  = \is_dir( $base ) ? \glob( $base . '/*', GLOB_ONLYDIR ) : array();

	foreach ( \is_array( $dirs ) ? $dirs : array() as $dir ) {
		$namespace = \basename( $dir );

		if ( ! is_slug( $namespace ) ) {
			continue;
		}

		$files = \glob( $dir . '/*.css' );

		foreach ( \is_array( $files ) ? $files : array() as $file ) {
			$block = \basename( $file, '.css' );

			if ( \str_ends_with( $block, '-rtl' ) || ! is_slug( $block ) || ! \is_file( $file ) ) {
				continue;
			}

			$found[] = array(
				'namespace' => $namespace,
				'block'     => $block,
				'file'      => $namespace . '/' . $block . '.css',
			);
		}
	}

	\usort(
		$found,
		static fn( array $a, array $b ): int => \strcmp( $a['file'], $b['file'] )
	);

	$cache[ $base ] = $found;

	return $found;
}

/**
 * Whether a string can be half of a block name. Pure.
 *
 * @param string $value Candidate.
 * @return bool
 */
function is_slug( string $value ): bool {
	return 1 === \preg_match( '/^[a-z0-9][a-z0-9-]*$/', $value );
}

/**
 * Handle for one stylesheet. Pure apart from the prefix filter.
 *
 * @param string $block_namespace Block namespace.
 * @param string $block           Block slug.
 * @param bool   $is_parent       From the parent theme.
 * @return string
 */
function handle( string $block_namespace, string $block, bool $is_parent ): string {
	/**
	 * Filters the handle prefix. Set `t2-theme-block-styles-` to keep T2's handles.
	 *
	 * @param string $prefix Default `isudev-theme-block-styles-`.
	 */
	$prefix = \apply_filters( 'isudev_library/extensions/enqueue_theme_block_styles/handle_prefix', DEFAULT_PREFIX );
	$prefix = \is_string( $prefix ) && 1 === \preg_match( '/^[a-z0-9_-]+$/', $prefix ) ? $prefix : DEFAULT_PREFIX;

	return $prefix . $block_namespace . '-' . $block . ( $is_parent ? '-parent' : '' );
}

/**
 * Stylesheet dependencies for a block, filterable.
 *
 * @param string $block_name Block name.
 * @return array
 */
function deps( string $block_name ): array {
	/**
	 * Filters the dependencies of a theme block stylesheet.
	 *
	 * @param array  $deps       Style handles. Default none.
	 * @param string $block_name Block name, e.g. `core/button`.
	 */
	$deps = \apply_filters( 'isudev_library/extensions/enqueue_theme_block_styles/deps', array(), $block_name );

	return \is_array( $deps ) ? \array_values( \array_filter( $deps, 'is_string' ) ) : array();
}

/**
 * Enqueue every block stylesheet of one theme.
 *
 * @param string $theme_dir Theme directory.
 * @param string $theme_uri Theme URI.
 * @param string $dir       Directory relative to the theme.
 * @param bool   $is_parent Whether this is the parent theme.
 * @return void
 */
function enqueue_from( string $theme_dir, string $theme_uri, string $dir, bool $is_parent ): void {
	$base = \untrailingslashit( $theme_dir ) . '/' . $dir;
	$url  = \untrailingslashit( $theme_uri ) . '/' . $dir;

	foreach ( discover( $base ) as $style ) {
		$path       = $base . '/' . $style['file'];
		$block_name = $style['namespace'] . '/' . $style['block'];
		$mtime      = \filemtime( $path );

		\wp_enqueue_block_style(
			$block_name,
			array(
				'handle' => handle( $style['namespace'], $style['block'], $is_parent ),
				'src'    => $url . '/' . $style['file'],
				'deps'   => deps( $block_name ),
				'ver'    => false !== $mtime ? (string) $mtime : false,
				// No `path` under WP_DEBUG: inlined styles break BrowserSync CSS injection.
				'path'   => \defined( 'WP_DEBUG' ) && WP_DEBUG ? null : $path,
			)
		);
	}
}
