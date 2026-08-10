# Design — icon registry aligned with `@isudev/gutenberg`

Date: 2026-08-11
Status: approved
Supersedes: §6 of `2026-08-10-isudev-read-more-design.md` (the viewBox override map).
Breaking: yes. Nobody runs this plugin yet, so no deprecation shims.

## 1. What this is

`includes/utils/icon.php` is a port from `t2` and speaks its own grammar: a slug ⇒
**path fragment** map plus a second slug ⇒ **viewBox** map, wrapped at render time
by `build_svg()`. `@isudev/gutenberg` — the library's editor UI dependency, and
the home of `Icon`, `IconPicker` and `IconSelect` — speaks `IconDefinition`:
`name`, optional `label`, `icon`, optional `keywords`, where `icon` is a
**complete serialized `<svg>`**, an image URL or a Dashicon name. Never a path
fragment, never a detached viewBox.

The two formats are not translatable into each other, so the registry cannot
currently back a picker. This redesign makes the PHP registry the single source of
truth for both frontend rendering and the editor collection.

## 2. What changes

| Now | After | Why |
| --- | --- | --- |
| `default_icon_paths()` — slug ⇒ path fragment | `default_icons()` — name ⇒ `{ label, icon, keywords }` | The shape `@isudev/gutenberg` consumes. |
| `default_icon_view_boxes()` + `isudev_library/icon_view_boxes` | *gone* | A complete `<svg>` carries its own viewBox. A detached map is a rescaling footgun. |
| `DEFAULT_VIEW_BOX`, `build_svg()` | `apply_root_attrs()` | We no longer build an `<svg>`; we override attributes on one we already have. |
| `icon( $slug, $size, $class )` | `get_icon( $name, $args )` + `the_icon( $name, $args )` | Every option past the name is an argument array, so `focusable`, `role` etc. are reachable without touching the signature. WP convention: `get_` returns, `the_` echoes. |
| SVG only | SVG **or** image URL | Mirrors the package: `<svg …` is inlined, anything else is treated as a URL and rendered as `<img>`. |
| No editor exposure | `localize_icons()`, injected once for the whole block editor | `getLocalizedIcons()` works in every block with no per-block wiring. |
| "Pure functions must not call WordPress" | "Utils must be testable under `tools/check.php`" | The plugin will never run outside WordPress. Contorting `icon.php` to avoid `__()` and `esc_attr()` buys nothing; the test runner gets shims instead. |

## 3. Registry

One map in `default_icons()`, keyed by name. Names stay `lowerCamelCase`
(`chevronDown`, `arrowForward`) — `name` is an opaque string to the picker, which
displays `label`, so a rename to kebab would be churn without a payoff.

```php
function default_icons(): array {
	return array(
		'chevronDown' => array(
			'label'    => __( 'Chevron down', 'isudev-library' ),
			'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" fill="currentColor"><path d="…"/></svg>',
			'keywords' => array( 'arrow', 'down', 'expand' ),
		),
		// … burger, close, arrowForward
	);
}
```

- Built-in glyphs keep the viewBox they were authored on: `0 0 600 600` for
  `chevronDown`, `burger`, `close`; `0 0 24 24` for `arrowForward`.
- Stored markup carries `fill="currentColor"` and **no** `width`/`height` — those
  are always injected at render time.
- `keywords` is optional and exists for `IconPicker` search.
- `label` uses `__()` with a literal string, so `wp i18n make-pot` picks it up.

