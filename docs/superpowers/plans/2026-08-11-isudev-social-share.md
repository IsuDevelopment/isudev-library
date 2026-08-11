# Implementation plan — port `share-to-social-media` into isudev-library

Date: 2026-08-11
Implementer: Codex/Sonnet. Reviewer: Claude Opus.
Source of the port (read-only, never edit):
`/Users/lukaszbiedron/Local Sites/dk-library/app/public/dekode-library/library/share-to-social-media`

## 0. Global constraints

- Read `AGENTS.md` first. Every golden rule there applies, in particular: blocks
  always render in PHP, `Loader` is the only caller of `register_block_type()`,
  no top-level hook registration in `includes/`, `apiVersion: 3`, WPCS.
- Branch: create `feat/social-share` **off the current `refactor/icon-registry`
  branch** (this work builds on `get_icon()`). Leave `main` untouched. Commit in
  logical chunks with the repo's configured git identity.
- **Do not invent glyph data.** Every SVG comes from the source plugin, copied
  verbatim except for the documented transformation in Task 1.
- **Do not port the T2 integration.** `@t2/editor`, `T2\Icons\get_icon`,
  `window.dekodeShareToSocialFallbackIcons` and
  `src/shareSocialNetwork/components/network-icon.js` have no equivalent here and
  are replaced by this plugin's own icon registry.
- **Do not port `src/constants.js`'s `getLibraryConfig` import.** The config layer
  is rebuilt on `IsuDevLibrary\Config` (Task 2).
- Nothing from `languages/`, `screenshoots/`, `webpack.config.js`, `plugin.php`,
  `composer.json` or `build/` in the source plugin is copied.
- WPCS gotchas that have bitten before: `declare( strict_types = 1 );` and
  `defined( 'ABSPATH' ) || exit;` in every PHP file, long array syntax, Yoda
  conditions, a docblock on every function with `@param`/`@return`, and the
  forbidden parameter names `$array`, `$list`, `$class`, `$object`.
- Baselines to hold: `npm run test:php` currently reports
  `154 passed, 0 failed (8 check files)`; `npm run test:e2e` reports
  `31 passed, 21 skipped`. Both must still be green at the end (with a higher
  passed count and one more check file).
- wp-cli cannot reach this site's database. Verify with `tools/check.php`,
  Playwright, or HTTP against `http://isudev-library.local/`.

## 1. Icons — 12 new registry entries

Add twelve definitions to `default_icons()` in `includes/utils/icon.php`, after
the four existing ones, in the order of the table below.

Source of the markup: `get_icon_templates()` in
`<source>/src/shareSocialNetwork/block.php`. Transformation, applied to each:

1. Drop `width="%1$d" height="%2$d"` from the root `<svg>`. Rendering injects
   both per call; stored markup must never carry them.
2. Keep the `viewBox` exactly as the source has it (they differ — see the table).
3. Root becomes `<svg xmlns="http://www.w3.org/2000/svg" viewBox="…" fill="currentColor">`.
4. Everything inside the root — `<path>`, `<g>`, their `d`, `fill`, `stroke`,
   `stroke-width`, `fill-rule`, `clip-rule` attributes — is copied **byte for
   byte**. Do not normalize, reformat or re-round any path data. Three glyphs
   (`bluesky`, `threads`, `mastodon`) are stroke drawings whose inner element
   sets `fill="none" stroke="currentColor"`; that stays, and the root
   `fill="currentColor"` is inert for them.

