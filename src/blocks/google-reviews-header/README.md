# Google Reviews Header — `isudev/google-reviews-header`

Displays one Google account's rating, logo and a "Rate us" button, sourced
from the [WP Google Review Slider](https://wordpress.org/plugins/wp-google-places-review-slider/)
plugin's data.

**Requires WP Google Review Slider.** Without that plugin active, this block
renders nothing.

**Deeper documentation:** [`guides/google-reviews.md`](../../../guides/google-reviews.md)
covers the shared account data source.

## Inserting it

Block inserter → **Widgets** → *Google Reviews Header*.

## Settings

| Setting | Where | Default | What it does |
| --- | --- | --- | --- |
| **Google account** | Sidebar → *Header settings* | none | Which Google Business Profile to show the rating and review button for. Picking an account with no header text yet fills it in with the account's name. |
| The header text | Typed directly in the canvas | the account name | Supports a limited set of inline formats (bold, italic, strikethrough, underline, highlight, subscript, superscript, keyboard). |
| The description text | Typed directly in the canvas | empty | Optional text below the header, same inline formats. |
| **Render title as heading** | Sidebar → *Header settings* | on | When off, the title is a plain, non-heading element. |
| Heading level | Block toolbar, shown when the above is on | H2 | The header's own heading level. |
| **Button text** | Sidebar → *Header settings* | "Rate us" | Text on the button linking to Google's review form. |
| **Hide "powered by Google"** | Sidebar → *Header settings* | off | Hides the small Google attribution next to the button. |
| The company logo | Sidebar → *Company logo* | none | Uploaded from the media library. Links to the account's Google Maps profile when both a logo and a matched account are set. |
| **Maximum logo height (px)** | Sidebar → *Company logo* | 64 | Caps the logo's rendered height; width scales proportionally. |

## Editor preview

This block renders from live database data the editor cannot see, so the
canvas shows a lightweight preview built from the same account data the
account picker uses, rather than exact frontend markup.

## Troubleshooting

**No rating shows.** No Google account is selected, or the selected account
has no ratings recorded yet by WP Google Review Slider.

## Related

- [`guides/google-reviews.md`](../../../guides/google-reviews.md) — data source, classes
- [Google Reviews](../google-reviews/README.md)
- [Google Reviews Badge](../google-reviews-badge/README.md)
