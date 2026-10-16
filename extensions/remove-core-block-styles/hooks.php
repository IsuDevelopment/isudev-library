<?php
/**
 * Remove core block styles: drop the `styles` core blocks declare in block.json, in the editor only.
 *
 * Editor-only on purpose: core blocks take `styles` from client-side block.json, and clearing
 * them on the server would also drop theme.json `variations` CSS for existing content
 * (WP_Theme_JSON::get_blocks_metadata()). Details in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\RemoveCoreBlockStyles;

use IsuDevLibrary\Extensions;
use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const SLUG          = 'remove-core-block-styles';
const EDITOR_HANDLE = 'isudev-library-remove-core-block-styles';

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor' );
}

/**
 * Normalise a keep list: `core/<block>` keeps every style of a block, `core/<block>:<style>`
 * one style. Anything else is dropped. Pure.
 *
 * @param mixed $keep Raw list.
 * @return array List of unique entries.
 */
function normalize_keep( $keep ): array {
	if ( ! \is_array( $keep ) ) {
		return array();
	}

	$out = array();

	foreach ( $keep as $entry ) {
		if ( \is_string( $entry ) && 1 === \preg_match( '#^core/[a-z0-9-]+(:[a-z0-9-]+)?$#', $entry ) ) {
			$out[] = $entry;
		}
	}

	return \array_values( \array_unique( $out ) );
}

/**
 * Styles that survive for a block. Mirrors editor.js. Pure.
 *
 * @param string $block_name Block name.
 * @param array  $styles     Styles as declared in block.json (each with a `name`).
 * @param array  $keep       Normalised keep list.
 * @return array
 */
function filter_styles( string $block_name, array $styles, array $keep ): array {
	if ( ! \str_starts_with( $block_name, 'core/' ) || \in_array( $block_name, $keep, true ) ) {
		return $styles;
	}

	return \array_values(
		\array_filter(
			$styles,
			static function ( $style ) use ( $block_name, $keep ): bool {
				$name = \is_array( $style ) && \is_string( $style['name'] ?? null ) ? $style['name'] : '';

				return '' !== $name && \in_array( $block_name . ':' . $name, $keep, true );
			}
		)
	);
}

/**
 * The keep list from isudev.json (`keep`), then the filter.
 *
 * @return array
 */
function keep(): array {
	$keep = normalize_keep( Extensions::setting( SLUG, 'keep', array() ) );

	/**
	 * Filters the core block styles that are kept.
	 *
	 * @param array $keep Entries `core/<block>` (all styles of the block) or `core/<block>:<style>`.
	 */
	return normalize_keep( \apply_filters( 'isudev_library/extensions/remove_core_block_styles/keep', $keep ) );
}

/**
 * Editor script and its keep list.
 *
 * @return void
 */
function enqueue_editor(): void {
	\wp_enqueue_script(
		EDITOR_HANDLE,
		\plugins_url( 'editor.js', __FILE__ ),
		array( 'wp-hooks' ),
		VERSION,
		true
	);

	\wp_add_inline_script(
		EDITOR_HANDLE,
		'window.isudevRemoveCoreBlockStyles = ' . \wp_json_encode( array( 'keep' => keep() ) ) . ';',
		'before'
	);
}
