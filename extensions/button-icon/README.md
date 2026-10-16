# Button icon

Lets editors put an icon inside a core **Button** block: before or after the
text, at a chosen size, or the icon alone with the text kept for screen readers.
Icons come from the library's shared icon registry (`isudev_library/icons`), the
same set the library's blocks use.

## What it changes

- **Editor.** A selected Button gets an **Icon** button in its toolbar (a
  dropdown with the icon grid and the options) and an **Icon** panel in the
  sidebar (the same options with a compact icon select):
  - **Icon** — pick one, or clear it.
  - **Icon position** — *Before text* / *After text* (logical: in RTL "before"
    is on the right).
  - **Icon size** — 8–128 px. Reset follows the theme default.
  - **Show icon only** — the text is visually hidden but stays in the link, so
    screen readers announce it. A warning appears when the text is empty: core
    renders **nothing** for a button without text, and the text is the only
    accessible label a core button has (it has no `aria-label` attribute).

  The canvas shows the icon as a CSS mask on the block's wrapper — no DOM
  change, so it works in the iframed editor. With icon-only, the text is hidden
  only while the block is not selected, so it stays editable.
- **Site.** The icon's SVG is inserted inside the `<a>` / `<button>`:

  ```html
  <div class="wp-block-button has-isudev-icon has-isudev-icon-after" style="--isudev-button-icon-size:20px;">
  	<a class="wp-block-button__link wp-element-button" href="…">Read more<svg class="isudev-button-icon" aria-hidden="true" focusable="false" fill="currentColor" …>…</svg></a>
  </div>
  ```

  Icon-only adds `is-isudev-icon-only` and wraps the text in
  `<span class="isudev-visually-hidden">`. The SVG is decorative
  (`aria-hidden`); the text is the name.
- **Styles.** `button-icon.css` is attached to `core/button` with
  `wp_enqueue_block_style()`: on the site it loads only when a button renders,
  and it reaches the iframed editor canvas.

## Attributes

Registered on `core/button` on both sides (PHP `register_block_type_args`, JS
`blocks.registerBlockType`), **without defaults**:

| Attribute | Type | Meaning |
| --- | --- | --- |
| `isudevIcon` | string | Registry icon name. Empty or unknown renders nothing. |
| `isudevIconPosition` | `before` \| `after` | Unset → `defaultPosition`. |
| `isudevIconSize` | integer | px, clamped to 8–128 on render. Unset → `defaultSize`, then the stylesheet (`1.25em`). |
| `isudevIconOnly` | boolean | Text visually hidden. |

An attribute holds only what an editor chose. Anything left unset follows the
theme's `isudev.json` at render time, so **changing `defaultPosition` or
`defaultSize` changes every button that never set its own** — that is
intended. A button whose position or size was picked keeps it.

Everything is sanitised on render: a wrong type (an array, a word for a
size) falls back as if unset, never fatals.

## Settings (`isudev.json`)

```json
{
	"library": {
		"extensions": {
			"button-icon": {
				"enabled": true,
				"icons": [ "arrowForward", "chevronRight", "email", "share" ],
				"defaultPosition": "after",
				"defaultSize": 20
			}
		}
	}
}
```

| Key | Default | Meaning |
| --- | --- | --- |
| `icons` | all | Names offered in the picker. Missing or empty → every registry icon. Only the picker is limited: a button that already uses another registered icon still renders it. |
| `defaultPosition` | `after` | `before` or `after`. |
| `defaultSize` | none | px. Without it the icon is `1.25em`, following the button's font size. |

Add icons to the registry with the `isudev_library/icons` filter (see
`includes/utils/icon.php`); the picker and the front end both read it.

## Class and property contract (what a theme can rely on)

- Wrapper: `has-isudev-icon`, `has-isudev-icon-before` / `has-isudev-icon-after`,
  `is-isudev-icon-only`; `--isudev-button-icon-size` when a size resolves.
- Icon: `svg.isudev-button-icon` (`fill="currentColor"`, so it follows the
  button's text colour, including `is-style-outline` and the
  [button colour](../button-color/README.md) extension).
- Hidden text: `span.isudev-visually-hidden`.
- `--isudev-button-icon-gap` (default `0.5em`) — space between icon and text.

