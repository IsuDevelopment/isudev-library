# `isudev/read-more` Block Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a second block to IsuDev Library — a linked card built on a link picker and media controls from `@isudev/gutenberg`, replacing the `t2`-coupled `isudev-read-more` plugin.

**Architecture:** The block follows the library's existing shape exactly: `block.php` returns a descriptor and registers nothing, `Loader` registers it, `save: () => null`, and all markup is produced by `render.php`. Editor UI composes three controls from `@isudev/gutenberg` (`BlockLinkControl`, `LinkPickerControl`, `MediaSourceControl` + `MediaSidebarControl`). Title and image resolve through a four-step precedence that is pure PHP, so it is covered by `tools/check.php` without WordPress.

**Tech Stack:** PHP 7.4+, WordPress 6.7+, `@wordpress/scripts` 31, React 19 via `@wordpress/element`, `@isudev/gutenberg` ~0.1.1 (ESM-only), SCSS.

**Spec:** `docs/superpowers/specs/2026-08-10-isudev-read-more-design.md`

## Global Constraints

Every task's requirements implicitly include this section.

- **Branch:** `feat/read-more-block`. Never commit to `main`.
- **Commit identity:** `git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no"`.
- **Blocks always render in PHP.** `save: () => null`. Never emit markup from `save`.
- **`Loader` is the only caller of `register_block_type()`.** `block.php` returns a descriptor array and registers nothing.
- **Purity boundary.** In any file with a `WordPress adapters` marker comment, nothing above that marker may call a WordPress function. `tools/check.php` requires those files with only `ABSPATH` defined.
- **No top-level hook registration in `includes/`.**
- **`apiVersion: 3`.** In editor JS never use global `document`/`window`; use `element.ownerDocument` via `useRefEffect` from `@wordpress/compose` if DOM access is ever needed. This block needs none.
- **WordPress Coding Standards.** `strict_types`, `ABSPATH` guard, `class-*.php` naming.
- **WPCS forbids these parameter names** (`Universal.NamingConventions.NoReservedKeywordParameterNames`): `$default $parent $namespace $array $class $function $list $new $print $static $string $use`. Locals and `foreach` keys are fine — parameters are not.
- **Docblock long descriptions must start with a capital letter** (`Generic.Commenting.DocComment.LongNotCapital`) and a `/* --- text --- */` marker comment is a hard error (`Squiz.Commenting.BlockComment.NoNewLine`). Use the plain `/*\n * Text.\n */` form.
- **`build/` is committed.** Do not gitignore it. Rebuild and commit `build/` whenever `src/` changes.
- **Block directory names must be globally unique** — `build/blocks-manifest.php` is keyed by directory basename.
- **Never run `npm start` / a watch loop.** Use one-shot `npm run build`.
- **wp-cli has no database access on this site.** Do not write verification steps using `wp eval`, `wp option` or `wp plugin`. Verify over HTTP against `http://isudev-library.local/`, with `tools/check.php`, or with Playwright.
- **Baseline before this plan:** `php tools/check.php` prints `107 passed, 0 failed (7 check files)`; `npm run test:e2e` prints `30 passed`, `20 skipped`.
- **How to report check counts.** Tasks state an expected **delta**, not an absolute. Report the exact `N passed, M failed` line verbatim, confirm `M` is `0`, and confirm the passed count rose by the stated delta. If it rose by a different amount, say so and explain before touching an assertion — a surprise delta is information, not noise. Note that `tools/checks/60-dist.php` derives its assertions from the repo, so adding a block file adds checks on its own: one per `src/blocks/<slug>/block.php`, one per `build/blocks/<slug>/block.json`, and one per bootstrap file listed in the descriptor.

---

### Task 1: Icon registry gains per-icon viewBox, plus `arrowForward`

`build_svg()` hardcodes `viewBox="0 0 600 600"`. The `arrowForward` glyph is authored on a `0 0 24 24` grid, so it must carry its own viewBox or it draws a fleck in the corner.

**Files:**
- Modify: `includes/utils/icon.php`
- Test: `tools/checks/50-icon.php`

**Interfaces:**
- Consumes: nothing from earlier tasks.
- Produces:
  - `IsuDevLibrary\Utils\default_icon_view_boxes(): array` — slug ⇒ viewBox string, holding only entries that differ from the default.
  - `IsuDevLibrary\Utils\build_svg( string $path_d, int $size, string $class_attr, string $view_box = '0 0 600 600' ): string`
  - `IsuDevLibrary\Utils\icon( string $slug, int $size = 24, string $class_name = '' ): string` — unchanged signature; now resolves a viewBox.
  - Filter `isudev_library/icon_view_boxes`.
  - Icon slug `arrowForward`.

- [ ] **Step 1: Write the failing checks**

Append to `tools/checks/50-icon.php`, and add `default_icon_view_boxes` to the `use function` list at the top of that file:

```php
use function IsuDevLibrary\Utils\default_icon_view_boxes;
```

First **edit** the existing count assertion at `tools/checks/50-icon.php:20` — do
not add a second one beside it, or the old line fails the moment the fourth
glyph lands:

```php
Checks::is( 'icons: exactly four defaults', \count( $paths ), 4 );
```

Then append:

```php
$boxes = default_icon_view_boxes();

Checks::is( 'icons: arrowForward is registered', isset( $paths['arrowForward'] ), true );
Checks::is( 'icons: arrowForward declares its own viewBox', $boxes['arrowForward'] ?? '', '0 0 24 24' );

/*
 * The three original glyphs are authored on the 600 grid and must NOT appear
 * in the override map — an entry there would silently rescale them.
 */
Checks::is( 'icons: chevronDown has no viewBox override', isset( $boxes['chevronDown'] ), false );
Checks::is( 'icons: burger has no viewBox override', isset( $boxes['burger'] ), false );
Checks::is( 'icons: close has no viewBox override', isset( $boxes['close'] ), false );

$default_box = build_svg( '<path d="M0 0"/>', 24, 'x' );
Checks::is(
	'build_svg: defaults to the 600 grid when no viewBox is passed',
	\strpos( $default_box, 'viewBox="0 0 600 600"' ) !== false,
	true
);

$custom_box = build_svg( '<path d="M0 0"/>', 24, 'x', '0 0 24 24' );
Checks::is(
	'build_svg: honours an explicit viewBox',
	\strpos( $custom_box, 'viewBox="0 0 24 24"' ) !== false,
	true
);
Checks::is(
	'build_svg: an explicit viewBox replaces the default rather than adding one',
	\substr_count( $custom_box, 'viewBox=' ),
	1
);
Checks::is(
	'build_svg: an empty viewBox falls back to the default',
	\strpos( build_svg( '<path d="M0 0"/>', 24, 'x', '' ), 'viewBox="0 0 600 600"' ) !== false,
	true
);

// The glyph must be path markup only: the wrapper already sets fill, and a
// fill on the path would override it and break currentColor tinting.
Checks::is(
	'icons: arrowForward carries no fill of its own',
	\strpos( $paths['arrowForward'] ?? '', 'fill=' ),
	false
);
```

- [ ] **Step 2: Run the checks to verify they fail**

```bash
php tools/check.php
```

Expected: fatal `Call to undefined function IsuDevLibrary\Utils\default_icon_view_boxes()`.

- [ ] **Step 3: Implement**

In `includes/utils/icon.php`, add after `default_icon_paths()`:

```php
/**
 * Viewbox overrides for glyphs not authored on the default grid. Pure.
 *
 * Only slugs that differ from DEFAULT_VIEW_BOX appear here.
 *
 * @return array slug => viewBox attribute value.
 */
function default_icon_view_boxes(): array {
	return array(
		'arrowForward' => '0 0 24 24',
	);
}
```

Add the constant just below the `defined( 'ABSPATH' ) || exit;` line:

```php
const DEFAULT_VIEW_BOX = '0 0 600 600';
```

Add the glyph to `default_icon_paths()`'s returned array, after `'close'`:

```php
'arrowForward' => '<path d="M11.3 19.3c-.2-.2-.3-.4-.3-.7 0-.3.1-.5.3-.7l4.9-4.9H5c-.3 0-.5-.1-.7-.3-.2-.2-.3-.4-.3-.7s.1-.5.3-.7c.2-.2.4-.3.7-.3h11.2l-4.9-4.9c-.2-.2-.3-.4-.3-.7 0-.3.1-.5.3-.7.2-.2.4-.3.7-.3s.5.1.7.3l6.6 6.6c.1.1.2.2.2.3 0 .1.1.3.1.4 0 .1 0 .3-.1.4 0 .1-.1.2-.2.3l-6.6 6.6c-.2.2-.4.3-.7.3s-.5-.1-.7-.3z"/>',
```

Replace `build_svg()` wholesale:

