# Working with icons

How icons work in `isudev-library`, which system to reach for, and how to add one
without breaking frontend rendering, the editor collection or accessibility.

## The three icon systems (do not mix them up)

| System | Lives in | Used for | Output |
| --- | --- | --- | --- |
| **Shared icon registry** | [includes/utils/icon.php](../includes/utils/icon.php) | Frontend block markup and the editor's localized collection | Inline `<svg>` or `<img>` in PHP; `IconDefinition[]` in the editor |
| **Block inserter icon** | `block.json` `"icon"` + optional `icon.js` | How a block looks in the inserter, list view and admin panel | React element or Dashicon slug |
| **Editor UI icons** | `@isudev/gutenberg` (`Icon`, `IconPicker`, `IconSelect`) | Rendering and selecting icons in block controls | React components consuming the shared collection |

Blocks still render on the frontend in PHP. The editor receives the same PHP
registry once for the whole editor, but it never becomes the frontend renderer.

---

## 1. Shared registry — `includes/utils/icon.php`

The registry is a map of names to definitions compatible with
`IconDefinition` from `@isudev/gutenberg`:

```php
'arrowForward' => array(
	'label'    => __( 'Arrow forward', 'isudev-library' ),
	'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="…"/></svg>',
	'keywords' => array( 'arrow', 'right', 'next' ),
),
```

Every definition requires a non-empty `icon`. `label` falls back to the name,
`keywords` is optional, and unknown keys pass through normalization untouched so
future metadata such as `category` needs no registry rewrite.

`icon` is either a complete serialized `<svg>` or an image URL. A registered
asset must therefore use a public URL such as
`\IsuDevLibrary\URL . 'assets/icons/example.svg'`, never `PATH` or another
filesystem path.

### Public API

```php
use function IsuDevLibrary\Utils\get_icon;
use function IsuDevLibrary\Utils\the_icon;

$markup = get_icon(
	'chevronDown',
	array(
		'size'  => 20,
		'class' => 'isudev-nav__chevron-icon',
	)
);

the_icon( 'close', array( 'class' => 'isudev-header__close-icon' ) );
```

`get_icon()` returns markup and `the_icon()` echoes it. An unknown name returns
an empty string. Both accept the same argument array:

| Argument | Type | Default | Behaviour |
| --- | --- | --- | --- |
| `size` | `int\|array{int,int}` | `24` | One integer sets both dimensions; `[ width, height ]` sets them separately. |
| `class` | `string` | `''` | Class on the root `<svg>` or `<img>`. |
| `attrs` | `array` | `array()` | Extra root attributes. They override defaults; `null` or `false` removes a default. |

Inline SVG defaults are `fill="currentColor"`, `aria-hidden="true"` and
`focusable="false"`. Image defaults are `alt=""` and `decoding="async"`; icons
do not get `loading="lazy"` because they are small and often above the fold.

Attribute names must be lowercase HTML-style names and may contain hyphens.
Event-handler names (`on*`), camelCase names, arrays and objects are rejected;
scalar values are escaped at serialization time.

A rejected name is also left alone in the stored markup, so `attrs => array( 'viewBox' => … )`
is ignored rather than destructive. Changing a glyph's coordinate system means
registering a different icon, not overriding its root at render time.

### Current registry

| Name | viewBox | Used by |
| --- | --- | --- |
| `chevronDown` | `0 0 600 600` | Site-header submenu disclosure toggle |
| `burger` | `0 0 600 600` | Available to integrators; the header burger itself is CSS spans |
| `close` | `0 0 600 600` | Site-header mobile drawer close button |
| `arrowForward` | `0 0 24 24` | Read-more card arrow |
| `socialFacebook` | `0 0 24 24` | `isudev/social-share-network` |
| `socialX` | `0 0 24 24` | `isudev/social-share-network` |
| `socialLinkedin` | `0 0 24 24` | `isudev/social-share-network` |
| `socialWhatsapp` | `0 0 24 24` | `isudev/social-share-network` |
| `socialBluesky` | `0 0 24 24` | `isudev/social-share-network` |
| `socialThreads` | `0 0 24 24` | `isudev/social-share-network` |
| `socialMastodon` | `0 0 24 24` | `isudev/social-share-network` |
| `socialSubstack` | `0 0 16 16` | `isudev/social-share-network` |
| `email` | `0 0 24 24` | `isudev/social-share-network`; available to integrators |
| `link` | `0 0 20 20` | `isudev/social-share-network`; available to integrators |
| `print` | `0 0 24 24` | `isudev/social-share-network`; available to integrators |
| `share` | `0 0 24 24` | `isudev/social-share-network`; available to integrators |

The `viewBox` belongs inside each stored SVG. There is no detached viewBox map
and no special default grid at render time.

---

## 2. Adding an icon to the registry

1. Add a `lowerCamelCase` entry to `default_icons()` with a translated `label`,
   a complete `icon`, and useful search `keywords`.
2. For SVG, keep the `viewBox` and `fill="currentColor"` on the root. Do not put
   `width` or `height` in stored markup; rendering injects them per call.
3. Do not put `fill` on an inner `<path>` unless the artwork genuinely needs a
   fixed colour. A path-level fill overrides the root's `currentColor` and stops
   normal CSS tinting.
4. For an asset file, store a public URL (`URL . 'assets/…'`). A filesystem path
   cannot become a browser image source.
5. Extend [tools/checks/50-icon.php](../tools/checks/50-icon.php), bump the strict
   default-count assertion, and run `npm run test:php`.
6. Consume the name with `get_icon()` or `the_icon()`. If a block's
   `render.php` changes, run `npm run build` so the committed `build/` copy stays
   current.

Registry markup and filtered definitions are trusted static configuration.
Never route post meta, request data or other author-controlled HTML into `icon`.

