<?php
/**
 * Hide author archives and user lists: author archives 404 (or 301 to a chosen
 * URL), and users cannot be enumerated over REST, oEmbed or sitemaps. Details in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\RedirectAuthors;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// Before redirect_canonical (10), which redirects ?author=<id> to /author/<login>/.
	\add_action( 'template_redirect', __NAMESPACE__ . '\\block_author_archives', 1 );

	\add_filter( 'rest_pre_dispatch', __NAMESPACE__ . '\\block_rest_users', 10, 3 );
	\add_filter( 'oembed_response_data', __NAMESPACE__ . '\\anonymize_oembed', 10, 2 );
	\add_filter( 'wp_sitemaps_add_provider', __NAMESPACE__ . '\\remove_users_sitemap', 10, 2 );
	\add_filter( 'wpseo_sitemap_exclude_author', '__return_empty_array' );
	\add_filter( 'render_block_data', __NAMESPACE__ . '\\strip_author_link' );
}

/**
 * Where author archives redirect to; '' answers them with a 404.
 *
 * @return string Validated URL, or ''.
 */
function redirect_url(): string {
	/**
	 * Filters where author archives are redirected (301). Default '' — a 404.
	 *
	 * Only URLs wp_validate_redirect() accepts are used: this site, or a host
	 * added through allowed_redirect_hosts.
	 *
	 * @param string $url Redirect target.
	 */
	$url = \apply_filters( 'isudev_library/extensions/redirect_authors/redirect_url', '' );

	return \is_string( $url ) && '' !== $url ? (string) \wp_validate_redirect( $url, '' ) : '';
}

/**
 * Answer author archives — also ?author=<id>, ?author_name= and author feeds —
 * the same way whether the user exists or not.
 *
 * @return void
 */
function block_author_archives(): void {
	global $wp_query;

	if ( ! $wp_query instanceof \WP_Query ) {
		return;
	}

	$requested = '' !== (string) $wp_query->get( 'author' ) || '' !== (string) $wp_query->get( 'author_name' );

	if ( ! $wp_query->is_author() && ! $requested ) {
		return;
	}

	$target = redirect_url();

	if ( '' !== $target ) {
		\wp_safe_redirect( $target, 301, 'IsuDev Library' );
		exit;
	}

	$wp_query->set_404();

	// set_404() keeps is_feed, and template-loader.php would still call do_feed().
	$wp_query->is_feed = false;

	\status_header( 404 );
	\nocache_headers();

	if ( ! \headers_sent() ) {
		// WP::send_headers() already queued a feed's Content-Type and ETag.
		\header( 'Content-Type: ' . \get_option( 'html_type' ) . '; charset=' . \get_option( 'blog_charset' ) );
		\header_remove( 'ETag' );
	}
}

/**
 * The user ID a REST route reads: 0 for the collection, the ID for one user,
 * null for any other route (including /wp/v2/users/me). Pure.
 *
 * @param string $route REST route.
 * @return int|null
 */
function users_route_id( string $route ): ?int {
	if ( 1 !== \preg_match( '#^/wp/v2/users(?:/(\d+))?/?$#', $route, $matches ) ) {
		return null;
	}

	return isset( $matches[1] ) ? (int) $matches[1] : 0;
}

/**
 * Whether the current user may read users over REST.
 *
 * @param int $user_id The user asked for; 0 for the collection.
 * @return bool
 */
function can_read_users( int $user_id ): bool {
	if ( ! \is_user_logged_in() ) {
		return false;
	}

	if ( $user_id > 0 && \get_current_user_id() === $user_id ) {
		return true;
	}

	/**
	 * Filters the capability needed to read users over REST.
	 *
	 * The default lets anyone who writes content keep the block editor's author
	 * picker (/wp/v2/users?who=authors) and author display.
	 *
	 * @param string $capability Default `edit_posts`.
	 */
	$capability = \apply_filters( 'isudev_library/extensions/redirect_authors/rest_capability', 'edit_posts' );

	return \is_string( $capability ) && '' !== $capability && \current_user_can( $capability );
}

/**
 * Refuse reading /wp/v2/users and /wp/v2/users/<id> to anyone without the capability.
 * Also applies to `_embed` and batch requests, which dispatch through the same filter.
 *
 * @param mixed            $result  Response so far.
 * @param \WP_REST_Server  $server  Server.
 * @param \WP_REST_Request $request Request.
 * @return mixed
 */
function block_rest_users( $result, $server, $request ) {
	if ( null !== $result || ! $request instanceof \WP_REST_Request ) {
		return $result;
	}

	// Writes already need create_users / edit_users.
	if ( ! \in_array( $request->get_method(), array( 'GET', 'HEAD' ), true ) ) {
		return $result;
	}

	$user_id = users_route_id( $request->get_route() );

	if ( null === $user_id || can_read_users( $user_id ) ) {
		return $result;
	}

	return new \WP_Error(
		'rest_user_cannot_view',
		\__( 'Sorry, you are not allowed to list users.', 'isudev-library' ),
		array( 'status' => \rest_authorization_required_code() )
	);
}

/**
 * Name the site in oEmbed responses, not the post author and their archive URL.
 *
 * @param array    $data The oEmbed response data.
 * @param \WP_Post $post The post.
 * @return array
 */
function anonymize_oembed( $data, $post ): array {
	unset( $post );
	$data = \is_array( $data ) ? $data : array();

	if ( isset( $data['author_name'] ) || isset( $data['author_url'] ) ) {
		$data['author_name'] = \get_bloginfo( 'name' );
		$data['author_url']  = \get_home_url();
	}

	return $data;
}

/**
 * Drop the core users sitemap: it lists every author archive.
 *
 * @param mixed  $provider Sitemap provider.
 * @param string $name     Provider name.
 * @return mixed False for `users`.
 */
function remove_users_sitemap( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}

/**
 * Author blocks whose link points at the author archive.
 *
 * @return array Block names.
 */
function linked_blocks(): array {
	/**
	 * Filters the blocks whose "Link to author archive" setting is ignored.
	 * Return an empty array to keep the links (for a 301 target, say).
	 *
	 * @param array $blocks Default core/post-author, core/post-author-name.
	 */
	$blocks = \apply_filters(
		'isudev_library/extensions/redirect_authors/unlinked_blocks',
		array( 'core/post-author', 'core/post-author-name' )
	);

	return \is_array( $blocks ) ? \array_values( \array_filter( $blocks, 'is_string' ) ) : array();
}

/**
 * Render author blocks without the link to an archive that no longer exists.
 *
 * @param array $parsed_block Parsed block.
 * @return array
 */
function strip_author_link( $parsed_block ): array {
	$parsed_block = \is_array( $parsed_block ) ? $parsed_block : array();
	$name         = (string) ( $parsed_block['blockName'] ?? '' );

	if ( ! empty( $parsed_block['attrs']['isLink'] ) && \in_array( $name, linked_blocks(), true ) ) {
		$parsed_block['attrs']['isLink'] = false;
	}

	return $parsed_block;
}
