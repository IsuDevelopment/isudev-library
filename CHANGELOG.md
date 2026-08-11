# Changelog

## 1.3.0 — 2026-08-11

### Added

- Block `isudev/social-share`: a container for social share buttons, with an
  optional prefix (above, before or after the icons) and a template of
  `isudev/social-share-network` children whose defaults and allowed set come
  from `isudev.json`.
- Block `isudev/social-share-network`: a single share button for one of
  twelve networks (Facebook, X, LinkedIn, WhatsApp, Bluesky, Threads,
  Mastodon, Substack, email, copy link, print, and the native "system"
  share sheet via the Web Share API).
- Twelve icons in the shared registry: the eight platform glyphs
  (`socialFacebook`, `socialX`, `socialLinkedin`, `socialWhatsapp`,
  `socialBluesky`, `socialThreads`, `socialMastodon`, `socialSubstack`,
  prefixed because `isudevIcons` is a namespace shared with other `isudev-*`
  plugins) and four plain UI icons (`email`, `link`, `print`, `share`).
- `Config\boot()` publishes the merged `isudev.json` `library` subtree to the
  block editor as `window.isudevLibraryConfig`, and `src/utils/config.js`
  mirrors `Config\resolve_block_value()` in JS so `edit.js` files can read the
  same config the PHP side reads at render time.

### Migrated from `share-to-social-media`

Ported from the standalone `dekode-library/share-to-social-media` plugin.
There is no compatibility layer: the `dekode-library/share-to-social-wrapper`
and `dekode-library/share-to-social-network` block names, and the `T2`
integration they depended on, are gone. `isudev/social-share*` content has to
be re-inserted.

- **Action networks render `<button type="button">`, not `<a href>`.** `link`,
  `print` and `system` perform an action rather than linking anywhere; the
  source's `href="#"` for print was a link to nowhere pretending to be a
  destination.
- **No `title` attribute.** The source set `title` and `aria-label` to the
  same string on every button. `aria-label` is now added only when the label
  is not already visible as text, instead of always duplicating it.
- `networkLabel.allowCustom` is dropped: the source's `constants.js` resolved
  it but nothing ever read it.
- `data-text` becomes `data-message` on the copy-link and system-share
  controls, matching the class rename below.
- CSS classes are renamed from `dk-share-social*` to `isudev-share*`, styled
  as this plugin's own classes rather than `.wp-block-isudev-social-share`, so
  they survive any future block rename.
- No `@t2/editor`, `T2\Icons\get_icon` or
  `window.dekodeShareToSocialFallbackIcons`: the icon comes from this
  plugin's own registry via `@isudev/gutenberg`'s `Icon` component.

## 1.2.0 — 2026-08-11

### Changed

- The icon registry now uses `name => { label, icon, keywords }` definitions
  compatible with `IconDefinition` from `@isudev/gutenberg`. `icon` contains a
  complete inline `<svg>` or an image URL; each SVG carries its own `viewBox`.
- Frontend rendering uses `get_icon( $name, $args )` and
  `the_icon( $name, $args )`, including per-call size, class and root attributes.

### Added

- Image URL definitions render as accessible `<img>` elements.
- The filtered registry is published automatically as `isudevIcons` for the whole
  block editor, ready for `getLocalizedIcons()`. It is appended to that global,
  not assigned, so several `isudev-*` plugins can share one collection.

### Breaking changes

- `icon()` is replaced by `get_icon()` and `the_icon()` with an argument array.
- `default_icon_paths()`, `default_icon_view_boxes()`, `build_svg()` and the
  `isudev_library/icon_view_boxes` filter are removed without compatibility
  shims.

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
- `isudev/read-more` opts into the core border support (colour, width, style and
  radius), alongside text and background colour and spacing. Heading level uses
  core's `HeadingLevelDropdown` in the block toolbar; its "Paragraph" option
  renders the title as a `div`.

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
