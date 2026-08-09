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
$isudev_raw_host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
$isudev_req_host = strtolower( (string) strtok( $isudev_raw_host, ':' ) );

/*
 * Exact match, not a substring test. `strpos()` would accept a Host header like
 * `isudev-library.local.attacker.tld`, which is not a guard at all. The port is
 * stripped first so `isudev-library.local:8080` still matches.
 */
if ( '' !== $isudev_req_host && $isudev_dev_host !== $isudev_req_host ) {
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

/*
 * Provision a dedicated e2e user and write its credentials to a gitignored file.
 * Runs only on the dev host (guarded at the top of this file). The password is
 * random, local-only, and never enters the repository or a prompt.
 */
add_action(
	'init',
	static function () {
		$creds_file = __DIR__ . '/../.e2e-credentials.json';
		$login      = 'isudev-e2e';
		$user       = get_user_by( 'login', $login );

		if ( $user && is_readable( $creds_file ) ) {
			return;
		}

		/*
		 * Without a writable target the password would be set and immediately
		 * lost, and this block would regenerate it on every single request.
		 * Bail before touching the account.
		 */
		if ( ! is_writable( dirname( $creds_file ) ) ) {
			error_log( 'isudev-library dev fixture: cannot write ' . $creds_file . ' — e2e user not provisioned.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Dev-only fixture.
			return;
		}

		/*
		 * Alphanumeric only, and longer to compensate. wp_generate_password()'s
		 * special-character set includes `&` and `=`, which silently break any
		 * form-encoded login that interpolates the password into a query string —
		 * a failure that appears or disappears depending on what the generator drew.
		 * 32 alphanumeric characters is far more entropy than a local dev account needs.
		 */
		$password = wp_generate_password( 32, false, false );

		if ( $user ) {
			wp_set_password( $password, $user->ID );
		} else {
			$user_id = wp_insert_user(
				array(
					'user_login'   => $login,
					'user_pass'    => $password,
					'user_email'   => 'isudev-e2e@isudev-library.local',
					'display_name' => 'IsuDev E2E',
					'role'         => 'administrator',
				)
			);

			if ( is_wp_error( $user_id ) ) {
				return;
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Local dev credentials file, not a WP filesystem operation.
		file_put_contents( $creds_file, (string) wp_json_encode( array( 'user' => $login, 'pass' => $password ) ) );
		@chmod( $creds_file, 0600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort tightening; failure is not fatal.
	},
	21
);
