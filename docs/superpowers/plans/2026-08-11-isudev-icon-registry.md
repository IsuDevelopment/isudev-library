# Icon registry rewrite — implementation plan

> **Worker:** Codex implements this plan task-by-task, in order. Claude reviews the
> result afterwards against the spec. Steps use checkbox (`- [ ]`) syntax.
> Do not reorder tasks: every task leaves the repo green and committable.

**Goal:** Replace the `t2`-inherited icon registry with one whose definitions match
`IconDefinition` from `@isudev/gutenberg`, so the same PHP registry both renders the
frontend and feeds the editor's `Icon` / `IconPicker` / `IconSelect`.

**Spec:** `docs/superpowers/specs/2026-08-11-isudev-icon-registry-design.md` — read it
first. It states the *why* for every decision below and is the tiebreaker if this plan
is ambiguous.

**Shape of the change:** `slug ⇒ path fragment` + a detached `slug ⇒ viewBox` map
becomes `name ⇒ { label, icon, keywords }`, where `icon` is a complete `<svg>` **or**
an image URL. `build_svg()` (which assembled an `<svg>`) is replaced by
`apply_root_attrs()` (which overrides attributes on one that already exists).
`icon()` becomes `get_icon()` / `the_icon()`, taking an `$args` array.

**Breaking:** yes, deliberately, with no shims. Nobody runs this plugin yet. The three
in-repo call sites are migrated in Task 5.

## Global constraints

Every task's requirements implicitly include this section.

- **Branch:** create and work on `refactor/icon-registry`. Never commit to `main`.
- **Commit identity:** `git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no"`.
- **WordPress Coding Standards.** `declare( strict_types = 1 );`, `ABSPATH` guard,
  tabs, Yoda-free but WPCS-clean. Verify with `composer run lint:php`.
- **WPCS forbids these parameter names** (`Universal.NamingConventions.NoReservedKeywordParameterNames`):
  `$default $parent $namespace $array $class $function $list $new $print $static $string $use`.
  This bites here: the attribute helper cannot take `$array`, and a local `$list` in a
  *function body* is fine but a *parameter* named `$list` is not.
- **WPCS forbids assigning to WordPress globals at file scope**
  (`WordPress.WP.GlobalVariablesOverride`) — relevant in `render.php`, which is
  included at file scope.
- **Docblock long descriptions must start with a capital letter**
  (`Generic.Commenting.DocComment.LongNotCapital`); a `/* --- text --- */` marker
  comment is a hard error (`Squiz.Commenting.BlockComment.NoNewLine`). Use
  `/*\n * Text.\n */`.
- **No top-level hook registration in `includes/`.** Hooks are registered from a
  `boot*()` function called by the bootstrap.
- **`build/` is committed.** Rebuild (`npm run build`, one-shot — never `npm start`,
  never a watch loop) and commit `build/` whenever `src/` changes.
- **wp-cli has no database access on this site.** No verification step may use
  `wp eval`, `wp option` or `wp plugin`. Verify with `php tools/check.php`, with
  Playwright, or over HTTP against `http://isudev-library.local/`.
- **Baseline before this plan:** `php tools/check.php` prints
  `143 passed, 0 failed (8 check files)`. `npm run test:e2e` prints `30 passed`,
  `20 skipped`.
- **Reporting check counts.** Report the `N passed, M failed` line verbatim, confirm
  `M` is `0`. Task 3 changes the total by design (the icon check file is rewritten);
  its expected total is stated there. A different total is information — count your
  assertions and explain before touching one.
- **Do not invent glyph data.** The four `d` attribute values already in
  `includes/utils/icon.php` are copied **verbatim**. If a `d` string in the new file
  differs from the old one by a single character, the task is wrong. Recover the
  originals with `git show HEAD:includes/utils/icon.php` if needed.

---

### Task 1: WordPress shims in the check runner

