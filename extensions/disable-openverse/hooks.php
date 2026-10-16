<?php
/**
 * Disable Openverse: hide the Openverse media category in the block inserter.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\DisableOpenverse;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_filter( 'block_editor_settings_all', __NAMESPACE__ . '\\filter_settings' );
}

/**
 * Turn the Openverse media category off.
 *
 * @param array $settings Block editor settings.
 * @return array
 */
function filter_settings( $settings ): array {
	$settings = \is_array( $settings ) ? $settings : array();

	// Block editor default is true; core PHP never sets it.
	$settings['enableOpenverseMediaCategory'] = false;

	return $settings;
}
