# Disable posts

Switches the built-in **Posts** post type off everywhere: admin, front end,
feeds, REST, XML-RPC, sitemaps and the block editor. Categories, tags and post
formats go with it.

Use it on sites that carry all their content in pages and custom post types,
where an unused Posts menu and a blog nobody writes are only noise.

**Before switching it on, set a static homepage** (Settings → Reading → *A
static page*). A homepage that shows the latest posts answers with a 404 once
posts are off; Site Health flags it as critical.

## What it changes

- **Front end — a real 404.** Every request that resolves to posts gets the
  theme's 404 template with status `404` and no-cache headers, on
  `template_redirect` priority 1 — before `redirect_canonical()` (10) gets a
  chance to redirect `?p=`, `?author=` or `?cat=` to a pretty URL:
  - single posts, including `?p=<id>`, previews, `/<post>/embed/` and a post's
    own comment feed;
  - the blog index: the homepage when it shows latest posts, or the page set as
    *Posts page*;
  - date, author, category and tag archives, including `?cat=`, `?tag=`,
    `?category_name=`, `?m=`, `?year=`;
  - post format archives (`/type/aside/`);
  - the posts feed `/feed/` and the category, tag, author and date feeds.

  Search, custom post type archives and custom taxonomy archives are left alone.
  An archive whose query a theme switched to another post type (via
  `pre_get_posts`) is left alone too. When the 404 fires, WordPress's "guess a
  page from the slug" redirect and the old-slug redirect are switched off for
  that request, so a dead post URL does not bounce to a similar page.
- **Feeds in `<head>`.** The posts feed link and the category, tag and author
  feed links are no longer printed. The site-wide comments feed link stays —
  that is [Disable comments](../disable-comments/README.md)' call.
- **REST and the block editor.** `show_in_rest` is off for the post type and
  the taxonomies, so `/wp/v2/posts`, `/wp/v2/categories` and `/wp/v2/tags` are
  not registered, `/wp/v2/search` returns no posts (it only searches public
  REST post types), and the editor's Query Loop and category pickers do not
  offer them.
- **Inserter.** Latest Posts, Archives and Calendar — plus Categories List and
  Tag Cloud while their taxonomy is hidden — are hidden from the inserter
  (`supports.inserter = false`). Instances already in content or templates stay
  editable but render nothing on the front end. A Categories List or Tag Cloud
  pointed at another taxonomy keeps rendering.