```php
/**
 * Wrap icon path markup in an SVG element. Pure.
 *
 * @param string $path_d     SVG child markup (already trusted, static).
 * @param int    $size       Pixel size for width and height.
 * @param string $class_attr Escaped value for the class attribute.
 * @param string $view_box   Escaped viewBox value; empty falls back to the default grid.
 * @return string SVG markup, or '' when there is nothing to draw.
 */
function build_svg( string $path_d, int $size, string $class_attr, string $view_box = DEFAULT_VIEW_BOX ): string {
	if ( '' === $path_d ) {
		return '';
	}

	if ( '' === $view_box ) {
		$view_box = DEFAULT_VIEW_BOX;
	}

	return \sprintf(
		'<svg class="%1$s" width="%2$d" height="%2$d" viewBox="%4$s" fill="currentColor" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">%3$s</svg>',
		$class_attr,
		$size,
		$path_d,
		$view_box
	);
}
```

In `icon()`, between the `$path_d` line and the `$svg` line, insert:

```php
	/**
	 * Filters the icon viewBox overrides, alongside isudev_library/icons.
	 *
	 * @param array $boxes slug => viewBox attribute value.
	 */
	$boxes = (array) \apply_filters( 'isudev_library/icon_view_boxes', default_icon_view_boxes() );

	$view_box = isset( $boxes[ $slug ] ) && \is_string( $boxes[ $slug ] ) ? $boxes[ $slug ] : DEFAULT_VIEW_BOX;
```

and change the `$svg` assignment to:

```php
	$svg = build_svg( $path_d, $size, \esc_attr( $class_name ), \esc_attr( $view_box ) );
```

- [ ] **Step 4: Run the checks to verify they pass**

```bash
php tools/check.php
```

Expected: still `7 check files`, `0 failed`, and the passed count **up by 10** from the 107 baseline. Report the verbatim line.

- [ ] **Step 5: Mutation-test the new checks**

Run each mutation, confirm the *named* check fails, then restore the file exactly.

1. In `build_svg()`, delete the `if ( '' === $view_box )` fallback block.
   Expected: `build_svg: an empty viewBox falls back to the default` fails.
2. In `build_svg()`, change `viewBox="%4$s"` back to `viewBox="0 0 600 600"`.
   Expected: `build_svg: honours an explicit viewBox` fails.
3. In `default_icon_view_boxes()`, change `'0 0 24 24'` to `'0 0 600 600'`.
   Expected: `icons: arrowForward declares its own viewBox` fails.
4. In `default_icon_view_boxes()`, add `'close' => '0 0 24 24'`.
   Expected: `icons: close has no viewBox override` fails.

Paste the verbatim failure line for each. After the fourth, run `git diff includes/utils/icon.php` and paste the output to prove the file is back to the implemented state.

- [ ] **Step 6: Lint and commit**

```bash
composer run lint:php
php tools/check.php
git add includes/utils/icon.php tools/checks/50-icon.php
git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" commit -m "feat: per-icon viewBox in the icon registry, plus arrowForward

The three existing glyphs are authored on a 0 0 600 600 grid that build_svg()
hardcoded. arrowForward is authored on 0 0 24 24, so the viewBox has to travel
with the glyph. build_svg() gains an optional fourth parameter defaulting to
the old value, so every existing caller is unaffected."
```

---

### Task 2: Block scaffold that registers, builds and appears in the inserter

Deliberately minimal editor UI — enough to prove the whole toolchain (ESM resolution of `@isudev/gutenberg`, DEWP externals, the manifest, the Loader, the panel) before any block logic exists.

**Files:**
- Modify: `package.json`
- Create: `src/blocks/read-more/block.json`
- Create: `src/blocks/read-more/block.php`
- Create: `src/blocks/read-more/index.js`
- Create: `src/blocks/read-more/edit.js`
- Create: `src/blocks/read-more/save.js`
- Create: `src/blocks/read-more/icon.js`
- Create: `src/blocks/read-more/render.php`
- Create: `src/blocks/read-more/style.scss`
- Create: `src/blocks/read-more/editor.scss`

**Interfaces:**
- Consumes: `IsuDevLibrary\Utils\icon()` from Task 1 (not yet called; Task 4 calls it).
- Produces: block `isudev/read-more`, slug `read-more`; the attribute set every later task reads; `build/blocks/read-more/`.

- [ ] **Step 1: Add the dependencies**

Edit `package.json`. Add a `dependencies` block (the file currently has only `devDependencies`), placed immediately before `"devDependencies"`:

```json
	"dependencies": {
		"@isudev/gutenberg": "~0.1.1"
	},
```

Add these to `devDependencies`, keeping the block alphabetically sorted:

```json
		"@wordpress/editor": "^15.0.0",
		"@wordpress/html-entities": "^4.35.0",
		"@wordpress/icons": "^11.5.0",
		"@wordpress/primitives": "^4.35.0",
```

Rationale, for the record: `@isudev/gutenberg` is a runtime dependency because its code is bundled into our block script. `@wordpress/editor` and `@wordpress/primitives` are declared peers that `@wordpress/dependency-extraction-webpack-plugin` externalizes to `wp-editor` / `wp-primitives`. `@wordpress/icons` is a peer that DEWP does **not** externalize — it gets bundled, so it must resolve at build time or the build fails.

- [ ] **Step 2: Install and verify resolution**

```bash
nvm use
npm install
node -e "console.log(require('./node_modules/@isudev/gutenberg/package.json').version)"
```

Expected: `0.1.1` (or a `0.1.x` patch). If npm reports peer conflicts, stop and report them — do not pass `--legacy-peer-deps`, which has broken this repo's `node_modules` before.

- [ ] **Step 3: Write `block.json`**

```json
{
	"$schema": "https://schemas.wp.org/trunk/block.json",
	"apiVersion": 3,
	"name": "isudev/read-more",
	"version": "1.0.0",
	"title": "Read More Block",
	"category": "design",
	"icon": "arrow-right-alt",
	"description": "A linked card: title, optional image, optional badge and supporting text. Points anywhere — a post, a page or an external URL. By IsuDev.",
	"textdomain": "isudev-library",
	"supports": {
		"html": false,
		"align": ["wide", "full"],
		"color": {
			"text": true,
			"background": true
		},
		"spacing": {
			"margin": true,
			"padding": true
		}
	},
	"attributes": {
		"_namespace": {
			"type": "string",
			"default": "",
			"description": "Variation identifier injected by IsuDevLibrary\\Variations. Must be declared here or isActive matching never resolves."
		},
		"link": { "type": "object", "default": {} },
		"media": { "type": "object", "default": {} },
		"focalPoint": { "type": "object" },
		"hasCustomTitle": { "type": "boolean", "default": false },
		"customTitle": { "type": "string", "default": "" },
		"showAdditionalText": { "type": "boolean", "default": false },
		"additionalText": { "type": "string", "default": "" },
		"renderAsHeading": { "type": "boolean", "default": true },
		"headingLevel": { "type": "number", "default": 3 },
		"readMoreText": { "type": "string", "default": "" },
		"showReadMoreBadge": { "type": "boolean", "default": false },
		"showFeaturedImage": { "type": "boolean", "default": true }
	},
	"editorScript": "file:./index.js",
	"style": "file:./style-index.css",
	"editorStyle": "file:./index.css",
	"render": "file:./render.php"
}
```

`_namespace` is not optional. Without it declared here, a variation registered from `isudev.json` sets the attribute but `isActive: ['_namespace']` never resolves, so the variation registers and stays permanently inactive with no error.

- [ ] **Step 4: Write `block.php`**

```php
<?php
/**
 * Descriptor for the isudev/read-more block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'read-more',
	'name'       => 'isudev/read-more',
	'requires'   => array(),
	'always_on'  => false,
	'variations' => true,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
```

- [ ] **Step 5: Create the bootstrap file as an empty namespaced stub**

`src/blocks/read-more/inc/render-helpers.php`. The descriptor above lists it, and `Loader::register()` bails on a bootstrap file it cannot resolve, so it must exist from this task onward. Task 3 fills it.

```php
<?php
/**
 * Render helpers for the read-more card block.
 *
 * @package IsuDevLibrary\Blocks\ReadMore
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ReadMore;

defined( 'ABSPATH' ) || exit;
```

- [ ] **Step 6: Write `icon.js`**

```jsx
/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

export default (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path
			fill="currentColor"
			d="m7.45 17.45l-1.4-1.4L9.075 13H2v-2h7.075L6.05 7.95l1.4-1.4L12.9 12zM13 17v-2h9v2zm0-8V7h9v2zm3 4v-2h6v2z"
		/>
	</SVG>
);
```

- [ ] **Step 7: Write `save.js`**

```js
/**
 * The block renders in PHP. Returning null keeps post_content free of markup
 * and makes render.php the single source of truth.
 *
 * @return {null} Nothing.
 */
export default function save() {
	return null;
}
```

- [ ] **Step 8: Write `index.js`**

```js
/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import icon from './icon';
import './style.scss';
import './editor.scss';

registerBlockType(metadata.name, {
	edit: Edit,
	save,
	icon,
});
```

- [ ] **Step 9: Write a minimal `edit.js` that exercises the library import**

This is the whole point of the task: prove `@isudev/gutenberg` resolves, bundles and renders before building anything on top of it.

