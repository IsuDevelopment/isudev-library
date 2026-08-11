# Social Share — `isudev/social-share`

A container for share buttons: an optional prefix such as "Share:" and any
number of [Social Share Network](../social-share-network/README.md) children.

The container renders nothing on its own — the buttons are the child blocks.

**Deeper documentation:** [`guides/social-share.md`](../../../guides/social-share.md)
covers the full `isudev.json` schema, the CSS class contract and the
network → icon map. Read this file first if you are placing the block; read the
guide if you are configuring or styling it for a theme.

## Inserting it

Block inserter → **Widgets** → *Social Share*. Inserting it also inserts a
starting set of buttons — Facebook, X, LinkedIn and Copy link by default. The
theme can change that set; see "Theme-controlled settings" below.

Only `isudev/social-share-network` can go inside it. Nothing else is allowed,
which is why the inserter inside the block offers just the one option.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Show prefix text** | Sidebar → *Prefix settings* | off | Turns on a short piece of text next to the buttons, e.g. "Share:". |
| **Prefix position** | Sidebar → *Prefix settings* | Before icons | `Above icons`, `Before icons` or `After icons`. Only shown once the prefix is on. |
| The prefix text itself | Typed directly in the canvas | empty | Appears as editable text once the prefix is on. Placeholder is "Share:". Plain text only — no bold, links or formatting. |
| **Content alignment** | Block toolbar | none | Aligns the row left, centre or right inside the block. |
| **Align** (wide/full/left/right/centre) | Block toolbar → align | none | Standard WordPress block alignment. This is the block's own width, not the alignment of the row inside it. |

There is no setting for the gap between the buttons or their size here — the
button size comes from `iconsSize` in the theme config, and spacing comes from
the stylesheet.

## Theme-controlled settings

A theme can lock or preconfigure this block through `isudev.json`. When it does,
a control may be missing from the sidebar — that is intentional, not a bug:

| What the theme sets | Effect you will notice |
| --- | --- |
| `prefix.disable` | The whole *Prefix settings* panel is gone, and no prefix renders even on posts saved with one. |
| `prefix.allowToggle` / `prefix.allowPosition` | The individual toggle or the position select is hidden. |
| `defaultTemplate` | Which buttons appear when you insert the block. |
| `allowedNetworks` | Which networks you can pick at all. With exactly one allowed network, the button set is locked and cannot be edited. |
| `features.allowAlignControl` | The content-alignment toolbar button is hidden. |

Values live under `library` → `isudev/social-share` in the theme's
`isudev.json`, and can differ per block variation. See the guide for the schema.

## On the frontend

The block renders a wrapper, an optional prefix and a row of buttons:

```html
<div class="wp-block-isudev-social-share isudev-share isudev-share--align-none isudev-share--prefix-before">
	<div class="isudev-share__content">
		<div class="isudev-share__row">
			<span class="isudev-share__prefix isudev-share__prefix--before">Share:</span>
			<div class="isudev-share__items"><!-- buttons --></div>
		</div>
	</div>
</div>
```

## Troubleshooting

**The block does not appear on the page at all.** It hides itself when no child
button rendered. That happens where there is no single post to share — an
archive, a search page, a 404, or a template part that runs before the loop. A
"Share:" prefix above an empty row is worse than nothing, so the block steps
aside. Put it in a single-post or single-page template.

**The prefix I typed is not showing.** Check that *Show prefix text* is on, that
the theme has not set `prefix.disable`, and that the text is not empty — an
empty prefix renders nothing rather than an empty box.

**I cannot add more buttons.** The theme has restricted `allowedNetworks` to a
single network, which locks the set.

## Related

- [Social Share Network](../social-share-network/README.md) — the button itself
- [`guides/social-share.md`](../../../guides/social-share.md) — theme configuration, classes, icon map
- [`guides/work-with-icons.md`](../../../guides/work-with-icons.md) — the icon registry the buttons draw from
