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
		'chevronDown'    => array(
			'label'    => __( 'Chevron down', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" fill="currentColor"><path d="M197.5 231.5c-.5.2-2.2.6-3.7.9-3.5.8-8.4 5-10.5 9s-2.1 13.2 0 17.1c.8 1.6 25.3 26.7 54.4 55.7 37.8 37.7 53.7 52.9 55.8 53.4 3.8 1 9.2 1 13 0 2.1-.5 18-15.7 55.8-53.4 29.1-29 53.6-54.1 54.4-55.7 2-3.6 2.1-13 .3-16.5-1.8-3.6-5.1-7-8.4-8.7-3.2-1.7-10.5-2.2-15.1-1-2.1.6-16.3 14.1-48.3 46L300 323.5l-44.8-44.7c-24.6-24.5-45.6-45-46.7-45.6-2.3-1.1-9.5-2.3-11-1.7"/></svg>',
			'keywords' => array( 'arrow', 'down', 'expand', 'submenu' ),
		),
		'burger'         => array(
			'label'    => __( 'Menu', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" fill="currentColor"><path d="M95.1 132c-9.5 2.3-15.4 12.4-13.1 22.4 1.7 7.8 7.9 13.1 16.3 14.1 2.9.3 95.7.5 206.2.3 215.6-.3 202.6 0 208.3-5.2 3.8-3.4 5.5-7.7 5.5-13.6s-1.7-10.2-5.5-13.6c-5.7-5.2 7.6-4.9-210.8-5.1-111.9 0-205 .3-206.9.7M95.1 282c-9.5 2.3-15.4 12.4-13.1 22.4 1.7 7.8 7.9 13.1 16.3 14.1 2.9.3 95.7.5 206.2.3 215.6-.3 202.6 0 208.3-5.2 3.8-3.4 5.5-7.7 5.5-13.6s-1.7-10.2-5.5-13.6c-5.7-5.2 7.6-4.9-210.8-5.1-111.9 0-205 .3-206.9.7M95.1 432c-9.5 2.3-15.4 12.4-13.1 22.4 1.7 7.8 7.9 13.1 16.3 14.1 2.9.3 95.7.5 206.2.3 215.6-.3 202.6 0 208.3-5.2 3.8-3.4 5.5-7.7 5.5-13.6s-1.7-10.2-5.5-13.6c-5.7-5.2 7.6-4.9-210.8-5.1-111.9 0-205 .3-206.9.7"/></svg>',
			'keywords' => array( 'menu', 'hamburger', 'nav' ),
		),
		'close'          => array(
			'label'    => __( 'Close', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" fill="currentColor"><path d="M172.5 156.5c-.5.2-2.2.6-3.7.9-3.4.8-8.4 5-10.4 8.9-1.8 3.3-2.3 10.6-1.1 15.2.6 2.1 17.5 19.7 58.5 60.7l57.7 57.8-57.7 57.8c-41 41-57.9 58.6-58.5 60.7-3.4 13.1 4.2 24.5 16.6 24.8 2.5.1 6-.2 7.6-.6 2.1-.6 19.7-17.5 60.8-58.5l57.7-57.7 57.8 57.7c41 41 58.6 57.9 60.7 58.5 13.1 3.4 24.5-4.2 24.8-16.6.1-2.5-.2-6-.6-7.6-.6-2.1-17.5-19.7-58.5-60.8L326.5 300l57.7-57.8c41-41 57.9-58.6 58.5-60.7 3.4-13.1-4.2-24.5-16.6-24.8-2.5-.1-5.9.2-7.6.6-2.1.6-19.7 17.5-60.8 58.5L300 273.5l-57.3-57.2c-31.4-31.4-58.1-57.5-59.2-58.1-2.3-1.1-9.5-2.3-11-1.7"/></svg>',
			'keywords' => array( 'x', 'dismiss', 'cancel' ),
		),
		'arrowForward'   => array(
			'label'    => __( 'Arrow forward', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.3 19.3c-.2-.2-.3-.4-.3-.7 0-.3.1-.5.3-.7l4.9-4.9H5c-.3 0-.5-.1-.7-.3-.2-.2-.3-.4-.3-.7s.1-.5.3-.7c.2-.2.4-.3.7-.3h11.2l-4.9-4.9c-.2-.2-.3-.4-.3-.7 0-.3.1-.5.3-.7.2-.2.4-.3.7-.3s.5.1.7.3l6.6 6.6c.1.1.2.2.2.3 0 .1.1.3.1.4 0 .1 0 .3-.1.4 0 .1-.1.2-.2.3l-6.6 6.6c-.2.2-.4.3-.7.3s-.5-.1-.7-.3z"/></svg>',
			'keywords' => array( 'arrow', 'right', 'next' ),
		),

		/*
		 * The eight platform glyphs below are prefixed `social*` because
		 * `isudevIcons` is a flat namespace shared with the other isudev-*
		 * plugins, and `x` alone is a collision waiting to happen. The
		 * remaining four (email, link, print, share) are ordinary UI icons
		 * and keep plain names. Markup ported verbatim from
		 * get_icon_templates() in the source share-to-social-media plugin's
		 * shareSocialNetwork/block.php — path data copied byte for byte;
		 * only the root <svg> tag's width/height attributes were dropped
		 * (this registry injects both per call) and fill="currentColor" was
		 * added to the root. bluesky, threads and mastodon are stroke
		 * drawings whose inner element already sets
		 * fill="none" stroke="currentColor"; the root fill is inert for them.
		 */
		'socialFacebook' => array(
			'label'    => __( 'Facebook', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill="currentColor" d="M13 10h3v3h-3v7h-3v-7H7v-3h3V8.745c0-1.189.374-2.691 1.118-3.512C11.862 4.41 12.791 4 13.904 4H16v3h-2.1c-.498 0-.9.402-.9.899V10z"/></svg>',
			'keywords' => array( 'facebook', 'social', 'share' ),
		),
		'socialX'        => array(
			'label'    => __( 'X (Twitter)', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill="currentColor" d="M10.488 14.651L15.25 21h7l-7.858-10.478L20.93 3h-2.65l-5.117 5.886L8.75 3h-7l7.51 10.015L2.32 21h2.65zM16.25 19L5.75 5h2l10.5 14z"/></svg>',
			'keywords' => array( 'x', 'twitter', 'social', 'share' ),
		),
		'socialLinkedin' => array(
			'label'    => __( 'LinkedIn', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill="currentColor" fill-rule="evenodd" d="M9.429 8.969h3.714v1.85c.535-1.064 1.907-2.02 3.968-2.02c3.951 0 4.889 2.118 4.889 6.004V22h-4v-6.312c0-2.213-.535-3.461-1.897-3.461c-1.889 0-2.674 1.345-2.674 3.46V22h-4V8.969ZM2.57 21.83h4V8.799h-4V21.83ZM7.143 4.55a2.53 2.53 0 0 1-.753 1.802a2.573 2.573 0 0 1-1.82.748a2.59 2.59 0 0 1-1.818-.747A2.548 2.548 0 0 1 2 4.55c0-.677.27-1.325.753-1.803A2.583 2.583 0 0 1 4.571 2c.682 0 1.336.269 1.819.747c.482.478.753 1.126.753 1.803Z" clip-rule="evenodd"/></svg>',
			'keywords' => array( 'linkedin', 'social', 'share' ),
		),
		'socialWhatsapp' => array(
			'label'    => __( 'WhatsApp', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill="currentColor" d="M19.05 4.91A9.82 9.82 0 0 0 12.04 2c-5.46 0-9.91 4.45-9.91 9.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91c0-2.65-1.03-5.14-2.9-7.01m-7.01 15.24c-1.48 0-2.93-.4-4.2-1.15l-.3-.18l-3.12.82l.83-3.04l-.2-.31a8.26 8.26 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24c2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.83c.02 4.54-3.68 8.23-8.22 8.23m4.52-6.16c-.25-.12-1.47-.72-1.69-.81c-.23-.08-.39-.12-.56.12c-.17.25-.64.81-.78.97c-.14.17-.29.19-.54.06c-.25-.12-1.05-.39-1.99-1.23c-.74-.66-1.23-1.47-1.38-1.72c-.14-.25-.02-.38.11-.51c.11-.11.25-.29.37-.43s.17-.25.25-.41c.08-.17.04-.31-.02-.43s-.56-1.34-.76-1.84c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31c-.22.25-.86.85-.86 2.07s.89 2.4 1.01 2.56c.12.17 1.75 2.67 4.23 3.74c.59.26 1.05.41 1.41.52c.59.19 1.13.16 1.56.1c.48-.07 1.47-.6 1.67-1.18c.21-.58.21-1.07.14-1.18s-.22-.16-.47-.28"/></svg>',
			'keywords' => array( 'whatsapp', 'chat', 'social', 'share' ),
		),
		'socialBluesky'  => array(
			'label'    => __( 'Bluesky', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.335 5.144C4.681 3.945 2 3.017 2 5.97c0 .59.35 4.953.556 5.661C3.269 14.094 5.686 14.381 8 14c-4.045.665-4.889 3.208-2.667 5.41C6.363 20.428 7.246 21 8 21c2 0 3.134-2.769 3.5-3.5q.5-1 .5-1.5q0 .5.5 1.5c.366.731 1.5 3.5 3.5 3.5c.754 0 1.637-.571 2.667-1.59C20.889 17.207 20.045 14.664 16 14c2.314.38 4.73.094 5.444-2.369c.206-.708.556-5.072.556-5.661c0-2.953-2.68-2.025-4.335-.826C15.372 6.806 12.905 10.192 12 12c-.905-1.808-3.372-5.194-5.665-6.856"/></svg>',
			'keywords' => array( 'bluesky', 'social', 'share' ),
		),
		'socialThreads'  => array(
			'label'    => __( 'Threads', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7.5Q17 3 12 3c-5 0-8 2.5-8 9s3.5 9 8 9s7-3 7-5s-1-5-7-5c-2.5 0-3 1.25-3 2.5C9 15 10 16 11.5 16c2.5 0 3.5-1.5 3.5-5s-2-4-3-4s-1.833.333-2.5 1"/></svg>',
			'keywords' => array( 'threads', 'social', 'share' ),
		),
		'socialMastodon' => array(
			'label'    => __( 'Mastodon', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M7 13.5V9c0-3 5-3 5 0v3m5 1.5V9c0-3-5-3-5 0v3"/><path d="M8 17c7.5 1 13 0 13-4V9c0-5.5-4-6.5-6-6.5H9c-3 0-6.067 1-5.863 6.5c.074 1.987.036 4.385.363 7c1 8 10.5 5.5 12 5v-1.5S7.5 21 8 17"/></g></svg>',
			'keywords' => array( 'mastodon', 'social', 'share' ),
		),
		'socialSubstack' => array(
			'label'    => __( 'Substack', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"><path fill="currentColor" d="M15 3.604H1v1.891h14v-1.89ZM1 7.208V16l7-3.926L15 16V7.208zM15 0H1v1.89h14z"/></svg>',
			'keywords' => array( 'substack', 'newsletter', 'social' ),
		),
		'email'          => array(
			'label'    => __( 'Email', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4.7l-8 5.334L4 8.7V6.297l8 5.333l8-5.333V8.7z"/></svg>',
			'keywords' => array( 'email', 'mail', 'envelope', 'contact' ),
		),
		'link'           => array(
			'label'    => __( 'Link', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill="currentColor" d="M17.74 2.76a4.321 4.321 0 0 1 0 6.1l-1.53 1.52c-1.12 1.12-2.7 1.47-4.14 1.09l2.62-2.61l.76-.77l.76-.76c.84-.84.84-2.2 0-3.04a2.13 2.13 0 0 0-3.04 0l-.77.76l-3.38 3.38c-.37-1.44-.02-3.02 1.1-4.14l1.52-1.53a4.321 4.321 0 0 1 6.1 0zM8.59 13.43l5.34-5.34c.42-.42.42-1.1 0-1.52c-.44-.43-1.13-.39-1.53 0l-5.33 5.34c-.42.42-.42 1.1 0 1.52c.44.43 1.13.39 1.52 0zm-.76 2.29l4.14-4.15c.38 1.44.03 3.02-1.09 4.14l-1.52 1.53a4.321 4.321 0 0 1-6.1 0a4.321 4.321 0 0 1 0-6.1l1.53-1.52c1.12-1.12 2.7-1.47 4.14-1.1l-4.14 4.15c-.85.84-.85 2.2 0 3.05c.84.84 2.2.84 3.04 0z"/></svg>',
			'keywords' => array( 'link', 'url', 'copy', 'chain' ),
		),
		'print'          => array(
			'label'    => __( 'Print', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill="currentColor" d="M18 7H6V3h12v4Zm0 5.5q.425 0 .713-.288T19 11.5q0-.425-.288-.713T18 10.5q-.425 0-.713.288T17 11.5q0 .425.288.713T18 12.5ZM16 19v-4H8v4h8Zm2 2H6v-4H2v-6q0-1.275.875-2.138T5 8h14q1.275 0 2.138.863T22 11v6h-4v4Z"/></svg>',
			'keywords' => array( 'print', 'printer', 'paper' ),
		),
		'share'          => array(
			'label'    => __( 'Share', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill="currentColor" d="M17 22q-1.25 0-2.125-.875T14 19q0-.15.075-.7L7.05 14.2q-.4.375-.925.588T5 15q-1.25 0-2.125-.875T2 12t.875-2.125T5 9q.6 0 1.125.213t.925.587l7.025-4.1q-.05-.175-.062-.337T14 5q0-1.25.875-2.125T17 2t2.125.875T20 5t-.875 2.125T17 8q-.6 0-1.125-.213T14.95 7.2l-7.025 4.1q.05.175.063.338T8 12t-.012.363t-.063.337l7.025 4.1q.4-.375.925-.587T17 16q1.25 0 2.125.875T20 19t-.875 2.125T17 22m0-2q.425 0 .713-.287T18 19t-.288-.712T17 18t-.712.288T16 19t.288.713T17 20M5 13q.425 0 .713-.288T6 12t-.288-.712T5 11t-.712.288T4 12t.288.713T5 13m12-7q.425 0 .713-.288T18 5t-.288-.712T17 4t-.712.288T16 5t.288.713T17 6m0-1"/></svg>',
			'keywords' => array( 'share', 'native', 'system', 'nodes' ),
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
