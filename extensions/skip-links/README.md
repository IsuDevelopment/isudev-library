# Skip links

Adds a keyboard-only shortcut list immediately after the opening `<body>` tag, so
a keyboard or screen reader user can jump straight past the header to the content
of the page.

The links are invisible until focused. Tab once on any front-end page and the
first one appears.

## What it replaces

Block themes ship a core "Skip to content" link of their own. This extension
removes it, because two skip mechanisms in the same tab order are worse for a
screen reader user than either one alone.

Out of the box this extension provides exactly one link, pointing at
`#wp--skip-link--target` — the anchor core itself renders in block themes — so
enabling it is a like-for-like replacement until you add links of your own.

## Markup

```html
<nav class="isudev-skip-links" aria-label="Skip links">
	<ul class="isudev-skip-links__list">
		<li class="isudev-skip-links__item">
			<a class="isudev-skip-links__link" href="#wp--skip-link--target">Skip to main content</a>
		</li>
	</ul>
</nav>
```

The links are hidden with a clip, never with `display: none` — a link set to
`display: none` is removed from the tab order entirely, which would make this
extension do nothing at all. Keep that in mind before restyling it.

## Hooks

### `isudev_library/extensions/skip_links/links`

The links, in tab order. Each entry is `array{href: string, label: string}`;
entries missing either key are dropped.

```php
add_filter(
	'isudev_library/extensions/skip_links/links',
	function ( array $links ): array {
		$links[] = array(
			'href'  => '#primary-navigation',
			'label' => __( 'Skip to main navigation', 'my-theme' ),
		);
		$links[] = array(
			'href'  => '#footer',
			'label' => __( 'Skip to footer', 'my-theme' ),
		);

		return $links;
	}
);
```

Every target must be an element ID that really exists on the page. A skip link
pointing at nothing is a dead end — worse than no skip link, because it costs a
keyboard user a tab stop and then does nothing.

### `isudev_library/extensions/skip_links/nav_label`

The `aria-label` on the `<nav>`. Defaults to *Skip links*.

### `isudev_library/extensions/skip_links/print_styles`

Return `false` to suppress the built-in styles and style
`.isudev-skip-links` in the theme instead. The styles are printed inline in the
`<head>` rather than enqueued as a stylesheet, so the links are already hidden
by the time the browser parses them — a separate stylesheet would let them flash
on first paint.

### `isudev_library/extensions/skip_links/remove_core_link`

Return `false` to keep the core block theme skip link as well. Only do this if
the links here point somewhere core's does not, and be aware of the double tab
stop it creates.

## When the links do nothing

- **Nothing appears on Tab.** The theme does not call `wp_body_open()` in its
  `<body>`. Classic themes must; block themes get it from the template canvas.
- **A link jumps nowhere.** Its target ID is not on that page. Check the ID
  exists in every template the link is offered on, or add it conditionally.
- **The link is visible all the time.** The theme is overriding the clip styles,
  or `print_styles` was filtered off without replacement styles.
