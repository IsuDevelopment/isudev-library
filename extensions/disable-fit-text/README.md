# Disable fit text

**Fit text** (Typography → *Fit text* on Heading and Paragraph) makes a
script on the page grow or shrink the text until it fills its container. It
ignores the theme's type scale, forces the text onto one line and can end up
at any size from 0 to 2400px. This extension removes the option and makes
blocks already saved with it render like ordinary text.

## What it changes

- **Editor.** The *Fit text* toggle is gone from the Typography panel of every
  block that declares `supports.typography.fitText` — not a fixed list, so a
  third-party block with the support loses it too. New content cannot use it.
- **Site.** For blocks saved with fit text on:
  - the `fitText` attribute is dropped before rendering (`render_block_data`),
    so core adds none of its Interactivity directives and does not enqueue the
    `@wordpress/block-editor/utils/fit-text-frontend` script module;
  - the saved `has-fit-text` class is removed from the block's first tag —
    core styles it `white-space: nowrap !important`;
  - the module is dequeued as well, in case anything else enqueued it.
  The text then renders at the block's or the theme's normal size.
- **Server registry.** `supports.typography.fitText` is set to `false` on the
  server-side block type too (`register_block_type_args`), so the REST
  `/wp/v2/block-types` response agrees with the editor.

## What it does not do

- **It does not rewrite saved posts.** Opening an old post does not mark it as
  changed. When it is next saved for another reason, `fitText` disappears from
  the block comment, and `has-fit-text` stays in the markup as an ordinary
  custom class (Advanced → *Additional CSS class(es)*). That leftover class is
  harmless: it is stripped on the site, and in the editor canvas a small style
  cancels core's `nowrap`. Remove it from the field to tidy up.
- A block that was fit-text has no font size of its own — core cleared it when
  fit text was switched on. Pick a size again if the default is not right.
- Switching the extension off later does not bring fit text back to posts
  saved while it was on.

No filters and no `isudev.json` settings.

## What it relies on

- Core `wp_render_typography_support()` (`wp-includes/block-supports/typography.php`,
  `render_block` priority 10): reads `attrs.fitText`, adds
  `data-wp-interactive`, `data-wp-context---core-fit-text`,
  `data-wp-init---core-fit-text`, `data-wp-style--font-size` and enqueues the
  script module. This extension drops the attribute at `render_block_data`
  priority 5 and strips leftovers at `render_block` priority 11.
- Editor: `@wordpress/block-editor`'s `hooks/fit-text` checks
  `hasBlockSupport( name, 'typography.fitText' )` for the control, the
  attribute and the class. `editor.js` sets the support to `false` on
  `blocks.registerBlockType` at priority 5, before core's
  `core/fit-text/addAttribute` (10). Core blocks register their supports from
  client-side block.json, which overrides the server definition, so the PHP
  filter alone would not hide the control.
- The class name `has-fit-text` and the `core/fit-text::` directive prefix. A
  rename in core shows up as old blocks scaling again — compare with
  `typography.php` around `fitText`.

## How to check it still works

- Editor: select a Heading → Typography panel → ⋮ menu has no *Fit text*.
- Site: on a page that had a fit-text heading, the `<h*>` has no
  `has-fit-text` class and no `data-wp-*fit-text*` attributes, and the page
  source has no `fit-text-frontend` module.
