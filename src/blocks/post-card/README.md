# Post Card — `isudev/post-card`

One post as a card, drawn from the theme's card template for its post type.
Works inside [Post Cards](../post-cards/README.md), on its own, and inside a
Query Loop's Post Template. Replaces T2 Featured Single / Query / Template Post.

Theme side (templates, options, hooks): [`guides/post-cards.md`](../../../guides/post-cards.md).

## Inserting it

Block inserter → **Theme** → *Post Card*, then **Choose post**. Inside a Query
Loop it needs nothing: it shows the loop's post.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Source** | Sidebar → *Card* (inside Post Cards) | Hand-picked post | Or *Next post from the query*: filled from Post Cards' query. |
| **Choose / Replace post** | Sidebar → *Card*, or toolbar | none | The post shown. Only published posts render. |
| **Template** | Sidebar → *Card* | Same as Post Cards / default | A variant the theme provides for this post type. |
| **Edit this card's layout** | Sidebar → *Card* | off | Copies the template into this card as blocks you can change, for this card only. *Use the template again* goes back. Not available for PHP templates. |
| **Columns spanned** | Sidebar → *Width*, per breakpoint (inside Post Cards) | 1 | Capped at Post Cards' columns. |
| **Card options** | Sidebar → *Card options* | theme-defined | Built-ins: *Custom title*, *Custom excerpt*, *Hide excerpt*, *Custom image*, plus whatever the theme registers. *Inherit* takes Post Cards' value. |

The canvas shows the card exactly as the page renders it (same PHP); it is not
editable in place unless the layout was detached.

## Theme-controlled settings

No `isudev.json` keys. Templates, variants and options come from the theme.

## On the frontend

`div.isudev-post-card.is-post-type-{pt}[.is-variant-{v}][.{taxonomy}-{term}…][.has-card-{option}…]`
(plus `role=listitem` inside a Post Cards grid) wrapping `div.isudev-post-card__inner`.
Filter: `isudev_library/post_card/classes`.

## What it relies on

`is_post_publicly_viewable()`, the template resolver (`includes/post-cards/templates.php`),
`WP_Block` context (`postId`, `postType`, `isudev/cardOptions`), `skip_inner_blocks`,
REST `POST isudev-library/v1/post-cards/preview`.

## How to check it still works

Pick a post; the editor card matches the page. Add `parts/card-post-wide.html`
to the theme with `<!-- Card: Wide -->`: *Wide* appears under *Template* and,
chosen, renders that part on both sides.

## Troubleshooting

**The card renders nothing.** The post is not published or was deleted, or the
template output is empty.

**The card shows the page's own post.** It cannot: outside a Query Loop a card
without a chosen post renders nothing.

**Edit this card's layout is greyed out or warns.** The preview has not loaded
yet, or the template is a PHP file.