```jsx
/**
 * External dependencies
 */
import { LinkPickerControl } from '@isudev/gutenberg/controls/LinkPickerControl';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Button, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from './icon';

export default function Edit({ attributes, setAttributes }) {
	const { link } = attributes;
	const blockProps = useBlockProps({ className: 'wp-block-isudev-read-more' });

	return (
		<div {...blockProps}>
			<LinkPickerControl
				value={link}
				onChange={(next) => setAttributes({ link: next })}
			>
				{({ anchorRef, open }) => (
					<div ref={anchorRef}>
						<Placeholder
							icon={icon}
							label={__('Read More', 'isudev-library')}
							instructions={__(
								'Choose where this card should link to.',
								'isudev-library'
							)}
						>
							<Button variant="primary" onClick={open}>
								{__('Pick link', 'isudev-library')}
							</Button>
						</Placeholder>
					</div>
				)}
			</LinkPickerControl>
			{link?.url ? <p>{link.url}</p> : null}
		</div>
	);
}
```

- [ ] **Step 10: Write placeholder styles**

`src/blocks/read-more/style.scss`:

```scss
/* Read More Block — frontend card layout. Filled in by the styling task. */

.wp-block-isudev-read-more {
	display: block;
}
```

`src/blocks/read-more/editor.scss`:

```scss
/* Read More Block — editor-only affordances. Filled in by the styling task. */

.wp-block-isudev-read-more {
	position: relative;
}
```

- [ ] **Step 11: Write a minimal `render.php`**

```php
<?php
/**
 * Server render for isudev/read-more.
 *
 * @package IsuDevLibrary\Blocks\ReadMore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused; this block has no InnerBlocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ReadMore;

defined( 'ABSPATH' ) || exit;

$link = isset( $attributes['link'] ) && \is_array( $attributes['link'] ) ? $attributes['link'] : array();
$url  = isset( $link['url'] ) ? (string) $link['url'] : '';

if ( '' === $url ) {
	return;
}

printf(
	'<a %1$s href="%2$s"></a>',
	\get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	\esc_url( $url )
);
```

- [ ] **Step 12: Build and verify the toolchain**

```bash
nvm use
npm run build
ls build/blocks/read-more/
grep -c "read-more" build/blocks-manifest.php
```

Expected: `build/blocks/read-more/` contains `block.json`, `index.js`, `index.asset.php`, `index.css`, `index-rtl.css`, `style-index.css`, `style-index-rtl.css`, `render.php`. The manifest mentions `read-more`.

Then confirm the library actually bundled rather than being externalized, and that `wp-primitives` was extracted:

```bash
cat build/blocks/read-more/index.asset.php
grep -c "isudev/gutenberg" build/blocks/read-more/index.js || echo "bundled under a mangled name (expected)"
```

Expected in `index.asset.php`: a dependency array containing at least `react`, `react-jsx-runtime`, `wp-block-editor`, `wp-blocks`, `wp-components`, `wp-i18n`, `wp-primitives`. Paste it verbatim.

- [ ] **Step 13: Verify registration end to end**

```bash
php tools/check.php
curl -s -o /dev/null -w "%{http_code}\n" http://isudev-library.local/
```

Expected: `0 failed`, and the passed count **up by 3** from Task 1's figure — `60-dist.php` derives its file list from the repo, so it now also asserts `src/blocks/read-more/block.php`, `src/blocks/read-more/inc/render-helpers.php` and `build/blocks/read-more/block.json` survive `.distignore`. Report the verbatim line. Site returns `200`.

Then confirm the panel sees two blocks. Log in with the credentials in `tools/.e2e-credentials.json` and read the REST list — the existing `e2e/admin-auth.js` helper does this; the quickest check is a one-off Playwright run in the next task. For now assert discovery from PHP:

```bash
php -r '
define( "ABSPATH", __DIR__ . "/" );
$files = glob( __DIR__ . "/src/blocks/*/block.php" );
foreach ( $files as $f ) { $d = require $f; echo $d["slug"], " => ", $d["name"], PHP_EOL; }
'
```

Expected two lines: `read-more => isudev/read-more` and `site-header => isudev/site-header`.

- [ ] **Step 14: Lint and commit**

```bash
composer run lint:php
npm run lint:js
npm run lint:css
git add package.json package-lock.json src/blocks/read-more build/
git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" commit -m "feat: scaffold the isudev/read-more block

Descriptor, block.json, an editor stub that mounts LinkPickerControl from
@isudev/gutenberg, and a render.php that emits only the anchor. Enough to
prove ESM resolution, DEWP externals, the manifest and the Loader before any
block logic exists."
```

---

### Task 3: Pure render helpers and their checks

Title and image precedence is the block's real logic. It is pure, so it is tested without WordPress.

**Files:**
- Modify: `src/blocks/read-more/inc/render-helpers.php`
- Test: `tools/checks/70-read-more.php`

**Interfaces:**
- Consumes: nothing.
- Produces, all in namespace `IsuDevLibrary\Blocks\ReadMore`:
  - `pick_title( bool $has_custom, string $custom, string $post_title, string $link_title, string $url ): string`
  - `pick_image( array $media, int $thumbnail_id ): array` returning `array( 'kind' => 'attachment'|'url'|'none', 'id' => int, 'url' => string, 'alt' => string )`
  - `card_classes( bool $has_image, bool $has_badge, string $link_type ): array` — raw, unsanitized names
  - `heading_tag( bool $render_as_heading, int $level ): string`

- [ ] **Step 1: Write the failing checks**

Create `tools/checks/70-read-more.php`:

```php
<?php
/**
 * Checks for the pure parts of the read-more card block.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/src/blocks/read-more/inc/render-helpers.php';

use function IsuDevLibrary\Blocks\ReadMore\card_classes;
use function IsuDevLibrary\Blocks\ReadMore\heading_tag;
use function IsuDevLibrary\Blocks\ReadMore\pick_image;
use function IsuDevLibrary\Blocks\ReadMore\pick_title;

/*
 * pick_title: four-step precedence.
 */
Checks::is(
	'pick_title: a custom title wins over everything',
	pick_title( true, 'Custom', 'Post', 'Link', 'https://example.com/' ),
	'Custom'
);
Checks::is(
	'pick_title: the linked post title wins over the link label',
	pick_title( false, 'Custom', 'Post', 'Link', 'https://example.com/' ),
	'Post'
);
Checks::is(
	'pick_title: the link label is used when no post resolved',
	pick_title( false, 'Custom', '', 'Link', 'https://example.com/' ),
	'Link'
);
Checks::is(
	'pick_title: the URL is the last resort so a card is never blank',
	pick_title( false, 'Custom', '', '', 'https://example.com/' ),
	'https://example.com/'
);

/*
 * The dangerous edge: hasCustomTitle true but the string is empty. Falling
 * through matters — otherwise switching the toggle on blanks the card until
 * something is typed.
 */
Checks::is(
	'pick_title: an empty custom title falls through instead of blanking the card',
	pick_title( true, '', 'Post', 'Link', 'https://example.com/' ),
	'Post'
);
Checks::is(
	'pick_title: a whitespace-only custom title also falls through',
	pick_title( true, '   ', 'Post', 'Link', 'https://example.com/' ),
	'Post'
);
Checks::is(
	'pick_title: everything empty yields an empty string, not a notice',
	pick_title( false, '', '', '', '' ),
	''
);

/*
 * pick_image: four-step precedence.
 */
Checks::is(
	'pick_image: an explicit attachment wins',
	pick_image( array( 'id' => 7, 'url' => 'https://cdn/x.jpg', 'alt' => 'A' ), 42 ),
	array( 'kind' => 'attachment', 'id' => 7, 'url' => 'https://cdn/x.jpg', 'alt' => 'A' )
);
Checks::is(
	'pick_image: a URL-only selection has no attachment id',
	pick_image( array( 'url' => 'https://cdn/x.jpg', 'source' => 'url' ), 42 ),
	array( 'kind' => 'url', 'id' => 0, 'url' => 'https://cdn/x.jpg', 'alt' => '' )
);
Checks::is(
	'pick_image: falls back to the linked post thumbnail',
	pick_image( array(), 42 ),
	array( 'kind' => 'attachment', 'id' => 42, 'url' => '', 'alt' => '' )
);
Checks::is(
	'pick_image: nothing at all is reported as none',
	pick_image( array(), 0 ),
	array( 'kind' => 'none', 'id' => 0, 'url' => '', 'alt' => '' )
);
Checks::is(
	'pick_image: a zero id is not a selection',
	pick_image( array( 'id' => 0 ), 42 ),
	array( 'kind' => 'attachment', 'id' => 42, 'url' => '', 'alt' => '' )
);
Checks::is(
	'pick_image: an empty url string is not a selection',
	pick_image( array( 'url' => '' ), 0 ),
	array( 'kind' => 'none', 'id' => 0, 'url' => '', 'alt' => '' )
);
/*
 * `true` is the value that actually distinguishes the is_numeric() guard:
 * (int) true is 1, so without the guard a boolean id would resolve to
 * attachment #1 — a real image belonging to someone else. A string like
 * 'seven' casts to 0 either way and would NOT pin the guard.
 */
Checks::is(
	'pick_image: a boolean id is not an attachment id',
	pick_image( array( 'id' => true ), 0 ),
	array( 'kind' => 'none', 'id' => 0, 'url' => '', 'alt' => '' )
);

/*
 * card_classes.
 */
Checks::is(
	'card_classes: all three flags present',
	card_classes( true, true, 'page' ),
	array( 'has-image', 'has-read-more-badge', 'is-link-type-page' )
);
Checks::is(
	'card_classes: nothing set yields an empty list, not a list of empties',
	card_classes( false, false, '' ),
	array()
);
Checks::is(
	'card_classes: the link type alone',
	card_classes( false, false, 'post' ),
	array( 'is-link-type-post' )
);

/*
 * heading_tag.
 */
Checks::is( 'heading_tag: level 2', heading_tag( true, 2 ), 'h2' );
Checks::is( 'heading_tag: level 6', heading_tag( true, 6 ), 'h6' );
Checks::is( 'heading_tag: h1 is out of range and clamps to h3', heading_tag( true, 1 ), 'h3' );
Checks::is( 'heading_tag: level 7 is out of range and clamps to h3', heading_tag( true, 7 ), 'h3' );
Checks::is( 'heading_tag: zero clamps to h3', heading_tag( true, 0 ), 'h3' );
Checks::is( 'heading_tag: not a heading yields a div', heading_tag( false, 2 ), 'div' );
```

