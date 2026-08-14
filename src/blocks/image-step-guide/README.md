# Image Step Guide — `isudev/image-step-guide`

A container for step-by-step content: any number of
[Step](../image-step-guide-step/README.md) children, each an image alongside
editable inner blocks.

**Deeper documentation:** [`guides/image-step-guide.md`](../../../guides/image-step-guide.md)
covers the full `isudev.json` schema and the CSS class contract.

## Inserting it

Block inserter → **Widgets** → *Image Step Guide*. It starts with two Step
children; add or remove steps like any other inner block.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Enlarge on click** | Block toolbar (fullscreen icon) or Sidebar → *Images* | off | Clicking a step's image opens it enlarged in an overlay. |
| **Display style** | Sidebar → *Layout* | List | `List` stacks steps in one column; `Grid` lays them out two per row (one column on narrow screens). |

## Theme-controlled settings

Set under `library` → **`isudev/image-step-guide`** in `isudev.json`:

| What the theme sets | Effect you will notice |
| --- | --- |
| `displayStyle.disable` | Hides the Display style control; the guide always uses `displayStyle.value`. |
| `displayStyle.value` | The fixed display style used when the control is disabled. |
| `imageLightbox.disable` | Hides the Enlarge on click control; the lightbox never activates. |
| `stepLabel.disable` | No label controls anywhere, and no step label renders even on content saved with one. |
| `stepLabel.prefix` / `stepLabel.visible` | Default label prefix and visibility for every step in this guide. Each step can still override both individually. |
| `allowedStepBlocks` | Which blocks a step's content area accepts. |
| `stepTemplate` | The blocks inserted into a new step, in order. |

## Accessibility

The lightbox overlay traps focus on its close button when it opens, closes on
Escape or a click outside the image, and is marked `role="dialog"`
`aria-modal="true"`.

## Troubleshooting

**Clicking an image does nothing.** "Enlarge on click" is off for this guide,
or the theme has set `imageLightbox.disable` in `isudev.json`.

**A step has no image.** An empty step shows a placeholder block instead of a
broken image — pick one from the Step's own media control.

## Related

- [Step](../image-step-guide-step/README.md) — the child block
- [`guides/image-step-guide.md`](../../../guides/image-step-guide.md) — theme configuration, classes
- [`CHANGELOG.md`](../../../CHANGELOG.md) — migration notes from `dekode-library/image-step-guide`
