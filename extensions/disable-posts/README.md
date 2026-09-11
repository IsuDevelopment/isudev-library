# Disable posts

Takes the built-in **Posts** post type out of the site: no admin menu, no
front-end archive, no single post URLs, no categories or tags.

Use it on sites that carry all their content in custom post types and pages,
where an unused Posts menu is one more thing for an editor to get lost in.

## What it changes

- **Admin.** The Posts menu is gone, and so are the *Quick Draft* and
  *Recent Drafts* dashboard widgets. Reaching `edit.php`, `post-new.php`,
  `post.php`, `edit-tags.php` or `term.php` for a post or one of its taxonomies
  by typing the URL redirects to the dashboard.
- **Front end.** Posts and the category and tag archives are no longer publicly
  queryable and are excluded from search.
- **REST and the block editor.** `show_in_rest` is switched off for the post type
  and the taxonomies, so they stop appearing in the editor's Query Loop and
  category pickers.

Nothing is deleted. Existing posts stay in the database and come back the moment
the extension is switched off.

## Hooks

### `isudev_library/extensions/disable_posts/taxonomies`

The taxonomies hidden alongside posts. Defaults to `category` and `post_tag`.
Return an empty array to keep them — worth doing when a custom post type has been
registered against the same taxonomies.

```php
add_filter(
	'isudev_library/extensions/disable_posts/taxonomies',
	function ( array $taxonomies ): array {
		return array(); // Keep categories and tags; hide only posts.
	}
);
```

## Pairs with

[Rename posts to articles](../post-rename/README.md) is the opposite choice —
keep the post type, call it something else. Enabling both hides a post type
whose labels you just changed, which is almost certainly not what you meant.

## When it does not work

- **Posts still appear in a Query Loop.** The block stores the post type in the
  post content. An existing block keeps asking for posts; the query returns them
  because the post type still exists. Edit or remove the block.
- **The Posts menu is still there.** Another plugin registered its own menu entry
  pointing at `edit.php`. Only core's entry is removed.
- **Old post URLs return a page instead of a 404.** WordPress permalinks are
  cached in rewrite rules. Visit **Settings → Permalinks** once to flush them.
