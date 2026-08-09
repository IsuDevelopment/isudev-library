# Changelog

## 1.0.0 — 2026-07-29

First release.

### Added

- Block registry with descriptor-based discovery and a single registration point.
- Enabled-state resolution: unmet dependencies, `always_on`, `isudev.json`,
  admin panel option, then enabled by default.
- Optional `isudev.json` theme config, reading only the `library` key. Child
  theme overrides parent.
- Block variations registered in PHP, with an injected `_namespace` attribute.
- Shared inline SVG icon registry.
- React admin panel: Blocks and Settings tabs, REST endpoints under
  `isudev-library/v1`, ACF-style `show_admin` / `capability` filters that gate
  the menu and the endpoints together. The Settings tab is read-only
  diagnostics.
- Block `isudev/site-header`, migrated from the standalone `isudev-header`
  plugin.

### Not in v1

Present as tested infrastructure, with no consumer yet:

- **Per-block config in `isudev.json`.** `Config\get_block_config()` and
  `Config\resolve_block_value()` resolve block- and variation-level values, but
  no block reads them; `site-header` takes everything from block attributes.
  Only `enabled` and `variations` change behaviour today.
- **The `isudev_library_settings` option.** Registered, schema'd and sanitized,
  and exposed on `/wp/v2/settings`, but nothing reads `loadBaseTokens` — the
  `--isudev-*` tokens are declared inside the header's own stylesheet, so there
  is no separate base sheet to gate. The Settings tab shows diagnostics only
  rather than a control that silently does nothing.

### Breaking changes from `isudev-header`

`isudev-header` is superseded by this plugin. There is no compatibility layer.

- Block renamed: `idl/site-header` → `isudev/site-header`.
- Text domain: `idl-site-header` → `isudev-library`.
- CSS custom properties: `--idl-*` → `--isudev-*`.
- CSS classes: `.idl-header` → `.isudev-header`, `.idl-nav` → `.isudev-nav`,
  `idl-scroll-locked` → `isudev-scroll-locked`.
- Filters: `idl_site_header/icon` → `isudev_library/icon`;
  `idl_site_header/regions` → `isudev_library/site_header/regions`;
  `idl_site_header/output` → `isudev_library/site_header/output`;
  `idl_site_header/menu_args` → `isudev_library/site_header/menu_args`.

Content containing `idl/site-header` will not render. Re-insert the block, or
rewrite `post_content` before upgrading.
