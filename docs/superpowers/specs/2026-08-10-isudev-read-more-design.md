# Design — `isudev/read-more`

Date: 2026-08-10
Status: approved
Supersedes: nothing. Extends the v1 library (`main @ v1.0.0`).

## 1. What this is

A second block for IsuDev Library: a linked card showing a title, an optional
image, an optional badge, optional supporting text and a trailing arrow. The
whole card is one anchor.

It is migrated from the standalone plugin at
`/Users/lukaszbiedron/Local Sites/kormas-isu/app/packages/plugins/isudev-read-more`,
which is coupled to the `t2` platform. This migration removes that coupling
entirely, in line with the library's first golden rule: no runtime dependency on
a theme, on `t2`, or on any other plugin.

## 2. What changes versus the source plugin

The source block selects a **post**. This one selects a **link**. That is the
whole design pressure, and everything below follows from it.

| Source | Here | Why |
| --- | --- | --- |
| `PostSelector` from `@t2/editor` | `LinkPickerControl` / `BlockLinkControl` from `@isudev/gutenberg` | Remove the `t2` dependency; links can point anywhere, not only at posts on this install. |
| `MediaUpload` + `MediaUploadCheck` from core, hand-rolled overlay | `MediaControl` from `@isudev/gutenberg` | One shared media UX across every IsuDev block, with focal point for free. |
| `T2Icon icon="arrowForward"` (JS) and `\T2\Icons\get_icon()` (PHP) | `IsuDevLibrary\Utils\icon( 'arrowForward' )` | The library owns its icon registry. |
| `t2-featured-single-post`, `t2-read-more-content`, `t2-featured-content-layout-col-12` classes | dropped | They style against the `t2` theme, which is not present. |
| `register_block_type_from_metadata()` in `block.php` | descriptor array in `block.php` | `Loader` is the only caller of `register_block_type()`. |

### 2.1 Deliberately lost

- **Multi-select insertion.** `PostSelector` accepted several posts at once and
  created sibling blocks for the extras (`edit.js:355-405`). The link picker has
  no multi-select, so this goes. Users insert cards one at a time.
- **`context.ids` dedupe.** `disabledIds` greyed out posts already used by a
  sibling query block. There is no equivalent for arbitrary links.
- **`siteId` / multisite subsite targeting.** `t2`-specific.
- **`isPreview`.** A `t2` preview-mode flag; nothing here sets it.
- **`t2LayoutColumns`.** Read in `edit.js:140` and written in `:372`, but never
  declared in the source `block.json` — it was already dead.

## 3. Attributes

```jsonc
{
  "link":               { "type": "object", "default": {} },
  "media":              { "type": "object", "default": {} },
  "focalPoint":         { "type": "object" },
  "hasCustomTitle":     { "type": "boolean", "default": false },
  "customTitle":        { "type": "string",  "default": "" },
  "showAdditionalText": { "type": "boolean", "default": false },
  "additionalText":     { "type": "string",  "default": "" },
  "renderAsHeading":    { "type": "boolean", "default": true },
  "headingLevel":       { "type": "number",  "default": 3 },
  "readMoreText":       { "type": "string",  "default": "" },
  "showReadMoreBadge":  { "type": "boolean", "default": false },
  "showFeaturedImage":  { "type": "boolean", "default": true }
}
```

`link` carries `@isudev/gutenberg`'s `LinkValue`: `url`, `title`, `id`, `type`,
`kind`, `opensInNewTab`, `nofollow`, `rel`. Every key is optional; an unset link
is `{}`.

`media` carries `MediaValue`: `source`, `id`, `url`, `type`, `mime`, `alt`,
`width`, `height`. Direct-URL selections carry only `url`, `type` and
`source: 'url'` — no `id`, no dimensions, no alt.

`readMoreText` defaults to `""` rather than the source plugin's `"Read more"`.
A default that needs translating cannot live in `block.json`; the editor shows a
translated `RichText` placeholder instead, and `render.php` falls back to a
translated string when the value is empty.

## 4. Title and image resolution

Both resolve in `render.php`, in this order. This is the "hybrid" decision: an
internal link keeps the convenience the source plugin had, an external link
still works.

**Title**

1. `customTitle`, when `hasCustomTitle` and the string is non-empty.
2. `get_the_title( link.id )`, when `link.kind === 'post-type'`, `link.id` is a
   positive int, and that post exists and is publicly viewable.
3. `link.title`.
4. `link.url`, so a card is never blank.

**Image** (only when `showFeaturedImage`)

1. `media.id` → `wp_get_attachment_image()`.
2. `media.url` with no id → a plain `<img>` with `esc_url()`, `media.alt` and no
   `srcset` (a direct URL has no attachment record to derive one from).
3. `get_post_thumbnail_id( link.id )` under the same guards as title step 2.
4. Nothing → `<figure class="read-more-image no-image">`, matching today.

