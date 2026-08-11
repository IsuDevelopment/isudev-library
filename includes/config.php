<?php
/**
 * Reader for the optional `isudev.json` theme config.
 *
 * Contract: this plugin reads ONLY the `library` key, ignores everything else in
 * the file, and never writes to it. Other isudev-* plugins own their own keys.
 *
 * Functions above the "WordPress adapters" marker are pure — no WP calls — so
 * they can be exercised by tools/check.php.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Config;

use function IsuDevLibrary\Utils\array_get;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/utils/array.php';

const CONFIG_FILE = 'isudev.json';
const CONFIG_KEY  = 'library';

/**
 * Decode a JSON string into an array. Pure.
 *
 * @param string $json Raw file contents.
 * @return array Decoded array, or [] when the JSON is invalid or not an object.
 */
function decode( string $json ): array {
	if ( '' === \trim( $json ) ) {
		return array();
	}

	$decoded = \json_decode( $json, true );

	if ( \JSON_ERROR_NONE !== \json_last_error() || ! \is_array( $decoded ) ) {
		return array();
	}

	return $decoded;
}

/**
 * Extract this plugin's subtree from a decoded isudev.json. Pure.
 *
 * @param array $raw Full decoded file.
 * @return array The `library` subtree, or [] when absent or malformed.
 */
function extract_library( array $raw ): array {
	$library = $raw[ CONFIG_KEY ] ?? null;

	return \is_array( $library ) ? $library : array();
}

/**
 * Whether an array is a list: keys are sequential integers starting at zero. Pure.
 *
 * Hand-rolled because `array_is_list()` needs PHP 8.1 and this plugin supports 7.4.
 *
 * @param array $value Array to inspect.
 * @return bool True for lists and for the empty array.
 */
function is_list_array( array $value ): bool {
	if ( array() === $value ) {
		return true;
	}

	return \array_keys( $value ) === \range( 0, \count( $value ) - 1 );
}

/**
 * Merge a child theme config over a parent theme config. Pure.
 *
 * Associative arrays merge recursively. Lists are replaced wholesale, so a child
 * theme can shorten one. This is deliberately NOT `array_replace_recursive()`:
 * that merges lists index by index, which makes it impossible for a child theme
 * to restrict `allowedBlocks` or `template` — the very thing isudev.json exists
 * for. Verified: parent `[a, b, c]` with child `[a]` yields `[a, b, c]` under
 * `array_replace_recursive()`.
 *
 * @param array $parent_config Parent theme subtree.
 * @param array $child_config  Child theme subtree.
 * @return array Merged config; child wins.
 */
function merge_configs( array $parent_config, array $child_config ): array {
	$merged = $parent_config;

	foreach ( $child_config as $key => $child_value ) {
		$parent_value = $merged[ $key ] ?? null;

		$both_assoc = \is_array( $child_value )
			&& \is_array( $parent_value )
			&& ! is_list_array( $child_value )
			&& ! is_list_array( $parent_value );

		$merged[ $key ] = $both_assoc
			? merge_configs( $parent_value, $child_value )
			: $child_value;
	}

	return $merged;
}

/**
 * Resolve a config value for a block, honouring variation overrides. Pure.
 *
 * Lookup order: variation value, then block value, then $fallback.
 *
 * @param array  $config              The `library` subtree.
 * @param string $block_name          Full block name, e.g. `isudev/site-header`.
 * @param array  $key_path            Ordered key path below the block (or variation).
 * @param mixed  $fallback            Value returned when nothing resolves.
 * @param string $variation_namespace Variation namespace; '' to skip variation lookup.
 * @return mixed Resolved value.
 */
function resolve_block_value( array $config, string $block_name, array $key_path, $fallback = null, string $variation_namespace = '' ) {
	$sentinel = new \stdClass();

	if ( '' !== $variation_namespace ) {
		$variation_value = array_get(
			$config,
			\array_merge( array( $block_name, 'variations', $variation_namespace ), $key_path ),
			$sentinel
		);

		if ( $sentinel !== $variation_value ) {
			return $variation_value;
		}
	}

	$block_value = array_get( $config, \array_merge( array( $block_name ), $key_path ), $sentinel );

	if ( $sentinel !== $block_value ) {
		return $block_value;
	}

	return $fallback;
}

/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Locate a readable isudev.json in the child or parent theme.
 *
 * @param bool $parent_theme Whether to look in the parent (template) theme.
 * @return string Absolute path, or '' when not readable.
 */
function config_file_path( bool $parent_theme = false ): string {
	$root      = $parent_theme ? \get_template_directory() : \get_stylesheet_directory();
	$candidate = $root . '/' . CONFIG_FILE;

	return \is_readable( $candidate ) ? $candidate : '';
}

/**
 * Paths of the isudev.json files that were found, for diagnostics.
 *
 * @return array{parent:string,child:string} Absolute paths; '' when absent.
 */
