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
- **Pure functions stay pure.** Anything above a "WordPress adapters" marker
  must not call WordPress — `tools/check.php` requires those files without WP.
- **No top-level hook registration in `includes/`.** Use `boot()`.
- **`isudev.json`: read the `library` key only, never write the file.** It is
  shared with other `isudev-*` plugins.
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