Step 2's `is_post_publicly_viewable()` guard matters: without it, linking a
draft would leak its title into a public page.

### 4.1 Editor parity

The editor mirrors this with `useSelect` on `core.getEntityRecord( 'postType',
link.type, link.id )` and `core.getMedia()`, exactly as the source does — so the
editor preview and the server render agree. When the record is unavailable the
editor falls back the same way.

## 5. Components and surfaces

| Surface | Component | Notes |
| --- | --- | --- |
| Empty state | `Placeholder` + `LinkPickerControl` render prop | Button label "Pick link". The picker anchors to the placeholder. |
| Toolbar | `BlockLinkControl` | Owns its own `BlockControls` fill — must not be wrapped in another. `group="default"`. `addLabel`/`editLabel` supplied in our text domain, since the library's own labels use WordPress' `default` domain. |
| Image on canvas | `MediaControl` `canvas` | Overlay replace/remove on the `<figure>`, reproducing today's pencil/trash buttons. |
| Image in sidebar | `MediaControl` `sidebar` with `preview: 'focal-point'` | Focal point is new; the source plugin had none. |
| Image in toolbar | **disabled** (`toolbar={false}`) | The toolbar belongs to the link. Two toolbar groups competing for the same block is the kind of ambiguity that makes a block feel broken. |
| Inspector | `PanelBody` with the six existing toggles | Unchanged from the source, minus post-specific ones. |

`hasTextControl` defaults to `true` inside `BlockLinkControl`, so the toolbar's
Text field populates `link.title` — which is exactly the fallback step 3 above
wants.

## 6. Icon registry change

`Utils\build_svg()` hardcodes `viewBox="0 0 600 600"` (`icon.php:45`). The three
existing glyphs are authored on that grid; `arrowForward` is authored on
`0 0 24 24`. Rescaling the path by hand is error-prone and unreviewable.

Change, backwards compatible:

- New pure `default_icon_view_boxes(): array` — slug ⇒ viewBox string, holding
  only the entries that differ from the default.
- `build_svg( string $path_d, int $size, string $class_attr, string $view_box = '0 0 600 600' )`.
- `icon()` looks the viewBox up alongside the path, through a new
  `isudev_library/icon_view_boxes` filter mirroring `isudev_library/icons`.

Existing callers keep working untouched. The ledger records that no check pins
the viewBox value (Task 8 deferred minor); this design closes that gap because
the block now depends on it being right.

## 7. Rendering contract

One anchor wrapping everything, as today:

```html
<a {wrapper_attributes} href="…" [target="_blank" rel="…"]>
  <div class="read-more-inner">
    <figure class="read-more-image [no-image]">…</figure>
    <div class="read-more-content">
      <span class="wp-block-button__read_more">…</span>
      <h3 class="read-more-title">…</h3>
      <p class="read-more-additional-text">…</p>
    </div>
    <span class="read-more-arrow" aria-hidden="true">{svg}</span>
  </div>
</a>
```

Wrapper classes: `has-image`, `has-read-more-badge`, and
`is-link-type-{type}` when `link.type` is present (replacing
`is-post-type-{postType}`), each passed through `sanitize_html_class()`.

The arrow is `icon( 'arrowForward', 24, 'read-more-arrow__icon' )`. The source
stylesheet sized the glyph through `.t2-icon`; that selector becomes
`.read-more-arrow__icon`, which our `icon()` puts on the `<svg>` itself.

`render()` returns `''` when `link.url` is empty — a card with no destination is
not a card.

### 7.1 Escaping and security

- `esc_url( link.url )` on the href. Never trust the editor's normalization; the
  library's own `getLinkAttributes` guard is a client-side convenience only.
- `target` and `rel` are rebuilt server-side with `wp_targeted_link_rel()`, not
  copied from `link.rel`.
- Title through `esc_html()`.
- `readMoreText` and `additionalText` keep the source's `wp_kses()` allowing only
  `<mark class style>`, since both are `RichText` fields limited to
  `core/text-color`.
- The arrow SVG comes from the static registry, already escaped by `icon()`.

## 8. Accessibility

The card is a single anchor; nothing inside it is separately focusable.

- The `<img>` gets `media.alt`, or the attachment's alt when resolved by id, or
  `alt=""` — never the title, which would double-announce.
- `.read-more-arrow` is `aria-hidden="true"` with `focusable="false"` on the SVG.
- The title renders as `h2`–`h6` or a `div`; the level is author-chosen, so the
  block cannot guarantee document outline correctness on its own.
- `:focus-visible` outline on the anchor. The source's
  `outline: 2px solid var(currentColor)` is a bug — `var()` around a keyword
  never resolves — and becomes `outline: 2px solid currentColor`.
- The reduced-motion query on the arrow transition is kept, and extended to the
  card's own `translateY` hover transform, which the source left unguarded.

