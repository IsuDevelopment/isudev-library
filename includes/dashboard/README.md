# Client dashboard

Two widgets on the WordPress dashboard (`wp-admin/index.php`) for the people
who run the site, set up in **IsuDev Library → Dashboard**.

## Switching it on

The tab has one master switch, **Dashboard widgets**, off by default. With it
on, each widget has its own switch, and the Support form has one more. Press
**Save**: the tab saves everything in one request.

Who sees the widgets: users with `edit_posts` (editors, authors, contributors,
administrators). Change it with the `capability` filter below. Each user can
still hide a widget in **Screen Options**, like any dashboard widget.

The `reset-admin-dashboard` extension removes the core widgets and leaves these
two alone, so the two together give a dashboard with only these widgets.

## Content overview

One row per content type that has an admin menu entry, in this order: Posts,
Pages, the other types by menu position, Media last. Block-editor internals
(patterns, navigation, templates, fonts, global styles) never show.

Each row shows:

- **The type's own menu icon.** A Dashicon, an SVG data URI or an image URL,
  exactly as the post type registered it. Posts, Pages and Media use their core icons.
- **The total** as a badge, and the non-zero counts by status: published,
  drafts, scheduled (blue), pending review (red), private. Each count links to
  the list filtered by that status.
- **View all** and **Add new** (**Upload** for Media). *Add new* only shows
  when the user may create that type.

A row only shows for users who can edit that type. The footer counts what
awaits review, and the total of every row.

**Content types shown** in the tab lists every candidate type. Untick one to
hide it. The setting stores the hidden ones, so a content type registered later
shows by default.

## Support

The developer's website and email, then **Show form**. The form has these fields:

- **Your email**: required. Prefilled with the **Client email** from the tab,
  or the signed-in user's own address when that is empty.
- **Subject**: required, up to 200 characters.
- **Problem type**: Bug / something broken, Content or editing question, Access
  or login, Feature request, Other.
- **Description**: required, up to 5000 characters. It is kept as typed, so
  pasted error messages and HTML arrive intact, and it is escaped in the email.

Sending it does this:

1. An HTML email goes to **support@isudev.com**. Its subject is
   `[Support form] <site name>: <subject>` and `Reply-To` is the sender's
   address. It contains the request plus the site URL, the sender's name and
   role, the WordPress, PHP, theme and plugin versions.
2. A copy goes to the sender, "We received your support request: <subject>",
   with `Reply-To` set to support.
3. The widget shows a confirmation. One user can send one request per minute.

Mail goes through `wp_mail()`, so the site's SMTP plugin (if any) delivers it.
The request only counts as failed when the email to support fails. A failed
copy is not reported.

## Filters

### `isudev_library/dashboard/capability`

Capability needed to see a widget (and, for `support`, to send the form).
Default `edit_posts`.

```php
add_filter(
	'isudev_library/dashboard/capability',
	fn( string $cap, string $widget ): string => 'support' === $widget ? 'manage_options' : $cap,
	10,
	2
);
```

### `isudev_library/dashboard/support_contact`

The address, website and link label shown and mailed to.

```php
add_filter(
	'isudev_library/dashboard/support_contact',
	fn( array $contact ): array => array_merge( $contact, array( 'email' => 'help@example.com' ) )
);
```

### `isudev_library/dashboard/support/problem_types`

The Problem type options, as slug => label.

```php
add_filter(
	'isudev_library/dashboard/support/problem_types',
	fn( array $types ): array => array( 'shop' => __( 'Shop or orders', 'my-theme' ) ) + $types
);
```

### `isudev_library/dashboard/content_overview/post_types`

The candidate post types (`WP_Post_Type` objects keyed by slug), before the
tab's hidden list applies.

```php
add_filter(
	'isudev_library/dashboard/content_overview/post_types',
	function ( array $types ): array {
		unset( $types['attachment'] ); // No Media row.
		return $types;
	}
);
```

## When it does nothing

- **No widgets:** the master switch is off, or the user lacks `edit_posts`,
  or they hid the widget in Screen Options.
- **A content type is missing:** it has no admin menu entry
  (`show_in_menu => false`), it is unticked in the tab, or the user cannot edit it.
- **No form:** **Support request form** is off.
- **"The request could not be sent":** `wp_mail()` failed. Check the site's
  mail setup. A local site needs a mail catcher (Local ships Mailpit).
- **"Wait a minute":** the same user sent a request less than a minute ago.