- [ ] **Step 2: Run the checks to verify they fail**

```bash
php tools/check.php
```

Expected: fatal `Call to undefined function IsuDevLibrary\Blocks\ReadMore\pick_title()`.

- [ ] **Step 3: Implement**

Append to `src/blocks/read-more/inc/render-helpers.php`:

```php
/**
 * Choose the card title from the available sources. Pure.
 *
 * A custom title that is present but blank falls through rather than emptying
 * the card, so switching the toggle on before typing is not destructive.
 *
 * @param bool   $has_custom Whether the custom-title toggle is on.
 * @param string $custom     The author's custom title.
 * @param string $post_title Title of the linked post, '' when none resolved.
 * @param string $link_title Label carried by the link value.
 * @param string $url        The link URL, used as a last resort.
 * @return string
 */
function pick_title( bool $has_custom, string $custom, string $post_title, string $link_title, string $url ): string {
	if ( $has_custom && '' !== \trim( $custom ) ) {
		return $custom;
	}

	if ( '' !== $post_title ) {
		return $post_title;
	}

	if ( '' !== $link_title ) {
		return $link_title;
	}

	return $url;
}

/**
 * Decide which image source wins, without rendering anything. Pure.
 *
 * @param array $media        The media attribute.
 * @param int   $thumbnail_id Featured image id of the linked post, 0 when none.
 * @return array{kind:string,id:int,url:string,alt:string}
 */
function pick_image( array $media, int $thumbnail_id ): array {
	$none = array(
		'kind' => 'none',
		'id'   => 0,
		'url'  => '',
		'alt'  => '',
	);

	$id  = isset( $media['id'] ) && \is_numeric( $media['id'] ) ? (int) $media['id'] : 0;
	$url = isset( $media['url'] ) && \is_string( $media['url'] ) ? $media['url'] : '';
	$alt = isset( $media['alt'] ) && \is_string( $media['alt'] ) ? $media['alt'] : '';

	if ( $id > 0 ) {
		return array(
			'kind' => 'attachment',
			'id'   => $id,
			'url'  => $url,
			'alt'  => $alt,
		);
	}

	if ( '' !== $url ) {
		return array(
			'kind' => 'url',
			'id'   => 0,
			'url'  => $url,
			'alt'  => $alt,
		);
	}

	if ( $thumbnail_id > 0 ) {
		return array(
			'kind' => 'attachment',
			'id'   => $thumbnail_id,
			'url'  => '',
			'alt'  => '',
		);
	}

	return $none;
}

/**
 * Build the wrapper class list. Pure, and deliberately unsanitized.
 *
 * The caller passes each name through sanitize_html_class(), which is a
 * WordPress function and would break this file's purity.
 *
 * @param bool   $has_image Whether an image will be rendered.
 * @param bool   $has_badge Whether the read-more badge is shown.
 * @param string $link_type Link entity type, '' when unknown.
 * @return array
 */
function card_classes( bool $has_image, bool $has_badge, string $link_type ): array {
	$classes = array();

	if ( $has_image ) {
		$classes[] = 'has-image';
	}

	if ( $has_badge ) {
		$classes[] = 'has-read-more-badge';
	}

	if ( '' !== $link_type ) {
		$classes[] = 'is-link-type-' . $link_type;
	}

	return $classes;
}

/**
 * Resolve the title element name. Pure.
 *
 * @param bool $render_as_heading Whether the title is a heading at all.
 * @param int  $level             Requested heading level.
 * @return string
 */
function heading_tag( bool $render_as_heading, int $level ): string {
	if ( ! $render_as_heading ) {
		return 'div';
	}

	return \in_array( $level, array( 2, 3, 4, 5, 6 ), true ) ? 'h' . $level : 'h3';
}
```

- [ ] **Step 4: Run the checks to verify they pass**

```bash
php tools/check.php
```

Expected: `8 check files`, `0 failed`, and the passed count **up by 23** from Task 2's figure. Report the verbatim line.

- [ ] **Step 5: Mutation-test**

For each, apply the mutation, run `php tools/check.php`, confirm the named check fails, restore.

1. In `pick_title()`, change `'' !== \trim( $custom )` to `'' !== $custom`.
   Expected: `pick_title: a whitespace-only custom title also falls through` fails.
2. In `pick_title()`, drop the `$has_custom &&` guard.
   Expected: `pick_title: the linked post title wins over the link label` fails.
3. In `pick_image()`, change the `$id > 0` test to `isset( $media['id'] )`.
   Expected: `pick_image: a zero id is not a selection` fails.
4. In `pick_image()`, swap the URL branch above the id branch.
   Expected: `pick_image: an explicit attachment wins` fails.
5. In `pick_image()`, change the id guard from `\is_numeric( $media['id'] )` to `isset( $media['id'] )`.
   Expected: `pick_image: a boolean id is not an attachment id` fails, with `actual` showing `'kind' => 'attachment', 'id' => 1`.
6. In `heading_tag()`, change the allowed list to `array( 1, 2, 3, 4, 5, 6 )`.
   Expected: `heading_tag: h1 is out of range and clamps to h3` fails.
7. In `card_classes()`, drop the `'' !== $link_type` guard.
   Expected: `card_classes: nothing set yields an empty list, not a list of empties` fails.

Paste the verbatim failure line for each, then `git diff src/blocks/read-more/inc/render-helpers.php` to prove restoration.

- [ ] **Step 6: Lint and commit**

```bash
composer run lint:php
php tools/check.php
git add src/blocks/read-more/inc/render-helpers.php tools/checks/70-read-more.php
git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" commit -m "feat: pure title and image precedence for the read-more card

Four-step precedence each, tested without WordPress. The load-bearing edge is
an empty custom title falling through rather than blanking the card."
```

---

### Task 4: Server render

**Files:**
- Modify: `src/blocks/read-more/inc/render-helpers.php` (adapters section)
- Modify: `src/blocks/read-more/render.php`

**Interfaces:**
- Consumes: Task 3's four pure functions; `IsuDevLibrary\Utils\icon()` from Task 1.
- Produces, below a `WordPress adapters` marker in `inc/render-helpers.php`:
  - `sanitize_highlight( string $content ): string`
  - `resolve_link_post( array $link ): int`
  - `render_image( array $picked ): array{html:string,has_image:bool}`

- [ ] **Step 1: Add the adapters**

Append to `src/blocks/read-more/inc/render-helpers.php`:

```php
/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Preserve the native RichText highlight while rejecting all other markup.
 *
 * WordPress stores the core/text-color format as a mark element. The style
 * attribute is additionally filtered by WordPress' safe CSS allowlist.
 *
 * @param string $content RichText content.
 * @return string
 */
function sanitize_highlight( string $content ): string {
	return \wp_kses(
		$content,
		array(
			'mark' => array(
				'class' => true,
				'style' => true,
			),
		)
	);
}

/**
 * Resolve the linked post id, but only for a post this visitor may see.
 *
 * Without the visibility guard, linking a draft would leak its title onto a
 * public page through the title fallback.
 *
 * @param array $link The link attribute.
 * @return int Post id, or 0 when the link is not a viewable post.
 */
function resolve_link_post( array $link ): int {
	$kind = isset( $link['kind'] ) && \is_string( $link['kind'] ) ? $link['kind'] : '';
	$id   = isset( $link['id'] ) && \is_numeric( $link['id'] ) ? (int) $link['id'] : 0;

	if ( 'post-type' !== $kind || $id <= 0 ) {
		return 0;
	}

	$post = \get_post( $id );

	if ( ! $post instanceof \WP_Post || ! \is_post_publicly_viewable( $post ) ) {
		return 0;
	}

	return $id;
}

/**
 * Turn a pick_image() descriptor into figure markup.
 *
 * Reports whether an image was actually drawn, because an attachment id can
 * point at a deleted attachment and yield nothing. The caller needs that
 * answer for the has-image wrapper class and must not re-derive it by
 * searching the returned markup.
 *
 * @param array $picked Descriptor from pick_image().
 * @return array{html:string,has_image:bool}
 */
function render_image( array $picked ): array {
	$kind = isset( $picked['kind'] ) ? (string) $picked['kind'] : 'none';
	$html = '';

	if ( 'attachment' === $kind ) {
		$html = (string) \wp_get_attachment_image(
			(int) $picked['id'],
			'medium',
			false,
			array( 'sizes' => '(max-width: 600px) 100vw, 600px' )
		);
	}

	if ( 'url' === $kind ) {
		$html = \sprintf(
			'<img src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
			\esc_url( (string) $picked['url'] ),
			\esc_attr( (string) $picked['alt'] )
		);
	}

	if ( '' === $html ) {
		return array(
			'html'      => '<figure class="read-more-image no-image"></figure>',
			'has_image' => false,
		);
	}

	return array(
		'html'      => '<figure class="read-more-image">' . $html . '</figure>',
		'has_image' => true,
	);
}
```

