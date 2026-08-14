# Agenda accordion blocks

`isudev/agenda-accordion` (a container) and `isudev/agenda-accordion-item`
(a single item, the only allowed child) together port the standalone
`dekode-library/agenda-accordion` plugin. This guide covers the
`isudev.json` config schema and the CSS class contract.

## The config key is the parent's name for both blocks

Every config key below lives under `isudev/agenda-accordion` in
`isudev.json`, **even the ones the item block reads** — the same
one-key-per-feature shape `isudev/social-share` and `isudev/social-share-network`
use.

## Full annotated example

```json
{
  "library": {
    "isudev/agenda-accordion": {
      "allowMultiple": true,
      "allowToggle": true,
      "icons": {
        "closed": "chevronDown",
        "open": "chevronUp",
        "size": 24
      },
      "allowedBlocks": [],
      "template": [
        ["core/paragraph", { "placeholder": "Write description..." }]
      ],
      "excerpt": {
        "insideTrigger": false
      }
    }
  }
}
```

Every key is optional; the values above are the built-in defaults.

| Key | Type | Default | Read by | Effect |
| --- | --- | --- | --- | --- |
| `allowMultiple` | `boolean` | `true` | wrapper | Whether more than one item may be open at once. |
| `allowToggle` | `boolean` | `true` | wrapper | Whether an open item may be closed again. Only meaningful when `allowMultiple` is `false` — with multiple open items, toggling closed is always possible. |
| `icons.closed` / `icons.open` | `string` | `chevronDown` / `chevronUp` | item | Icon registry names for the trigger's collapsed / expanded state. |
| `icons.size` | `int` | `24` | item | Icon size in pixels. |
| `allowedBlocks` | `string[]` | `[]` (unrestricted) | item (editor only) | Which blocks an item's content area accepts. Empty means any block. |
| `template` | `array` | one paragraph | item (editor only) | The blocks inserted into a new item, as `[name, attributes]` tuples. |
| `excerpt.insideTrigger` | `boolean` | `false` | item | When `true`, the excerpt renders inside the `<button>` (part of its click target) instead of as a sibling below it. |

A malformed value never breaks the editor: `allowedBlocks` and `template` go
through `asArray()` from `src/utils/config.js` and fall back to the default
when the shape is wrong, mirroring the `is_array()` guards on the PHP side.

### Variation scoping

Every key above also resolves under `variations.<namespace>`, for both
blocks, the same pattern `isudev/social-share` uses. The namespace lives on
the wrapper as its `_namespace` attribute and reaches the item block through
block context — `providesContext: { "isudev/agendaAccordionNamespace":
"_namespace" }` on the wrapper, `usesContext` on the child.

## Deliberate departure: the template no longer depends on `allowedBlocks`

The source plugin only applied its default `template` when `allowedBlocks`
was also non-empty (`template = !isEmpty(ALLOWED_BLOCKS) ? TEMPLATE : []`) —
so a theme that restricted nothing also silently lost the "Write
description…" placeholder paragraph on every new item. This port applies
`template` unconditionally; `allowedBlocks` only ever restricts which blocks
are *available*, never whether a template is inserted.

## No T2 dependency

The source read icon and layout settings through `T2\Config\get_config_variable()`
against `t2.json`, and rendered icons through `T2\Icons\icon()` /
`T2\Icons\get_icon()`. This port has no T2 dependency anywhere: config comes
from `isudev.json` through `IsuDevLibrary\Config`, and icons come from the
shared `IsuDevLibrary\Utils` registry — the `chevronUp` icon was added there
specifically for this block's expanded-state default.

## CSS class contract

Renamed from the source's `dekode-library-agenda-accordion*` classes to
`isudev-agenda-accordion*`, and from `--dekode-library-agenda-accordion-*`
custom properties to `--isudev-agenda-accordion-*`, styled as this plugin's
own classes — never `.wp-block-isudev-agenda-accordion` — so the styles
survive a block rename.

### `isudev/agenda-accordion` (wrapper)

- Root: `isudev-agenda-accordion`, with `data-allow-multiple` /
  `data-allow-toggle` attributes read by `view.js`

### `isudev/agenda-accordion-item` (child)

- Root: `isudev-agenda-accordion-item`, plus `has-no-content` when the item
  has nothing to expand
- `isudev-agenda-accordion__title` (the heading wrapping the trigger),
  `isudev-agenda-accordion__trigger` (the `<button>`),
  `isudev-agenda-accordion__title-inner`, `isudev-agenda-accordion__date`,
  `isudev-agenda-accordion__excerpt`
- `isudev-agenda-accordion-icon`, `.is-closed-icon` / `.is-open-icon`
  (visibility toggled purely by CSS on `[aria-expanded]`, not by JS)
- `isudev-agenda-accordion-item__inner-container` (the expandable panel)

## No stylesheet on the item block

`isudev/agenda-accordion-item`'s `block.json` declares no `style` /
`editorStyle` — every rule for its markup lives in the wrapper's
`style.scss` / `editor.scss`, matching the source plugin's own asset layout
and the same pattern `isudev/image-step-guide-step` uses. This works because
an item can only ever exist inside the wrapper
(`"parent": ["isudev/agenda-accordion"]`), which is always the block actually
enqueued.

## The editor config bridge

Both blocks' `edit.js` read `isudev.json` through `getBlockConfig()` in
`src/utils/config.js`, which mirrors
`IsuDevLibrary\Config\resolve_block_value()` against the
`window.isudevLibraryConfig` global that `Config\boot()` publishes once for
the whole block editor. There is no JS test runner in this repo to catch
drift between the two — change them together.
