# Bento grid blocks

`isudev/bento-grid` (a container) and `isudev/bento-card` (a single cell,
the only allowed child) together port the standalone `isudev/bento-grid`
plugin — it already used the `isudev` namespace, so neither block name
changed. This guide covers the `isudev.json` config schema, the JS filters,
and the CSS custom property contract.

## Unlike other paired blocks, each keeps its own config key

`isudev/social-share` and `isudev/social-share-network` share one config key
because they are one feature. Bento grid and card are more independent —
layout (the grid's job) and content defaults (the card's job) are separate
concerns — so each block reads its **own** name in `isudev.json`, matching
the source plugin's own `library.json` schema.

## Grid: custom layout variations

```json
{
  "library": {
    "isudev/bento-grid": {
      "customVariations": [
        {
          "name": "news-grid",
          "title": "News Grid",
          "icon": "<svg viewBox=\"0 0 48 48\" xmlns=\"http://www.w3.org/2000/svg\">…</svg>",
          "innerBlocks": [
            {
              "_namespace": "news-grid",
              "gridColumn": { "desktop": { "start": "auto", "span": 6 } },
              "gridRow": { "desktop": { "start": "auto", "span": 1 } }
            }
          ]
        }
      ]
    }
  }
}
```

A custom variation with the same `name` as a built-in one (`layout-1`
through `layout-5`) replaces it; anything else is appended. Every card in a
variation's `innerBlocks` should carry a matching `_namespace` so
`isudev/bento-card`'s own config (below) can be scoped to that layout.

**Icon formats:** inline SVG (starts with `<`), a file URL (starts with `/`
or `http`), or a Dashicon class (starts with `dashicons-`).

## Card: content defaults

```json
{
  "library": {
    "isudev/bento-card": {
      "allowedBlocks": ["core/heading", "core/paragraph", "core/image", "core/buttons"],
      "template": [
        ["core/heading", { "level": 3, "placeholder": "Card heading" }],
        ["core/paragraph", { "placeholder": "Card content..." }]
      ],
      "templateLock": false,
      "variations": {
        "layout-1": {
          "template": [
            ["core/heading", { "level": 2 }],
            ["core/paragraph", {}],
            ["core/buttons", {}]
          ]
        }
      }
    }
  }
}
```

| Key | Type | Default | Effect |
| --- | --- | --- | --- |
| `allowedBlocks` | `string[]` | `["core/heading","core/paragraph","core/image","core/buttons","core/list"]` | Blocks a card's content area accepts. |
| `template` | `array` | `[]` (no enforced content) | Blocks inserted into a new card, as `[name, attributes]` tuples. |
| `templateLock` | `false` \| `"all"` \| `"insert"` \| `"contentOnly"` | `false` | Template lock mode. |

### Variation scoping

Every key above also resolves under `variations.<namespace>` — the same
`get_block_config()` / `getBlockConfig()` mechanism every other paired block
in this plugin uses, keyed here by the `_namespace` the **grid's** variation
picker set on each card, not by `IsuDevLibrary\Variations`. Lookup order: the
card's own `_namespace` → the global `isudev/bento-card` config → the JS
defaults.

## JS filters

Registered with `@wordpress/hooks`, not PHP — these affect the editor only.

| Filter | Default | Purpose |
| --- | --- | --- |
| `isudevLibrary.bentoGrid.variations` | — | Add, remove or modify the merged (built-in + `customVariations`) layout list shown in the picker. |
| `isudevLibrary.bentoGrid.showGapControl` | `true` | Return `false` to hide the Gap control. |
| `isudevLibrary.bentoGrid.showCardSizeControl` | `true` | Return `false` to hide the Min. card height control. |

```js
wp.hooks.addFilter(
	'isudevLibrary.bentoGrid.variations',
	'my-theme/no-layout-4',
	(variations) => variations.filter((v) => v.name !== 'layout-4')
);
```

## CSS custom properties

The grid sets these on its own wrapper; cards and child styles read them:

| Property | Set by | Description |
| --- | --- | --- |
| `--bento-cols-desktop` / `-tablet` / `-mobile` | grid | Column count per breakpoint. |
| `--bento-gap-desktop` / `-tablet` / `-mobile` | grid | Gap, in pixels, per breakpoint. |
| `--bento-min-card-height` | grid | Minimum row track height, in pixels. |

Each card additionally sets `--grid-col-{breakpoint}` and
`--grid-row-{breakpoint}` (each already the complete `span N` value) on
itself, consumed by its own stylesheet's media queries.

## The editor's device switcher

Both blocks' `edit.js` use `@isudev/gutenberg`'s `useBreakpoint()` (synced to
and from the editor's own device preview) and `BreakpointSwitcher`, replacing
the source's `@t2/editor` `DevicePanelBody`. The attribute shape is
unchanged from the source — `columns` / `gap` on the grid and `gridColumn` /
`gridRow` on the card stay single objects keyed by `desktop` / `tablet` /
`mobile`, rather than being split into `@isudev/gutenberg`'s
`ResponsiveControl` per-breakpoint-suffix attributes
(`columnsTablet`, `columnsMobile`, …). That flat-attribute convention doesn't
fit here: the grid needs all three breakpoints' values simultaneously (to
clamp card spans when columns shrink) and the card needs dual-binding with
its parent, neither of which `ResponsiveControl`'s one-control-at-a-time
render prop is built for.

## No T2 dependency

The source read `@t2/editor`'s `DevicePanelBody` and `getLibraryBlockConfig`
from `@helpers`. Both are gone: the device switcher comes from
`@isudev/gutenberg` (above), and config comes from `isudev.json` through
`getBlockConfig()` in `src/utils/config.js`, the same as every other block in
this plugin.

## The editor config bridge

`edit.js` in both blocks reads `isudev.json` through `getBlockConfig()` in
`src/utils/config.js`, which mirrors
`IsuDevLibrary\Config\resolve_block_value()` against the
`window.isudevLibraryConfig` global that `Config\boot()` publishes once for
the whole block editor. There is no JS test runner in this repo to catch
drift between the two — change them together.
