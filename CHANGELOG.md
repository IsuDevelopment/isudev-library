# Changelog

## 1.12.0 — 2026-09-12

### Fixed

- The published release zip no longer omits `src/`. It is runtime code,
  not build input — `Registry::descriptors()` globs
  `src/blocks/*/block.php` and the Loader requires each block's bootstrap
  files from there — so a zip without it registered zero blocks. The cause
  was a second exclude list, hand-maintained inside the release workflow
  and checked by nothing, while `.distignore` was correct all along.
  There is now one list: the workflow packs from `.distignore`.
- Releases failed whenever CI's rebuild matched the committed `build/`.
  The tagging step ran an unconditional `git commit`, which exits 1 with
  nothing staged and, under `bash -e`, took the whole release down. It now
  commits only when the rebuild actually differs.

### Changed

- **Releases are cut from a tag**, not from a push to `main` that happens
  to touch the version header. Push `vX.Y.Z`, or dispatch the workflow
  manually. The old trigger published whatever `main` held the moment the
  version line changed; on the sibling wp-content-bridge project that
  shipped a release built from a rename commit alone, missing the feature
  work that landed in the follow-up commit.
- A tag that disagrees with the plugin header now fails the release
  instead of publishing the artifact under a version it does not contain.
- The release body is the matching `CHANGELOG.md` section, and a version
  with no changelog entry fails the release. Previously the body was a
  bare commit list from `generate_release_notes`.
- CI builds on Node 22, matching `.nvmrc` and `package.json` engines.
  It was pinned to 20, which cannot run this project's stylelint.

### Added

- The release workflow asserts its own artifact before publishing: every
  runtime path present, at least one block and one extension descriptor
  discoverable, and no `docs/`, `guides/`, `e2e/`, `tools/`, `.agents/`,
  `.claude/`, `AGENTS.md` or build configuration anywhere in it. Roughly
  580 KB of development files previously shipped to every install.
- `tools/checks/60-dist.php` guards the single-list invariant: it fails if
  the workflow grows a second inline exclude list, if it stops packing
  from `.distignore`, or if the one documented exception — re-including
  `vendor/`, without which the plugin cannot recognise a release-zip
  install and self-update — is missing or ordered after the exclusions.

## 1.11.0 — 2026-09-11

### Added

- **Extensions**: a third thing the plugin ships, alongside blocks and
  settings, with its own tab in the admin panel. Extensions are
  quality-of-life features that are not blocks — an admin column, a post
  type rename, a plugin integration — each one switchable, grouped by
  category, and **off by default**. A block only appears once an editor
  inserts it; an extension changes the admin or the front end the moment
  it loads, so enabling one is the operator's decision.
- Eight extensions, each with its own `README.md` and its interesting
  values behind filters:
  - **Last edited column** (Admin experience) — a sortable "Last edited"
    column on post type list screens: who changed a post and when.
  - **Show template name** (Admin experience) — the assigned page template
    as a badge next to the title.
  - **Site logo in General settings** (Admin experience) — a logo picker on
    Settings → General, writing the theme's own `custom_logo` theme mod
    rather than storing a second logo.
  - **Disable posts** (Content) — hides the built-in Posts post type, its
    categories and tags, from the admin and the front end.
  - **Rename posts to articles** (Content) — relabels Posts as Articles;
    the words are filterable, so one extension covers News, Stories or
    anything else.
  - **Skip links** (Accessibility) — a keyboard-only shortcut list after
    `<body>`, replacing the core block theme skip link so there are not two
    skip mechanisms in one tab order.
  - **Gravity Forms block wrapper** (Plugin integrations) — gives the form
    block the wrapper element it does not render, so a theme has something
    to style.
  - **Lock the Gravity Forms block theme** (Plugin integrations) — pins
    every form block to one theme and hides the theme picker.
- An extension can declare a third-party dependency (`requires`, detected
  by class or function). An extension whose plugin is inactive cannot be
  enabled at all — the panel shows it as unavailable rather than letting it
  be switched on to do nothing.
