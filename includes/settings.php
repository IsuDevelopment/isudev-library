<?php
/**
 * Global plugin options, exposed through the core settings REST endpoint.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Settings;

defined( 'ABSPATH' ) || exit;

const OPTION = 'isudev_library_settings';

/**
 * Default option value.
 *
 * @return array
 */
function defaults(): array {
	return array( 'loadBaseTokens' => true );
}

/**
 * Hook registration.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'init', __NAMESPACE__ . '\\register' );
}

/**
 * Register the option so @wordpress/core-data can read and write it.
 *
 * @return void
 */
function register(): void {
	\register_setting(
		'isudev_library',
		OPTION,
		array(
			'type'              => 'object',
			'default'           => defaults(),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize',
			'show_in_rest'      => array(
				'schema' => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'loadBaseTokens' => array( 'type' => 'boolean' ),
					),
				),
			),
		)
	);
}

/**
 * Sanitize the option value.
 *
 * @param mixed $value Incoming value.
 * @return array
 */
function sanitize( $value ): array {
	$value = \is_array( $value ) ? $value : array();

	return array(
		'loadBaseTokens' => ! isset( $value['loadBaseTokens'] ) || (bool) $value['loadBaseTokens'],
	);
}

/**
 * Read a single option key.
 *
 * @param string $key      Option key.
 * @param mixed  $fallback Value returned when the key is absent.
 * @return mixed
 */
function get( string $key, $fallback = null ) {
	$option = \get_option( OPTION, defaults() );
	$option = \is_array( $option ) ? $option : defaults();

	return $option[ $key ] ?? $fallback;
}
