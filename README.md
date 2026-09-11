# IsuDev Library

Reusable, server-rendered Gutenberg blocks and tools. One place instead of
copying blocks from project to project.

- **Self-contained.** No runtime dependency on a theme, on `t2`, or on any other
  plugin. `build/` ships with the repo, so no build step is needed to install it.
- **Always server-rendered.** Every block renders in PHP.
- **Toggleable.** Enable or disable blocks from the admin panel, or pin them in
  code via `isudev.json`.

## Requirements

WordPress 6.7+, PHP 7.4+. Node 22.22.2 for development only.

## Blocks

| Block | Description |
| --- | --- |
| [`isudev/site-header`](src/blocks/site-header/README.md) | Accessibility-first site header: logo, disclosure navigation, mobile drawer, actions slot. |
| [`isudev/read-more`](src/blocks/read-more/README.md) | A linked card: title, optional image, badge and supporting text. Links to a post, a page or an external URL. |
| [`isudev/social-share`](src/blocks/social-share/README.md) | A container for social share buttons, with an optional prefix. |
| [`isudev/social-share-network`](src/blocks/social-share-network/README.md) | A single share button — one of twelve networks — nested inside `isudev/social-share`. |
| [`isudev/toggle-blocks`](src/blocks/toggle-blocks/README.md) | A collapsible toggle: a button that shows or hides any inner blocks. |
| [`isudev/image-step-guide`](src/blocks/image-step-guide/README.md) | A container for step-by-step content, with an image alongside inner blocks for each step. |
| [`isudev/image-step-guide-step`](src/blocks/image-step-guide-step/README.md) | A single step — an image and editable content — nested inside `isudev/image-step-guide`. |
| [`isudev/agenda-accordion`](src/blocks/agenda-accordion/README.md) | A WAI-ARIA accordion container: any number of collapsible items. |
| [`isudev/agenda-accordion-item`](src/blocks/agenda-accordion-item/README.md) | A single accordion item — a trigger and collapsible content — nested inside `isudev/agenda-accordion`. |
| [`isudev/bento-grid`](src/blocks/bento-grid/README.md) | A responsive CSS grid layout: any number of resizable cards. |
| [`isudev/bento-card`](src/blocks/bento-card/README.md) | A single card, resizable by drag — nested inside `isudev/bento-grid`. |
| [`isudev/selling-points`](src/blocks/selling-points/README.md) | A container for selling points in a responsive grid, with optional reveal-on-scroll. |
| [`isudev/selling-point`](src/blocks/selling-point/README.md) | A single point — icon, image, badge, title, description and link — nested inside `isudev/selling-points`. |
| [`isudev/google-reviews`](src/blocks/google-reviews/README.md) | Reviews from WP Google Review Slider, as a card grid or a carousel. |
| [`isudev/google-reviews-header`](src/blocks/google-reviews-header/README.md) | A Google account's rating, logo and a review button. |
| [`isudev/google-reviews-badge`](src/blocks/google-reviews-badge/README.md) | A compact Google rating badge linking to the account's Maps profile. |

Each block ships a `README.md` in its own directory: every setting, where to find
it, what a theme can lock, and what to check when the block does not render.
Deeper theme-side documentation lives in [`guides/`](guides/).

## Extensions

Quality-of-life features that are not blocks — an admin column, a post type
rename, a plugin integration. Each is toggled from **IsuDev Library →
Extensions**, and **every one is off until someone turns it on**: a block only
appears when an editor inserts it, but an extension changes the admin or the
front end the moment it loads.

| Extension | Category | Needs |
| --- | --- | --- |
| [Last edited column](extensions/last-edited-column/README.md) | Admin experience | — |
| [Show template name](extensions/template-post-state/README.md) | Admin experience | — |
| [Site logo in General settings](extensions/site-logo-option/README.md) | Admin experience | — |
| [Disable posts](extensions/disable-posts/README.md) | Content | — |
| [Rename posts to articles](extensions/post-rename/README.md) | Content | — |
| [Skip links](extensions/skip-links/README.md) | Accessibility | — |
| [Gravity Forms block wrapper](extensions/gravity-forms-wrapper/README.md) | Plugin integrations | Gravity Forms |
| [Lock the Gravity Forms block theme](extensions/gravity-forms-theme-lock/README.md) | Plugin integrations | Gravity Forms |

