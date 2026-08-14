# Toggle Content block

`isudev/toggle-blocks` ports the standalone `dekode-library/toggle-blocks`
plugin's collapsible toggle. This guide covers the `isudev.json` config schema
and the CSS class contract.

## Full annotated example

```json
{
  "library": {
    "isudev/toggle-blocks": {
      "iconPosition": "right",
      "icons": {
        "open": "chevronDown",
        "close": "close"
      }
    }
  }
}
```

Every key is optional; neither `icons.open` nor `icons.close` has a built-in
default, so no icon renders unless the theme configures one.

| Key | Type | Default | Effect |
| --- | --- | --- | --- |
| `iconPosition` | `string` (`left` \| `right`) | `right` | Which side of the button text the icon renders on. |
| `icons.open` | `string` | none | Icon registry name shown while the content is closed. |
| `icons.close` | `string` | none | Icon registry name shown while the content is open. Falls back to `icons.open` when unset, and vice versa. |

### Variation scoping

Both keys also resolve under `variations.<namespace>`:

```json
"isudev/toggle-blocks": {
  "iconPosition": "right",
  "variations": {
    "accordion-item": { "iconPosition": "left" }
  }
}
```

The namespace lives on the block's own `_namespace` attribute, injected by
`IsuDevLibrary\Variations` for any variation registered under this block's
`variations` key in `isudev.json`.

## No fixed template

Unlike `isudev/social-share`, this block does not restrict or template its
inner blocks — any block can go inside the toggle. That is a deliberate
departure from the source plugin's `settings.allowedInnerBlocks` /
`settings.template` config, which was never used to restrict content in
practice.

## CSS class contract

Renamed from the source's `toggle-block*` classes to `isudev-toggle*`, styled
as this plugin's own classes — never `.wp-block-isudev-toggle-blocks` — so the
styles survive a block rename.

- Root: `isudev-toggle`, `isudev-toggle--placement-{top|bottom}`
- `isudev-toggle__button-wrapper`,
  `isudev-toggle__button-wrapper--placement-{top|bottom}`, plus
  `is-style-{slug}` when a `core/button` style is selected
- `isudev-toggle__button`, `isudev-toggle__button--icon-{left|right}`
- `isudev-toggle__text`, `isudev-toggle__icons`, `isudev-toggle__icon`,
  `isudev-toggle__icon--open` / `--close`
- `isudev-toggle__content`

The animation duration is a CSS custom property, `--isudev-toggle-speed`,
declared inline on the root element from the block's `toggleSpeed` attribute.

## Frontend events

`view.js` dispatches `isudev/toggle:open` and `isudev/toggle:close` on
`document`, `bubbles: true`, with `detail: { block, button, content, blockId }`
— renamed from the source plugin's `dekode/toggle:open` / `:close`.

## The editor config bridge

`edit.js` reads `isudev.json` through `getBlockConfig()` in
`src/utils/config.js`, which mirrors
`IsuDevLibrary\Config\resolve_block_value()` against the
`window.isudevLibraryConfig` global that `Config\boot()` publishes once for
the whole block editor. There is no JS test runner in this repo to catch drift
between the two — change them together.