- [ ] **Step 2: Write the real `render.php`**

Replace the file entirely:

```php
<?php
/**
 * Server render for isudev/read-more.
 *
 * @package IsuDevLibrary\Blocks\ReadMore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused; this block has no InnerBlocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ReadMore;

use function IsuDevLibrary\Utils\icon;

defined( 'ABSPATH' ) || exit;

$link = isset( $attributes['link'] ) && \is_array( $attributes['link'] ) ? $attributes['link'] : array();
$url  = isset( $link['url'] ) && \is_string( $link['url'] ) ? $link['url'] : '';

// A card with no destination is not a card.
if ( '' === \trim( $url ) ) {
	return;
}

$media               = isset( $attributes['media'] ) && \is_array( $attributes['media'] ) ? $attributes['media'] : array();
$show_image          = ! isset( $attributes['showFeaturedImage'] ) || (bool) $attributes['showFeaturedImage'];
$show_badge          = ! empty( $attributes['showReadMoreBadge'] );
$show_additional     = ! empty( $attributes['showAdditionalText'] );
$has_custom_title    = ! empty( $attributes['hasCustomTitle'] );
$custom_title        = isset( $attributes['customTitle'] ) && \is_string( $attributes['customTitle'] ) ? $attributes['customTitle'] : '';
$additional_text     = isset( $attributes['additionalText'] ) && \is_string( $attributes['additionalText'] ) ? $attributes['additionalText'] : '';
$read_more_text      = isset( $attributes['readMoreText'] ) && \is_string( $attributes['readMoreText'] ) ? $attributes['readMoreText'] : '';
$render_as_heading   = ! isset( $attributes['renderAsHeading'] ) || (bool) $attributes['renderAsHeading'];
$heading_level       = isset( $attributes['headingLevel'] ) ? (int) $attributes['headingLevel'] : 3;
$link_type           = isset( $link['type'] ) && \is_string( $link['type'] ) ? $link['type'] : '';
$opens_in_new_tab    = ! empty( $link['opensInNewTab'] );
$is_nofollow         = ! empty( $link['nofollow'] );

$post_id    = resolve_link_post( $link );
$post_title = $post_id > 0 ? (string) \get_the_title( $post_id ) : '';
$link_title = isset( $link['title'] ) && \is_string( $link['title'] ) ? $link['title'] : '';

$title     = pick_title( $has_custom_title, $custom_title, $post_title, $link_title, $url );
$title_tag = heading_tag( $render_as_heading, $heading_level );

$thumbnail_id = $post_id > 0 ? (int) \get_post_thumbnail_id( $post_id ) : 0;
$picked       = pick_image( $media, $thumbnail_id );

/*
 * render_image() reports whether it drew anything, rather than the caller
 * sniffing its markup for 'no-image' — an attachment id can resolve to
 * nothing, so the descriptor alone does not answer this.
 */
$figure    = '';
$has_image = false;

if ( $show_image ) {
	$rendered  = render_image( $picked );
	$figure    = $rendered['html'];
	$has_image = $rendered['has_image'];
}

$badge = $show_badge
	? \sprintf(
		'<span class="wp-block-button__read_more">%s</span>',
		sanitize_highlight( '' !== $read_more_text ? $read_more_text : \__( 'Read more', 'isudev-library' ) )
	)
	: '';

$additional = $show_additional && '' !== $additional_text
	? \sprintf( '<p class="read-more-additional-text">%s</p>', sanitize_highlight( $additional_text ) )
	: '';

$arrow = \sprintf(
	'<span class="read-more-arrow" aria-hidden="true">%s</span>',
	icon( 'arrowForward', 24, 'read-more-arrow__icon' )
);

$inner = \sprintf(
	'<div class="read-more-inner">%1$s<div class="read-more-content">%2$s<%3$s class="read-more-title">%4$s</%3$s>%5$s</div>%6$s</div>',
	$figure,
	$badge,
	$title_tag,
	\esc_html( $title ),
	$additional,
	$arrow
);

$classes = \array_map( '\sanitize_html_class', card_classes( $has_image, $show_badge, $link_type ) );

$wrapper = \get_block_wrapper_attributes(
	array( 'class' => \implode( ' ', $classes ) )
);

/*
 * Rebuild target and rel server-side. The editor normalizes link.rel, but an
 * attribute is author-supplied data and is not evidence of anything.
 */
$target_attr = $opens_in_new_tab ? ' target="_blank"' : '';
$rel_value   = \trim( ( $opens_in_new_tab ? 'noopener noreferrer' : '' ) . ( $is_nofollow ? ' nofollow' : '' ) );
$rel_attr    = '' !== $rel_value ? \sprintf( ' rel="%s"', \esc_attr( $rel_value ) ) : '';

/*
 * Every part above is escaped at its own boundary: esc_html on the title,
 * esc_url on the href, wp_kses on both RichText fields, esc_attr on rel, and
 * the arrow comes from the static icon registry. get_block_wrapper_attributes()
 * escapes its own output.
 */
echo \sprintf( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
	'<a %1$s href="%2$s"%3$s%4$s>%5$s</a>',
	$wrapper,
	\esc_url( $url ),
	$target_attr,
	$rel_attr,
	$inner
);
```

- [ ] **Step 3: Rebuild so `build/blocks/read-more/render.php` matches**

```bash
nvm use
npm run build
diff src/blocks/read-more/render.php build/blocks/read-more/render.php && echo "render.php identical"
```

Expected: `render.php identical`.

- [ ] **Step 4: Verify against a real page**

The dev fixture does not seed this block until Task 7, so verify by hand. Create a draft page through the editor is not available here; instead assert the code paths compile and the block renders nothing without a URL:

```bash
php -l src/blocks/read-more/render.php
php tools/check.php
curl -s -o /dev/null -w "%{http_code}\n" http://isudev-library.local/
```

Expected: no syntax errors, `0 failed` with the same passed count as Task 3, and `200`. Full rendered-output verification is Task 7's job, which is where the fixture seeds real blocks — do not claim the markup is verified before then.

- [ ] **Step 5: Lint and commit**

```bash
composer run lint:php
git add src/blocks/read-more build/
git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" commit -m "feat: server render for the read-more card

Composes the pure precedence helpers, resolves the linked post behind an
is_post_publicly_viewable() guard so a draft title cannot leak, and rebuilds
target/rel server-side rather than trusting the stored attribute."
```

---

### Task 5: Editor UI

**Files:**
- Modify: `src/blocks/read-more/edit.js`

**Interfaces:**
- Consumes: the attribute set from Task 2.
- Produces: nothing other tasks import.

- [ ] **Step 1: Replace `edit.js` entirely**

