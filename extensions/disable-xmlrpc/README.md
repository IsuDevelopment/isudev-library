# Disable XML-RPC

Shuts `xmlrpc.php` — WordPress's pre-REST remote API — for sites where nothing
uses it. It is a favourite of password-guessing bots (`system.multicall` tries
hundreds of passwords in one request) and of pingback abuse.

## What it changes

- **Every request to `xmlrpc.php` is refused** with a bare `403`
  (`text/plain`, "XML-RPC is disabled.", no-cache), on `init` priority 1 —
  before any method runs, before the RSD document (`xmlrpc.php?rsd`) is
  printed, before a login is attempted. `xmlrpc.php` defines `XMLRPC_REQUEST`
  before it loads WordPress, which is how the request is recognised.
- **Belt and braces for code that builds the server itself:**
  `xmlrpc_methods` returns no methods and `xmlrpc_enabled` is `false`.
  `xmlrpc_enabled` on its own is **not** enough, which is why this extension
  exists: core only uses it to refuse the methods that log in, and the IXR
  server always adds `system.multicall`, `system.listMethods` and
  `system.getCapabilities` on top of whatever `xmlrpc_methods` returns.
- **XML-RPC is no longer advertised:** the `X-Pingback` response header and the
  `<link rel="EditURI">` (RSD) tag in `<head>` are removed. Core stopped
  printing the `wlwmanifest` link in WordPress 6.3, so there is nothing to
  remove there.

Outgoing pingbacks and update-service pings are not affected — they are
requests WordPress makes to other sites, not requests to `xmlrpc.php`.
Settings → Discussion and Settings → Writing control those.

## What it breaks

- **Jetpack**: its WordPress.com connection talks to the site over XML-RPC,
  so the connection and everything built on it stop.
- Old desktop and mobile publishing apps and services that still post over
  XML-RPC. The current WordPress mobile apps use the REST API with an
  application password and keep working (unless
  [Disable application passwords](../disable-application-passwords/README.md)
  is on).
- Incoming pingbacks. Trackbacks go to `wp-trackback.php` and are not
  affected; [Disable comments](../disable-comments/README.md) refuses those.

## What it relies on

Core: the `XMLRPC_REQUEST` constant defined by `xmlrpc.php`, `init`,
`xmlrpc_methods`, `xmlrpc_enabled`, `wp_headers` and `rsd_link` hooked on
`wp_head`. [Disable comments](../disable-comments/README.md) and
[Disable posts](../disable-posts/README.md) also filter `xmlrpc_methods`, each
dropping its own keys; this extension runs at priority 100 and empties the list,
so any combination works.

## Hooks

### `isudev_library/extensions/disable_xmlrpc/allow_request`

Return `true` to let XML-RPC through after all — every method and core's own
`xmlrpc_enabled` decision come back; the header and the RSD link stay removed.
Useful for a staging environment or a temporary Jetpack connection:

```php
add_filter(
	'isudev_library/extensions/disable_xmlrpc/allow_request',
	fn() => 'staging' === wp_get_environment_type()
);
```

## Recommended: deny it in the web server too

The refusal here is cheap, but WordPress still loads to send it. Denying the
file in the web server (or the WAF / CDN) stops the traffic before PHP runs:

```nginx
location = /xmlrpc.php { deny all; access_log off; log_not_found off; }
```

```apache
<Files xmlrpc.php>
	Require all denied
</Files>
```

Keep the extension on anyway: it covers the setups where the server rule is
missing, and it removes the header and the RSD link, which a server rule does
not.

## When it does not work

- **`xmlrpc.php` still answers 200.** A page cache or the web server is
  answering, or a plugin returns `true` from the `allow_request` filter.
- **`X-Pingback` is still sent.** A plugin adds it after `wp_headers`, or the
  theme prints `<link rel="pingback">` itself (`bloginfo( 'pingback_url' )` in
  `header.php`).

## How to check it still works

```bash
curl -si -X POST -d '<?xml version="1.0"?><methodCall><methodName>system.listMethods</methodName></methodCall>' https://example.test/xmlrpc.php | head -1   # 403
curl -si 'https://example.test/xmlrpc.php?rsd' | head -1   # 403
curl -sI https://example.test/ | grep -i x-pingback        # nothing
```
