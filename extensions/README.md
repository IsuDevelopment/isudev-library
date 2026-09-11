# Extensions

Quality-of-life features that are not blocks: an admin column, a post type
rename, a plugin integration. Each one is a directory here, switchable from
**IsuDev Library → Extensions**, and off until someone turns it on.

| Extension | Category | Needs |
| --- | --- | --- |
| [Last edited column](last-edited-column/README.md) | Admin experience | — |
| [Show template name](template-post-state/README.md) | Admin experience | — |
| [Site logo in General settings](site-logo-option/README.md) | Admin experience | — |
| [Disable posts](disable-posts/README.md) | Content | — |
| [Rename posts to articles](post-rename/README.md) | Content | — |
| [Skip links](skip-links/README.md) | Accessibility | — |
| [Gravity Forms block wrapper](gravity-forms-wrapper/README.md) | Plugin integrations | Gravity Forms |
| [Lock the Gravity Forms block theme](gravity-forms-theme-lock/README.md) | Plugin integrations | Gravity Forms |

## How one is wired

`Extensions::load()` runs on `init` at priority 0 — ahead of the block `Loader`
and of anything a theme adds on `init`, because post type labels, admin menu
entries and the registered `post` object are all read later in the request.

For each **enabled** extension it requires the descriptor's `bootstrap` files and
then calls its `boot()`. A disabled extension is never required at all, so its
code does not exist in the request: no hooks, no cost.

That is why **nothing in an extension may register hooks at the top level of a
file**. The descriptor is read for every extension, enabled or not, during
discovery; only `boot()` is called selectively. A stray `add_filter()` outside a
function would fire for an extension the operator has switched off.

## Adding one

Create `extensions/<slug>/` with three files. The directory name is the slug, and
`tools/check.php` enforces that they match.

### `extension.php` — the descriptor

Returns an array and **registers nothing**. This file is loaded for every
extension on every request, so it must stay a plain array literal.

```php
<?php
declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'my-extension',
	'title'       => \__( 'My extension', 'isudev-library' ),
	'description' => \__( 'One sentence an operator can act on.', 'isudev-library' ),
	'category'    => 'admin',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\MyExtension\\boot',
	'requires'    => array(
		'label' => 'Gravity Forms',
		'class' => 'GFForms',
	),
);
```

| Key | Required | Meaning |
| --- | --- | --- |
| `slug` | yes | Must equal the directory name. |
| `title` | yes | Shown in the panel. |
| `description` | no | Shown under the title. Write it for the person deciding whether to switch it on, not for us. |
| `category` | no | One of `admin`, `content`, `accessibility`, `plugins`. Defaults to `admin`. Add new ones in `Extensions::categories()`. |
| `default` | no | State when neither the panel nor `isudev.json` has an opinion. Defaults to `false` — see below. |
| `bootstrap` | no | Files required, relative to the extension directory, before `boot` is called. |
| `boot` | no | Callable name invoked after the bootstrap files load. |
| `requires` | no | A third-party dependency: `label` (shown in the panel) plus `class` or `function` to detect it by. Unmet means the extension cannot be enabled at all. |

### `hooks.php` — the code

Namespace it `IsuDevLibrary\Extensions\<StudlySlug>`, and put every
`add_action()` / `add_filter()` inside `boot()`.

```php
<?php
declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\MyExtension;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_filter( 'some_hook', __NAMESPACE__ . '\\do_the_thing' );
}
```

### `README.md` — the instructions

Required. `tools/check.php` fails without one. Write it for the person operating
the extension: what it changes, where that shows up, every filter it offers with
a copy-pasteable example, and what to check when it appears to do nothing.

## Conventions worth keeping

- **Default to off.** A block only appears when an editor inserts it; an
  extension changes the admin or the front end the moment it loads. Switching one
  on is the operator's decision. Set `'default' => true` only for something that
  cannot surprise anyone.
- **Make the interesting parts filterable**, and name the filter
  `isudev_library/extensions/<snake_slug>/<thing>`. These features exist because
  they were copied between projects with one or two values changed; a filter is
  what stops the ninth project needing a ninth copy.
- **Document every filter in the README.** A filter nobody can find is a filter
  nobody uses.
- **Validate what comes back from a filter.** Every one of these is a public
  extension point; a theme returning the wrong shape should fall back to the
  default, not fatal.
- **One concern per extension.** Renaming posts and hiding posts are two
  extensions, not one with a flag, so an operator can have either.
- **Say what a change does not do.** A block attribute default does not rewrite
  saved content; a label change does not change permalinks. Those are the notes
  that save a support conversation.

## Pinning in code

A theme can lock an extension on or off in its `isudev.json`, exactly as it can a
block. The panel then shows the toggle as managed in code:

```json
{
	"library": {
		"extensions": {
			"skip-links": { "enabled": true },
			"disable-posts": { "enabled": false }
		}
	}
}
```

## Checks

`php tools/check.php` covers the pure parts of the registry and, for every
extension shipped here, verifies that the descriptor normalizes, the slug matches
the directory, the category is known, the bootstrap files exist, the `boot`
callback is callable once they are loaded, and the README is present.
