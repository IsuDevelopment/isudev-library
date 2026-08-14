# Bento Card — `isudev/bento-card`

A single card inside a [Bento Grid](../bento-grid/README.md) — it cannot be
inserted on its own, and it does not appear in the main inserter.

**Deeper documentation:** [`guides/bento-grid.md`](../../../guides/bento-grid.md)
covers the full `isudev.json` schema for card content defaults.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Column span** / **Row span** | Sidebar → *Card position*, or drag the card's right/bottom edge | 2×2 (desktop) | How many grid cells the card occupies. Switch the breakpoint switcher above the controls to edit a different device. |
| The card's own content | Canvas | empty | Any block the theme allows (`allowedBlocks` in `isudev.json`). |
| Background, text color, gradient, padding, min-height | Sidebar → *Styles* | none | Native WordPress block supports. |

## Drag to resize

Hover a card to reveal handles on its right and bottom edges. Dragging the
right edge changes its column span (growing the grid itself if the new span
exceeds the grid's current column count); dragging the bottom edge changes
its row span.

## Related

- [Bento Grid](../bento-grid/README.md) — the container
- [`guides/bento-grid.md`](../../../guides/bento-grid.md) — theme configuration for card content
