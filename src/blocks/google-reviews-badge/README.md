# Google Reviews Badge — `isudev/google-reviews-badge`

A compact rating badge — the Google logo, average rating and review count —
linking to a Google account's Maps profile. Sourced from the
[WP Google Review Slider](https://wordpress.org/plugins/wp-google-places-review-slider/)
plugin's data.

**Requires WP Google Review Slider.** Without that plugin active, or without
the selected account having any ratings yet, this block renders nothing.

**Deeper documentation:** [`guides/google-reviews.md`](../../../guides/google-reviews.md)
covers the shared account data source.

## Inserting it

Block inserter → **Widgets** → *Google Reviews Badge*.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Google account** | Sidebar → *Badge settings* | none | Which Google Business Profile the badge shows the rating for. Required — the badge renders nothing without one. |
| **Use light text** | Sidebar → *Badge settings* | off | Switches the badge to light text, for use on a dark background. |
| Background color, border color/width | Sidebar → *Styles* | none | Native WordPress block supports. |

## Troubleshooting

**The badge is missing entirely.** No Google account is selected, or the
selected account has no ratings recorded yet.

## Related

- [`guides/google-reviews.md`](../../../guides/google-reviews.md) — data source, classes
- [Google Reviews](../google-reviews/README.md)
- [Google Reviews Header](../google-reviews-header/README.md)
