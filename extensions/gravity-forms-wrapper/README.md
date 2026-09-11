# Gravity Forms block wrapper

Wraps the rendered Gravity Forms block in a `<div>` so the theme has something to
style against.

**Requires Gravity Forms.** The extension cannot be enabled while the plugin is
inactive; the panel shows it as unavailable.

## Why it exists

`gravityforms/form` renders the form markup straight into the page, without the
`wp-block-…` wrapper almost every other block emits. There is no element between
the page layout and the form itself, so a theme cannot give a form a width, a
background or spacing without reaching into Gravity Forms' own class names.

This adds the wrapper the block would have had:

```html
<div class="wp-block-gravityforms-form">
	<!-- the form -->
</div>
```

A block that renders nothing — no form selected, or the form was deleted — is
left alone, so an empty wrapper never turns up in the layout as a mysterious gap.

## Hooks

### `isudev_library/extensions/gravity_forms_wrapper/class`

The class on the wrapper. Defaults to `wp-block-gravityforms-form`.

```php
add_filter(
	'isudev_library/extensions/gravity_forms_wrapper/class',
	function ( string $class ): string {
		return 'my-theme-form';
	}
);
```

## When the wrapper is not there

- **The form is not in a block.** This filters the block's render only. A form
  placed with the `[gravityform]` shortcode or with `gravity_form()` in a
  template never passes through it.
- **A form embedded in another form's confirmation.** Those render outside the
  block pipeline too.
