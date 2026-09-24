<?php
/**
 * Button colour property: copies a core/button's picked colour onto its wrapper as a custom property.
 *
 * Core writes the picked colour onto the inner `<a>` as a preset class with `!important`,
 * so CSS can neither cancel the fill nor read the value. A property on the wrapper can be
 * read: a theme uses it for a border, text or an icon. `editor.js` mirrors it in the canvas.
 * Everything else is in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\ButtonColor;

use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const DEFAULT_PROPERTY = '--isudev-button-color';
const EDITOR_HANDLE    = 'isudev-library-button-color';

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// Priority 9: the property is in place before anything else filters the markup.
	\add_filter( 'render_block_core/button', __NAMESPACE__ . '\\add_property', 9, 2 );
	\add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor' );
}

/**
 * The custom property name, filterable; falls back to the default for anything that is not `--name`.
 *
 * @return string
 */
function property(): string {
	/**
	 * Filters the custom property the button colour is written to.
	 *
	 * @param string $property Default `--isudev-button-color`.
	 */
	$property = \apply_filters( 'isudev_library/extensions/button_color/property', DEFAULT_PROPERTY );

	return \is_string( $property ) && 1 === \preg_match( '/^--[a-z0-9-]+$/', $property ) ? $property : DEFAULT_PROPERTY;
}

/**
 * Whether core's Outline style is removed from the editor.
 *
 * @return bool
 */
function removes_core_outline(): bool {
	/**
	 * Filters whether core's "Outline" button style is unregistered in the editor.
	 *
	 * @param bool $remove Default true.
	 */
	return (bool) \apply_filters( 'isudev_library/extensions/button_color/remove_core_outline', true );
}

/**
 * CSS value of the picked colour: a preset stays a preset reference, so the button follows the palette.
 *
 * @param array $attributes Block attributes.
 * @return string Empty when no colour is picked.
 */
function color_value( array $attributes ): string {
	if ( ! empty( $attributes['backgroundColor'] ) && \is_string( $attributes['backgroundColor'] ) ) {
		return \sprintf( 'var(--wp--preset--color--%s)', \sanitize_key( $attributes['backgroundColor'] ) );
	}

	$custom = $attributes['style']['color']['background'] ?? '';

	// A custom colour reaches an inline style; keep only characters a colour value needs.
	if ( \is_string( $custom ) && 1 === \preg_match( '/^[#a-zA-Z0-9(),.%\s|:-]+$/', $custom ) ) {
		return \trim( $custom );
	}

	return '';
}

/**
 * Add the property to the `.wp-block-button` wrapper's style attribute.
 *
 * @param string $content Block markup.
 * @param array  $block   Parsed block.
 * @return string
 */
function add_property( string $content, array $block ): string {
	$color = color_value( (array) ( $block['attrs'] ?? array() ) );

	if ( '' === $color ) {
		return $content;
	}

	$processor = new \WP_HTML_Tag_Processor( $content );
	$property  = property();

	while ( $processor->next_tag( 'div' ) ) {
		if ( true !== $processor->has_class( 'wp-block-button' ) ) {
			continue;
		}

		$style = \trim( (string) $processor->get_attribute( 'style' ) );

		if ( \str_contains( $style, $property . ':' ) ) {
			return $content;
		}

		if ( '' !== $style && ! \str_ends_with( $style, ';' ) ) {
			$style .= ';';
		}

		$processor->set_attribute( 'style', $style . $property . ':' . $color . ';' );

		return $processor->get_updated_html();
	}

	return $content;
}

/**
 * Editor script: the same property on the canvas wrapper, and core's Outline style removed.
 *
 * @return void
 */
function enqueue_editor(): void {
	\wp_enqueue_script(
		EDITOR_HANDLE,
		\plugins_url( 'editor.js', __FILE__ ),
		array( 'wp-blocks', 'wp-dom-ready', 'wp-hooks' ),
		VERSION,
		true
	);

	\wp_add_inline_script(
		EDITOR_HANDLE,
		'window.isudevButtonColor = ' . \wp_json_encode(
			array(
				'property'          => property(),
				'removeCoreOutline' => removes_core_outline(),
			)
		) . ';',
		'before'
	);
}
