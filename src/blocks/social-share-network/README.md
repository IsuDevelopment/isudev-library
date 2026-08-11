# Social Share Network — `isudev/social-share-network`

One share button. Lives only inside
[Social Share](../social-share/README.md) — it cannot be inserted on its own,
and it does not appear in the main inserter.

**Deeper documentation:** [`guides/social-share.md`](../../../guides/social-share.md)
covers the full `isudev.json` schema, the CSS class contract and the
network → icon map.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Network** | Block toolbar (icon dropdown) or Sidebar → *Network settings* | Facebook | Which service this button shares to. Both controls do the same thing; the toolbar one shows the icons. |
| **Show label** | Block toolbar (caption button) or Sidebar | off | Shows text next to the icon instead of an icon alone. |
| **Label position** | Sidebar → *Network settings* | After icon | `Before icon` or `After icon`. Only shown once the label is on. |
| The label text itself | Typed directly in the canvas | empty | Editable once the label is on. Leave it empty to use the built-in wording for that network ("Share on Facebook", "Copy link", …), which is also what screen readers announce. Plain text only. |

## The twelve networks

| Network | Renders as | Behaviour when clicked |
| --- | --- | --- |
| Facebook, X, LinkedIn | link | Opens a centred share popup. |
| WhatsApp, Bluesky, Threads, Mastodon, Substack | link | Opens the service's share page in a new tab. |
| Email | link | Opens the visitor's mail client with a prefilled subject and body. |
| Copy Link | button | Copies the URL and shows a short confirmation next to the button. |
| Print | button | Opens the browser's print dialog. |
| System Share | button | Opens the device's native share sheet (Web Share API). On a browser without it, silently copies the URL instead. Best used on its own — it replaces the platform-specific buttons rather than sitting next to them. |

Actions render as `<button>` and destinations as `<a>`. That is deliberate: a
print button is not a link to anywhere, and assistive technology announces the
two differently.

## Theme-controlled settings

Set under `library` → **`isudev/social-share`** in `isudev.json` — the parent's
key, not this block's, because one feature has one key:

| What the theme sets | Effect you will notice |
| --- | --- |
| `networkLabel.disable` | No label controls, and no label renders even on posts saved with one. |
| `networkLabel.allowToggle` / `networkLabel.allowPosition` | The individual control is hidden. |
| `allowedNetworks` | Which networks the dropdown offers. |
| `iconsSize` | Icon size in pixels, in the editor and on the frontend. Default 24. |
| `icons` | Swaps the icon used for a network to another name from the plugin's icon registry. |
| `email.subject` / `email.body` | The prefilled email, with `%title%` and `%url%` placeholders. |
| `link.copyText` | What Copy Link puts on the clipboard. Defaults to the URL; `%title%` and `%url%` are available. |

## Accessibility

The icon is decorative and hidden from assistive technology. The accessible name
comes from the visible label when one is shown, and from the built-in wording
for that network when it is not — so a button is never unlabelled either way.
Do not type a label that repeats the network name when the label is visible; it
is already the name.

The copy confirmation is announced, not only shown.

## Troubleshooting

**The button vanished from the page.** Every network except Print and System
Share needs a post to share. Without one — an archive, a search page, a 404 — the
button would point at an empty URL, so it renders nothing instead. Move the
block into a single-post or single-page template.

**The icon looks flat or single-coloured in the editor.** Editor previews render
the icon through an `<img>`, which cannot inherit text colour. The frontend
inlines the same icon and tints it correctly. This is a known limitation of the
editor components package, documented in
[`guides/work-with-icons.md`](../../../guides/work-with-icons.md).

**Copy Link copies nothing.** Check whether the theme set `link.copyText` to an
empty string.

**The network I want is not in the dropdown.** The theme has restricted
`allowedNetworks`.

## Related

- [Social Share](../social-share/README.md) — the container
- [`guides/social-share.md`](../../../guides/social-share.md) — theme configuration, classes, icon map
