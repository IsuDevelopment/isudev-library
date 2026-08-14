# Step — `isudev/image-step-guide-step`

A single step: an image alongside editable inner blocks (a heading and a
paragraph by default). Lives only inside
[Image Step Guide](../image-step-guide/README.md) — it cannot be inserted on
its own, and it does not appear in the main inserter.

**Deeper documentation:** [`guides/image-step-guide.md`](../../../guides/image-step-guide.md)
covers the full `isudev.json` schema and the CSS class contract.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| The image | Click the media area | none | Opens the media library. Renders a placeholder until one is chosen. |
| **Label prefix** | Sidebar → *Label override* | empty | Overrides the guide's default label prefix (e.g. "Step") for this step only. Leave empty to use the guide default. |
| **Hide this step's label** | Sidebar → *Label override* | off | Hides the label badge for this step only. |
| The step's own content | Canvas | a heading and a paragraph | Any of the blocks the theme allows (`allowedStepBlocks` in `isudev.json`). |

The step number in the label badge (e.g. "Step **2**") is never editable — it
always reflects the step's position among its siblings.

## Related

- [Image Step Guide](../image-step-guide/README.md) — the container
- [`guides/image-step-guide.md`](../../../guides/image-step-guide.md) — theme configuration, classes
