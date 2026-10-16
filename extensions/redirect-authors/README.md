# Hide author archives and user lists

Stops a site from telling strangers who its users are. Out of the box WordPress
hands out login names in several places — `/?author=1` redirects to
`/author/<login>/`, `/wp-json/wp/v2/users` lists every author, oEmbed names the
author, the users sitemap lists them all — which is half of what a
password-guessing bot needs.

The slug is `redirect-authors` (parity with the T2 extension it replaces), but
it no longer redirects to the homepage by default: it answers with a 404.

## What it changes

- **Author archives answer 404.** `/author/<name>/`, `?author=<id>`,
  `?author_name=<name>` and author feeds get the theme's 404 template with
  status `404` and no-cache headers, on `template_redirect` priority 1 —
  before `redirect_canonical()` (10), which would otherwise redirect
  `?author=1` to `/author/<login>/` and leak the login name (core does this
  for any user with a published post). An existing and a non-existent user get
  the same answer. Any front-end request carrying `author` or `author_name`
  (a search filtered by author, too) is treated the same way.
- **REST user lists are closed to visitors.** `GET /wp/v2/users` and
  `GET /wp/v2/users/<id>` answer `401 rest_user_cannot_view` (`403` when signed
  in) unless the current user has `edit_posts` — so the block editor's author
  picker (`?who=authors`) and author display keep working for anyone who
  writes content, and everyone can still read their own record and
  `/wp/v2/users/me`. `_embed`ded authors and batch requests go through the same
  check. Creating and editing users is untouched (core already requires
  `create_users` / `edit_users`).
- **oEmbed** responses name the site and link the homepage instead of
  `author_name` / `author_url` (the display name and the author archive).
- **Sitemaps.** The core users sitemap is removed; with Yoast SEO the author
  sitemap is emptied.
- **Author blocks** — *Author* (`core/post-author`) and *Author Name*
  (`core/post-author-name`) — render without their "Link to author archive",
  which would point at a 404. The setting stays in the editor; only the front
  end ignores it.

## What it relies on

Core: `template_redirect`, `WP_Query::set_404()`, `redirect_canonical()`
running at priority 10, the `author` / `author_name` query vars,
`rest_pre_dispatch` (also used by `_embed` and batch dispatch), the
`/wp/v2/users` route shape, `oembed_response_data`, `wp_sitemaps_add_provider`,
`render_block_data` and the `isLink` attribute of the two author blocks.
Yoast SEO: `wpseo_sitemap_exclude_author`.

## Hooks

### `isudev_library/extensions/redirect_authors/redirect_url`

Send author archives somewhere with a `301` instead of a 404 — the T2
behaviour, or a team page. Only URLs `wp_validate_redirect()` accepts are used
(this site, or a host added through `allowed_redirect_hosts`); anything else
falls back to the 404.

```php
add_filter(
	'isudev_library/extensions/redirect_authors/redirect_url',
	fn() => home_url( '/about/team/' )
);
```

Existing and non-existent users are redirected alike, so the redirect does not
leak which users exist either.

### `isudev_library/extensions/redirect_authors/rest_capability`

The capability needed to read users over REST. Default `edit_posts`. Raise it
to `list_users` to keep even authors from listing other users — note that this
also empties the editor's author picker for anyone below administrator:

```php
add_filter(
	'isudev_library/extensions/redirect_authors/rest_capability',
	fn() => 'list_users'
);
```

### `isudev_library/extensions/redirect_authors/unlinked_blocks`

The blocks whose author-archive link is ignored. Default `core/post-author`
and `core/post-author-name`. Return an empty array to keep the links — useful
together with a `redirect_url`:

```php
add_filter( 'isudev_library/extensions/redirect_authors/unlinked_blocks', '__return_empty_array' );
```

## Not done, on purpose

- **Theme template tags.** A classic theme that prints `the_author_posts_link()`
  or `get_author_posts_url()` still links to the archive (which now 404s).
  Filtering `author_link` would change the URL everywhere, including places
  that need it; edit the theme instead.
- **The Avatar block's link** (`core/avatar` with *Link to user profile*) is
  left alone: in a comment it links to the commenter's own website, not an
  archive.
- **Yoast SEO's own author settings.** When Yoast's *Author archives* setting
  is off, Yoast itself 301-redirects author archives to the homepage on the
  `wp` action, before this extension runs. Yoast's schema may still name the
  author. Both are Yoast settings (Yoast SEO → Settings → Author archives).

## When it does not work

- **`/?author=1` still redirects to `/author/<login>/`.** A page cache or SEO
  plugin answers first, or another plugin redirects on `template_redirect`
  priority 0 or on `wp`.
- **The editor's author dropdown is empty.** The current user lacks the
  `rest_capability` (default `edit_posts`) — check the filter.
- **Logins still leak.** Check the other usual places: the login error message
  ("The password you entered for the username admin is incorrect"), comment
  author classes in classic themes (`comment-author-<login>`), and RSS
  `<dc:creator>` (display name, switch feeds off with
  [Disable feeds](../disable-feeds/README.md)).

## How to check it still works

Signed out:

```bash
curl -sI 'https://example.test/?author=1' | head -1                 # 404, no Location header
curl -s  https://example.test/wp-json/wp/v2/users | head -c 80     # rest_user_cannot_view
curl -s  'https://example.test/wp-json/oembed/1.0/embed?url=https://example.test/sample-page/' | grep -o '"author_name":"[^"]*"'   # the site name
```

Signed in as an editor, the Author panel in the post editor still lists authors.
