<?php
/**
 * Accordion toggle icons: swap core/accordion-heading's "+" for two registry icons.
 *
 * The accordion's attributes reach each heading as block context (registered on
 * the core block types below), so a heading never looks at a sibling or a parent
 * by hand. Couplings to core markup are listed in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\CoreAccordionCustomIcons;

use IsuDevLibrary\Extensions;

use function IsuDevLibrary\Utils\get_icon;

use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const SLUG         = 'core-accordion-custom-icons';
const STYLE_HANDLE = 'isudev-library-core-accordion-custom-icons';
const MIN_SIZE     = 8;
const MAX_SIZE     = 128;

const CONTEXT_CLOSED = 'isudev/accordion-closed-icon';
const CONTEXT_OPEN   = 'isudev/accordion-open-icon';
const CONTEXT_SIZE   = 'isudev/accordion-icon-size';

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// Core registers its blocks on init 10; this runs on init 0, so the args filter is in place.
	\add_filter( 'register_block_type_args', __NAMESPACE__ . '\\register_args', 10, 2 );
	\add_filter( 'render_block_core/accordion-heading', __NAMESPACE__ . '\\render_heading', 10, 3 );
	\add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor' );

	// Front end: only when an accordion renders. Editor: enqueue_block_assets, so it reaches the iframe.
	\wp_enqueue_block_style(
		'core/accordion',
		array(
			'handle' => STYLE_HANDLE,
			'src'    => \plugins_url( 'style.css', __FILE__ ),
			'path'   => __DIR__ . '/style.css',
			'ver'    => VERSION,
		)
	);
}

/**
 * Clamp an icon size to whole pixels in [MIN_SIZE, MAX_SIZE].
 *
 * @param mixed $value    Candidate size.
 * @param int   $fallback Returned for anything that is not a positive number.
 * @return int
 */
function clamp_size( $value, int $fallback ): int {
	if ( ! \is_int( $value ) && ! \is_float( $value ) && ! ( \is_string( $value ) && \is_numeric( $value ) ) ) {
		return $fallback;
	}

	$size = (int) \round( (float) $value );

	if ( $size <= 0 ) {
		return $fallback;
	}

	return \max( MIN_SIZE, \min( MAX_SIZE, $size ) );
}

/**
 * An icon name, or '' for anything that cannot be a registry key.
 *
 * @param mixed $value Candidate name.
 * @return string
 */
function icon_name( $value ): string {
	return \is_string( $value ) && 1 === \preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $value ) ? $value : '';
}

/**
 * Site-wide defaults: filter, then isudev.json, then the built-in chevrons.
 *
 * @return array{closed: string, open: string, size: int}
 */
function defaults(): array {
	$fallback = array(
		'closed' => icon_name( Extensions::setting( SLUG, 'closedIcon', '' ) ),
		'open'   => icon_name( Extensions::setting( SLUG, 'openIcon', '' ) ),
		'size'   => clamp_size( Extensions::setting( SLUG, 'size', 24 ), 24 ),
	);

	$fallback['closed'] = '' !== $fallback['closed'] ? $fallback['closed'] : 'chevronDown';
	$fallback['open']   = '' !== $fallback['open'] ? $fallback['open'] : 'chevronUp';

	/**
	 * Filters the icons and size used when an accordion picks none.
	 *
	 * @param array $defaults `closed` and `open` icon names, `size` in pixels.
	 */
	$filtered = \apply_filters( 'isudev_library/extensions/core_accordion_custom_icons/defaults', $fallback );
	$filtered = \is_array( $filtered ) ? $filtered : array();

	$closed = icon_name( $filtered['closed'] ?? '' );
	$open   = icon_name( $filtered['open'] ?? '' );

	return array(
		'closed' => '' !== $closed ? $closed : $fallback['closed'],
		'open'   => '' !== $open ? $open : $fallback['open'],
		'size'   => clamp_size( $filtered['size'] ?? null, $fallback['size'] ),
	);
}

/**
 * The icons and size for one heading: accordion attribute, else the default.
 *
 * @param array $context  Block context of the heading.
 * @param array $defaults Output of defaults().
 * @return array{closed: string, open: string, size: int}
 */
function resolve( array $context, array $defaults ): array {
	$closed = icon_name( $context[ CONTEXT_CLOSED ] ?? '' );
	$open   = icon_name( $context[ CONTEXT_OPEN ] ?? '' );

	return array(
		'closed' => '' !== $closed ? $closed : $defaults['closed'],
		'open'   => '' !== $open ? $open : $defaults['open'],
		'size'   => clamp_size( $context[ CONTEXT_SIZE ] ?? null, $defaults['size'] ),
	);
}

/**
 * Attributes on core/accordion, and the context that carries them to each heading.
 *
 * @param array  $args       Block type args.
 * @param string $block_type Block name.
 * @return array
 */