An extension whose required plugin is inactive cannot be enabled at all; the
panel shows it as unavailable rather than letting it be switched on to do
nothing.

Each ships a `README.md` in its own directory: what it changes, every filter it
offers, and what to check when it appears to do nothing.
[`extensions/README.md`](extensions/README.md) is the guide to writing a new one.

## Configuration

Everything works on sensible defaults with no configuration. To override
per-project, add an optional `isudev.json` to your theme (child overrides
parent). See `isudev.json.example`.

This plugin reads **only** the `library` key and never writes to the file, so the
same `isudev.json` is safe to share with other `isudev-*` plugins.

```json
{
  "library": {
    "isudev/site-header": { "enabled": true }
  }
}
```

Extensions are pinned the same way, under their own `extensions` key — they are
keyed by slug rather than by block name, because an extension has no block name:

```json
{
  "library": {
    "extensions": {
      "skip-links": { "enabled": true },
      "disable-posts": { "enabled": false }
    }
  }
}
```

`enabled` set in `isudev.json` wins over the admin panel and locks the toggle,
for blocks and extensions alike.

In v1 a block entry accepts `enabled` and `variations`. Per-block default
attributes are read by `Config\get_block_config()`, but no block consumes them
yet — see the CHANGELOG's "Not in v1" list.

### Variations

Register extra variations of a block from `isudev.json`. Each gets a
`_namespace` attribute, so `render.php` can vary markup and classes per
variation. See `isudev.json.example`.

## Filters

| Filter | Purpose |
| --- | --- |
| `isudev_library/settings/show_admin` | Whether the current user sees the panel. Also gates the REST endpoints. |
| `isudev_library/settings/capability` | Capability for the menu and REST. Default `manage_options`. |
| `isudev_library/config` | The `library` subtree after extraction. |
| `isudev_library/config/raw` | The whole decoded `isudev.json`. |
| `isudev_library/config/inherit_from_parent` | Whether to inherit from the parent theme. Default `true`. |
| `isudev_library/icons` | Icon definitions containing complete inline SVG markup or image URLs. |
| `isudev_library/icon` | Rendered inline SVG or image markup. |
| `isudev_library/site_header/regions` | Header regions before assembly. |
| `isudev_library/site_header/output` | Final header markup. |
| `isudev_library/site_header/menu_args` | `wp_nav_menu` args for the header. |

Each extension adds its own filters under
`isudev_library/extensions/<slug>/…`, documented with examples in that
extension's README — see the [Extensions](#extensions) table above.

Code-only mode — no panel, configuration lives entirely in `isudev.json`:

```php
add_filter( 'isudev_library/settings/show_admin', '__return_false' );
```

## Development

```bash
nvm use
npm install && composer install
npm run build
npm run test:php
npm run lint:js && npm run lint:css && composer run lint:php
WP_ADMIN_USER=... WP_ADMIN_PASS=... npm run test:e2e
```

Never run `npm start` in automated work — use the one-shot `npm run build`.

## Adding a block

1. Create `src/blocks/<slug>/` with `block.json` (`apiVersion: 3`,
   `"render": "file:./render.php"`), `index.js`, `render.php`.
2. Add `block.php` returning a descriptor — see
   `src/blocks/site-header/block.php`.
3. `npm run build`. The loader picks it up; the panel lists it automatically.

Block directory names must be globally unique: the generated
`build/blocks-manifest.php` is keyed by directory basename.

## Adding an extension

1. Create `extensions/<slug>/` with `extension.php` (a descriptor that registers
   nothing), `hooks.php` (every hook inside a `boot()` function) and a
   `README.md`.
2. No build step — extensions ship no JavaScript. The panel lists it
   automatically.

Read [`extensions/README.md`](extensions/README.md) first: it documents every
descriptor key, why hooks may never be registered at the top level of a file, and
the conventions `tools/check.php` enforces.

## License

GPL-2.0-or-later.
