<?php
/**
 * Render helpers for the accessible header block.
 *
 * @package IsuDevLibrary\Blocks\SiteHeader
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\SiteHeader;

defined( 'ABSPATH' ) || exit;

/**
 * Build the logo markup according to the block's logoSource attribute.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function build_logo( array $attributes ): string {
	$source   = isset( $attributes['logoSource'] ) ? (string) $attributes['logoSource'] : 'site';
	$home_url = \esc_url( \home_url( '/' ) );

	if ( 'none' === $source ) {
		return '';
	}

	if ( 'custom' === $source ) {
		$url = isset( $attributes['logoUrl'] ) ? (string) $attributes['logoUrl'] : '';
		if ( '' === $url ) {
			return '';
		}
		return '<a class="isudev-header__logo" href="' . $home_url . '" rel="home">'
			. '<img src="' . \esc_url( $url ) . '" alt="' . \esc_attr( \get_bloginfo( 'name' ) ) . '" /></a>';
	}

	// 'site' — custom logo when set, else the site title as text.
	if ( \has_custom_logo() ) {
		$custom = \get_custom_logo();
		if ( \is_string( $custom ) && '' !== $custom ) {
			return '<span class="isudev-header__logo">' . $custom . '</span>';
		}
	}
	return '<a class="isudev-header__logo isudev-header__logo--text" href="' . $home_url . '" rel="home">'
		. \esc_html( \get_bloginfo( 'name' ) ) . '</a>';
}

/**
 * Resolve the menuRef attribute into wp_nav_menu location/menu args.
 *
 * @param string $menu_ref menuRef attribute (`location:<slug>` | `id:<n>` | '').
 * @return array Partial wp_nav_menu args ('theme_location' or 'menu').
 */
function resolve_menu_ref( string $menu_ref ): array {
	if ( 0 === \strpos( $menu_ref, 'location:' ) ) {
		return array( 'theme_location' => \substr( $menu_ref, \strlen( 'location:' ) ) );
	}
	if ( 0 === \strpos( $menu_ref, 'id:' ) ) {
		return array( 'menu' => (int) \substr( $menu_ref, \strlen( 'id:' ) ) );
	}
	return array();
}

/**
 * Render the navigation menu HTML for the header.
 *
 * @param array $attributes Block attributes.
 * @return string Menu markup ('' when no menu resolves).
 */
function build_menu( array $attributes ): string {
	$menu_ref = isset( $attributes['menuRef'] ) ? (string) $attributes['menuRef'] : '';

	$args = \array_merge(
		array(
			'container'   => false,
			'menu_class'  => 'isudev-nav__list',
			'items_wrap'  => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			'fallback_cb' => false,
			'echo'        => false,
			'depth'       => 2,
			'walker'      => new Nav_Walker(),
		),
		resolve_menu_ref( $menu_ref )
	);

	/**
	 * Filter the wp_nav_menu args used to build the header navigation.
	 *
	 * @param array $args       wp_nav_menu args.
	 * @param array $attributes Block attributes.
	 */
	$args = \apply_filters( 'isudev_library/site_header/menu_args', $args, $attributes );

	if ( empty( $args['theme_location'] ) && empty( $args['menu'] ) ) {
		return '';
	}

	$html = \wp_nav_menu( $args );
	return \is_string( $html ) ? $html : '';
}
