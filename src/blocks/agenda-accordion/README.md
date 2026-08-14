# Agenda Accordion — `isudev/agenda-accordion`

A container for accordion items, following the
[WAI-ARIA accordion pattern](https://www.w3.org/WAI/ARIA/apg/patterns/accordion/):
keyboard navigation between triggers, and a URL hash that opens an item and
can be bookmarked.

**Deeper documentation:** [`guides/agenda-accordion.md`](../../../guides/agenda-accordion.md)
covers the full `isudev.json` schema and the CSS class contract.

## Inserting it

Block inserter → **Design** → *Agenda Accordion*. It starts with one
[Agenda Accordion Item](../agenda-accordion-item/README.md) child; add or
remove items like any other inner block.

## Behavior

By default, more than one item can be open at once, and any open item can be
closed again. A theme can change this in `isudev.json` — see the guide.

## Keyboard support

| Key | Effect |
| --- | --- |
| `Up` / `Down` arrow | Moves focus to the previous / next trigger, wrapping around. |
| `Home` / `End` | Moves focus to the first / last trigger. |

## Troubleshooting

**An item's content never shows.** The item has no inner blocks — its
trigger renders disabled, since there is nothing to expand.

## Related

- [Agenda Accordion Item](../agenda-accordion-item/README.md) — the child block
- [`guides/agenda-accordion.md`](../../../guides/agenda-accordion.md) — theme configuration, classes
- [`CHANGELOG.md`](../../../CHANGELOG.md) — migration notes from `dekode-library/agenda-accordion`
