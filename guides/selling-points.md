# Selling points blocks

`isudev/selling-points` (a container) and `isudev/selling-point` (a single
point, the only allowed child) together port the standalone
`extended-selling-points` plugin — renamed at the user's request, dropping
the "extended-" prefix and the leftover `t2/` namespace from before this
plugin's own port. This guide covers the `isudev.json` config schema and the
CSS class contract.

## The config key is the parent's name for both blocks

Every config key below lives under `isudev/selling-points` in `isudev.json`,
**even the ones the point block reads** — the same one-key-per-feature shape
`isudev/social-share` and `isudev/social-share-network` use.

## Full annotated example

```json
{
  "library": {
    "isudev/selling-points": {
      "features": {
        "hasIcon": true,
        "allowedIcons": null,
        "hasImage": false,
        "hasDescription": true,
        "hasLink": true,
        "hasBadge": true
      },
      "iconSize": 28,
      "imageSize": "medium_large",
      "defaultColumns": 12,
      "template": [
        ["isudev/selling-point"],
        ["isudev/selling-point"],
        ["isudev/selling-point"]
      ]
    }
  }
}
```

Every key is optional; the values above are the built-in defaults.

| Key | Type | Default | Read by | Effect |
| --- | --- | --- | --- | --- |
| `features.hasIcon` | `boolean` | `true` | point | Whether a point can carry an icon at all. `false` hides every icon control and the icon never renders, even on content saved with one. |
| `features.allowedIcons` | `string[]` \| `null` | `null` (every registered icon) | point (editor only) | Restricts which icons the picker offers. |
| `features.hasImage` | `boolean` | `false` | point | Whether a point can carry an image. |
| `features.hasDescription` | `boolean` | `true` | point (editor only) | Whether the description field is shown in the editor. Existing description text still renders on the frontend even if later turned off — matching the source plugin's own behaviour. |
| `features.hasLink` | `boolean` | `true` | point (editor only) | Whether the "show link text" toggle is offered. The link itself (via the block toolbar) is always available; this only gates the separate link-text field. |
| `features.hasBadge` | `boolean` | `true` | point (editor only) | Whether the badge toggle is offered. Existing badge text still renders even if later turned off, same caveat as the description. |
| `iconSize` | `int` | `28` | point | Icon size in pixels, editor and frontend, when the wrapper doesn't already provide one through `isudev/sellingPointsIconSize` block context. |
| `imageSize` | `string` | `medium_large` | point | WordPress image size used for the point's image. |
| `defaultColumns` | `int` | `12` | wrapper | The column span (out of 60) every point uses unless it picks its own Width. |
| `template` | `array` | three points | wrapper (editor only) | The blocks inserted into a new grid, as `[name, attributes]` tuples. |

A malformed value never breaks the editor: `features.allowedIcons` and
`template` go through `asArray()` from `src/utils/config.js` and fall back to
the default when the shape is wrong, mirroring the `is_numeric()` /
`is_array()` guards on the PHP side.

### Variation scoping

Every key above also resolves under `variations.<namespace>`, for both
blocks, the same pattern `isudev/social-share` uses. The namespace lives on
the wrapper as its `_namespace` attribute and reaches the point block through
block context — `providesContext` on the wrapper, `usesContext` on the
child.

## No T2 dependency

The source read every setting above through `T2\Config\get_config_variable()`
/ `@t2/editor`'s `getConfig()` against `t2.json`, rendered icons through
`T2\Icons\get_icon()`, picked them with `@t2/editor`'s `BlockIconSelector`,
and handled media with `@t2/editor`'s `MediaSuitePicker` / `MediaSuiteViewer`.
None of that remains:

- Config comes from `isudev.json` through `IsuDevLibrary\Config` /
  `getBlockConfig()`, the same as every other block in this plugin.
- Icons render through the shared `IsuDevLibrary\Utils` registry, picked in
  the editor with `@isudev/gutenberg`'s `IconSelect`. The registry is
  currently narrower than T2's — a theme that needs more icons available can
  add them through the `isudev_library/icons` PHP filter (see
  `guides/work-with-icons.md`), the same extension point every other block
  in this plugin already relies on.
- Media uses `@isudev/gutenberg`'s `MediaControl` (canvas surface only,
  matching the source's single toolbar-picker simplicity), with the point's
  three flat `mediaId` attribute replaced by one `media` object attribute,
  matching `isudev/read-more`'s shape.
- The point's hand-rolled `link-popup` component is replaced by
  `@isudev/gutenberg`'s `BlockLinkControl`. The stored `link` shape changes
  from the source's `{ rel, url, linkTarget, nofollow }` to
  `{ url, opensInNewTab, nofollow, … }`, matching every other link attribute
  in this plugin. `render.php` rebuilds `target`/`rel` from
  `opensInNewTab`/`nofollow` itself rather than trusting a client-supplied
  `rel` string verbatim — the same hardening `isudev/read-more` already
  applies to its own link.

## Dropped: `t2AddBeforeAfter`, block styles, and the dead `templateLock`

- `t2AddBeforeAfter` was a T2-specific block support (pseudo-element style
  controls) with no equivalent in this plugin and no other block here uses
  anything like it. Dropped.
- The source's two named block styles ("ciemny"/dark, "accent") shipped no
  CSS of their own in the plugin — they depended entirely on T2 theme
  styles for `.is-style-ciemny` / `.is-style-accent` that don't exist here.
  Keeping the metadata without the styling it depended on would be inert, so
  it's dropped along with the T2 dependency.
- The wrapper's `edit.js` destructured a `templateLock` attribute that was
  never declared in `block.json` — always `undefined` (WordPress's
  `templateLock={undefined}` is a no-op), so it was already dead code in the
  source. Not carried over.

## CSS class contract

Renamed from the source's `t2-extended-selling-point*` classes to
`isudev-selling-point*`, and from `--t2-extended-selling-point(s)-*` custom
properties to `--isudev-selling-point(s)-*`, styled as this plugin's own
classes — never `.wp-block-isudev-selling-points` — so the styles survive a
block rename.

### `isudev/selling-points` (wrapper)

- Root: `isudev-selling-points`, `has-animate-in` when reveal-on-scroll is on
  (plus `is-visible` once each point has revealed)

### `isudev/selling-point` (child)

- Root: `isudev-selling-point`, `is-size-{20|25|33|50|75|100}` for an
  explicit Width, `has-icon` / `has-image` / `has-link`
- `isudev-selling-point__icon`, `isudev-selling-point__badge`,
  `isudev-selling-point__figure`, `isudev-selling-point__title`,
  `isudev-selling-point__description`, `isudev-selling-point__link`

## No stylesheet on the point block

`isudev/selling-point`'s `block.json` declares no `style` / `editorStyle` —
every rule for its markup lives in the wrapper's `style.scss`, matching the
source plugin's own asset layout and the same pattern
`isudev/image-step-guide-step` and `isudev/agenda-accordion-item` use. This
works because a point can only ever exist inside the wrapper
(`"parent": ["isudev/selling-points"]`), which is always the block actually
enqueued.

## The editor config bridge

Both blocks' `edit.js` read `isudev.json` through `getBlockConfig()` in
`src/utils/config.js`, which mirrors
`IsuDevLibrary\Config\resolve_block_value()` against the
`window.isudevLibraryConfig` global that `Config\boot()` publishes once for
the whole block editor. There is no JS test runner in this repo to catch
drift between the two — change them together.
