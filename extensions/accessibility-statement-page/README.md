# Accessibility statement page

Core's Privacy Policy page, for the accessibility statement (deklaracja
dostępności): one page the site knows is "the statement", a draft to start it
from, and a reminder when it goes missing or stale.

## What it changes

- **Settings → Accessibility statement** (`options-general.php?page=isudev-accessibility-statement`,
  `manage_options` only). Two forms, each posting to `admin-post.php` with its
  own nonce and a capability check:
  - **Create a new page** — pick a template, get a **draft** page pre-filled
    with it, opened in the editor. The new page becomes the statement page.
  - **Use an existing page** — any page that is published, draft, pending,
    scheduled or private. Anything else (a post, a trashed page, a made-up ID)
    is refused, and the option's own sanitizer refuses it again.

  The screen links to edit/view the current page, and says so when the
  selected page is in the Trash or no longer exists.
- **Pages list.** The page shows the state **Accessibility statement**, like
  "Privacy Policy Page".
- **Capabilities.** Trashing or deleting the statement page needs
  `manage_options` on top of core's page capabilities, so an editor cannot
  take the page down by accident. **Editing is not locked**: the statement has
  to be reviewed yearly and is usually kept up to date by whoever runs the
  content, not the administrator. (Core locks editing of the Privacy Policy
  page behind `manage_privacy_options`; switch that on here with the
  `edit_capability` filter.) Deleting the page permanently clears the setting;
  trashing it does not, as in core.
- **Block bindings source `isudev/accessibility-statement`.** A Button,
  Navigation link or Paragraph in the footer can follow the setting instead of
  a hard-coded URL. Keys: `url`, `title` (plain text), `link` (an `<a>`, for
  rich text). While no statement is **published**, a block bound to this
  source renders nothing on the front end, so a footer link never points at
  its placeholder. In the editor the bound values are read-only.
- **`<head>`.** On a Polish site (locale `pl_*`) with a published statement:
  `<meta name="deklaracja-dostępności" content="…">`, the good practice from
  the gov.pl technical conditions.
- **Site Health** (Tools → Site Health → Status), test
  `isudev_accessibility_statement`, "recommended" when no page is set, the page
  is not published, the published page still contains template placeholders
  (`[UZUPEŁNIJ`, `[FILL IN`), or it was last modified over a year ago.

Nothing else is changed: the option is not exposed over REST, no link is
added to the theme automatically, and switching the extension off leaves the
page and the option in place.

## The templates

Both are core blocks only (headings, paragraphs, lists), so they look like the
rest of the theme. The site name and home URL are filled in from Settings →
General and the preparation date from today. Everything else is a placeholder
in square brackets — `[UZUPEŁNIJ: …]` / `[FILL IN: …]` — and the notes saying
what to choose are placeholders too, so Site Health flags a statement
published before they are gone. Neither template has an `h1`: the page title
is the statement's `h1`, so the theme's page template must print it as one.

- **Poland: public-sector declaration** (`pl`, preselected on a Polish site).
  Follows *Warunki techniczne publikacji oraz struktura dokumentu
  elektronicznego Deklaracji Dostępności* (gov.pl, under art. 12 pkt 7 of the
  ustawa z dnia 4 kwietnia 2019 r. o dostępności cyfrowej stron internetowych i
  aplikacji mobilnych podmiotów publicznych). The mandatory formulas (intro,
  status, preparation method) are copied verbatim, which is why this template
  is in Polish whatever the admin language and is **not** translatable. It
  carries every identifier the document requires: `a11y-wstep`,
  `a11y-podmiot`, `a11y-url`, `a11y-data-publikacja`,
  `a11y-data-aktualizacja`, `a11y-status`, `a11y-data-sporzadzenie`,
  `a11y-kontakt`, `a11y-osoba`, `a11y-email`, `a11y-telefon`,
  `a11y-procedura`, `a11y-architektura`, `a11y-aplikacje` — on headings and
  paragraphs as the block **HTML anchor** (Advanced panel), on inline values
  as `<span id>`. The optional `a11y-ocena` (link to the excessive-burden
  analysis) and `a11y-audytor` (external auditor) are described in the notes;
  add them in the code editor when they apply. Sections: status with
  non-accessible content and exemptions, preparation date and method, keyboard
  shortcuts, contact, the request/complaint procedure (7 days / 2 months / the
  Ombudsman, with the mandatory link to bip.brpo.gov.pl), architectural
  accessibility (entrance, corridors and lifts, adaptations, parking, assistance
  dogs, Polish Sign Language interpreter) and mobile apps (delete the section
  when there are none). Keep the identifiers when editing: renaming a heading
  is fine, retyping the paragraph that holds a `<span id>` may drop it.
