<?php
/**
 * Remove core patterns: drop the `core-block-patterns` theme support before core reads it.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\RemoveCorePatterns;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * Runs at init 0, after the theme's after_setup_theme (a theme re-adding the support there is
 * overridden) and before core's _register_core_block_patterns_and_categories() on init 10.
 * The same support gates the remote core and featured directory patterns.
 *
 * @return void
 */
function boot(): void {
	\remove_theme_support( 'core-block-patterns' );
}
