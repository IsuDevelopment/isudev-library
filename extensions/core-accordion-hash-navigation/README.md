# Accordion links

Gives every item of the core Accordion block a stable, readable link, so a
question can be shared as `https://example.test/faq/#delivery-times`.

## What it changes

- **Ids.** Core numbers items per request (`accordion-item-3`), so the number
  changes when content above it changes. This replaces it with:
  1. the heading's **HTML anchor** (select the Accordion Heading → Advanced →
     HTML anchor), if set; else
  2. the heading text as a slug (`Delivery times?` → `delivery-times`).

  A second item with the same id on the page gets `-2`, `-3`, …. The new id is
  written everywhere core wrote its own, so nothing falls out of step: the
  toggle button's `id`, its `aria-controls` (`<id>-panel`), the panel's `id` and
  `aria-labelledby`, and the item's Interactivity context. A heading anchor moves
  from the heading element to the button (one element per id).
- **Visiting a link** (`#id` on load, or a later `hashchange`) opens that item,
  scrolls it into view (instantly under `prefers-reduced-motion: reduce`) and
  moves keyboard focus to its button. In an accordion with **Auto-close** on,
  the others close as usual.
- **Opening an item** puts its link in the address bar
  (`history.replaceState`, no new history entry, no jump). **Closing** it
  removes the link again, or switches to another item still open in the same
  accordion.
- Core's own behaviour is kept: a link to something *inside* a panel (an
  anchor on a heading in the answer) still opens that panel, and opening the
  panel then does not overwrite that deeper link.

The view script (`view.js`, ~2 kB, no dependencies) loads only on pages where an
accordion renders. Nothing changes in the editor.

## Hooks

### `isudev_library/extensions/core_accordion_hash_navigation/id`

The id before it is made unique. Receives the anchor or slug, the heading text
and the parsed `core/accordion-item`. Return `''` to keep core's numbered id.
Ids with whitespace, `#`, quotes or `<>` are refused; at most 80 characters.

```php
add_filter(
	'isudev_library/extensions/core_accordion_hash_navigation/id',
	function ( string $id, string $title ): string {
		return '' === $id ? '' : 'faq-' . $id;
	},
	10,
	2
);
```

## What it relies on and does not own

- `core/accordion-item`'s render callback (WordPress 6.9+, checked against
  7.1.3): it writes `data-wp-context='{"id":"accordion-item-N",…}'` on
  `.wp-block-accordion-item`, `id` + `aria-controls` on
  `.wp-block-accordion-heading__toggle`, and `id="…-panel"` +
  `aria-labelledby` on `.wp-block-accordion-panel`. The rewrite finds the
  item's own toggle and panel by that id (never a nested accordion's), and does
  nothing unless it finds all of them.
- The heading's saved markup (`.wp-block-accordion-heading` with its `id`
  anchor, `.wp-block-accordion-heading__toggle-title`).
- Core's view module (`@wordpress/block-library/accordion/view`): its store is
  locked, so the script opens items by dispatching `beforematch` on the panel,
  which core handles with `actions.handleBeforeMatch`, and reads state from the
  button's `aria-expanded`. Core's `openPanelByHash` reacts only to targets
  inside a panel, so the two never handle the same link.
- Why a classic script and not a script module: it imports nothing and calls
  nothing in core's store (it cannot — the store is locked), so a module would
  add an import map entry and a build for no gain. It is a static file,
  enqueued in the footer with `defer` from `render_block_core/accordion`.
- `render_block_core/accordion-item`, `WP_HTML_Tag_Processor`, `sanitize_title`.

Ids are unique per request and keyed by the item's content, so an accordion
rendered twice in one request (an excerpt, structured data, the page) keeps the
same ids; two items with identical heading and content on one page share one.

## When it does not work

- **The link does not open the item.** The id is not what you expect — inspect
  the toggle button's `id`. Changing a heading's text changes its slug and
  breaks old links: set an HTML anchor on headings you link to.
- **Opens but does not scroll under a sticky header.** Add
  `scroll-margin-top` to `.wp-block-accordion-heading__toggle` in the theme.
- **The address bar never changes.** The script did not load — look for
  `isudev-library-core-accordion-hash-navigation` in the page; a page cache may
  serve HTML from before the extension was switched on.
- **Two items open from one link.** A theme or plugin also handles accordion
  hashes (T2's `core-accordion-hash-navigation`, for one). Run only one.

## How to check it still works

Accordion with items "Delivery times" and "Delivery times" (duplicate) and one
item with the anchor `returns`. Source: buttons `id="delivery-times"`,
`id="delivery-times-2"`, `id="returns"`, panels `…-panel` with matching
`aria-labelledby`, no `id="returns"` left on the `h3`. Open `/#returns`: that
item opens, scrolls, has focus. Open and close items: the address bar follows.
`php tools/check.php` covers the id registry (`250-core-accordion.php`).
