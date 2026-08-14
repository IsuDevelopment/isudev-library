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

## No T2 dependency: Swiper is bundled, not shared

The source plugin's carousel (`isudev/google-reviews` only) used a `swiper`
script/style handle a theme (T2) registered, so the plugin itself never
bundled Swiper. This plugin has no such theme dependency to lean on, so
`view.js` bundles Swiper itself:

```js
import Swiper from 'swiper';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';
```

`swiper` is a direct `dependencies` entry in `package.json`, not a
`devDependency` — it ships inside the built `view.js`/`view.css`, not
resolved against a host-provided global. `block.json` declares
`"viewStyle": "file:./view.css"` so WordPress enqueues the CSS webpack
extracts from those imports alongside the view script.

The carousel initializes ~400px before entering the viewport
(`IntersectionObserver`, `rootMargin: '400px 0px'`), autoplays only while
visible and no review is expanded, and respects
`prefers-reduced-motion`. Before initialization, and with no JavaScript at
all, reviews remain visible as a horizontally scrollable row of cards. The
"Read more" expander is independent of the carousel — it works whether or
not Swiper ever initializes.

The Gutenberg block wrapper itself stays independent of Swiper: the
`swiper`, `swiper-initialized` classes and Swiper's own transforms are
applied only to the inner `.isudev-google-reviews__viewport`, so the block's
own width/spacing settings never collide with the carousel's mechanics.

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
- `isudev-google-reviews__list`, `isudev-google-reviews__item`
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
