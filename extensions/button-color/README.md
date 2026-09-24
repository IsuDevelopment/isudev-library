# Button colour for outline and link styles

Makes the colour an editor picks on a **Button** block available to the theme's
stylesheet, so outline and text-only buttons can be drawn in that colour.

## The problem it solves

Core writes a picked button colour onto the inner `<a>` as a preset class
(`has-primary-background-color`) that carries `!important`. CSS can neither
cancel that fill nor read the colour back. That is why core's own **Outline**
style breaks the moment a colour is picked: you get a filled button with a
border, not an outline in that colour.

## What it changes

- **Site:** every `core/button` with a picked background colour gets
  `--isudev-button-color: <colour>` on its `.wp-block-button` wrapper. A preset
  stays a preset reference (`var(--wp--preset--color--primary)`), so the button
  follows palette changes. A custom colour is copied as picked.
- **Editor:** the same property on the block's canvas wrapper, so a contour
  button looks the same while editing.
- **Editor:** core's **Outline** style is removed from the Styles panel — two
  entries called "Outline", one of them broken, is worse than one that works.

It adds **no styles and no CSS**. The theme registers its own button styles and
reads the property, for example:

```css
.wp-block-button.is-style-outline-only .wp-block-button__link {
	background-color: transparent !important; /* outranks core's preset class */
	border: 1px solid var(--isudev-button-color, currentColor);
	color: var(--isudev-button-color, inherit);
}
```

`!important` on the background is required: it is the only thing that outranks
core's colour classes. Icons inside the button (`currentColor` SVGs) follow the
text colour automatically.

## Filters

```php
// Another property name (must start with `--`; anything else falls back to the default).
add_filter( 'isudev_library/extensions/button_color/property', fn() => '--my-button-color' );

// Keep core's Outline style in the editor.
add_filter( 'isudev_library/extensions/button_color/remove_core_outline', '__return_false' );
```

## What it does not do

- It does not rewrite saved content; the property is added at render time.
- It reads the **background** colour only (the one that makes a filled button).
  A picked text colour stays core's business.
- Existing blocks that already use core's Outline style keep the class
  `is-style-outline` in their markup; only the editor entry is removed.

## When it seems to do nothing

- No colour picked on the button → no property; the theme's fallback applies.
- The property is on the **wrapper** (`.wp-block-button`), not on the `<a>`.
  Read it from the link with inheritance, as in the example.
- In the editor, the canvas updates on the next render after picking a colour.

## Relies on (what a core update can break)

- `render_block_core/button` and core's button markup: a `div.wp-block-button`
  wrapper around the link.
- Attributes `backgroundColor` (preset slug) and `style.color.background`.
- Editor: the `blocks.registerBlockType` filter and `getEditWrapperProps`; core
  naming its style `outline`.

Check: insert a Button, pick a colour, switch to the theme's outline style —
the wrapper carries `--isudev-button-color` in the editor canvas and on the page.
