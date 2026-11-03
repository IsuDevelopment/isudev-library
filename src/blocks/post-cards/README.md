# Post Cards — `isudev/post-cards`

A grid or carousel of post cards. Each card is a hand-picked post or a *query
slot* that the block fills with the next matching post. Replaces T2 Featured
Content Layout.

Theme side (templates, options, hooks): [`guides/post-cards.md`](../../../guides/post-cards.md).

## Inserting it

Block inserter → **Theme** → *Post Cards*. It starts with three query slots
showing the latest posts.

- **Toolbar → Add posts** opens the post picker; every chosen post becomes a
  card. Empty hand-picked cards are filled first.
- **Toolbar → Add query slot** adds a card filled from the query.
- Reorder cards by dragging them; remove one like any block.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Display as** | Sidebar → *Layout* | Grid | Grid, or Carousel (shared slider). The editor always shows a grid. |
| **Columns** | Sidebar → *Layout*, per breakpoint | 3 / 2 / 1 | Grid columns, or cards in view for the carousel. |
| **Accessible name** | Sidebar → *Layout* | none | Screen-reader name of the list or carousel, e.g. "Latest news". |
| **Post type, Filter by, terms, Order by, Skip first posts, Leave out the current post** | Sidebar → *Query slots* | Posts, newest, current post left out | What query slots are filled with. One query per block; hand-picked posts are skipped. |
| **Template for every card** | Sidebar → *Cards* | Default for each post type | A template variant from the theme. A card whose post type has no such variant uses its default. Each card can override it. |
| **No repeats on this page** | Sidebar → *Cards* | off | Query slots also skip posts shown by cards earlier on the page. The editor skips earlier hand-picked posts only. |
| **Options for every card** | Sidebar | theme-defined | Defaults for the theme's card options (scope `both` or `cards`); each card can override `both` options. |

Card width (columns spanned) is set on each card.

## Theme-controlled settings

No `isudev.json` keys. The theme provides the card templates and options (see
the guide) and can style through `--wp--custom--isudev-post-cards--gap`.

## On the frontend

`div.isudev-post-cards.is-layout-{grid|slider}` with `--isudev-cards-columns*`
custom properties, then `div.isudev-post-cards__grid[role=list]` or the slider.
Cards that render nothing are left out; a block whose cards are all empty
renders nothing.

## What it relies on

`skip_inner_blocks` (cards are rendered by this block with their slot's post in
context), `WP_Query` with `fields => ids`, the shared slider
(`includes/utils/slider.php`, `src/utils/slider/`), filter
`isudev_library/post_cards/query_args`.

## How to check it still works

`npm run test:php` (`tools/checks/310-post-cards.php`); then on a page with three
query slots and one hand-picked post: the page shows four distinct posts, the
hand-picked one is never repeated by a slot, and the editor preview shows the
same posts.

## Troubleshooting

**A query slot is empty.** Not enough matching posts after skipping hand-picked
ones, the current post and the offset. The editor says "No post left for this query slot".

**Slots show the wrong post type.** Check *Query slots → Post type*; it is not
inherited from the page.

**Two blocks on a page show the same posts.** Turn on *No repeats on this page*
on the later one.
