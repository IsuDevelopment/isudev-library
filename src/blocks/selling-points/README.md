# Selling Points — `isudev/selling-points`

A container for selling points in a responsive grid: any number of
[Selling Point](../selling-point/README.md) children, each an icon, image,
badge, title, description and link — any of which a theme can turn on or
off.

**Deeper documentation:** [`guides/selling-points.md`](../../../guides/selling-points.md)
covers the full `isudev.json` schema and the CSS class contract.

## Inserting it

Block inserter → **Design** → *Selling Points*. It starts with three
children; add or remove points like any other inner block.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Animate in** | Sidebar → *Settings* | off | Reveals each point with a staggered fade/slide as the block scrolls into view. |
| **Render titles as headings** | Sidebar → *Settings* | off | When on, each point's title is a real heading element at the chosen level; otherwise it's a plain, non-heading element. |
| Heading level | Block toolbar (shown only when the above is on) | H2 | The level every point's title renders at. |
| **Icon size** | Sidebar → *Settings* (shown only when icons are enabled) | 28 | Size in pixels for every point's icon. |

## Related

- [Selling Point](../selling-point/README.md) — the child block
- [`guides/selling-points.md`](../../../guides/selling-points.md) — theme configuration, classes
- [`CHANGELOG.md`](../../../CHANGELOG.md) — migration notes from `extended-selling-points`
