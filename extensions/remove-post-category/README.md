# Remove categories from posts

Keeps **Posts** but takes **categories** away from them, for blogs that
organise with tags only (or not at all) and never want an editor to pick an
"Uncategorized" box again.

## What it changes

On `init` priority 100 — after themes and plugins have attached categories to
their own post types — `category` is detached from `post`
(`unregister_taxonomy_for_object_type()`). That alone removes:

- **Posts → Categories** from the admin menu, the *Categories* column and the
  Quick Edit checklist from the posts list;
- the *Categories* panel in the block editor;
- the default category: `wp_insert_post()` only assigns *Default Post
  Category* when the post type is in the taxonomy, so new posts get none.

When the taxonomy is then attached to **no post type at all**, it is hidden
everywhere, the same way [Disable posts](../disable-posts/README.md) hides it:

- **Front end — a real 404.** Category archives (`/category/<slug>/`,
  `?cat=<id>`, `?category_name=<slug>`) and their feeds get the theme's 404
  template with status `404` and no-cache headers, on `template_redirect`
  priority 1 — before `redirect_canonical()` (10) would redirect `?cat=` to a
  pretty URL. No permalink flush is needed.
- **REST.** `show_in_rest` is off: `/wp/v2/categories` is not registered, and
  `/wp/v2/search?type=term` no longer returns categories.
- **Sitemaps.** The core sitemap and Yoast SEO drop the category sitemap.
- **Blocks.** *Categories List* leaves the inserter. An existing one that lists
  categories renders nothing; one pointed at another taxonomy keeps rendering.
  The *Post Terms* block's Categories variation disappears on its own (core
  only offers it for public, REST-visible taxonomies) and an existing one
  renders nothing.
- **Admin.** `edit-tags.php?taxonomy=category` answers with core's "Sorry, you
  are not allowed to edit terms in this taxonomy."
- **`<head>`.** No category feed link.

Nothing is deleted. Terms and their assignments stay in the database and come
back with the extension switched off.

## What it relies on

Core: `unregister_taxonomy_for_object_type()`, the registered `category`
taxonomy object (`object_type`, `public`, `publicly_queryable`, `show_ui`,
`show_in_rest`, …), the registered `core/categories` block type object
(`supports.inserter`, read by the editor after `init`), `template_redirect`,
`WP_Query::set_404()`, `pre_render_block`, `wp_sitemaps_taxonomies`,
`feed_links_extra_show_category_feed`. Yoast SEO: `wpseo_sitemap_exclude_taxonomy`.

It shares no code with [Remove tags from posts](../remove-post-tags/README.md);
the two can be used separately or together.

## Not done, on purpose

- **Settings → Writing still shows *Default Post Category*.** Core prints the
  row without a hook (`options-writing.php`). The value is no longer used.
- **No filter.** There is nothing to configure: which post types use
  categories is decided by whoever registers them.

## When it does not work

- **Category archives still answer.** Another post type still uses
  categories (`register_taxonomy_for_object_type( 'category', … )` or
  `'taxonomies' => array( 'category' )`). The taxonomy then stays public on
  purpose; only posts lose it. Note that core's category archive lists posts
  only, so such an archive may now show old post assignments — a theme that
  wants it to list the other post type adds it with `pre_get_posts`.
- **The Categories panel still shows in the editor.** A plugin re-attached
  categories to posts after `init` priority 100.
- **A Categories List still renders.** It is set to another taxonomy, or the
  theme renders `wp_list_categories()` itself.

## How to check it still works

Edit a post: no Categories panel. With nothing else using categories:

```bash
curl -sI 'https://example.test/?cat=1' | head -1          # 404
curl -s https://example.test/wp-json/wp/v2/categories | head -c 80   # rest_no_route
```
