# Last edited column

Adds a **Last edited** column to post type list screens: who last saved the
post, and when.

## Where it appears

Every list screen of every post type that has an admin UI — Posts, Pages, and
any custom post type registered with `show_ui`. The column sits at the end of
the row, after Date.

The cell shows the editor's display name on one line and the date on the next,
formatted with the site's own **Settings → General** date and time formats.

A post that has never been edited since it was created shows an em dash. That is
deliberate: WordPress copies `post_date` into `post_modified` when a post is
first saved, so without that check every brand-new post would claim an edit that
never happened.

## Sorting

The column header is clickable. Sorting maps to WordPress's built-in `modified`
ordering, so it sorts by the real modification timestamp — not by the name shown
in the cell.

## Hooks

### `isudev_library/extensions/last_edited_column/post_types`

Limits the column to specific post types. Defaults to every post type with an
admin UI.

```php
add_filter(
	'isudev_library/extensions/last_edited_column/post_types',
	function ( array $post_types ): array {
		return array( 'page', 'product' );
	}
);
```

## When it shows nothing

- **The column is missing entirely.** The post type has to be in the list above.
  Check the filter if the theme uses it.
- **The date shows but no name.** The editor's name comes from the `_edit_last`
  post meta, which WordPress only writes when a post is saved through the editor.
  A post changed by WP-CLI, an import, or a REST call has no `_edit_last`, so
  only the date is shown.
- **An em dash in every row.** Those posts have not been edited since they were
  created.
