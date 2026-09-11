<?php
/**
 * Disable posts: take the built-in `post` type out of the admin and the front end.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\DisablePosts;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	/*
	 * Priority 1, not the register_post_type_args filter: core registers `post`
	 * in create_initial_post_types(), which runs before init fires, so that
	 * filter is already spent by the time any extension is resolved. Rewriting
	 * the registered object on init is the only hook order that works and is
	 * what WordPress itself reads for every later capability and query check.
	 */
	\add_action( 'init', __NAMESPACE__ . '\\disable_post_type', 1 );
	\add_action( 'admin_menu', __NAMESPACE__ . '\\remove_admin_menu' );
	\add_action( 'admin_init', __NAMESPACE__ . '\\block_admin_screens' );
	\add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\remove_dashboard_widgets' );
}

/**
 * Taxonomies hidden alongside posts.
 *
 * @return array List of taxonomy slugs.
 */
function taxonomies(): array {
	/**
	 * Filters the taxonomies hidden alongside posts.
	 *
	 * Return an empty array to keep categories and tags — useful when another
	 * post type has been registered against them.
	 *
	 * @param array $taxonomies List of taxonomy slugs. Default category and post_tag.
	 */
	$taxonomies = \apply_filters(
		'isudev_library/extensions/disable_posts/taxonomies',
		array( 'category', 'post_tag' )
	);

	return \is_array( $taxonomies ) ? $taxonomies : array();
}

/**
 * Hide the post type and its taxonomies everywhere.
 *
 * @return void
 */
function disable_post_type(): void {
	global $wp_post_types, $wp_taxonomies;

	if ( isset( $wp_post_types['post'] ) ) {
		$post_type = $wp_post_types['post'];

		$post_type->public              = false;
		$post_type->publicly_queryable  = false;
		$post_type->show_ui             = false;
		$post_type->show_in_menu        = false;
		$post_type->show_in_admin_bar   = false;
		$post_type->show_in_nav_menus   = false;
		$post_type->show_in_rest        = false;
		$post_type->exclude_from_search = true;
		$post_type->has_archive         = false;
		$post_type->rewrite             = false;
		$post_type->query_var           = false;
		$post_type->can_export          = false;
	}

	foreach ( taxonomies() as $taxonomy ) {
		if ( ! isset( $wp_taxonomies[ $taxonomy ] ) ) {
			continue;
		}

		$object = $wp_taxonomies[ $taxonomy ];

		$object->public             = false;
		$object->publicly_queryable = false;
		$object->show_ui            = false;
		$object->show_in_menu       = false;
		$object->show_in_nav_menus  = false;
		$object->show_in_rest       = false;
		$object->rewrite            = false;
		$object->query_var          = false;
	}
}

/**
 * Remove the Posts menu.
 *
 * `show_in_menu = false` above already stops core from adding it, so this only
 * catches an entry another plugin added back by hand.
 *
 * @return void
 */
function remove_admin_menu(): void {
	\remove_menu_page( 'edit.php' );
}

/**
 * Remove the dashboard widgets that link into posts.
 *
 * @return void
 */
function remove_dashboard_widgets(): void {
	\remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	\remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
}

/**
 * Send anyone who reaches a post admin screen by URL back to the dashboard.
 *
 * @return void
 */
function block_admin_screens(): void {
	if ( \wp_doing_ajax() ) {
		return;
	}

	global $pagenow;

	$screen = \is_string( $pagenow ) ? $pagenow : '';

	if ( ! \in_array( $screen, array( 'edit.php', 'post-new.php', 'post.php', 'edit-tags.php', 'term.php' ), true ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reading the screen being requested, not acting on input.
	$post_type = isset( $_GET['post_type'] ) ? \sanitize_key( \wp_unslash( $_GET['post_type'] ) ) : '';
	$taxonomy  = isset( $_GET['taxonomy'] ) ? \sanitize_key( \wp_unslash( $_GET['taxonomy'] ) ) : '';
	$post_id   = isset( $_GET['post'] ) ? \absint( \wp_unslash( $_GET['post'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	// edit.php and post-new.php without post_type are the post screens.
	$is_post_screen = \in_array( $screen, array( 'edit.php', 'post-new.php' ), true )
		&& ( '' === $post_type || 'post' === $post_type );

	if ( 'post.php' === $screen && $post_id > 0 ) {
		$post           = \get_post( $post_id );
		$is_post_screen = $post instanceof \WP_Post && 'post' === $post->post_type;
	}

	if ( \in_array( $screen, array( 'edit-tags.php', 'term.php' ), true ) ) {
		$is_post_screen = \in_array( $taxonomy, taxonomies(), true );
	}

	if ( ! $is_post_screen ) {
		return;
	}

	\wp_safe_redirect( \admin_url() );
	exit;
}
