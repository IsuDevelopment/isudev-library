# Custom HTML block wrapper

Wraps the rendered **Custom HTML** block (`core/html`) in a `<div>`, so its
markup — and any `<script>` pasted after it — is one element in the layout.

## Why it exists

`core/html` saves exactly what the editor typed, with no `wp-block-…` wrapper.
Paste an embed such as

```html
<div id="widget"></div>
<script src="https://example.com/widget.js"></script>
```

and the page gets two sibling elements. Layout rules that target direct children
(`.is-layout-constrained > *`, `.is-layout-flow > * + *`, grid and flex gaps)
then treat the trailing `<script>` as a second item: an extra gap, a stray
grid cell, a margin on the wrong element. A theme also has nothing to style the
block by.

This adds the wrapper the block would have had:

```html
<div class="wp-block-html">
	<div id="widget"></div>
	<script src="https://example.com/widget.js"></script>
</div>
```

A block that renders nothing is left alone, so an empty wrapper never turns up
as a mysterious gap.

## What it relies on

- The `render_block_core/html` filter. Saved content is not changed; switching
  the extension off removes the wrapper everywhere at once.
- `core/html` rendering without a wrapper of its own. If core ever adds one,
  this produces a double wrapper — check the front-end markup after a core
  update.

## Hooks

### `isudev_library/extensions/wrap_html_block/class`

The class on the wrapper. Defaults to `wp-block-html`.

```php
add_filter(
	'isudev_library/extensions/wrap_html_block/class',
	function ( string $class ): string {
		return 'wp-block-html my-theme-embed';
	}
);
```

## When the wrapper is not there

- **The HTML is not in a Custom HTML block.** Only `core/html` is filtered — a
  Classic block, a shortcode or a Code block is not.
- **A page cache served the old markup.** Purge it after switching the extension
  on or off.

## How to check it still works

Add a Custom HTML block with any markup, view the page and confirm the markup
sits inside `<div class="wp-block-html">`.