`tools/check.php` is a plain-PHP runner with no WordPress. The rewritten registry uses
`__()`, `esc_attr()`, `esc_url()` and `apply_filters()` normally — the plugin will never
run outside WordPress, so avoiding them buys nothing. The runner gets minimal shims so
it can still require and exercise the file.

**Files:** Modify `tools/check.php`.

- [ ] **Step 1: Add the shim block**

  Immediately after the existing `defined( 'ABSPATH' ) || define( ... );` line and
  **before** the `glob( __DIR__ . '/checks/*.php' )` loop, add:

  ```php
  /*
   * Minimal WordPress shims.
   *
   * These exist so utils that legitimately use WordPress escaping, translation and
   * filters can still be exercised without a WordPress bootstrap. They are close
   * enough for assertions about escaping and default behaviour, and nothing more:
   * apply_filters() returns its value untouched, so checks always see defaults.
   */
  if ( ! function_exists( 'esc_attr' ) ) {
  	/**
  	 * Escape a value for an HTML attribute.
  	 *
  	 * @param string $text Value to escape.
  	 * @return string Escaped value.
  	 */
  	function esc_attr( string $text ): string {
  		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
  	}
  }

  if ( ! function_exists( 'esc_url' ) ) {
  	/**
  	 * Escape a URL for output.
  	 *
  	 * @param string $url URL to escape.
  	 * @return string Escaped URL.
  	 */
  	function esc_url( string $url ): string {
  		return htmlspecialchars( strip_tags( $url ), ENT_QUOTES, 'UTF-8' );
  	}
  }

  if ( ! function_exists( '__' ) ) {
  	/**
  	 * Return a string unchanged, standing in for translation.
  	 *
  	 * @param string $text   Text to translate.
  	 * @param string $domain Text domain. Ignored.
  	 * @return string The text.
  	 */
  	function __( string $text, string $domain = 'default' ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Test-only shim for a WordPress function.
  		unset( $domain );
  		return $text;
  	}
  }

  if ( ! function_exists( 'apply_filters' ) ) {
  	/**
  	 * Return the filtered value unchanged, standing in for the hook system.
  	 *
  	 * @param string $hook_name Hook name. Ignored.
  	 * @param mixed  $value     Value to filter.
  	 * @return mixed The value.
  	 */
  	function apply_filters( string $hook_name, $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Test-only shim for a WordPress function.
  		unset( $hook_name );
  		return $value;
  	}
  }
  ```

  Note `apply_filters()` here accepts only the two arguments the registry passes plus
  the extras PHP discards silently — do not add variadics it does not need.

- [ ] **Step 2: Verify nothing regressed**

  ```bash
  php tools/check.php
  ```

  Expect the unchanged baseline: `143 passed, 0 failed (8 check files)`. The shims are
  inert until Task 3 uses them.

- [ ] **Step 3: Lint and commit**

  ```bash
  composer run lint:php
  git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" \
    commit -am "test: WordPress shims in the plain-PHP check runner"
  ```

---

### Task 2: The new `includes/utils/icon.php`

Rewrite the file completely. This task ships the implementation; Task 3 rewrites its
checks. (Checks-first is the house style, but here the checks must call functions that
do not exist yet in any form, and their names are fixed by this task — so implement,
then assert, then mutation-test in Task 3.)

**Files:** Rewrite `includes/utils/icon.php`.

**Interfaces produced** (all in namespace `IsuDevLibrary\Utils`):

```php
const DEFAULT_ICON_SIZE = 24;

default_icons(): array                                        // name ⇒ definition
normalize_icons( array $icons ): array                        // validate + label fallback
get_icons(): array                                            // default_icons() through the filter, normalized
normalize_size( $size ): array                                // int|array ⇒ [ int $width, int $height ]
build_attrs( array $attrs ): string                           // ' key="value"' … serialization
apply_root_attrs( string $svg, array $attrs ): string          // override attrs on the first <svg …>
render_icon( string $markup, array $attrs ): string           // dispatch: inline SVG vs <img>
get_icon( string $name, array $args = array() ): string
the_icon( string $name, array $args = array() ): void
localize_icons( string $handle, string $object_name = 'isudevIcons' ): void
boot_icons(): void
```

