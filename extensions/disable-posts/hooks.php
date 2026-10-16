<?php
/**
 * Disable posts: switch the built-in `post` type off everywhere — admin, front end,
 * feeds, REST, XML-RPC, sitemaps and the block inserter. Details in README.md.
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

	// Before redirect_canonical (10), which would otherwise redirect ?author=, ?cat= and the like.
	\add_action( 'template_redirect', __NAMESPACE__ . '\\block_front_end', 1 );

	\add_filter( 'feed_links_show_posts_feed', '__return_false' );
	\add_filter( 'feed_links_extra_show_category_feed', '__return_false' );
	\add_filter( 'feed_links_extra_show_tag_feed', '__return_false' );
	\add_filter( 'feed_links_extra_show_author_feed', '__return_false' );

	\add_filter( 'xmlrpc_methods', __NAMESPACE__ . '\\remove_xmlrpc_methods' );

	\add_filter( 'wp_sitemaps_post_types', __NAMESPACE__ . '\\remove_sitemap_post_type' );
	\add_filter( 'wp_sitemaps_taxonomies', __NAMESPACE__ . '\\remove_sitemap_taxonomies' );
	\add_filter( 'wp_sitemaps_add_provider', __NAMESPACE__ . '\\remove_users_sitemap', 10, 2 );
	\add_filter( 'wpseo_sitemap_exclude_post_type', __NAMESPACE__ . '\\yoast_exclude_post_type', 10, 2 );
	\add_filter( 'wpseo_sitemap_exclude_taxonomy', __NAMESPACE__ . '\\yoast_exclude_taxonomy', 10, 2 );
	\add_filter( 'wpseo_sitemap_exclude_author', '__return_empty_array' );

	\add_filter( 'register_block_type_args', __NAMESPACE__ . '\\filter_block_type_args', 10, 2 );
	\add_filter( 'pre_render_block', __NAMESPACE__ . '\\short_circuit_block', 10, 2 );

	\add_filter( 'enable_post_by_email_configuration', '__return_false' );
	\add_filter( 'enable_update_services_configuration', '__return_false' );

	\add_action( 'admin_menu', __NAMESPACE__ . '\\remove_admin_menu' );
	\add_action( 'admin_init', __NAMESPACE__ . '\\block_admin_screens' );
	\add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\remove_dashboard_widgets' );
	\add_action( 'admin_head-index.php', __NAMESPACE__ . '\\hide_glance_post_count' );
	\add_filter( 'manage_users_columns', __NAMESPACE__ . '\\remove_users_posts_column' );
	\add_filter( 'site_status_tests', __NAMESPACE__ . '\\add_site_health_test' );
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
	 * @param array $taxonomies List of taxonomy slugs. Default category, post_tag and post_format.
	 */
	$taxonomies = \apply_filters(
		'isudev_library/extensions/disable_posts/taxonomies',
		array( 'category', 'post_tag', 'post_format' )
	);

	return \is_array( $taxonomies ) ? \array_values( \array_filter( $taxonomies, 'is_string' ) ) : array();
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
		$object->show_tagcloud      = false;
		$object->show_in_rest       = false;
		$object->rewrite            = false;
		$object->query_var          = false;
	}
}

/**
 * Whether a query's post type means posts: empty (core's default) or exactly `post`.
 *
 * Pure.
 *
 * @param mixed $post_type The `post_type` query var.
 * @return bool
 */
function targets_posts( $post_type ): bool {
	if ( '' === $post_type || null === $post_type || array() === $post_type ) {
		return true;
	}

	return 'post' === $post_type || array( 'post' ) === $post_type;
}

/**
 * Whether the main query shows posts: a single post (also ?p=, embeds, its comment
 * feed), the blog index, date, author, category and tag archives, post-format
 * archives and the main posts feed.
 *
 * Search, post type archives and custom taxonomy archives belong to other post
 * types and are left alone.
 *
 * @param \WP_Query $query Main query.
 * @return bool
 */
