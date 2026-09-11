# Site logo in General settings

Adds a **Site logo** picker to **Settings → General**, so the logo can be changed
without opening the site editor.

## Where it appears

Settings → General, under the standard fields. *Select logo* opens the media
library; *Remove logo* clears it. The current logo is shown above the buttons.

Changes take effect on **Save Changes**, like every other field on that screen.

## It is the same logo, not a second one

The field is a second door onto the theme's `custom_logo` theme mod — not a
separate place to store a logo. Saving writes the theme mod, and reading prefers
it.

That means:

- Everything already reading the site logo keeps working untouched:
  `get_custom_logo()`, the `core/site-logo` block, the customizer, the site
  editor.
- Changing the logo in the site editor changes what this field shows, and the
  other way round.
- Removing the logo here removes the theme mod entirely, so the theme falls back
  exactly as it would if a logo had never been set.

## Why it exists

Clients look for the logo in Settings. On a block theme it lives in the site
editor, behind a template part, which is a long way to travel for a file swap —
and a place where it is easy to change something else by accident.

## Options

| Option | Purpose |
| --- | --- |
| `isudev_library_website_logo` | Mirror of the chosen attachment ID. Read `get_theme_mod( 'custom_logo' )` instead — that is the value everything else uses. Deleted when the plugin is uninstalled. |

## When the field does not work

- **The buttons do nothing.** The media library scripts did not load. Another
  plugin printing a JavaScript error on Settings → General will stop them; check
  the browser console.
- **The logo does not appear on the site.** The theme has to render it. A block
  theme needs a `core/site-logo` block in a template part; a classic theme needs
  `the_custom_logo()` and `add_theme_support( 'custom-logo' )`.
- **The field shows a logo the site editor does not.** The theme mod is
  per-theme. Switching themes leaves the old theme's logo behind and this field
  falls back to the stored option until a logo is saved for the new theme.