- `GET /isudev-library/v1/extensions` and
  `POST /isudev-library/v1/extensions/<slug>`, gated by the same
  `show_admin` check as the block endpoints.
- `isudev.json` can pin an extension on or off under
  `library.extensions.<slug>.enabled`, exactly as it can a block, which
  locks the panel toggle.
- `tools/check.php` covers the new registry and, for every extension
  shipped, verifies the descriptor normalizes, the slug matches its
  directory, the category is known, the bootstrap files exist, the boot
  callback is callable once they load, and the README is present. The
  `.distignore` check now covers `extensions/` too — it is runtime code,
  the same trap `src/` fell into.

## 1.10.0 — 2026-08-14

### Added

- The admin panel's Blocks tab now groups a parent block with the inner
  blocks that `require` it into a single card: a "Parent" badge on the
  parent row, an "Inner blocks — N of M enabled" divider, and each child
  nested underneath with a connecting rule. Previously every block —
  parent and child alike — was its own flat card in one list.
- A summary bar ("N of M blocks enabled") with "Enable all" / "Disable
  all" buttons above the block list. Both send one request per block,
  sequentially rather than in parallel, because the toggle endpoint reads,
  mutates and writes back the whole options array per request — concurrent
  requests could otherwise silently drop one block's change.
- When a parent block is disabled, its inner-blocks section dims and its
  count line reads "Unavailable while parent is disabled" instead of a
  count.

### Changed

- The page-level explanation of what disabling a block does now appears
  once, under the panel's title, instead of being repeated under every
  unlocked block's toggle. Per-card notices are now reserved for the
  locked states that actually differ block to block (managed in
  `isudev.json`, always enabled, or waiting on a disabled parent).

## 1.9.0 — 2026-08-14

### Added

- Block `isudev/google-reviews`: reviews from the WP Google Review Slider
  plugin, as a card grid or a carousel.
- Block `isudev/google-reviews-header`: a Google account's rating, logo and
  a "Rate us" button.
- Block `isudev/google-reviews-badge`: a compact rating badge linking to the
  account's Google Maps profile.
- `GET /isudev-library/v1/google-review-accounts`, a new REST endpoint
  gated by `edit_posts` (narrower than this plugin's other,
  `manage_options`-gated endpoints) that all three blocks' editor UIs use to
  populate their account picker.
- `swiper` added as a direct dependency, bundled into
  `isudev/google-reviews`'s `view.js`/`view.css`.
- `includes/google-reviews/`: database access and pure render helpers
  shared by all three blocks, loaded unconditionally like this plugin's
  other core infrastructure. See `guides/google-reviews.md` for why these
  three sibling blocks needed a shared `includes/` module where every
  previous multi-block port here used a parent block's own `inc/`.

### Migrated from `isudev-google-reviews`

Ported from `kormas-isu`'s standalone `isudev-google-reviews` plugin. There
is no compatibility layer: content has to be re-inserted.

- **Requires the WP Google Review Slider plugin**, same as the source — the
  one deliberate exception to this plugin's "no runtime dependency on any
  other plugin" rule, because these blocks render an existing plugin's data
  rather than fetching anything themselves. Every block's `render.php`
  checks for that plugin's `WPREV_GOOGLE_PLUGIN_DIR` constant and renders
  nothing without it.
- **No T2 dependency.** The source's carousel block depended on a `swiper`
  script/style handle a theme (T2) registered and shared; this plugin
  bundles Swiper itself instead. See `guides/google-reviews.md`.
- **The header block's three flat `logoId` / `logoUrl` / `logoAlt`
  attributes become one `logo` object attribute**, matching
  `isudev/read-more`'s shape, and use `@isudev/gutenberg`'s `MediaControl`
  in the editor instead of `@t2/editor`'s raw `MediaUpload` /
  `MediaUploadCheck`.