`get_icons(): array` returns `default_icons()` through the
`isudev_library/icons` filter, then normalizes: entries whose `icon` is not a
non-empty string are dropped, and a missing `label` falls back to the name
(mirroring the package's `parseLocalizedIcons()`).

**Unknown keys pass through untouched.** When the components package grows icon
categories or tab grouping, a `category` key added by a filter — or by
`default_icons()` — reaches the editor with no change to this file. Normalization
validates `icon` and `label`; it does not allowlist keys.

## 4. Public API

```php
get_icons(): array
get_icon( string $name, array $args = array() ): string
the_icon( string $name, array $args = array() ): void
localize_icons( string $handle, string $object_name = 'isudevIcons' ): void
```

`$args`:

| Key | Type | Default | Behaviour |
| --- | --- | --- | --- |
| `size` | `int｜array{int,int}` | `24` | `int` → both dimensions; `[ $w, $h ]` → separately. |
| `class` | `string` | `''` | Class on the root element. Omitted when empty. |
| `attrs` | `array` | `array()` | Extra root attributes; overrides the defaults below. `null` or `false` **removes** an attribute — that is how a caller drops `aria-hidden` for a labelled icon. |

Root defaults, all overridable through `attrs`: `fill="currentColor"`,
`aria-hidden="true"`, `focusable="false"` (SVG); `alt=""`,
`decoding="async"` (`<img>` — no `loading="lazy"`, icons are small and often
above the fold).

`attrs` hygiene: keys must match `/^[a-z][a-z0-9-]*$/` (so `viewBox` and
`camelCase` are not settable through `attrs`, and neither is anything with
whitespace or quotes), `on*` keys are dropped, values go through `esc_attr()`.

An unknown or empty `name` returns `''`, as today — a typo is silent, which is
what the checks exist for.

`get_icon()` output passes through the `isudev_library/icon` filter, whose
signature becomes `( string $markup, string $name, array $args )`.

Dashicon names are out of scope. The registry will never hold one, so any value
not starting with `<svg` is treated as a URL, full stop.

## 5. Rendering

```php
$markup = ltrim( (string) $definition['icon'] );

if ( str_starts_with( $markup, '<svg' ) ) {
	// Inline: override attributes on the existing root.
	return apply_root_attrs( $markup, $attrs );
}

// Otherwise a URL.
return sprintf( '<img src="%s"%s>', esc_url( $markup ), build_attrs( $attrs ) );
```

Helpers, all plain string work and therefore the interesting things to test:

- `normalize_size( $size ): array` → `[ int $width, int $height ]`.
- `build_attrs( array $attrs ): string` → serialized attribute string, applying
  the hygiene rules from §4.
- `apply_root_attrs( string $svg, array $attrs ): string` → rewrites the first
  `<svg …>` opening tag: strips any existing occurrence of the attributes being
  set, then injects them. `viewBox` in the source survives untouched and is never
  duplicated.

`WP_HTML_Tag_Processor` is deliberately not used: it is a WordPress class, and
`apply_root_attrs()` needs to run under `tools/check.php`.

## 6. Editor injection

One data-only script handle for the whole block editor, not per block:

```php
function boot_icons(): void {
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_icons', 5 );
}

function enqueue_icons(): void {
	wp_register_script( 'isudev-library-icons', false, array(), VERSION );
	wp_enqueue_script( 'isudev-library-icons' );
	localize_icons( 'isudev-library-icons' );
}
```

`boot_icons()` is called from the bootstrap alongside the other `boot()` calls —
no top-level hook registration in `includes/`.

`localize_icons()` converts the keyed map to the list the package expects, with
`name` injected into each entry:

```php
wp_localize_script( $handle, $object_name, array_values( $list ) );
```

The handle has no `src`, so its localized data prints in `<head>`, ahead of every
block editor script. `getLocalizedIcons()` reads `globalThis.isudevIcons` at
module scope in the top frame (editor JS is not iframed — only the canvas DOM
is), so the ordering requirement is met.

Known limitation, to be fixed in the components package rather than here: `Icon`
renders serialized SVG through a percent-encoded `<img>`, never
`dangerouslySetInnerHTML`. An `<img>` cannot inherit `currentColor`, so the
editor preview is monochrome while the frontend tints correctly.

## 7. Migration

Three call sites, all `icon( … )` → `get_icon( … )` or `the_icon( … )`:

- `src/blocks/site-header/inc/class-nav-walker.php:131` — `chevronDown`, size 20.
- `src/blocks/site-header/render.php:40` — `close`, size 24.
- `src/blocks/read-more/render.php:79` — `arrowForward`, size 24.

All three build strings by concatenation, so they take `get_icon()`; `the_icon()`
exists for direct-echo templates. Their CSS classes and the surrounding
accessibility markup do not change — `.isudev-nav__chevron-icon`,
`.isudev-header__close-icon`, `.read-more-arrow__icon` keep sizing the glyph, and
the `isudev-sr-only` labels and `aria-hidden` wrappers stay as they are.

`burger` stays in the registry although nothing renders it (the header burger is
three CSS-animated `<span>`s); it is there for integrators.

## 8. Testing

`tools/check.php` gains a clearly marked block of minimal WordPress shims,
defined only when absent: `__()`, `esc_attr()`, `esc_url()`, `apply_filters()`.
`esc_attr()` is `htmlspecialchars( $value, ENT_QUOTES )` — close enough to WP for
the assertions below. `apply_filters()` returns its value unchanged, so checks
exercise the defaults.

`tools/checks/50-icon.php` is rewritten to a pragmatic minimum:

- `normalize_size()`: `int` and `[ $w, $h ]`.
- `apply_root_attrs()`: injects `width`/`height`/`class`, overrides a `width`
  already present in the source, leaves exactly one `viewBox`, keeps
  `fill="currentColor"` and the a11y pair.
- URL branch: a non-`<svg` value yields `<img` with `src`, `width`, `height` and
  `alt=""`.
- `attrs`: `null` removes `aria-hidden`, an `on*` key is dropped, a value with a
  quote is escaped.
- Unknown name yields `''`.
- The four built-ins are present and each carries a non-empty `icon`.

`npm run test:php` stays the command. `npm run test:e2e` after the header call
sites change, because the header markup contract is axe-covered.

## 9. Files

```
includes/utils/icon.php              rewritten
isudev-library.php                   + Utils\boot_icons()
tools/check.php                      + WP shim block
tools/checks/50-icon.php             rewritten
src/blocks/site-header/render.php    icon() → get_icon()
src/blocks/site-header/inc/class-nav-walker.php   icon() → get_icon()
src/blocks/read-more/render.php      icon() → get_icon()
AGENTS.md                            purity golden rule narrowed
guides/work-with-icons.md            rewritten for the new model
CHANGELOG.md, package.json, isudev-library.php, VERSION   → 1.2.0
```

## 10. Out of scope

- Exposing an author-facing icon choice in any block (no block needs one yet).
- A REST endpoint for the registry; the admin panel does not render icons.
- Icon categories or tab grouping — the registry passes unknown keys through, so
  this lands when the components package supports it.
- Fixing `<img>`-based rendering of serialized SVG; that is a components-package
  change.
