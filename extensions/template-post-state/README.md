# Show template name

Shows the assigned page template as a small badge next to a post's title on list
screens, alongside WordPress's own "— Draft" and "— Front Page" states.

## Where it appears

Any post type list screen, on rows whose post has a template assigned in
**Page attributes → Template**. Posts using the default template get no badge —
that is the common case and a badge on every row would only be noise.

The badge shows the template's **name**, as declared in its file header or in
`theme.json`, not its file name.

## What it is for

Finding the one page that is still on an old template, or confirming that a
landing page really is using the landing page template, without opening each post
in turn.

## When a badge looks wrong

- **A file name instead of a name.** The assigned template is no longer in the
  theme — renamed or deleted. The badge falls back to the stored file name so the
  stale assignment is visible rather than silently ignored. Reassign the template
  on that post.
- **No badge on a block theme's template.** Block theme templates chosen in the
  editor's template panel are stored differently from classic
  `_wp_page_template` assignments. Only the latter is shown here.
