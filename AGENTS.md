# AGENTS.md — IsuDev Library

Source of truth for agents working in this repo.

## What this is

A self-contained WordPress plugin: a library of reusable, always server-rendered
Gutenberg blocks plus shared PHP utils, with a React admin panel for enabling and
disabling blocks. No runtime dependency on a theme, on `t2`, or on any other
plugin.

## Golden rules

- **Blocks always render in PHP.** Leaf blocks `save: () => null`; containers
  save `<InnerBlocks.Content />` and receive it as `$content`.
- **`Loader` is the only caller of `register_block_type()`.** A `block.php`
  returns a descriptor and registers nothing.
- **Utils stay testable.** `tools/check.php` requires `includes/utils/*` and other
  pure helpers with no WordPress bootstrap, so they must not touch the database,
  `WP_Query`, HTTP or the object cache. Escaping, translation and filters are fine —
  the runner shims them. Files that still carry a `WordPress adapters` marker keep
  that split; `includes/utils/icon.php` deliberately does not.
- **Every block carries a `README.md` in its own directory.** Written for the
  person operating the block, not for us: every setting, where it lives in the
  UI, what a theme can lock through `isudev.json`, and what to check when the
  block renders nothing. A new block is not done without one, and a changed
  control means changing it. Theme-side depth belongs in `guides/`, linked from
  the block's README.
- **No top-level hook registration in `includes/`.** Use `boot()`.
- **`isudev.json`: read the `library` key only, never write the file.** It is
  shared with other `isudev-*` plugins.
- **Editor components come from `@isudev/gutenberg`.** Link and media UI use that
  package, not hand-rolled controls. It ships no PHP and no CSS: attribute
  shapes reach `render.php` as plain arrays and all styling is ours. The shared
  icon registry is published once for the whole block editor as `isudevIcons`,
  **appended** to that global and never assigned — the name is shared with the
  other `isudev-*` plugins.
- **The merged `isudev.json` config also reaches the editor.** `Config\boot()`
  publishes the whole `library` subtree once for the block editor as
  `window.isudevLibraryConfig`, **assigned** (not appended, unlike
  `isudevIcons`) because the name is this plugin's own. `src/utils/config.js`
  is its JS mirror of `Config\resolve_block_value()` and must stay in step
  with it — there is no JS test runner to catch drift.
- **Accessibility is non-negotiable.** The site-header markup contract (disclosure
  nav, drawer, link-vs-button rule, state on `.isudev-header`,
  `isudev-scroll-locked` on `<html>`) must keep the Playwright + axe suite green.
- **`apiVersion: 3` everywhere.** In editor code never use global
  `document`/`window` — use `element.ownerDocument` via `useRefEffect`.
  `view.js` is frontend and may use globals.
- **WordPress Coding Standards.** `class-*.php`, `strict_types`, `ABSPATH` guard.
- **`build/` is committed.** Do not gitignore it.
- Block directory names must be globally unique — `blocks-manifest.php` is keyed
  by directory basename.

## Layout

- `isudev-library.php` — bootstrap: constants, textdomain, requires, `boot()`.
- `includes/` — registry, loader, config reader, variations, REST, admin, utils.
- `src/blocks/<slug>/` — one block: `block.json`, `block.php` (descriptor),
  editor JS, `view.js`, styles, `render.php`, `inc/` for block-only PHP.
- `src/admin/` — React panel.
- `tools/check.php` + `tools/checks/` — plain-PHP checks for pure functions.
- `e2e/` — Playwright + axe.

## Build & test

```bash
nvm use
npm run build
npm run test:php
npm run lint:js && npm run lint:css && composer run lint:php
WP_ADMIN_USER=... WP_ADMIN_PASS=... npm run test:e2e
```

Never run `npm run build` in a watch loop during automated work — run the
one-shot `build` and move on.

Note: wp-cli cannot reach this Local site's database. Do not write verification
steps that rely on `wp eval`, `wp option` or `wp plugin`. Use `tools/check.php`
and Playwright over HTTP against `http://isudev-library.local/`.

## Docs

- Design spec: `docs/superpowers/specs/2026-07-29-isudev-library-design.md`
- Implementation plan: `docs/superpowers/plans/2026-07-29-isudev-library-v1.md`

<!-- isudev-gutenberg:begin -->
## @isudev/gutenberg

This project uses `@isudev/gutenberg` for Gutenberg editor UI. Before writing block code
that touches components, controls, fields or hooks from it, read
[`.agents/vendor/isudev-gutenberg.md`](./.agents/vendor/isudev-gutenberg.md) — it lists
every public module, its narrowest import and where the full documentation for that
module lives. Import from the narrowest subpath; never from `dist/`.

Pinned to @isudev/gutenberg@0.1.1. Refresh with `npx @isudev/gutenberg init`.
<!-- isudev-gutenberg:end -->
