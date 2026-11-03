# Post cards — theme guide

How a theme designs, extends and switches post cards. Operator settings live in
the block READMEs: [`post-cards`](../src/blocks/post-cards/README.md),
[`post-card`](../src/blocks/post-card/README.md) and the building blocks
[`card-title`](../src/blocks/card-title/README.md),
[`card-excerpt`](../src/blocks/card-excerpt/README.md),
[`card-image`](../src/blocks/card-image/README.md),
[`card-slot`](../src/blocks/card-slot/README.md).

Design record: `docs/superpowers/specs/2026-11-02-post-cards-design.md` (not shipped).

## The model in one paragraph

A card's look is **one template per post type** (optionally per variant), owned
by the theme. Editors pick posts and widths; they do not style cards. The same
PHP renders the front end and the editor preview, so a PHP filter changes both.
Theme-defined **card options** (toggles, selects, text, image) are the only
per-card knobs; templates and blocks read them.

## Templates

### Where they live and which one wins

For a card showing post type `{pt}` with variant `{v}`, slugs are tried in this
order, and for each slug a PHP file before a template part:

1. the slug returned by `isudev_library/post_card/template` (default `{pt}-{v}`, or `{pt}`)
2. `{pt}-{v}`, then `default-{v}` (only with a variant)
3. `{pt}`
4. `default`
5. the plugin's own default (`includes/post-cards/default-template.html`)

| Format | File | Editable in the Site Editor | Use for |
| --- | --- | --- | --- |
| PHP | `{theme}/isudev/cards/{slug}.php` | no | Anything programmatic; hooks; markup blocks cannot express. |
| Block markup | `{theme}/parts/card-{slug}.html` | yes — Patterns → Template parts → **Cards** | Designs an editor may later adjust. |

- Child theme first, then parent; the first match wins.
- A Site Editor edit of a part is saved in the database and wins over the file
  automatically (core template part behaviour). "Clear customizations" reverts it.
- **One slug, one format.** If `isudev/cards/post.php` and `parts/card-post.html`
  both exist, the PHP file wins and `_doing_it_wrong()` reports it.
- Parts are listed under the **Cards** area (`isudev-card`) without a
  `theme.json` entry: the plugin declares every `parts/card-*.html` for you. A
  `templateParts` entry the theme writes itself is left as written.
- A part created from scratch in the Site Editor counts once its slug is
  `card-{slug}` and its area is Cards.

### Labels and variants

The first line of a template can name it; the editor shows that name in the
*Template* selects and the Site Editor shows it as the part's title:

```html
<!-- Card: Wide image -->
```

```php
<?php
/**
 * Card: Simplified
 */
```

Without a label the variant name is humanised (`wide-image` → "Wide image").
Variants are discovered from file names: `person-simple.php` is post type
`person`, variant `simple`. Post types containing hyphens resolve by the longest
registered prefix (`customer-case-wide` → `customer-case` / `wide`).
`default-{v}` gives every post type that variant.

### A block markup template

```html
<!-- Card: Default -->
<!-- wp:isudev/card-image {"aspectRatio":"4/3"} /-->

<!-- wp:group {"className":"isudev-post-card__body","layout":{"type":"default"}} -->
<div class="wp-block-group isudev-post-card__body">
<!-- wp:post-terms {"term":"category"} /-->
<!-- wp:isudev/card-title {"level":3} /-->
<!-- wp:isudev/card-excerpt {"words":20} /-->
<!-- wp:isudev/card-slot {"name":"meta","tagName":"p"} /-->
</div>
<!-- /wp:group -->
```

- Every block gets `postId`, `postType` and `isudev/cardOptions` in context, the
  way `core/post-template` passes them. Core post blocks (`post-terms`,
  `post-date`, `post-author-name` …) work as they do in a Query Loop.
- Translatable strings: put them in a theme pattern (`patterns/*.php`, where
  `__()` works) and reference it with `<!-- wp:pattern {"slug":"theme/card-more"} /-->`.
- Do not wrap the template in a link; `card-title` stretches its link over the
  card. A second link (`post-terms`, a button) stays clickable above it.

### A PHP template

```php
<?php
/**
 * Card: Simplified
 *
 * @var array $args { post: WP_Post, options: array, variant: string, attributes: array, template: string }
 */
$card = $args['post'];
?>
<h3 class="isudev-card-title">
	<a class="isudev-card-title__link isudev-post-card__link" href="<?php echo esc_url( get_permalink( $card ) ); ?>">
		<?php echo esc_html( $args['options']['customTitle'] ?? get_the_title( $card ) ); ?>
	</a>
</h3>
<?php if ( empty( $args['options']['hideExcerpt'] ) ) : ?>
	<p><?php echo esc_html( get_the_excerpt( $card ) ); ?></p>
<?php endif; ?>
```

