<?php
/**
 * Disable comments: close comments and pingbacks and take them out of the admin.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\DisableComments;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// Late, so post types registered by themes and plugins on init are already there.
	\add_action( 'init', __NAMESPACE__ . '\\remove_post_type_support', 100 );

	\add_filter( 'comments_open', __NAMESPACE__ . '\\filter_open', 20, 2 );
	\add_filter( 'pings_open', __NAMESPACE__ . '\\filter_open', 20, 2 );
	\add_filter( 'comments_array', __NAMESPACE__ . '\\filter_comments', 20, 2 );
	\add_filter( 'get_comments_number', __NAMESPACE__ . '\\filter_count', 20, 2 );
	\add_filter( 'rest_comment_query', __NAMESPACE__ . '\\exclude_post_types', 20, 1 );
	\add_filter( 'render_block', __NAMESPACE__ . '\\filter_block', 20, 3 );

	\add_filter( 'feed_links_show_comments_feed', '__return_false' );
	\add_action( 'template_redirect', __NAMESPACE__ . '\\block_comment_feeds' );
	\add_filter( 'wp_headers', __NAMESPACE__ . '\\remove_pingback_header' );
	\add_filter( 'xmlrpc_methods', __NAMESPACE__ . '\\remove_pingback_methods' );
	\add_filter( 'xmlrpc_allow_anonymous_comments', '__return_false' );
	\add_filter( 'rest_allow_anonymous_comments', '__return_false' );

	// Spam bots post straight to these; refuse before core does any work.
	\add_action( 'wp_loaded', __NAMESPACE__ . '\\block_comment_endpoints', 0 );
	\add_action( 'pre_comment_on_post', __NAMESPACE__ . '\\refuse_for_post', 0, 1 );
	\add_action( 'pre_trackback_post', __NAMESPACE__ . '\\refuse_for_post', 0, 1 );
	\add_filter( 'rest_pre_dispatch', __NAMESPACE__ . '\\block_rest_create', 0, 3 );

	\add_action( 'admin_menu', __NAMESPACE__ . '\\remove_admin_menu' );
	\add_action( 'admin_init', __NAMESPACE__ . '\\block_admin_screens' );
	\add_filter( 'dashboard_recent_comments_query_args', __NAMESPACE__ . '\\exclude_post_types' );
	\add_action( 'admin_bar_menu', __NAMESPACE__ . '\\remove_admin_bar_node', 100 );
}

/**
 * Post types comments are disabled on.
 *
 * @return array List of post type slugs.
 */
function post_types(): array {
	/**
	 * Filters the post types comments are disabled on.
	 *
	 * Remove a post type to keep its comments — WooCommerce product reviews are
	 * comments on `product`.
	 *
	 * @param array $post_types List of post type slugs. Default every registered post type.
	 */
	$post_types = \apply_filters(
		'isudev_library/extensions/disable_comments/post_types',
		\array_values( \get_post_types() )
	);

	return \is_array( $post_types ) ? \array_values( \array_filter( $post_types, 'is_string' ) ) : array();
}

/**
 * Whether comments are disabled for a post.
 *
 * @param int|\WP_Post|null $post Post ID or object; null for the current post.
 * @return bool
 */
function is_disabled_for( $post ): bool {
	$post_type = \get_post_type( $post );

	// No post to ask about (a 404, an archive): treat as disabled.
	if ( false === $post_type ) {
		return true;
	}

	return \in_array( $post_type, post_types(), true );
}

/**
 * Drop comment and trackback support, which hides the Discussion panel and the
 * comment columns.
 *
 * @return void
 */
function remove_post_type_support(): void {
	foreach ( post_types() as $post_type ) {
		\remove_post_type_support( $post_type, 'comments' );
		\remove_post_type_support( $post_type, 'trackbacks' );
	}
}

/**
 * Close comments and pings. Also blocks wp-comments-post.php and REST creates,
 * which both check comments_open().
 *
 * @param bool $open    Whether open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function filter_open( $open, $post_id ): bool {
	return is_disabled_for( (int) $post_id ) ? false : (bool) $open;
}

/**
 * Hide existing comments from comments_template().
 *
 * @param array $comments Comments.
 * @param int   $post_id  Post ID.
 * @return array
 */
function filter_comments( $comments, $post_id ): array {
	if ( is_disabled_for( (int) $post_id ) ) {
		return array();
	}

	return \is_array( $comments ) ? $comments : array();
}

/**
 * Report zero comments, so themes do not print "3 comments" links.
 *
 * @param int|string $count   Comment count.
 * @param int        $post_id Post ID.
 * @return int
 */
function filter_count( $count, $post_id ): int {
	return is_disabled_for( (int) $post_id ) ? 0 : (int) $count;
}

/**
 * Keep comments on disabled post types out of the REST collection and the
 * dashboard Activity widget.
 *
 * @param array $args WP_Comment_Query arguments.
 * @return array
 */
function exclude_post_types( $args ): array {
	$args = \is_array( $args ) ? $args : array();

	$args['post_type__not_in'] = \array_values(
		\array_unique( \array_merge( (array) ( $args['post_type__not_in'] ?? array() ), post_types() ) )
	);

	return $args;
}

/**
 * Comment blocks rendered empty.
 *
 * @return array Map of block name => whether it is tied to the current post.
 */
function comment_blocks(): array {
	/**
	 * Filters the blocks rendered empty while comments are disabled.
	 *
	 * Post-bound blocks are hidden only on disabled post types; the others
	 * (Latest Comments) are hidden everywhere.
	 *
	 * @param array $blocks Map of block name => true when tied to the current post.
	 */
	$blocks = \apply_filters(
		'isudev_library/extensions/disable_comments/blocks',
		array(
			'core/comments'            => true,
			'core/post-comments-form'  => true,
			'core/post-comments-count' => true,
			'core/post-comments-link'  => true,
			'core/latest-comments'     => false,
		)
	);

	return \is_array( $blocks ) ? $blocks : array();
}

