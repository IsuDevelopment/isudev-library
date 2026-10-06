# Sidebar layout: sidebar — `isudev/content-sidebar-aside`

The sidebar column of [Content with sidebar](../content-sidebar/README.md),
rendered as an `<aside>`. It exists only inside that block — inserted with it,
never on its own, and it cannot be removed or moved (the parent locks its two
columns).

## Settings

None here. Position, mobile order and stickiness are set on the parent. Put
any blocks inside; it starts with one paragraph and is not locked
(`templateLock: false`).

## Markup and classes

`<aside class="isudev-content-sidebar__aside wp-block-isudev-content-sidebar-aside">`
wrapping the inner blocks. It ships no stylesheet — all layout, ordering and
sticky positioning live in the parent's `style.scss` (flex column,
`gap: var(--isudev-content-sidebar-aside-gap)`,
`flex: 1 1 var(--isudev-content-sidebar-aside-basis)`). See the parent's
README for the full class contract and custom properties.

## When it renders nothing

The parent block is disabled (in the admin panel or `isudev.json`), which
disables this block too.