- **EU model accessibility statement** (`eu`, preselected elsewhere).
  The structure of the model in Commission Implementing Decision (EU)
  2018/1523: commitment, compliance status, non-accessible content,
  preparation, feedback and contact, enforcement procedure. Translatable, so a
  Polish admin creating it gets it in Polish.

### Private businesses and the European Accessibility Act

From **28 June 2025** the EAA applies in Poland through the ustawa z dnia
26 kwietnia 2024 r. o zapewnianiu spełniania wymagań dostępności niektórych
produktów i usług przez podmioty gospodarcze. An online shop or another
covered service does not publish a formal *deklaracja dostępności*; it has to
provide **information on how the service meets the accessibility
requirements** (in its terms or another accessible place on the site), and
micro-enterprises providing services are exempt. Start from the EU template
and replace the public-sector wording, or add your own template with the
`templates` filter. This README is not legal advice.

## Template tags

Defined only while the extension is on — call them behind `function_exists()`:

```php
if ( function_exists( 'isudev_library_get_accessibility_statement_link' ) ) {
	echo isudev_library_get_accessibility_statement_link( '<p class="footer-a11y">', '</p>' ); // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped inside.
}
```

- `isudev_library_get_accessibility_statement_url(): string` — empty until published.
- `isudev_library_get_accessibility_statement_link( $before, $after ): string` —
  `<a class="isudev-accessibility-statement-link">` with the page title.
- `isudev_library_is_accessibility_statement(): bool` — the main query is the page.

The namespaced versions (`IsuDevLibrary\Extensions\AccessibilityStatementPage\url()`,
`statement_link()`, `is_statement_page()`, `page_id()`) are the same functions.

## Linking it from the footer

In a block theme, prefer a binding over a template tag. In the footer
template part, add a Button (or Navigation link, or Paragraph), open
**Attributes** in the block sidebar and pick **Accessibility statement →
Statement URL** (and **Statement title** for the text). The markup it saves:

```html
<!-- wp:button {"metadata":{"bindings":{"url":{"source":"isudev/accessibility-statement","args":{"key":"url"}},"text":{"source":"isudev/accessibility-statement","args":{"key":"title"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Deklaracja dostępności</a></div>
<!-- /wp:button -->
```

```html
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"isudev/accessibility-statement","args":{"key":"link"}}}}} -->
<p>Deklaracja dostępności</p>
<!-- /wp:paragraph -->
```

A Navigation link bound with `{"url":{"source":"isudev/accessibility-statement","args":{"key":"url"}}}`
keeps its own label (core binds only `url` on that block). A plain Page Link
to the page works too; it just does not follow the setting if the page
changes.

## Hooks

All filters are named `isudev_library/extensions/accessibility_statement_page/<name>`.

### `edit_capability`

Extra capability to edit the page. Default `''` (none). Lock it like core's
Privacy Policy page:

```php
add_filter(
	'isudev_library/extensions/accessibility_statement_page/edit_capability',
	fn() => 'manage_options'
);
```

### `delete_capability`

Extra capability to trash or delete the page. Default `manage_options`; `''`
leaves only core's page capabilities.

```php
add_filter(
	'isudev_library/extensions/accessibility_statement_page/delete_capability',
	fn() => 'delete_others_pages'
);
```

### `templates`

