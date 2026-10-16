<?php
/**
 * Disable application passwords. Details in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\DisableApplicationPasswords;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// wp_is_application_passwords_available_for_user() checks this first, so one filter covers both.
	\add_filter( 'wp_is_application_passwords_available', '__return_false', 100 );
}
