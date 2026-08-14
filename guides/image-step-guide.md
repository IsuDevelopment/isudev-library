# Image step guide blocks

`isudev/image-step-guide` (a container) and `isudev/image-step-guide-step`
(a single step, the only allowed child) together port the standalone
`dekode-library/image-step-guide` plugin. This guide covers the `isudev.json`
config schema, the CSS class contract, and the lightbox reimplementation.

## The config key is the parent's name for both blocks

Every config key below lives under `isudev/image-step-guide` in
`isudev.json`, **even the ones the step block reads** — the same
one-key-per-feature shape `isudev/social-share` and `isudev/social-share-network`
use. `isudev/image-step-guide-step`'s `render.php` and `edit.js` both read
`isudev/image-step-guide`'s config, not their own block name.

## Full annotated example

```json
{
  "library": {
    "isudev/image-step-guide": {
      "displayStyle": {
        "disable": false,
        "value": "list"
      },
      "imageLightbox": {
        "disable": false
      },
      "stepLabel": {
        "disable": false,
        "prefix": "Step",
        "visible": true
      },
      "allowedStepBlocks": [
        "core/heading", "core/paragraph", "core/list", "core/buttons"
      ],
      "stepTemplate": [
        ["core/heading", { "placeholder": "Add step title...", "level": 3 }],
        ["core/paragraph", { "placeholder": "Add step description..." }]
      ]
    }
  }
}
```

Every key is optional; the values above are the built-in defaults except
`stepLabel.prefix`, which defaults to `''` (resolved to the translated "Step"
only once nothing else applies — see below).

| Key | Type | Default | Read by | Effect |
| --- | --- | --- | --- | --- |
| `displayStyle.disable` | `boolean` | `false` | wrapper | Hides the Display style control; the guide always uses `displayStyle.value` instead of its own attribute. |
| `displayStyle.value` | `string` (`list`\|`grid`) | `list` | wrapper | The fixed display style used when the control is disabled. |
| `imageLightbox.disable` | `boolean` | `false` | wrapper | Hides the Enlarge on click control everywhere; the lightbox never activates even on content saved with it on. |
| `stepLabel.disable` | `boolean` | `false` | step | Suppresses the label badge entirely, on every step, and hides the per-step override panel. |
| `stepLabel.prefix` | `string` | `''` | step | Default label prefix for every step in this guide. A step's own `labelPrefix` attribute wins when set. |
| `stepLabel.visible` | `boolean` | `true` | step | Default label visibility. A step's own `labelHidden` attribute can still hide it individually; neither can force it back on when `stepLabel.disable` is true. |
| `allowedStepBlocks` | `string[]` | `["core/heading","core/paragraph","core/list","core/buttons"]` | step (editor only) | Which blocks a step's content area accepts. |
| `stepTemplate` | `array` | a heading + a paragraph | step (editor only) | The blocks inserted into a new step, as `[name, attributes]` tuples. |

A malformed value never breaks the editor: `allowedStepBlocks` and
`stepTemplate` go through `asArray()` from `src/utils/config.js` and fall back
to the default when the shape is wrong, mirroring the `is_array()` guards on
the PHP side.

### Label prefix resolution order

1. The step's own `labelPrefix` attribute, if not blank.
2. `stepLabel.prefix` from `isudev.json`, if not blank.
3. The translated word "Step".

`stepLabel.disable` overrides all three: no label renders regardless of
either source.

### Variation scoping

Every key above also resolves under `variations.<namespace>`, for both
blocks:

```json
"isudev/image-step-guide": {
  "displayStyle": { "value": "list" },
  "variations": {
    "compact": { "displayStyle": { "value": "grid" } }
  }
}
```

The namespace lives on the wrapper as its `_namespace` attribute and reaches
the step block through block context —
`providesContext: { "isudev/imageStepGuideNamespace": "_namespace" }` on the
wrapper, `usesContext` on the child.

## Deliberate simplification from the source plugin

The source plugin copied `isudev.json`-equivalent (`library.json`) values into
the wrapper's own block **attributes** once, on mount, via several `useEffect`
hooks (`hasInitializedDisplayType`, `hasInitializedLabelSettings`,
`hasInitializedLightboxSettings`). That meant a theme changing its config
after a guide was already inserted had no effect on existing content without
re-triggering those effects.

