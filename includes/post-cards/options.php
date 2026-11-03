<?php
/**
 * Card options: definitions, sanitising and the card → wrapper → default cascade.
 *
 * Contract and examples: guides/post-cards.md → "Card options".
 *
 * @package IsuDevLibrary\PostCards
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\PostCards;

defined( 'ABSPATH' ) || exit;

const OPTION_TYPES  = array( 'boolean', 'select', 'text', 'media' );
const OPTION_SCOPES = array( 'card', 'cards', 'both' );

/**
 * Normalise one option definition. Pure.
 *
 * @param string $key Option key, `[A-Za-z][A-Za-z0-9]*`.
 * @param array  $def Raw definition; see guides/post-cards.md.
 * @return array|null Normalised definition, or null when invalid.
 */
function normalize_option( string $key, array $def ): ?array {
	$type = isset( $def['type'] ) && \in_array( $def['type'], OPTION_TYPES, true ) ? $def['type'] : '';

	if ( '' === $type || 1 !== \preg_match( '/^[A-Za-z][A-Za-z0-9]*$/', $key ) ) {
		return null;
	}

	$choices = array();
	if ( 'select' === $type ) {
		foreach ( (array) ( $def['choices'] ?? array() ) as $value => $choice ) {
			if ( \is_array( $choice ) && isset( $choice['value'] ) ) {
				$choices[] = array(
					'value' => (string) $choice['value'],
					'label' => (string) ( $choice['label'] ?? $choice['value'] ),
				);
			} elseif ( \is_string( $choice ) ) {
				$choices[] = array(
					'value' => \is_string( $value ) ? $value : $choice,
					'label' => $choice,
				);
			}
		}

		if ( array() === $choices ) {
			return null;
		}
	}

	$controls = array(
		'boolean' => array( 'toggle' ),
		'select'  => array( 'select', 'radio' ),
		'text'    => array( 'text', 'textarea' ),
		'media'   => array( 'media' ),
	);
	$control  = isset( $def['control'] ) && \in_array( $def['control'], $controls[ $type ], true ) ? $def['control'] : $controls[ $type ][0];

	$normalized = array(
		'key'       => $key,
		'type'      => $type,
		'label'     => isset( $def['label'] ) && \is_string( $def['label'] ) ? $def['label'] : $key,
		'help'      => isset( $def['help'] ) && \is_string( $def['help'] ) ? $def['help'] : '',
		'control'   => $control,
		'choices'   => $choices,
		'postTypes' => \array_values( \array_filter( (array) ( $def['postTypes'] ?? array() ), 'is_string' ) ),
		'scope'     => isset( $def['scope'] ) && \in_array( $def['scope'], OPTION_SCOPES, true ) ? $def['scope'] : 'both',
		'default'   => null,
	);

	$normalized['default'] = sanitize_option_value( $normalized, $def['default'] ?? null );

	if ( 'boolean' === $type && null === $normalized['default'] ) {
		$normalized['default'] = false;
	}

	return $normalized;
}

/**
 * Coerce a stored value to its option's type. Pure.
 *
 * @param array $def   Normalised definition.
 * @param mixed $value Stored value.
 * @return mixed Sanitised value, or null when unset or invalid.
 */
function sanitize_option_value( array $def, $value ) {
	if ( null === $value ) {
		return null;
	}

	switch ( $def['type'] ) {
		case 'boolean':
			return \is_bool( $value ) ? $value : ( \is_scalar( $value ) ? (bool) $value : null );

		case 'select':
			$allowed = \array_column( $def['choices'], 'value' );
			return \is_scalar( $value ) && \in_array( (string) $value, $allowed, true ) ? (string) $value : null;

		case 'text':
			if ( ! \is_scalar( $value ) ) {
				return null;
			}
			$text = \trim( \wp_strip_all_tags( (string) $value ) );
			return '' === $text ? null : $text;

		case 'media':
			return sanitize_media_value( $value );
	}

	return null;
}

/**
 * Sanitise a media option value. Pure.
 *
 * @param mixed $value `{ id, url, alt, focalPoint: { x, y } }`.
 * @return array|null
 */
