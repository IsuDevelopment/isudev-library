# Bento Grid — `isudev/bento-grid`

A responsive CSS grid container for step-by-step or showcase layouts: any
number of [Bento Card](../bento-card/README.md) children, each spanning a
configurable number of columns and rows per breakpoint.

**Deeper documentation:** [`guides/bento-grid.md`](../../../guides/bento-grid.md)
covers the full `isudev.json` schema, the JS filters and the CSS custom
property contract.

## Inserting it

Block inserter → **Design** → *Bento Grid*. Choose one of five built-in
layouts to start — the grid has no cards until one is picked.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Columns** | Sidebar → *Grid settings* | 6 / 4 / 2 (desktop/tablet/mobile) | Number of grid columns. Switch the breakpoint switcher above the control to edit a different device. |
| **Gap (px)** | Sidebar → *Grid settings* | 16 / 12 / 8 | Space between cards, per breakpoint. |
| **Grid guide** | Sidebar → *Grid settings* | on | Shows a translucent column overlay in the editor only. |
| **Min. card height (px)** | Sidebar → *Card size* | 150 | Minimum row track height. |
| **Reset layout** | Sidebar → *Advanced* | — | Removes every card and returns to the layout picker. |

## Cards can grow the grid

Dragging a card's edge (see [Bento Card](../bento-card/README.md)) past the
grid's current column count for that breakpoint automatically increases the
grid's own column count to fit it.

## Related

- [Bento Card](../bento-card/README.md) — the child block
- [`guides/bento-grid.md`](../../../guides/bento-grid.md) — layout variations, JS filters, CSS custom properties