function register_args( $args, $block_type ): array {
	$args = \is_array( $args ) ? $args : array();

	if ( 'core/accordion' === $block_type ) {
		$args['attributes'] = \array_merge(
			(array) ( $args['attributes'] ?? array() ),
			array(
				'isudevClosedIcon' => array( 'type' => 'string' ),
				'isudevOpenIcon'   => array( 'type' => 'string' ),
				'isudevIconSize'   => array( 'type' => 'number' ),
			)
		);

		$args['provides_context'] = \array_merge(
			(array) ( $args['provides_context'] ?? array() ),
			array(
				CONTEXT_CLOSED => 'isudevClosedIcon',
				CONTEXT_OPEN   => 'isudevOpenIcon',
				CONTEXT_SIZE   => 'isudevIconSize',
			)
		);
	}

	if ( 'core/accordion-heading' === $block_type ) {
		$args['uses_context'] = \array_values(
			\array_unique(
				\array_merge(
					(array) ( $args['uses_context'] ?? array() ),
					array( CONTEXT_CLOSED, CONTEXT_OPEN, CONTEXT_SIZE )
				)
			)
		);
	}

	return $args;
}

/**
 * Both icons for the toggle, closed first; '' when either cannot be rendered.
 *
 * @param array $icons Output of resolve().
 * @param array $defaults Output of defaults(), the fallback for a name the registry lost.
 * @return string
 */
function icons_markup( array $icons, array $defaults ): string {
	$markup = '';

	foreach ( array( 'closed', 'open' ) as $state ) {
		$args = array(
			'size'  => $icons['size'],
			'class' => 'isudev-accordion-icon isudev-accordion-icon--' . $state,
		);
		$icon = get_icon( $icons[ $state ], $args );

		if ( '' === $icon && $icons[ $state ] !== $defaults[ $state ] ) {
			$icon = get_icon( $defaults[ $state ], $args );
		}

		if ( '' === $icon ) {
			return '';
		}

		$markup .= $icon;
	}

	return $markup;
}

/**
 * Put the icons inside core's toggle-icon span, which holds exactly the text "+".
 *
 * The span's text node is swapped for a random placeholder through the HTML API,
 * then the placeholder for the icon markup, so nothing outside that span can match.
 *
 * @param string $html   Rendered heading.
 * @param string $markup Icon markup from icons_markup().
 * @param int    $size   Icon size in pixels.
 * @return string Unchanged when the span is missing (showIcon off) or not core's.
 */
function replace_icon( string $html, string $markup, int $size ): string {
	if ( '' === $markup ) {
		return $html;
	}

	$processor = new \WP_HTML_Tag_Processor( $html );

	if ( ! $processor->next_tag( array( 'class_name' => 'wp-block-accordion-heading__toggle-icon' ) ) ) {
		return $html;
	}

	$style = \trim( (string) $processor->get_attribute( 'style' ) );
	$style = ( '' === $style || \str_ends_with( $style, ';' ) ) ? $style : $style . ';';

	$processor->add_class( 'is-isudev-icon' );
	$processor->set_attribute( 'style', $style . '--isudev-accordion-icon-size:' . $size . 'px;' );

	if ( ! $processor->next_token() || '#text' !== $processor->get_token_type() ) {
		return $html;
	}

	$placeholder = 'isudev-accordion-icon-' . \wp_generate_uuid4();
	$processor->set_modifiable_text( $placeholder );

	if ( ! $processor->next_token() || 'SPAN' !== $processor->get_tag() || ! $processor->is_tag_closer() ) {
		return $html;
	}

	return \str_replace( $placeholder, $markup, $processor->get_updated_html() );
}

/**
 * Filter callback for render_block_core/accordion-heading.
 *
 * @param string         $content  Rendered heading.
 * @param array          $block    Parsed block.
 * @param \WP_Block|null $instance Block instance; carries the accordion's context.
 * @return string
 */
function render_heading( $content, $block, $instance = null ): string {
	$content = (string) $content;

	if ( '' === $content || ! \str_contains( $content, 'wp-block-accordion-heading__toggle-icon' ) ) {
		return $content;
	}

	$context  = $instance instanceof \WP_Block && \is_array( $instance->context ) ? $instance->context : array();
	$defaults = defaults();
	$icons    = resolve( $context, $defaults );

	return replace_icon( $content, icons_markup( $icons, $defaults ), $icons['size'] );
}

/**
 * Editor script (inspector panel + canvas preview) with the resolved defaults.
 *
 * @return void
 */
function enqueue_editor(): void {
	$handle = Extensions::enqueue_build( SLUG, 'editor' );

	if ( '' === $handle ) {
		return;
	}

	$data = \wp_json_encode(
		array_merge(
			defaults(),
			array(
				'minSize' => MIN_SIZE,
				'maxSize' => MAX_SIZE,
			)
		)
	);

	if ( \is_string( $data ) ) {
		\wp_add_inline_script( $handle, 'window.isudevAccordionIcons = ' . $data . ';', 'before' );
	}
}
