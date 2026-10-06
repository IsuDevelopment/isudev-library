# Google Reviews — `isudev/google-reviews`

Displays reviews stored by the [WP Google Review Slider](https://wordpress.org/plugins/wp-google-places-review-slider/)
plugin, as a card grid or an auto-scrolling carousel.

**Requires WP Google Review Slider.** This block reads an existing database
table that plugin owns and populates — it does not fetch anything from
Google itself. Without that plugin active, the block renders nothing.

**Deeper documentation:** [`guides/google-reviews.md`](../../../guides/google-reviews.md)
covers the shared account data source, the carousel set-up and the CSS class
contract. The carousel itself is the library's shared slider:
[`guides/slider.md`](../../../guides/slider.md).

## Inserting it

Block inserter → **Widgets** → *Google Reviews*.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Number of reviews** | Sidebar → *Review settings* | 20 | Maximum reviews shown, 1–100. |
| **Review text length** | Sidebar → *Review settings* | 220 | Characters shown before the "Read more" button collapses the rest. |
| **Google account** | Sidebar → *Review settings* | All accounts | Restrict to reviews for one Google Business Profile. |
| **Enable carousel** | Sidebar → *Review settings* | on | Off shows every review as a responsive card grid instead. |
| **Carousel mode** | Sidebar → *Review settings*, shown only when the carousel is on | Continuous scroll | *Continuous scroll* moves slowly and continuously, with a pause button; *Classic* has prev/next buttons and dots and does not move by itself. |

Both modes can be dragged with the mouse, swiped on touch screens and moved
with the Left/Right keys once the carousel has focus. Continuous scrolling
pauses while the pointer is over it, while it has keyboard focus, while a
review is expanded with "Read more", and while it is off-screen. With
`prefers-reduced-motion` it does not move at all and shows the classic
buttons and dots instead.

Cards per view: 1 on phones (1.5 in continuous mode), 2 from 768px, 4 from
1024px, 5 from 1440px.

## Editor preview

This block renders from live database data the editor cannot see, so the
canvas shows a settings summary instead of the reviews themselves — the same
approach [Google Reviews Header](../google-reviews-header/README.md) and
[Google Reviews Badge](../google-reviews-badge/README.md) use for the same
reason.

## Troubleshooting

**The block shows nothing on the frontend.** Either WP Google Review Slider
is not active, or it has no visible Google reviews with text yet for the
selected account.

**The carousel shows as a plain scrollable row with no buttons.** The view
script has not initialized it: check the browser console, and that
`build/blocks/google-reviews/view.js` and `view.css` load on the page. It
initializes when the block comes within ~400px of the screen.

## What it relies on

- The `wpfb_reviews` table and the `WPREV_GOOGLE_PLUGIN_DIR` constant of WP
  Google Review Slider (see the guide).
- The shared slider: `IsuDevLibrary\Utils\Slider\render()` and
  `src/utils/slider/` (Embla Carousel 8.6.0, bundled into this block's
  `view.js`). Not WordPress core APIs beyond `get_block_wrapper_attributes()`.

## What a theme can rely on

- Classes in [the guide's class contract](../../../guides/google-reviews.md#css-class-contract):
  in carousel mode `.isudev-google-reviews__viewport` is also the
  `.isudev-slider` root, `__list` is the slider container and `__item` each
  slide. The `isudev-slider__*` elements, state classes and
  `--isudev-slider-*` custom properties are in
  [`guides/slider.md`](../../../guides/slider.md).
- `--isudev-slider-slides` and `--isudev-slider-gap` on
  `.isudev-google-reviews__viewport` override cards per view and the gap.
  This block's own rules use `:where()`, so a plain class selector wins.
- Since 1.15.0 there are no `swiper-*` classes (see `CHANGELOG.md`).

## How to check it still works

On a page with the block (and WP Google Review Slider data), at 390px and
1280px wide:

- *Classic*: prev/next and dots move the cards; the active dot has
  `aria-current="true"`; mouse drag and touch swipe move; Left/Right work
  with the carousel focused.
- *Continuous*: cards move on their own; the pause button stops and restarts
  them; hovering or opening "Read more" pauses; prev/next/dots are hidden.
- With reduced motion emulated, continuous does not move and shows
  prev/next/dots.
- No console errors. `npm run test:php` covers the markup helpers.

## Related

- [`guides/google-reviews.md`](../../../guides/google-reviews.md) — data source, carousel, classes
- [`guides/slider.md`](../../../guides/slider.md) — the shared slider
- [Google Reviews Header](../google-reviews-header/README.md)
- [Google Reviews Badge](../google-reviews-badge/README.md)
- [`CHANGELOG.md`](../../../CHANGELOG.md) — migration notes from `isudev-google-reviews`
