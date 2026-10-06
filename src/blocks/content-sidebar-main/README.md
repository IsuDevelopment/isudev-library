# Main content — `isudev/content-sidebar-main`

The main column of [Content with sidebar](../content-sidebar/README.md). It
exists only inside that block — inserted with it, never on its own, and it
cannot be removed or moved (the parent locks its two columns).

## Settings

None. Put any blocks inside; it starts with one paragraph and is not locked
(`templateLock: false`, so it does not inherit the parent's lock).

## Markup and classes

`<div class="isudev-content-sidebar__main wp-block-isudev-content-sidebar-main">`
wrapping the inner blocks. It ships no stylesheet — all layout lives in the
parent's `style.scss` (flex column, `gap: var(--isudev-content-sidebar-main-gap)`,
`flex: 1 1 var(--isudev-content-sidebar-main-basis)`). See the parent's README
for the full class contract and custom properties.

## When it renders nothing

The parent block is disabled (in the admin panel or `isudev.json`), which
disables this block too.