function sanitize_media_value( $value ): ?array {
	if ( ! \is_array( $value ) ) {
		return null;
	}

	$id  = isset( $value['id'] ) ? \max( 0, (int) $value['id'] ) : 0;
	$url = isset( $value['url'] ) && \is_string( $value['url'] ) ? \trim( $value['url'] ) : '';

	if ( 0 === $id && '' === $url ) {
		return null;
	}

	$media = array(
		'id'  => $id,
		'url' => $url,
		'alt' => isset( $value['alt'] ) && \is_string( $value['alt'] ) ? \trim( \wp_strip_all_tags( $value['alt'] ) ) : '',
	);

	if ( isset( $value['focalPoint'] ) && \is_array( $value['focalPoint'] ) ) {
		$media['focalPoint'] = array(
			'x' => \max( 0.0, \min( 1.0, (float) ( $value['focalPoint']['x'] ?? 0.5 ) ) ),
			'y' => \max( 0.0, \min( 1.0, (float) ( $value['focalPoint']['y'] ?? 0.5 ) ) ),
		);
	}

	return $media;
}

/**
 * Resolve the options that apply to one card. Pure.
 *
 * Order: card value, then wrapper value, then the definition's default. An
 * option limited to other post types is left out entirely.
 *
 * @param array  $defs      Normalised definitions keyed by option key.
 * @param array  $wrapper   `cardOptions` stored on `isudev/post-cards`.
 * @param array  $card      `cardOptions` stored on `isudev/post-card`.
 * @param string $post_type The card's post type.
 * @return array key => value. Unset text and media options are null.
 */
function resolve_options( array $defs, array $wrapper, array $card, string $post_type ): array {
	$resolved = array();

	foreach ( $defs as $key => $def ) {
		if ( array() !== $def['postTypes'] && ! \in_array( $post_type, $def['postTypes'], true ) ) {
			continue;
		}

		$value = null;

		if ( 'cards' !== $def['scope'] ) {
			$value = sanitize_option_value( $def, $card[ $key ] ?? null );
		}

		if ( null === $value && 'card' !== $def['scope'] ) {
			$value = sanitize_option_value( $def, $wrapper[ $key ] ?? null );
		}

		$resolved[ $key ] = null === $value ? $def['default'] : $value;
	}

	return $resolved;
}

/**
 * Class names for resolved options: `has-card-{key}` for a true boolean,
 * `has-card-{key}-{value}` for a select. Keys are kebab-cased. Pure.
 *
 * @param array $defs     Normalised definitions keyed by option key.
 * @param array $resolved Output of resolve_options().
 * @return string[]
 */
function option_classes( array $defs, array $resolved ): array {
	$classes = array();

	foreach ( $resolved as $key => $value ) {
		$type = $defs[ $key ]['type'] ?? '';
		$slug = kebab( (string) $key );

		if ( 'boolean' === $type && true === $value ) {
			$classes[] = 'has-card-' . $slug;
		} elseif ( 'select' === $type && \is_string( $value ) ) {
			$value = \strtolower( (string) \preg_replace( '/[^A-Za-z0-9_-]+/', '-', $value ) );
			if ( '' !== \trim( $value, '-' ) ) {
				$classes[] = 'has-card-' . $slug . '-' . $value;
			}
		}
	}

	return $classes;
}

/**
 * `hideExcerpt` → `hide-excerpt`. Pure.
 *
 * @param string $key camelCase key.
 * @return string
 */
function kebab( string $key ): string {
	return \strtolower( (string) \preg_replace( '/(?<!^)[A-Z]/', '-$0', $key ) );
}

/**
 * Definitions the library ships. Read by `isudev/card-title`, `card-excerpt`
 * and `card-image`. Pure apart from translation.
 *
 * @return array key => raw definition.
 */
function builtin_options(): array {
	return array(
		'customTitle'   => array(
			'type'  => 'text',
			'label' => __( 'Custom title', 'isudev-library' ),
			'help'  => __( 'Replaces the post title on this card only.', 'isudev-library' ),
			'scope' => 'card',
		),
		'customExcerpt' => array(
			'type'    => 'text',
			'control' => 'textarea',
			'label'   => __( 'Custom excerpt', 'isudev-library' ),
			'help'    => __( 'Replaces the post excerpt on this card only.', 'isudev-library' ),
			'scope'   => 'card',
		),
		'hideExcerpt'   => array(
			'type'  => 'boolean',
			'label' => __( 'Hide excerpt', 'isudev-library' ),
		),
		'customImage'   => array(
			'type'  => 'media',
			'label' => __( 'Custom image', 'isudev-library' ),
			'help'  => __( 'Replaces the featured image on this card only.', 'isudev-library' ),
			'scope' => 'card',
		),
	);
}
