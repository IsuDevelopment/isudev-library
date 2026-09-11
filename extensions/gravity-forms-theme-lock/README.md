# Lock the Gravity Forms block theme

Pins every Gravity Forms block to one form theme and hides the theme picker from
the editor, so forms cannot drift away from the site styles.

**Requires Gravity Forms.** The extension cannot be enabled while the plugin is
inactive; the panel shows it as unavailable.

## What it changes

- **The default theme** of the `gravityforms/form` block becomes
  `gravity-theme`, Gravity Forms' own minimally styled base — the one intended to
  be styled by a theme rather than to look finished on its own.
- **The block's styling panels are hidden** in the editor sidebar. The form
  selector, which is the last panel, stays.

An editor inserting a form gets the right theme without knowing there was a
choice.

## A default, not a lock on saved content

The theme is a block attribute, and attributes are serialised into post content.
This changes the **default** — what a newly inserted block gets. A block that was
already saved with another theme keeps it until someone removes and re-inserts
it. Hiding the panel means they can no longer change it back by hand, which is
the part that matters going forward.

## Hooks

### `isudev_library/extensions/gravity_forms_theme_lock/theme`

The theme blocks are pinned to.

```php
add_filter(
	'isudev_library/extensions/gravity_forms_theme_lock/theme',
	function ( string $theme ): string {
		return 'orbital';
	}
);
```

## Notes

The panel is hidden with a stylesheet added on `enqueue_block_editor_assets`.
That hook does not reach the iframed editor canvas, which is correct here — the
block inspector sidebar is not iframed, so the rule lands where it is needed.

## When forms still look wrong

- **An old form still carries another theme.** See the section above: re-insert
  the block.
- **The panel is visible again.** Gravity Forms changed its panel markup. The
  rule targets `.gform-block__panel`; check the sidebar's current markup.
