# Card Slot — `isudev/card-slot`

A named place in a card template that the theme fills from PHP. Renders nothing when the filter returns nothing.

A building block for card templates (theme `parts/card-*.html`, Site Editor →
Patterns → Cards) and for cards whose layout was detached. Theme guide:
[`guides/post-cards.md`](../../../guides/post-cards.md).

## Inserting it

Block inserter → **Theme** → *Card Slot*, inside a card template or a detached card.
On or off together with *Post Card*; it cannot be disabled on its own.

## Settings

**Slot name** and **HTML element** (sidebar → *Slot*). Filled by `isudev_library/post_card/slot/{name}` (`$html, $post, $options, $attributes`); output passes `wp_kses_post()`.

## Theme-controlled settings

No `isudev.json` keys. Reads `postId`, `postType` and `isudev/cardOptions` from block context.

## On the frontend

`{div|p|span}.isudev-card-slot.isudev-card-slot--{name}`

## What it relies on

Block context from `isudev/post-card` (or a Query Loop / the current post when used elsewhere).

## How to check it still works

Put it in `parts/card-post.html`; a card for a post shows it on the page and in the editor preview.

## Troubleshooting

**Nothing renders.** There is no post in context, or the data it shows is empty
for that post. In the Site Editor it shows a placeholder: a template has no post.
