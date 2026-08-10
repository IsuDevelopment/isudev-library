<?php
/**
 * Server render for isudev/site-header.
 *
 * @package IsuDevLibrary\Blocks\SiteHeader
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    InnerBlocks content (the "end" region).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\SiteHeader;

use function IsuDevLibrary\Utils\get_icon;

defined( 'ABSPATH' ) || exit;

$aria_label  = isset( $attributes['ariaLabel'] ) && '' !== $attributes['ariaLabel']
	? (string) $attributes['ariaLabel']
	: \__( 'Main', 'isudev-library' );
$white_class = isset( $attributes['whiteHeaderBodyClass'] ) ? (string) $attributes['whiteHeaderBodyClass'] : '';
$is_sticky   = ! isset( $attributes['sticky'] ) || $attributes['sticky'];

$logo      = build_logo( $attributes );
$menu_html = build_menu( $attributes );
$end       = \is_string( $content ) ? $content : '';

$burger = '<button type="button" class="isudev-header__burger" aria-expanded="false" aria-controls="isudev-header-nav">'
	. '<span class="isudev-sr-only">' . \esc_html__( 'Menu', 'isudev-library' ) . '</span>'
	. '<span class="isudev-header__burger-box" aria-hidden="true">'
	. '<span class="isudev-header__burger-line"></span>'
	. '<span class="isudev-header__burger-line"></span>'
	. '<span class="isudev-header__burger-line"></span>'
	. '</span></button>';

$close = '<button type="button" class="isudev-header__close">'
	. '<span class="isudev-header__close-label">' . \esc_html__( 'Close', 'isudev-library' ) . '</span>'
	. get_icon( 'close', array( 'class' => 'isudev-header__close-icon' ) )
	. '</button>';

$end_region = '' !== $end ? '<div class="isudev-header__end">' . $end . '</div>' : '';

$nav = '<div class="isudev-header__nav" id="isudev-header-nav">'
	. '<div class="isudev-header__nav-head">' . $logo . $close . '</div>'
	. '<nav class="isudev-nav" aria-label="' . \esc_attr( $aria_label ) . '">' . $menu_html . '</nav>'
	. $end_region
	. '</div>';

/**
 * Filter the rendered header regions before assembly. Integrators may replace
 * or extend regions (e.g. inject a search form, swap the logo markup).
 *
 * Note: 'logo' governs the top-bar logo only. The mobile drawer head embeds its
 * own copy inside 'nav'; to re-skin the logo everywhere, filter 'nav' as well
 * (or use the isudev_library/site_header/output filter below).
 *
 * @param array $regions    Keyed region markup: 'logo', 'nav', 'burger'.
 * @param array $attributes Block attributes.
 */
$regions = \apply_filters(
	'isudev_library/site_header/regions',
	array(
		'logo'   => $logo,
		'nav'    => $nav,
		'burger' => $burger,
	),
	$attributes
);

$inner = '<div class="isudev-header__inner">'
	. ( $regions['logo'] ?? '' )
	. ( $regions['nav'] ?? '' )
	. ( $regions['burger'] ?? '' )
	. '</div>';

$classes = 'isudev-header';
if ( $is_sticky ) {
	$classes .= ' is-sticky';
}

$wrapper = \get_block_wrapper_attributes(
	array(
		'class'                   => $classes,
		'data-white-header-class' => $white_class,
	)
);

$html = '<div ' . $wrapper . '>' . $inner . '</div>';

/**
 * Filter the final header markup.
 *
 * @param string $html       Assembled header markup.
 * @param array  $attributes Block attributes.
 */
echo \apply_filters( 'isudev_library/site_header/output', $html, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup assembled from escaped parts.
