# Disable feeds

Switches off every core feed — RSS 2.0, RSS 0.92, RDF and Atom, for posts,
comments, categories, tags, authors, searches and custom post types — for sites
that publish nothing anyone subscribes to.

## What it changes

- **Feed URLs answer with the theme's 404 page**, status `404`, HTML
  `Content-Type`, no-cache headers. Not a redirect to the homepage: a feed
  reader or crawler learns the feed is gone instead of parsing a web page.
  Covered: `/feed/`, `/feed/atom/`, `/comments/feed/`, `/<post>/feed/`,
  `/category/<slug>/feed/`, `/author/<name>/feed/`, `/?s=x&feed=rss2`,
  `/?feed=rss2`, `/<post-type>/feed/` and the old `comments-rss2` style names.
- **No feed links in `<head>`.** `feed_links()` and `feed_links_extra()` are
  removed from `wp_head`, so no `<link rel="alternate" type="application/rss+xml">`
  is printed anywhere.

The decision is made on the `request` filter — before `WP::send_headers()`
gives the response a feed `Content-Type` and before it answers a feed reader's
conditional request with `304 Not Modified`. The request then runs as an
ordinary 404 (`error=404`), so the theme's 404 template renders and nothing
downstream ever sees a feed. As a backstop, each disabled feed's `do_feed_*`
action ends the request with a bare plain-text 404 if a theme or plugin calls
`do_feed()` itself.

Nothing is deleted and no option changes: switching the extension off brings
every feed back.

## What it relies on

Core hooks and functions: the `request` filter, the `feed`, `withcomments`,
`withoutcomments` and `error` query vars (`WP::send_headers()` and
`WP_Query::parse_query()` turn `error=404` into a 404), `get_default_feed()`,
the `do_feed_rdf`, `do_feed_rss`, `do_feed_rss2`, `do_feed_atom` actions, and
`feed_links` / `feed_links_extra` hooked on `wp_head` at priorities 2 and 3.

## Hooks

### `isudev_library/extensions/disable_feeds/types`

The feed names answered with a 404. Default `rdf`, `rss`, `rss2`, `atom`. A
feed a plugin registers with `add_feed()` — a podcast feed, a product feed —
keeps working unless you add its name. Remove a name to keep that feed:

```php
add_filter(
	'isudev_library/extensions/disable_feeds/types',
	function ( array $types ): array {
		$types[] = 'podcast'; // Also switch off a plugin's feed.
		return array_values( array_diff( $types, array( 'rss2' ) ) ); // Keep RSS 2.0.
	}
);
```

Keeping a type keeps its URLs working, but the `<head>` links stay removed.

## Pairs with

- [Disable posts](../disable-posts/README.md) and
  [Disable comments](../disable-comments/README.md) 404 their own feeds too.
  With this extension on, the feed is refused first and theirs never fire; any
  combination is safe and sends one response.

## When it does not work

- **A feed still answers.** Check its name in the URL (`/feed/<name>/` or
  `?feed=<name>`). A name that is not in the `types` list — usually a plugin's
  `add_feed()` feed — is left alone; add it with the filter.
- **A feed link is still in `<head>`.** The theme prints it by hand (look for
  `bloginfo( 'rss2_url' )` or `get_feed_link()` in its templates) or a plugin
  adds it on its own hook.
- **The response is a cached feed.** A page cache in front of WordPress is
  serving a copy made before the extension was on. Purge it.

## How to check it still works

Each must answer `404` with `Content-Type: text/html`:

```bash
curl -sI https://example.test/feed/ | head -1
curl -sI https://example.test/comments/feed/ | head -1
curl -sI 'https://example.test/?feed=atom' | head -1
curl -sI -H 'If-Modified-Since: Thu, 01 Jan 2099 00:00:00 GMT' https://example.test/feed/ | head -1
```

The last one proves a feed reader's conditional request gets the 404 too, not
a `304`. View source on any page: no `application/rss+xml` link.