| Registry name | `label` | viewBox | Source key | `keywords` |
| --- | --- | --- | --- | --- |
| `socialFacebook` | Facebook | `0 0 24 24` | `facebook` | facebook, social, share |
| `socialX` | X (Twitter) | `0 0 24 24` | `x` | x, twitter, social, share |
| `socialLinkedin` | LinkedIn | `0 0 24 24` | `linkedin` | linkedin, social, share |
| `socialWhatsapp` | WhatsApp | `0 0 24 24` | `whatsapp` | whatsapp, chat, social, share |
| `socialBluesky` | Bluesky | `0 0 24 24` | `bluesky` | bluesky, social, share |
| `socialThreads` | Threads | `0 0 24 24` | `threads` | threads, social, share |
| `socialMastodon` | Mastodon | `0 0 24 24` | `mastodon` | mastodon, social, share |
| `socialSubstack` | Substack | `0 0 16 16` | `substack` | substack, newsletter, social |
| `email` | Email | `0 0 24 24` | `email` | email, mail, envelope, contact |
| `link` | Link | `0 0 20 20` | `link` | link, url, copy, chain |
| `print` | Print | `0 0 24 24` | `print` | print, printer, paper |
| `share` | Share | `0 0 24 24` | `system` | share, native, system, nodes |

Naming rationale, for the docblock or a short comment: the eight platform glyphs
are prefixed because `isudevIcons` is a flat namespace shared with the other
`isudev-*` plugins and `x` alone is a collision waiting to happen. The remaining
four are ordinary UI icons and keep plain names.

Every `label` goes through `__( …, 'isudev-library' )` with a literal string.

### Checks (`tools/checks/50-icon.php`)

- Update the existing strict count assertion: `4` → `16`.
- Add: every default definition has a non-empty `icon` and a non-empty `label`.
- Add: no stored root `<svg>` carries `width` or `height`. Match the root tag
  only — `/<svg\b[^>]*\s(?:width|height)=/` — because `stroke-width="2"` contains
  the substring `width=` and a naive `strpos` would report a false failure.
- Add: `get_icon( 'socialX', array( 'size' => 32 ) )` yields `width="32"`,
  `height="32"` and exactly one `viewBox`.

### Docs

`guides/work-with-icons.md` §1 "Current registry" gains the twelve rows, with
"Used by" pointing at `isudev/social-share-network` for the social ones and
"Available to integrators" for anything the block does not itself render.

## 2. Config bridge — `isudev.json` reaches the editor

`Config\get_block_config()` already resolves block- and variation-scoped values
in PHP. The editor has no equivalent. Build the smallest bridge that works.

### PHP — `includes/config.php`

Below the `WordPress adapters` marker, add:

```php
function boot(): void {
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_config', 5 );
}

function enqueue_editor_config(): void {
	$json = wp_json_encode( get_config() );

	if ( ! is_string( $json ) ) {
		return;
	}

	wp_register_script( 'isudev-library-config', false, array(), \IsuDevLibrary\VERSION, false );
	wp_enqueue_script( 'isudev-library-config' );
	wp_add_inline_script( 'isudev-library-config', sprintf( 'window.isudevLibraryConfig = %s;', $json ) );
}
```

- Priority 5 and a `src`-less handle, exactly like `enqueue_editor_icons()`, so
  the data prints in `<head>` ahead of every block editor script.
- **Plain assignment is correct here**, unlike `localize_icons()`. Say so in a
  comment: `isudevLibraryConfig` is this plugin's own name and this is its only
  writer, whereas `isudevIcons` is shared and therefore appended.
- The whole merged `library` subtree is published, not one block's slice. It is
  theme configuration — no secrets — and it only reaches users who can already
  open the editor. Note that in the docblock.

Call `Config\boot();` from `isudev-library.php` next to the other `boot()` calls.
`includes/config.php` is already required there.

### JS — `src/utils/config.js`

New shared module. Mirror of `resolve_block_value()`:

```js
export function getLibraryConfig()  // globalThis.isudevLibraryConfig ?? {}
export function getBlockConfig( blockName, key, fallback = null, variationNamespace = '' )
```

- `key` accepts a dot-notation string or an array key path, like the PHP side.
- Lookup order: `[ blockName, 'variations', variationNamespace, ...keyPath ]`,
  then `[ blockName, ...keyPath ]`, then `fallback`.
- A resolved `undefined` counts as "not set" and falls through to the next step.
- Full JSDoc on both exports. Add a comment stating that this duplicates
  `IsuDevLibrary\Config\resolve_block_value()` and the two must be changed
  together — there is no JS test runner in this repo to catch drift.

