<?php
/**
 * REST endpoint exposing Google review accounts to the block editor.
 *
 * Read-only and available to any user who can edit posts — narrower than
 * IsuDevLibrary\REST's `manage_options`-gated endpoints, because every
 * Google Reviews block's own editor UI needs this to populate its account
 * picker, not just the admin settings panel.
 *
 * @package IsuDevLibrary\GoogleReviews
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\GoogleReviews\Rest;

use function IsuDevLibrary\GoogleReviews\find_accounts;

use const IsuDevLibrary\REST\NAMESPACE_V1;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );
}

/**
 * Whether the current user may read the account list.
 *
 * @return bool
 */
function permission_check(): bool {
	return \current_user_can( 'edit_posts' );
}

/**
 * Register the route.
 *
 * @return void
 */
function register_routes(): void {
	\register_rest_route(
		NAMESPACE_V1,
		'/google-review-accounts',
		array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => __NAMESPACE__ . '\\get_accounts',
			'permission_callback' => __NAMESPACE__ . '\\permission_check',
		)
	);
}

/**
 * GET /google-review-accounts
 *
 * @return \WP_REST_Response
 */
function get_accounts(): \WP_REST_Response {
	global $wpdb;

	return \rest_ensure_response( find_accounts( $wpdb ) );
}