This port reads `isudev.json` live instead, through `get_block_config()` /
`getBlockConfig()` at render and edit time — the same approach every other
block in this plugin uses. There is no attribute-seeding step, and the six
context keys the source threaded from wrapper to step
(`stepAllowedInnerBlocks`, `stepTemplate`, `stepLabelPrefix`,
`stepLabelVisible`, `displayStyle` and `useImageLightbox` all as block
context) are reduced to config reads under a shared block name, the same
pattern `isudev/social-share-network` already uses for its parent's config.
Only `_namespace` still needs context, because it is genuinely per-instance
state, not configuration.

The source's per-namespace "variation" JS logic (`getVariation()`,
`getVariationLibSettings()` in `edit.js`, switching `stepTemplate` /
`stepAllowedInnerBlocks` based on which variation's `displayStyle` matched)
is gone entirely, replaced by this plugin's existing generic
`IsuDevLibrary\Variations` system — the same one every other block with
`'variations' => true` in its descriptor already gets, no special-case code
needed.

## The image lightbox is a from-scratch reimplementation

The source plugin's lightbox reused WordPress core's own `core/image` block
lightbox by string-building an `<!-- wp:image {"lightbox":{"enabled":true}} -->`
comment and running it through `do_blocks()`, gated behind
`version_compare( $wp_version, '6.4', '>=' )`. That ties the feature to core's
own internal markup and behaviour for the image block, which this plugin does
not otherwise depend on anywhere.

Instead, `view.js` implements a small self-contained overlay: a delegated
click handler on `.isudev-image-step-guide.has-lightbox-images
.isudev-image-step-guide-step__media img` builds a `<div
class="isudev-image-step-guide-lightbox">` with the full-size image and a
close button, appended to `<body>`, closable by Escape, an outside click, or
the close button. No WordPress version check, no `do_blocks()`, no core block
internals.

Two PHP hooks the source exposed for this are gone as a result:
`dekode-library/image-step-guide/image/size` and
`dekode-library/image-step-guide/step/image/html`. The rendered image size is
a fixed `large`, matching every other image this plugin renders through
`wp_get_attachment_image()`.

## CSS class contract

Renamed from the source's `dekode-image-step-guide*` classes to
`isudev-image-step-guide*`, styled as this plugin's own classes — never
`.wp-block-isudev-image-step-guide` — so the styles survive a block rename.

### `isudev/image-step-guide` (wrapper)

- Root: `isudev-image-step-guide`, `is-display-{list|grid}`,
  `has-lightbox-images` when the lightbox is on
- `isudev-image-step-guide__items` (the inner-blocks container)

### `isudev/image-step-guide-step` (child)

- Root: `isudev-image-step-guide-step`
- `isudev-image-step-guide-step__media`,
  `isudev-image-step-guide-step__media-placeholder` (no image chosen yet)
- `isudev-image-step-guide-step__content`,
  `isudev-image-step-guide-step__content-inner` (the step's own inner blocks)
- `isudev-image-step-guide-step__label` — the numbered badge. The visible
  number comes from a CSS counter (`counter-reset` on the wrapper,
  `counter-increment` on `::after`), not from PHP or JS, on the frontend; the
  editor iframe suppresses that counter and shows a JS-computed number instead
  so reordering steps updates the label live.

### Lightbox overlay (appended to `<body>`, not scoped to the block)

- `isudev-image-step-guide-lightbox`,
  `isudev-image-step-guide-lightbox__image`,
  `isudev-image-step-guide-lightbox__close`
- `isudev-image-step-guide-lightbox-open` on `<body>` while the overlay is open

## No stylesheet on the step block

`isudev/image-step-guide-step`'s `block.json` declares no `style` /
`editorStyle` — every rule for its markup lives in the wrapper's
`style.scss` / `editor.scss`, matching the source plugin's own asset layout.
This works because a step can only ever exist inside the wrapper
(`"parent": ["isudev/image-step-guide"]`), which is always the block actually
enqueued.

## The editor config bridge

Both blocks' `edit.js` read `isudev.json` through `getBlockConfig()` in
`src/utils/config.js`, which mirrors
`IsuDevLibrary\Config\resolve_block_value()` against the
`window.isudevLibraryConfig` global that `Config\boot()` publishes once for
the whole block editor. There is no JS test runner in this repo to catch
drift between the two — change them together.
