# Post cards — design

Status: implemented in 1.19.0 (2026-11-02). Living reference: `guides/post-cards.md`; where this spec and the guide differ, the guide is current. Replaces T2 Featured Content (`t2/featured-*`)
in new projects. No migration of T2 content.

## Why not T2

Verified against T2 8.36.0 source:

- Editor preview parses the card template into a nested `BlockEditorProvider` +
  `<Disabled><BlockList>` (`featured-single-post/edit.js`). The editor shows each
  block's edit preview, not the PHP result; PHP filters never run. The
  `__experimentalCardSSR` escape hatch is one `ServerSideRender` request per card
  and needs every custom attribute re-registered in PHP.
- `featured-query-post` has no preview at all ("Only supported frontend") and one
  `WP_Query` per slot (`posts_per_page = 1`). Dedupe is a request-wide `static $ids`,
  so a later layout can render empty.
- Front end injects post data with a global `render_block_context` filter around
  `do_blocks()`, leaking card attributes into every nested block.
- Template switching needs the same logic twice: JS `T2.FeaturedPost.template` and PHP
  `pre_get_template`. Query cards never receive card attributes.
- Fixed 12-column grid with global `col-N` classes; width-dependent looks are CSS hacks.
- The whole card is one `<a>`.

## Blocks

| Block | Role |
| --- | --- |
| `isudev/post-cards` | Wrapper. Grid (or shared slider), hand-picked + query slots, wrapper-level template and option defaults. `role="list"`. |
| `isudev/post-card` | One card. Post from its own `postId`, from a wrapper query slot, or from context (`postId`, `postType`) inside `core/post-template`. |
| `isudev/card-title`, `isudev/card-excerpt`, `isudev/card-slot` | Template building blocks that read card options from context. Core post blocks (`post-featured-image`, `post-date`, `post-terms` …) work too. |

`isudev/post-card` is **not** `parent`-restricted, so it works in `core/query`.

## Card source

- **Hand-picked:** `PostSelectorControl` / `PostPickerControl` from `@isudev/gutenberg`
  (bump to `0.2.0`). "Add posts" on the wrapper creates one card per picked post.
