# Slider — shared carousel for blocks

One carousel implementation for every block that needs one (reviews,
galleries, logo strips…). Two halves:

| Half | File | Role |
| --- | --- | --- |
| PHP shell | `includes/utils/slider.php` — `IsuDevLibrary\Utils\Slider\render()` | Renders root, viewport, container, slides, controls and the options attribute. |
| JS wrapper | `src/utils/slider/index.js` (+ `slider.scss`) | Initializes that markup with **Embla Carousel 8.6.0** and its Autoplay / Auto Scroll plugins. |

**Blocks never import `embla-carousel*` directly.** They import the wrapper.
That keeps the Embla API in one file, so upgrading Embla (see *Embla v9*
below) touches only `src/utils/slider/`.

First consumer: `isudev/google-reviews` (see `guides/google-reviews.md`).

## Why it is bundled per block, not a shared handle

Block view scripts here are classic `viewScript`s built by `wp-scripts`, and
the repo has no shared front-end script handle or script-module pattern.
Importing the wrapper bundles it (and Embla, ~8 KB gz with both plugins) into
the consuming block's `view.js`, and its CSS into that block's `view.css`.
WordPress then loads it only on pages that render the block, and no handle
is shared with a theme or another plugin. The cost: two different slider
blocks on one page each ship their own Embla copy. If that ever matters,
promote `src/utils/slider/` to its own webpack entry registered as a shared
script handle (or a script module) and list it as a dependency. The public
API below does not change.

## Using it from a block

**1. `render.php`** — render each slide's inner HTML yourself (escaped),
then hand the list to the shell:

```php
use function IsuDevLibrary\Utils\Slider\render as render_slider;

echo render_slider( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() escapes what it adds; slides are escaped by the caller.
	$slides, // string[] of trusted, already-escaped HTML.
	array(
		'label'           => __( 'Gallery', 'my-domain' ), // Region name.
		'class'           => 'my-block__slider',           // Extra root classes.
		'container_class' => 'my-block__track',
		'slide_class'     => 'my-block__slide',
		'options'         => array(
			'loop'       => count( $slides ) > 1,
			'align'      => 'start',
			'autoplay'   => 6000,  // ms, or false.
		),
		'navigation'      => true,  // prev/next buttons.
		'pagination'      => true,  // dots.
		'labels'          => array( 'prev' => __( 'Previous image', 'my-domain' ) ),
	)
);
```

**2. `view.js`** — `viewScript` in `block.json`:

```js
import { initSliders, getSlider } from '../../utils/slider';

initSliders(document, { selector: '.my-block__slider' });
```

**3. `style.scss`** — slides per view and anything block-specific. Widths are
CSS only:

```scss
.my-block__slider { --isudev-slider-slides: 1; }
@media (min-width: 768px) { .my-block__slider { --isudev-slider-slides: 3; } }
```

**4. `block.json`** — `"viewStyle": "file:./view.css"` (the wrapper's CSS is
extracted there because `view.js` imports it).

Gotcha: the wrapper's stylesheet is `slider.scss`, not `style.scss`.
`wp-scripts` splits any import named `style.(s)css` into a separate
`style-<entry>.css` chunk that `block.json` would not reference.

Gotcha: a block's own list reset (`margin: 0` on its track class) must not
hit the slider container. The container's negative `margin-inline-start` is
how the gap works.

## PHP API — `IsuDevLibrary\Utils\Slider`

- `render( array $slides, array $args = array() ): string` — empty string for
  no slides. `$args`: `label`, `class`, `attrs` (only `id` and `data-*`,
  escaped), `container_class`, `slide_class`, `options`, `navigation`
  (default true), `pagination` (default true), `labels`, `icons` (icon
  registry names for `prev`/`next`/`pause`/`play`; defaults `chevronLeft`,
  `chevronRight`, `pause`, `play`).
- `normalize_options( array $options ): array` — pure; what ends up in
  `data-isudev-slider`.
- `default_labels(): array` — `label`, `prev`, `next`, `goto` (`Go to slide
  %d`), `slide` (`%1$d of %2$d`), `pause`, `play`; all translated in the
  `isudev-library` domain.
- `join_classes( array $classes ): string` — pure; drops unsafe tokens.

It uses only escaping, translation and the icon registry, so
`tools/checks/55-slider.php` exercises it without WordPress.

## Options (`data-isudev-slider` JSON)

| Key | Type | Default | Embla mapping |
| --- | --- | --- | --- |
| `loop` | bool | false | `loop` (Embla turns it off itself when the slides do not overflow enough) |
| `align` | `start`\|`center`\|`end` | `start` | `align` |
| `slidesToScroll` | int ≥ 1 \| `auto` | 1 | `slidesToScroll` |
| `dragFree` | bool | false | `dragFree` |
| `autoplay` | ms ≥ 1000 \| false | false | Autoplay plugin `delay` |
| `autoScroll` | px/frame 0.1–10 \| false | false | Auto Scroll plugin `speed` (wins over `autoplay`) |

The wrapper also sets `direction` (`rtl` when the root's computed
`direction` is rtl) and `duration` (25, or 10 with reduced motion). Options
passed to `createSlider()` / `initSliders({ options })` are defaults. The
root's attribute overrides them.

## JS API — `src/utils/slider/index.js`

- `initSliders( scope = document, { selector, options, lazy = true, rootMargin = '400px 0px', onInit } )`
  — finds roots, initializes each when it comes within `rootMargin` of the
  viewport (immediately without IntersectionObserver or with `lazy: false`).
  `onInit( controller )` runs once per new instance.