## 3. Block `isudev/social-share` (the wrapper)

Directory `src/blocks/social-share/`. Files: `block.php`, `block.json`,
`index.js`, `edit.js`, `save.js`, `render.php`, `style.scss`, `editor.scss`.
No `view.js` — the source wrapper's `view.js` is an empty `domReady` stub.

`block.php` descriptor: `slug` `social-share`, `name` `isudev/social-share`,
`requires` empty, `always_on` false, `variations` true, `bootstrap` empty.

`block.json`, modelled on `src/blocks/read-more/block.json`:

- `category` `widgets`, `icon` `share`, `textdomain` `isudev-library`.
- `supports`: `html` false, `align` `["left","center","right","wide","full"]`.
- Attributes: `_namespace` (string, `''`, with the same description as the other
  blocks — a variation identifier injected by `IsuDevLibrary\Variations`),
  `contentAlignment` (string, `none`), `prefixText` (string, `''`), `showPrefix`
  (boolean, false), `prefixPosition` (string, `before`).
- `editorScript` `file:./index.js`, `style` `file:./style-index.css`,
  `editorStyle` `file:./index.css`, `render` `file:./render.php`.
- Keep the source's `example` block, with the new block names.

`save.js`: `useBlockProps.save()` wrapper containing `<InnerBlocks.Content />`.

`edit.js`: port `<source>/src/shareSocialWrapper/edit.js`, replacing
`getConstants()` with `getBlockConfig()` calls (Task 2) and the text domain with
`isudev-library`. Config keys read here, all under `isudev/social-share`:

| Key | Type | Default | Effect |
| --- | --- | --- | --- |
| `prefix.disable` | boolean | false | Hides the prefix UI entirely and suppresses the prefix in render. |
| `prefix.allowToggle` | boolean | true | Shows the "Show prefix text" toggle. |
| `prefix.allowPosition` | boolean | true | Shows the prefix position select. |
| `allowedNetworks` | string[] | all twelve | Restricts which child networks may be inserted or selected. |
| `defaultTemplate` | string[] | `["facebook","x","linkedin","link"]` | Networks inserted with the wrapper, in order. |
| `features.allowAlignControl` | boolean | true | Shows the content-alignment toolbar. |

Behaviour to preserve from the source: `defaultTemplate` is filtered by
`allowedNetworks` before becoming the `useInnerBlocksProps` template; when
`allowedNetworks` holds exactly one network the template is that single block and
`templateLock` is `'all'`; `orientation` is `horizontal`.

`render.php`: port `<source>/src/shareSocialWrapper/block.php`'s `render()` into
this plugin's shape — a `render.php` reading `$attributes`, `$content` and
`$block`, ending in one `echo`. Use `Config\get_block_config()` for
`prefix.disable`, honouring the variation namespace from
`$attributes['_namespace']`. Inner blocks go where `$content` goes.

CSS class contract (rename from `dk-share-social*`; style **our own** classes,
never `.wp-block-isudev-social-share`, so the styles survive a rename):

- root, added to the wrapper attributes: `isudev-share`,
  `isudev-share--align-{contentAlignment}`, `isudev-share--prefix-{position}`
- `isudev-share__content`, `isudev-share__row`, `isudev-share__items` (the
  inner-blocks container), `isudev-share__prefix`,
  `isudev-share__prefix--above|--before|--after`

`style.scss` is the port of `<source>/src/shareSocialWrapper/view.css`,
`editor.scss` holds editor-only tweaks (the source has none — an empty-but-for-a-
comment file is fine, or omit `editorStyle` from `block.json` if nothing is
needed; do not ship an empty stylesheet reference). Every `--wp--preset--*`
custom property must be given a fallback: this plugin has no theme dependency.

## 4. Block `isudev/social-share-network` (the child)

