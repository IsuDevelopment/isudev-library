<?php
/**
 * Reset admin dashboard: remove core and common plugin dashboard widgets and the Welcome panel.
 *
 * Widgets this extension adds later belong in their own function hooked on wp_dashboard_setup
 * at the default priority, so the removal at 999 never touches them unless listed.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\ResetAdminDashboard;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// Late, after plugins add their widgets on wp_dashboard_setup.
	\add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\remove_widgets', 999 );
}

/**
 * Default widget IDs removed. Not `dashboard_php_nag` / `dashboard_browser_nag`: those are
 * security warnings.
 *
 * @return array
 */
function default_widgets(): array {
	return array(
		// Core, wp-admin/includes/dashboard.php.
		'dashboard_site_health',
		'dashboard_right_now',
		'dashboard_activity',
		'dashboard_quick_press',
		'dashboard_primary',
		// Yoast SEO.
		'wpseo-dashboard-overview',
		'wpseo-wincher-dashboard-overview',
		// Gravity Forms.
		'rg_forms_dashboard',
	);
}

/**
 * The widget IDs to remove, filterable. Pure apart from the filter.
 *
 * @return array List of widget IDs.
 */
function widgets(): array {
	/**
	 * Filters the dashboard widget IDs removed.
	 *
	 * @param array $widgets Widget (meta box) IDs.
	 */
	$widgets = \apply_filters( 'isudev_library/extensions/reset_admin_dashboard/widgets', default_widgets() );

	if ( ! \is_array( $widgets ) ) {
		return default_widgets();
	}

	return \array_values( \array_unique( \array_filter( $widgets, static fn( $id ): bool => \is_string( $id ) && '' !== $id ) ) );
}

/**
 * Whether the Welcome panel is removed, filterable.
 *
 * @return bool
 */
function removes_welcome_panel(): bool {
	/**
	 * Filters whether the dashboard Welcome panel is removed.
	 *
	 * @param bool $remove Default true.
	 */
	return (bool) \apply_filters( 'isudev_library/extensions/reset_admin_dashboard/remove_welcome_panel', true );
}

/**
 * Remove the listed widgets from whichever column they were added to, and the Welcome panel.
 *
 * @return void
 */
function remove_widgets(): void {
	global $wp_meta_boxes;

	$registered = isset( $wp_meta_boxes['dashboard'] ) && \is_array( $wp_meta_boxes['dashboard'] ) ? $wp_meta_boxes['dashboard'] : array();
	$contexts   = \array_unique( \array_merge( array( 'normal', 'side', 'column3', 'column4' ), \array_keys( $registered ) ) );

	foreach ( widgets() as $id ) {
		foreach ( $contexts as $context ) {
			\remove_meta_box( $id, 'dashboard', (string) $context );
		}
	}

	if ( removes_welcome_panel() ) {
		\remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}
}
