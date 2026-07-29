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