**Removed, with no replacement:** `default_icon_paths()`, `default_icon_view_boxes()`,
`build_svg()`, `DEFAULT_VIEW_BOX`, `icon()`, the `isudev_library/icon_view_boxes`
filter, and the `WordPress adapters.` marker comment (the whole file may call
WordPress now).

- [ ] **Step 1: Build the definition map**

  Four entries, keyed by the existing names. For each one, take the **existing path
  fragment verbatim** and wrap it:

  ```php
  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="{VIEWBOX}" fill="currentColor">{EXISTING_PATH_FRAGMENT}</svg>'
  ```

  | Name | `{VIEWBOX}` | `label` | `keywords` |
  | --- | --- | --- | --- |
  | `chevronDown` | `0 0 600 600` | `__( 'Chevron down', 'isudev-library' )` | `array( 'arrow', 'down', 'expand', 'submenu' )` |
  | `burger` | `0 0 600 600` | `__( 'Menu', 'isudev-library' )` | `array( 'menu', 'hamburger', 'nav' )` |
  | `close` | `0 0 600 600` | `__( 'Close', 'isudev-library' )` | `array( 'x', 'dismiss', 'cancel' )` |
  | `arrowForward` | `0 0 24 24` | `__( 'Arrow forward', 'isudev-library' )` | `array( 'arrow', 'right', 'next' )` |

  The stored markup carries **no** `width`/`height` — those are injected per call. It
  does carry `fill="currentColor"`, so a standalone render (the editor's `<img>` path)
  is not left with a UA default.

  Definition order in the array is the order the picker will show, so keep the current
  order: `chevronDown`, `burger`, `close`, `arrowForward`.

- [ ] **Step 2: `normalize_icons()` and `get_icons()`**

  ```php
  function normalize_icons( array $icons ): array {
  	$normalized = array();

  	foreach ( $icons as $name => $definition ) {
  		if ( ! is_string( $name ) || '' === $name || ! is_array( $definition ) ) {
  			continue;
  		}

  		$markup = isset( $definition['icon'] ) && is_string( $definition['icon'] )
  			? trim( $definition['icon'] )
  			: '';

  		if ( '' === $markup ) {
  			continue;
  		}

  		$label = isset( $definition['label'] ) && is_string( $definition['label'] ) && '' !== $definition['label']
  			? $definition['label']
  			: $name;

  		$definition['icon']  = $markup;
  		$definition['label'] = $label;

  		$normalized[ $name ] = $definition;
  	}

  	return $normalized;
  }
  ```

  Keys other than `icon` and `label` **pass through untouched** — that is deliberate,
  so a future `category` key from the components package needs no change here. Do not
  add an allowlist.

  `get_icons()` is `normalize_icons( (array) apply_filters( 'isudev_library/icons', default_icons() ) )`.
  Keep the filter docblock, drop the `icon_view_boxes` one.

- [ ] **Step 3: `normalize_size()`**

  `int`-ish input ⇒ `array( $n, $n )`. Array input ⇒ `array( (int) $size[0], (int) $size[1] )`,
  with `$size[1]` falling back to the width when absent. Any dimension that is not
  greater than zero falls back to `DEFAULT_ICON_SIZE` for the width, and to the
  resolved width for the height. Return a two-element list, always `int`.

- [ ] **Step 4: `build_attrs()`**

  Serializes `array( 'width' => 24, … )` to ` width="24" …`, one leading space per
  attribute, in insertion order. Rules, all of which Task 3 asserts:

  - Key must match `/^[a-z][a-z0-9-]*$/` — anything else is skipped. This is what keeps
    `viewBox`, camelCase and anything containing whitespace or a quote out of the
    attribute position.
  - A key starting with `on` is skipped.
  - `null` and `false` values are skipped — that is how a caller **removes** a default
    such as `aria-hidden`.
  - `true` renders the bare attribute name.
  - Array or object values are skipped.
  - Everything else is cast to string and passed through `esc_attr()`. An empty string
    renders as `key=""` (needed for `alt=""`).

- [ ] **Step 5: `apply_root_attrs()`**

  Rewrites the **first** `<svg …>` opening tag: strips any existing occurrence of each
  attribute being set, then appends the new ones. Returns `''` when the input has no
  `<svg` tag.

  ```php
  function apply_root_attrs( string $svg, array $attrs ): string {
  	if ( ! preg_match( '/<svg\b[^>]*>/i', $svg, $matches, PREG_OFFSET_CAPTURE ) ) {
  		return '';
  	}

  	$tag    = (string) $matches[0][0];
  	$offset = (int) $matches[0][1];
  	$inner  = substr( $tag, 4, -1 );              // Drop '<svg' and '>', keep the leading space.
  	$inner  = rtrim( $inner );

  	$self_closing = '/' === substr( $inner, -1 );
  	if ( $self_closing ) {
  		$inner = rtrim( substr( $inner, 0, -1 ) );
  	}

  	foreach ( array_keys( $attrs ) as $key ) {
  		if ( ! is_string( $key ) ) {
  			continue;
  		}

  		$pattern = '/\s' . preg_quote( $key, '/' ) . '\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i';
  		$inner   = (string) preg_replace( $pattern, '', $inner );
  	}

  	$rebuilt = '<svg' . rtrim( $inner ) . build_attrs( $attrs ) . ( $self_closing ? ' />' : '>' );

  	return substr_replace( $svg, $rebuilt, $offset, strlen( $tag ) );
  }
  ```

  `WP_HTML_Tag_Processor` is deliberately not used — see the spec §5. Add a short
  comment saying the `[^>]*` match is safe because the registry is trusted static
  configuration, not arbitrary HTML.

- [ ] **Step 6: `render_icon()`**

  ```php
  function render_icon( string $markup, array $attrs ): string {
  	$markup = trim( $markup );

  	if ( 0 === stripos( $markup, '<svg' ) ) {
  		return apply_root_attrs( $markup, $attrs );
  	}

  	return sprintf( '<img src="%s"%s>', esc_url( $markup ), build_attrs( $attrs ) );
  }
  ```

  Anything not starting with `<svg` is a URL. Dashicon names are explicitly out of
  scope (spec §4) — no branch for them.

- [ ] **Step 7: `get_icon()` and `the_icon()`**

  `get_icon()`:

  1. Resolve the definition from `get_icons()`. Unknown or empty name ⇒ return
     `apply_filters( 'isudev_library/icon', '', $name, $args )` — an unknown name stays
     silent, and the filter still sees the call.
  2. `list( $width, $height ) = normalize_size( $args['size'] ?? DEFAULT_ICON_SIZE );`
  3. Read `$args['class']` (string, trimmed) and `$args['attrs']` (array).
  4. Defaults depend on the branch. SVG:
     `width`, `height`, `fill => 'currentColor'`, `aria-hidden => 'true'`,
     `focusable => 'false'`. Image: `width`, `height`, `alt => ''`,
     `decoding => 'async'` — **no** `loading` attribute, and no `fill`/`focusable`,
     which mean nothing on `<img>`.
  5. Append `class` when non-empty, then `array_merge( $defaults, $extra )` so `attrs`
     wins.
  6. Return `apply_filters( 'isudev_library/icon', render_icon( $markup, $attrs ), $name, $args )`.
     Update the filter docblock to the new signature.

  `the_icon()` echoes `get_icon()` with

  ```php
  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Registry markup is trusted static configuration; attribute values are escaped in build_attrs().
  ```

- [ ] **Step 8: `localize_icons()` and `boot_icons()`**

  ```php
  function localize_icons( string $handle, string $object_name = 'isudevIcons' ): void {
  	$definitions = array();

  	foreach ( get_icons() as $name => $definition ) {
  		$definitions[] = array_merge( array( 'name' => $name ), $definition );
  	}

  	wp_localize_script( $handle, $object_name, $definitions );
  }

  function boot_icons(): void {
  	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_icons', 5 );
  }

  function enqueue_editor_icons(): void {
  	wp_register_script( 'isudev-library-icons', false, array(), \IsuDevLibrary\VERSION );
  	wp_enqueue_script( 'isudev-library-icons' );
  	localize_icons( 'isudev-library-icons' );
  }
  ```

  The list shape — `name` inside each entry — is what `@isudev/gutenberg`'s
  `getLocalizedIcons()` / `parseLocalizedIcons()` expects. Document in a comment why
  the handle has no `src`: a data-only handle prints its localized data in `<head>`,
  ahead of every block editor script, and attaching to `wp-blocks` instead would couple
  us to Core's loading lifecycle (the package's own documented gotcha).