- UI strings are translated from the source's hardcoded Polish to English
  `__()` source strings, matching every other block in this plugin, and the
  editor's rating-number preview formatting no longer hardcodes the
  `pl-PL` locale.
- PHP namespace changed from `IsuDev\GoogleReviews\*` to
  `IsuDevLibrary\Blocks\GoogleReviews*` / `IsuDevLibrary\GoogleReviews`, and
  textdomain from `isudev-google-reviews` to `isudev-library`. The block
  names themselves (`isudev/google-reviews*`) were already correctly
  namespaced and are unchanged.

## 1.8.0 — 2026-08-14

### Added

- Block `isudev/selling-points`: a container for selling points in a
  responsive grid, with optional reveal-on-scroll.
- Block `isudev/selling-point`: a single point — icon, image, badge, title,
  description and link, each independently toggleable by the theme — nested
  inside `isudev/selling-points`.
- `features`, `iconSize`, `imageSize`, `defaultColumns` and `template`
  config keys under `isudev/selling-points` in `isudev.json`. See
  `guides/selling-points.md`.

### Migrated from `extended-selling-points`

Ported from `kormas-isu`'s standalone `extended-selling-points` plugin, at
the user's request renamed from `t2/extended-selling-points` /
`t2/extended-selling-point` to `isudev/selling-points` / `isudev/selling-point`
— dropping both the leftover `t2/` namespace and the "extended-" prefix.
There is no compatibility layer: content has to be re-inserted.

- **No T2 dependency.** `T2\Config\get_config_variable()` / `@t2/editor`'s
  `getConfig()` are replaced by `isudev.json` through `IsuDevLibrary\Config`
  / `getBlockConfig()`; `T2\Icons\get_icon()` and `@t2/editor`'s
  `BlockIconSelector` are replaced by the shared `IsuDevLibrary\Utils` icon
  registry and `@isudev/gutenberg`'s `IconSelect`; `@t2/editor`'s
  `MediaSuitePicker` / `MediaSuiteViewer` are replaced by
  `@isudev/gutenberg`'s `MediaControl`; the point's hand-rolled `link-popup`
  component is replaced by `@isudev/gutenberg`'s `BlockLinkControl`. See
  `guides/selling-points.md`.
- **The point's `mediaId` attribute becomes a `media` object attribute**,
  matching `isudev/read-more`'s shape.
- **The point's `link` shape changes** from `{ rel, url, linkTarget, nofollow }`
  to `{ url, opensInNewTab, nofollow, … }`, matching every other link
  attribute in this plugin. `render.php` rebuilds `target` / `rel` from
  `opensInNewTab` / `nofollow` itself instead of trusting a client-supplied
  `rel` string verbatim, the same hardening `isudev/read-more` already
  applies.
- UI strings are translated from the source's hardcoded Polish to English
  `__()` source strings, matching every other block in this plugin.
- Dropped: the `t2AddBeforeAfter` block support (T2-specific, no equivalent
  here); the two named block styles ("ciemny"/dark, "accent"), which shipped
  no CSS of their own and depended entirely on T2 theme styles that don't
  exist in this plugin; and a `templateLock` destructured from attributes in
  the source's `edit.js` that was never declared in `block.json` — always
  `undefined`, and so already dead code before this port.
- Context keys reaching the point from the wrapper (`headingLevel`,
  `iconSize`, `renderTitlesAsHeadings`) are renamed to
  `isudev/sellingPoints*`-prefixed context keys, avoiding collisions with
  unrelated blocks that might otherwise provide same-named, unprefixed
  context on the same page — matching every other block context key in this
  plugin.

## 1.7.0 — 2026-08-14

### Added

- Block `isudev/bento-grid`: a responsive CSS grid layout with a
  five-layout picker, per-breakpoint columns and gap, and a grid guide
  overlay in the editor.
- Block `isudev/bento-card`: a single grid cell, resizable by dragging its
  right/bottom edge, with dual binding that grows the parent grid when a
  card's span exceeds its current column count.
