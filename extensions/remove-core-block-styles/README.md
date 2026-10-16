# Remove core block styles

Several core blocks come with styles of their own in the block's **Styles**
panel: Button *Fill / Outline*, Image *Rounded*, Quote *Plain*, Separator
*Wide / Dots*, Site Logo *Rounded*, Social Icons *Logos only / Pill shape*,
Table *Stripes*, Tag Cloud *Outline*. They rarely match the design and sit
next to the theme's own styles. This extension removes them, so the Styles
panel lists only what the theme (or a plugin) registers.

## What it changes

- **Editor** (post, site and widget editors): every `core/*` block registers
  without the `styles` from its block.json. The list is not hard-coded — a
  style core adds to a block in a later release goes too.
- Styles the theme registers — `register_block_style()` in PHP,
  `wp.blocks.registerBlockStyle()` in JS, `styles/blocks/*.json` partials —
  are separate registrations and stay.

## Keeping some

In the theme's `isudev.json`, under `keep`: `core/<block>` keeps all of a
block's core styles, `core/<block>:<style>` keeps one.

```json
{
	"library": {
		"extensions": {
			"remove-core-block-styles": {
				"enabled": true,
				"keep": [ "core/table", "core/image:rounded" ]
			}
		}
	}
}
```

Or in code (runs after `isudev.json`; entries in any other shape are dropped):

```php
add_filter(
	'isudev_library/extensions/remove_core_block_styles/keep',
	function ( array $keep ): array {
		$keep[] = 'core/separator:dots';
		return $keep;
	}
);
```

When a block keeps some styles but not its default one, the editor adds a
*Default* entry itself.

## What it does not do

- **Existing content keeps its class.** A button saved as Outline still
  carries `is-style-outline` and still looks like core's outline: core's
  block CSS and any theme.json `styles.blocks.core/button.variations.outline`
  keep applying. To change it, pick another style, or — when the block has
  no styles left and the panel is hidden — delete the `is-style-…` class
  under Advanced → *Additional CSS class(es)*.
- **It changes nothing on the server.** Clearing the styles there would not
  reach the editor (core blocks take `styles` from their client-side
  block.json, which overrides the server definition) and would make core stop
  generating theme.json variation CSS for existing content.
- Works with [Button colour](../button-color/README.md), which removes core's
  button *Outline* on its own; with both on, Outline is simply gone. Keeping
  `core/button:outline` here does not bring it back while Button colour's
  `remove_core_outline` filter is on.

## What it relies on

The `blocks.registerBlockType` JS filter and core blocks being registered
after the editor scripts load (core calls `registerCoreBlocks()` from
`initializeEditor()` on DOM ready). A block registered before this script
runs keeps its styles.

## How to check it still works

Insert a Button and an Image: the Styles panel shows only the theme's
styles (or none). In the browser console,
`wp.blocks.getBlockStyles( 'core/table' )` returns only non-core styles.