/**
 * Render comment blocks empty. Templates keep the blocks; the front end drops them.
 *
 * @param string         $block_content Rendered block.
 * @param array          $parsed_block  Parsed block.
 * @param \WP_Block|null $block         Block instance.
 * @return string
 */
function filter_block( $block_content, $parsed_block, $block = null ): string {
	$name   = \is_array( $parsed_block ) ? (string) ( $parsed_block['blockName'] ?? '' ) : '';
	$blocks = comment_blocks();

	if ( '' === $name || ! \array_key_exists( $name, $blocks ) ) {
		return (string) $block_content;
	}

	if ( ! $blocks[ $name ] ) {
		return '';
	}

	$post_id = $block instanceof \WP_Block && isset( $block->context['postId'] )
		? (int) $block->context['postId']
		: \get_the_ID();

	return is_disabled_for( $post_id ? $post_id : null ) ? '' : (string) $block_content;
}

/**
 * End the request with a bare 403. No template, no wp_die() page: the clients
 * that reach this are bots.
 *
 * @return void
 */
function refuse(): void {
	\status_header( 403 );
	\nocache_headers();
	\header( 'Content-Type: text/plain; charset=utf-8' );
	echo 'Comments are closed.';
	exit;
}

/**
 * Refuse a comment or trackback on a disabled post type.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function refuse_for_post( $post_id ): void {
	if ( is_disabled_for( (int) $post_id ) ) {
		refuse();
	}
}

/**
 * Refuse wp-comments-post.php and trackbacks (wp-trackback.php or a
 * /trackback/ URL) before core looks up the post or runs spam checks.
 *
 * @return void
 */
function block_comment_endpoints(): void {
	$script = isset( $_SERVER['SCRIPT_FILENAME'] ) ? \basename( \sanitize_text_field( \wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) ) ) : '';

	// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Refusing the request, not acting on it.
	if ( 'wp-comments-post.php' === $script ) {
		$post_id = isset( $_POST['comment_post_ID'] ) ? \absint( \wp_unslash( $_POST['comment_post_ID'] ) ) : 0;
		refuse_for_post( $post_id );
		return;
	}

	if ( 'wp-trackback.php' === $script ) {
		$post_id = isset( $_GET['p'] ) ? \absint( \wp_unslash( $_GET['p'] ) ) : 0;
		refuse_for_post( $post_id );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended

	// /post-name/trackback/ resolves in the main query; see block_comment_feeds().
}

/**
 * Refuse comment creation over REST for a disabled post type, whoever asks.
 *
 * @param mixed            $result  Response so far.
 * @param \WP_REST_Server  $server  Server.
 * @param \WP_REST_Request $request Request.
 * @return mixed
 */
function block_rest_create( $result, $server, $request ) {
	if ( null !== $result || ! $request instanceof \WP_REST_Request ) {
		return $result;
	}

	if ( 'POST' !== $request->get_method() || ! \preg_match( '#^/wp/v2/comments/?$#', $request->get_route() ) ) {
		return $result;
	}

	if ( ! is_disabled_for( (int) $request->get_param( 'post' ) ) ) {
		return $result;
	}

	return new \WP_Error( 'rest_comment_closed', \__( 'Comments are closed.', 'isudev-library' ), array( 'status' => 403 ) );
}

/**
 * Send comment feeds to a 404 and refuse trackback URLs.
 *
 * @return void
 */
function block_comment_feeds(): void {
	if ( \is_trackback() ) {
		refuse_for_post( (int) \get_queried_object_id() );
	}

	if ( ! \is_comment_feed() ) {
		return;
	}

	global $wp_query;

	$wp_query->set_404();
	\status_header( 404 );
	\nocache_headers();
}

/**
 * Drop the X-Pingback response header.
 *
 * @param array $headers Response headers.
 * @return array
 */
function remove_pingback_header( $headers ): array {
	$headers = \is_array( $headers ) ? $headers : array();
	unset( $headers['X-Pingback'] );

	return $headers;
}

/**
 * Drop the XML-RPC pingback methods and wp.newComment.
 *
 * @param array $methods XML-RPC methods.
 * @return array
 */
function remove_pingback_methods( $methods ): array {
	$methods = \is_array( $methods ) ? $methods : array();
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'], $methods['wp.newComment'] );

	return $methods;
}

/**
 * Remove Comments and Settings → Discussion from the admin menu.
 *
 * @return void
 */
function remove_admin_menu(): void {
	\remove_menu_page( 'edit-comments.php' );
	\remove_submenu_page( 'options-general.php', 'options-discussion.php' );
}

/**
 * Send anyone who reaches the comment screens by URL back to the dashboard.
 *
 * @return void
 */
function block_admin_screens(): void {
	if ( \wp_doing_ajax() ) {
		return;
	}

	global $pagenow;

	if ( ! \in_array( $pagenow, array( 'edit-comments.php', 'comment.php', 'options-discussion.php' ), true ) ) {
		return;
	}

	\wp_safe_redirect( \admin_url() );
	exit;
}

/**
 * Remove the comments bubble from the admin bar.
 *
 * @param \WP_Admin_Bar $wp_admin_bar Admin bar.
 * @return void
 */
function remove_admin_bar_node( $wp_admin_bar ): void {
	if ( $wp_admin_bar instanceof \WP_Admin_Bar ) {
		$wp_admin_bar->remove_node( 'comments' );
	}
}