- [ ] **Step 9: Wire the bootstrap**

  In `isudev-library.php`, next to the other boot calls, add
  `Utils\boot_icons();`. The `require_once PATH . 'includes/utils/icon.php';` line
  already exists and does not move.

- [ ] **Step 10: Lint**

  ```bash
  composer run lint:php
  php tools/check.php
  ```

  `tools/check.php` **will fail** at this point: `tools/checks/50-icon.php` still calls
  `default_icon_paths()` and `build_svg()`. That is expected — Task 3 fixes it. Do not
  commit yet; Task 3 commits both halves together, so the repo is never left with a red
  runner.

---

### Task 3: Rewrite `tools/checks/50-icon.php`

Pragmatic minimum: cover the string-manipulation helpers (where bugs actually live),
the registry contents, and the two rendering branches. Nineteen assertions, listed
exactly so the resulting count is predictable.

**Files:** Rewrite `tools/checks/50-icon.php`.

- [ ] **Step 1: Write the assertions**

  Import what you need with `use function IsuDevLibrary\Utils\…`. Write exactly these,
  in this order:

  1. `default_icons()` holds exactly 4 entries.
  2. Every default carries a non-empty `icon` string **and** a non-empty `label`
     (one boolean assertion over a loop).
  3. Every default's `icon` starts with `<svg` and contains a `viewBox` (one boolean
     assertion over a loop).
  4. `arrowForward`'s markup contains `viewBox="0 0 24 24"`.
  5. `chevronDown`'s markup contains `viewBox="0 0 600 600"`.
  6. `normalize_icons()` falls a missing `label` back to the name.
  7. `normalize_icons()` drops an entry whose `icon` is an empty string.
  8. `normalize_icons()` passes an unknown key (`'category' => 'ui'`) through untouched.
  9. `normalize_size( 24 )` is `array( 24, 24 )`.
  10. `normalize_size( array( 32, 16 ) )` is `array( 32, 16 )`.
  11. `normalize_size( 0 )` is `array( 24, 24 )`.
  12. `build_attrs( array( 'title' => 'a "quoted" value' ) )` contains `&quot;` and no
      raw `"` inside the value.
  13. `build_attrs( array( 'onclick' => 'x()' ) )` is `''`.
  14. `build_attrs( array( 'aria-hidden' => null ) )` is `''`.
  15. `build_attrs( array( 'alt' => '' ) )` is ` alt=""`.
  16. `apply_root_attrs( '<svg viewBox="0 0 24 24"><path/></svg>', array( 'width' => 20, 'class' => 'x' ) )`
      contains `width="20"` and `class="x"`, and still contains exactly one `viewBox=`.
  17. `apply_root_attrs()` on a source that already has `width="99"` yields exactly one
      `width=` occurrence, and it is `width="20"`.
  18. `render_icon( 'https://example.com/i.svg', array( 'width' => 24, 'alt' => '' ) )`
      starts with `<img ` and contains `src="https://example.com/i.svg"` and `alt=""`.
  19. `get_icon( 'nope' )` is `''`; and `get_icon( 'arrowForward', array( 'size' => 20, 'class' => 'c', 'attrs' => array( 'aria-hidden' => null ) ) )`
      contains `width="20" `, `class="c"`, `viewBox="0 0 24 24"`, `fill="currentColor"`
      and **no** `aria-hidden`.

  Items 16, 17 and 19 each need more than one `Checks::is()` call — that is fine and
  expected; the count below accounts for it.

