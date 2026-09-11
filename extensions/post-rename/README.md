# Rename posts to articles

Relabels the built-in **Posts** post type as **Articles** everywhere in the
admin: the menu, the list screen, the editor, and the notices after saving.

No new post type is registered and no content is touched — this changes words,
nothing else. Permalinks, capabilities, feeds and the REST route all stay exactly
as they are.

## Where it appears

- The admin menu entry and its *All articles* / *Add new article* submenu.
- The list screen title, its search and empty states.
- The editor, the admin bar, and the "Article published." notices.

## Renaming it to something else

The default words are *Article* and *Articles*. Both are filterable, so this one
extension covers News, Stories, Aktualności, or anything else — there is no need
for a second copy of it.

### `isudev_library/extensions/post_rename/names`

```php
add_filter(
	'isudev_library/extensions/post_rename/names',
	function ( array $names ): array {
		return array(
			'singular' => __( 'News item', 'my-theme' ),
			'plural'   => __( 'News', 'my-theme' ),
		);
	}
);
```

Both keys are required; a missing or empty one falls back to the default for
that key alone.

### `isudev_library/extensions/post_rename/labels`

The full label set, after it has been built from the two names. Use this when one
specific label needs different wording from the pattern — a plural that is not
the singular plus an *s*, or a phrasing the generated sentence gets wrong.

```php
add_filter(
	'isudev_library/extensions/post_rename/labels',
	function ( array $labels, string $singular, string $plural ): array {
		$labels['not_found'] = __( 'Nothing published yet.', 'my-theme' );

		return $labels;
	},
	10,
	3
);
```

Keys are the standard WordPress post type label names — see
[`get_post_type_labels()`](https://developer.wordpress.org/reference/functions/get_post_type_labels/)
for the full list.

## Pairs with

[Disable posts](../disable-posts/README.md) hides the same post type instead of
relabelling it. Enable one or the other, not both.

## When the old name is still showing

- **The admin menu says "Posts".** The menu is built from labels captured before
  this extension runs, which is why it is rewritten separately. If it is still
  wrong, another plugin is rewriting it afterwards on `admin_menu` at a later
  priority.
- **A translation shows through.** The defaults go through the plugin's own text
  domain. On a non-English site, set the words explicitly with the `names` filter
  rather than relying on a translation that may not exist.
