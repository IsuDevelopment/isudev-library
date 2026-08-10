# Changelog

## 1.1.0 — 2026-08-10

### Added

- Block `isudev/read-more`: a linked card with a title, optional image, optional
  badge and supporting text. Links anywhere — a post, a page or an external URL.
  Migrated from the standalone `isudev-read-more` plugin, with the `t2`
  dependency removed.
- `@isudev/gutenberg` supplies the link picker and the media controls. It is the
  library's first runtime npm dependency.
- Per-icon `viewBox` support in the icon registry, an `arrowForward` glyph, and
  the `isudev_library/icon_view_boxes` filter.

### Breaking changes from `isudev-read-more`

The standalone plugin is superseded. There is no compatibility layer and no
deprecation, matching the stance taken for `isudev-header`.

- The block selects a **link**, not a post. `postId`, `postType`, `siteId` and
  `isPreview` are gone, replaced by a `link` object.
- The `t2-featured-single-post`, `t2-read-more-content` and
  `t2-featured-content-layout-col-12` classes are gone.
- `is-post-type-{type}` becomes `is-link-type-{type}`.
- Selecting several posts at once, which inserted sibling cards, is gone: the
  link picker has no multi-select.
- Text domain `isudev-read-more` becomes `isudev-library`.

Existing `isudev/read-more` content authored against the standalone plugin will
not render. Re-insert the block.

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