```jsx
/**
 * External dependencies
 */
import { BlockLinkControl } from '@isudev/gutenberg/controls/BlockLinkControl';
import { LinkPickerControl } from '@isudev/gutenberg/controls/LinkPickerControl';
import { MediaSidebarControl } from '@isudev/gutenberg/controls/MediaSidebarControl';
import { MediaSourceControl } from '@isudev/gutenberg/controls/MediaSourceControl';

/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	Placeholder,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from './icon';

const classNames = (...classes) => classes.filter(Boolean).join(' ');
const HEADING_LEVELS = [2, 3, 4, 5, 6];

export default function Edit({ attributes, setAttributes }) {
	const {
		link = {},
		media = {},
		focalPoint,
		hasCustomTitle = false,
		customTitle = '',
		showAdditionalText = false,
		additionalText = '',
		renderAsHeading = true,
		headingLevel = 3,
		readMoreText = '',
		showReadMoreBadge = false,
		showFeaturedImage = true,
	} = attributes;

	// Mirror render.php: only a post-type link resolves to an entity record.
	const linkedPost = useSelect(
		(select) => {
			if (link?.kind !== 'post-type' || !link?.id || !link?.type) {
				return null;
			}
			return select(coreStore).getEntityRecord(
				'postType',
				link.type,
				link.id
			);
		},
		[link?.kind, link?.id, link?.type]
	);

	const postTitle = decodeEntities(
		typeof linkedPost?.title === 'string'
			? linkedPost.title
			: linkedPost?.title?.rendered || ''
	);

	const hasOwnMedia = Boolean(media?.id || media?.url);
	const thumbnailId = linkedPost?.featured_media || 0;

	// The fallback image is only fetched when the author has not chosen one.
	const thumbnail = useSelect(
		(select) => {
			if (!showFeaturedImage || hasOwnMedia || !thumbnailId) {
				return null;
			}
			return select(coreStore).getMedia(thumbnailId);
		},
		[showFeaturedImage, hasOwnMedia, thumbnailId]
	);

	const imageUrl =
		media?.url ||
		thumbnail?.media_details?.sizes?.medium?.source_url ||
		thumbnail?.source_url ||
		'';
	const imageAlt = media?.alt || thumbnail?.alt_text || '';

	const title =
		(hasCustomTitle && customTitle.trim() !== '' && customTitle) ||
		postTitle ||
		link?.title ||
		link?.url ||
		'';

	const level = HEADING_LEVELS.includes(Number(headingLevel))
		? Number(headingLevel)
		: 3;
	const TitleTag = renderAsHeading ? `h${level}` : 'div';

	const enableCustomTitle = useCallback(() => {
		setAttributes({
			hasCustomTitle: true,
			customTitle: customTitle || title,
		});
	}, [customTitle, title, setAttributes]);

	const setCustomTitleEnabled = useCallback(
		(value) => {
			setAttributes({
				hasCustomTitle: value,
				...(value && !customTitle ? { customTitle: title } : {}),
			});
		},
		[customTitle, title, setAttributes]
	);

	const blockProps = useBlockProps({
		className: classNames(
			'wp-block-isudev-read-more',
			showFeaturedImage && imageUrl && 'has-image',
			showReadMoreBadge && 'has-read-more-badge',
			link?.type && `is-link-type-${link.type}`
		),
	});

	if (!link?.url) {
		return (
			<div {...blockProps}>
				<LinkPickerControl
					value={link}
					onChange={(next) => setAttributes({ link: next })}
				>
					{({ anchorRef, open }) => (
						<div ref={anchorRef}>
							<Placeholder
								icon={icon}
								label={__('Read More', 'isudev-library')}
								instructions={__(
									'Choose where this card should link to.',
									'isudev-library'
								)}
							>
								<Button variant="primary" onClick={open}>
									{__('Pick link', 'isudev-library')}
								</Button>
							</Placeholder>
						</div>
					)}
				</LinkPickerControl>
			</div>
		);
	}

	return (
		<>
			<BlockLinkControl
				value={link}
				onChange={(next) => setAttributes({ link: next })}
				showUnlinkButton
				addLabel={__('Add link', 'isudev-library')}
				editLabel={__('Edit link', 'isudev-library')}
				unlinkLabel={__('Remove link', 'isudev-library')}
			/>

			<InspectorControls>
				<PanelBody
					title={__('Read More settings', 'isudev-library')}
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Show image', 'isudev-library')}
						checked={showFeaturedImage}
						onChange={(value) =>
							setAttributes({ showFeaturedImage: value })
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Read more badge', 'isudev-library')}
						help={__(
							'Show a small label above the title.',
							'isudev-library'
						)}
						checked={showReadMoreBadge}
						onChange={(value) =>
							setAttributes({ showReadMoreBadge: value })
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Use custom title', 'isudev-library')}
						help={__(
							'Otherwise the linked page title is used.',
							'isudev-library'
						)}
						checked={hasCustomTitle}
						onChange={setCustomTitleEnabled}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Additional text', 'isudev-library')}
						help={__(
							'Show supporting text below the title.',
							'isudev-library'
						)}
						checked={showAdditionalText}
						onChange={(value) =>
							setAttributes({ showAdditionalText: value })
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Render as heading', 'isudev-library')}
						checked={renderAsHeading}
						onChange={(value) =>
							setAttributes({ renderAsHeading: value })
						}
					/>
					{renderAsHeading && (
						<SelectControl
							__nextHasNoMarginBottom
							label={__('Heading level', 'isudev-library')}
							value={level}
							options={HEADING_LEVELS.map((each) => ({
								label: `H${each}`,
								value: each,
							}))}
							onChange={(value) =>
								setAttributes({ headingLevel: Number(value) })
							}
						/>
					)}
				</PanelBody>
			</InspectorControls>

			{showFeaturedImage && (
				<MediaSidebarControl
					value={media}
					onChange={(next) => setAttributes({ media: next })}
					onRemove={() => setAttributes({ media: {} })}
					focalPoint={focalPoint}
					onFocalPointChange={(next) =>
						setAttributes({ focalPoint: next })
					}
					preview="focal-point"
					title={__('Card image', 'isudev-library')}
					sources={{ featured: false }}
					featuredMedia={null}
					selectLabel={__('Select image', 'isudev-library')}
					replaceLabel={__('Replace image', 'isudev-library')}
					removeLabel={__('Remove image', 'isudev-library')}
				/>
			)}

			<div {...blockProps}>
				<div className="read-more-inner">
					{showFeaturedImage && (
						<figure
							className={classNames(
								'read-more-image',
								!imageUrl && 'no-image'
							)}
						>
							<div className="read-more-image-controls">
								<MediaSourceControl
									value={media}
									onChange={(next) =>
										setAttributes({ media: next })
									}
									onRemove={() => setAttributes({ media: {} })}
									variant="buttons"
									sources={{ featured: false }}
									featuredMedia={null}
									labels={{
										select: __(
											'Select image',
											'isudev-library'
										),
										replace: __(
											'Replace image',
											'isudev-library'
										),
										remove: __(
											'Remove image',
											'isudev-library'
										),
									}}
								/>
							</div>
							{imageUrl ? (
								<img src={imageUrl} alt={imageAlt} />
							) : null}
						</figure>
					)}

					<div className="read-more-content">
						{showReadMoreBadge && (
							<RichText
								tagName="span"
								className="wp-block-button__read_more"
								role="presentation"
								allowedFormats={['core/text-color']}
								withoutInteractiveFormatting
								placeholder={__('Read more', 'isudev-library')}
								value={readMoreText}
								onChange={(value) =>
									setAttributes({ readMoreText: value })
								}
							/>
						)}

						{hasCustomTitle ? (
							<RichText
								tagName={TitleTag}
								className="read-more-title"
								allowedFormats={[]}
								withoutInteractiveFormatting
								placeholder={__(
									'Custom title',
									'isudev-library'
								)}
								value={customTitle}
								onChange={(value) =>
									setAttributes({ customTitle: value })
								}
							/>
						) : (
							<TitleTag className="read-more-title">
								<button
									type="button"
									className="read-more-title__edit-button"
									onClick={enableCustomTitle}
								>
									{title ||
										__('Untitled', 'isudev-library')}
								</button>
							</TitleTag>
						)}

						{showAdditionalText && (
							<RichText
								tagName="p"
								className="read-more-additional-text"
								allowedFormats={['core/text-color']}
								withoutInteractiveFormatting
								placeholder={__(
									'Additional text',
									'isudev-library'
								)}
								value={additionalText}
								onChange={(value) =>
									setAttributes({ additionalText: value })
								}
							/>
						)}
					</div>
				</div>
			</div>
		</>
	);
}
```

Notes the implementer must not "fix":

- `BlockLinkControl` renders its own `BlockControls` fill. Do **not** wrap it.
- `featuredMedia={null}` and `sources={{ featured: false }}` are load-bearing on both media controls: their `featured` source resolves the featured image of *the post being edited*, which is never what this block means.
- The arrow is intentionally absent from the editor preview. It is decorative, server-rendered from the PHP icon registry, and duplicating it in JS would mean a second copy of the glyph to keep in sync.

- [ ] **Step 2: Build and lint**

```bash
nvm use
npm run build
npm run lint:js
```

Expected: build succeeds, eslint clean. If prettier complains about formatting, run `npm run lint:js:fix` and re-run, then note in the report exactly which lines it rewrote.

- [ ] **Step 3: Verify in the real editor**

Log in as the e2e user (credentials at `tools/.e2e-credentials.json`) and open `/wp-admin/post-new.php`. Insert the "Read More Block". Then, capturing browser console and page errors:

1. The placeholder shows with a **Pick link** button.
2. Clicking it opens the link popover.
3. Picking an internal page fills the card: the title becomes that page's title, and the toolbar shows the link edit button.
4. The sidebar shows a **Card image** panel.
5. Zero page errors and zero console errors.

Do this with a short Playwright script rather than by claiming it — paste the captured `pageErrors` and `consoleErrors` arrays verbatim. If either is non-empty, report it; do not filter entries out.

- [ ] **Step 4: Commit**

```bash
git add src/blocks/read-more/edit.js build/
git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" commit -m "feat: read-more editor UI on the shared link and media controls

Placeholder triggers the link picker, BlockLinkControl owns the toolbar, and
media composes MediaSourceControl in the figure with MediaSidebarControl for
focal point. The composite MediaControl is not used: its canvas owns the empty
state, and this block's empty state falls back to the linked post's thumbnail."
```

