# Working with icons

How icons work in `isudev-library`, which system to reach for, and how to add one
without breaking `tools/check.php` or the a11y suite.

## The three icon systems (do not mix them up)

| System | Lives in | Used for | Output |
| --- | --- | --- | --- |
| **Frontend SVG registry** | [includes/utils/icon.php](../includes/utils/icon.php) | Glyphs inside rendered block markup (chevrons, close, arrows) | Inline `<svg>` echoed by `render.php` |
| **Block inserter icon** | `block.json` `"icon"` + optional `icon.js` | How the block looks in the inserter / list view / admin panel | React element or Dashicon slug |
| **Editor UI icons** | `@isudev/gutenberg` (`Icon`, `IconPicker`, `IconSelect`) | Author-selectable icons in block controls | React, from an injected collection |

Rule of thumb: **anything the visitor sees comes from the PHP registry; anything
only the editor sees is JS.** Blocks always render in PHP, so the frontend never
gets an icon from JS.

---

## 1. Frontend SVG registry — `includes/utils/icon.php`

There is no icon-font, no sprite sheet, no external icon dependency. Icons are
inline SVG built from a slug → path map.

### Public API

```php
use function IsuDevLibrary\Utils\icon;

echo icon( 'chevronDown', 20, 'isudev-nav__chevron-icon' );
//        slug            size  class on the <svg> itself
```

`icon( string $slug, int $size = 24, string $class_name = '' ): string`

- Returns **already-safe** markup — echo it directly. Never wrap it in
  `esc_html()` (that prints the tags) and never in `wp_kses()` (that would strip
  attributes we rely on).
- An **unknown slug returns `''`**, not a warning and not a placeholder. A typo
  fails silently, so the check file below is what protects you.
- `$class_name` is passed through `esc_attr()` inside `icon()`.

### File structure: pure functions, then WP adapters