## 9. Files

```
src/blocks/read-more/
  block.json      apiVersion 3, render: file:./render.php
  block.php       descriptor only
  index.js        registerBlockType, save: () => null
  edit.js
  icon.js         inserter icon (SVG/Path from @wordpress/primitives)
  render.php
  style.scss
  editor.scss
  inc/render-helpers.php
```

`inc/render-helpers.php` is split by a `WordPress adapters` marker, following
`site-header`'s file of the same name.

Above the marker — pure, and therefore checkable without WordPress:

| Function | Purpose |
| --- | --- |
| `pick_title( bool $has_custom, string $custom, string $post_title, string $link_title, string $url ): string` | The four-step precedence of §4, over already-resolved strings. |
| `pick_image( array $media, int $thumbnail_id ): array` | Returns `array( 'kind' => 'attachment'\|'url'\|'none', 'id' => int, 'url' => string, 'alt' => string )`. Decides *which* source wins; renders nothing. |
| `card_classes( bool $has_image, bool $has_badge, string $link_type ): array` | Raw class names, unsanitized. |
| `heading_tag( bool $render_as_heading, int $level ): string` | `h2`–`h6` or `div`, with the same `[2,3,4,5,6]` clamp as the source. |

Below the marker — WordPress adapters:

| Function | Purpose |
| --- | --- |
| `sanitize_highlight( string $content ): string` | `wp_kses()` allowing only `<mark class style>`. Carried over verbatim. |
| `resolve_link_post( array $link ): int` | `link.id` when `kind === 'post-type'` and the post exists and `is_post_publicly_viewable()`; `0` otherwise. The single guard both title step 2 and image step 3 go through. |
| `render_image( array $picked, bool $show ): string` | Turns `pick_image()`'s descriptor into `<figure>` markup. |

`render.php` composes these and does the escaping.

Descriptor:

```php
return array(
  'slug'       => 'read-more',
  'name'       => 'isudev/read-more',
  'requires'   => array(),
  'always_on'  => false,
  'variations' => true,
  'bootstrap'  => array( 'inc/render-helpers.php' ),
);
```

`variations => true` costs nothing and lets a project add variations from
`isudev.json` the same way `site-header` can.

## 10. Dependencies

`package.json` gains:

- `@isudev/gutenberg` as a real **dependency**, pinned `~0.1.1` — the package
  warns that a minor bump may break the API while major is 0.
- `@wordpress/icons` as a **devDependency**. It is a peer of the library and is
  *not* externalized by `@wordpress/dependency-extraction-webpack-plugin`, so it
  gets bundled into our build; it must resolve at build time.
- `@wordpress/editor` and `@wordpress/primitives` as devDependencies — declared
  peers that are externalized to `wp-editor` / `wp-primitives`.

No webpack changes. The library is ESM with WordPress packages left as bare
imports, which `@wordpress/scripts` handles unconfigured; its own example plugin
ships no `webpack.config.js`.

The library contributes **no PHP and no CSS**. All styling is ours.

## 11. Testing

**Pure PHP checks** (`tools/checks/`), following the existing pattern — the new
resolution logic is pure and must be tested without WordPress:

- `70-read-more.php`: `pick_title()` across all four precedence steps, including
  `hasCustomTitle` true with an empty string (must fall through, not blank the
  card); `pick_image()` across all four, including a URL-only `media` with no
  `id`; `card_classes()`; `heading_tag()`'s clamp on an out-of-range level.
- Extend `50-icon.php`: `build_svg()` honours a custom viewBox and still
  defaults to `0 0 600 600` when none is passed; `arrowForward` resolves with
  viewBox `0 0 24 24` while the three existing glyphs keep the default.

Each new check is mutation-tested: break the behaviour, confirm a *targeted*
check fails, restore. A check that stays green under mutation is not a check.

**Playwright + axe** (`e2e/read-more.spec.js`): the dev fixture seeds a page
carrying an `isudev/read-more` block with an internal link and one with an
external link. Assertions: the anchor's href, that the title resolves from the
linked post, that an external link renders `target="_blank"` with
`rel` containing `noopener`, that the arrow is `aria-hidden`, that the image
carries a non-null alt attribute, and zero axe violations on the card.

**Editor coverage**: extend `e2e/panel.spec.js`'s inserter round-trip to assert
both blocks appear, proving the registry handles more than one block — something
no current test does.

## 12. Out of scope

- Recreating multi-select insertion in any form.
- An alt-text editing UI. `@isudev/gutenberg` captures alt at selection but does
  not edit it; the media library remains the place to change it.
- Migrating existing `isudev/read-more` content from the kormas-isu install.
  There is no deprecation path and no `postId` reader — that content will not
  render here. Same stance the library took for `idl/site-header`.
- Consuming `isudev.json` per-block config. Still unconsumed library-wide.
