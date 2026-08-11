# Site Header — `isudev/site-header`

An accessibility-first site header: a logo, disclosure navigation with keyboard
and screen-reader support, a mobile drawer, and a slot for your own blocks at the
end of the bar.

Only one per template — WordPress will not let you insert a second.

## Inserting it

Block inserter → **Layout** → *Site Header Block*. Intended for a header template
part in a block theme, not for post content.

The inserter also offers the variation **Minimal header (no actions)** — the same
block with the site logo and nothing in the actions slot.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Navigation source** | Sidebar → *Menu* | none | Which WordPress menu the navigation renders. The list is loaded from the site, so create the menu first (Appearance → Menus) and it will appear here. |
| **Nav ARIA label** | Sidebar → *Menu* | `Main` | Names the navigation for screen-reader users. Change it only if the page has more than one navigation — then each needs a distinct name, e.g. "Main" and "Footer". |
| **Logo source** | Sidebar → *Logo* | Site logo / title | `Site logo / title` uses whatever the site identity is set to, `Custom image URL` takes a URL, `None` renders no logo. |
| **Logo image URL** | Sidebar → *Logo* | empty | Only shown with *Custom image URL* selected. |
| **Sticky header** | Sidebar → *Behavior* | on | Keeps the header at the top of the viewport as the page scrolls. |
| **White-header body class** | Sidebar → *Behavior* | `is-white-header` | When `<body>` carries this class, the header is transparent until the page is scrolled. Used for pages with a full-bleed hero. The class itself is added by the theme or a template, not by this block. |
| Actions slot | Canvas, at the end of the bar | empty | Drop your own blocks here. Allowed: Buttons, Button, Search, Navigation, Paragraph, Social Icons. |

## Theme-controlled settings

A theme cannot preconfigure the individual settings above; there is no
`isudev.json` schema for this block. It can
[turn the block off](../../../README.md) and register variations.

Presentation is driven by CSS custom properties named `--isudev-*`, declared in
the block's own stylesheet — override them in the theme rather than fighting the
selectors.

## Accessibility contract

This block's markup is covered by an automated accessibility test suite, so keep
these in mind when styling or extending it:

- Submenus are disclosure buttons, not links — a control that opens something is
  a button.
- The mobile drawer traps focus while open and returns it on close.
- Opening the drawer puts `isudev-scroll-locked` on `<html>` to stop the page
  behind it scrolling.
- Header state lives on `.isudev-header`.

If you override the markup through the block's PHP filters, run the suite:
`npm run test:e2e`.

## Troubleshooting

**The navigation is empty.** No menu is selected, or the selected menu has no
items. Create a menu in Appearance → Menus, then pick it under *Menu*.

**The header is transparent when it should not be.** Something is putting the
white-header body class on `<body>` — check the theme's template or the class
name in *Behavior*.

**I cannot add a second header.** By design; the block is marked as usable once
per template.

**Content from the old standalone `isudev-header` plugin does not render.** There
is no compatibility layer: the block name, text domain, CSS classes and filters
all changed. Re-insert the block; the 1.0.0 entry in
[`CHANGELOG.md`](../../../CHANGELOG.md) lists every rename.

## Related

- [`CHANGELOG.md`](../../../CHANGELOG.md) — the full list of renames from `isudev-header`
- [`guides/work-with-icons.md`](../../../guides/work-with-icons.md) — the chevron and close icons come from the shared registry
