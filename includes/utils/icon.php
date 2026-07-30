<?php
/**
 * Inline SVG icon registry. No external icon dependency.
 *
 * The default_icon_paths() and build_svg() functions are pure — no WP calls.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Built-in icon path markup, keyed by slug. Pure.
 *
 * Paths are authored on a 0 0 600 600 viewBox.
 *
 * @return array slug => SVG child markup.
 */
function default_icon_paths(): array {
	return array(
		'chevronDown' => '<path d="M197.5 231.5c-.5.2-2.2.6-3.7.9-3.5.8-8.4 5-10.5 9s-2.1 13.2 0 17.1c.8 1.6 25.3 26.7 54.4 55.7 37.8 37.7 53.7 52.9 55.8 53.4 3.8 1 9.2 1 13 0 2.1-.5 18-15.7 55.8-53.4 29.1-29 53.6-54.1 54.4-55.7 2-3.6 2.1-13 .3-16.5-1.8-3.6-5.1-7-8.4-8.7-3.2-1.7-10.5-2.2-15.1-1-2.1.6-16.3 14.1-48.3 46L300 323.5l-44.8-44.7c-24.6-24.5-45.6-45-46.7-45.6-2.3-1.1-9.5-2.3-11-1.7"/>',
		'burger'      => '<path d="M95.1 132c-9.5 2.3-15.4 12.4-13.1 22.4 1.7 7.8 7.9 13.1 16.3 14.1 2.9.3 95.7.5 206.2.3 215.6-.3 202.6 0 208.3-5.2 3.8-3.4 5.5-7.7 5.5-13.6s-1.7-10.2-5.5-13.6c-5.7-5.2 7.6-4.9-210.8-5.1-111.9 0-205 .3-206.9.7M95.1 282c-9.5 2.3-15.4 12.4-13.1 22.4 1.7 7.8 7.9 13.1 16.3 14.1 2.9.3 95.7.5 206.2.3 215.6-.3 202.6 0 208.3-5.2 3.8-3.4 5.5-7.7 5.5-13.6s-1.7-10.2-5.5-13.6c-5.7-5.2 7.6-4.9-210.8-5.1-111.9 0-205 .3-206.9.7M95.1 432c-9.5 2.3-15.4 12.4-13.1 22.4 1.7 7.8 7.9 13.1 16.3 14.1 2.9.3 95.7.5 206.2.3 215.6-.3 202.6 0 208.3-5.2 3.8-3.4 5.5-7.7 5.5-13.6s-1.7-10.2-5.5-13.6c-5.7-5.2 7.6-4.9-210.8-5.1-111.9 0-205 .3-206.9.7"/>',
		'close'       => '<path d="M172.5 156.5c-.5.2-2.2.6-3.7.9-3.4.8-8.4 5-10.4 8.9-1.8 3.3-2.3 10.6-1.1 15.2.6 2.1 17.5 19.7 58.5 60.7l57.7 57.8-57.7 57.8c-41 41-57.9 58.6-58.5 60.7-3.4 13.1 4.2 24.5 16.6 24.8 2.5.1 6-.2 7.6-.6 2.1-.6 19.7-17.5 60.8-58.5l57.7-57.7 57.8 57.7c41 41 58.6 57.9 60.7 58.5 13.1 3.4 24.5-4.2 24.8-16.6.1-2.5-.2-6-.6-7.6-.6-2.1-17.5-19.7-58.5-60.8L326.5 300l57.7-57.8c41-41 57.9-58.6 58.5-60.7 3.4-13.1-4.2-24.5-16.6-24.8-2.5-.1-5.9.2-7.6.6-2.1.6-19.7 17.5-60.8 58.5L300 273.5l-57.3-57.2c-31.4-31.4-58.1-57.5-59.2-58.1-2.3-1.1-9.5-2.3-11-1.7"/>',
	);
}

/**
 * Wrap icon path markup in an SVG element. Pure.
 *
 * @param string $path_d     SVG child markup (already trusted, static).
 * @param int    $size       Pixel size for width and height.
 * @param string $class_attr Escaped value for the class attribute.
 * @return string SVG markup, or '' when there is nothing to draw.
 */
function build_svg( string $path_d, int $size, string $class_attr ): string {
	if ( '' === $path_d ) {
		return '';
	}

	return \sprintf(
		'<svg class="%1$s" width="%2$d" height="%2$d" viewBox="0 0 600 600" fill="currentColor" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">%3$s</svg>',
		$class_attr,
		$size,
		$path_d
	);
}

/*
 * WordPress adapters.
 */

/**
 * Return an inline SVG icon for the given slug.
 *
 * @param string $slug       Icon slug.
 * @param int    $size       Pixel size.
 * @param string $class_name Extra CSS class.
 * @return string Safe SVG markup ('' for unknown slugs).
 */
function icon( string $slug, int $size = 24, string $class_name = '' ): string {
	/**
	 * Filters the icon registry, so integrators can add or replace icons.
	 *
	 * @param array $paths slug => SVG child markup.
	 */
	$paths = (array) \apply_filters( 'isudev_library/icons', default_icon_paths() );

	$path_d = isset( $paths[ $slug ] ) && \is_string( $paths[ $slug ] ) ? $paths[ $slug ] : '';
	$svg    = build_svg( $path_d, $size, \esc_attr( $class_name ) );

	/**
	 * Filters the rendered icon markup.
	 *
	 * @param string $svg        The SVG markup ('' if unknown).
	 * @param string $slug       Icon slug.
	 * @param int    $size       Pixel size.
	 * @param string $class_name Extra CSS class.
	 */
	return (string) \apply_filters( 'isudev_library/icon', $svg, $slug, $size, $class_name );
}
