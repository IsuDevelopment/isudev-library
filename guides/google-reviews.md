# Google Reviews blocks

`isudev/google-reviews`, `isudev/google-reviews-header` and
`isudev/google-reviews-badge` together port the standalone
`isudev-google-reviews` plugin — three independent blocks (no parent/child
relationship between them) sharing one data source. This guide covers that
shared architecture, the carousel implementation, the REST endpoint, and the
CSS class contract.

## Unlike this plugin's other multi-block ports, these are siblings

Every other block pair in this plugin (`isudev/social-share` and
`isudev/social-share-network`, `isudev/image-step-guide` and its `-step`,
`isudev/agenda-accordion` and its `-item`, `isudev/selling-points` and its
`-point`) is a parent-and-child pair, where the child's own `inc/` bootstrap
file is loaded because the parent requires it directly. These three Google
Reviews blocks have no parent/child relationship — they are three separate,
independently toggleable top-level blocks that happen to read the same
underlying data.

That is the reason the shared code —
`IsuDevLibrary\GoogleReviews\find_visible()` / `find_accounts()` /
`find_account_summary()` (database access) and
`IsuDevLibrary\GoogleReviews\clamp_rating()` / `star_string()` /
`maps_search_query_args()` / `write_review_query_args()` (pure render
helpers) — lives in `includes/google-reviews/`, not in any one block's own
`inc/`. `IsuDevLibrary\Loader::contained_path()` only allows a block's
`bootstrap` array to load files from inside that block's own directory, so
there is no way for one block to load a file from a sibling block's
directory. The main plugin file (`isudev-library.php`) requires these files
unconditionally, alongside `includes/config.php` and the other core
infrastructure — safe, because the functions do nothing until an enabled
block's `render.php` calls them.

## Requires the WP Google Review Slider plugin

None of these three blocks fetch anything from Google. They read
`{$wpdb->prefix}wpfb_reviews`, a table owned and populated by the
[WP Google Review Slider](https://wordpress.org/plugins/wp-google-places-review-slider/)
plugin. Every block's `render.php` checks
`defined('WPREV_GOOGLE_PLUGIN_DIR')` — a constant that plugin defines — and
renders nothing when it is absent, the same graceful-degradation pattern
`isudev/read-more` uses for a missing link. This is the one deliberate
exception to this plugin's "no runtime dependency on any other plugin" rule:
there is no meaningful way to show Google reviews without a plugin that has
already imported them.

There is no `isudev.json` configuration for any of these three blocks —
every setting is a block attribute, matching the source plugin, which had no
theme-config integration either.

## Accounts REST endpoint

All three blocks' editor UIs need the list of available Google accounts (to
populate a "Google account" picker). `GET /isudev-library/v1/google-review-accounts`
(`includes/google-reviews/rest.php`) returns it, gated by `edit_posts` rather
than the `manage_options` this plugin's other REST endpoints
(`includes/rest.php`) use — any user who can open the block editor needs
this, not just someone who can manage the admin settings panel.

Each account in the response:

```json
{
  "id": "ChIJ...",
  "name": "Acme Ltd",
  "reviewCount": 12,
  "totalReviewCount": 18,
  "averageRating": 4.7
}
```

`reviewCount` is reviews with text (what the carousel/grid block can show);
`totalReviewCount` includes star-only ratings with no text (what the header
and badge blocks' aggregate stats use).

`src/utils/use-review-accounts.js` is the shared `useReviewAccounts()` hook
all three blocks' `edit.js` files import.

## Carousel: the shared slider

Since 1.15.0 the carousel is the library's shared slider — the PHP shell
`IsuDevLibrary\Utils\Slider\render()` and the Embla wrapper
`src/utils/slider/` bundled into this block's `view.js`/`view.css`. See
[`slider.md`](./slider.md) for the wrapper itself. Before 1.15.0 the block
bundled Swiper; the migration notes are in `CHANGELOG.md` (1.15.0).

What this block sets on top of the shared slider:

- `render.php` passes the review cards as slides, with
  `class` = the viewport classes (`isudev-google-reviews__viewport is-slider
  is-mode-{continuous|classic}`), `container_class` =
  `isudev-google-reviews__list`, `slide_class` = `isudev-google-reviews__item`.
  So `.isudev-google-reviews__viewport` **is** the slider root.
- Options: `loop` when there is more than one review; *classic* → `align:
  start`, prev/next + dots, no autoplay (as before); *continuous* → `align:
  center`, `autoScroll: 0.5` px/frame (≈30 px/s, close to Swiper's old
  10 s-per-card at four cards per view).
- Slides per view, in `style.scss` via `--isudev-slider-slides`: 1 → 2
  (768px) → 4 (1024px) → 5 (1440px); continuous starts at 1.5 below 768px
  (1 with reduced motion). Gap 24px.
- Continuous mode hides prev/next/dots while it auto-scrolls and shows only
  the pause control; with `prefers-reduced-motion` no auto-scroll starts and
  the manual controls show instead.
- Opening a review's "Read more" pauses that carousel (`pause('expanded')`)
  until every review in it is collapsed again.
- In slider mode the slider root is the labelled carousel region ("Customer
  reviews"), so the outer `<section>` carries no `aria-label` (it would be a
  second landmark with the same name). The grid keeps the label on the
  `<section>`.

The "Read more" expander is independent of the carousel and works without it.

## Assets

`assets/google-logo.svg` and `assets/verified-rounded.svg` live at the
plugin root (like the rest of this plugin's static files), referenced via
the `IsuDevLibrary\URL` constant in PHP. The badge block's decorative Google
logo is referenced from CSS instead
(`src/blocks/google-reviews-badge/style.scss`); webpack resolves and inlines
it as a data URI at build time, so no extra HTTP request or path
computation is needed at runtime.

## CSS class contract

Renamed from nothing — the source plugin's classes were already namespaced
`isudev-google-reviews*`, so this port changes none of them.

### `isudev/google-reviews`

- Root: `isudev-google-reviews`
- `isudev-google-reviews__viewport`, `.is-slider`, `.is-mode-{continuous|classic}`
  — in slider mode this element is also `.isudev-slider` (see `slider.md`
  for the `isudev-slider__*` elements and `is-ready`/`has-auto-scroll`/… states)
- `isudev-google-reviews__list`, `isudev-google-reviews__item` — `<ul>`/`<li>`
  in the grid, `<div>`s (`.isudev-slider__container` / `.isudev-slider__slide`)
  in the carousel
- `isudev-google-reviews__review`, `__top`, `__content`, `__quote`,
  `__footer`, `__person`, `__author-row`, `__author`
- `isudev-google-reviews__avatar` (plus `.is-fallback` for the initials badge),
  `__rating`, `__date`, `__source`, `__verified`
- `isudev-google-reviews__ellipsis`, `__remainder`, `__read-more` (the
  expandable text control)

### `isudev/google-reviews-header`

- Root: `isudev-google-reviews-header`
- `__logo`, `__logo-image`, `__main`, `__title`, `__description`,
  `__rating`, `__stars`, `__count`, `__actions`, `__powered`, `__button`

### `isudev/google-reviews-badge`

- Root: `isudev-google-reviews-badge`, plus `has-light-text`
- `__google-logo`, `__content`, `__rating`, `__stars`, `__count`

## No Schema.org markup, intentionally

Neither the source plugin nor this port emits `Review` or `AggregateRating`
structured data for reviews of the business shown on its own site — carried
over deliberately, not an oversight.