All rules are wrapped in `:where()`, so one theme class outranks them:

```css
.wp-block-button.has-isudev-icon { --isudev-button-icon-gap: 0.75rem; }
[dir="rtl"] .isudev-button-icon { transform: scaleX(-1); } /* mirror arrows in RTL */
```

The layout is inline (`vertical-align: middle`), not flex, so inline formatting
in the text is not split apart.

Editor only: `is-isudev-icon-preview` and `--isudev-button-icon` (the mask
image) on the canvas wrapper.

## Filters

```php
// Stop reading T2's attribute names (see below).
add_filter( 'isudev_library/extensions/button_icon/read_t2_attributes', '__return_false' );

// Change the rendered icon markup (shared with every library icon).
add_filter( 'isudev_library/icon', function ( string $markup, string $name, array $args ): string {
	return $markup;
}, 10, 3 );
```

## Coming from T2's button icon

Content made with T2's `t2/button-icon` stored `icon`, `iconPosition`
(`left`/`right`), `iconSize` and `hideText`. They are **read as a fallback**
when a button has none of the `isudev*` attributes: `left` → before,
`right` → after, `hideText` → icon only. The editor keeps them on save, and
the first change through this extension's controls moves the button onto the
`isudev*` attributes and drops T2's. Content is never rewritten in bulk.

The icon **name** must exist in this registry. T2 names (`arrow-forward`,
`core/arrow-right`) usually do not; register them under the same name with
`isudev_library/icons`, or the button renders without an icon. Do not run T2's
button-icon extension at the same time — both would insert an icon.

## What it does not do

- It does not rewrite saved content; the icon is added at render time, so
  switching the extension off removes every icon (the attributes stay in the
  content, harmless).
- No `aria-label`: core/button has none, the visible (or hidden) text is the
  label.
- An icon registered as an image URL renders as `<img>`, which cannot follow
  `currentColor`.

## When it seems to do nothing

- **No toolbar button / panel** — the editor script is missing: run
  `npm run build` (`build/extensions/button-icon/editor.js`).
- **Picked in the editor, nothing on the page** — the icon name is not in the
  registry on the front end (a filter added only in admin), or the button has
  no text (core then renders nothing at all).
- **Icon is huge or tiny** — a theme rule on `.wp-block-button__link svg`
  outranks the `:where()` rules; set `--isudev-button-icon-size` instead.
- **Icon not in the canvas but on the page** — the canvas preview needs the
  icon's SVG in `window.isudevIcons`; check that the registry filter runs in
  the editor too.

## Relies on (what a core update can break)

- Core's button markup: a first `div.wp-block-button` wrapping the first
  `<a>` or `<button>` (same lookup `render_block_core_button()` uses in 7.1);
  the `render_block_core/button` filter; `register_block_type_args`.
- `WP_HTML_Tag_Processor`'s protected `$bookmarks` and `$lexical_updates`,
  through a subclass — the same technique core uses in
  `_block_template_add_skip_link()` and the late-styles buffer in
  `script-loader.php`. If the markup is not the expected shape, or the link has
  no closing tag, the button is returned untouched.
- Editor: `blocks.registerBlockType` with `getEditWrapperProps` (className and
  style are merged by `useBlockProps`), `editor.BlockEdit`, `BlockControls`
  group `other`, `ToggleGroupControl` (still exported only as
  `__experimentalToggleGroupControl` in 7.1), `@isudev/gutenberg`
  `IconPicker` / `IconSelect` / `Icon`.
- `wp_enqueue_block_style()` loading on `enqueue_block_assets` in the editor.

## How to check it still works

- `php tools/check.php` — `tools/checks/240-button-icon.php` covers attribute
  sanitising, clamping, the T2 fallback and the inserted markup pieces.
- Insert a Button with text, pick an icon, set *Before text* and 32 px: the
  canvas shows it; on the page the `<a>` starts with
  `svg.isudev-button-icon` and the wrapper has
  `--isudev-button-icon-size:32px`.
- Switch on **Show icon only**: the page shows only the icon, and the
  accessibility tree names the link by its text. Empty the text: the sidebar
  warns.