Directory `src/blocks/social-share-network/`. Files: `block.php`, `block.json`,
`index.js`, `edit.js`, `save.js`, `view.js`, `render.php`, `style.scss`,
`editor.scss`, `inc/render-helpers.php`.

`block.php` descriptor: `slug` `social-share-network`, `name`
`isudev/social-share-network`, `requires` `array( 'social-share' )` — so
disabling the parent in the admin panel cascades — `always_on` false,
`variations` false, `bootstrap` `array( 'inc/render-helpers.php' )`.

`block.json`: `parent` `["isudev/social-share"]`, `category` `widgets`, `icon`
`share`, `supports` `html` false / `align` false / `reusable` false. Attributes
`network` (string, enum of the twelve, default `facebook`), `label` (string,
`''`), `showLabel` (boolean, false), `labelPosition` (string, enum
`before|after`, default `after`). `editorScript`, `style`, `editorStyle`,
`viewScript` `file:./view.js`, `render` `file:./render.php`.

`save.js`: `() => null`. The source returns `<InnerBlocks.Content />` for a block
that has no inner blocks; that is a bug, not a convention to carry over.

### `inc/render-helpers.php`

Namespace `IsuDevLibrary\Blocks\SocialShareNetwork`. Pure functions only — no
`get_permalink()`, no `get_the_title()`, no config reads. `render.php` gathers
that context and passes it in. This is what `tools/checks/80-social-share.php`
exercises.

```php
const NETWORKS = array( 'facebook', 'x', 'linkedin', 'whatsapp', 'bluesky', 'threads', 'mastodon', 'substack', 'link', 'email', 'print', 'system' );

function default_icon_names(): array          // network => registry icon name
function icon_name( string $network, array $overrides = array() ): string
function default_label( string $network ): string
function is_action_network( string $network ): bool
function share_url( string $network, string $permalink, string $title, array $templates = array() ): string
function network_classes( string $network, string $label_position, bool $show_label ): array
```

- `default_icon_names()`: `facebook => socialFacebook`, `x => socialX`,
  `linkedin => socialLinkedin`, `whatsapp => socialWhatsapp`,
  `bluesky => socialBluesky`, `threads => socialThreads`,
  `mastodon => socialMastodon`, `substack => socialSubstack`, `email => email`,
  `link => link`, `print => print`, `system => share`.
- `icon_name()`: an `$overrides` entry (from the `icons` config key) wins over the
  default; an unknown network returns `''`.
- `default_label()`: the twelve strings from the source's `get_default_label()`,
  text domain `isudev-library`.
- `is_action_network()`: true for `link`, `print`, `system`. See the markup rule
  below.
- `share_url()`: the source's `switch` for the nine URL networks, verbatim in the
  URL shapes and `rawurlencode()` placement. `$templates` carries the resolved
  `email` subject/body and `link` copy text, so template resolution stays out of
  a pure function. For `link` and `system` it returns the permalink; for `print`
  it returns `''`.
- `network_classes()`: `isudev-share__network`,
  `isudev-share__network--{network}`,
  `isudev-share__network--label-{position}`, plus
  `isudev-share__network--with-label` when the label is visible.

### `render.php`

Namespace `IsuDevLibrary\Blocks\SocialShareNetwork`, importing
`IsuDevLibrary\Utils\get_icon` and `IsuDevLibrary\Config\get_block_config`.

Config keys, **all under the parent's name `isudev/social-share`** — one key for
one feature, mirroring the source plugin's single `library.json` key. Say so in a
comment, because reading the parent's config from the child block is otherwise
surprising:

| Key | Type | Default | Effect |
| --- | --- | --- | --- |
| `networkLabel.disable` | boolean | false | Suppresses the label everywhere, overriding `showLabel`. |
| `networkLabel.allowToggle` | boolean | true | Editor: shows the "Show label" toggle. |
| `networkLabel.allowPosition` | boolean | true | Editor: shows the label position select. |
| `allowedNetworks` | string[] | all twelve | Editor: restricts the network selector. |
| `iconsSize` | int | 24 | Rendered icon size, editor and frontend. |
| `icons` | object | `{}` | network => registry icon name overrides. |
| `email.subject` / `email.body` | string | translated defaults | `%title%` and `%url%` templates. |
| `link.copyText` | string | `%url%` | `%title%` / `%url%` template for the clipboard. |

