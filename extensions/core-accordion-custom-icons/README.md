# Accordion toggle icons

Replaces the "+" that core's Accordion block draws on every item with two icons
from the library's icon registry: one while the item is closed, one while it is
open. Core's 45° rotation of the "+" is switched off, because the open icon
already says "open".

## What it changes

- **Editor.** The Accordion block (the outer `core/accordion`, not an item) gets
  a **Toggle icons** panel in its sidebar: **Closed icon**, **Open icon** and
  **Icon size (px)** (8–128). Leaving a field empty uses the theme's default,
  which the field's placeholder names. The canvas shows the chosen icons as you
  pick them.
- **Front end.** Inside each item's own heading, core's
  `<span class="wp-block-accordion-heading__toggle-icon">+</span>` keeps its
  place (left or right, per the accordion's **Icon position**) and its
  `aria-hidden`, gains the class `is-isudev-icon` and the property
  `--isudev-accordion-icon-size`, and its "+" becomes two icons:
  `svg.isudev-accordion-icon.isudev-accordion-icon--closed` and
  `svg.isudev-accordion-icon.isudev-accordion-icon--open`. A small stylesheet
  shows the one matching the item's `is-open` class.
- **Show icon off** on an accordion means core renders no icon span, and
  nothing is added.

Nested accordions are independent: an item only styles its own heading, and a
nested accordion uses its own icons (or the defaults), never its parent's.

Saved content is not rewritten. Switching the extension off brings the "+"
back; the three attributes stay in the block comment, unused.

## Theme defaults — `isudev.json`

```json
{
	"library": {
		"extensions": {
			"core-accordion-custom-icons": {
				"enabled": true,
				"closedIcon": "chevronDown",
				"openIcon": "chevronUp",
				"size": 20
			}
		}
	}
}
```

Names are keys of the icon registry (`isudev_library/icons`; a theme adds its
own there). Without settings: `chevronDown`, `chevronUp`, 24 px. An unknown name
falls back to the default; if the default is unknown too, the "+" stays.

Icons are drawn in the text colour (`fill: currentColor`). Size them in CSS if
you prefer: `.is-isudev-icon { --isudev-accordion-icon-size: 1em; }`.

## Hooks

### `isudev_library/extensions/core_accordion_custom_icons/defaults`

The defaults after `isudev.json`, as `closed`, `open` (icon names) and `size`
(pixels, clamped to 8–128). Also what the editor shows as the default.

```php
add_filter(
	'isudev_library/extensions/core_accordion_custom_icons/defaults',
	function ( array $defaults ): array {
		$defaults['closed'] = 'arrowForward';
		return $defaults;
	}
);
```

The icon markup itself goes through the registry's `isudev_library/icon` filter.

## What it relies on and does not own

- Core blocks `core/accordion` and `core/accordion-heading` (WordPress 6.9+,
  checked against 7.1.3): the heading's static markup
  `button.wp-block-accordion-heading__toggle > span.wp-block-accordion-heading__toggle-icon`
  holding exactly the text `+`, the item's `is-open` class and core's rule
  `.wp-block-accordion-item.is-open > .wp-block-accordion-heading .wp-block-accordion-heading__toggle-icon { transform: rotate(45deg) }`.
  If core changes the span's contents, the replacement steps aside and the "+"
  shows — it never guesses.
- `register_block_type_args`: three attributes (`isudevClosedIcon`,
  `isudevOpenIcon`, `isudevIconSize`) on `core/accordion`, passed down as block
  context (`isudev/accordion-closed-icon`, `isudev/accordion-open-icon`,
  `isudev/accordion-icon-size`) that `core/accordion-heading` uses. Context
  flows through `core/accordion-item` untouched.
- `render_block_core/accordion-heading`, the HTML API (`WP_HTML_Tag_Processor`)
  and `wp_enqueue_block_style( 'core/accordion' )` for `style.css` (front end
  only on pages with an accordion; editor canvas through `enqueue_block_assets`).
- Editor: `blocks.registerBlockType` (attributes and `getEditWrapperProps`, which
  puts `has-isudev-accordion-icons` and the properties
  `--isudev-accordion-icon-closed` / `-open` / `-size` on the accordion's wrapper;
  `style.css` paints them as a CSS mask), `editor.BlockEdit` for the panel, and
  `window.isudevIcons`.

## When it does not work

- **Still a "+".** The accordion has **Show icon** off; or the icon name is not
  in the registry and neither is the default; or a cache serves old HTML. Check
  the page source for `is-isudev-icon`.
- **Both icons show at once.** `style.css` did not load — look for the handle
  `isudev-library-core-accordion-custom-icons` in the page. A theme that dequeues
  block styles takes it with them.
- **The icon still turns 45°.** A theme rule more specific than ours rotates it;
  target `.is-isudev-icon` with `transform: none`.
- **No Toggle icons panel.** Select the outer Accordion block, not an item or
  heading. The editor script is missing until `npm run build` has produced
  `build/extensions/core-accordion-custom-icons/editor.js`.

## How to check it still works

Insert an Accordion with two items, nest an accordion in the first item's panel,
pick different icons for the inner one. On the front end: closed items show the
closed icon, open ones the open icon, no rotation, the inner accordion keeps its
own icons while the outer item is open. Toggle **Icon position** to left, then
**Show icon** off: the icon moves, then disappears. `php tools/check.php`
covers the pure helpers (`250-core-accordion.php`).