- **Query slot:** a card with `source: "query"`. The wrapper runs **one** query for all
  its slots (`posts_per_page = slot count`, excludes the wrapper's hand-picked IDs and
  the current post) and assigns posts in slot order. Page-wide dedupe ("no repeats on
  this page") is an opt-in wrapper toggle, documented as previewed approximately in
  the editor.
- **Context:** inside `core/post-template`, no `postId` attribute → context.

## Templates

Resolved by `IsuDevLibrary\PostCards\resolve_template( $post, $variant, $options, $card )`:

1. filter `isudev_library/post_card/template` — `( string $slug, WP_Post $post, array $options, array $card ): string`, returns a slug
2. `{theme}/isudev/cards/{slug}.php` (child before parent, `locate_template`)
3. template part `card-{slug}` in area `isudev-card` — DB-customised version first, then
   `{theme}/parts/card-{slug}.html`
4. same lookups for `{post_type}`, then `default`
5. plugin default template

`slug` is `{post_type}-{variant}` when a variant is set, else `{post_type}`.

- **`.php`** — developer template. Receives `$args = [ 'post', 'options', 'card', 'classes' ]`.
  Fires `isudev_library/post_card/before`, `isudev_library/post_card/after` (and the template's own actions).
- **`.html`** — block markup in a template part, editable in the Site Editor (area registered
  via `default_wp_template_part_areas`). Rendered like `core/post-template`: each parsed block
  as `new WP_Block( $parsed, $context )`, context scoped to that card. May reference
  `core/pattern` (theme patterns are PHP, so translatable strings live there).
- One slug lives in one format. If both exist, `.php` wins and `tools/checks` / `_doing_it_wrong`
  reports it — otherwise Site Editor edits would be silently ignored.
- Variants are discovered from files. Labels come from a file header, read with
  `get_file_data()`: `Card: Simplified` (PHP docblock / HTML comment). The editor receives
  only the variant list (`slug`, `label`, `postType`), never template markup.
- In the Site Editor a card template has no post; core post blocks show placeholders.
  A preview-post picker there is out of scope for v1.

## Card options

One object attribute, `cardOptions`, on both `post-cards` and `post-card`, declared in
`block.json`. Themes never add block attributes.

```php
add_action( 'isudev_library/post_card/register_options', function ( $cards ) {
	$cards->add_option( 'dark', [ 'type' => 'boolean', 'label' => __( 'Dark card', 'theme' ) ] );
	$cards->add_option( 'hideExcerpt', [ 'type' => 'boolean', 'label' => __( 'Hide excerpt', 'theme' ), 'postTypes' => [ 'post' ] ] );
	$cards->add_option( 'customExcerpt', [ 'type' => 'text', 'control' => 'textarea', 'label' => __( 'Custom excerpt', 'theme' ), 'scope' => 'card' ] );
} );
```

- Types: `boolean`, `select` (`choices`), `text`. Keys: `label`, `help`, `default`,
  `postTypes`, `scope` (`card` | `cards` | `both`, default `both`), `control`.
- The registry is published to the editor once (`window.isudevPostCardOptions`, assigned);
  the inspector panel is generated from it and filtered by the card's post type.
  Query-slot cards show every option with a "applies when the post is X" help line.
- Resolution: card value → wrapper value → `default`. The UI offers "Inherit".
- Sanitised in PHP by type on render and in the preview endpoint.
- Resolved options reach:
  - blocks via context `isudev/cardOptions` (`usesContext`),
  - `.php` templates via `$args['options']`,
  - CSS via wrapper classes `has-card-{key}` (truthy booleans) and `has-card-{key}-{value}` (selects), keys kebab-cased,
  - any other block via filter `isudev_library/post_card/render_block` ( `$html, $block, $options, $post` ).
- Built-ins shipped by the library: `customTitle`, `customExcerpt`, `hideExcerpt`
  (read by `card-title` / `card-excerpt`). Custom image: phase 3.
- JS hooks only for UI: `isudevLibrary.postCard.optionControl` to swap one option's control.

## Rendering and editor preview

- Front end and editor use the same PHP path. Endpoint
  `POST isudev-library/v1/post-cards/render` takes a whole wrapper (cards, slots, options,
  variants) and returns HTML per card, so query slots resolve exactly as on the front end.
  `edit_posts` capability; read checks per post. Client cache keyed by
  `postId + variant + options + modified`.
- The editor injects the returned HTML into the card wrapper. Card and theme card CSS go
  through `wp_enqueue_block_style( 'isudev/post-card', … )` so they reach the iframe.
- "Edit this card's layout" (phase 3): copies the resolved `.html` template into the card's
  InnerBlocks; the card `providesContext` `postId`, `postType`, `isudev/cardOptions`, so core
  post blocks render natively, as in `core/post-template`.

## Layout and markup

- Card width per breakpoint via `ResponsiveControl` (same model as bento-grid), exposed as
  `--isudev-card-span`. Cards are `container-type: inline-size`; horizontal-at-wide is an
  `@container` rule, not a width class.
- Optional carousel through `IsuDevLibrary\Utils\Slider\render()`.
- Card wrapper classes: `isudev-post-card is-post-type-{pt} is-variant-{v}` + `{tax}-{slug}`
  for public taxonomies + option classes. Filter `isudev_library/post_card/classes`.
- Link on the title only, stretched over the card with `::after`; no wrapping `<a>`, no nested
  links. `role="listitem"` when the wrapper is a list.

## Documentation for agents

The library is mostly used and extended by agents, in this repo and in consuming projects.
That is a requirement of this feature and of the project as a whole:

- **Feature docs:** `src/blocks/post-cards/README.md`, `post-card/README.md` (operator) and
  `guides/post-cards.md` (theme developer: every hook with a copy-paste example, template
  resolution order, option registry, recipes: per-type template, variant, dark toggle,
  custom excerpt, template switch by option, query loop usage, troubleshooting).
- **Ship guides:** drop `/guides` from `.distignore` (and update `tools/checks/60-dist.php`)
  so consuming projects have them.
- **Consumer agent guide:** `agents/README.md`, shipped. Hand-written preamble (what the
  library is, golden rules for consumers, where theme files go, `isudev.json`) + generated
  catalog: blocks, extensions, every `isudev/*` PHP hook and JS filter, `isudev.json` keys,
  each with its README path.
- **Generator + drift check:** `tools/agents-catalog.php` builds the catalog from block.json,
  descriptors and a scan of `apply_filters`/`do_action` calls; `tools/checks/` fails when the
  catalog is stale or a hook found in code is not documented in any README/guide.
- **Init in consuming projects:** `php <plugin>/bin/agents-init.php` (plain PHP, no WP
  bootstrap) copies the guide to `.agents/vendor/isudev-library.md` and upserts a marked block
  in the project's `AGENTS.md` — same contract as `npx @isudev/gutenberg init`.

## Phases

1. `@isudev/gutenberg@0.2.0`; resolver (`.php` + template parts + default); `post-card`
   (hand-picked + context); option registry with built-ins; preview endpoint; docs above
   for what ships.
2. `post-cards` wrapper: responsive spans, query slots with one query, wrapper defaults,
   variants select, optional slider.
3. Detach to InnerBlocks; custom image override; Site Editor preview post.
4. Project-wide agent docs: `agents/README.md`, generator, drift check, `bin/agents-init.php`
   (can start in parallel with phase 1).