- [ ] **Step 2: Run**

  ```bash
  php tools/check.php
  ```

  Expect `0 failed`. The total moves from 143 to whatever your assertion count yields
  (the old file had 21 `Checks::` calls). Report the line verbatim.

- [ ] **Step 3: Mutation-test three assertions**

  Prove the checks bite. One at a time, make the change, run `php tools/check.php`,
  confirm the *specific* expected failure, then revert:

  1. In `build_attrs()`, drop the `on`-prefix guard ⇒ assertion 13 fails.
  2. In `apply_root_attrs()`, skip the strip loop (append only) ⇒ assertion 17 fails
     with two `width=` occurrences.
  3. In `render_icon()`, invert the `<svg` test ⇒ assertions 18 and 19 fail.

  Revert every mutation. `git diff` must show only the intended rewrite before
  committing.

- [ ] **Step 4: Lint and commit both halves**

  ```bash
  composer run lint:php && php tools/check.php
  git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" \
    commit -am "refactor!: icon registry speaks IconDefinition, get_icon()/the_icon() API"
  ```

---

### Task 4: Migrate the three call sites

**Files:**
- Modify `src/blocks/site-header/inc/class-nav-walker.php` (line ~131)
- Modify `src/blocks/site-header/render.php` (line ~40)
- Modify `src/blocks/read-more/render.php` (line ~79)

