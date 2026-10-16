# Clean up the dashboard

The first screen a client sees after logging in is a wall of widgets they
never asked for: WordPress news, a Quick Draft box, comment activity, plugin
upsells. This extension clears it.

## What it changes

On `wp_dashboard_setup` at priority 999 — after core and plugins have added
their widgets — it removes these from **Dashboard → Home**:

| Widget | ID | Added by |
| --- | --- | --- |
| Site Health Status | `dashboard_site_health` | core |
| At a Glance | `dashboard_right_now` | core |
| Activity | `dashboard_activity` | core |
| Quick Draft | `dashboard_quick_press` | core |
| WordPress Events and News | `dashboard_primary` | core |
| Yoast SEO Posts Overview | `wpseo-dashboard-overview` | Yoast SEO |
| Yoast SEO / Wincher: Top Keyphrases | `wpseo-wincher-dashboard-overview` | Yoast SEO |
| Gravity Forms | `rg_forms_dashboard` | Gravity Forms |

Each is removed from every dashboard column (`normal`, `side`, `column3`,
`column4` and any other registered), so a widget a user dragged to another
column goes too. An ID that is not present is simply skipped.

The **Welcome** panel ("Welcome to WordPress!") is removed as well
(`remove_action( 'welcome_panel', 'wp_welcome_panel' )`).

## What stays

- **PHP Update Required / Recommended** (`dashboard_php_nag`) and
  **Your browser is out of date** (`dashboard_browser_nag`). They are
  security warnings and appear only when there is something to fix. Add
  them to the list below if a project really wants them gone.
- Widgets from any plugin not in the list.
- The network admin and user dashboards (`wp_network_dashboard_setup`,
  `wp_user_dashboard_setup`) are not touched.

## Hooks

### `isudev_library/extensions/reset_admin_dashboard/widgets`

The widget IDs removed. Add or drop entries; anything that is not a
non-empty string is ignored, and a non-array falls back to the default list.

```php
add_filter(
	'isudev_library/extensions/reset_admin_dashboard/widgets',
	function ( array $widgets ): array {
		$widgets[] = 'googlerevsid'; // WP Google Review Slider.
		return array_diff( $widgets, array( 'dashboard_site_health' ) ); // Keep Site Health.
	}
);
```

Find a widget's ID in the dashboard's HTML: it is the `id` of its
`<div class="postbox">`.

### `isudev_library/extensions/reset_admin_dashboard/remove_welcome_panel`

```php
add_filter( 'isudev_library/extensions/reset_admin_dashboard/remove_welcome_panel', '__return_false' );
```

## What it does not do

- It does not disable anything the widgets point to: Site Health, Quick
  Draft's drafts and the plugins keep working on their own screens.
- No `isudev.json` settings.

## What it relies on

`wp_dashboard_setup`, `remove_meta_box()`, the `welcome_panel` action and
`wp_welcome_panel()`, and the widget IDs above (core:
`wp-admin/includes/dashboard.php`; plugins: their own `wp_add_dashboard_widget()`
calls). A plugin that renames its widget ID shows up again — update the list.

## How to check it still works

Log in as an administrator and open **Dashboard**: no Welcome panel and none
of the widgets in the table. *Screen Options* no longer lists them.