- Loaded with `load_template()`; the card post is also the global `$post`
  (restored afterwards), so template tags work.
- Add `isudev-post-card__link` to the main link to make the whole card clickable.
- Output nothing (an empty string) and the card is not rendered at all.

## Card options

Options are the per-card (and per-wrapper) knobs. A theme registers them once;
the editor builds the inspector panel from the registry, PHP sanitises and
resolves them, and templates read them.

```php
add_action( 'isudev_library/post_card/register_options', function ( $cards ) {
	$cards->add_option( 'dark', array(
		'type'  => 'boolean',
		'label' => __( 'Dark card', 'theme' ),
	) );
	$cards->add_option( 'size', array(
		'type'    => 'select',
		'label'   => __( 'Size', 'theme' ),
		'choices' => array( 'compact' => __( 'Compact', 'theme' ), 'large' => __( 'Large', 'theme' ) ),
	) );
	$cards->add_option( 'simplified', array(
		'type'      => 'boolean',
		'label'     => __( 'Simplified card', 'theme' ),
		'postTypes' => array( 'person' ),
	) );
	$cards->remove_option( 'customImage' ); // Built-ins can go too.
} );
```

| Key | Values | Default |
| --- | --- | --- |
| `type` | `boolean`, `select`, `text`, `media` | required |
| `label`, `help` | strings shown in the inspector | key, '' |
| `choices` | `select` only: `[ value => label ]` or `[ [ 'value', 'label' ] ]` | required for select |
| `default` | a valid value for the type | `false` for boolean, else none |
| `postTypes` | only cards of these post types get the option | all |
| `scope` | `card` (card only), `cards` (Post Cards only, applies to all its cards), `both` | `both` |
| `control` | boolean `toggle`; select `select`/`radio`; text `text`/`textarea`; media `media` | first |

Keys are `[A-Za-z][A-Za-z0-9]*`. A key registered twice keeps the last definition.

**Cascade:** card value → Post Cards value → `default`. On a card, an option with
scope `both` offers *Inherit*; a missing key in the stored `cardOptions` means inherit.

**Built-in options** (read by the building blocks; remove them if a theme does not want them):

| Key | Type | Scope | Read by |
| --- | --- | --- | --- |
| `customTitle` | text | card | `card-title` |
| `customExcerpt` | text (textarea) | card | `card-excerpt` |
| `hideExcerpt` | boolean | both | `card-excerpt` |
| `customImage` | media | card | `card-image` |

### Where a resolved option shows up

| Reader | How |
| --- | --- |
| A block in the template | `"usesContext": ["isudev/cardOptions"]`, then `$block->context['isudev/cardOptions']['dark']` |
| A PHP template | `$args['options']['dark']` |
| CSS | `has-card-{key}` on the card for a true boolean, `has-card-{key}-{value}` for a select. Keys are kebab-cased: `hideExcerpt` → `has-card-hide-excerpt`. |
| Any block's output | `isudev_library/post_card/render_block` (below) |

## Recipes

### Dark card toggle

Register `dark` (above), then CSS only:

```css
.isudev-post-card.has-card-dark { background: var(--wp--preset--color--contrast); color: var(--wp--preset--color--base); }
```

### Switch template by an option (the T2 `T2.FeaturedPost.template` case)

```php
add_filter( 'isudev_library/post_card/template', function ( $slug, $post, $options ) {
	if ( 'person' === $post->post_type && ! empty( $options['simplified'] ) ) {
		return 'person-simple'; // isudev/cards/person-simple.php or parts/card-person-simple.html
	}
	return $slug;
}, 10, 3 );
```

One PHP filter covers the front end and the editor preview. There is no JS twin
to keep in step.

### Let editors choose a template

Add a file per variant (`parts/card-post-wide.html` with `<!-- Card: Wide -->`).
It appears in the card's *Template* select and in *Template for every card* on
Post Cards. No code.

### Custom excerpt on one card

Built in: select the card → *Card options* → *Custom excerpt*. A theme block can
read the same value from context.

### Inject something into every card

```php
add_filter( 'isudev_library/post_card/slot/meta', function ( $html, $post, $options ) {
	return esc_html( get_the_date( '', $post ) ) . ' · ' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) );
}, 10, 3 );
```

with `<!-- wp:isudev/card-slot {"name":"meta"} /-->` in the template. Or, without
touching templates, `isudev_library/post_card/before` / `after` (echo).

### Cards in a Query Loop

Put *Post Card* inside the Query Loop's *Post Template*. Each card shows the
loop's post with the same template and options. Width and query slots belong to
Post Cards only.

## Hooks

