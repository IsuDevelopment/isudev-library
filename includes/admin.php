<?php
/**
 * Admin screen: menu registration, visibility gate and panel assets.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Admin;

use const IsuDevLibrary\PATH;
use const IsuDevLibrary\URL;
use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const MENU_SLUG = 'isudev-library';

/**
 * Hook registration.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'admin_menu', __NAMESPACE__ . '\\register_menu' );
	\add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue' );
}

/**
 * Capability required to view and change the library configuration.
 *
 * @return string
 */
function capability(): string {
	/**
	 * Filters the capability guarding the admin screen and the REST endpoints.
	 *
	 * @param string $capability Default 'manage_options'.
	 */
	$capability = \apply_filters( 'isudev_library/settings/capability', 'manage_options' );

	return \is_string( $capability ) && '' !== $capability ? $capability : 'manage_options';
}

/**
 * Whether the current user may see the admin panel.
 *
 * This also gates the REST endpoints, so returning false actually closes write
 * access rather than only hiding the menu.
 *
 * @return bool
 */
function show_admin(): bool {
	/**
	 * Filters whether the library admin panel is available to the current user.
	 *
	 * Mirrors the ACF `acf/settings/show_admin` pattern.
	 *
	 * @param bool $show Default current_user_can( capability() ).
	 */
	return (bool) \apply_filters( 'isudev_library/settings/show_admin', \current_user_can( capability() ) );
}

/**
 * Register the top-level menu page.
 *
 * @return void
 */
function register_menu(): void {
	if ( ! show_admin() ) {
		return;
	}

	\add_menu_page(
		\__( 'IsuDev Library', 'isudev-library' ),
		\__( 'IsuDev Library', 'isudev-library' ),
		capability(),
		MENU_SLUG,
		__NAMESPACE__ . '\\render_page',
		'dashicons-screenoptions',
		58
	);
}

/**
 * Render the mount point for the React panel.
 *
 * @return void
 */
function render_page(): void {
	echo '<div class="wrap"><div id="isudev-library-admin"></div></div>';
}

/**
 * Enqueue the panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 * @return void
 */
function enqueue( string $hook_suffix ): void {
	if ( 'toplevel_page_' . MENU_SLUG !== $hook_suffix || ! show_admin() ) {
		return;
	}

	$asset_file = PATH . 'build/admin/index.asset.php';

	if ( ! \is_readable( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	\wp_enqueue_script(
		'isudev-library-admin',
		URL . 'build/admin/index.js',
		$asset['dependencies'] ?? array(),
		$asset['version'] ?? VERSION,
		true
	);

	\wp_set_script_translations( 'isudev-library-admin', 'isudev-library', PATH . 'languages' );

	if ( \is_readable( PATH . 'build/admin/index.css' ) ) {
		\wp_enqueue_style(
			'isudev-library-admin',
			URL . 'build/admin/index.css',
			array( 'wp-components' ),
			$asset['version'] ?? VERSION
		);
	}
}
