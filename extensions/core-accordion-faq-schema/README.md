# Accordion FAQ structured data

Adds a switch to the core Accordion block that publishes its items as
`FAQPage` structured data (JSON-LD): each item's heading is a `Question`, its
panel the `acceptedAnswer`.

**Set expectations first.** Since August 2023 Google shows FAQ rich results
only for well-known, authoritative government and health sites. For everyone
else the markup will not produce a rich result in Google. It is still read by
other search engines (Bing) and by AI assistants and answer engines, which is
the realistic reason to switch it on. Use it only for real questions with one
answer each, never for marketing copy in an accordion.

## What it changes

- **Editor.** The Accordion block (the outer `core/accordion`) gets a
  **FAQ structured data** panel with **Publish as FAQ structured data**, off by
  default. Each accordion opts in on its own.
- **Front end.** Every opted-in accordion on the page contributes to **one**
  `FAQPage` for the page:
  - **Question** — the heading's text, tags stripped and entities decoded.
  - **Answer** — the panel's fully rendered HTML (nested blocks, patterns and
    shortcodes included), reduced to the tags Google allows in an answer:
    `h1`–`h6`, `br`, `ol`, `ul`, `li`, `a` (with `href` only), `p`, `div`, `b`,
    `strong`, `i`, `em`. Every other tag and every attribute is dropped; text
    is kept; `script` and `style` contents are dropped.
  - An item with an empty heading or an empty panel is left out. Collapsed
    items are included — hidden-by-default answers are fine for this markup.
  - A nested accordion belongs to its parent's answer as text, and publishes
    its own items only if it is switched on itself.
- **Where it goes**, in this order:
  1. **Yoast SEO** — the page's `WebPage` node gets `FAQPage` added to its
     `@type` and the questions appended to `mainEntity` (`wpseo_schema_graph`).
     Works next to Yoast's own FAQ block.
  2. **Rank Math** — the questions join Rank Math's `FAQPage` entity (its FAQ
     block's), or a new one (`rank_math/json_ld`).
  3. **Otherwise** — one `<script type="application/ld+json" id="isudev-faq-schema">`
     printed in `wp_footer`. Also the fallback when an SEO plugin is active but
     did not take the questions (for example, its schema output is off).

Only singular views and the front page publish anything (see the `enabled`
filter). The block markup is not changed.

## Hooks

### `isudev_library/extensions/core_accordion_faq_schema/enabled`

Whether this request publishes FAQ data. Defaults to `is_singular() || is_front_page()`.

```php
add_filter(
	'isudev_library/extensions/core_accordion_faq_schema/enabled',
	function ( bool $enabled ): bool {
		return $enabled && ! is_page( 'contact' );
	}
);
```

### `isudev_library/extensions/core_accordion_faq_schema/questions`

The `Question` nodes for the page (`name`, `acceptedAnswer.text`). Edit, reorder,
or return `array()` to publish nothing. Malformed entries are dropped.

```php
add_filter(
	'isudev_library/extensions/core_accordion_faq_schema/questions',
	function ( array $questions ): array {
		return array_slice( $questions, 0, 10 );
	}
);
```

To keep the questions out of an SEO plugin and always print the standalone
script instead, remove that integration:

```php
add_action( 'init', function () {
	remove_filter( 'wpseo_schema_graph', 'IsuDevLibrary\\Extensions\\CoreAccordionFaqSchema\\inject_yoast', 99 );
	remove_filter( 'rank_math/json_ld', 'IsuDevLibrary\\Extensions\\CoreAccordionFaqSchema\\inject_rank_math', 100 );
}, 1 );
```

## What it relies on and does not own

- Core blocks `core/accordion`, `core/accordion-item`, `core/accordion-heading`
  (title in `.wp-block-accordion-heading__toggle-title`) and
  `core/accordion-panel` (wrapper `.wp-block-accordion-panel`), checked against
  WordPress 7.1.3.
- `register_block_type_args` (attribute `isudevFaqSchema` on `core/accordion`),
  `pre_render_block` and the `render_block_core/accordion*` filters to capture
  each item while it renders, `WP_HTML_Tag_Processor`, `wp_kses`,
  `wp_print_inline_script_tag`.
- Yoast SEO's `wpseo_schema_graph` (graph pieces + `Meta_Tags_Context::$main_schema_id`)
  and Rank Math's `rank_math/json_ld` (entities keyed by name; its FAQ block
  uses the key `faqs`). Both are read defensively: an unexpected shape leaves
  their output untouched and the standalone script takes over.
- **Classic themes** print `wp_head` (where both SEO plugins output schema)
  before the content renders. There, the queried post's switched-on accordions
  are rendered once early to collect the questions. Accordions that a classic
  theme renders outside the post content after `wp_head` (a widget, a footer
  pattern) are then missed by the SEO plugin's output. Block themes render the
  whole template first and are not affected. Accordions inside a synced pattern
  are found only when they render on the page, not by that early pass.

## When it does not work

- **No FAQ in the page source.** The accordion's switch is off; or the view is
  not singular; or every item lacks a heading or answer. Search the source for
  `FAQPage`.
- **With Yoast, nothing in its graph.** Yoast has no `WebPage` node on that page
  (schema off for the post type) — then the standalone script prints instead.
- **Duplicated FAQ.** Another plugin also builds FAQ schema from the accordion
  (T2's `core-accordion-faq-schema`, for one). Run only one.
- **Rich Results Test shows no FAQ result.** Expected for most sites (see
  above); the Schema Markup Validator (validator.schema.org) still shows the
  data.

## How to check it still works

Page with two accordions, one switched on with three items (one answer holding
a list, a link and an image), one off. Without an SEO plugin: one
`#isudev-faq-schema` script with three questions, the list and link kept, the
image gone. With Yoast: no standalone script; the `WebPage` node reads
`"@type":["WebPage","FAQPage"]` with the three questions in `mainEntity`.
`php tools/check.php` covers the graph merging and validation
(`250-core-accordion.php`).
