# Card Image — `isudev/card-image`

The card's image — the *Custom image* card option, else the post's featured image (with an empty alt, because the title names the card). Renders nothing without an image.

A building block for card templates (theme `parts/card-*.html`, Site Editor →
Patterns → Cards) and for cards whose layout was detached. Theme guide:
[`guides/post-cards.md`](../../../guides/post-cards.md).

## Inserting it

Block inserter → **Theme** → *Card Image*, inside a card template or a detached card.
On or off together with *Post Card*; it cannot be disabled on its own.

## Settings

**Image size** and **Aspect ratio** (sidebar → *Image*; ratio like `16/9`, empty keeps the image's own). A custom image's focal point sets `object-position`.

## Theme-controlled settings

No `isudev.json` keys. Reads `postId`, `postType` and `isudev/cardOptions` from block context.

## On the frontend

`figure.isudev-card-image > img.isudev-card-image__img`

## What it relies on

Block context from `isudev/post-card` (or a Query Loop / the current post when used elsewhere).

## How to check it still works

Put it in `parts/card-post.html`; a card for a post shows it on the page and in the editor preview.

## Troubleshooting

**Nothing renders.** There is no post in context, or the data it shows is empty
for that post. In the Site Editor it shows a placeholder: a template has no post.