---

### Task 6: Styles

Port the source plugin's CSS, dropping the `t2` coupling.

**Files:**
- Modify: `src/blocks/read-more/style.scss`
- Modify: `src/blocks/read-more/editor.scss`

**Interfaces:** none.

- [ ] **Step 1: Write `style.scss`**

```scss
/* Read More Block — a linked card: image, badge, title, supporting text, arrow.
   Ported from the standalone isudev-read-more plugin, with the t2 layout
   classes removed. */

.wp-block-isudev-read-more {
	color: inherit;
	display: block;
	text-decoration: none;
	transition: transform 0.2s cubic-bezier(0.25, 0, 0, 1);

	& mark {
		background-color: unset;
	}

	&:hover {
		transform: translateY(-2px);
	}

	&::after {
		content: unset;
	}

	&:focus-visible {
		outline: 2px solid currentcolor;
		outline-offset: 2px;
	}

	.read-more-inner {
		align-items: stretch;
		display: flex;
		flex-direction: row;
		gap: var(--wp--preset--spacing--30, 24px);
	}

	.read-more-image {
		aspect-ratio: 159 / 119;
		border-radius: 2px;
		margin: 0;
		position: relative;
		width: 163px;

		img {
			display: block;
			height: 100%;
			object-fit: cover;
			width: 100%;
		}
	}

	.read-more-content {
		display: flex;
		flex: 1 1 auto;
		flex-direction: column;
		gap: 1.5rem;
		justify-content: center;
		padding: 5px;
	}

	.read-more-arrow {
		align-self: center;
		display: block;
		flex: 0 0 24px;
		height: 24px;
		margin-inline-start: auto;
		transition: transform 0.3s cubic-bezier(0.25, 0, 0, 1);
		width: 24px;
	}

	.read-more-arrow__icon {
		display: block;
		height: 100%;
		width: 100%;
	}

	&:hover .read-more-arrow {
		transform: translateX(4px);
	}

	.wp-block-button__read_more {
		color: color-mix(in srgb, currentcolor 95%, transparent);
		font-size: 14px;
	}

	.read-more-title {
		color: inherit;
		font-size: var(--wp--preset--font-size--large, 20px);
		font-weight: 400;
		margin: 0;
	}

	.read-more-additional-text {
		color: inherit;
		font-size: 14px;
		font-weight: 400;
		margin: 0;
	}
}

@media screen and (max-width: 768px) {

	.wp-block-isudev-read-more {

		.wp-block-button__read_more {
			font-size: 12px;
		}

		.read-more-title {
			-webkit-box-orient: vertical;
			display: -webkit-box;
			font-size: 16px;
			-webkit-line-clamp: 2;
			max-height: 2.8em;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.read-more-image {
			aspect-ratio: 1 / 1;
			width: 82px;
		}
	}
}

@media screen and (prefers-reduced-motion: reduce) {

	.wp-block-isudev-read-more,
	.wp-block-isudev-read-more .read-more-arrow {
		transition: none;
	}

	.wp-block-isudev-read-more:hover {
		transform: none;
	}
}
```

Three deliberate changes from the source, all of which the implementer must keep:

1. `outline: 2px solid var(currentColor)` becomes `outline: 2px solid currentcolor`. `var()` around a keyword never resolves, so the source had **no** visible focus outline — an accessibility bug, not a style preference.
2. The `!important` on `transition` and on `::after { content: unset }` is dropped. Both existed to out-shout `t2` theme styles that are not present here.
3. The reduced-motion block now also disables the card's own `translateY` hover transform, which the source left running.

- [ ] **Step 2: Write `editor.scss`**

```scss
/* Read More Block — editor-only affordances: the image overlay controls and
   the click-to-edit title button. */

.wp-block-isudev-read-more {

	.read-more-image-controls {
		display: flex;
		gap: 0.25rem;
		inset-block-start: 0.5rem;
		inset-inline-end: 0.5rem;
		position: absolute;
		z-index: 1;
	}

	.read-more-title__edit-button {
		appearance: none;
		background: transparent;
		border: 0;
		color: inherit;
		cursor: text;
		font: inherit;
		margin: 0;
		padding: 0;
		text-align: inherit;

		&:focus-visible {
			outline: 2px solid currentcolor;
			outline-offset: 2px;
		}
	}
}
```

The source's `--wp--custom--color--primary-80` is a `t2` theme token and resolves to nothing here; `currentcolor` always resolves. The overlay button styling from the source is dropped entirely — those rules styled hand-rolled `components-button` markup, and the buttons now come from `MediaSourceControl`, which brings its own.

- [ ] **Step 3: Build, lint and verify the CSS is applied**

```bash
nvm use
npm run build
npm run lint:css
ls -l build/blocks/read-more/style-index.css build/blocks/read-more/index.css
```

Expected: stylelint clean, both files non-trivial in size. `editor.scss` compiles into `index.css` and `style.scss` into `style-index.css` — `wp-scripts` forces the `style-` prefix on any file named `style.*`, which is why `block.json` points `style` at `style-index.css` and `editorStyle` at `index.css`. Getting this backwards silently ships an unstyled block.

- [ ] **Step 4: Commit**

```bash
git add src/blocks/read-more/style.scss src/blocks/read-more/editor.scss build/
git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" commit -m "style: port the read-more card styles off t2

Fixes the source's focus outline, which used var() around a keyword and so
never resolved, and extends the reduced-motion guard to the hover transform."
```

---

### Task 7: Dev fixture and end-to-end coverage

**Files:**
- Modify: `tools/mu-plugins/isudev-library-dev-fixture.php`
- Create: `e2e/read-more.spec.js`
- Modify: `e2e/panel.spec.js`

**Interfaces:** none consumed by later tasks.

- [ ] **Step 1: Extend the fixture**

In `tools/mu-plugins/isudev-library-dev-fixture.php`, inside the seeding closure, change `$seed_version = 1;` to `$seed_version = 2;` so the fixture reseeds.

Immediately before the `$content = '<!-- wp:isudev/site-header ...` line, insert:

```php
		/*
		 * A real published post for the read-more card to link at. The card
		 * resolves its title and thumbnail from this post, so it has to exist
		 * as a genuine, publicly viewable entity — not a bare URL.
		 */
		$existing_target = get_page_by_path( 'isudev-read-more-target', OBJECT, 'post' );
		if ( $existing_target ) {
			wp_delete_post( $existing_target->ID, true );
		}
		$target_id = wp_insert_post(
			array(
				'post_title'   => 'Linked Target Article',
				'post_name'    => 'isudev-read-more-target',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_content' => 'Target of the read-more card fixture.',
			)
		);
```

Then extend `$content` — append these blocks to the existing concatenation, after the `<!-- /wp:isudev/site-header -->` line:

```php
			. '<!-- wp:isudev/read-more ' . wp_json_encode(
				array(
					'link' => array(
						'url'   => get_permalink( $target_id ),
						'id'    => $target_id,
						'kind'  => 'post-type',
						'type'  => 'post',
						'title' => 'Ignored — the post title wins',
					),
				)
			) . ' /-->'
			. '<!-- wp:isudev/read-more ' . wp_json_encode(
				array(
					'link'  => array(
						'url'           => 'https://example.com/external',
						'title'         => 'External Resource',
						'opensInNewTab' => true,
						'nofollow'      => true,
					),
					'media' => array(
						'url'    => '/wp-includes/images/media/default.png',
						'alt'    => 'Placeholder artwork',
						'source' => 'url',
						'type'   => 'image',
					),
					'showAdditionalText' => true,
					'additionalText'     => 'Supporting copy.',
					'showReadMoreBadge'  => true,
				)
			) . ' /-->';
```

Two things to be careful about: the self-closing `/-->` form is required because the block has no inner content, and the existing `$content` assignment ends with `;` — move that semicolon to the end of the new final line.

The external card deliberately uses a **direct media URL** rather than an attachment. Seeding an attachment would mean writing a file into `uploads/`, which the fixture has no business doing; the URL path exercises `pick_image()`'s second branch, which nothing else covers.

- [ ] **Step 2: Reseed and confirm the fixture took**

```bash
curl -s http://isudev-library.local/ -o /tmp/front.html
grep -c "wp-block-isudev-read-more" /tmp/front.html
```

Expected: `2`. If it is `0`, the seed version did not bump or the block is disabled in the panel — check both before changing any code.

- [ ] **Step 3: Write `e2e/read-more.spec.js`**

