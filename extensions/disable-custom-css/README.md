# Disable custom CSS

WordPress lets an administrator type CSS in three places: the Customizer's
**Additional CSS**, **Styles → Additional CSS** in the site editor (Global
Styles), and **Advanced → Additional CSS** on a single block. CSS typed there
lives in the database, outside the theme's repository and review, and is the
usual cause of "the design broke after the update". This extension makes all
three simply not work: the fields are gone, and CSS already saved in them
stops loading.

## What it changes

- **Capability.** `edit_css` is mapped to `do_not_allow` for every user,
  administrators and super admins included (`map_meta_cap`). That one switch
  removes, in core:
  - Customizer → **Additional CSS** (section capability `edit_css`);
  - Site editor → Styles → ⋮ → **Additional CSS** (the Global Styles REST
    response no longer carries `wp:action-edit-css`);
  - Block → Advanced → **Additional CSS** (`canEditCSS` is `false` in the
    editor settings).
- **Output of CSS already saved** — user-made CSS only:
  - Customizer CSS: `wp_get_custom_css` returns `''`. Classic themes print no
    `<style id="wp-custom-css">`; block themes no longer append it to
    `global-styles-inline-css`; the editor no longer loads it either.
  - Global Styles CSS: the `css` keys (root, per block, per block style
    variation, elements, breakpoints) are removed from the **user** origin on
    `wp_theme_json_data_user`, so they never reach `wp_get_global_stylesheet()`
    on the site or in the post editor.
  - Per-block CSS: the block's `style.css` attribute is dropped on
    `render_block_data` (priority 9, before core's 10), so core neither prints
    the `wp-block-custom-css` styles nor adds `has-custom-css wp-custom-css-*`
    classes.

## What keeps working: theme CSS

`styles.css` and `styles.blocks.<block>.css` in the **theme's own**
`theme.json` (and in a parent theme's) are the theme author's code, versioned
with the theme. They are a different origin (`theme`) from what an editor
saves in Global Styles (`custom`) and are left alone. So are theme
stylesheets, `wp_add_inline_style()` and block stylesheets.

One overlap: picking a theme **style variation** (`styles/*.json`) in the
site editor copies it into the user origin. A `css` key inside that
variation is therefore stripped too. Put variation-specific CSS in the
theme's stylesheet instead.

## What it does not do

- **Nothing is deleted on its own.** The Customizer CSS (`custom_css` post),
  the Global Styles post and block attributes keep their CSS in the database;
  switching the extension off brings it all back. Two core behaviours do
  delete, because the user now lacks `edit_css`:
  - **Saving a post** that has per-block CSS removes that CSS from the post
    (core's `wp_strip_custom_css_from_blocks`). The editor shows a warning
    notice saying so when such a post is opened.
  - Saving Global Styles as a user without `unfiltered_html` drops its CSS
    (core's kses for global styles).
- **Editor canvases still preview old CSS.** Per-block CSS on an already saved
  block, and Global Styles CSS in the **site editor**, are applied by the
  editor's JavaScript from the raw data. The published site does not load
  them.
- `DISALLOW_UNFILTERED_HTML` already denies `edit_css`; on such a site only
  the output part of this extension is new.

No filters and no `isudev.json` settings.

## What it relies on

Core 7.x: the `edit_css` meta capability (`capabilities.php`), `canEditCSS`
in `block-editor.php`, `wp_get_custom_css` (`theme.php`, used by
`wp_custom_css_cb()` on `wp_head` 101, by `wp_enqueue_global_styles()` for
block themes and by the editor settings), `wp_theme_json_data_user`
(`WP_Theme_JSON_Resolver::get_user_data()`), and the custom-CSS block support
(`wp-includes/block-supports/custom-css.php`) reading `attrs.style.css` on
`render_block_data` 10. A new place core stores user CSS in would need adding
here.

## How to check it still works

- As an administrator: Customizer has no *Additional CSS*; Site editor →
  Styles → ⋮ has no *Additional CSS*; a block's Advanced panel has no
  *Additional CSS* field.
- On a site with saved Customizer or Global Styles CSS: the page source has no
  `wp-custom-css` style and the saved rules are absent from
  `global-styles-inline-css`, while rules from the theme's `theme.json`
  `styles.css` are still there.
- A block with saved custom CSS renders without `has-custom-css` and without
  a `wp-block-custom-css-inline-css` style.