function config_sources(): array {
	$is_child = \get_template_directory() !== \get_stylesheet_directory();

	return array(
		'parent' => $is_child ? config_file_path( true ) : '',
		'child'  => config_file_path(),
	);
}

/**
 * Read and decode one isudev.json, logging a parse failure.
 *
 * Purity keeps decode() from logging: it can only answer "empty array".
 * Distinguishing a genuinely empty file from a malformed one has to happen
 * here, or a theme author with a trailing comma gets silence and block
 * defaults.
 *
 * @param string $path Absolute path to a readable isudev.json.
 * @return array Decoded contents, or an empty array on failure.
 */
function read_config_file( string $path ): array {
	$contents = (string) \file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local theme file, not a remote request.
	$decoded  = decode( $contents );

	/*
	 * decode() runs json_decode() last, so the global error state still belongs
	 * to it here. Test that rather than the empty return: a file holding `{}`
	 * decodes to an empty array and is perfectly valid.
	 */
	if ( '' !== \trim( $contents ) && \JSON_ERROR_NONE !== \json_last_error() ) {
		\error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Theme-author diagnostic; the plugin must not fatal on bad JSON.
			\sprintf(
				'IsuDev Library: could not parse %1$s (%2$s). Falling back to block defaults.',
				$path,
				\json_last_error_msg()
			)
		);
	}

	return $decoded;
}

/**
 * Read the merged config from disk, bypassing the per-request cache.
 *
 * Parent theme first, child theme on top. This is the only place that touches
 * the filesystem; get_config() is a caching wrapper around it.
 *
 * @return array The merged `library` subtree.
 */
function get_config_uncached(): array {
	$raw = array();

	/**
	 * Filters whether to inherit isudev.json from the parent theme.
	 *
	 * @param bool $should_inherit Default true.
	 */
	$inherit = (bool) \apply_filters( 'isudev_library/config/inherit_from_parent', true );

	// get_template_directory() vs get_stylesheet_directory() instead of
	// is_child_theme(), which is not reliable this early.
	if ( $inherit && \get_template_directory() !== \get_stylesheet_directory() ) {
		$parent_path = config_file_path( true );
		if ( '' !== $parent_path ) {
			$raw = read_config_file( $parent_path );
		}
	}

	$child_path = config_file_path();
	if ( '' !== $child_path ) {
		$raw = merge_configs( $raw, read_config_file( $child_path ) );
	}

	/**
	 * Filters the whole decoded isudev.json, before this plugin's key is extracted.
	 *
	 * @param array $raw Full decoded file contents.
	 */
	$raw = (array) \apply_filters( 'isudev_library/config/raw', $raw );

	/**
	 * Filters this plugin's `library` subtree.
	 *
	 * @param array $config The `library` subtree.
	 */
	return (array) \apply_filters( 'isudev_library/config', extract_library( $raw ) );
}

/**
 * Read the merged `library` config from the active theme, cached per request.
 *
 * @return array The merged `library` subtree.
 */
function get_config(): array {
	static $config = null;

	if ( null === $config ) {
		$config = get_config_uncached();
	}

	return $config;
}

/**
 * Resolve a config value for a block.
 *
 * @param string       $block_name          Full block name, e.g. `isudev/site-header`.
 * @param string|array $key                 Dot-notation key or ordered key path.
 * @param mixed        $fallback            Value returned when nothing resolves.
 * @param string       $variation_namespace Variation namespace; '' to skip.
 * @return mixed Resolved value.
 */
function get_block_config( string $block_name, $key, $fallback = null, string $variation_namespace = '' ) {
	$key_path = \is_array( $key ) ? $key : \explode( '.', (string) $key );

	return resolve_block_value( get_config(), $block_name, $key_path, $fallback, $variation_namespace );
}

/**
 * Register config editor integration hooks.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_config', 5 );
}

/**
 * Publish the merged `library` config to the block editor as `isudevLibraryConfig`.
 *
 * Unlike `Utils\localize_icons()`, this is a plain assignment, not an append:
 * `isudevLibraryConfig` is this plugin's own name and this is its only writer,
 * whereas `isudevIcons` is shared with the other isudev-* plugins.
 *
 * The whole merged `library` subtree is published, not one block's slice — it
 * is theme configuration, not secret data, and it only reaches users who can
 * already open the block editor.
 *
 * @return void
 */
function enqueue_editor_config(): void {
	$json = \wp_json_encode( get_config() );

	if ( ! \is_string( $json ) ) {
		return;
	}

	// A data-only handle prints localized data in the head before block editor scripts, exactly like enqueue_editor_icons().
	\wp_register_script( 'isudev-library-config', false, array(), \IsuDevLibrary\VERSION, false );
	\wp_enqueue_script( 'isudev-library-config' );
	\wp_add_inline_script( 'isudev-library-config', \sprintf( 'window.isudevLibraryConfig = %s;', $json ) );
}
