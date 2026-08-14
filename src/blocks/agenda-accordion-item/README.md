# Agenda Accordion Item — `isudev/agenda-accordion-item`

A single accordion item: a trigger with a title, an optional date and
excerpt, and collapsible content. Lives only inside
[Agenda Accordion](../agenda-accordion/README.md) — it cannot be inserted on
its own, and it does not appear in the main inserter.

**Deeper documentation:** [`guides/agenda-accordion.md`](../../../guides/agenda-accordion.md)
covers the full `isudev.json` schema and the CSS class contract.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| Heading level | Block toolbar | H3 | The trigger's own heading level, `H1`–`H6`. |
| The date text | Typed directly in the canvas | empty | Shown before the title, e.g. "Sept 14". Optional. |
| The title text | Typed directly in the canvas | empty | Required — an item with no title renders nothing. |
| The excerpt text | Typed directly in the canvas | empty | Shown below (or, if the theme allows it, inside) the trigger. |
| **Default open** | Sidebar → *Settings* | off | Whether this item starts expanded when the page loads. |
| The item's own content | Canvas | a paragraph | Any block the theme allows (`allowedBlocks` in `isudev.json`). |

An item with no inner content still renders, but its trigger is disabled —
there is nothing to expand.

## Related

- [Agenda Accordion](../agenda-accordion/README.md) — the container
- [`guides/agenda-accordion.md`](../../../guides/agenda-accordion.md) — theme configuration, classes