The templates offered on the settings screen, as
`key => array( 'label' => …, 'title' => …, 'content' => block markup )`. The
second argument holds the values the defaults were filled with (`entity`,
`site_name`, `url`, `date`). Entries of the wrong shape are dropped.

```php
add_filter(
	'isudev_library/extensions/accessibility_statement_page/templates',
	function ( array $templates, array $data ): array {
		unset( $templates['eu'] );
		$templates['shop'] = array(
			'label'   => 'Sklep internetowy (EAA)',
			'title'   => 'Informacje o dostępności',
			'content' => file_get_contents( get_theme_file_path( 'patterns/eaa-statement.html' ) ),
		);
		return $templates;
	},
	10,
	2
);
```

### `default_template`

The template preselected on the screen. Default `pl` on a `pl_*` locale, `eu`
otherwise.

```php
add_filter( 'isudev_library/extensions/accessibility_statement_page/default_template', fn() => 'eu' );
```

### `url` and `link`

The statement URL (`$url, $page_id`) and the link markup (`$link, $url`)
returned by the template tags and the binding source.

```php
add_filter(
	'isudev_library/extensions/accessibility_statement_page/url',
	fn( string $url ) => $url ? $url : 'https://bip.example.gov.pl/deklaracja-dostepnosci'
);
```

### `meta_tag`

Whether `<meta name="deklaracja-dostępności">` is printed. Default true on a
Polish site.

```php
add_filter( 'isudev_library/extensions/accessibility_statement_page/meta_tag', '__return_false' );
```

### `placeholder_markers`

Strings Site Health looks for in the published statement. Default
`[UZUPEŁNIJ`, `[FILL IN`.

```php
add_filter(
	'isudev_library/extensions/accessibility_statement_page/placeholder_markers',
	fn( array $markers ) => array_merge( $markers, array( 'TODO' ) )
);
```

## What it relies on

The option `isudev_library_accessibility_statement_page` (integer, registered
with `register_setting()`, not in REST). Core: `add_options_page()`,
`admin_post_{action}`, `check_admin_referer()`, `wp_dropdown_pages()`,
`wp_admin_notice()`, `display_post_states`, `map_meta_cap` (`edit_post`,
`edit_page`, `delete_post`, `delete_page`; trashing checks `delete_post`),
`deleted_post`, `register_block_bindings_source()` and the client
`wp.blocks.registerBlockBindingsSource()` with `getFieldsList` (block bindings
attributes listed by `get_block_bindings_supported_attributes()`: Button
`url`/`text`, Paragraph and Heading `content`, Navigation link `url`),
`render_block`, `wp_head`, `site_status_tests`. The Polish template mirrors
the gov.pl document above; if that document changes its identifiers or
formulas, `template_pl()` in `template.php` and `tools/checks/270-accessibility-statement-page.php`
change with it.

## When it does not work

- **No Settings → Accessibility statement.** You are not an administrator
  (`manage_options`).
- **"That page cannot be used."** The page is trashed, or it is not a page.
- **An editor cannot trash the page.** By design; see `delete_capability`.
- **The footer button disappeared.** It is bound to the source and no
  statement is published yet. Publish the page.
- **The bound button shows the field name in the editor.** Same cause: the
  page is not published.
- **No meta tag.** The site locale is not `pl_*`, the page is not published,
  or the theme does not call `wp_head()`.
- **A `<span id>` vanished after editing.** Retyping across the span can drop
  it; restore it in the code editor (Options → Code editor).

## How to check it still works

`php tools/check.php` covers the templates (every gov.pl identifier once,
anchors matching ids, escaping, the mandatory formulas). Then, as an
administrator: Settings → Accessibility statement → Create draft opens a draft
whose blocks all validate (no "This block contains unexpected content"); the
Pages list shows **Accessibility statement** next to it; as an Editor, the
page has Edit but no Trash. Site Health → Status lists "No accessibility
statement page is set" before, "still contains template placeholders" after
publishing the untouched draft. On a Polish site, the front end `<head>`
carries the `deklaracja-dostępności` meta tag once the page is published.