- [ ] **Step 1: Rewrite the calls**

  All three build strings by concatenation or `sprintf()`, so all three take
  `get_icon()`, not `the_icon()`. Update the `use function` line in each file.

  | File | Was | Becomes |
  | --- | --- | --- |
  | `class-nav-walker.php` | `icon( 'chevronDown', 20, 'isudev-nav__chevron-icon' )` | `get_icon( 'chevronDown', array( 'size' => 20, 'class' => 'isudev-nav__chevron-icon' ) )` |
  | `site-header/render.php` | `icon( 'close', 24, 'isudev-header__close-icon' )` | `get_icon( 'close', array( 'class' => 'isudev-header__close-icon' ) )` |
  | `read-more/render.php` | `icon( 'arrowForward', 24, 'read-more-arrow__icon' )` | `get_icon( 'arrowForward', array( 'class' => 'read-more-arrow__icon' ) )` |

  Where the old size was the default 24, omit `size` — the default is
  `DEFAULT_ICON_SIZE`. Do not change any surrounding markup: the `isudev-sr-only`
  labels, the `aria-hidden` wrapper spans and the CSS class names all stay exactly as
  they are.

  Grep for stragglers: `grep -rn "Utils\\\\icon\|icon(" src includes` must show no
  remaining `Utils\icon` import or bare `icon(` call.

- [ ] **Step 2: Rebuild**

  ```bash
  nvm use && npm run build
  ```

  `build/blocks/*/render.php` are copies of the source files — confirm both rendered
  files changed:

  ```bash
  grep -rn "get_icon" build/blocks/*/render.php
  ```