- `createSlider( root, options )` → controller, or `null` without a viewport.
  Idempotent per root.
- `getSlider( root )` → controller or `undefined`.

Controller: `root`, `scrollNext()`, `scrollPrev()`, `scrollTo( index )`,
`selectedIndex()`, `snapCount()`, `slideCount()`, `hasMotion()`,
`isPlaying()`, `pause( reason = 'api' )`, `resume( reason = 'api' )`,
`on( 'select' | 'reInit', cb )` → unsubscribe, `destroy()`.

Motion runs only while **no pause reason is held**. Built-in reasons:
`hover` (mouse pointer only), `focus` (keyboard focus only), `drag`,
`offscreen`, `user` (the pause button), `reduced-motion` (preference changed
at runtime). A block adds its own, e.g. google-reviews holds `expanded` while
a review is open. The plugins are created with `playOnInit: false` and
`stopOnInteraction: true`, so they never restart on their own.

No globals: instances live in a module-scoped `WeakMap`, and one shared
IntersectionObserver handles the off-screen pause.

## Class contract (theme-facing)

Elements:

- `.isudev-slider` — root, `role="region"`, `aria-roledescription="carousel"`, `aria-label`
- `.isudev-slider__viewport` — `tabindex="0"`, overflow clip, arrow keys
- `.isudev-slider__container` — the flex track
- `.isudev-slider__slide` — `role="group"`, `aria-roledescription="slide"`, `aria-label="N of M"`
- `.isudev-slider__controls` — wraps the buttons and dots, after the viewport
- `.isudev-slider__button` plus `__prev`, `__next`, `__pause`
- `.isudev-slider__icon`, `__icon-pause`, `__icon-play` — SVGs inside the buttons
- `.isudev-slider__dots` (`data-label` template), `.isudev-slider__dot` (built by JS)

States on the root, set by JS:

- `is-ready` — initialized. Before that, and without JS, the viewport is a
  native horizontally scrollable row and the controls are hidden.
- `is-static` — nothing to scroll (one snap); controls hidden.
- `has-autoplay` / `has-auto-scroll` — a motion plugin is active (never with
  reduced motion). The pause control is visible only then.
- `is-playing` — motion is running right now.
- `is-paused` — the user pressed pause (the button shows the play icon).
- `is-dragging` — during a pointer drag (text selection is disabled).

## Custom properties

Defaults are set on `:where(body)`, each from `theme.json`
`settings.custom.isudev-slider.*` first. Override per block on the root.

| Property | Default |
| --- | --- |
| `--isudev-slider-slides` | `--wp--custom--isudev-slider--slides`, 1 |
| `--isudev-slider-gap` | `--wp--custom--isudev-slider--gap`, 1.5rem |
| `--isudev-slider-controls-gap` | `…--controls-gap`, 0.75rem |
| `--isudev-slider-button-size` | `…--button-size`, 2.5rem |
| `--isudev-slider-button-color` | `…--button-color`, currentcolor |
| `--isudev-slider-button-background` | `…--button-background`, transparent |
| `--isudev-slider-button-border` | `…--button-border`, 1px solid currentcolor |
| `--isudev-slider-button-radius` | `…--button-radius`, 50% |
| `--isudev-slider-dot-size` | `…--dot-size`, 0.625rem |
| `--isudev-slider-dot-color` | `…--dot-color`, currentcolor |
| `--isudev-slider-dot-opacity` | `…--dot-opacity`, 0.3 (active dot: 1) |
| `--isudev-slider-focus-color` | `…--focus-color`, currentcolor |

Fractional slide counts work (`1.5` peeks the next slide).

## Accessibility behaviour

- Region and slide semantics as above. Both role descriptions and every
  label are translated strings from PHP. The view script carries no English.
- Prev/next: at the ends of a non-looping slider they get `disabled` and
  `aria-disabled="true"`.
- Dots: real buttons, `aria-label` from the `data-label` template ("Go to
  slide %d"), `aria-current="true"` on the active one, a 24px hit target.
  They are rebuilt when Embla re-initializes (the snap count depends on
  slides per view).
- Keyboard: Left/Right on the focused viewport (swapped in RTL); Embla's
  `watchFocus` scrolls a slide into view when focus moves into it.
- Motion (WCAG 2.2.2): any autoplay or auto-scroll renders a visible pause
  control. Motion also pauses on mouse hover, keyboard focus inside, drag or
  touch, when the root is off-screen, and when the tab is hidden.
- `prefers-reduced-motion: reduce`: no plugin is created (no autoplay or
  auto-scroll), and buttons, dots and keys jump instead of animating.

## Checking it still works

- `npm run test:php` — `tools/checks/55-slider.php` (shell markup, options,
  escaping) and `50-icon.php` (the four slider icons).
- Front end: the google-reviews checks in its README (classic + continuous,
  drag/swipe, buttons, dots, keys, reduced motion). With no Local site, the
  1.15.0 verification rendered the real `render.php` + this helper with WP
  shims into a static page and drove it with Playwright (mouse drag, touch
  swipe via a `hasTouch` context, arrows, dots, keyboard, reduced motion,
  lazy init, off-screen pause, RTL, axe).

## Embla v9 migration note

Embla 9 was still a release candidate when this was written (1.15.0 pins
8.6.0); expect API changes. Only `src/utils/slider/index.js` talks to Embla. Migrate it, keep the controller
API, the class contract and the `data-isudev-slider` keys stable, and no
block or theme changes. Re-run the checks above. Pin exact versions
(`embla-carousel*` in `package.json` are exact on purpose).