```js
/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

const CARD = '.wp-block-isudev-read-more';

test.describe('read-more card', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('./');
	});

	test('an internal card takes its title from the linked post', async ({
		page,
	}) => {
		const card = page.locator(CARD).first();

		await expect(card).toHaveAttribute('href', /isudev-read-more-target/);
		await expect(card.locator('.read-more-title')).toHaveText(
			'Linked Target Article'
		);
	});

	test('the link label loses to the linked post title', async ({ page }) => {
		// The fixture sets link.title to a sentinel that must never surface.
		await expect(page.locator(CARD).first()).not.toContainText(
			'Ignored — the post title wins'
		);
	});

	test('an external card opens in a new tab with a safe rel', async ({
		page,
	}) => {
		const card = page.locator(CARD).nth(1);

		await expect(card).toHaveAttribute('target', '_blank');

		const rel = await card.getAttribute('rel');
		expect(rel).toContain('noopener');
		expect(rel).toContain('noreferrer');
		expect(rel).toContain('nofollow');
	});

	test('an external card falls back to its own title and renders its parts', async ({
		page,
	}) => {
		const card = page.locator(CARD).nth(1);

		await expect(card.locator('.read-more-title')).toHaveText(
			'External Resource'
		);
		await expect(card.locator('.wp-block-button__read_more')).toBeVisible();
		await expect(card.locator('.read-more-additional-text')).toHaveText(
			'Supporting copy.'
		);
	});

	test('a URL-only image renders with its alt text', async ({ page }) => {
		const image = page.locator(CARD).nth(1).locator('.read-more-image img');

		await expect(image).toHaveAttribute('alt', 'Placeholder artwork');
		await expect(image).toHaveAttribute('src', /default\.png/);
	});

	test('the arrow is decorative and hidden from assistive tech', async ({
		page,
	}) => {
		const arrow = page.locator(CARD).first().locator('.read-more-arrow');

		await expect(arrow).toHaveAttribute('aria-hidden', 'true');
		await expect(arrow.locator('svg')).toHaveAttribute(
			'focusable',
			'false'
		);
	});

	test('the whole card is one link and nothing inside it is focusable', async ({
		page,
	}) => {
		const card = page.locator(CARD).first();

		await expect(card).toHaveJSProperty('tagName', 'A');
		expect(
			await card.locator('a, button, input, select, textarea').count()
		).toBe(0);
	});

	test('has no axe violations', async ({ page }) => {
		const { violations } = await new AxeBuilder({ page })
			.include(CARD)
			.analyze();

		expect(violations).toEqual([]);
	});
});
```

- [ ] **Step 4: Extend the panel inserter test to cover both blocks**

In `e2e/panel.spec.js`, in the test named `site-header is discoverable in the block inserter`, after the existing `await expect(page.locator(SITE_HEADER_OPTION)).toBeVisible();` add:

```js
		// Two blocks in the library is the first time anything proves the
		// registry handles more than one descriptor.
		await page.getByRole('searchbox', { name: /Search/ }).fill('Read More');
		await expect(
			page.locator('.editor-block-list-item-isudev-read-more')
		).toBeVisible();
```

- [ ] **Step 5: Run the suite**

```bash
nvm use
npm run test:e2e
```

Expected: `46 passed`, `20 skipped`, `0 failed` — the eight new specs run in both viewport projects (16 runs), of which none are viewport-gated, added to the previous 30. Report the exact numbers; if they differ, explain why before changing an assertion.

Run it a second time and confirm identical numbers. A suite that passes once is not a suite that passes.

- [ ] **Step 6: Prove the axe check is not vacuous**

Temporarily change `render_image()` in `src/blocks/read-more/inc/render-helpers.php` so the URL branch emits no `alt` attribute at all, rebuild, and re-run only the read-more spec:

```bash
npm run build
npx playwright test e2e/read-more.spec.js --project=desktop-chromium
```

Expected: the alt-text spec fails, and axe reports an `image-alt` violation. Restore the code, rebuild, re-run, confirm green, and paste both outputs. Without this the axe assertion could be passing on markup it never actually examined.

- [ ] **Step 7: Commit**

```bash
git add tools/mu-plugins/isudev-library-dev-fixture.php e2e/read-more.spec.js e2e/panel.spec.js src/blocks/read-more build/
git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" commit -m "test: seed and cover the read-more card end to end

The fixture seeds an internal card pointing at a real published post and an
external card with a direct-URL image, covering both title precedence branches
and pick_image()'s URL branch, plus an axe pass proven non-vacuous by removing
the alt attribute and watching it fail."
```

---

### Task 8: Documentation, translations and release hygiene

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `AGENTS.md`
- Modify: `languages/isudev-library.pot`

**Interfaces:** none.

- [ ] **Step 1: Add the block to the README table**

In `README.md`, add a row to the Blocks table beneath the `isudev/site-header` row:

```markdown
| `isudev/read-more` | A linked card: title, optional image, badge and supporting text. Links to a post, a page or an external URL. |
```

- [ ] **Step 2: Add the filter rows**

In `README.md`'s Filters table, after the `isudev_library/icon` row:

```markdown
| `isudev_library/icon_view_boxes` | Per-icon `viewBox` overrides, for glyphs not authored on the default `0 0 600 600` grid. |
```

- [ ] **Step 3: Update the CHANGELOG**

Insert a new section above the `## 1.0.0` heading:

```markdown
## 1.1.0 — 2026-08-10

### Added

- Block `isudev/read-more`: a linked card with a title, optional image, optional
  badge and supporting text. Links anywhere — a post, a page or an external URL.
  Migrated from the standalone `isudev-read-more` plugin, with the `t2`
  dependency removed.
- `@isudev/gutenberg` supplies the link picker and the media controls. It is the
  library's first runtime npm dependency.
- Per-icon `viewBox` support in the icon registry, plus an `arrowForward` glyph
  and the `isudev_library/icon_view_boxes` filter.

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
```

Then bump `Version:` in `isudev-library.php` to `1.1.0`, the `VERSION` constant to `'1.1.0'`, and `version` in `package.json` to `1.1.0`. Leave each block's own `block.json` `version` at `1.0.0` — those track the block, not the plugin.

- [ ] **Step 4: Update AGENTS.md**

In the "Layout" section, the `src/blocks/<slug>/` line already generalises. Add one line to "Golden rules":

```markdown
- **Editor components come from `@isudev/gutenberg`.** Link and media UI use that
  package, not hand-rolled controls. It ships no PHP and no CSS: attribute
  shapes reach `render.php` as plain arrays and all styling is ours.
```

- [ ] **Step 5: Regenerate translations**

```bash
npm run i18n:make-pot
grep -c "^#: build/" languages/isudev-library.pot
grep -c "read-more" languages/isudev-library.pot
```

Expected: the `build/` count is non-zero (it must stay so — excluding `build` here is what previously made every JS string untranslatable), and `read-more` appears. Paste both numbers.

- [ ] **Step 6: Full verification sweep**

```bash
nvm use
npm run build
php tools/check.php
composer run lint:php
npm run lint:js
npm run lint:css
npm run test:e2e
curl -s -o /dev/null -w "home=%{http_code}\n" http://isudev-library.local/
```

Expected: `0 failed` with the same passed count Task 3 reported, all linters clean, `46 passed` / `20 skipped` / `0 failed`, home `200`. Report every figure verbatim.

Then re-run the distribution simulation, which is the check that caught the v1 critical:

```bash
DIST=$(mktemp -d)
grep -v '^\s*#' .distignore | grep -v '^\s*$' > /tmp/dist-excl.txt
rsync -a --exclude-from=/tmp/dist-excl.txt ./ "$DIST/"
ls "$DIST"/src/blocks/*/block.php | wc -l
ls "$DIST"/src/blocks/read-more/inc/*.php | wc -l
rm -rf "$DIST"
```

Expected: `2` descriptors and `1` bootstrap file for read-more.

- [ ] **Step 7: Commit**

```bash
git add README.md CHANGELOG.md AGENTS.md languages/ isudev-library.php package.json package-lock.json build/
git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" commit -m "docs: document the read-more card and release 1.1.0"
```

---

## Self-Review

**Spec coverage.** Every section maps to a task: §2 rewiring → Tasks 2/4/5; §2.1 losses → recorded in Task 8's CHANGELOG; §3 attributes → Task 2 Step 3; §4 precedence → Tasks 3 and 4; §4.1 editor parity → Task 5 Step 1's `useSelect` blocks; §5 surfaces → Task 5; §6 icon registry → Task 1; §7 markup → Task 4; §7.1 escaping → Task 4 Step 2; §8 accessibility → Task 6 (focus outline, reduced motion) and Task 7 (axe, arrow, single-anchor specs); §9 files → Tasks 2 and 3; §10 dependencies → Task 2 Step 1; §11 testing → Tasks 1, 3, 7; §12 out of scope → not implemented anywhere, correctly.

**Type consistency.** `pick_title`, `pick_image`, `card_classes`, `heading_tag` are declared once in Task 3 and called with matching arity in Task 4. `pick_image()`'s return shape is asserted in Task 3's checks and consumed by `render_image()` in Task 4 using the same four keys. `build_svg()`'s fourth parameter is added in Task 1 and used by `icon()` in the same task; no other caller passes it.

**Known risk, flagged rather than hidden.** Tasks 2 and 5 are written against `@isudev/gutenberg`'s documented API as read from its source, not against a running build. If a prop name or export path differs, Task 2 Step 12 is where it surfaces — that step exists specifically to fail early and cheaply. An implementer hitting a mismatch should report the actual signature and stop, not improvise a workaround.
