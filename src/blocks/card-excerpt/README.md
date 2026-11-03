# Card Excerpt — `isudev/card-excerpt`

The card's excerpt — the card's *Custom excerpt* option, else the post's written excerpt, else its content trimmed to a word count. Renders nothing with the *Hide excerpt* option.

A building block for card templates (theme `parts/card-*.html`, Site Editor →
Patterns → Cards) and for cards whose layout was detached. Theme guide:
[`guides/post-cards.md`](../../../guides/post-cards.md).

## Inserting it

Block inserter → **Theme** → *Card Excerpt*, inside a card template or a detached card.
On or off together with *Post Card*; it cannot be disabled on its own.

## Settings

**Maximum words** (sidebar → *Excerpt*, default 25; applies to an excerpt made from the content). Filter: `isudev_library/post_card/excerpt`.

## Theme-controlled settings

No `isudev.json` keys. Reads `postId`, `postType` and `isudev/cardOptions` from block context.

## On the frontend

`p.isudev-card-excerpt`

## What it relies on

Block context from `isudev/post-card` (or a Query Loop / the current post when used elsewhere).

## How to check it still works

Put it in `parts/card-post.html`; a card for a post shows it on the page and in the editor preview.

## Troubleshooting

**Nothing renders.** There is no post in context, or the data it shows is empty
for that post. In the Site Editor it shows a placeholder: a template has no post.
