<?php
/**
 * Skip links: a keyboard-only shortcut list at the top of every front-end page.
 *
 * Everything about the list is filterable — see README.md in this directory.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\SkipLinks;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'wp_body_open', __NAMESPACE__ . '\\render' );
	\add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\dequeue_core_skip_link', 20 );
	\add_action( 'wp_head', __NAMESPACE__ . '\\print_styles' );
}

/**
 * Drop the core block theme skip link.
 *
 * Core injects its own "Skip to content" link into block themes. Leaving it
 * would put two skip mechanisms in the same tab order, which is worse for a
 * screen reader user than either one alone.
 *
 * @return void
 */
function dequeue_core_skip_link(): void {
	/**
	 * Filters whether to remove the core block theme skip link.
	 *
	 * @param bool $remove Default true.
	 */
	if ( ! \apply_filters( 'isudev_library/extensions/skip_links/remove_core_link', true ) ) {
		return;
	}

	\wp_dequeue_script( 'wp-block-template-skip-link' );
}

/**
 * The links, in tab order.
 *
 * Targets are element IDs. A link whose target does not exist on the page is a
 * dead end for a keyboard user, so keep this list to landmarks every template
 * actually has, and add per-template entries through the filter.
 *
 * @return array List of array{href:string,label:string}.
 */
function links(): array {
	$links = array(
		array(
			'href'  => '#wp--skip-link--target',
			'label' => \__( 'Skip to main content', 'isudev-library' ),
		),
	);

	/**
	 * Filters the skip links.
	 *
	 * Each entry is array{href:string,label:string}; `href` is a fragment such
	 * as `#content`. Entries missing either key are dropped.
	 *
	 * @param array $links List of array{href:string,label:string}.
	 */
	$links = \apply_filters( 'isudev_library/extensions/skip_links/links', $links );

	if ( ! \is_array( $links ) ) {
		return array();
	}

	$valid = array();

	foreach ( $links as $link ) {
		if ( ! \is_array( $link ) ) {
			continue;
		}

		$href  = \is_string( $link['href'] ?? null ) ? \trim( $link['href'] ) : '';
		$label = \is_string( $link['label'] ?? null ) ? \trim( $link['label'] ) : '';

		if ( '' === $href || '' === $label ) {
			continue;
		}

		$valid[] = array(
			'href'  => $href,
			'label' => $label,
		);
	}

	return $valid;
}

/**
 * The accessible name of the skip link navigation landmark.
 *
 * @return string
 */
function nav_label(): string {
	/**
	 * Filters the aria-label on the skip link navigation.
	 *
	 * @param string $label Default 'Skip links'.
	 */
	$label = \apply_filters( 'isudev_library/extensions/skip_links/nav_label', \__( 'Skip links', 'isudev-library' ) );

	return \is_string( $label ) && '' !== $label ? $label : \__( 'Skip links', 'isudev-library' );
}

/**
 * Render the navigation.
 *
 * @return void
 */
function render(): void {
	$links = links();

	if ( array() === $links ) {
		return;
	}

	\printf(
		'<nav class="isudev-skip-links" aria-label="%s"><ul class="isudev-skip-links__list">',
		\esc_attr( nav_label() )
	);

	foreach ( $links as $link ) {
		\printf(
			'<li class="isudev-skip-links__item"><a class="isudev-skip-links__link" href="%1$s">%2$s</a></li>',
			\esc_attr( $link['href'] ),
			\esc_html( $link['label'] )
		);
	}

	echo '</ul></nav>';
}

/**
 * Print the styles that keep the list off-screen until it is focused.
 *
 * Inline in the head, not a stylesheet: these rules must be in effect before
 * the markup they hide is parsed, or the links flash on first paint.
 *
 * @return void
 */
function print_styles(): void {
	/**
	 * Filters whether this extension prints its own skip link styles.
	 *
	 * Return false when the theme styles `.isudev-skip-links` itself. The links
	 * must stay reachable by keyboard: hide them with a clip, never with
	 * `display: none`, which takes them out of the tab order entirely.
	 *
	 * @param bool $print Default true.
	 */
	if ( ! \apply_filters( 'isudev_library/extensions/skip_links/print_styles', true ) ) {
		return;
	}

	echo '<style id="isudev-skip-links-styles">
		.isudev-skip-links__list { margin: 0; padding: 0; list-style: none; }
		.isudev-skip-links__link {
			position: absolute;
			top: 0;
			left: 0;
			z-index: 100000;
			width: 1px;
			height: 1px;
			padding: 0;
			overflow: hidden;
			clip-path: inset(50%);
			white-space: nowrap;
		}
		.isudev-skip-links__link:focus {
			width: auto;
			height: auto;
			padding: 0.75rem 1.5rem;
			overflow: visible;
			clip-path: none;
			background: #fff;
			color: #101517;
			font-size: 0.875rem;
			font-weight: 600;
			line-height: normal;
			text-decoration: underline;
		}
	</style>';
}
