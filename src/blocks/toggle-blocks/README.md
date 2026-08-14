# Toggle Content — `isudev/toggle-blocks`

A collapsible toggle: a button that shows or hides any inner blocks placed
inside it. Unlike [Social Share](../social-share/README.md), it accepts any
block as content — there is no fixed child.

**Deeper documentation:** [`guides/toggle-blocks.md`](../../../guides/toggle-blocks.md)
covers the `isudev.json` schema and the CSS class contract.

## Inserting it

Block inserter → **Design** → *Toggle Content*. Add any blocks inside it as the
collapsible content.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| The button's own text | Typed directly in the canvas | "Open content" | What the button reads while the content is closed. |
| **Close text** | Sidebar → *Settings* | "Close content" | What the button reads while the content is open. |
| **Open by default** | Sidebar → *Settings* | off | Whether the content is expanded when the page loads. |
| **Scroll to content** | Sidebar → *Settings* | off | Scrolls the content into view when it opens. |
| **Button placement** | Sidebar → *Style* | Bottom | Whether the button sits above or below the content. |
| **Button style** | Sidebar → *Style* | Default | Applies one of the registered `core/button` styles to the toggle button. |
| **Animation speed** | Sidebar → *Style* | 150ms | How long the open/close animation takes. |

The toggle is always shown open in the editor, so its content stays editable.
It collapses and expands as expected on the frontend.

## Theme-controlled settings

Set under `library` → **`isudev/toggle-blocks`** in `isudev.json`:

| What the theme sets | Effect you will notice |
| --- | --- |
| `iconPosition` | `left` or `right` — which side of the button text the icon renders on. Default `right`. |
| `icons.open` / `icons.close` | Icon registry names shown next to the button text for the closed / open state. Setting only one reuses it for both states. Neither is set by default, so no icon shows unless the theme configures one. |

## Accessibility

The button is a real `<button>`, not a link or a `<div>` with a click handler,
and exposes `aria-expanded` and `aria-controls`. The content region carries
`role="region"` and `aria-labelledby`, pointing back at the button. Keyboard
users can activate the button with Enter or Space in addition to the browser's
native click handling.

## Troubleshooting

**The toggle does not animate.** Check `prefers-reduced-motion` in the
browser — the animation is intentionally skipped for users who have asked for
reduced motion.

**No icon shows even though `isudev.json` sets one.** Icon names come from the
shared icon registry (`Utils\get_icons()`); an unknown name renders nothing.
See [`guides/work-with-icons.md`](../../../guides/work-with-icons.md).

## Related

- [`guides/toggle-blocks.md`](../../../guides/toggle-blocks.md) — theme
  configuration and the CSS class contract
- [`CHANGELOG.md`](../../../CHANGELOG.md) — migration notes from
  `dekode-library/toggle-blocks`