The file is split by a `WordPress adapters.` marker
([icon.php:76](../includes/utils/icon.php#L76)). Everything **above** it must not
call WordPress, because `tools/check.php` requires the file with no WP loaded:

- `DEFAULT_VIEW_BOX` — `'0 0 600 600'`, the grid the original glyphs were drawn on.
- `default_icon_paths(): array` — slug ⇒ SVG child markup (pure).
- `default_icon_view_boxes(): array` — slug ⇒ viewBox, **only for glyphs that are
  not on the 600 grid** (pure).
- `build_svg( $path_d, $size, $class_attr, $view_box = DEFAULT_VIEW_BOX ): string`
  — wraps path markup in the `<svg>` element (pure).

Below the marker sits `icon()`, the only function allowed to call
`apply_filters()` / `esc_attr()`.

### What `build_svg()` guarantees

```html
<svg class="…" width="24" height="24" viewBox="0 0 600 600"
     fill="currentColor" aria-hidden="true" focusable="false"
     xmlns="http://www.w3.org/2000/svg">…path…</svg>
```

- `fill="currentColor"` — the icon inherits text `color`. Tint with CSS `color`,
  never with `fill`.
- `aria-hidden="true"` + `focusable="false"` — icons are always decorative. The
  accessible name has to come from the surrounding element (see §4).
- `width`/`height` both take `$size`; icons are square by contract.
- Empty path markup ⇒ empty string, so a missing glyph never emits a stray `<svg>`.

### Current registry

| Slug | viewBox | Used by |
| --- | --- | --- |
| `chevronDown` | 600 grid | [class-nav-walker.php:131](../src/blocks/site-header/inc/class-nav-walker.php#L131) — submenu disclosure toggle |
| `close` | 600 grid | [site-header/render.php:40](../src/blocks/site-header/render.php#L40) — mobile drawer close button |
| `arrowForward` | `0 0 24 24` | [read-more/render.php:79](../src/blocks/read-more/render.php#L79) — read-more card arrow |
| `burger` | 600 grid | *nothing* — the header burger is three CSS-animated `<span>`s, not this glyph. Kept for integrators. |

---

## 2. Adding an icon to the registry

1. **Get clean path markup.** Child markup only — `<path d="…"/>`, no `<svg>`
   wrapper, no `width`/`height`, and **no `fill` on the path**: a path-level fill
   overrides the wrapper's `currentColor` and the glyph stops following text
   colour. Run it through an SVG optimiser first; the map holds one long line per
   slug.
2. **Add it to `default_icon_paths()`** with a `lowerCamelCase` slug matching the
   existing style (`chevronDown`, `arrowForward`).
3. **Declare the viewBox only if it is not `0 0 600 600`.** Add an entry to
   `default_icon_view_boxes()` (e.g. `'0 0 24 24'` for Material-sized glyphs).
   Adding an entry for a 600-grid glyph silently rescales it.
4. **Extend [tools/checks/50-icon.php](../tools/checks/50-icon.php).** At minimum:
   the slug is registered, its viewBox override is what you expect (or absent),
   and the glyph carries no `fill=`. **Bump the `exactly four defaults` count
   assertion** — it is deliberately strict so an accidental registry change is
   caught.
5. `npm run test:php` — no WordPress or database needed.
6. **Consume it in `render.php`** with a BEM-ish class of the owning block, then
   size it in the block's `style.scss`.

No build step is needed for the registry itself: `includes/` is loaded from
source. Editing a block's `render.php`, however, means running `npm run build` —
the loader serves blocks from `build/blocks/<slug>/`
([class-loader.php:40](../includes/class-loader.php#L40)).

---

## 3. Styling icons

The class goes on the `<svg>` element itself, so size it directly — the plugin
does not ship any global icon CSS.

Two established patterns:

```scss
/* Fixed size, straight on the svg — site-header */
.isudev-nav__chevron-icon {
	width: 1.25rem;
	height: 1.25rem;
}
```

```scss
/* Sized box + svg filling it, so the box can be animated — read-more */
.read-more-arrow {
	flex: 0 0 24px;
	height: 24px;
	width: 24px;
	transition: transform 0.3s cubic-bezier(0.25, 0, 0, 1);
}

.read-more-arrow__icon {
	display: block;
	height: 100%;
	width: 100%;
}
```

- The `$size` argument sets the SVG's own `width`/`height` attributes; CSS wins
  over them. Pass a sensible size anyway so the icon is right without CSS.
- **Animate the wrapper, not the `<svg>`.** `.isudev-nav__item.is-open .isudev-nav__chevron { transform: rotate(180deg) }`
  and the read-more hover nudge both transform the wrapping span.
- Colour comes from `currentColor`, so set `color` on the button/link.
- Respect the existing `prefers-reduced-motion` block in
  [site-header/style.scss](../src/blocks/site-header/style.scss) when adding new
  icon motion.

---

## 4. Accessibility contract

Icons are decorative by construction (`aria-hidden`, `focusable="false"`). The
name must come from the control around them:

```php
// Icon-only button → visually hidden text carries the name.
'<button type="button" class="isudev-nav__toggle" aria-expanded="false" aria-controls="…">'
	. '<span class="isudev-sr-only">' . sprintf( esc_html__( 'Show submenu for %s', 'isudev-library' ), esc_html( $title ) ) . '</span>'
	. '<span class="isudev-nav__chevron" aria-hidden="true">' . $chevron . '</span>'
	. '</button>'
```

```php
// Purely ornamental → the wrapper is aria-hidden too, belt and braces.
$arrow = sprintf(
	'<span class="read-more-arrow" aria-hidden="true">%s</span>',
	icon( 'arrowForward', 24, 'read-more-arrow__icon' )
);
```

Never give an icon an `aria-label`, `role="img"` or `<title>`; the wrapper's
`aria-hidden` would fight it. The site-header markup contract is covered by the
Playwright + axe suite — run `npm run test:e2e` after touching header icons.

---

## 5. Filters for integrators

Three filters, all fired in `icon()`:

| Filter | Signature | Purpose |
| --- | --- | --- |
| `isudev_library/icons` | `array $paths` | Add or replace glyphs in the registry. |
| `isudev_library/icon_view_boxes` | `array $boxes` | ViewBox overrides, mirroring the above. |
| `isudev_library/icon` | `string $svg, string $slug, int $size, string $class_name` | Rewrite the final markup (last resort). |

```php
add_filter(
	'isudev_library/icons',
	function ( array $paths ): array {
		$paths['chevronDown'] = '<path d="…"/>';   // swap a built-in
		$paths['externalLink'] = '<path d="…"/>';  // add a new one
		return $paths;
	}
);

add_filter(
	'isudev_library/icon_view_boxes',
	function ( array $boxes ): array {
		$boxes['externalLink'] = '0 0 24 24';
		return $boxes;
	}
);
```

Filtered path markup is echoed unescaped — the registry is a *trusted static
source*. Never route user input, post meta or a request parameter into these
filters.

---

## 6. Block inserter icons (editor-only)

Two layers, and the JS one wins in the editor:

- **`block.json` `"icon"`** — a Dashicon slug: `"menu-alt"` for site-header,
  `"arrow-right-alt"` for read-more. This is what ends up in
  `build/blocks-manifest.php`, and therefore what the admin panel's REST payload
  reports ([rest.php:119](../includes/rest.php#L119)) — including for **disabled**
  blocks, which are never registered and have no block type to read from. Always
  set a real Dashicon slug here, even when you override it in JS.
- **`icon.js` + `registerBlockType( metadata.name, { icon } )`** — a React SVG
  that replaces the Dashicon inside the editor. Build it with
  `@wordpress/primitives`, not raw JSX tags:

```jsx
import { SVG, Path } from '@wordpress/primitives';

export default (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path fill="currentColor" d="…" />
	</SVG>
);
```

The same element doubles as the `Placeholder` icon in `edit.js`, which is why it
lives in its own module rather than inline in `index.js`.

`@wordpress/icons` is a **devDependency** (a peer of `@isudev/gutenberg`) — fine
to import in editor code, never in `view.js` or PHP.

---

## 7. Author-selectable icons (`@isudev/gutenberg`)

No block currently exposes an icon choice to the author. When one does, use the
package's components rather than hand-rolling a picker — see
[.agents/vendor/isudev-gutenberg.md](../.agents/vendor/isudev-gutenberg.md):

- `Icon` — renders one named icon from an injected collection.
- `IconPicker` — accessible grid, with search and clearing.
- `IconSelect` — button + popover around `IconPicker`.

Key constraint: **the package reads no global registry.** Collections are passed
as props (`defaultIcons` / `icons`); `getLocalizedIcons()` is the boundary helper
that reads a `wp_localize_script` global. Do not add a module-level JS registry.

The intended wiring, per the design spec, is that `utils/icon.php` becomes the
backend of that picker: localize `default_icon_paths()` (filtered) onto the
block's own editor-script handle via `generate_block_asset_handle()`, feed it to
`getLocalizedIcons()`, store the chosen **slug** in an attribute, and render it
server-side with `icon( $attributes['iconName'], … )`. Store the slug, never the
markup.

---

## Checklist

- [ ] Path markup only, no `fill` on the path, no `<svg>` wrapper.
- [ ] `lowerCamelCase` slug in `default_icon_paths()`.
- [ ] viewBox override **only** when the glyph is not on the 600 grid.
- [ ] `tools/checks/50-icon.php` extended, default count bumped, `npm run test:php` green.
- [ ] Echoed raw in `render.php` — no `esc_html()`, no `wp_kses()`.
- [ ] Class on the `<svg>` sized in the block's `style.scss`; colour via `currentColor`.
- [ ] Accessible name on the surrounding control, not on the icon.
- [ ] `npm run build` after touching any `render.php`; `npm run test:e2e` after touching header icons.
