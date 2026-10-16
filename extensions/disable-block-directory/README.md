# Disable the block directory

When the block inserter finds no match, core offers blocks from the
WordPress.org block directory and installs them as plugins with one click.
On a managed site that is a plugin install nobody reviewed. This extension
switches that off.

## What it changes

- **Editor.** The *Available to install* section of the inserter search is
  gone: core's `wp-block-directory` script and style are no longer enqueued
  (`wp_enqueue_editor_block_directory_assets` is unhooked from
  `enqueue_block_editor_assets`). The same applies to the site and widget
  editors.
- **REST.** `GET /wp/v2/block-directory/search` is no longer registered, so it
  answers `404 rest_no_route` even to a script that calls it directly.

## What it does not do

- It does not touch plugin installation. **Plugins → Add New** and
  `POST /wp/v2/plugins` keep working for anyone with `install_plugins`; to
  forbid installs altogether use `DISALLOW_FILE_MODS`.
- Blocks already installed from the directory stay installed and active.

No filters and no `isudev.json` settings.

## What it relies on

The core function name `wp_enqueue_editor_block_directory_assets` (hooked in
`wp-includes/default-filters.php` at the default priority) and the route
`/wp/v2/block-directory/search` from `WP_REST_Block_Directory_Controller`.
A renamed function in a future core would bring the inserter section back —
check `default-filters.php` for `block_directory`.

## How to check it still works

- In the post editor, search the inserter for a word no installed block
  matches (e.g. `qwertyuiop`): no *Available to install* list, no "Search
  the block directory" request in the network panel.
- `curl -i -u user:app-password 'https://example.test/wp-json/wp/v2/block-directory/search?term=map'`
  answers `404` with `rest_no_route`.
