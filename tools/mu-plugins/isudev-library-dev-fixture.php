<?php
/**
 * Plugin Name: IsuDev Library — dev fixture (Local only)
 * Description: Force-activates isudev-library and seeds an e2e demo on this Local dev site. NOT for production.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/*
 * Only ever symlinked into the local dev site. Bail on a positively-different
 * HTTP host so it can never force-activate or reseed another install. An empty
 * host means CLI or cron on this install, which is allowed.
 */
$isudev_dev_host = 'isudev-library.local';
$isudev_req_host = (string) ( $_SERVER['HTTP_HOST'] ?? '' );
if ( '' !== $isudev_req_host && false === strpos( $isudev_req_host, $isudev_dev_host ) ) {
	return;
}

// Force-activate the plugin under test without writing to the DB.
add_filter(
	'option_active_plugins',
	static function ( $plugins ) {
		$slug = 'isudev-library/isudev-library.php';
		if ( is_array( $plugins ) && ! in_array( $slug, $plugins, true ) ) {
			$plugins[] = $slug;
		}
		return $plugins;
	}
);

/*
 * Seed the menu and front page once. Bump the seed version to force a reseed.
 * The three menu shapes below are what the accessibility suite distinguishes:
 * a label-only parent, a navigable parent, and plain leaves.
 */
add_action(
	'init',
	static function () {
		$seed_version = 1;
		if ( (int) get_option( 'isudev_library_dev_seed_version' ) === $seed_version ) {
			return;
		}

		$old = get_term_by( 'name', 'isudev-demo', 'nav_menu' );
		if ( $old ) {
			wp_delete_nav_menu( $old->term_id );
		}
		$menu_id = wp_create_nav_menu( 'isudev-demo' );

		// Label-only parent (URL '#') → pure disclosure button.
		$products = wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Products', 'menu-item-url' => '#', 'menu-item-status' => 'publish' ) );
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Product A', 'menu-item-url' => '/product-a', 'menu-item-parent-id' => $products, 'menu-item-status' => 'publish' ) );
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Product B', 'menu-item-url' => '/product-b', 'menu-item-parent-id' => $products, 'menu-item-status' => 'publish', 'menu-item-description' => 'Second product' ) );

		// Navigable parent (real URL) → link plus a split toggle.
		$solutions = wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Solutions', 'menu-item-url' => '/solutions', 'menu-item-status' => 'publish' ) );
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Solution X', 'menu-item-url' => '/solution-x', 'menu-item-parent-id' => $solutions, 'menu-item-status' => 'publish' ) );

		// Plain leaves.
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Pricing', 'menu-item-url' => '/pricing', 'menu-item-status' => 'publish' ) );
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'About', 'menu-item-url' => '/about', 'menu-item-status' => 'publish' ) );

		$content = '<!-- wp:isudev/site-header {"menuRef":"id:' . (int) $menu_id . '","logoSource":"site"} -->'
			. '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button -->'
			. '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact">Contact</a></div>'
			. '<!-- /wp:button --></div><!-- /wp:buttons -->'
			. '<!-- /wp:isudev/site-header -->';

		$existing = get_page_by_path( 'isudev-demo' );
		if ( $existing ) {
			wp_delete_post( $existing->ID, true );
		}
		$page_id = wp_insert_post(
			array(
				'post_title'   => 'IsuDev Demo',
				'post_name'    => 'isudev-demo',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $content,
			)
		);

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );
		update_option( 'isudev_library_dev_seed_version', $seed_version );
	},
	20
);