- [ ] **Step 3: Verify against the real site**

  ```bash
  curl -s http://isudev-library.local/ | grep -o '<svg[^>]*isudev-nav__chevron-icon[^>]*>' | head -1
  curl -s http://isudev-library.local/ | grep -c 'read-more-arrow__icon'
  ```

  The chevron tag must show `width="20" height="20"`, `class="isudev-nav__chevron-icon"`,
  `fill="currentColor"`, `aria-hidden="true"`, `focusable="false"` and exactly one
  `viewBox="0 0 600 600"`. If the front page has no read-more card, skip the second
  command rather than inventing content.

- [ ] **Step 4: E2E**

  ```bash
  npm run test:e2e
  ```

  Expect `30 passed`, `20 skipped` — unchanged. The header markup contract is
  axe-covered, so a regression in the chevron or close button shows up here.

- [ ] **Step 5: Lint and commit**

  ```bash
  composer run lint:php && php tools/check.php
  git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" \
    commit -am "refactor: block render moves to get_icon()"
  ```

  Include the rebuilt `build/` output in this commit.

---

### Task 5: Verify the editor injection

One Playwright test, because this is the one thing `tools/check.php` cannot reach and
its failure mode is silent (an empty picker later).

**Files:** Add `e2e/icons.spec.js`.