| Hook | Type | Arguments | Notes |
| --- | --- | --- | --- |
| `isudev_library/post_card/register_options` | action | `Option_Registry $cards` | `add_option( $key, $def )`, `remove_option( $key )`. Runs once per request, on first use. |
| `isudev_library/post_card/template` | filter | `string $slug, WP_Post $post, array $options, array $attributes` | Return a slug; falls back to the post type's and the default template. |
| `isudev_library/post_card/classes` | filter | `string[] $classes, WP_Post $post, array $options, string $variant` | Card wrapper classes. |
| `isudev_library/post_card/before` | action | `WP_Post $post, array $args` | Echo inside the card, before the template. |
| `isudev_library/post_card/after` | action | `WP_Post $post, array $args` | Echo inside the card, after the template. |
| `isudev_library/post_card/render_block` | filter | `string $html, array $parsed_block, array $options, WP_Post $post` | Every block rendered inside a block-markup card. |
| `isudev_library/post_card/slot/{name}` | filter | `string $html, WP_Post $post, array $options, array $attributes` | Fills `isudev/card-slot`. Output goes through `wp_kses_post()`. |
| `isudev_library/post_card/excerpt` | filter | `string $excerpt, int $post_id, array $options, array $attributes` | Plain text from `card-excerpt`. |
| `isudev_library/post_cards/query_args` | filter | `array $args, array $query, array $wrapper_attributes` | One query per Post Cards block. Keep `fields => ids`. |
| `isudevLibrary.postCard.optionControl` | JS filter | `Element control, props, level` | Replace one option's inspector control. `props`: `def, value, inherited, canInherit, onChange`. |

## Markup and CSS

```html
<div class="isudev-post-cards is-layout-grid" style="--isudev-cards-columns:3;--isudev-cards-columns-tablet:2;--isudev-cards-columns-mobile:1">
	<div class="isudev-post-cards__grid" role="list" aria-label="…">
		<div class="isudev-post-card is-post-type-post is-variant-wide category-news has-card-dark" role="listitem" style="--isudev-card-span:2;…">
			<div class="isudev-post-card__inner"> …template… </div>
		</div>
	</div>
</div>
```

- The card is `container-type: inline-size`, so a template can change layout by
  the card's own width: `@container (min-width: 36rem) { .isudev-post-card__inner { … } }`.
  The default template already goes image-beside-text from 36rem.
- Grid breakpoints match the editor's: tablet ≤ 1023px, mobile ≤ 767px.
- Gap: `--wp--custom--isudev-post-cards--gap` (theme.json `settings.custom.isudev-post-cards.gap`),
  default 1.5rem. Card inner gap: `--wp--custom--isudev-post-card--gap`, default 1rem.
- Carousel layout: `isudev-post-cards__slider` (the shared slider, `guides/slider.md`);
  cards per view = the columns for that breakpoint.
- Editor: the card wrapper also has `is-editor-preview`. The preview HTML is the
  PHP output; card CSS must be a block style (`wp_enqueue_block_style( 'isudev/post-card', … )`
  or theme.json/`style.css` loaded in the editor) to reach the iframe.

## What this relies on (and does not own)

- Core: `WP_Block` with available context (as `core/post-template`),
  `skip_inner_blocks` on the block type (as `core/post-template`), `get_block_template()` /
  `get_block_templates()` for parts, the `default_wp_template_part_areas` and
  `wp_theme_json_data_theme` filters, `load_template()`, `is_post_publicly_viewable()`.
- `@isudev/gutenberg` ≥ 0.2: `PostPickerControl`, `ResponsiveControl`,
  `MediaPickerControl`, `MediaPreview`, `useCurrentPostId`.
- REST: `POST isudev-library/v1/post-cards/preview` (`edit_posts`), the editor's only data source for cards.

## Troubleshooting

- **A card is missing on the page.** The post is not published (drafts, private
  and password-protected posts never render), or a query slot ran out of posts.
  The editor says which.
- **The wrong template is used.** Check the slug order above; a PHP file shadows
  a part of the same slug (see the `_doing_it_wrong` notice with `WP_DEBUG`).
  Check the filter `isudev_library/post_card/template`.
- **A theme part does not show under Cards in the Site Editor.** The file must be
  `parts/card-{slug}.html`. If theme.json already declares it with another area,
  that declaration wins.
- **An option does not appear in the inspector.** Its `postTypes` exclude the
  card's post type, its `scope` excludes the level, or the definition is invalid
  (`add_option()` returns false).
- **Editor and page differ.** Card CSS is not loaded in the editor iframe, or
  the template branches on `is_front_page()` / the queried object — the preview
  runs in a REST request where those are meaningless.
- **Query slots repeat posts from another Post Cards block.** Turn on *No repeats
  on this page* on the later block. It only sees cards rendered before it. The
  editor preview skips posts hand-picked above the block, but not posts that
  earlier blocks' query slots took, so there a slot can still show a repeat.
