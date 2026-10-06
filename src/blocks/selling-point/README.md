# Selling Point — `isudev/selling-point`

A single selling point: an optional icon, image, badge, title, description
and link. Lives only inside
[Selling Points](../selling-points/README.md) — it cannot be inserted on its
own, and it does not appear in the main inserter.

**Deeper documentation:** [`guides/selling-points.md`](../../../guides/selling-points.md)
covers which of the settings below a theme can turn off entirely.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Width** | Block toolbar | Default (the grid's own span) | Overrides this point's column span: 20% / 25% / 33.33% / 50% / 75% / 100%. |
| The link | Block toolbar (link icon) | none | Makes the whole point a link. Unlink to remove it. |
| The icon | Canvas, when shown | none | Pick one from the shared icon registry. |
| **Show icon** | Toolbar or Sidebar → *Settings* | on | Hides the icon without clearing which one is picked. |
| The badge text | Typed directly in the canvas | empty | A small label above the title. |
| **Show badge** | Sidebar → *Settings* | on | Hides the badge without clearing its text. |
| The image | Click the image area, when shown | none | Opens the media library. |
| The title | Typed directly in the canvas | empty | Required for anything else on the point to matter — an empty point still renders (there is no equivalent of Read More's "nothing to point at" rule here). |
| The description | Typed directly in the canvas | empty | Supporting text below the title. |
| The link text | Typed directly in the canvas | empty | Shown separately from the point's own link — wrapped in its own `<a>` only when the point overall is not already a link, to avoid nesting an anchor inside an anchor. |
| **Show link text** | Sidebar → *Settings* | off | Reveals the link text field. |
| **Background / Text colour** | Sidebar → *Styles* → *Color* | none (inherits) | Core colour support on this point only. Lands on `.isudev-selling-point` as `has-background` / `has-<slug>-background-color`, `has-text-color` / `has-<slug>-color` or inline styles. The library sets no background of its own, so a chosen one is never overridden; with a background the point's border turns transparent. |

Every row above except Width and the link itself can be turned off entirely
for the whole grid by the theme — see the parent block's
[guide](../../../guides/selling-points.md).

## Icon on the canvas

In the editor the icon is a button (`@isudev/gutenberg` `IconSelect`) that
opens the icon picker. Once an icon is picked it looks like the front end:
only the icon, at the grid's *Icon size*, in the text colour, with no button
border, background or name text; the name stays in the button's
`aria-label` ("Icon: Laptop") and the focus ring still shows on keyboard
focus. With no icon picked it is a normal button reading *Select icon*.

This is done by the class `isudev-selling-point__icon-select` (+ `is-selected`)
and the CSS in `selling-points/editor.scss`. It relies on IconSelect's markup
— icon `<span>` first, label `<span>` last, the icon box sized inline — so an
`@isudev/gutenberg` update that changes that markup can bring the name back.
**How to check:** pick an icon on a point in the editor; only the icon shows,
and Tab to it shows the focus ring.

## Related

- [Selling Points](../selling-points/README.md) — the container
- [`guides/selling-points.md`](../../../guides/selling-points.md) — theme configuration, classes
