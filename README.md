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
| `isudev/site-header` | Accessibility-first site header: logo, disclosure navigation, mobile drawer, actions slot. |

## Configuration

Everything works on sensible defaults with no configuration. To override
per-project, add an optional `isudev.json` to your theme (child overrides
parent). See `isudev.json.example`.

This plugin reads **only** the `library` key and never writes to the file, so the
same `isudev.json` is safe to share with other `isudev-*` plugins.

```json
{
  "library": {
    "isudev/site-header": { "enabled": true, "sticky": false }
  }
}
```

`enabled` set in `isudev.json` wins over the admin panel and locks the toggle.

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
| `isudev_library/icons` | The inline SVG icon registry. |
| `isudev_library/icon` | Rendered icon markup. |
| `isudev_library/site_header/regions` | Header regions before assembly. |
| `isudev_library/site_header/output` | Final header markup. |
| `isudev_library/site_header/menu_args` | `wp_nav_menu` args for the header. |

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

## License

GPL-2.0-or-later.
