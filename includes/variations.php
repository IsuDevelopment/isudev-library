<?php
/**
 * Block variations registered in PHP.
 *
 * Registering in PHP rather than JS keeps variations visible to PHP hooks, so
 * they can be filtered per post type and render.php can read `_namespace`.
 *
 * The build_variations() function is pure — no WP calls.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Variations;

use IsuDevLibrary\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Append configured variations to a block's variation list. Pure.
 *
 * @param array $existing     Variations already registered for the block.
 * @param array $block_config The block's subtree from the `library` config.
 * @return array Combined variation list.
 */
function build_variations( array $existing, array $block_config ): array {
	$configured = $block_config['variations'] ?? null;

	if ( ! \is_array( $configured ) ) {
		return $existing;
	}

	foreach ( $configured as $namespace => $variation ) {
		if ( ! \is_array( $variation ) ) {
			continue;
		}

		if ( ! isset( $variation['name'], $variation['title'] ) ) {
			continue;
		}

		// `settings` is internal to the config file, never exposed to the editor.
		unset( $variation['settings'] );

		if ( ! isset( $variation['attributes'] ) || ! \is_array( $variation['attributes'] ) ) {
			$variation['attributes'] = array();
		}

		$variation['attributes']['_namespace'] = (string) $namespace;

		if ( empty( $variation['isActive'] ) || ! \is_array( $variation['isActive'] ) ) {
			$variation['isActive'] = array( '_namespace' );
		}

		$existing[] = $variation;
	}

	return $existing;
}

/**
 * Wire the variations filter for a single block.
 *
 * @param string $block_name Full block name, e.g. `isudev/site-header`.
 * @return void
 */
function attach( string $block_name ): void {
	\add_filter(
		'get_block_type_variations',
		static function ( $variations, $block_type ) use ( $block_name ) {
			if ( ! \is_array( $variations ) || ! isset( $block_type->name ) || $block_name !== $block_type->name ) {
				return $variations;
			}

			$config = Config\get_config();

			return build_variations( $variations, $config[ $block_name ] ?? array() );
		},
		10,
		2
	);
}
