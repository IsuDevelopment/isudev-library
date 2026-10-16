# Load theme block styles per block

A Dekode project-base theme builds one stylesheet per block it restyles —
`src/blocks/core/button.css` becomes `build/blocks/core/button.css`. This
extension finds those files and hands each one to core's
`wp_enqueue_block_style()`, so a block's CSS loads only on pages that render
the block (on block themes and with on-demand block assets) and always in the
editor canvas. It replaces T2's `t2/enqueue-theme-block-styles`.

## What it does

- Looks in `<theme>/<dir>/<namespace>/<block>.css`, `dir` defaulting to
  `build/blocks`. The directory name is the block namespace, the file name
  the block: `core/button.css` → `core/button`, `gravityforms/form.css` →
  `gravityforms/form`. Any namespace works — `core`, `isudev`, `t2`, a
  plugin's.
- **Child themes:** the parent theme's files are enqueued first, then the
  child's, so for the same block the child's rules win. Both load.
- `<block>-rtl.css` is not a block of its own and is skipped; core swaps it
  in on RTL sites because the `path` is passed.
- `ver` is the file's modification time, so a rebuilt file busts caches.
- `path` is passed, so core may inline small files into the page instead of
  linking them — **except when `WP_DEBUG` is on**: inlined CSS cannot be
  hot-swapped by BrowserSync, so in development every file is a `<link>`.
- Files and directories with names that cannot form a block name (anything
  but lowercase letters, digits and `-`), and non-files, are ignored. A
  missing directory is not an error.
- The directory is scanned once per request, at `init` priority 0.

## Handles

`isudev-theme-block-styles-<namespace>-<block>`, plus `-parent` for a parent
theme's file — e.g. `isudev-theme-block-styles-core-button`. T2 used
`t2-theme-block-styles-…`; a theme that enqueues one of these handles itself
(to load the same CSS outside the block, say for a shortcode) must use the
new name or set the prefix back:

```php
add_filter(
	'isudev_library/extensions/enqueue_theme_block_styles/handle_prefix',
	fn() => 't2-theme-block-styles-'
);
```

The prefix must match `[a-z0-9_-]+`; anything else falls back to the default.

## Settings

`isudev.json`:

```json
{
	"library": {
		"extensions": {
			"enqueue-theme-block-styles": {
				"enabled": true,
				"dir": "build/blocks"
			}
		}
	}
}
```

`dir` is relative to the theme root. A value with `..` or characters outside
`A–Z a–z 0–9 _ - . /` falls back to `build/blocks`.

## Hooks

### `isudev_library/extensions/enqueue_theme_block_styles/deps`

Style handles a block's stylesheet depends on. Default none.

```php
add_filter(
	'isudev_library/extensions/enqueue_theme_block_styles/deps',
	function ( array $deps, string $block_name ): array {
		if ( 'core/button' === $block_name ) {
			$deps[] = 'my-theme-tokens';
		}
		return $deps;
	},
	10,
	2
);
```

### `isudev_library/extensions/enqueue_theme_block_styles/handle_prefix`

See *Handles* above.

## Do not enable together with T2

T2's `t2/enqueue-theme-block-styles` enqueues the same files under its own
handles; with both on, every file loads twice. Remove it from `t2.json` when
switching this on.

## What it relies on

`wp_enqueue_block_style()` (core `script-loader.php`): with on-demand block
assets it hooks `render_block` and enqueues when that block renders;
otherwise it enqueues on `wp_enqueue_scripts`. Either way it also hooks
`enqueue_block_assets`, which reaches the iframed editor canvas. Calling it
at `init` is early enough for all of these.

## When it does nothing

- The file is not where expected: check `<theme>/<dir>/<namespace>/<block>.css`
  exists after the build, and the `dir` setting.
- On the front end with on-demand assets the CSS appears only on a page
  that renders the block — that is the point.
- The block name is wrong: a file `core/buttons.css` styles the *Buttons*
  container, not *Button*.

## How to check it still works

On a page with a Button, the source has
`isudev-theme-block-styles-core-button-css` (a `<link>`, or an inline
`<style>` when `WP_DEBUG` is off and the file is small). A page without
buttons has neither when the theme loads block assets on demand.
