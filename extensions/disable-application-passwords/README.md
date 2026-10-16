# Disable application passwords

Switches off **application passwords** — the per-app passwords (Users →
Profile → *Application Passwords*) that let an external app or script sign in to
the REST API and XML-RPC with HTTP Basic auth. For sites where nothing should
sign in that way, so a leaked one cannot be used either.

## What it changes

`wp_is_application_passwords_available` is forced to `false` (priority 100).
Core checks it everywhere application passwords appear, and
`wp_is_application_passwords_available_for_user()` checks it first, so:

- **Profile screens.** The *Application Passwords* section is gone from Users →
  Profile and Users → Edit user.
- **Existing passwords stop working.** A REST or XML-RPC request signed with
  one is rejected with `application_passwords_disabled` — "Application
  passwords are not available." — instead of being logged in. The passwords
  are not deleted; they work again when the extension is switched off.
- **The authorise flow** (`wp-admin/authorize-application.php`, used by apps to
  request a password) shows "Application passwords are not available."
- **REST.** The `/wp/v2/users/<id>/application-passwords` routes refuse, and
  the REST index no longer announces the `application-passwords`
  authentication method, so apps do not offer it.

Cookie logins (the admin, the block editor) and REST requests made from the
admin with a nonce are not affected.

## What it breaks

Anything that signs in with an application password:

- the WordPress mobile apps when connected to a self-hosted site by
  username + application password;
- external integrations and automations (Zapier, Make, n8n, custom scripts,
  headless front ends, deploy or content-sync tools) that call the REST API
  with Basic auth;
- other sites or plugins that connect over the REST API this way.

OAuth- or JWT-based plugins, and plugins that keep their own API keys, are not
affected.

## What it relies on

Core: the `wp_is_application_passwords_available` filter, read by
`wp_is_application_passwords_available_for_user()`,
`wp_authenticate_application_password()`, `wp_validate_application_password()`,
the REST index, the application-passwords REST controller, `user-edit.php` and
`authorize-application.php`.

## Hooks

None of its own. To keep application passwords for some users only, leave this
extension off and use core's filter instead:

```php
add_filter(
	'wp_is_application_passwords_available_for_user',
	fn( bool $available, WP_User $user ): bool => $available && user_can( $user, 'manage_options' ),
	10,
	2
);
```

## When it does not work

- **The profile section is still there.** Another plugin returns `true` from
  `wp_is_application_passwords_available` at a priority above 100.
- **An integration still gets in.** It is not using an application password —
  check for a plugin-specific API key, OAuth or JWT plugin.

## How to check it still works

Users → Profile has no *Application Passwords* section, and a request with an
existing application password is refused:

```bash
curl -s -u 'admin:xxxx xxxx xxxx xxxx xxxx xxxx' https://example.test/wp-json/wp/v2/users/me | head -c 120
# an error (application_passwords_disabled), not the user
```