function shows_posts( \WP_Query $query ): bool {
	if ( $query->is_404() || $query->is_robots() || $query->is_favicon() || $query->is_sitemap() || '' !== (string) $query->get( 'sitemap-stylesheet' ) ) {
		return false;
	}

	if ( $query->is_singular() ) {
		return $query->is_singular( 'post' );
	}

	if ( $query->is_search() || $query->is_post_type_archive() ) {
		return false;
	}

	if ( $query->is_tax() ) {
		return $query->is_tax( 'post_format' );
	}

	// The site-wide comments feed is disable-comments' job.
	if ( $query->is_comment_feed() ) {
		return false;
	}

	if ( ! ( $query->is_home() || $query->is_archive() || $query->is_feed() ) ) {
		return false;
	}

	return targets_posts( $query->get( 'post_type' ) );
}

/**
 * Send any front-end request that resolves to posts to the theme's 404.
 *
 * @return void
 */
function block_front_end(): void {
	global $wp_query;

	if ( ! $wp_query instanceof \WP_Query ) {
		return;
	}

	/**
	 * Filters whether the current request is answered with a 404.
	 *
	 * @param bool      $not_found Whether the request shows posts.
	 * @param \WP_Query $wp_query  Main query.
	 */
	if ( ! \apply_filters( 'isudev_library/extensions/disable_posts/not_found', shows_posts( $wp_query ), $wp_query ) ) {
		return;
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

	// Neither guess a page from the post slug nor follow an old post slug.
	\add_filter( 'do_redirect_guess_404_permalink', '__return_false' );
	\remove_action( 'template_redirect', 'wp_old_slug_redirect' );
}

/**
 * XML-RPC methods removed: everything that reads or writes posts, categories or tags.
 *
 * @return array List of method names.
 */
function xmlrpc_methods(): array {
	/**
	 * Filters the XML-RPC methods removed while posts are disabled.
	 *
	 * @param array $methods Method names.
	 */
	$methods = \apply_filters(
		'isudev_library/extensions/disable_posts/xmlrpc_methods',
		array(
			'wp.newPost',
			'wp.editPost',
			'wp.deletePost',
			'wp.getPost',
			'wp.getPosts',
			'wp.getPostFormats',
			'wp.getPostStatusList',
			'wp.getCategories',
			'wp.getTags',
			'wp.newCategory',
			'wp.deleteCategory',
			'wp.suggestCategories',
			'blogger.getPost',
			'blogger.getRecentPosts',
			'blogger.newPost',
			'blogger.editPost',
			'blogger.deletePost',
			'metaWeblog.newPost',
			'metaWeblog.editPost',
			'metaWeblog.getPost',
			'metaWeblog.getRecentPosts',
			'metaWeblog.getCategories',
			'metaWeblog.deletePost',
			'mt.getCategoryList',
			'mt.getRecentPostTitles',
			'mt.getPostCategories',
			'mt.setPostCategories',
			'mt.getTrackbackPings',
			'mt.publishPost',
		)
	);

	return \is_array( $methods ) ? \array_values( \array_filter( $methods, 'is_string' ) ) : array();
}

/**
 * Drop methods from an XML-RPC method map. Pure; order-independent with other
 * extensions that unset their own keys.
 *
 * @param array $methods Method name => callback.
 * @param array $remove  Method names.
 * @return array
 */
function without_methods( array $methods, array $remove ): array {
	return \array_diff_key( $methods, \array_flip( $remove ) );
}

/**
 * Remove the post XML-RPC methods.
 *
 * @param array $methods XML-RPC methods.
 * @return array
 */
function remove_xmlrpc_methods( $methods ): array {
	return without_methods( \is_array( $methods ) ? $methods : array(), xmlrpc_methods() );
}

/**
 * Keep posts out of the core sitemap. Already non-public; this survives a plugin
 * that flips the flag back.
 *
 * @param array $post_types Post type objects keyed by name.
 * @return array
 */
function remove_sitemap_post_type( $post_types ): array {
	$post_types = \is_array( $post_types ) ? $post_types : array();
	unset( $post_types['post'] );

	return $post_types;
}

/**
 * Keep the hidden taxonomies out of the core sitemap.
 *
 * @param array $taxonomies Taxonomy objects keyed by name.
 * @return array
 */
function remove_sitemap_taxonomies( $taxonomies ): array {
	$taxonomies = \is_array( $taxonomies ) ? $taxonomies : array();

	return \array_diff_key( $taxonomies, \array_flip( taxonomies() ) );
}

/**
 * Drop the core users sitemap: it lists author archives, which now 404.
 *
 * @param mixed  $provider Sitemap provider.
 * @param string $name     Provider name.
 * @return mixed False for `users`.
 */
function remove_users_sitemap( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}

/**
 * Yoast SEO: no post sitemap.
 *
 * @param bool   $exclude   Whether excluded.
 * @param string $post_type Post type.
 * @return bool
 */
function yoast_exclude_post_type( $exclude, $post_type ): bool {
	return 'post' === $post_type ? true : (bool) $exclude;
}

/**
 * Yoast SEO: no sitemap for the hidden taxonomies.
 *
 * @param bool   $exclude  Whether excluded.
 * @param string $taxonomy Taxonomy.
 * @return bool
 */
function yoast_exclude_taxonomy( $exclude, $taxonomy ): bool {
	return \in_array( $taxonomy, taxonomies(), true ) ? true : (bool) $exclude;
}

/**
 * Blocks that only ever list posts, removed from the inserter and rendered empty.
 *
 * @return array List of block names.
 */
function blocks(): array {
	$blocks = array( 'core/latest-posts', 'core/archives', 'core/calendar' );

	if ( \in_array( 'category', taxonomies(), true ) ) {
		$blocks[] = 'core/categories';
	}

	if ( \in_array( 'post_tag', taxonomies(), true ) ) {
		$blocks[] = 'core/tag-cloud';
	}

	/**
	 * Filters the blocks hidden from the inserter and rendered empty.
	 *
	 * @param array $blocks Block names. Default Latest Posts, Archives, Calendar,
	 *                      plus Categories List and Tag Cloud when their taxonomy is hidden.
	 */
	$blocks = \apply_filters( 'isudev_library/extensions/disable_posts/blocks', $blocks );

	return \is_array( $blocks ) ? \array_values( \array_filter( $blocks, 'is_string' ) ) : array();
}

/**
 * Post type a new Query Loop starts with.
 *
 * @return string
 */
function query_post_type(): string {
	/**
	 * Filters the post type a newly inserted Query Loop queries. Core's default is `post`.
	 *
	 * @param string $post_type Default `page`.
	 */
	$post_type = \apply_filters( 'isudev_library/extensions/disable_posts/query_post_type', 'page' );
	$post_type = \is_string( $post_type ) ? \sanitize_key( $post_type ) : '';

	return '' === $post_type ? 'page' : $post_type;
}

/**
 * Hide post-only blocks from the inserter, and point the Query Loop's default at pages.
 *
 * Server-side block settings win over the editor's own metadata, so this reaches
 * the inserter without editor JS. Existing block instances stay editable.
 *
 * @param array  $args       Block type arguments.
 * @param string $block_name Block name.
 * @return array
 */
function filter_block_type_args( $args, $block_name ): array {
	$args = \is_array( $args ) ? $args : array();

	if ( \in_array( $block_name, blocks(), true ) ) {
		$args['supports']             = isset( $args['supports'] ) && \is_array( $args['supports'] ) ? $args['supports'] : array();
		$args['supports']['inserter'] = false;
	}

	if ( 'core/query' === $block_name && isset( $args['attributes']['query']['default'] ) && \is_array( $args['attributes']['query']['default'] ) ) {
		$args['attributes']['query']['default']['postType'] = query_post_type();
	}

	return $args;
}

/**
 * Render a post-only block as nothing. Categories List and Tag Cloud keep
 * rendering when pointed at a taxonomy that is not hidden.
 *
 * @param string|null $pre_render   Short-circuited output.
 * @param array       $parsed_block Parsed block.
 * @return string|null
 */
function short_circuit_block( $pre_render, $parsed_block ) {
	$name = \is_array( $parsed_block ) ? (string) ( $parsed_block['blockName'] ?? '' ) : '';

	if ( null !== $pre_render || '' === $name || ! \in_array( $name, blocks(), true ) ) {
		return $pre_render;
	}

	$taxonomy = $parsed_block['attrs']['taxonomy'] ?? null;

	if ( null === $taxonomy ) {
		$type     = \WP_Block_Type_Registry::get_instance()->get_registered( $name );
		$taxonomy = $type && isset( $type->attributes['taxonomy']['default'] ) ? $type->attributes['taxonomy']['default'] : null;
	}

	if ( \is_string( $taxonomy ) && ! \in_array( $taxonomy, taxonomies(), true ) ) {
		return $pre_render;
	}

	return '';
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
 * Remove the dashboard widgets that are about posts. Activity lists recent posts;
 * its comment list moves to the Comments screen.
 *
 * @return void
 */
function remove_dashboard_widgets(): void {
	\remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	\remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
	\remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
}

/**
 * At a Glance prints its "N Published posts" line unconditionally
 * (wp_dashboard_right_now(), no filter), linking to a screen that now redirects.
 *
 * @return void
 */
function hide_glance_post_count(): void {
	echo '<style>#dashboard_right_now li.post-count{display:none}</style>' . "\n";
}

/**
 * Drop the Posts count column from Users.
 *
 * @param array $columns Columns.
 * @return array
 */
function remove_users_posts_column( $columns ): array {
	$columns = \is_array( $columns ) ? $columns : array();
	unset( $columns['posts'] );

	return $columns;
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

/**
 * Register the Site Health test.
 *
 * @param array $tests Site Health tests.
 * @return array
 */
function add_site_health_test( $tests ): array {
	$tests = \is_array( $tests ) ? $tests : array();

	$tests['direct']['isudev_library_disable_posts'] = array(
		'label' => \__( 'Reading settings and disabled posts', 'isudev-library' ),
		'test'  => __NAMESPACE__ . '\\site_health_test',
	);

	return $tests;
}

/**
 * Warn when Settings → Reading still points the homepage or a page at the post list,
 * which this extension answers with a 404.
 *
 * @return array Site Health result.
 */
function site_health_test(): array {
	$static_front = 'page' === \get_option( 'show_on_front' ) && (int) \get_option( 'page_on_front' ) > 0;
	$posts_page   = $static_front ? (int) \get_option( 'page_for_posts' ) : 0;
	$reading_link = \sprintf(
		'<p><a href="%1$s">%2$s</a></p>',
		\esc_url( \admin_url( 'options-reading.php' ) ),
		\esc_html__( 'Open Settings → Reading', 'isudev-library' )
	);
	$result       = array(
		'label'       => \__( 'The homepage does not depend on posts', 'isudev-library' ),
		'status'      => 'good',
		'badge'       => array(
			'label' => \__( 'Content', 'isudev-library' ),
			'color' => 'blue',
		),
		'description' => \sprintf(
			'<p>%s</p>',
			\esc_html__( 'Posts are disabled, and Settings → Reading shows a static page as the homepage.', 'isudev-library' )
		),
		'actions'     => '',
		'test'        => 'isudev_library_disable_posts',
	);

	if ( ! $static_front ) {
		$result['label']          = \__( 'The homepage shows latest posts, but posts are disabled', 'isudev-library' );
		$result['status']         = 'critical';
		$result['badge']['color'] = 'red';
		$result['description']    = \sprintf(
			'<p>%s</p>',
			\esc_html__( 'With posts disabled, a homepage set to show the latest posts answers with “Page not found”. Choose a static page as the homepage.', 'isudev-library' )
		);
		$result['actions']        = $reading_link;
	} elseif ( $posts_page > 0 ) {
		$result['label']       = \__( 'A posts page is set, but posts are disabled', 'isudev-library' );
		$result['status']      = 'recommended';
		$result['description'] = \sprintf(
			'<p>%s</p>',
			\esc_html(
				\sprintf(
					/* translators: %s: Page title. */
					\__( 'The page “%s” is set as the posts page, so it answers with “Page not found”. Set Posts page to “— Select —”.', 'isudev-library' ),
					\get_the_title( $posts_page )
				)
			)
		);
		$result['actions'] = $reading_link;
	}

	return $result;
}