---

## 3. Styling icons

The `class` argument lands on the rendered `<svg>` or `<img>`. The plugin ships
no global icon stylesheet, so the owning block controls presentation.

```scss
.isudev-nav__chevron-icon {
	height: 1.25rem;
	width: 1.25rem;
}
```

```scss
.read-more-arrow {
	flex: 0 0 24px;
	height: 24px;
	width: 24px;
	transition: transform 0.3s cubic-bezier(0.25, 0, 0, 1);
}

.read-more-arrow__icon {
	display: block;
	height: 100%;
	width: 100%;
}
```

- CSS width and height override the HTML attributes; still pass a sensible
  `size` to avoid layout shifts before styles apply.
- Colour for built-in SVGs comes from `currentColor`, so set `color` on the
  surrounding button or link.
- Animate a stable wrapper where possible, and preserve the existing
  `prefers-reduced-motion` behaviour when adding motion.
- Image URL icons do not inherit `currentColor`; supply an asset with the
  intended colour or use a complete inline SVG.

---

## 4. Accessibility contract

Icons are decorative by default. Put the accessible name on the surrounding
control:

```php
$arrow = sprintf(
	'<span class="read-more-arrow" aria-hidden="true">%s</span>',
	get_icon( 'arrowForward', array( 'class' => 'read-more-arrow__icon' ) )
);
```

When the icon itself must carry a label, use `attrs` to remove decorative
defaults and provide an accessible name:

```php
get_icon(
	'alert',
	array(
		'attrs' => array(
			'aria-hidden' => null,
			'focusable'   => null,
			'role'        => 'img',
			'aria-label'  => __( 'Warning', 'isudev-library' ),
		),
	)
);
```

For an image URL definition, override `alt` with meaningful text instead. Do
not duplicate a label on both an icon and its already-labelled control. Run the
Playwright + axe suite after changing header icon markup.

---

## 5. Filters for integrators

| Filter | Signature | Purpose |
| --- | --- | --- |
| `isudev_library/icons` | `array $icons` | Add or replace complete icon definitions. |
| `isudev_library/icon` | `string $markup, string $name, array $args` | Rewrite final `<svg>` or `<img>` markup as a last resort. |

```php
add_filter(
	'isudev_library/icons',
	function ( array $icons ): array {
		$icons['externalLink'] = array(
			'label'    => __( 'External link', 'my-project' ),
			'icon'     => \IsuDevLibrary\URL . 'assets/icons/external-link.svg',
			'keywords' => array( 'external', 'open', 'new tab' ),
			'category' => 'navigation',
		);

		return $icons;
	}
);
```

The unknown `category` key survives and reaches the editor. Invalid definitions
or entries with an empty `icon` are dropped. Treat filters as trusted code: the
registry intentionally does not sanitize complete SVG markup.

---

## 6. Block inserter icons (editor-only)

Two layers exist, and the JavaScript one wins in the editor:

- `block.json` `"icon"` is a Dashicon slug. It also reaches the manifest and the
  admin panel's REST payload, including for disabled blocks.
- `icon.js` can export a React SVG passed to `registerBlockType()`. Build it with
  `@wordpress/primitives`, not raw JSX tags:

```jsx
import { SVG, Path } from '@wordpress/primitives';

export default (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path fill="currentColor" d="…" />
	</SVG>
);
```

Inserter artwork describes the block itself. It is not a selectable content
icon and does not belong in the PHP registry unless the frontend also renders it.

---

## 7. Author-selectable icons (`@isudev/gutenberg`)

No block currently exposes an icon choice. When one does, use `Icon`,
`IconPicker` or `IconSelect` from `@isudev/gutenberg` rather than building a
second picker.

The plugin publishes the normalized registry as `isudevIcons` on
`enqueue_block_editor_assets`, **appending** to that global rather than assigning
it — the name is shared with the other `isudev-*` plugins, and the package keeps
the first entry per name when it resolves a collection. A consuming block only
reads it once with `getLocalizedIcons()` and passes the result as `defaultIcons`:

```js
import {
	Icon,
	getLocalizedIcons,
} from '@isudev/gutenberg/components/Icon';

const defaultIcons = getLocalizedIcons();

<Icon
	name={attributes.iconName}
	defaultIcons={defaultIcons}
	label="Feature icon"
/>;
```

Store the selected name in block attributes, never the markup. PHP then renders
that name through `get_icon()`.

Collision to watch for: a co-installed plugin that publishes `isudevIcons` with
`wp_localize_script()` **assigns** the global and discards everything printed
before it, including this registry. `isudev-test-blocks` does exactly that on the
dev install. Anything writing into this global should append, the way
`localize_icons()` does.

Known limitation: the components package percent-encodes serialized SVG and
renders it through `<img>`. An image cannot inherit `currentColor`, so editor
previews can be monochrome even though the inline frontend SVG tints correctly.
That is a components-package concern, not something this plugin should work
around with duplicate icon data.

---

## Checklist

- [ ] Definition has a translated `label`, a complete SVG or public image URL,
      and useful `keywords`.
- [ ] Stored SVG has its own `viewBox` and no `width` or `height`.
- [ ] Inner paths do not override `currentColor` accidentally.
- [ ] `tools/checks/50-icon.php` is extended, the default count is bumped, and
      `npm run test:php` is green.
- [ ] Frontend uses `get_icon()` or `the_icon()`; author-selected attributes
      store only the icon name.
- [ ] Decorative icons keep their defaults; labelled icons remove
      `aria-hidden` with `null` and receive an accessible name.
- [ ] Classes are styled in the owning block; image URL icons do not assume
      `currentColor` support.
- [ ] `npm run build` runs after a `render.php` change and `npm run test:e2e`
      runs after header/editor integration changes.
