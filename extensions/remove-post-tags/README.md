# Remove tags from posts

Keeps **Posts** but takes **tags** away from them, for blogs that organise with
categories only and do not want a Tags box nobody fills in.

## What it changes

On `init` priority 100 — after themes and plugins have attached tags to their
own post types — `post_tag` is detached from `post`
(`unregister_taxonomy_for_object_type()`). That alone removes:

- **Posts → Tags** from the admin menu, the *Tags* column and the Quick Edit
  tags field from the posts list;
- the *Tags* panel in the block editor; `tags_input` is ignored on save.

When the taxonomy is then attached to **no post type at all**, it is hidden
everywhere, the same way [Disable posts](../disable-posts/README.md) hides it:

- **Front end — a real 404.** Tag archives (`/tag/<slug>/`, `?tag=<slug>`) and
  their feeds get the theme's 404 template with status `404` and no-cache
  headers, on `template_redirect` priority 1 — before `redirect_canonical()`
  (10). No permalink flush is needed.
- **REST.** `show_in_rest` is off: `/wp/v2/tags` is not registered, and
  `/wp/v2/search?type=term` no longer returns tags.
- **Sitemaps.** The core sitemap and Yoast SEO drop the tag sitemap.
- **Blocks.** *Tag Cloud* leaves the inserter. An existing one that lists tags
  renders nothing; one pointed at another taxonomy keeps rendering. The *Post
  Terms* block's Tags variation disappears on its own and an existing one
  renders nothing.
- **Admin.** `edit-tags.php?taxonomy=post_tag` answers with core's "Sorry, you
  are not allowed to edit terms in this taxonomy." The classic Tag Cloud widget
  no longer offers tags (`show_tagcloud` is off).
- **`<head>`.** No tag feed link.

Nothing is deleted. Terms and their assignments stay in the database and come
back with the extension switched off.

## What it relies on

Core: `unregister_taxonomy_for_object_type()`, the registered `post_tag`
taxonomy object, the registered `core/tag-cloud` block type object
(`supports.inserter`, read by the editor after `init`), `template_redirect`,
`WP_Query::set_404()`, `pre_render_block`, `wp_sitemaps_taxonomies`,
`feed_links_extra_show_tag_feed`. Yoast SEO: `wpseo_sitemap_exclude_taxonomy`.

It shares no code with
[Remove categories from posts](../remove-post-category/README.md); the two can
be used separately or together.

## Not done, on purpose

- **No filter.** There is nothing to configure: which post types use tags is
  decided by whoever registers them.

## When it does not work

- **Tag archives still answer.** Another post type still uses `post_tag`. The
  taxonomy then stays public on purpose; only posts lose it. Core's tag
  archive lists posts only, so such an archive may show old post assignments
  until a theme adds its post type with `pre_get_posts`.
- **The Tags panel still shows in the editor.** A plugin re-attached tags to
  posts after `init` priority 100.

## How to check it still works

Edit a post: no Tags panel. With nothing else using tags:

```bash
curl -sI 'https://example.test/?tag=news' | head -1       # 404
curl -s https://example.test/wp-json/wp/v2/tags | head -c 80   # rest_no_route
```
