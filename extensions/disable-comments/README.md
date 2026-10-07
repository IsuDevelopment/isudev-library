# Disable comments

Turns comments and pingbacks off for the whole site. For sites that never take
comments, where an empty Comments menu, a Discussion panel and a spam queue are
only noise.

## What it changes

- **Front end.** Comments and pings are closed on every post type, existing
  comments are hidden from `comments_template()`, and the comment count reads
  zero. The comment blocks — Comments, Comments Form, Comments Count, Comments
  Link — render nothing on a disabled post type, and Latest Comments renders
  nothing anywhere. Templates keep the blocks; only their output goes.
- **Spam endpoints.** Bots skip the form and post straight to the endpoints.
  Each one is refused with a bare `403` (plain text, no theme, no `wp_die()`
  page), before core looks up the post or runs its flood and spam checks:
  - `POST /wp-comments-post.php` — refused on `wp_loaded` when
    `comment_post_ID` is missing or points to a disabled post type; again on
    `pre_comment_on_post` in case the script is reached under another name.
  - Trackbacks — `wp-trackback.php` and `/<post>/trackback/` URLs, plus
    `pre_trackback_post`.
  - `POST /wp/v2/comments` — `403 rest_comment_closed` for a disabled post type
    (or no post), for anonymous and logged-in users alike;
    `rest_allow_anonymous_comments` is forced off.
  - XML-RPC — `pingback.ping`, `pingback.extensions.getPingbacks` and
    `wp.newComment` are removed, `xmlrpc_allow_anonymous_comments` is off.
- **Admin.** The Comments menu, Settings → Discussion and the admin-bar
  comments bubble are gone. Reaching `edit-comments.php`, `comment.php` or
  `options-discussion.php` by URL redirects to the dashboard. The dashboard
  Activity widget stops listing comments. `comments` and `trackbacks` support is
  removed from each post type, which hides the editor's Discussion panel and the
  Comments column in post lists.
- **Feeds, REST, pingbacks.** Comment feeds return 404 and are no longer linked
  from `<head>`. The REST comment collection leaves out comments on disabled post
  types. The `X-Pingback` header and the XML-RPC pingback methods are removed.

Nothing is deleted. Existing comments stay in the database, and the settings on
Settings → Discussion keep their values, ready for when the extension is
switched off.

## What it relies on

Core hooks only: `comments_open`, `pings_open`, `comments_array`,
`get_comments_number`, `rest_comment_query`, `rest_pre_dispatch`,
`rest_allow_anonymous_comments`, `pre_comment_on_post`, `pre_trackback_post`,
`xmlrpc_allow_anonymous_comments`, `wp_loaded` with `SCRIPT_FILENAME`,
`dashboard_recent_comments_query_args`, `render_block`,
`feed_links_show_comments_feed`, `wp_headers`, `xmlrpc_methods`, and the
core block names listed below. A renamed comment block in a future core release
would start rendering again — add it with the `blocks` filter.

## Hooks

### `isudev_library/extensions/disable_comments/post_types`

The post types comments are disabled on. Defaults to every registered post type.
Remove one to keep its comments — **WooCommerce product reviews are comments on
`product`**, so a shop that shows reviews needs this:

```php
add_filter(
	'isudev_library/extensions/disable_comments/post_types',
	function ( array $post_types ): array {
		return array_values( array_diff( $post_types, array( 'product' ) ) );
	}
);
```

The admin Comments screen stays hidden either way; WooCommerce moderates
reviews under **Products → Reviews**.

### `isudev_library/extensions/disable_comments/blocks`

The blocks rendered empty, as `block name => tied to the current post`. A
post-bound block (`true`) is hidden only on disabled post types; `false` hides
it everywhere.

```php
add_filter(
	'isudev_library/extensions/disable_comments/blocks',
	function ( array $blocks ): array {
		$blocks['my-plugin/comment-teaser'] = true;
		return $blocks;
	}
);
```

## When it does not work

- **A comment form still shows.** The theme or a plugin prints its own form
  without asking `comments_open()`, or uses a block not in the `blocks` list.
- **Comment counts still show.** The theme reads `$post->comment_count`
  directly instead of `get_comments_number()`.
- **Product reviews disappeared.** See the `post_types` filter above.
- **A single comment is still readable at `/wp/v2/comments/<id>`.** Only the
  collection is filtered; a single comment on a public post stays readable by
  ID.

- **Spam still arrives.** Check the access log for which URL it comes in on.
  A request that never reaches PHP (a cached page) cannot be refused here; one
  that arrives with a comment on a kept post type is allowed by design. To stop
  the traffic before WordPress loads at all, deny `wp-comments-post.php` and
  `wp-trackback.php` in the web server or the WAF as well.

## How to check it still works

On a post with existing comments: no comments, form or count on the front end;
no Comments menu or admin-bar bubble; `/wp-admin/edit-comments.php` redirects
to the dashboard; `/feed/` pages still work while `/comments/feed/` is a 404.

The endpoints, each expected to answer `403`:

```bash
curl -i -X POST -d 'comment_post_ID=1&comment=x&author=a&email=a@a.a' https://example.test/wp-comments-post.php
curl -i -X POST -d 'url=https://spam.test' 'https://example.test/wp-trackback.php?p=1'
curl -i -X POST -H 'Content-Type: application/json' -d '{"post":1,"content":"x","author_name":"a","author_email":"a@a.a"}' https://example.test/wp-json/wp/v2/comments
```
