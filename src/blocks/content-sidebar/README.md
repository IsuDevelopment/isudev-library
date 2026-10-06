# Content with sidebar — `isudev/content-sidebar`

A two-column layout: a main column and a sidebar that can stay in view while
the page scrolls. It always holds exactly one
[Main content](../content-sidebar-main/README.md) and one
[Sidebar](../content-sidebar-aside/README.md) child — the pair
is locked; what goes inside each column is free.

## Inserting it

Block inserter → **Design** → *Content with sidebar*. It starts with both
columns, each holding an empty paragraph. Alignment defaults to **wide**;
**full** is also available.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Sidebar position** | Sidebar → *Sidebar* | Right | Which side the sidebar sits on from 960px up. |
| **On mobile** | Sidebar → *Sidebar* | Below the content | Where the sidebar goes once the columns stack: below or above the main column. |
| **Sticky sidebar** | Sidebar → *Sidebar* | on | From 960px up the sidebar sticks below the top of the viewport while the main column scrolls. |
| Alignment | Block toolbar | Wide | Wide or full. |
| Margin / padding, anchor | Sidebar → *Styles* / *Advanced* | — | Core supports. |

## What a theme can lock (`isudev.json`)

Every key is optional; values shown are the built-in defaults.

```json
{
  "library": {
    "isudev/content-sidebar": {
      "enabled": true,
      "sidebarPosition": { "disable": false, "value": "right" },
      "mobilePosition": { "disable": false, "value": "bottom" },
      "sticky": { "disable": false, "value": true }
    }
  }
}
```

| Key | Effect |
| --- | --- |
| `enabled` | `true`/`false` pins the block on or off and locks its toggle in *IsuDev Library* admin panel. Off also disables both column blocks (they `require` this one). |
| `<setting>.disable` | `true` hides that control in the editor; every instance then uses `<setting>.value` on the front end and in the editor, whatever was saved. Same convention as `isudev/image-step-guide`'s `displayStyle`. |
| `<setting>.value` | The forced value while the control is disabled: `sidebarPosition` `left`\|`right`, `mobilePosition` `top`\|`bottom`, `sticky` boolean. |

No variations: `isudev.json` keys are read at block level only.

## Class contract (stable — changing it is a breaking change)

Styled through these classes, never `.wp-block-isudev-*`.

- Wrapper `<div>`: `isudev-content-sidebar`, `is-sidebar-right|left`,
  `is-mobile-sidebar-bottom|top`, `has-sticky-sidebar` (when sticky), plus
  core's `alignwide|alignfull` and `wp-block-isudev-content-sidebar`.
- Main column `<div>`: `isudev-content-sidebar__main`.
- Sidebar `<aside>`: `isudev-content-sidebar__aside`.

Layout: the wrapper is a wrapping flex row (`align-items: flex-start`); the
main column is `flex: 1 1 <main-basis>`, the sidebar `flex: 1 1 <aside-basis>`
and both are flex columns whose children get `margin-block: 0` and are spaced
by `gap`. From **960px** the row never wraps, the sidebar takes its desktop
side and, when sticky, `position: sticky`. The breakpoint is fixed (media
queries cannot read custom properties); override in the theme if needed.

## Custom properties

Each reads a `theme.json` `settings.custom.isudev-content-sidebar.*` value
first (`--wp--custom--isudev-content-sidebar--*`), then the fallback. They are
defined on `body`; override them there or on `.isudev-content-sidebar`.

| Property | theme.json `custom` key | Fallback |
| --- | --- | --- |
| `--isudev-content-sidebar-gap` | `gap` | `var(--wp--style--block-gap, 2rem)` |
| `--isudev-content-sidebar-main-basis` | `main-basis` | `560px` |
| `--isudev-content-sidebar-aside-basis` | `aside-basis` | `300px` |
| `--isudev-content-sidebar-main-gap` | `main-gap` | `var(--wp--style--block-gap, 2rem)` |
| `--isudev-content-sidebar-aside-gap` | `aside-gap` | `var(--wp--style--block-gap, 1.5rem)` |
| `--isudev-content-sidebar-sticky-offset` | `sticky-offset` | `var(--isudev-sticky-offset, 1.5rem)` |

Sticky `top` is `calc(var(--isudev-content-sidebar-sticky-offset) +
var(--wp-admin--admin-bar--height, 0px))`, so the admin bar is always
accounted for. A theme with a sticky header sets the shared
`--isudev-sticky-offset` on `:root` (header height plus breathing room), or
the block-specific property for this block only.

## Relies on (does not own)

- Core `get_block_wrapper_attributes()` (align, spacing, anchor classes and
  styles) and `--wp-admin--admin-bar--height` (core admin bar CSS).
- Editor: with `apiVersion: 3`, `useInnerBlocksProps` puts
  `block-editor-block-list__layout` on each column element itself. Never add
  `> .block-editor-block-list__layout { display: contents }` — it flattens the
  column and lays its blocks out in a row.

## When it renders nothing / looks wrong

- Nothing at all: the block (or, for the columns, this parent) is disabled in
  the admin panel or by `enabled: false`; or both columns are empty.
- Sidebar does not stick: the viewport is under 960px, sticky is off (or
  locked off by `sticky.disable`), or an ancestor has `overflow: hidden|auto`,
  which breaks `position: sticky`.
- Sidebar hides under a sticky header: set `--isudev-sticky-offset`.

**How to check:** insert the block, add a long main column; at ≥960px the
sidebar follows the scroll, at <960px it stacks per *On mobile*.
`tools/checks/160-content-sidebar.php` covers the class and lock logic.

## Related

- [Main content](../content-sidebar-main/README.md)
- [Sidebar](../content-sidebar-aside/README.md)
