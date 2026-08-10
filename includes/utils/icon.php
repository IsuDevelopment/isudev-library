<?php
/**
 * Icon registry shared by frontend rendering and the block editor.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Utils;

defined( 'ABSPATH' ) || exit;

const DEFAULT_ICON_SIZE = 24;

/**
 * Return the built-in icon definitions.
 *
 * @return array Icon definitions keyed by name.
 */
function default_icons(): array {
	return array(
		'chevronDown'  => array(
			'label'    => __( 'Chevron down', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" fill="currentColor"><path d="M197.5 231.5c-.5.2-2.2.6-3.7.9-3.5.8-8.4 5-10.5 9s-2.1 13.2 0 17.1c.8 1.6 25.3 26.7 54.4 55.7 37.8 37.7 53.7 52.9 55.8 53.4 3.8 1 9.2 1 13 0 2.1-.5 18-15.7 55.8-53.4 29.1-29 53.6-54.1 54.4-55.7 2-3.6 2.1-13 .3-16.5-1.8-3.6-5.1-7-8.4-8.7-3.2-1.7-10.5-2.2-15.1-1-2.1.6-16.3 14.1-48.3 46L300 323.5l-44.8-44.7c-24.6-24.5-45.6-45-46.7-45.6-2.3-1.1-9.5-2.3-11-1.7"/></svg>',
			'keywords' => array( 'arrow', 'down', 'expand', 'submenu' ),
		),
		'burger'       => array(
			'label'    => __( 'Menu', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" fill="currentColor"><path d="M95.1 132c-9.5 2.3-15.4 12.4-13.1 22.4 1.7 7.8 7.9 13.1 16.3 14.1 2.9.3 95.7.5 206.2.3 215.6-.3 202.6 0 208.3-5.2 3.8-3.4 5.5-7.7 5.5-13.6s-1.7-10.2-5.5-13.6c-5.7-5.2 7.6-4.9-210.8-5.1-111.9 0-205 .3-206.9.7M95.1 282c-9.5 2.3-15.4 12.4-13.1 22.4 1.7 7.8 7.9 13.1 16.3 14.1 2.9.3 95.7.5 206.2.3 215.6-.3 202.6 0 208.3-5.2 3.8-3.4 5.5-7.7 5.5-13.6s-1.7-10.2-5.5-13.6c-5.7-5.2 7.6-4.9-210.8-5.1-111.9 0-205 .3-206.9.7M95.1 432c-9.5 2.3-15.4 12.4-13.1 22.4 1.7 7.8 7.9 13.1 16.3 14.1 2.9.3 95.7.5 206.2.3 215.6-.3 202.6 0 208.3-5.2 3.8-3.4 5.5-7.7 5.5-13.6s-1.7-10.2-5.5-13.6c-5.7-5.2 7.6-4.9-210.8-5.1-111.9 0-205 .3-206.9.7"/></svg>',
			'keywords' => array( 'menu', 'hamburger', 'nav' ),
		),
		'close'        => array(
			'label'    => __( 'Close', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" fill="currentColor"><path d="M172.5 156.5c-.5.2-2.2.6-3.7.9-3.4.8-8.4 5-10.4 8.9-1.8 3.3-2.3 10.6-1.1 15.2.6 2.1 17.5 19.7 58.5 60.7l57.7 57.8-57.7 57.8c-41 41-57.9 58.6-58.5 60.7-3.4 13.1 4.2 24.5 16.6 24.8 2.5.1 6-.2 7.6-.6 2.1-.6 19.7-17.5 60.8-58.5l57.7-57.7 57.8 57.7c41 41 58.6 57.9 60.7 58.5 13.1 3.4 24.5-4.2 24.8-16.6.1-2.5-.2-6-.6-7.6-.6-2.1-17.5-19.7-58.5-60.8L326.5 300l57.7-57.8c41-41 57.9-58.6 58.5-60.7 3.4-13.1-4.2-24.5-16.6-24.8-2.5-.1-5.9.2-7.6.6-2.1.6-19.7 17.5-60.8 58.5L300 273.5l-57.3-57.2c-31.4-31.4-58.1-57.5-59.2-58.1-2.3-1.1-9.5-2.3-11-1.7"/></svg>',
			'keywords' => array( 'x', 'dismiss', 'cancel' ),
		),
		'arrowForward' => array(
			'label'    => __( 'Arrow forward', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.3 19.3c-.2-.2-.3-.4-.3-.7 0-.3.1-.5.3-.7l4.9-4.9H5c-.3 0-.5-.1-.7-.3-.2-.2-.3-.4-.3-.7s.1-.5.3-.7c.2-.2.4-.3.7-.3h11.2l-4.9-4.9c-.2-.2-.3-.4-.3-.7 0-.3.1-.5.3-.7.2-.2.4-.3.7-.3s.5.1.7.3l6.6 6.6c.1.1.2.2.2.3 0 .1.1.3.1.4 0 .1 0 .3-.1.4 0 .1-.1.2-.2.3l-6.6 6.6c-.2.2-.4.3-.7.3s-.5-.1-.7-.3z"/></svg>',
			'keywords' => array( 'arrow', 'right', 'next' ),
		),
	);
}

/**
 * Normalize icon definitions from the registry filter.
 *
 * @param array $icons Icon definitions keyed by name.
 * @return array Valid normalized definitions.
 */
function normalize_icons( array $icons ): array {
	$normalized = array();

	foreach ( $icons as $name => $definition ) {
		if ( ! is_string( $name ) || '' === $name || ! is_array( $definition ) ) {
			continue;
		}

		$markup = isset( $definition['icon'] ) && is_string( $definition['icon'] )
			? trim( $definition['icon'] )
			: '';

		if ( '' === $markup ) {
			continue;
		}

		$label = isset( $definition['label'] ) && is_string( $definition['label'] ) && '' !== $definition['label']
			? $definition['label']
			: $name;

		$definition['icon']  = $markup;
		$definition['label'] = $label;

		$normalized[ $name ] = $definition;
	}

	return $normalized;
}

/**
 * Return the filtered and normalized icon registry.
 *
 * @return array Icon definitions keyed by name.
 */
function get_icons(): array {
	/**
	 * Filters the icon registry.
	 *
	 * @param array $icons Icon definitions keyed by name.
	 */
	return normalize_icons( (array) apply_filters( 'isudev_library/icons', default_icons() ) );
}

/**
 * Normalize an icon size to width and height.
 *
 * @param mixed $size Integer-like size or a width and height array.
 * @return array Two-element list containing integer width and height.
 */
function normalize_size( $size ): array {
	if ( is_array( $size ) ) {
		$width  = (int) ( $size[0] ?? 0 );
		$height = (int) ( $size[1] ?? $width );
	} else {
		$width  = (int) $size;
		$height = $width;
	}

	if ( $width <= 0 ) {
		$width = DEFAULT_ICON_SIZE;
	}

	if ( $height <= 0 ) {
		$height = $width;
	}

	return array( $width, $height );
}

/**
 * Whether a key may be written as an HTML attribute name.
 *
 * Lowercase hyphenated names only, and never an event handler. Both the
 * serializer and the SVG root rewriter test keys with this function, so an
 * attribute that cannot be written is also never stripped from the source
 * markup — otherwise passing `viewBox` would delete the glyph's own viewBox and
 * put nothing back.
 *
 * @param mixed $key Candidate attribute name.
 * @return bool Whether the name is writable.
 */
function is_attr_name( $key ): bool {
	return is_string( $key )
		&& 1 === preg_match( '/^[a-z][a-z0-9-]*$/', $key )
		&& 0 !== stripos( $key, 'on' );
}

/**
 * Serialize safe HTML attributes.
 *
 * @param array $attrs Attributes keyed by name.
 * @return string Serialized attributes, each with a leading space.
 */
function build_attrs( array $attrs ): string {
	$output = '';

	foreach ( $attrs as $key => $value ) {
		if ( ! is_attr_name( $key ) ) {
			continue;
		}

		if ( null === $value || false === $value || is_array( $value ) || is_object( $value ) ) {
			continue;
		}

		if ( true === $value ) {
			$output .= ' ' . $key;
			continue;
		}

		$output .= sprintf( ' %s="%s"', $key, esc_attr( (string) $value ) );
	}

	return $output;
}

/**
 * Override attributes on the first SVG root element.
 *
 * @param string $svg   Trusted SVG registry markup.
 * @param array  $attrs Attributes to set or remove.
 * @return string Updated SVG markup, or an empty string when no SVG tag exists.
 */
function apply_root_attrs( string $svg, array $attrs ): string {
	// The broad tag match is safe because the registry is trusted static configuration, not arbitrary HTML.
	if ( ! preg_match( '/<svg\b[^>]*>/i', $svg, $matches, PREG_OFFSET_CAPTURE ) ) {
		return '';
	}

	$tag    = (string) $matches[0][0];
	$offset = (int) $matches[0][1];
	$inner  = substr( $tag, 4, -1 );
	$inner  = rtrim( $inner );

	$self_closing = '/' === substr( $inner, -1 );
	if ( $self_closing ) {
		$inner = rtrim( substr( $inner, 0, -1 ) );
	}

	foreach ( array_keys( $attrs ) as $key ) {
		if ( ! is_attr_name( $key ) ) {
			continue;
		}

		$pattern = '/\s' . preg_quote( $key, '/' ) . '\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i';
		$inner   = (string) preg_replace( $pattern, '', $inner );
	}

	$rebuilt = '<svg' . rtrim( $inner ) . build_attrs( $attrs ) . ( $self_closing ? ' />' : '>' );

	return substr_replace( $svg, $rebuilt, $offset, strlen( $tag ) );
}

/**
 * Render inline SVG markup or an image URL with root attributes.
 *
 * @param string $markup Complete SVG markup or an image URL.
 * @param array  $attrs  Root element attributes.
 * @return string Rendered icon markup.
 */
function render_icon( string $markup, array $attrs ): string {
	$markup = trim( $markup );

	if ( 0 === stripos( $markup, '<svg' ) ) {
		return apply_root_attrs( $markup, $attrs );
	}

	return sprintf( '<img src="%s"%s>', esc_url( $markup ), build_attrs( $attrs ) );
}

/**
 * Return rendered markup for an icon definition.
 *
 * @param string $name Icon name.
 * @param array  $args Rendering arguments.
 * @return string Rendered icon markup, or an empty string for an unknown icon.
 */
function get_icon( string $name, array $args = array() ): string {
	$icons = get_icons();

	if ( '' === $name || ! isset( $icons[ $name ] ) ) {
		/**
		 * Filters rendered icon markup.
		 *
		 * @param string $markup Rendered markup, or an empty string for an unknown icon.
		 * @param string $name   Icon name.
		 * @param array  $args   Rendering arguments.
		 */
		return (string) apply_filters( 'isudev_library/icon', '', $name, $args );
	}

	$markup = (string) $icons[ $name ]['icon'];

	list( $width, $height ) = normalize_size( $args['size'] ?? DEFAULT_ICON_SIZE );

	$class_name = isset( $args['class'] ) && is_string( $args['class'] ) ? trim( $args['class'] ) : '';
	$extra      = isset( $args['attrs'] ) && is_array( $args['attrs'] ) ? $args['attrs'] : array();
	$is_svg     = 0 === stripos( trim( $markup ), '<svg' );
	$defaults   = $is_svg
		? array(
			'width'       => $width,
			'height'      => $height,
			'fill'        => 'currentColor',
			'aria-hidden' => 'true',
			'focusable'   => 'false',
		)
		: array(
			'width'    => $width,
			'height'   => $height,
			'alt'      => '',
			'decoding' => 'async',
		);

	if ( '' !== $class_name ) {
		$defaults['class'] = $class_name;
	}

	$attrs  = array_merge( $defaults, $extra );
	$markup = render_icon( $markup, $attrs );

	/**
	 * Filters rendered icon markup.
	 *
	 * @param string $markup Rendered markup.
	 * @param string $name   Icon name.
	 * @param array  $args   Rendering arguments.
	 */
	return (string) apply_filters( 'isudev_library/icon', $markup, $name, $args );
}

/**
 * Echo rendered markup for an icon definition.
 *
 * @param string $name Icon name.
 * @param array  $args Rendering arguments.
 * @return void
 */
function the_icon( string $name, array $args = array() ): void {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Registry markup is trusted static configuration; attribute values are escaped in build_attrs().
	echo get_icon( $name, $args );
}

/**
 * Append the icon registry to a shared editor collection.
 *
 * @param string $handle      Registered script handle.
 * @param string $object_name JavaScript global name.
 * @return void
 */
function localize_icons( string $handle, string $object_name = 'isudevIcons' ): void {
	// The name is interpolated into JavaScript, so it has to be an identifier.
	if ( 1 !== preg_match( '/^[A-Za-z_$][A-Za-z0-9_$]*$/', $object_name ) ) {
		return;
	}

	$definitions = array();

	foreach ( get_icons() as $name => $definition ) {
		$definitions[] = array_merge( array( 'name' => $name ), $definition );
	}

	$json = wp_json_encode( $definitions );

	if ( ! is_string( $json ) ) {
		return;
	}

	/*
	 * Append rather than assign. This global is shared with the other isudev-*
	 * plugins, and wp_localize_script() emits `var name = [...]`, which would
	 * discard whatever they published first. @isudev/gutenberg resolves a
	 * collection by name and keeps the first entry per name, so concatenating
	 * composes registries instead of clobbering one. A plugin that still assigns
	 * the global overwrites everything printed before it; that is a bug in that
	 * plugin, and nothing this side can defend against.
	 */
	wp_add_inline_script(
		$handle,
		sprintf( 'window.%1$s = ( window.%1$s || [] ).concat( %2$s );', $object_name, $json )
	);
}

/**
 * Register icon editor integration hooks.
 *
 * @return void
 */
function boot_icons(): void {
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_icons', 5 );
}

/**
 * Enqueue the localized icon registry for every block editor.
 *
 * @return void
 */
function enqueue_editor_icons(): void {
	// A data-only handle prints localized data in the head before block editor scripts without coupling to a Core handle's lifecycle.
	wp_register_script( 'isudev-library-icons', false, array(), \IsuDevLibrary\VERSION, false );
	wp_enqueue_script( 'isudev-library-icons' );
	localize_icons( 'isudev-library-icons' );
}