- `customVariations` config key under `isudev/bento-grid`, and
  `allowedBlocks` / `template` / `templateLock` under `isudev/bento-card`,
  in `isudev.json`. See `guides/bento-grid.md`.
- `@wordpress/hooks` added as an explicit dependency, for the
  `isudevLibrary.bentoGrid.*` JS filters.

### Migrated from the standalone `isudev/bento-grid` plugin

Ported from `kormas-isu`'s standalone `bento-grid` plugin. Unlike this
plugin's other ports, the block names were already `isudev/bento-grid` and
`isudev/bento-card` and the CSS classes already `isudev-bento-*`, so neither
changed.

- **No `@t2/editor` dependency.** The source's `DevicePanelBody` component is
  replaced by `@isudev/gutenberg`'s `useBreakpoint()` (synced to and from the
  editor's own device preview) and `BreakpointSwitcher`. The attribute shape
  is unchanged — `columns` / `gap` / `gridColumn` / `gridRow` stay single
  objects keyed by breakpoint, rather than being reshaped into
  `ResponsiveControl`'s per-breakpoint-suffix attributes, because the grid
  needs every breakpoint's value at once (to clamp card spans when columns
  shrink) and the card needs dual-binding with its parent — neither fits
  `ResponsiveControl`'s one-control-at-a-time model. See
  `guides/bento-grid.md`.
- **No `@helpers` dependency.** `getLibraryBlockConfig()` is replaced by
  `getBlockConfig()` in `src/utils/config.js`, reading `isudev.json` through
  the same `window.isudevLibraryConfig` bridge every other block in this
  plugin uses.
- JS filter names are renamed from `isudev.bentoGrid.*` to
  `isudevLibrary.bentoGrid.*`, matching this plugin's own naming rather than
  the source's.
- UI strings are translated from the source's hardcoded Polish to English
  `__()` source strings, matching every other block in this plugin and
  correct WordPress i18n practice — msgids are meant to be the neutral
  source text, not a specific locale's translation.
