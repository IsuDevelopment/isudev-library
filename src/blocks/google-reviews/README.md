# Google Reviews — `isudev/google-reviews`

Displays reviews stored by the [WP Google Review Slider](https://wordpress.org/plugins/wp-google-places-review-slider/)
plugin, as a card grid or an auto-scrolling carousel.

**Requires WP Google Review Slider.** This block reads an existing database
table that plugin owns and populates — it does not fetch anything from
Google itself. Without that plugin active, the block renders nothing.

**Deeper documentation:** [`guides/google-reviews.md`](../../../guides/google-reviews.md)
covers the shared account data source, the carousel implementation and the
CSS class contract.

## Inserting it

Block inserter → **Widgets** → *Google Reviews*.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Number of reviews** | Sidebar → *Review settings* | 20 | Maximum reviews shown, 1–100. |
| **Review text length** | Sidebar → *Review settings* | 220 | Characters shown before the "Read more" button collapses the rest. |
| **Google account** | Sidebar → *Review settings* | All accounts | Restrict to reviews for one Google Business Profile. |
| **Enable carousel** | Sidebar → *Review settings* | on | Off shows every review as a responsive card grid instead. |
| **Carousel mode** | Sidebar → *Review settings*, shown only when the carousel is on | Continuous scroll | *Continuous scroll* auto-advances; *Classic* adds prev/next buttons and pagination dots instead. |

Continuous mode respects `prefers-reduced-motion`: with reduced motion
requested, it behaves like classic mode and exposes manual navigation.

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

## Related

- [`guides/google-reviews.md`](../../../guides/google-reviews.md) — data source, carousel, classes
- [Google Reviews Header](../google-reviews-header/README.md)
- [Google Reviews Badge](../google-reviews-badge/README.md)
- [`CHANGELOG.md`](../../../CHANGELOG.md) — migration notes from `isudev-google-reviews`
