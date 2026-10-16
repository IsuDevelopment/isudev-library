# Remove core patterns

The pattern inserter mixes the theme's patterns with ones WordPress brings
along: a handful bundled with core (query loops, navigation overlays) and a
few dozen downloaded from the WordPress.org pattern directory (*Featured*
and the *core* keyword). They use none of the theme's design and are the
patterns editors most often insert by mistake. This extension removes them.

## What it changes

`remove_theme_support( 'core-block-patterns' )` at `init` priority 0. Core
reads that support in three places, so all three go:

- **Bundled patterns** — `core/query-standard-posts`, `core/query-grid-posts`,
  `core/navigation-overlay…` and the rest of the list in
  `_register_core_block_patterns_and_categories()` (`init` 10).
- **Remote core patterns** — `_load_remote_block_patterns()`, the directory's
  *core* keyword.
- **Remote featured patterns** — `_load_remote_featured_patterns()`, the
  directory's *Featured* category.

The remote ones are fetched when the editor first asks
`/wp/v2/block-patterns/patterns`; with the support gone that request no
longer calls api.wordpress.org for them.

## What stays

- **Theme patterns** — files in the theme's `patterns/` and anything it
  registers with `register_block_pattern()`.
- **Synced and unsynced patterns** created on the site (*My patterns*).
- **Patterns the theme requests by slug** from the directory through
  `"patterns": [ … ]` in its `theme.json`
  (`_register_remote_theme_patterns()`). The theme asked for them, so they
  are kept. To block every remote pattern, add:

  ```php
  add_filter( 'should_load_remote_block_patterns', '__return_false' );
  ```

- **Pattern categories.** Core's categories (Banners, Buttons, Columns, …)
  stay registered: themes file their own patterns under them. A category
  with no patterns left is not shown in the inserter.

No filters and no `isudev.json` settings of its own.

## What it relies on

The `core-block-patterns` theme feature (added by core in
`wp-includes/block-patterns.php`), and its readers listed above. A theme
calling `add_theme_support( 'core-block-patterns' )` on `init` after
priority 0 would bring the patterns back.

## How to check it still works

Inserter → Patterns: no *Featured* category, no query-loop or navigation
overlay patterns from WordPress. `wp-json/wp/v2/block-patterns/patterns`
(logged in) lists no pattern whose `source` is `core`,
`pattern-directory/core` or `pattern-directory/featured`.