- **Query Loop.** A newly inserted Query Loop queries **pages** instead of posts
  (core's default `postType` is `post`). Saved blocks keep whatever they store.
- **XML-RPC.** The post, category and tag methods are removed:
  `wp.newPost`, `wp.editPost`, `wp.deletePost`, `wp.getPost`, `wp.getPosts`,
  `wp.getPostFormats`, `wp.getPostStatusList`, `wp.getCategories`,
  `wp.getTags`, `wp.newCategory`, `wp.deleteCategory`, `wp.suggestCategories`,
  and the post methods of `blogger.*`, `metaWeblog.*` and `mt.*`. The page
  methods (`wp.getPages`, `wp.newPage`, …) and `wp.uploadFile` stay. The
  pingback methods are [Disable comments](../disable-comments/README.md)' job;
  both extensions only drop their own keys, so they combine in any order.
- **Sitemaps.** The core sitemap drops posts, the hidden taxonomies and the
  **users** sitemap (it lists author archives, which now 404). With Yoast SEO
  the post, taxonomy and author sitemaps are excluded too.
- **Admin.**
  - The Posts menu is gone, and so are the *Quick Draft*, *Recent Drafts* and
    *Activity* dashboard widgets and the "N Published posts" line in *At a
    Glance*.
  - Reaching `edit.php`, `post-new.php`, `post.php`, `edit-tags.php` or
    `term.php` for a post or a hidden taxonomy by URL redirects to the
    dashboard.
  - The Users list loses its *Posts* column.
  - Settings → Writing loses *Post via email* and *Update Services* (both only
    ever deal with posts); `wp-mail.php` refuses to run.
  - The admin bar's **+ New** loses *Post*; its top-level link already points
    at the first remaining item (core picks it), so nothing is patched there.
- **Site Health** (Tools → Site Health) gets a *Reading settings and disabled
  posts* test: critical when the homepage shows latest posts, recommended when a
  *Posts page* is set — both answer with a 404.

Nothing is deleted. Existing posts and terms stay in the database and come back
the moment the extension is switched off. No permalink flush is needed either
way: the 404s are decided per request, not by rewrite rules.

## What it relies on

Core objects and hooks: the registered `post` post type object and
`category`/`post_tag`/`post_format` taxonomy objects (rewritten on `init`
priority 1), `template_redirect`, `WP_Query::set_404()` (which keeps
`is_feed`, so the extension resets it and the feed `Content-Type` that
`WP::send_headers()` already queued), `do_redirect_guess_404_permalink`,
`wp_old_slug_redirect`, `feed_links_show_posts_feed`,
`feed_links_extra_show_{category,tag,author}_feed`, `xmlrpc_methods`,
`wp_sitemaps_post_types`, `wp_sitemaps_taxonomies`, `wp_sitemaps_add_provider`,
`register_block_type_args` (server-side block settings override the editor's),
`pre_render_block`, `enable_post_by_email_configuration`,
`enable_update_services_configuration`, `manage_users_columns`,
`site_status_tests`, the dashboard widget ids `dashboard_quick_press`,
`dashboard_recent_drafts`, `dashboard_activity`, and the At a Glance markup
`#dashboard_right_now li.post-count`. Yoast SEO: `wpseo_sitemap_exclude_post_type`,
`wpseo_sitemap_exclude_taxonomy`, `wpseo_sitemap_exclude_author`.

## Hooks

### `isudev_library/extensions/disable_posts/taxonomies`

The taxonomies hidden alongside posts. Defaults to `category`, `post_tag` and
`post_format`. Return fewer to keep some — worth doing when a custom post type
has been registered against the same taxonomies. A kept taxonomy also keeps its
Categories List / Tag Cloud block.

```php
add_filter(
	'isudev_library/extensions/disable_posts/taxonomies',
	function ( array $taxonomies ): array {
		return array( 'post_format' ); // Keep categories and tags; hide only posts.
	}
);
```

### `isudev_library/extensions/disable_posts/not_found`

Whether the current front-end request is answered with a 404. Receives the
extension's decision and the main `WP_Query`. Return `false` to let a request
through — for example a theme whose `front-page.html` never lists posts while
Settings → Reading still says *Your latest posts*:

```php
add_filter(
	'isudev_library/extensions/disable_posts/not_found',
	function ( bool $not_found, WP_Query $query ): bool {
		return $query->is_front_page() ? false : $not_found;
	},
	10,
	2
);
```

### `isudev_library/extensions/disable_posts/blocks`

The blocks hidden from the inserter and rendered empty. Default
`core/latest-posts`, `core/archives`, `core/calendar`, plus `core/categories`
while `category` is hidden and `core/tag-cloud` while `post_tag` is.

```php
add_filter(
	'isudev_library/extensions/disable_posts/blocks',
	function ( array $blocks ): array {
		$blocks[] = 'my-plugin/latest-articles';
		return $blocks;
	}
);
```

### `isudev_library/extensions/disable_posts/query_post_type`

The post type a newly inserted Query Loop starts with. Default `page`.

```php
add_filter(
	'isudev_library/extensions/disable_posts/query_post_type',
	fn() => 'case-study'
);
```

### `isudev_library/extensions/disable_posts/xmlrpc_methods`

The XML-RPC methods removed. Return fewer to keep one for a legacy client.

```php
add_filter(
	'isudev_library/extensions/disable_posts/xmlrpc_methods',
	function ( array $methods ): array {
		return array_values( array_diff( $methods, array( 'wp.getPost' ) ) );
	}
);
```

## Pairs with

- [Rename posts to articles](../post-rename/README.md) is the opposite choice —
  keep the post type, call it something else. Enabling both hides a post type
  whose labels you just changed, which is almost certainly not what you meant.
- [Disable feeds](../disable-feeds/README.md) switches off every feed,
  including page comment feeds and custom post type feeds. Both can be on.
- [Hide author archives and user lists](../redirect-authors/README.md) also
  stops user enumeration over REST and oEmbed; author archives already 404
  here.

## Not done, on purpose

- **Settings → Writing still shows *Default Post Category* and *Default Post
  Format*.** Core prints both rows without a hook (`options-writing.php`), so
  removing them would mean hacking markup. The values are harmless while posts
  are off.
- **Saved Query Loops that ask for posts** keep asking. The post type still
  exists, so they still find posts. Edit those blocks.

## When it does not work

- **The homepage is a 404.** Settings → Reading shows *Your latest posts*, or a
  *Posts page* is set. Set a static homepage, or see the `not_found` filter.
- **Posts still appear in a Query Loop.** The block stores the post type in the
  post content. Edit or remove the block.
- **The Posts menu is still there.** Another plugin registered its own menu entry
  pointing at `edit.php`. Only core's entry is removed.
- **A feed reader still gets `304 Not Modified` for `/feed/`.** `WP::send_headers()`
  answers conditional feed requests before the query runs, so a reader that
  cached the feed keeps its copy until any public content changes; then it gets
  the 404. [Disable feeds](../disable-feeds/README.md) refuses feeds early
  enough to avoid this.
- **An archive still lists posts.** A theme or plugin queries posts itself,
  outside the main query. Only the main query is checked.

## How to check it still works

Signed out, each of these must answer `404` (not `301`):

```bash
curl -sI 'https://example.test/?p=1' | head -1
curl -sI 'https://example.test/?author=1' | head -1
curl -sI 'https://example.test/?cat=1' | head -1
curl -sI 'https://example.test/feed/' | head -1
curl -sI 'https://example.test/2024/' | head -1
```

Then: `/wp-json/wp/v2/posts` is `rest_no_route`; `/wp-sitemap.xml` lists no
posts, categories, tags or users; the inserter offers no Latest Posts; a new
Query Loop lists pages; Tools → Site Health shows the test as passed.
