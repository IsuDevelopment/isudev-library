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
- **No top-level hook registration in `includes/` or `extensions/`.** Use
  `boot()`. For an extension this is not style: `extension.php` descriptors are
  read for every extension on every request and only enabled ones have their
  `boot()` called, so a stray `add_filter()` at file scope fires for an extension
  the operator switched off.
- **`Extensions` is the only caller of an extension's `boot()`.** An
  `extension.php` returns a descriptor and registers nothing. Extensions default
  to **off** — a block only appears when an editor inserts it, an extension
  changes the admin or the front end the moment it loads.
- **Every extension carries a `README.md` in its own directory**, same contract
  as a block's: written for the operator, every filter with a copy-pasteable
  example, and what to check when it does nothing. `tools/check.php` fails
  without one. `extensions/README.md` is the guide for adding one.
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
- **One exclude list: `.distignore`.** It is what `wp dist-archive` and the
  release workflow both pack from. Never inline a second list anywhere — a
  second list is how `src/` (runtime code, not build input) once fell out of the
  published zip while every check stayed green. The single documented exception
  is the workflow re-including `vendor/`, which the zip needs and
  `.distignore` drops. `tools/checks/60-dist.php` enforces all of this.
- Block directory names must be globally unique — `blocks-manifest.php` is keyed
  by directory basename.

## Working agentically

This library is built and maintained by AI agents, across projects that consume
it. Two rules keep that workable:

- **Comments are for agents, not human readers.** Every comment costs context
  on every read. Comment only what the code cannot say — public APIs,
  non-obvious decisions, browser or core workarounds, security-sensitive logic,
  couplings to core markup. One or two terse lines; longer background goes once
  into the block's or extension's `README.md` (or `guides/`), and the comment
  points there.
- **Every change updates the docs of what it touches, in the same commit** — a
  new setting, filter, class, markup change or dependency — so a later agent can
  repair it without the conversation that built it (typically after a WordPress
  core update breaks it). The block's or extension's `README.md` records:
  - **what it relies on and does not own** — core hooks and filters, core block
    markup and class names, WP APIs; these are what an upstream update breaks;
  - **what a consuming theme can rely on** — its class contract, custom
    properties, filters, `isudev.json` keys; changing those is a breaking change
    and goes in `CHANGELOG.md`;
  - **how to check it still works** — the page, the flow, the check in
    `tools/checks/` or the e2e spec.

## Layout

- `isudev-library.php` — bootstrap: constants, textdomain, requires, `boot()`.
- `includes/` — registry, loader, config reader, variations, REST, admin, utils.
- `src/blocks/<slug>/` — one block: `block.json`, `block.php` (descriptor),
  editor JS, `view.js`, styles, `render.php`, `inc/` for block-only PHP.
- `extensions/<slug>/` — one extension: `extension.php` (descriptor),
  `hooks.php`, `README.md`, optionally a static `editor.js` (no build step,
  WordPress globals; see `extensions/README.md`). Discovered at runtime — so
  this tree is runtime code, like `src/`, and must survive `.distignore`.
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

## Releasing

A release is cut from a **tag**, never from a push to `main`:

```bash
# 1. Bump the version in isudev-library.php (header + VERSION), package.json
#    and package-lock.json, and add the matching CHANGELOG.md section.
# 2. npm run build, commit, push main.
git tag v1.12.0 && git push origin v1.12.0
```

`.github/workflows/release.yml` then builds the zip, asserts it, and publishes
the GitHub release. `workflow_dispatch` from `main` does the same and creates the
tag itself.

The workflow fails, deliberately, when the tag disagrees with the plugin header,
when `CHANGELOG.md` has no section for the version, or when the artifact is
missing runtime files or carries development ones. A failed release is the
system working — read the failing step, do not work around it.

Version lives in four places and they must agree: the plugin header, the
`VERSION` const, `package.json`, `package-lock.json` (two entries).

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