`networkLabel.allowCustom` from the source is **dropped**: `constants.js` resolves
it and nothing ever reads it. Do not port dead config.

Markup, with two deliberate departures from the source — both required by the
accessibility golden rule, and both to be called out in the CHANGELOG:

1. **Link vs button.** `is_action_network()` networks (`link`, `print`,
   `system`) render `<button type="button">`: they perform an action and go
   nowhere. `href="#"` for print, as the source does, is a link to nowhere. The
   other nine keep `<a href>`.
2. **No `title` attribute.** The source sets `title` and `aria-label` to the same
   string on every button. Keep `aria-label` only when the label is not visible
   (`! $show_label`); when the label is rendered as text it is already the
   accessible name, and a duplicate `aria-label` just overrides it with the same
   words.

Everything else is faithful: `data-network` on the control,
`data-message` / `data-copy-text` for the clipboard cases (renamed from the
source's `data-text`), label span before or after the icon, icon wrapped in
`<span class="isudev-share__network-icon">`.

The icon comes from `get_icon( icon_name( $network, $overrides ), array( 'size' => $icons_size ) )`.
Its registry defaults already add `aria-hidden="true"` and `focusable="false"`,
so do not add a second `aria-hidden` on the wrapping span.

`label` and the wrapper's `prefixText` are `RichText` values with
`allowedFormats: []`. Sanitize them with `wp_kses( $value, array() )`, not
`esc_html()`: the stored value already contains HTML entities, and `esc_html()`
would double-escape an `&`. `read-more`'s `sanitize_highlight()` solves the same
problem the same way — keep the helper block-local rather than sharing it.

### `edit.js`

Port `<source>/src/shareSocialNetwork/edit.js`, with:

- `getConstants()` → `getBlockConfig()`.
- `NetworkIcon` / `ToolbarNetworkIcon` / `window.dekodeShareToSocialFallbackIcons`
  → `Icon` from `@isudev/gutenberg`:

  ```js
  import { Icon, getLocalizedIcons } from '@isudev/gutenberg/components/Icon';

  const defaultIcons = getLocalizedIcons();   // module scope, read once
  <Icon name={ iconName } defaultIcons={ defaultIcons } size={ iconsSize } />
  ```

  `Icon` props are exactly `defaultIcons`, `icons`, `name`, `size`, `label`,
  `className`, `style` — see
  `node_modules/@isudev/gutenberg/src/components/Icon/README.md`. Unknown and
  empty names render nothing, which is the right behaviour for a network whose
  icon was overridden to a name that does not exist.
- `network-config.js` ported as-is (titles, default labels, the `system`
  description notice), text domain `isudev-library`.
- The toolbar `ToolbarDropdownMenu` of networks and the label toggle stay.

Known and accepted: `Icon` percent-encodes serialized SVG into an `<img>`, so
editor previews do not inherit `currentColor` and can look monochrome while the
frontend inline SVG tints correctly. That is a components-package limitation
already documented in `guides/work-with-icons.md` §7. Do not work around it by
duplicating icon data.

### `view.js`

Port `<source>/src/shareSocialNetwork/view.js`. It is frontend code, so globals
are allowed. Changes:

- Selector matches both element types and the renamed class:
  `a.isudev-share__network[data-network], button.isudev-share__network[data-network]`.
- `dataset.text` → `dataset.message`.
- Popup networks stay `facebook`, `x`, `linkedin`. Print, copy-link and system
  share now arrive as `<button>`, so `event.preventDefault()` is no longer needed
  for them; keep it only where an anchor is intercepted.
- The tooltip element becomes `isudev-share__network-message`, and it must not be
  the only announcement of success — add `role="status"` to it so a screen reader
  hears the copy confirmation.

### Styles

`style.scss` from `<source>/src/shareSocialNetwork/view.css`, renamed to the new
class contract, `--wp--preset--*` values all given fallbacks, and a
`button.isudev-share__network` reset (no default border, background or padding;
inherit the font) so a button and an anchor look identical.

## 5. Checks — `tools/checks/80-social-share.php`

`tools/check.php` discovers `tools/checks/*.php` by glob, so the file is picked
up automatically; the summary line goes from 8 to 9 check files. Requires
`src/blocks/social-share-network/inc/render-helpers.php` at the top, like
`70-read-more.php` does.

Cover, pragmatically — the URL builder is the part that silently breaks:

- `share_url()` for facebook, x, linkedin, whatsapp, bluesky, threads, mastodon,
  substack, email: the URL starts with the expected host and the permalink is
  present **percent-encoded**, i.e. the raw `https://example.com/post/` does not
  appear literally in the query string.
- `share_url( 'email', … )` with templates substitutes `%title%` and `%url%` in
  both subject and body, and yields a `mailto:` URL.
- `share_url( 'print', … )` is `''`; `share_url( 'link', … )` and
  `share_url( 'system', … )` are the permalink.
- `share_url()` for an unknown network is `''`.
- `icon_name()`: default map hit, an override wins, unknown network is `''`.
- `is_action_network()`: true for the three, false for a sample of the rest.
- `default_label()`: known network non-empty, unknown network `''`.
- `network_classes()`: contains the network and position classes, gains
  `--with-label` only when the label shows.

If a helper needs a WordPress function the runner does not shim yet, add the
shim to the marked shim block in `tools/check.php` following the existing style —
defined only when absent, with the `phpcs:ignore` for the non-prefixed function
name.

No e2e spec is added. The suite drives the front page and the post editor; this
block needs a page containing it, and there is no fixture for that. Say so
explicitly in the report rather than leaving it unexplained. `npm run test:e2e`
must still pass unchanged as a regression check.

## 6. Docs and version

- **`CHANGELOG.md`**: new `## 1.3.0 — 2026-08-11` section. Added: the two blocks,
  the twelve icons, the editor config bridge. Changed from the source plugin:
  action networks render as `<button>`, the `title` attribute is gone,
  `aria-label` only when the label is invisible, `allowCustom` dropped, no T2
  integration, `data-text` → `data-message`.
- **`README.md`**: add the two blocks wherever blocks are listed, and document
  `isudevLibraryConfig` if the README covers globals.
- **`guides/social-share.md`**: new. The full `isudev.json` config schema for
  `isudev/social-share` as one annotated JSON example, the CSS class contract for
  both blocks, the network → icon name map, and the note that the child reads the
  parent's config key.
- **`guides/work-with-icons.md`**: the registry table from Task 1.
- **`AGENTS.md`**: one bullet for the config bridge — the merged `library`
  subtree is published once for the block editor as `isudevLibraryConfig`,
  assigned (not appended, unlike `isudevIcons`) because the name is this
  plugin's own; `src/utils/config.js` is its JS mirror and must stay in step with
  `Config\resolve_block_value()`.
- Version `1.2.0` → `1.3.0` in `package.json`, the `isudev-library.php` plugin
  header, and `const VERSION`.

## 7. Definition of done

Run and report the actual output of each:

```bash
npm run build          # build/ is committed — do not gitignore it
npm run test:php       # expect 0 failed, 9 check files
npm run lint:js
npm run lint:css
composer run lint:php
npm run test:e2e       # regression only: 31 passed, 21 skipped
```

Then confirm by grep that no dead symbol survived the port:

```bash
grep -rn "dk-share-social\|dekodeShareToSocial\|@t2/editor\|T2\\\\Icons\|getLibraryConfig\|dekode-share-to-social-media\|allowCustom" src/ includes/ tools/ guides/
```

Report back with: the branch and commit list; the gate output verbatim; every
place you deviated from this plan and why; anything you could not verify. Do not
report a gate as passing without having run it.
