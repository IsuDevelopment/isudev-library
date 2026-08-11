# Read More — `isudev/read-more`

A linked card: a title, an optional image, an optional badge and optional
supporting text. The whole card is one link, and it can point anywhere — a post,
a page or an external URL.

## Inserting it

Block inserter → **Design** → *Read More Block*. The block starts as a
placeholder asking where the card should link; pick a link and the card appears.
Everything else is optional.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Link** | Placeholder on insert, then block toolbar → *Edit link* | none | The card's destination. Search for a post or page, or paste a URL. The picker's own options — open in new tab, `nofollow` — are honoured on the frontend. There is no unlink action: a card with no destination renders nothing, so removing the link would blank the block. |
| **Heading level** | Block toolbar → heading dropdown | H3 | H2–H6, or *Paragraph* to render the title as a plain `div` instead of a heading. Use Paragraph when the card sits inside a section that already has its own heading — several cards each claiming an H2 makes a mess of the document outline. |
| **Show image** | Sidebar → *Read More settings* | on | Whether the card has an image at all. |
| **Read more badge** | Sidebar → *Read More settings* | off | A small label above the title. Its text is editable in the canvas; the placeholder is "Read more". |
| **Use custom title** | Sidebar → *Read More settings* | off | Off, the card shows the linked page's own title, so it stays in step when that page is renamed. On, you type the title yourself in the canvas. |
| **Additional text** | Sidebar → *Read More settings* | off | Supporting text below the title, editable in the canvas. |
| The image | Canvas, once *Show image* is on | featured image | Click it to choose or replace an image. With nothing chosen, the card falls back to the linked post's featured image. |

The editable pieces — badge, custom title, additional text — are typed straight
into the card, not into sidebar fields.

## Styling

The card opts into the standard WordPress design controls, so they behave the way
they do on core blocks:

- **Colour** — text and background
- **Border** — colour, width, style and radius
- **Spacing** — margin and padding
- **Align** — wide and full

## Theme-controlled settings

A theme cannot preconfigure this block's individual settings; there is no
`isudev.json` schema for it. What a theme can do is
[turn the block off](../../../README.md) or register variations of it, which are
just preset attribute combinations shown in the inserter.

## On the frontend

The card renders as a single `<a>`. Its classes reflect what it contains, so a
stylesheet can react to it:

- `is-link-type-{type}` — the kind of destination that was picked
- a "no image" state when no image resolved, so text-only cards can be laid out
  differently
- `read-more-arrow` wraps the arrow icon, which is decorative and hidden from
  assistive technology

## Troubleshooting

**The block renders nothing on the page.** The card has no link. A card without a
destination is not a card, so it renders nothing rather than an unclickable box.

**The title is not the one I expect.** With *Use custom title* off, the title
comes from the linked page. If that page has no title, the link's own label is
used, and the URL is the last resort — the card is never blank.

**Turning on *Use custom title* blanked the title.** Type one; until you do, the
linked page's title is still used rather than an empty heading.

**No image, even though the linked post has a featured image.** Check that *Show
image* is on. An image chosen by hand always wins over the featured image.

**Content from the old standalone `isudev-read-more` plugin does not render.**
There is no compatibility layer. Re-insert the block; see
[`CHANGELOG.md`](../../../CHANGELOG.md).

## Related

- [`CHANGELOG.md`](../../../CHANGELOG.md) — the 1.1.0 entry lists everything that
  changed when this block moved out of the standalone plugin