- [ ] **Step 1: Write the spec**

  Model it on `e2e/panel.spec.js`: require `hasAdminCredentials` and `loginAsAdmin`
  from `./admin-auth`, skip the whole describe without credentials, restrict to the
  `desktop-chromium` project in `beforeEach` (`test.skip( testInfo.project.name !== 'desktop-chromium', 'desktop only' )`),
  and use `test.describe.configure({ mode: 'serial' })`.

  The test opens `/wp-admin/post-new.php` (a Post, not a Page — `panel.spec.js`
  documents why: the Page editor's starter-pattern dialog steals focus), dismisses any
  `Close|Zamknij` dialog button the same way `openPageEditor()` does, then asserts on
  the localized global in the top frame:

  ```js
  const icons = await page.evaluate( () => window.isudevIcons );

  expect( Array.isArray( icons ) ).toBe( true );
  expect( icons.length ).toBeGreaterThanOrEqual( 4 );

  const names = icons.map( ( entry ) => entry.name );
  expect( names ).toContain( 'arrowForward' );

  const arrow = icons.find( ( entry ) => entry.name === 'arrowForward' );
  expect( arrow.label ).toBeTruthy();
  expect( arrow.icon.startsWith( '<svg' ) ).toBe( true );
  ```

  Add a comment stating why the assertion runs in the top frame and not the canvas
  iframe: block editor scripts execute in the top frame; only the canvas DOM is
  iframed.

- [ ] **Step 2: Run**

  ```bash
  npm run test:e2e
  ```

  Expect the previous totals plus this test's project split — report the line verbatim.
  If it fails because the global is `undefined`, the fault is ordering: confirm
  `boot_icons()` is called from the bootstrap and that the handle is registered with
  `false` as its `src`.

- [ ] **Step 3: Commit**

  ```bash
  git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" \
    commit -am "test: assert the icon registry reaches the block editor"
  ```

---

### Task 6: Documentation and release metadata

**Files:** `README.md`, `AGENTS.md`, `guides/work-with-icons.md`, `CHANGELOG.md`,
`isudev-library.php`, `package.json`, `.distignore`.

- [ ] **Step 1: `README.md` filter table**

  Drop the `isudev_library/icon_view_boxes` row. Reword the other two: the registry is
  no longer "inline SVG" only — it holds inline SVG **or** image URLs.

- [ ] **Step 2: `AGENTS.md` golden rule**

  The "Pure functions stay pure" bullet currently forbids any WordPress call above the
  `WordPress adapters` marker. Narrow it to what it actually protects, and say what
  changed so a future reader does not "restore" the old rule:

  > **Utils stay testable.** `tools/check.php` requires `includes/utils/*` and other
  > pure helpers with no WordPress bootstrap, so they must not touch the database,
  > `WP_Query`, HTTP or the object cache. Escaping, translation and filters are fine —
  > the runner shims them. Files that still carry a `WordPress adapters` marker keep
  > that split; `includes/utils/icon.php` deliberately does not.

  Also update the `@isudev/gutenberg` paragraph's neighbourhood if it claims icons are
  passed per block — the registry is now localized once for the whole editor.

- [ ] **Step 3: Rewrite `guides/work-with-icons.md`**

  The guide currently documents the old model in detail and is now wrong in most
  sections. Rewrite it against the new one, keeping its structure (three icon systems,
  adding an icon, styling, a11y, filters, inserter icons, picker outlook, checklist).
  Specifically:

  - `get_icon()` / `the_icon()` with the `$args` table, replacing `icon( $slug, $size, $class )`.
  - Definitions carry `label`, `icon`, optional `keywords`; unknown keys pass through.
  - `icon` is a complete `<svg>` **or** a URL; the URL branch renders `<img>`, so a
    registered asset must be a URL (`URL . 'assets/…'`), never a filesystem path.
  - viewBox travels inside the markup — the "600 grid" special case and the
    `isudev_library/icon_view_boxes` filter are gone.
  - Adding an icon: no `fill` on the inner `<path>` (the root's `currentColor` would
    lose), no `width`/`height` in stored markup, extend `50-icon.php`, bump the count
    assertion, `npm run test:php`.
  - `attrs` removal semantics (`null` drops a default) as the documented way to make a
    labelled, non-decorative icon.
  - The editor gets the registry automatically via `enqueue_block_editor_assets`; a
    block consuming it only calls `getLocalizedIcons()`.
  - Keep the known limitation note: the package renders serialized SVG through a
    percent-encoded `<img>`, so editor previews do not inherit `currentColor`. Mark it
    as a components-package fix, not ours.

- [ ] **Step 4: `.distignore`**

  Add `/guides` — it is developer documentation and must not ship in the release
  archive, exactly like `/docs`.

- [ ] **Step 5: Version and changelog**

  Bump to `1.2.0` in `isudev-library.php` (both the plugin header `Version:` and the
  `VERSION` const) and in `package.json`. Add a `## 1.2.0 — <today>` section to
  `CHANGELOG.md` with `### Changed`, `### Added` and `### Breaking changes` subsections
  covering: the definition shape, `get_icon()`/`the_icon()` replacing `icon()`, URL
  icons, automatic editor localization (`isudevIcons`), and the removal of
  `default_icon_paths()`, `default_icon_view_boxes()`, `build_svg()` and the
  `isudev_library/icon_view_boxes` filter.

- [ ] **Step 6: Final verification and commit**

  ```bash
  php tools/check.php && composer run lint:php && npm run lint:js && npm run lint:css
  git -c user.name="Lukasz Biedron" -c user.email="lukasz.biedron@dekode.no" \
    commit -am "docs: icon registry guide, filters and 1.2.0 release notes"
  ```

---

## Definition of done

- `php tools/check.php` — `0 failed`, count reported and explained.
- `composer run lint:php`, `npm run lint:js`, `npm run lint:css` — clean.
- `npm run test:e2e` — no regression, plus the new icons spec.
- `grep -rn "default_icon_paths\|default_icon_view_boxes\|build_svg\|icon_view_boxes" .`
  (excluding `node_modules`, `docs/`, `CHANGELOG.md`) returns nothing.
- The four `d` strings are byte-identical to `git show <base>:includes/utils/icon.php`.
- Branch `refactor/icon-registry` holds one commit per task, `main` untouched.

## Report back

For the review, state: the final check count line, the e2e totals, which of the three
mutations you ran and the exact failure each produced, and any place you deviated from
this plan with the reason. Deviations are expected occasionally — an unreported one is
the only real problem.
