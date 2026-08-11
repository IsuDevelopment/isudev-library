# Social share blocks

`isudev/social-share` (a container) and `isudev/social-share-network` (a
single button, the only allowed child) together port the standalone
`share-to-social-media` plugin's social share buttons. This guide covers the
`isudev.json` config schema, the CSS class contract and the network → icon
map.

## The config key is the parent's name for both blocks

Every config key below lives under `isudev/social-share` in `isudev.json`,
**even the ones the network block reads**. The source plugin used a single
`library.json` key for both its wrapper and its network buttons, and this
keeps that one-key-per-feature shape instead of splitting it across two block
names. `isudev/social-share-network`'s `render.php` and `edit.js` both read
`isudev/social-share`'s config, not their own block name — that is
intentional, not a bug, if you go looking for it under
`isudev/social-share-network` and find nothing.

## Full annotated example

```json
{
  "library": {
    "isudev/social-share": {
      "prefix": {
        "disable": false,
        "allowToggle": true,
        "allowPosition": true
      },
      "networkLabel": {
        "disable": false,
        "allowToggle": true,
        "allowPosition": true
      },
      "allowedNetworks": [
        "facebook", "x", "linkedin", "whatsapp", "bluesky", "threads",
        "mastodon", "substack", "link", "email", "print", "system"
      ],
      "defaultTemplate": ["facebook", "x", "linkedin", "link"],
      "features": {
        "allowAlignControl": true
      },
      "iconsSize": 24,
      "icons": {
        "facebook": "socialFacebook"
      },
      "email": {
        "subject": "Have a look at this article: %title%",
        "body": "You might be interested in this: %title%, %url%"
      },
      "link": {
        "copyText": "%url%"
      }
    }
  }
}
```

Every key is optional; the values above are the built-in defaults except
`icons`, which defaults to `{}` (every network falls back to its default
registry icon).

| Key | Type | Default | Read by | Effect |
| --- | --- | --- | --- | --- |
| `prefix.disable` | `boolean` | `false` | wrapper | Hides the prefix UI entirely and suppresses the prefix in the rendered markup. |
| `prefix.allowToggle` | `boolean` | `true` | wrapper (editor only) | Shows the "Show prefix text" toggle. |
| `prefix.allowPosition` | `boolean` | `true` | wrapper (editor only) | Shows the prefix position select. |
| `networkLabel.disable` | `boolean` | `false` | network | Suppresses the label everywhere, overriding a button's own `showLabel`. |
| `networkLabel.allowToggle` | `boolean` | `true` | network (editor only) | Shows the "Show label" toggle. |
| `networkLabel.allowPosition` | `boolean` | `true` | network (editor only) | Shows the label position select. |
| `allowedNetworks` | `string[]` | all twelve | wrapper + network (editor only) | Restricts which networks may be inserted or selected. |
| `defaultTemplate` | `string[]` | `["facebook","x","linkedin","link"]` | wrapper (editor only) | Networks inserted with the wrapper, in order, filtered by `allowedNetworks`. |
| `features.allowAlignControl` | `boolean` | `true` | wrapper (editor only) | Shows the content-alignment toolbar. |
| `iconsSize` | `int` | `24` | network | Rendered icon size in pixels, editor and frontend. |
| `icons` | `object` | `{}` | network | Network slug → icon registry name overrides. |
| `email.subject` / `email.body` | `string` | translated defaults | network | `%title%` / `%url%` templates for the email network's `mailto:` link. |
| `link.copyText` | `string` | `%url%` | network | `%title%` / `%url%` template for the text copied to the clipboard. |

`networkLabel.allowCustom` from the source plugin is **not** implemented here:
the source's own `constants.js` resolved it but nothing ever read it — dead
config, not ported.

## Network → icon registry map

`isudev/social-share-network`'s `icon_name()` resolves this default map
before applying an `icons` override:

| Network | Default icon | Notes |
| --- | --- | --- |
| `facebook` | `socialFacebook` | |
| `x` | `socialX` | |
| `linkedin` | `socialLinkedin` | |
| `whatsapp` | `socialWhatsapp` | |
| `bluesky` | `socialBluesky` | |
| `threads` | `socialThreads` | |
| `mastodon` | `socialMastodon` | |
| `substack` | `socialSubstack` | |
| `email` | `email` | |
| `link` | `link` | Renders `<button>`, not `<a>` — see below. |
| `print` | `print` | Renders `<button>`, not `<a>`. |
| `system` | `share` | Native share sheet (Web Share API); renders `<button>`. |

## Link vs button

`link`, `print` and `system` perform an action rather than linking anywhere,
so they render `<button type="button">`. The other nine render `<a href>`.
This is a deliberate departure from the source plugin, which rendered every
network as an `<a>` — including `print`, whose `href="#"` was a link to
nowhere pretending to be a destination. See the CHANGELOG for the rest of the
accessibility departures (no duplicate `title`/`aria-label`, `aria-label`
only when the label text is not already visible).

## CSS class contract

Renamed from the source's `dk-share-social*` classes to `isudev-share*`,
styled as this plugin's own classes — never `.wp-block-isudev-social-share`
or `.wp-block-isudev-social-share-network` — so the styles survive a block
rename.

### `isudev/social-share` (wrapper)

- Root: `isudev-share`, `isudev-share--align-{contentAlignment}`,
  `isudev-share--prefix-{position}`
- `isudev-share__content`, `isudev-share__row`, `isudev-share__items` (the
  inner-blocks container), `isudev-share__prefix`,
  `isudev-share__prefix--above` / `--before` / `--after`

### `isudev/social-share-network` (child)

- Root (`<a>` or `<button>`): `isudev-share__network`,
  `isudev-share__network--{network}`,
  `isudev-share__network--label-{position}`, plus
  `isudev-share__network--with-label` when the label is visible
- `isudev-share__network-icon`, `isudev-share__network-label`
- `isudev-share__network-message`: the transient status element the copy-link
  and system-share actions insert on click. Carries `role="status"` so its
  text is the accessible confirmation, not merely a visual one.

## The editor config bridge

Both blocks' `edit.js` read `isudev.json` through
`getBlockConfig()` in `src/utils/config.js`, which mirrors
`IsuDevLibrary\Config\resolve_block_value()` against the
`window.isudevLibraryConfig` global that `Config\boot()` publishes once for
the whole block editor. There is no JS test runner in this repo to catch
drift between the two — change them together.
