# Disable Openverse

The block inserter's **Media** tab lists an **Openverse** category next to
Images, Videos and Audio. Picking an image there inserts it straight from
openverse.org's CDN — hotlinked, with an attribution caption the editor may
delete, and outside the Media Library. This extension removes that category.

## What it changes

- **Editor** (post, site and widget editors): the Openverse category is gone
  from Inserter → Media. Images, Videos and Audio from the Media Library stay.

Nothing else: no front-end change, no REST change.

## What it does not do

- Images already inserted from Openverse keep their remote URL and caption.
  Find them by searching post content for `openverse` or the CDN host, and
  re-upload them if they should live in the Media Library.
- There is no endpoint to close: the editor queries Openverse from the
  browser, not through WordPress. With the category gone it makes no
  Openverse requests at all.

No filters and no `isudev.json` settings.

## What it relies on

The `block_editor_settings_all` filter and the block-editor setting
`enableOpenverseMediaCategory` (default `true` in `@wordpress/block-editor`,
passed through by `@wordpress/editor`). If a future core renames the setting
the category reappears — search `wp-includes/js/dist/block-editor.js` for
`Openverse`.

## How to check it still works

Open the inserter → **Media**: no *Openverse* entry, and the network panel
shows no request to `api.openverse.org` while browsing the tab.
