# Card Title — `isudev/card-title`

The card's title, linked to the post. The link is stretched over the whole card, so the card is one click target while other links in it stay clickable.

A building block for card templates (theme `parts/card-*.html`, Site Editor →
Patterns → Cards) and for cards whose layout was detached. Theme guide:
[`guides/post-cards.md`](../../../guides/post-cards.md).

## Inserting it

Block inserter → **Theme** → *Card Title*, inside a card template or a detached card.
On or off together with *Post Card*; it cannot be disabled on its own.

## Settings

**Heading level** (toolbar), **Link to the post** and **Make the whole card clickable** (sidebar → *Link*). Reads the *Custom title* card option.

## Theme-controlled settings

No `isudev.json` keys. Reads `postId`, `postType` and `isudev/cardOptions` from block context.

## On the frontend

`h{n}.isudev-card-title > a.isudev-card-title__link.isudev-post-card__link`

## What it relies on

Block context from `isudev/post-card` (or a Query Loop / the current post when used elsewhere).

## How to check it still works

Put it in `parts/card-post.html`; a card for a post shows it on the page and in the editor preview.

## Troubleshooting

**Nothing renders.** There is no post in context, or the data it shows is empty
for that post. In the Site Editor it shows a placeholder: a template has no post.
