<?php
/**
 * Disable fit text: drop the `typography.fitText` block support and neutralise blocks saved with it.
 *
 * Core (block-supports/typography.php) acts on the `fitText` attribute, not on the support, so
 * removing the support alone would leave old blocks scaling. Details in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\DisableFitText;

use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const EDITOR_HANDLE = 'isudev-library-disable-fit-text';
const MODULE_ID     = '@wordpress/block-editor/utils/fit-text-frontend';
const CLASS_NAME    = 'has-fit-text';

/**
 * Directives core's wp_render_typography_support() adds to the block's first tag.
 */
const DIRECTIVES = array(
	'data-wp-context---core-fit-text',
	'data-wp-init---core-fit-text',
	'data-wp-style--font-size',
);

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_filter( 'register_block_type_args', __NAMESPACE__ . '\\disable_support', 10, 1 );

	// Before core's wp_render_typography_support (render_block, 10) reads the attribute.
	\add_filter( 'render_block_data', __NAMESPACE__ . '\\drop_attribute', 5, 1 );
	\add_filter( 'render_block', __NAMESPACE__ . '\\strip_markup', 11, 1 );

	// Block themes print modules in wp_head, classic themes in wp_footer.
	\add_action( 'wp_head', __NAMESPACE__ . '\\dequeue_module', 0 );
	\add_action( 'wp_footer', __NAMESPACE__ . '\\dequeue_module', 0 );

	\add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor' );
	\add_action( 'enqueue_block_assets', __NAMESPACE__ . '\\enqueue_canvas_style' );
}

/**
 * Turn `supports.typography.fitText` off for every block that declares it.
 *
 * @param array $args Block type arguments.
 * @return array
 */
function disable_support( $args ): array {
	$args = \is_array( $args ) ? $args : array();

	if ( isset( $args['supports']['typography'] ) && \is_array( $args['supports']['typography'] ) && \array_key_exists( 'fitText', $args['supports']['typography'] ) ) {
		$args['supports']['typography']['fitText'] = false;
	}

	return $args;
}

/**
 * Remove a saved `fitText` attribute before rendering, so core neither adds its directives nor enqueues the module.
 *
 * @param array $parsed_block Parsed block.
 * @return array
 */
function drop_attribute( $parsed_block ): array {
	$parsed_block = \is_array( $parsed_block ) ? $parsed_block : array();

	if ( isset( $parsed_block['attrs'] ) && \is_array( $parsed_block['attrs'] ) ) {
		unset( $parsed_block['attrs']['fitText'] );
	}

	return $parsed_block;
}

/**
 * Remove the saved `has-fit-text` class (it forces `white-space: nowrap`) and any fit-text
 * directives from the block's first tag. Directives only appear on render paths that skip
 * render_block_data, e.g. a WP_Block rendered directly.
 *
 * @param string $block_content Rendered block.
 * @return string
 */
function strip_markup( $block_content ): string {
	$block_content = (string) $block_content;

	if ( ! \str_contains( $block_content, CLASS_NAME ) && ! \str_contains( $block_content, 'core/fit-text' ) ) {
		return $block_content;
	}

	$processor = new \WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() ) {
		return $block_content;
	}

	$processor->remove_class( CLASS_NAME );

	foreach ( DIRECTIVES as $directive ) {
		$value = $processor->get_attribute( $directive );

		if ( \is_string( $value ) && \str_starts_with( $value, 'core/fit-text::' ) ) {
			$processor->remove_attribute( $directive );
		}
	}

	// Core adds a bare `data-wp-interactive` only when none was there; drop it once nothing else needs it.
	$others = $processor->get_attribute_names_with_prefix( 'data-wp-' );
	$others = \is_array( $others ) ? \array_diff( $others, array( 'data-wp-interactive' ) ) : array();

	if ( true === $processor->get_attribute( 'data-wp-interactive' ) && array() === $others ) {
		$processor->remove_attribute( 'data-wp-interactive' );
	}

	return $processor->get_updated_html();
}

/**
 * Dequeue core's fit-text front-end module. Only fit text uses it.
 *
 * @return void
 */
function dequeue_module(): void {
	\wp_dequeue_script_module( MODULE_ID );
}

/**
 * Editor script: removes the support from client-registered blocks, which is where the control comes from.
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
}

/**
 * Canvas CSS: a block saved with fit text keeps `has-fit-text` as a custom class in the editor;
 * cancel the `white-space: nowrap` core attaches to it.
 *
 * @return void
 */
function enqueue_canvas_style(): void {
	if ( ! \is_admin() ) {
		return;
	}

	\wp_register_style( EDITOR_HANDLE, false, array(), VERSION );
	\wp_add_inline_style( EDITOR_HANDLE, ':root .has-fit-text{white-space:normal!important}' );
	\wp_enqueue_style( EDITOR_HANDLE );
}
