<?php
/**
 * Button icon: an icon from the shared registry inside core/button.
 *
 * Attributes are registered on both sides; the front end inserts the SVG into the
 * link with an HTML API subclass (the pattern core uses in script-loader.php);
 * the editor previews it as a CSS mask on the canvas wrapper, without touching
 * the DOM. Everything else is in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\ButtonIcon;

use IsuDevLibrary\Extensions;
use function IsuDevLibrary\Utils\get_icon;
use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const SLUG         = 'button-icon';
const STYLE_HANDLE = 'isudev-library-button-icon';

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_filter( 'register_block_type_args', __NAMESPACE__ . '\\register_attributes', 10, 2 );
	\add_filter( 'render_block_core/button', __NAMESPACE__ . '\\render', 10, 2 );
	\add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor' );

	// Front end (only where a button renders) and the iframed editor canvas.
	\wp_enqueue_block_style(
		'core/button',
		array(
			'handle' => STYLE_HANDLE,
			'src'    => \plugins_url( 'button-icon.css', __FILE__ ),
			'path'   => __DIR__ . '/button-icon.css',
			'ver'    => VERSION,
		)
	);
}

/**
 * Theme settings, sanitised once per request.
 *
 * @return array See normalize_settings().
 */
function settings(): array {
	static $settings = null;

	if ( null === $settings ) {
		/**
		 * Filters whether T2's button-icon attributes (`icon`, `iconPosition`,
		 * `iconSize`, `hideText`) are read when a button has none of its own.
		 *
		 * @param bool $read Default true.
		 */
		$read_t2 = \apply_filters( 'isudev_library/extensions/button_icon/read_t2_attributes', true );

		$settings = normalize_settings(
			Extensions::setting( SLUG, 'icons', null ),
			Extensions::setting( SLUG, 'defaultPosition', DEFAULT_POSITION ),
			Extensions::setting( SLUG, 'defaultSize', null ),
			(bool) $read_t2
		);
	}

	return $settings;
}

/**
 * Register the icon attributes on core/button, so REST and server rendering know them.
 *
 * No defaults on purpose: an unset attribute means "use the theme default".
 *
 * @param array  $args       Block type arguments.
 * @param string $block_type Block name.
 * @return array
 */
function register_attributes( $args, $block_type ): array {
	$args = \is_array( $args ) ? $args : array();

	if ( 'core/button' !== $block_type ) {
		return $args;
	}

	$args['attributes'] = \array_merge(
		\is_array( $args['attributes'] ?? null ) ? $args['attributes'] : array(),
		array(
			'isudevIcon'         => array( 'type' => 'string' ),
			'isudevIconPosition' => array(
				'type' => 'string',
				'enum' => array( 'before', 'after' ),
			),
			'isudevIconSize'     => array(
				'type'    => 'integer',
				'minimum' => MIN_SIZE,
				'maximum' => MAX_SIZE,
			),
			'isudevIconOnly'     => array( 'type' => 'boolean' ),
		)
	);

	return $args;
}

/**
 * Insert the icon into the button link and mark the wrapper.
 *
 * @param string $block_content Rendered block.
 * @param array  $block         Parsed block.
 * @return string
 */
function render( $block_content, $block ): string {
	$content = \is_string( $block_content ) ? $block_content : '';

	// Core renders '' for a button without text.
	if ( '' === $content || ! \is_array( $block ) ) {
		return $content;
	}

	$resolved = resolve( \is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array(), settings() );

	if ( '' === $resolved['icon'] ) {
		return $content;
	}

	$icon = get_icon(
		$resolved['icon'],
		array(
			'size'  => $resolved['size'] ?? \IsuDevLibrary\Utils\DEFAULT_ICON_SIZE,
			'class' => 'isudev-button-icon',
		)
	);

	return '' === $icon ? $content : insert_icon( $content, $icon, $resolved );
}

/**
 * Put the icon markup inside the first `<a>`/`<button>` of `.wp-block-button`.
 *
 * The HTML API has no public insert; like core (`wp_print_late_styles` buffer,
 * `_block_template_add_skip_link`) a subclass turns a bookmark span into a text
 * replacement. Offsets come from the tokenizer, so attribute values containing
 * `>` and inline markup in the text cannot mislead it.
 *
 * @param string $content  Rendered button markup.
 * @param string $icon     Trusted registry markup.
 * @param array  $resolved Output of resolve().
 * @return string Unchanged when the markup is not core's button shape.
 */
function insert_icon( string $content, string $icon, array $resolved ): string {
	$processor = new class( $content ) extends \WP_HTML_Tag_Processor {
		/**
		 * Insert HTML before (or after) the current token.
		 *
		 * @param string $html  Markup.
		 * @param bool   $after Insert after the token instead.
		 * @return void
		 */
		public function isudev_insert( string $html, bool $after ): void {
			if ( '' === $html || ! $this->set_bookmark( 'isudev-button-icon' ) ) {
				return;
			}

			$span                    = $this->bookmarks['isudev-button-icon'];
			$this->lexical_updates[] = new \WP_HTML_Text_Replacement( $after ? $span->start + $span->length : $span->start, 0, $html );
		}
	};

	if ( ! $processor->next_tag( 'DIV' ) || true !== $processor->has_class( 'wp-block-button' ) ) {
		return $content;
	}

	foreach ( wrapper_classes( $resolved ) as $class_name ) {
		$processor->add_class( $class_name );
	}

	$style = size_style( (string) $processor->get_attribute( 'style' ), $resolved['size'] );

	if ( '' !== $style ) {
		$processor->set_attribute( 'style', $style );
	}

	$tag = null;

	while ( $processor->next_tag() ) {
		if ( \in_array( $processor->get_tag(), array( 'A', 'BUTTON' ), true ) ) {
			$tag = $processor->get_tag();
			break;
		}
	}

	if ( null === $tag ) {
		return $content;
	}

	list( $open, $close ) = inserts( $icon, $resolved );
	$processor->isudev_insert( $open, true );

	$depth = 1;

	while ( $processor->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
		if ( $tag !== $processor->get_tag() ) {
			continue;
		}

		$depth += $processor->is_tag_closer() ? -1 : 1;

		if ( 0 === $depth ) {
			$processor->isudev_insert( $close, false );

			return $processor->get_updated_html();
		}
	}

	// No closing tag: leave the markup as core rendered it.
	return $content;
}

/**
 * Editor script plus the sanitised settings it needs.
 *
 * @return void
 */
function enqueue_editor(): void {
	$handle = Extensions::enqueue_build( SLUG, 'editor' );

	if ( '' === $handle ) {
		return;
	}

	\wp_add_inline_script(
		$handle,
		'window.isudevButtonIcon = ' . \wp_json_encode(
			\array_merge(
				settings(),
				array(
					'minSize' => MIN_SIZE,
					'maxSize' => MAX_SIZE,
				)
			)
		) . ';',
		'before'
	);
}