- Card content config keeps its own source shape (`isudev/bento-card`'s own
  key in `isudev.json`, not the grid's), unlike the parent/child config
  sharing `isudev/social-share` and `isudev/social-share-network` use — the
  grid's layout and the card's content defaults are independent concerns,
  and the source's own `library.json` schema already treated them that way.

## 1.6.0 — 2026-08-14

### Added

- Block `isudev/agenda-accordion`: a WAI-ARIA accordion container — keyboard
  navigation between triggers, and a URL hash that opens an item and can be
  bookmarked.
- Block `isudev/agenda-accordion-item`: a single accordion item — a trigger
  with a title, optional date and excerpt, and collapsible content — nested
  inside `isudev/agenda-accordion`.
- `allowMultiple`, `allowToggle`, `icons`, `allowedBlocks`, `template` and
  `excerpt.insideTrigger` config keys under `isudev/agenda-accordion` in
  `isudev.json`. See `guides/agenda-accordion.md`.
- A `chevronUp` icon in the shared registry, for the accordion item's
  expanded-state default.

### Migrated from `agenda-accordion`

Ported from the standalone `dekode-library/agenda-accordion` plugin. There is
no compatibility layer: the `dekode-library/agenda-accordion*` block names
are gone, and content has to be re-inserted.

- CSS classes are renamed from `dekode-library-agenda-accordion*` to
  `isudev-agenda-accordion*`, and `--dekode-library-agenda-accordion-*`
  custom properties to `--isudev-agenda-accordion-*`. As in the source, the
  item block ships no stylesheet of its own — every rule for its markup lives
  in the wrapper's `style.scss` / `editor.scss`.
- **No T2 dependency.** The source read icon and layout settings through
  `T2\Config\get_config_variable()` against `t2.json` and rendered icons
  through `T2\Icons\icon()` / `T2\Icons\get_icon()`. This plugin's own
  `isudev.json` / `IsuDevLibrary\Config` and the shared
  `IsuDevLibrary\Utils` icon registry replace both, the same as every other
  block in this plugin.
- **The default content template no longer depends on `allowedBlocks` being
  set.** The source only inserted its default "Write description…" paragraph
  when a theme had also restricted `allowedBlocks`
  (`template = !isEmpty(ALLOWED_BLOCKS) ? TEMPLATE : []`), so an
  unrestricted accordion silently lost its placeholder content. This port
  applies the template unconditionally.
- `--theme--text-color`, a T2 theme custom property with no equivalent here,
  is replaced by `var(--wp--preset--color--contrast, currentColor)`
  wherever the source used it (the item border and the content panel's
  decorative top rule).
- Six context keys become one. The source threaded icon/layout config
  (`layout.icons.size` / `.closed` / `.open`) by reading `t2.json` directly
  from the item's own PHP, with no context wiring at all — this port makes
  those the same one-key-per-feature `isudev/agenda-accordion` config keys
  the `isudev/social-share*` blocks already use, reached from the item via
  `get_block_config()` / `getBlockConfig()`, plus one real context key
  (`isudev/agendaAccordionNamespace`) for variation-scoped resolution.

## 1.5.0 — 2026-08-14

### Added

- Block `isudev/image-step-guide`: a container for step-by-step content, any
  number of `isudev/image-step-guide-step` children.
- Block `isudev/image-step-guide-step`: a single step — an image alongside
  editable inner blocks — nested inside `isudev/image-step-guide`.
- `displayStyle`, `imageLightbox` and `stepLabel` config keys, plus
  `allowedStepBlocks` / `stepTemplate`, under `isudev/image-step-guide` in
  `isudev.json`. See `guides/image-step-guide.md`.

### Migrated from `image-step-guide`

Ported from the standalone `dekode-library/image-step-guide` plugin. There is
no compatibility layer: the `dekode-library/image-step-guide*` block names
are gone, and content has to be re-inserted.

- CSS classes are renamed from `dekode-image-step-guide*` to
  `isudev-image-step-guide*`, styled as this plugin's own classes rather than
  `.wp-block-isudev-image-step-guide`, so the styles survive a future block
  rename. As in the source, the step block ships no stylesheet of its own —
  every rule for its markup lives in the wrapper's `style.scss` /
  `editor.scss`, since a step can only ever exist inside the wrapper.
- **The image lightbox is a from-scratch reimplementation**, not a port. The
  source built an `<!-- wp:image {"lightbox":{"enabled":true}} --> `comment
  and ran it through `do_blocks()` to borrow core's own lightbox, gated
  behind `version_compare( $wp_version, '6.4', '>=' )`. This plugin implements
  its own small overlay in `view.js` instead — no WordPress version check, no
  `do_blocks()`, no dependency on `core/image`'s internal markup. The
  `dekode-library/image-step-guide/image/size` and
  `…/step/image/html` PHP filters this replaced are gone; the rendered image
  size is a fixed `large`, matching every other image this plugin renders.
- **The step's three flat `mediaId` / `mediaUrl` / `mediaAlt` attributes
  become one `media` object attribute**, matching `isudev/read-more`'s shape,
  and use `@isudev/gutenberg`'s `MediaControl` in the editor instead of
  `@t2/editor`'s `MediaSuite`.
- **Config is read live, not baked into attributes.** The source copied
  `library.json`-equivalent values into the wrapper's own attributes once, on
  mount, via three `useEffect` hooks. This port reads `isudev.json` live
  through `get_block_config()` / `getBlockConfig()` at render and edit time
  instead, like every other block in this plugin — a theme config change now
  applies immediately, including to already-inserted content.
- **Six context keys become one.** The source threaded
  `stepAllowedInnerBlocks`, `stepTemplate`, `stepLabelPrefix`,
  `stepLabelVisible`, `displayStyle` and `useImageLightbox` from wrapper to
  step as block context. Only `_namespace` (`isudev/imageStepGuideNamespace`)
  remains: the rest are config reads under the wrapper's block name from the
  step block, the same pattern `isudev/social-share-network` already uses.
- **The source's per-namespace variation-switching JS is gone.** The source's
  `edit.js` special-cased `getVariation()` / `getVariationLibSettings()` to
  swap `stepTemplate` / `stepAllowedInnerBlocks` based on which registered
  variation's `displayStyle` matched. This plugin's existing generic
  `IsuDevLibrary\Variations` system already covers registering variations
  from `isudev.json` for any block with `'variations' => true`; no
  block-specific code is needed.
- The step's numbered label badge still uses a pure CSS counter on the
  frontend, and a JS-computed number in the editor (where the counter is
  suppressed) — unchanged from the source.

## 1.4.0 — 2026-08-14

### Added

- Block `isudev/toggle-blocks`: a collapsible toggle — a button that shows or
  hides any inner blocks placed inside it. Unlike `isudev/social-share`, it
  has no fixed child; any block may go inside.
- `iconPosition` and `icons.open` / `icons.close` config keys under
  `isudev/toggle-blocks` in `isudev.json`, resolved through
  `Config\get_block_config()` and its JS mirror, including variation scoping.

### Migrated from `toggle-blocks`

Ported from the standalone `dekode-library/toggle-blocks` plugin. There is no
compatibility layer: the `dekode-library/toggle-blocks` block name is gone,
and `isudev/toggle-blocks` content has to be re-inserted.

- CSS classes are renamed from `toggle-block*` to `isudev-toggle*`, styled as
  this plugin's own classes rather than `.wp-block-isudev-toggle-blocks`, so
  the styles survive a future block rename.
- Frontend `CustomEvent`s are renamed from `dekode/toggle:open` /
  `dekode/toggle:close` to `isudev/toggle:open` / `isudev/toggle:close`.
- The CSS custom property driving the animation duration is renamed from
  `--toggle-speed` to `--isudev-toggle-speed`.
- No `T2\Icons\get_icon()` or `window.t2.editor.Icon` integration: icons come
  from the shared `IsuDevLibrary\Utils` registry and
  `@isudev/gutenberg`'s `Icon` component, matching every other block in this
  plugin.
- The source's `_namespace`-scoped `iconPosition` and `icons` config read
  through `DekodeLibrary\Config\get_library_block_config()` under the block's
  own key, `dekode-library/toggle-blocks`; the equivalent keys here live
  under `isudev/toggle-blocks` and go through `IsuDevLibrary\Config`, this
  plugin's own config reader, unlike the social share blocks, which read the
  parent's key for both blocks.
- `settings.allowedInnerBlocks` / `settings.template` / `settings.templateLock`
  from the source's config are dropped: any block can go inside, with no
  fixed template. The source config surface was never used to restrict
  content in practice.
- `buttonStyle` still applies a `core/button` style (`is-style-*`) to the
  toggle button, unchanged from the source.
- The manually-declared `anchor` attribute is dropped: `supports.anchor: true`
  already gives the block one, via WordPress core's own handling.

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
- The four `dekode/share-to-social-media/*` PHP filters are gone:
  `…/icon/size`, `…/email/subject`, `…/email/body` and `…/link/copy-text`.
  `iconsSize`, `email.subject`, `email.body` and `link.copyText` in
  `isudev.json` cover the same ground declaratively. Rendered icon markup is
  still filterable through `isudev_library/icon`.
- **A control with nothing to share renders nothing.** Outside a post context
  `get_permalink()` is empty, and the source shipped `href="…/sharer.php?u="`.
  Every network except `print` and `system` — neither of which needs a
  permalink — now drops itself, and the wrapper drops itself when no child
  rendered.
- Variation-scoped config reaches both blocks: the wrapper provides its
  `_namespace` through block context, so keys the network button reads resolve
  under `variations.<namespace>` too.
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
