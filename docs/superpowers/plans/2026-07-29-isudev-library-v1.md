# isudev-library v1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Zbudować self-contained plugin `isudev-library` z fundamentem rejestru bloków, blokiem `isudev/site-header` zmigrowanym z `isudev-header` i panelem React do włączania/wyłączania bloków.

**Architecture:** Deskryptory PHP w `src/blocks/<slug>/block.php` opisują bloki; `Registry` rozstrzyga stan włączenia czystą funkcją, `Loader` jest jedynym miejscem wywołującym `register_block_type()`. Cała logika decyzyjna (precedencja, merge configu, budowanie wariacji) to funkcje czyste bez wywołań WP — testowane plain-PHP runnerem; wokół nich cienkie adaptery WP. Bloki renderują się wyłącznie w PHP.

**Tech Stack:** WordPress 7.0.2, PHP 7.4+ (lokalnie 8.4.6), `@wordpress/scripts` 31.8.0, React 19 przez `@wordpress/element`, Node 22.22.2, Playwright + `@axe-core/playwright`, phpcs + WPCS.

**Spec:** `docs/superpowers/specs/2026-07-29-isudev-library-design.md` — czytaj przy każdym zadaniu.

## Global Constraints

Każde zadanie implicite podlega tym regułom.

- **Katalog roboczy:** `/Users/lukaszbiedron/Local Sites/isudev-library/app/public/wp-content/plugins/isudev-library`. Wszystkie ścieżki w planie są względne do niego, chyba że napisano inaczej.
- **Node:** uruchom `nvm use` przed każdą komendą npm. `.nvmrc` = `22.22.2`. Ta sesja startuje na 20.19.6 — bez `nvm use` `npm install` odrzuci `engines`.
- **PHP namespace:** `IsuDevLibrary\`. Nazwy bloków: `isudev/<slug>`. Text domain: `isudev-library` (jedyna w całym pluginie). JS global: `window.isudevLibrary`. REST: `isudev-library/v1`. Opcje: `isudev_library_blocks`, `isudev_library_settings`. Prefix hooków: `isudev_library/`. CSS custom properties: `--isudev-*`.
- **Każdy plik PHP:** `declare( strict_types = 1 );` i `defined( 'ABSPATH' ) || exit;` (poza `tools/`).
- **Konwencja nazw plików:** klasy w `class-*.php` (WPCS).
- **Zero rejestracji hooków na poziomie pliku w `includes/`.** Wszystkie `add_action`/`add_filter` idą przez funkcje `boot()` wołane z `isudev-library.php`. Powód: pliki muszą być `require`-owalne przez plain-PHP runner testowy bez WP.
- **Funkcje czyste nie wywołują funkcji WP.** Żadnego `apply_filters`, `__()`, `esc_*`, `_doing_it_wrong` w funkcjach oznaczonych jako czyste. Adaptery WP je opakowują.
- **`build/` jest commitowany** (inaczej niż w `isudev-header`). Nie dodawaj go do `.gitignore`.
- **Nazwy katalogów bloków muszą być globalnie unikalne.** `build-blocks-manifest.js` kluczuje manifest przez `basename( dirname( file ) )`, nie przez ścieżkę względną.
- **Bloki `apiVersion: 3`.** W kodzie edytora nigdy globalny `document`/`window` — `element.ownerDocument` przez `useRefEffect`. **Wyjątek: `view.js` (frontend) używa globali legalnie — nie zmieniaj tego, view scripts nie są iframe'owane.**
- **Nie używaj** `DimensionControl` (usunięty w WP 7.0) ani `__next40pxDefaultSize` (no-op w 7.1). Tylko stabilizowane nazwy z `@wordpress/components`.
- **Nie używaj `_wp_array_get()`** (prywatne API rdzenia) ani `assert()`/`assert_options()` (deprecated w PHP 8.3+).
- **Komentarze blokowe: tekst musi zaczynać się w nowej linii.** `/* --- opis --- */` z tekstem w pierwszej linii to **ERROR** `Squiz.Commenting.BlockComment.NoNewLine` i `phpcs` pada. Używaj formy `/*` / ` * opis` / ` */`. Sprawdzone empirycznie na tym repo.
- **Długi opis w docblocku musi zaczynać się wielką literą.** `Generic.Commenting.DocComment.LongNotCapital` to **ERROR**, więc akapit rozpoczynający się od nazwy funkcji (`build_variations() is pure…`) wywala lint. Przeformułuj (`The build_variations() function is pure…`). Sprawdzone empirycznie na tym repo.
- **Nazwy parametrów nie mogą być słowami zarezerwowanymi PHP.** WPCS 3.x (przez PHPCSExtra) zgłasza `Universal.NamingConventions.NoReservedKeywordParameterNames`, a `phpcs` wychodzi wtedy z kodem 1 — `composer run lint:php` pada. Sprawdzone empirycznie na tym repo; odrzucane są m.in.: `$default`, `$parent`, `$namespace`, `$array`, `$class`, `$function`, `$list`, `$new`, `$print`, `$static`, `$string`, `$use`. Przyjęte zamienniki w tym projekcie: `$fallback`, `$parent_config`, `$child_config`, `$variation_namespace`. Dotyczy **wyłącznie parametrów** — zmienne lokalne i klucze `foreach` mogą nazywać się dowolnie (`foreach ( $x as $namespace => $y )` jest legalne).
- **wp-cli nie ma dostępu do bazy tego Locala.** Nie pisz kroków weryfikacyjnych opartych na `wp eval`, `wp plugin`, `wp option`. Weryfikacja: plain-PHP checks + Playwright po HTTP na `http://isudev-library.local/`.
- **Po każdej zmianie kodu:** `npm run lint:js`, `npm run lint:css`, `composer run lint:php` muszą być zielone przed commitem.
- **Nigdy nie uruchamiaj** `npm start` / watchera w automatyzacji. Tylko jednorazowy `npm run build`.
- **Nie czytaj ani nie modyfikuj** `wp-config.php` ani żadnego pliku z sekretami.

## File Structure

| Plik | Odpowiedzialność |
| --- | --- |
| `isudev-library.php` | Nagłówek pluginu, stałe, textdomain, `require`, wywołania `boot()`. Nic więcej. |
| `includes/utils/array.php` | `array_get()` — czysta. Zamiennik `_wp_array_get`. |
| `includes/utils/icon.php` | Rejestr ikon: czysty `build_svg()` + adapter `icon()` z filtrem. |
| `includes/utils/attributes.php` | Czyste budowanie `--isudev-*` custom properties i list klas. |
| `includes/config.php` | Czyste `decode()`/`extract_library()`/`resolve_block_value()` + adaptery `get_config()`, `get_block_config()`. |
| `includes/class-registry.php` | Czyste `normalize_descriptor()`, `build_dependents()`, `resolve_states()` + adaptery `descriptors()`, `blocks()`, `is_enabled()`. |
| `includes/class-loader.php` | Jedyne miejsce z `register_block_type()`. `boot()`, `register()`. |
| `includes/variations.php` | Czyste `build_variations()` + adapter `attach()` na `get_block_type_variations`. |
| `includes/settings.php` | `register_setting()` dla `isudev_library_settings`, getter. |
| `includes/rest.php` | Kontroler `isudev-library/v1/blocks`, `permission_check()`. |
| `includes/admin.php` | `show_admin()`, `capability()`, menu, enqueue panelu. |
| `src/blocks/site-header/block.php` | Deskryptor. Zwraca tablicę, nie rejestruje niczego. |
| `src/blocks/site-header/render.php` | Szablon SSR. |
| `src/blocks/site-header/inc/*.php` | Walker + helpery renderu, tylko dla tego bloku. |
| `src/admin/*` | Panel React. |
| `tools/check.php` | Runner plain-PHP checków (kody wyjścia, bez `assert()`). |
| `tools/checks/*.php` | Checki funkcji czystych. |
| `e2e/*` | Playwright + axe. |

---

### Task 1: Scaffold repo, tooling i runner checków

**Files:**
- Create: `.gitignore`, `.distignore`, `.nvmrc`, `.editorconfig`
- Create: `package.json`, `composer.json`, `phpcs.xml.dist`, `webpack.config.js`
- Create: `isudev-library.php`
- Create: `tools/check.php`, `tools/checks/.gitkeep`
- Existing: `docs/superpowers/specs/2026-07-29-isudev-library-design.md` (do commitu)

**Interfaces:**
- Consumes: nic.
- Produces: stałe `IsuDevLibrary\VERSION` (string `'1.0.0'`), `IsuDevLibrary\PATH` (string, ścieżka z trailing slash), `IsuDevLibrary\URL` (string, URL z trailing slash). Runner: `php tools/check.php` → exit 0 gdy wszystko przechodzi, 1 gdy nie. Klasa `Checks` z metodami `Checks::is( string $label, $actual, $expected )` i `Checks::true( string $label, $actual )`.

- [ ] **Step 1: Potwierdź, że jesteś na gałęzi roboczej**

Repo i gałąź zostały utworzone przed startem planu. Wszystkie zadania commitują
na `feat/isudev-library-v1`; `main` ma tylko commit startowy, żeby dało się
porzucić całość jednym ruchem.

```bash
cd "/Users/lukaszbiedron/Local Sites/isudev-library/app/public/wp-content/plugins/isudev-library"
git rev-parse --abbrev-ref HEAD
```

Oczekiwane: `feat/isudev-library-v1`. Jeśli zobaczysz `main` — przełącz się
(`git checkout feat/isudev-library-v1`) i nie commituj na `main`.

- [ ] **Step 2: Napisz `.gitignore`**

`build/` **nie** jest ignorowany — spec §2 wymaga commitowania go.

```gitignore
/node_modules/
/vendor/
/test-results/
/playwright-report/
/blob-report/
/playwright/.cache/
.DS_Store
```

- [ ] **Step 3: Napisz `.distignore`**

```
/.git
/.github
/node_modules
/vendor
/src
/e2e
/docs
/tools
/test-results
/playwright-report
.distignore
.editorconfig
.gitignore
.nvmrc
composer.json
composer.lock
package.json
package-lock.json
phpcs.xml.dist
playwright.config.js
webpack.config.js
```

- [ ] **Step 4: Napisz `.nvmrc` i `.editorconfig`**

`.nvmrc`:

```
22.22.2
```

`.editorconfig`:

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
indent_style = tab
trim_trailing_whitespace = true

[*.{yml,yaml,json,md}]
indent_style = space
indent_size = 2
```

- [ ] **Step 5: Napisz `package.json`**

```json
{
	"name": "@isudev/library",
	"version": "1.0.0",
	"description": "Reusable Gutenberg blocks and tools by IsuDev. Server-rendered, modular, toggleable.",
	"license": "GPL-2.0-or-later",
	"private": true,
	"engines": {
		"node": ">=22.22.2 <23",
		"npm": ">=10"
	},
	"devDependencies": {
		"@axe-core/playwright": "^4.12.1",
		"@playwright/test": "^1.61.1",
		"@wordpress/api-fetch": "^7.51.0",
		"@wordpress/block-editor": "^15.16.0",
		"@wordpress/blocks": "^15.16.0",
		"@wordpress/components": "^36.1.0",
		"@wordpress/core-data": "^7.51.0",
		"@wordpress/data": "^10.51.0",
		"@wordpress/element": "^6.46.0",
		"@wordpress/i18n": "^6.17.0",
		"@wordpress/prettier-config": "^4.49.0",
		"@wordpress/scripts": "^31.8.0"
	},
	"prettier": "@wordpress/prettier-config",
	"scripts": {
		"build": "wp-scripts build --blocks-manifest",
		"start": "wp-scripts start --blocks-manifest",
		"format": "wp-scripts format",
		"lint:js": "wp-scripts lint-js",
		"lint:js:fix": "wp-scripts lint-js --fix",
		"lint:css": "wp-scripts lint-style",
		"lint:css:fix": "wp-scripts lint-style --fix",
		"test:php": "php tools/check.php",
		"test:e2e": "playwright test",
		"test:e2e:report": "playwright show-report",
		"test:e2e:install": "playwright install chromium",
		"i18n:make-pot": "wp i18n make-pot . languages/isudev-library.pot --exclude=build,node_modules,vendor,e2e,tools,docs",
		"i18n:make-json": "wp i18n make-json languages --no-purge",
		"i18n:make-mo": "wp i18n make-mo languages"
	}
}
```

- [ ] **Step 6: Napisz `composer.json`**

```json
{
	"name": "isudev/library",
	"description": "Reusable Gutenberg blocks and tools by IsuDev. Server-rendered, modular, toggleable.",
	"type": "wordpress-plugin",
	"license": "GPL-2.0-or-later",
	"require": {
		"php": ">=7.4"
	},
	"require-dev": {
		"squizlabs/php_codesniffer": "^3.9",
		"wp-coding-standards/wpcs": "^3.1",
		"phpcompatibility/phpcompatibility-wp": "^2.1",
		"dealerdirect/phpcodesniffer-composer-installer": "^1.0"
	},
	"scripts": {
		"lint:php": "phpcs",
		"lint:php:fix": "phpcbf"
	},
	"config": {
		"allow-plugins": {
			"dealerdirect/phpcodesniffer-composer-installer": true
		}
	}
}
```

- [ ] **Step 7: Napisz `phpcs.xml.dist`**

Bazuje na pliku z `isudev-header`, z podmienionymi prefiksami i wykluczeniem `tools/`.

```xml
<?xml version="1.0"?>
<ruleset name="IsuDev Library">
	<description>WordPress Coding Standards for the isudev-library plugin.</description>

	<file>.</file>
	<arg name="extensions" value="php"/>
	<arg name="basepath" value="."/>
	<arg name="colors"/>
	<arg value="sp"/>

	<exclude-pattern>*/build/*</exclude-pattern>
	<exclude-pattern>*/node_modules/*</exclude-pattern>
	<exclude-pattern>*/vendor/*</exclude-pattern>
	<exclude-pattern>*/languages/*</exclude-pattern>
	<!-- tools/ holds a dev-only plain-PHP check runner, not distributed plugin code. -->
	<exclude-pattern>*/tools/*</exclude-pattern>

	<rule ref="WordPress">
		<!-- Hooks are intentionally namespaced with a slash (isudev_library/foo). -->
		<exclude name="WordPress.NamingConventions.ValidHookName.UseUnderscores"/>
	</rule>

	<!-- Block render templates run in WP_Block::render() method scope, so their
	     top-level variables are local, not globals — a PrefixAllGlobals false positive. -->
	<rule ref="WordPress.NamingConventions.PrefixAllGlobals">
		<exclude-pattern>*/render.php</exclude-pattern>
	</rule>

	<config name="minimum_wp_version" value="6.7"/>
	<config name="testVersion" value="7.4-"/>

	<rule ref="WordPress.WP.I18n">
		<properties>
			<property name="text_domain" type="array">
				<element value="isudev-library"/>
			</property>
		</properties>
	</rule>

	<rule ref="WordPress.NamingConventions.PrefixAllGlobals">
		<properties>
			<property name="prefixes" type="array">
				<element value="IsuDevLibrary"/>
				<element value="isudev_library"/>
			</property>
		</properties>
	</rule>
</ruleset>
```

- [ ] **Step 8: Napisz `webpack.config.js`**

Jedyny powód istnienia tego pliku: `getWebpackEntryPoints()` zwraca **tylko** entry pochodzące z `block.json`, gdy takie znajdzie, więc panel admina nie zbudowałby się sam.

```js
/**
 * WordPress dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

/**
 * External dependencies
 */
const fs = require('fs');
const path = require('path');

const adminEntry = path.resolve(__dirname, 'src/admin/index.js');

module.exports = {
	...defaultConfig,
	entry: {
		// Spread the block entry points discovered from src/blocks/**/block.json.
		...(typeof defaultConfig.entry === 'function'
			? defaultConfig.entry()
			: defaultConfig.entry),
		// The admin panel lands in a later task than the first build, so this entry
		// is added only once its source exists. Without the guard, webpack fails
		// the whole build on an unresolved entry and emits nothing at all.
		//
		// The key must be 'admin/index', not 'admin'. wp-scripts writes
		// output.filename as '[name].js', so a bare key emits a flat build/admin.js
		// while includes/admin.php enqueues build/admin/index.js. The block entries
		// only nest because their names already contain slashes.
		...(fs.existsSync(adminEntry) ? { 'admin/index': adminEntry } : {}),
	},
};
```

**Entry `admin` musi być warunkowy.** `src/admin/index.js` powstaje dopiero
w Task 13, a pierwszy `npm run build` leci w Task 10 — bezwarunkowy entry wywala
cały build z `Field 'browser' doesn't contain a valid alias configuration` i nie
emituje **żadnego** pliku, także bloków. Nie twórz zaślepki `src/admin/index.js`,
żeby to obejść.

- [ ] **Step 9: Napisz `isudev-library.php`**

```php
<?php
/**
 * Plugin Name:       IsuDev Library
 * Plugin URI:        https://isudev.pl
 * Description:       Reusable Gutenberg blocks and tools by IsuDev. Server-rendered, modular, toggleable from the admin.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            IsuDev
 * Author URI:        https://isudev.pl
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       isudev-library
 * Domain Path:       /languages
 * Update URI:        false
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.0';

define( 'IsuDevLibrary\\PATH', plugin_dir_path( __FILE__ ) );
define( 'IsuDevLibrary\\URL', plugin_dir_url( __FILE__ ) );

require_once PATH . 'includes/utils/array.php';

/**
 * Load the plugin text domain for PHP translations.
 *
 * @return void
 */
function load_textdomain(): void {
	load_plugin_textdomain( 'isudev-library', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\\load_textdomain' );
```

Kolejne zadania dopisują tu `require_once` i wywołania `boot()`. Nie dodawaj ich teraz.

- [ ] **Step 10: Napisz `tools/check.php`**

`assert()`/`assert_options()` są deprecated w PHP 8.3+, więc runner opiera się na porównaniach i kodzie wyjścia.

```php
<?php
/**
 * Plain-PHP check runner for pure functions. No WordPress, no database.
 *
 * Usage: php tools/check.php
 * Exit code: 0 when every check passes, 1 otherwise.
 *
 * These checks cover functions that must not call WordPress. They will be
 * replaced by PHPUnit once a test harness exists (see spec §17).
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

/**
 * Minimal assertion collector.
 */
final class Checks {

	/**
	 * Number of passing checks.
	 *
	 * @var int
	 */
	public static $passed = 0;

	/**
	 * Failure messages.
	 *
	 * @var array
	 */
	public static $failed = array();

	/**
	 * Assert strict equality.
	 *
	 * @param string $label    Human-readable check name.
	 * @param mixed  $actual   Value produced by the code under test.
	 * @param mixed  $expected Expected value.
	 * @return void
	 */
	public static function is( string $label, $actual, $expected ): void {
		if ( $actual === $expected ) {
			++self::$passed;
			return;
		}

		self::$failed[] = sprintf(
			"%s\n    expected: %s\n    actual:   %s",
			$label,
			var_export( $expected, true ),
			var_export( $actual, true )
		);
	}

	/**
	 * Assert the value is boolean true.
	 *
	 * @param string $label  Human-readable check name.
	 * @param mixed  $actual Value produced by the code under test.
	 * @return void
	 */
	public static function true( string $label, $actual ): void {
		self::is( $label, $actual, true );
	}
}

// Plugin files guard on ABSPATH. Define it so they can be required standalone.
defined( 'ABSPATH' ) || define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$files = glob( __DIR__ . '/checks/*.php' );
$files = is_array( $files ) ? $files : array();
sort( $files );

foreach ( $files as $file ) {
	require_once $file;
}

$failed = count( Checks::$failed );

foreach ( Checks::$failed as $message ) {
	fwrite( STDERR, 'FAIL  ' . $message . "\n" );
}

printf(
	"%d passed, %d failed (%d check files)\n",
	Checks::$passed,
	$failed,
	count( $files )
);

exit( $failed > 0 ? 1 : 0 );
```

- [ ] **Step 11: Utwórz `tools/checks/.gitkeep`**

```bash
mkdir -p tools/checks && touch tools/checks/.gitkeep
```

- [ ] **Step 12: Zainstaluj zależności**

```bash
nvm use
npm install
composer install
```

Oczekiwane: brak błędów. Jeśli `npm install` odrzuci `engines` — nie uruchomiłeś `nvm use`.

- [ ] **Step 13: Uruchom runner — musi przejść z zerem checków**

```bash
npm run test:php
```

Oczekiwane: `0 passed, 0 failed (0 check files)`, exit 0.

- [ ] **Step 14: Sprawdź składnię i lint**

```bash
php -l isudev-library.php
composer run lint:php
npm run lint:js
```

Oczekiwane: `No syntax errors detected`, phpcs bez błędów, eslint bez błędów. `lint:css` pominąć — nie ma jeszcze plików CSS.

- [ ] **Step 15: Commit**

```bash
git add -A
git commit -m "$(cat <<'EOF'
chore: scaffold isudev-library plugin, tooling and check runner

Adds the plugin bootstrap, build/lint tooling and a plain-PHP check runner
for pure functions. Includes the accepted design spec.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: `array_get()` — czysta funkcja dostępu do zagnieżdżonych tablic

Zamiennik prywatnego `_wp_array_get()` z rdzenia.

**Files:**
- Create: `includes/utils/array.php`
- Test: `tools/checks/10-array.php`

**Interfaces:**
- Consumes: `Checks` z Task 1.
- Produces: `IsuDevLibrary\Utils\array_get( array $data, array $path, $fallback = null )` → mixed. Czysta. Zwraca `$fallback` gdy którykolwiek segment `$path` nie istnieje lub gdy trafi na wartość nie-tablicową przed końcem ścieżki. Pusta `$path` zwraca `$data`.

**Nie nazywaj parametru `$default`.** WPCS 3.x (przez PHPCSExtra) zgłasza
`Universal.NamingConventions.NoReservedKeywordParameterNames` dla `$default`,
a `phpcs` wychodzi wtedy z kodem 1 — `composer run lint:php` pada. Sprawdzone
empirycznie na tym repo. Ta sama reguła obowiązuje w każdym zadaniu.

- [ ] **Step 1: Napisz failing check**

`tools/checks/10-array.php`:

```php
<?php
/**
 * Checks for IsuDevLibrary\Utils\array_get().
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/utils/array.php';

use function IsuDevLibrary\Utils\array_get;

$data = array(
	'library' => array(
		'isudev/site-header' => array(
			'enabled'    => false,
			'sticky'     => true,
			'variations' => array(
				'compact' => array( 'title' => 'Compact' ),
			),
		),
	),
	'scalar'  => 'not-an-array',
	// Present, but holds null. Pins array_key_exists() vs isset(): isset() is the
	// only case where a stored null is indistinguishable from a missing key.
	'nullish' => null,
);

Checks::is( 'array_get: empty path returns whole array', array_get( $data, array() ), $data );
Checks::is( 'array_get: single segment', array_get( $data, array( 'scalar' ) ), 'not-an-array' );
Checks::is( 'array_get: deep hit', array_get( $data, array( 'library', 'isudev/site-header', 'sticky' ) ), true );
Checks::is( 'array_get: deep hit on false value', array_get( $data, array( 'library', 'isudev/site-header', 'enabled' ) ), false );
Checks::is( 'array_get: nested variation', array_get( $data, array( 'library', 'isudev/site-header', 'variations', 'compact', 'title' ) ), 'Compact' );
Checks::is( 'array_get: missing key returns default', array_get( $data, array( 'library', 'nope' ), 'fallback' ), 'fallback' );
Checks::is( 'array_get: default is null when omitted', array_get( $data, array( 'nope' ) ), null );
Checks::is( 'array_get: traversing through a scalar returns fallback', array_get( $data, array( 'scalar', 'deeper' ), 'fallback' ), 'fallback' );
Checks::is( 'array_get: absent integer key returns fallback', array_get( $data, array( 0 ), 'fallback' ), 'fallback' );

// A key that EXISTS but holds null must return null, not the fallback. This is
// the whole reason the implementation uses array_key_exists() and not isset():
// swapping in isset() would still pass every other check in this file.
Checks::is( 'array_get: existing key holding null returns null, not fallback', array_get( $data, array( 'nullish' ), 'fallback' ), null );

// Exercises the segment type guard for real. An array is neither string nor int,
// so it must return the fallback rather than raising a PHP 8 TypeError inside
// array_key_exists(). Without this, the guard could be deleted and stay green.
Checks::is( 'array_get: array as a path segment returns fallback', array_get( $data, array( array( 'nope' ) ), 'fallback' ), 'fallback' );
```

- [ ] **Step 2: Uruchom check — musi się wywalić**

```bash
npm run test:php
```

Oczekiwane: PHP fatal error — `Failed to open stream: No such file or directory` dla `includes/utils/array.php`.

- [ ] **Step 3: Napisz minimalną implementację**

`includes/utils/array.php`:

```php
<?php
/**
 * Array helpers. Pure functions — no WordPress calls.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Read a value from a nested array by path.
 *
 * Replacement for the private core function `_wp_array_get()`.
 *
 * @param array $data    Source array.
 * @param array $path    Ordered list of keys to walk.
 * @param mixed $fallback Value returned when the path does not resolve.
 * @return mixed Resolved value, or $fallback.
 */
function array_get( array $data, array $path, $fallback = null ) {
	$current = $data;

	foreach ( $path as $segment ) {
		if ( ! \is_string( $segment ) && ! \is_int( $segment ) ) {
			return $fallback;
		}

		if ( ! \is_array( $current ) || ! \array_key_exists( $segment, $current ) ) {
			return $fallback;
		}

		$current = $current[ $segment ];
	}

	return $current;
}
```

- [ ] **Step 4: Uruchom check — musi przejść**

```bash
npm run test:php
```

Oczekiwane: `11 passed, 0 failed (1 check files)`, exit 0.

Uwaga do dwóch checków, które łatwo źle zrozumieć:

- `absent integer key returns fallback` przekazuje `0` (int). Implementacja **akceptuje** int jako typ klucza — zwraca `'fallback'` tylko dlatego, że klucz `0` nie istnieje w `$data`. Ten check **nie** testuje guardu typu; int-owe klucze są w PHP legalne i mają działać.
- Guard typu testuje dopiero `array as a path segment returns fallback`. Bez niego można usunąć całą gałąź `! is_string && ! is_int` i suite zostanie zielony.

Podobnie `existing key holding null returns null, not fallback` jest jedynym checkiem, który przypina `array_key_exists()` — podmiana na `isset()` przechodzi wszystkie pozostałe.

- [ ] **Step 5: Lint i commit**

```bash
composer run lint:php
git add includes/utils/array.php tools/checks/10-array.php
git commit -m "$(cat <<'EOF'
feat: add pure array_get() helper

Replaces the private core _wp_array_get() with an owned, tested equivalent.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: Czytnik `isudev.json` — funkcje czyste

**Files:**
- Create: `includes/config.php`
- Test: `tools/checks/20-config.php`

**Interfaces:**
- Consumes: `IsuDevLibrary\Utils\array_get()`.
- Produces, wszystkie czyste, w namespace `IsuDevLibrary\Config`:
  - `const CONFIG_FILE = 'isudev.json';`
  - `const CONFIG_KEY = 'library';`
  - `decode( string $json ): array` — `[]` przy błędzie JSON lub gdy top-level nie jest tablicą.
  - `extract_library( array $raw ): array` — zwraca `$raw['library']`, `[]` gdy brak lub nie-tablica.
  - `is_list_array( array $value ): bool` — czy tablica jest listą (kolejne klucze całkowite od zera). Pusta tablica to lista. Własna implementacja, bo `array_is_list()` wymaga PHP 8.1, a plugin wspiera 7.4.
  - `merge_configs( array $parent_config, array $child_config ): array` — child wygrywa. Tablice asocjacyjne scalane rekurencyjnie, **listy podmieniane w całości**, żeby child theme mógł skrócić `allowedBlocks` lub `template`. Świadomie **nie** `array_replace_recursive()` — ta scala listy indeks po indeksie, więc parent `[a, b, c]` z child `[a]` daje `[a, b, c]` i ograniczenie listy jest niemożliwe.
  - `resolve_block_value( array $config, string $block_name, array $key_path, $fallback = null, string $variation_namespace = '' )` — łańcuch: `<block>.variations.<ns>.<key_path>` → `<block>.<key_path>` → `$fallback`.

- [ ] **Step 1: Napisz failing check**

`tools/checks/20-config.php`:

```php
<?php
/**
 * Checks for the pure parts of IsuDevLibrary\Config.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/config.php';

use function IsuDevLibrary\Config\decode;
use function IsuDevLibrary\Config\extract_library;
use function IsuDevLibrary\Config\is_list_array;
use function IsuDevLibrary\Config\merge_configs;
use function IsuDevLibrary\Config\resolve_block_value;

// decode().
Checks::is( 'decode: valid object', decode( '{"library":{"a":1}}' ), array( 'library' => array( 'a' => 1 ) ) );
Checks::is( 'decode: malformed JSON yields empty array', decode( '{oops' ), array() );
Checks::is( 'decode: empty string yields empty array', decode( '' ), array() );
Checks::is( 'decode: scalar top level yields empty array', decode( '"a string"' ), array() );
Checks::is( 'decode: JSON null yields empty array', decode( 'null' ), array() );

// extract_library() — the shared-file contract: only the `library` key is ours.
$raw = array(
	'library'         => array( 'isudev/site-header' => array( 'enabled' => false ) ),
	'google-reviews'  => array( 'apiKey' => 'ignored' ),
);
Checks::is(
	'extract_library: returns only the library subtree',
	extract_library( $raw ),
	array( 'isudev/site-header' => array( 'enabled' => false ) )
);
Checks::is( 'extract_library: missing key yields empty array', extract_library( array( 'other' => 1 ) ), array() );
Checks::is( 'extract_library: non-array value yields empty array', extract_library( array( 'library' => 'nope' ) ), array() );

// merge_configs() — child theme overrides parent.
$parent = array(
	'isudev/site-header' => array(
		'enabled' => true,
		'sticky'  => true,
	),
);
$child = array(
	'isudev/site-header' => array( 'sticky' => false ),
);
// Lists are replaced wholesale so a child theme can RESTRICT one. Under
// array_replace_recursive() these three would merge index-by-index and the
// child could never shorten allowedBlocks or template — the main reason
// isudev.json exists. Each of these fails against array_replace_recursive().
Checks::is(
	'merge_configs: child list replaces the parent list wholesale',
	merge_configs(
		array( 'b' => array( 'allowedBlocks' => array( 'core/paragraph', 'core/image', 'core/button' ) ) ),
		array( 'b' => array( 'allowedBlocks' => array( 'core/paragraph' ) ) )
	),
	array( 'b' => array( 'allowedBlocks' => array( 'core/paragraph' ) ) )
);
Checks::is(
	'merge_configs: child empty list clears the parent list',
	merge_configs(
		array( 'b' => array( 'template' => array( array( 'core/heading' ), array( 'core/paragraph' ) ) ) ),
		array( 'b' => array( 'template' => array() ) )
	),
	array( 'b' => array( 'template' => array() ) )
);
Checks::is(
	'merge_configs: child scalar replaces a parent array',
	merge_configs(
		array( 'b' => array( 'sticky' => array( 'desktop' => true ) ) ),
		array( 'b' => array( 'sticky' => false ) )
	),
	array( 'b' => array( 'sticky' => false ) )
);

// Regression protection for merge paths that are correct today but untested.
// merge_configs() is hand-written logic and Tasks 4-9 build on it.
Checks::is(
	'merge_configs: recurses more than one level deep',
	merge_configs(
		array( 'a' => array( 'b' => array( 'c' => 1, 'd' => 2 ) ) ),
		array( 'a' => array( 'b' => array( 'c' => 3 ) ) )
	),
	array( 'a' => array( 'b' => array( 'c' => 3, 'd' => 2 ) ) )
);
Checks::is(
	'merge_configs: child-only key is added',
	merge_configs( array( 'a' => 1 ), array( 'b' => 2 ) ),
	array( 'a' => 1, 'b' => 2 )
);
Checks::is(
	'merge_configs: child null replaces a parent array',
	merge_configs( array( 'a' => array( 'x' => 1 ) ), array( 'a' => null ) ),
	array( 'a' => null )
);
Checks::is(
	'merge_configs: child assoc replaces a parent list',
	merge_configs( array( 'a' => array( 'x', 'y' ) ), array( 'a' => array( 'k' => 'v' ) ) ),
	array( 'a' => array( 'k' => 'v' ) )
);
Checks::is(
	'merge_configs: child list replaces a parent assoc',
	merge_configs( array( 'a' => array( 'k' => 'v' ) ), array( 'a' => array( 'x', 'y' ) ) ),
	array( 'a' => array( 'x', 'y' ) )
);

// is_list_array() — the predicate the merge depends on.
// The empty-array guard in is_list_array() is load-bearing, not defensive:
// range( 0, -1 ) counts down and yields array( 0, -1 ), so without the guard
// is_list_array( array() ) would return false.
Checks::true( 'is_list_array: empty array is a list', is_list_array( array() ) );
Checks::true( 'is_list_array: sequential from zero is a list', is_list_array( array( 'a', 'b' ) ) );
Checks::is( 'is_list_array: string keys are not a list', is_list_array( array( 'k' => 'v' ) ), false );
Checks::is( 'is_list_array: gap in integer keys is not a list', is_list_array( array( 0 => 'a', 2 => 'b' ) ), false );

Checks::is(
	'merge_configs: child overrides parent key, keeps siblings',
	merge_configs( $parent, $child ),
	array(
		'isudev/site-header' => array(
			'enabled' => true,
			'sticky'  => false,
		),
	)
);

// resolve_block_value() — variation beats block, block beats fallback.
$config = array(
	'isudev/site-header' => array(
		'sticky'     => true,
		'ariaLabel'  => 'Main',
		// Present at block level, holds null. Pins the sentinel in the block branch.
		'nullish'    => null,
		'variations' => array(
			'compact' => array(
				'sticky' => false,
				// Present at variation level, holds null. Pins the sentinel in the
				// variation branch: it must win over the block value below.
				'winner' => null,
			),
		),
		'winner'     => 'block-value',
	),
);
Checks::is( 'resolve: block-level value', resolve_block_value( $config, 'isudev/site-header', array( 'sticky' ), 'fb' ), true );
Checks::is( 'resolve: variation overrides block', resolve_block_value( $config, 'isudev/site-header', array( 'sticky' ), 'fb', 'compact' ), false );
Checks::is( 'resolve: variation falls back to block value', resolve_block_value( $config, 'isudev/site-header', array( 'ariaLabel' ), 'fb', 'compact' ), 'Main' );
Checks::is( 'resolve: unknown key returns fallback', resolve_block_value( $config, 'isudev/site-header', array( 'nope' ), 'fb' ), 'fb' );
Checks::is( 'resolve: unknown block returns fallback', resolve_block_value( $config, 'isudev/nope', array( 'sticky' ), 'fb' ), 'fb' );
Checks::is( 'resolve: unknown variation falls back to block value', resolve_block_value( $config, 'isudev/site-header', array( 'sticky' ), 'fb', 'ghost' ), true );

// The two checks below are why resolve_block_value() uses a sentinel object
// instead of `??` or a `!== null` test. Without them the sentinel could be
// removed and every other check in this file would still pass.
Checks::is( 'resolve: block key holding null returns null, not the fallback', resolve_block_value( $config, 'isudev/site-header', array( 'nullish' ), 'fb' ), null );
Checks::is( 'resolve: variation key holding null wins over the block value', resolve_block_value( $config, 'isudev/site-header', array( 'winner' ), 'fb', 'compact' ), null );
```

- [ ] **Step 2: Uruchom check — musi się wywalić**

```bash
npm run test:php
```

Oczekiwane: fatal error, brak `includes/config.php`.

- [ ] **Step 3: Napisz czyste funkcje w `includes/config.php`**

Plik dostanie adaptery WP w Task 4. Na razie tylko czyste funkcje.

```php
<?php
/**
 * Reader for the optional `isudev.json` theme config.
 *
 * Contract: this plugin reads ONLY the `library` key, ignores everything else in
 * the file, and never writes to it. Other isudev-* plugins own their own keys.
 *
 * Functions above the "WordPress adapters" marker are pure — no WP calls — so
 * they can be exercised by tools/check.php.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Config;

use function IsuDevLibrary\Utils\array_get;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/utils/array.php';

const CONFIG_FILE = 'isudev.json';
const CONFIG_KEY  = 'library';

/**
 * Decode a JSON string into an array. Pure.
 *
 * @param string $json Raw file contents.
 * @return array Decoded array, or [] when the JSON is invalid or not an object.
 */
function decode( string $json ): array {
	if ( '' === \trim( $json ) ) {
		return array();
	}

	$decoded = \json_decode( $json, true );

	if ( \JSON_ERROR_NONE !== \json_last_error() || ! \is_array( $decoded ) ) {
		return array();
	}

	return $decoded;
}

/**
 * Extract this plugin's subtree from a decoded isudev.json. Pure.
 *
 * @param array $raw Full decoded file.
 * @return array The `library` subtree, or [] when absent or malformed.
 */
function extract_library( array $raw ): array {
	$library = $raw[ CONFIG_KEY ] ?? null;

	return \is_array( $library ) ? $library : array();
}

/**
 * Whether an array is a list: keys are sequential integers starting at zero. Pure.
 *
 * Hand-rolled because `array_is_list()` needs PHP 8.1 and this plugin supports 7.4.
 *
 * @param array $value Array to inspect.
 * @return bool True for lists and for the empty array.
 */
function is_list_array( array $value ): bool {
	if ( array() === $value ) {
		return true;
	}

	return \array_keys( $value ) === \range( 0, \count( $value ) - 1 );
}

/**
 * Merge a child theme config over a parent theme config. Pure.
 *
 * Associative arrays merge recursively. Lists are replaced wholesale, so a child
 * theme can shorten one. This is deliberately NOT `array_replace_recursive()`:
 * that merges lists index by index, which makes it impossible for a child theme
 * to restrict `allowedBlocks` or `template` — the very thing isudev.json exists
 * for. Verified: parent `[a, b, c]` with child `[a]` yields `[a, b, c]` under
 * `array_replace_recursive()`.
 *
 * @param array $parent_config Parent theme subtree.
 * @param array $child_config  Child theme subtree.
 * @return array Merged config; child wins.
 */
function merge_configs( array $parent_config, array $child_config ): array {
	$merged = $parent_config;

	foreach ( $child_config as $key => $child_value ) {
		$parent_value = $merged[ $key ] ?? null;

		$both_assoc = \is_array( $child_value )
			&& \is_array( $parent_value )
			&& ! is_list_array( $child_value )
			&& ! is_list_array( $parent_value );

		$merged[ $key ] = $both_assoc
			? merge_configs( $parent_value, $child_value )
			: $child_value;
	}

	return $merged;
}

/**
 * Resolve a config value for a block, honouring variation overrides. Pure.
 *
 * Lookup order: variation value, then block value, then $fallback.
 *
 * @param array  $config              The `library` subtree.
 * @param string $block_name          Full block name, e.g. `isudev/site-header`.
 * @param array  $key_path            Ordered key path below the block (or variation).
 * @param mixed  $fallback            Value returned when nothing resolves.
 * @param string $variation_namespace Variation namespace; '' to skip variation lookup.
 * @return mixed Resolved value.
 */
function resolve_block_value( array $config, string $block_name, array $key_path, $fallback = null, string $variation_namespace = '' ) {
	$sentinel = new \stdClass();

	if ( '' !== $variation_namespace ) {
		$variation_value = array_get(
			$config,
			\array_merge( array( $block_name, 'variations', $variation_namespace ), $key_path ),
			$sentinel
		);

		if ( $sentinel !== $variation_value ) {
			return $variation_value;
		}
	}

	$block_value = array_get( $config, \array_merge( array( $block_name ), $key_path ), $sentinel );

	if ( $sentinel !== $block_value ) {
		return $block_value;
	}

	return $fallback;
}
```

- [ ] **Step 4: Uruchom check — musi przejść**

```bash
npm run test:php
```

Oczekiwane: `40 passed, 0 failed (2 check files)`, exit 0.

- [ ] **Step 5: Lint i commit**

```bash
composer run lint:php
git add includes/config.php tools/checks/20-config.php
git commit -m "$(cat <<'EOF'
feat: add pure isudev.json config resolution

Implements decode/extract_library/merge_configs/resolve_block_value. The
`library`-key-only contract keeps the shared isudev.json file safe for other
isudev-* plugins.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 4: Adaptery WP dla configu

**Files:**
- Modify: `includes/config.php` (dopisz sekcję adapterów na końcu)
- Modify: `isudev-library.php` (dodaj `require_once`)
- Create: `isudev.json.example`

**Interfaces:**
- Consumes: czyste funkcje z Task 3.
- Produces, w namespace `IsuDevLibrary\Config`:
  - `config_file_path( bool $parent_theme = false ): string` — ścieżka do czytelnego `isudev.json` albo `''`.
  - `get_config(): array` — scalony `library` subtree, cache statyczny na request, filtry `isudev_library/config/raw` i `isudev_library/config`.
  - `get_block_config( string $block_name, $key, $fallback = null, string $variation_namespace = '' )` — `$key` jako string z kropkami lub tablica.
  - `config_sources(): array` — `[ 'parent' => string, 'child' => string ]`, ścieżki znalezionych plików (`''` gdy brak). Dla diagnostyki w panelu.
  - `get_config_uncached(): array` — odczyt z dysku pomijający cache; jedyne miejsce czytające plik i stosujące filtry.

- [ ] **Step 1: Dopisz adaptery do `includes/config.php`**

Dodaj **na końcu** pliku:

```php
/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Locate a readable isudev.json in the child or parent theme.
 *
 * @param bool $parent_theme Whether to look in the parent (template) theme.
 * @return string Absolute path, or '' when not readable.
 */
function config_file_path( bool $parent_theme = false ): string {
	$root      = $parent_theme ? \get_template_directory() : \get_stylesheet_directory();
	$candidate = $root . '/' . CONFIG_FILE;

	return \is_readable( $candidate ) ? $candidate : '';
}

/**
 * Paths of the isudev.json files that were found, for diagnostics.
 *
 * @return array{parent:string,child:string} Absolute paths; '' when absent.
 */
function config_sources(): array {
	$is_child = \get_template_directory() !== \get_stylesheet_directory();

	return array(
		'parent' => $is_child ? config_file_path( true ) : '',
		'child'  => config_file_path(),
	);
}

/**
 * Read the merged config from disk, bypassing the per-request cache.
 *
 * Parent theme first, child theme on top. This is the only place that touches
 * the filesystem; get_config() is a caching wrapper around it.
 *
 * @return array The merged `library` subtree.
 */
function get_config_uncached(): array {
	$raw = array();

	/**
	 * Filters whether to inherit isudev.json from the parent theme.
	 *
	 * @param bool $should_inherit Default true.
	 */
	$inherit = (bool) \apply_filters( 'isudev_library/config/inherit_from_parent', true );

	// get_template_directory() vs get_stylesheet_directory() instead of
	// is_child_theme(), which is not reliable this early.
	if ( $inherit && \get_template_directory() !== \get_stylesheet_directory() ) {
		$parent_path = config_file_path( true );
		if ( '' !== $parent_path ) {
			$raw = decode( (string) \file_get_contents( $parent_path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local theme file, not a remote request.
		}
	}

	$child_path = config_file_path();
	if ( '' !== $child_path ) {
		$child_raw = decode( (string) \file_get_contents( $child_path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local theme file, not a remote request.
		$raw       = merge_configs( $raw, $child_raw );
	}

	/**
	 * Filters the whole decoded isudev.json, before this plugin's key is extracted.
	 *
	 * @param array $raw Full decoded file contents.
	 */
	$raw = (array) \apply_filters( 'isudev_library/config/raw', $raw );

	/**
	 * Filters this plugin's `library` subtree.
	 *
	 * @param array $config The `library` subtree.
	 */
	return (array) \apply_filters( 'isudev_library/config', extract_library( $raw ) );
}

/**
 * Read the merged `library` config from the active theme, cached per request.
 *
 * @return array The merged `library` subtree.
 */
function get_config(): array {
	static $config = null;

	if ( null === $config ) {
		$config = get_config_uncached();
	}

	return $config;
}

/**
 * Resolve a config value for a block.
 *
 * @param string       $block_name          Full block name, e.g. `isudev/site-header`.
 * @param string|array $key                 Dot-notation key or ordered key path.
 * @param mixed        $fallback            Value returned when nothing resolves.
 * @param string       $variation_namespace Variation namespace; '' to skip.
 * @return mixed Resolved value.
 */
function get_block_config( string $block_name, $key, $fallback = null, string $variation_namespace = '' ) {
	$key_path = \is_array( $key ) ? $key : \explode( '.', (string) $key );

	return resolve_block_value( get_config(), $block_name, $key_path, $fallback, $variation_namespace );
}
```

Po dopisaniu sekcja adapterów ma zawierać, w tej kolejności: `config_file_path()`, `config_sources()`, `get_config_uncached()`, `get_config()`, `get_block_config()`. Nic więcej — nie dodawaj funkcji, dla której nie ma wołającego.

`get_config_uncached()` jest **jedynym** miejscem czytającym z dysku i stosującym filtry; `get_config()` to tylko cache statyczny wokół niego. Nie duplikuj logiki odczytu w obu — kolejność w pliku ma znaczenie, bo `get_config()` woła `get_config_uncached()`.

- [ ] **Step 2: Dodaj `require_once` w `isudev-library.php`**

Zamień linię:

```php
require_once PATH . 'includes/utils/array.php';
```

na:

```php
require_once PATH . 'includes/utils/array.php';
require_once PATH . 'includes/config.php';
```

- [ ] **Step 3: Napisz `isudev.json.example`**

```json
{
  "library": {
    "isudev/site-header": {
      "enabled": true,
      "sticky": false,
      "ariaLabel": "Główna nawigacja",
      "variations": {
        "compact": {
          "name": "compact",
          "title": "Compact header",
          "icon": "menu",
          "description": "Header bez slotu akcji.",
          "attributes": {
            "sticky": false,
            "logoSource": "site"
          }
        }
      }
    }
  }
}
```

- [ ] **Step 5: Uruchom checki**

Adaptery są w tym samym pliku co funkcje czyste, ale ich ciała nie wykonują się przy `require`.

```bash
npm run test:php
```

Oczekiwane: `40 passed, 0 failed (2 check files)`, exit 0. Jeśli pojawi się fatal o nieznanej funkcji WP — masz wywołanie WP na poziomie pliku, przenieś je do funkcji.

- [ ] **Step 5: Lint i commit**

```bash
composer run lint:php
git add includes/config.php isudev-library.php isudev.json.example
git commit -m "$(cat <<'EOF'
feat: add WordPress adapters for the isudev.json reader

Locates isudev.json in child/parent theme, merges child over parent, caches per
request, and exposes get_block_config() plus diagnostics.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 5: `Registry` — czysta logika precedencji

Serce spec §7. Cała tabela precedencji jako jedna czysta funkcja.

**Files:**
- Create: `includes/class-registry.php`
- Test: `tools/checks/30-registry.php`

**Interfaces:**
- Consumes: nic (funkcje czyste są samodzielne).
- Produces, klasa `IsuDevLibrary\Registry`:
  - `const OPTION = 'isudev_library_blocks';`
  - `public static function normalize_descriptor( array $raw ): ?array` — czysta. Zwraca `null` gdy brak `slug` lub `name` (albo nie są niepustymi stringami). W przeciwnym razie tablicę z **wszystkimi** kluczami: `slug`, `name`, `requires` (array of string), `always_on` (bool), `variations` (bool), `bootstrap` (array of string).
  - `public static function build_dependents( array $descriptors ): array` — czysta. `slug => list<slug>`; każdy slug ma klucz, także gdy lista pusta.
  - `public static function resolve_states( array $descriptors, array $config, array $option ): array` — czysta. `slug => [ 'enabled' => bool, 'source' => string, 'locked' => bool ]`. `source` ∈ `dependency|always_on|code|panel|default`. `locked` = `true` dla `dependency`, `always_on`, `code`.

  `$descriptors` to `slug => normalized descriptor`. `$config` to `library` subtree. `$option` to `slug => bool`.

- [ ] **Step 1: Napisz failing check**

`tools/checks/30-registry.php`:

```php
<?php
/**
 * Checks for the pure parts of IsuDevLibrary\Registry.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/class-registry.php';

use IsuDevLibrary\Registry;

// normalize_descriptor().
Checks::is( 'normalize: missing slug rejected', Registry::normalize_descriptor( array( 'name' => 'isudev/x' ) ), null );
Checks::is( 'normalize: missing name rejected', Registry::normalize_descriptor( array( 'slug' => 'x' ) ), null );
Checks::is( 'normalize: empty slug rejected', Registry::normalize_descriptor( array( 'slug' => '', 'name' => 'isudev/x' ) ), null );
Checks::is( 'normalize: non-string name rejected', Registry::normalize_descriptor( array( 'slug' => 'x', 'name' => 42 ) ), null );

Checks::is(
	'normalize: fills every default',
	Registry::normalize_descriptor( array( 'slug' => 'site-header', 'name' => 'isudev/site-header' ) ),
	array(
		'slug'       => 'site-header',
		'name'       => 'isudev/site-header',
		'requires'   => array(),
		'always_on'  => false,
		'variations' => false,
		'bootstrap'  => array(),
	)
);

Checks::is(
	'normalize: coerces types and drops non-string entries',
	Registry::normalize_descriptor(
		array(
			'slug'       => 'bento-card',
			'name'       => 'isudev/bento-card',
			'requires'   => array( 'bento-grid', 7, '' ),
			'always_on'  => 1,
			'variations' => 'yes',
			'bootstrap'  => array( 'inc/a.php', null ),
		)
	),
	array(
		'slug'       => 'bento-card',
		'name'       => 'isudev/bento-card',
		'requires'   => array( 'bento-grid' ),
		'always_on'  => true,
		'variations' => true,
		'bootstrap'  => array( 'inc/a.php' ),
	)
);

// build_dependents().
$descriptors = array(
	'bento-grid'  => Registry::normalize_descriptor( array( 'slug' => 'bento-grid', 'name' => 'isudev/bento-grid' ) ),
	'bento-card'  => Registry::normalize_descriptor( array( 'slug' => 'bento-card', 'name' => 'isudev/bento-card', 'requires' => array( 'bento-grid' ) ) ),
	'site-header' => Registry::normalize_descriptor( array( 'slug' => 'site-header', 'name' => 'isudev/site-header' ) ),
);

Checks::is(
	'dependents: parent lists its child, every slug has a key',
	Registry::build_dependents( $descriptors ),
	array(
		'bento-grid'  => array( 'bento-card' ),
		'bento-card'  => array(),
		'site-header' => array(),
	)
);

// The map's keys must be exactly the known slugs. Inventing a key for an unknown
// requires target would put a block that does not exist into the map, and
// anything later iterating those keys would read a phantom entry.
Checks::is(
	'dependents: a requires entry naming an unknown slug creates no phantom key',
	Registry::build_dependents(
		array(
			'orphan' => Registry::normalize_descriptor( array( 'slug' => 'orphan', 'name' => 'isudev/orphan', 'requires' => array( 'ghost' ) ) ),
		)
	),
	array( 'orphan' => array() )
);

// resolve_states() — spec §7 precedence table.
$simple = array(
	'site-header' => Registry::normalize_descriptor( array( 'slug' => 'site-header', 'name' => 'isudev/site-header' ) ),
);

Checks::is(
	'resolve: no config, no option -> enabled by default',
	Registry::resolve_states( $simple, array(), array() ),
	array( 'site-header' => array( 'enabled' => true, 'source' => 'default', 'locked' => false ) )
);

Checks::is(
	'resolve: option disables, source is panel',
	Registry::resolve_states( $simple, array(), array( 'site-header' => false ) ),
	array( 'site-header' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ) )
);

Checks::is(
	'resolve: isudev.json beats the option and locks',
	Registry::resolve_states(
		$simple,
		array( 'isudev/site-header' => array( 'enabled' => true ) ),
		array( 'site-header' => false )
	),
	array( 'site-header' => array( 'enabled' => true, 'source' => 'code', 'locked' => true ) )
);

Checks::is(
	'resolve: isudev.json can disable and lock',
	Registry::resolve_states(
		$simple,
		array( 'isudev/site-header' => array( 'enabled' => false ) ),
		array( 'site-header' => true )
	),
	array( 'site-header' => array( 'enabled' => false, 'source' => 'code', 'locked' => true ) )
);

Checks::is(
	'resolve: isudev.json without an enabled key does not lock',
	Registry::resolve_states(
		$simple,
		array( 'isudev/site-header' => array( 'sticky' => false ) ),
		array( 'site-header' => false )
	),
	array( 'site-header' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ) )
);

$always = array(
	'site-header' => Registry::normalize_descriptor( array( 'slug' => 'site-header', 'name' => 'isudev/site-header', 'always_on' => true ) ),
);

Checks::is(
	'resolve: always_on beats both config and option',
	Registry::resolve_states(
		$always,
		array( 'isudev/site-header' => array( 'enabled' => false ) ),
		array( 'site-header' => false )
	),
	array( 'site-header' => array( 'enabled' => true, 'source' => 'always_on', 'locked' => true ) )
);

// Dependencies win over everything.
Checks::is(
	'resolve: child disabled when parent is off, source is dependency',
	Registry::resolve_states( $descriptors, array(), array( 'bento-grid' => false ) ),
	array(
		'bento-grid'  => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ),
		'bento-card'  => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
		'site-header' => array( 'enabled' => true, 'source' => 'default', 'locked' => false ),
	)
);

Checks::is(
	'resolve: child enabled when parent is on',
	Registry::resolve_states( $descriptors, array(), array() ),
	array(
		'bento-grid'  => array( 'enabled' => true, 'source' => 'default', 'locked' => false ),
		'bento-card'  => array( 'enabled' => true, 'source' => 'default', 'locked' => false ),
		'site-header' => array( 'enabled' => true, 'source' => 'default', 'locked' => false ),
	)
);

Checks::is(
	'resolve: dependency beats always_on on the child',
	Registry::resolve_states(
		array(
			'bento-grid' => Registry::normalize_descriptor( array( 'slug' => 'bento-grid', 'name' => 'isudev/bento-grid' ) ),
			'bento-card' => Registry::normalize_descriptor( array( 'slug' => 'bento-card', 'name' => 'isudev/bento-card', 'requires' => array( 'bento-grid' ), 'always_on' => true ) ),
		),
		array(),
		array( 'bento-grid' => false )
	),
	array(
		'bento-grid' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ),
		'bento-card' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
	)
);

// Row 1 also beats row 3. The cascade's guard skips only slugs already resolved
// to `dependency`, so a `code`-locked state must still be overwritten. Special
// casing `code` in that guard would ship silently without this check.
Checks::is(
	'resolve: dependency beats an isudev.json-forced enabled child',
	Registry::resolve_states(
		array(
			'bento-grid' => Registry::normalize_descriptor( array( 'slug' => 'bento-grid', 'name' => 'isudev/bento-grid' ) ),
			'bento-card' => Registry::normalize_descriptor( array( 'slug' => 'bento-card', 'name' => 'isudev/bento-card', 'requires' => array( 'bento-grid' ) ) ),
		),
		array( 'isudev/bento-card' => array( 'enabled' => true ) ),
		array( 'bento-grid' => false )
	),
	array(
		'bento-grid' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ),
		'bento-card' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
	)
);

Checks::is(
	'resolve: requires pointing at an unknown slug disables the block',
	Registry::resolve_states(
		array( 'orphan' => Registry::normalize_descriptor( array( 'slug' => 'orphan', 'name' => 'isudev/orphan', 'requires' => array( 'ghost' ) ) ) ),
		array(),
		array()
	),
	array( 'orphan' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ) )
);

// Transitive: c requires b, b requires a, a off -> both off.
Checks::is(
	'resolve: dependency cascade is transitive',
	Registry::resolve_states(
		array(
			'a' => Registry::normalize_descriptor( array( 'slug' => 'a', 'name' => 'isudev/a' ) ),
			'b' => Registry::normalize_descriptor( array( 'slug' => 'b', 'name' => 'isudev/b', 'requires' => array( 'a' ) ) ),
			'c' => Registry::normalize_descriptor( array( 'slug' => 'c', 'name' => 'isudev/c', 'requires' => array( 'b' ) ) ),
		),
		array(),
		array( 'a' => false )
	),
	array(
		'a' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ),
		'b' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
		'c' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
	)
);

// Same chain, declared in REVERSE dependency order. This is the check that
// actually pins the stabilizing while loop: with descriptors ordered c, b, a a
// single ordered pass sees c before b is demoted, so c stays enabled and the
// cascade silently stops one level short. The check above passes even without
// the loop, because a, b, c happen to be in dependency order already.
Checks::is(
	'resolve: transitive cascade holds when descriptors are declared in reverse order',
	Registry::resolve_states(
		array(
			'c' => Registry::normalize_descriptor( array( 'slug' => 'c', 'name' => 'isudev/c', 'requires' => array( 'b' ) ) ),
			'b' => Registry::normalize_descriptor( array( 'slug' => 'b', 'name' => 'isudev/b', 'requires' => array( 'a' ) ) ),
			'a' => Registry::normalize_descriptor( array( 'slug' => 'a', 'name' => 'isudev/a' ) ),
		),
		array(),
		array( 'a' => false )
	),
	array(
		'c' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
		'b' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
		'a' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ),
	)
);
```

- [ ] **Step 2: Uruchom check — musi się wywalić**

```bash
npm run test:php
```

Oczekiwane: fatal error, brak `includes/class-registry.php`.

- [ ] **Step 3: Napisz `includes/class-registry.php` — tylko funkcje czyste**

Adaptery WP dojdą w Task 6.

```php
<?php
/**
 * Block registry: descriptor discovery and enabled-state resolution.
 *
 * The static methods above the "WordPress adapters" marker are pure — no WP
 * calls — so they can be exercised by tools/check.php.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary;

defined( 'ABSPATH' ) || exit;

/**
 * Discovers block descriptors and resolves which blocks are enabled.
 */
class Registry {

	/**
	 * Option holding panel-managed toggles, as slug => bool.
	 *
	 * @var string
	 */
	const OPTION = 'isudev_library_blocks';

	/**
	 * Normalize a raw descriptor, filling every key. Pure.
	 *
	 * @param array $raw Descriptor as returned by a block's block.php.
	 * @return array|null Normalized descriptor, or null when invalid.
	 */
	public static function normalize_descriptor( array $raw ): ?array {
		$slug = $raw['slug'] ?? null;
		$name = $raw['name'] ?? null;

		if ( ! \is_string( $slug ) || '' === $slug || ! \is_string( $name ) || '' === $name ) {
			return null;
		}

		return array(
			'slug'       => $slug,
			'name'       => $name,
			'requires'   => self::string_list( $raw['requires'] ?? array() ),
			'always_on'  => (bool) ( $raw['always_on'] ?? false ),
			'variations' => (bool) ( $raw['variations'] ?? false ),
			'bootstrap'  => self::string_list( $raw['bootstrap'] ?? array() ),
		);
	}

	/**
	 * Coerce a value into a list of non-empty strings. Pure.
	 *
	 * @param mixed $value Candidate list.
	 * @return array List of non-empty strings.
	 */
	private static function string_list( $value ): array {
		if ( ! \is_array( $value ) ) {
			return array();
		}

		$out = array();
		foreach ( $value as $item ) {
			if ( \is_string( $item ) && '' !== $item ) {
				$out[] = $item;
			}
		}

		return $out;
	}

	/**
	 * Invert `requires` into a slug => dependents map. Pure.
	 *
	 * The returned keys are exactly the slugs present in $descriptors — every one
	 * of them, and no others. A `requires` entry naming an unknown slug is
	 * ignored here rather than inventing a key for a block that does not exist.
	 *
	 * @param array $descriptors Normalized descriptors, keyed by slug.
	 * @return array slug => list of slugs that require it.
	 */
	public static function build_dependents( array $descriptors ): array {
		$dependents = array();

		foreach ( \array_keys( $descriptors ) as $slug ) {
			$dependents[ $slug ] = array();
		}

		foreach ( $descriptors as $slug => $descriptor ) {
			foreach ( $descriptor['requires'] as $required ) {
				/*
				 * A requires entry naming an unknown slug creates no key, so the
				 * map's keys are exactly the known slugs and nothing downstream
				 * can read a phantom entry. resolve_states() already treats the
				 * unknown requirement as unmet, so the block ends as `dependency`.
				 */
				if ( ! isset( $dependents[ $required ] ) ) {
					continue;
				}

				$dependents[ $required ][] = $slug;
			}
		}

		return $dependents;
	}

	/**
	 * Resolve enabled state for every descriptor. Pure.
	 *
	 * Precedence (spec §7): unmet requires, then always_on, then isudev.json,
	 * then the panel option, then enabled by default.
	 *
	 * @param array $descriptors Normalized descriptors, keyed by slug.
	 * @param array $config      The `library` subtree from isudev.json.
	 * @param array $option      Panel toggles, as slug => bool.
	 * @return array slug => array{enabled:bool,source:string,locked:bool}.
	 */
	public static function resolve_states( array $descriptors, array $config, array $option ): array {
		$states = array();

		// Pass 1: own state, ignoring dependencies.
		foreach ( $descriptors as $slug => $descriptor ) {
			$states[ $slug ] = self::resolve_own_state( $descriptor, $config, $option );
		}

		// Pass 2: cascade unmet dependencies until the result is stable.
		$changed = true;
		while ( $changed ) {
			$changed = false;

			foreach ( $descriptors as $slug => $descriptor ) {
				if ( 'dependency' === $states[ $slug ]['source'] ) {
					continue;
				}

				foreach ( $descriptor['requires'] as $required ) {
					$satisfied = isset( $states[ $required ] ) && $states[ $required ]['enabled'];

					if ( ! $satisfied ) {
						$states[ $slug ] = array(
							'enabled' => false,
							'source'  => 'dependency',
							'locked'  => true,
						);
						$changed         = true;
						break;
					}
				}
			}
		}

		return $states;
	}

	/**
	 * Resolve a single descriptor's state, ignoring dependencies. Pure.
	 *
	 * @param array $descriptor Normalized descriptor.
	 * @param array $config     The `library` subtree from isudev.json.
	 * @param array $option     Panel toggles, as slug => bool.
	 * @return array{enabled:bool,source:string,locked:bool}
	 */
	private static function resolve_own_state( array $descriptor, array $config, array $option ): array {
		if ( $descriptor['always_on'] ) {
			return array(
				'enabled' => true,
				'source'  => 'always_on',
				'locked'  => true,
			);
		}

		$from_code = $config[ $descriptor['name'] ]['enabled'] ?? null;
		if ( \is_bool( $from_code ) ) {
			return array(
				'enabled' => $from_code,
				'source'  => 'code',
				'locked'  => true,
			);
		}

		$from_panel = $option[ $descriptor['slug'] ] ?? null;
		if ( \is_bool( $from_panel ) ) {
			return array(
				'enabled' => $from_panel,
				'source'  => 'panel',
				'locked'  => false,
			);
		}

		return array(
			'enabled' => true,
			'source'  => 'default',
			'locked'  => false,
		);
	}
}
```

- [ ] **Step 4: Uruchom check — musi przejść**

```bash
npm run test:php
```

Oczekiwane: `61 passed, 0 failed (3 check files)`, exit 0.

- [ ] **Step 5: Lint i commit**

```bash
composer run lint:php
git add includes/class-registry.php tools/checks/30-registry.php
git commit -m "$(cat <<'EOF'
feat: add pure block state resolution to Registry

Implements the spec §7 precedence table as a pure function, including
transitive dependency cascade and descriptor normalization.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 6: Adaptery `Registry` + `Loader`

**Files:**
- Modify: `includes/class-registry.php` (dopisz sekcję adapterów)
- Create: `includes/class-loader.php`
- Modify: `isudev-library.php` (`require_once` + `boot()`)
- Test: `tools/checks/35-loader.php`

**Interfaces:**
- Consumes: `Registry::normalize_descriptor()`, `Registry::build_dependents()`, `Registry::resolve_states()`, `IsuDevLibrary\Config\get_config()`.
- Produces:
  - `Registry::descriptors(): array` — `slug => normalized descriptor`, z `src/blocks/*/block.php`, cache statyczny.
  - `Registry::blocks(): array` — `slug => descriptor + [ 'enabled', 'source', 'locked', 'dependents' ]`.
  - `Registry::is_enabled( string $slug ): bool`
  - `Registry::flush(): void` — czyści cache statyczny.
  - `Loader::boot(): void` — wiesza `register()` na `init` z priorytetem 5.
  - `Loader::register(): void`
  - `Loader::block_build_path( string $slug ): string` — `PATH . 'build/blocks/' . $slug`.

- [ ] **Step 1: Dopisz adaptery do `includes/class-registry.php`**

Dodaj jako ostatnie metody klasy, przed zamykającym `}`:

```php
	/*
	 * WordPress adapters. Everything below may call WordPress functions.
	 */

	/**
	 * Cache for descriptors().
	 *
	 * @var array|null
	 */
	private static $descriptors_cache = null;

	/**
	 * Cache for blocks().
	 *
	 * @var array|null
	 */
	private static $blocks_cache = null;

	/**
	 * Discover and normalize every block descriptor.
	 *
	 * Descriptors are read from src/, not build/: PHP needs no compilation, so
	 * editing it takes effect without a rebuild.
	 *
	 * @return array slug => normalized descriptor.
	 */
	public static function descriptors(): array {
		if ( null !== self::$descriptors_cache ) {
			return self::$descriptors_cache;
		}

		$descriptors = array();
		$files       = \glob( PATH . 'src/blocks/*/block.php' );
		$files       = \is_array( $files ) ? $files : array();

		\sort( $files );

		foreach ( $files as $file ) {
			$raw = require $file;

			if ( ! \is_array( $raw ) ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Block descriptor %s must return an array.', $file ) ),
					'1.0.0'
				);
				continue;
			}

			$descriptor = self::normalize_descriptor( $raw );

			if ( null === $descriptor ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Block descriptor %s must define non-empty "slug" and "name".', $file ) ),
					'1.0.0'
				);
				continue;
			}

			$expected_slug = \basename( \dirname( $file ) );
			if ( $descriptor['slug'] !== $expected_slug ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Block descriptor %1$s declares slug "%2$s" but lives in directory "%3$s".', $file, $descriptor['slug'], $expected_slug ) ),
					'1.0.0'
				);
				continue;
			}

			$descriptors[ $descriptor['slug'] ] = $descriptor;
		}

		self::$descriptors_cache = $descriptors;

		return $descriptors;
	}

	/**
	 * Descriptors decorated with resolved state and dependents.
	 *
	 * @return array slug => descriptor + enabled/source/locked/dependents.
	 */
	public static function blocks(): array {
		if ( null !== self::$blocks_cache ) {
			return self::$blocks_cache;
		}

		$descriptors = self::descriptors();
		$option      = \get_option( self::OPTION, array() );
		$option      = \is_array( $option ) ? $option : array();

		$states     = self::resolve_states( $descriptors, Config\get_config(), $option );
		$dependents = self::build_dependents( $descriptors );

		$blocks = array();
		foreach ( $descriptors as $slug => $descriptor ) {
			$blocks[ $slug ] = \array_merge(
				$descriptor,
				$states[ $slug ],
				array( 'dependents' => $dependents[ $slug ] ?? array() )
			);
		}

		self::$blocks_cache = $blocks;

		return $blocks;
	}

	/**
	 * Whether a block is enabled.
	 *
	 * @param string $slug Block slug.
	 * @return bool
	 */
	public static function is_enabled( string $slug ): bool {
		$blocks = self::blocks();

		return isset( $blocks[ $slug ] ) && $blocks[ $slug ]['enabled'];
	}

	/**
	 * Clear the per-request caches.
	 *
	 * @return void
	 */
	public static function flush(): void {
		self::$descriptors_cache = null;
		self::$blocks_cache      = null;
	}
```

Dodaj też `use IsuDevLibrary\Config;` pod deklaracją namespace — potrzebne dla `Config\get_config()`.

- [ ] **Step 2: Napisz `includes/class-loader.php`**

```php
<?php
/**
 * Block loader. The only place in this plugin that calls register_block_type().
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary;

defined( 'ABSPATH' ) || exit;

/**
 * Registers enabled blocks and boots their PHP.
 */
class Loader {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function boot(): void {
		// Priority 5: before the default 10, so blocks exist for anything that
		// inspects the registry on init.
		\add_action( 'init', array( __CLASS__, 'register' ), 5 );
	}

	/**
	 * Absolute path to a block's compiled metadata directory.
	 *
	 * This maps `src/blocks/<slug>/` to `build/blocks/<slug>/` because wp-scripts
	 * derives the entry name from the path relative to the source directory.
	 *
	 * @param string $slug Block slug.
	 * @return string
	 */
	public static function block_build_path( string $slug ): string {
		return PATH . 'build/blocks/' . $slug;
	}

	/**
	 * Register every enabled block.
	 *
	 * @return void
	 */
	public static function register(): void {
		$manifest = PATH . 'build/blocks-manifest.php';

		// Metadata collection is a read optimisation only: it caches block.json
		// contents, it does not register anything. Safe to skip when absent.
		if ( \function_exists( 'wp_register_block_metadata_collection' ) && \is_readable( $manifest ) ) {
			\wp_register_block_metadata_collection( PATH . 'build', $manifest );
		}

		foreach ( Registry::blocks() as $slug => $block ) {
			if ( ! $block['enabled'] ) {
				continue;
			}

			$build_path = self::block_build_path( $slug );

			/*
			 * Bail before running any of the block's side effects. Requiring its
			 * bootstrap files or attaching its variations for a block that then
			 * cannot be registered would leave half-initialised state behind:
			 * a bootstrap file that adds a REST route or a filter assuming its
			 * own block type exists would still have run.
			 */
			if ( ! \is_readable( $build_path . '/block.json' ) ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Block "%s" has no compiled metadata. Run npm run build.', $slug ) ),
					'1.0.0'
				);
				continue;
			}

			$block_dir = PATH . 'src/blocks/' . $slug . '/';

			foreach ( $block['bootstrap'] as $relative ) {
				$file = self::contained_path( $block_dir, $relative );

				if ( '' === $file ) {
					\_doing_it_wrong(
						__METHOD__,
						\esc_html( \sprintf( 'Block "%1$s" declares a bootstrap file that is missing or outside its own directory: %2$s', $slug, $relative ) ),
						'1.0.0'
					);
					continue;
				}

				require_once $file;
			}

			if ( $block['variations'] ) {
				Variations\attach( $block['name'] );
			}

			\register_block_type( $build_path );
		}
	}

	/**
	 * Resolve a path relative to a directory, refusing anything that escapes it.
	 *
	 * Descriptor `bootstrap` entries are first-party, so this is not a security
	 * boundary — anyone who can edit `block.php` can already run code. It exists
	 * to catch a mistyped relative path, which would otherwise silently load a
	 * different block's file.
	 *
	 * Public because it is exercised directly by tools/checks/35-loader.php.
	 *
	 * @param string $root     Absolute directory the path must stay inside, with trailing slash.
	 * @param string $relative Path declared in the descriptor, relative to $root.
	 * @return string Absolute readable path, or '' when missing or out of bounds.
	 */
	public static function contained_path( string $root, string $relative ): string {
		$resolved = \realpath( $root . \ltrim( $relative, '/' ) );
		$base     = \realpath( $root );

		if ( false === $resolved || false === $base ) {
			return '';
		}

		// The separator matters: it stops `blocks/site-header-evil` from passing
		// a prefix test against `blocks/site-header`.
		if ( 0 !== \strpos( $resolved, $base . \DIRECTORY_SEPARATOR ) ) {
			return '';
		}

		return \is_readable( $resolved ) ? $resolved : '';
	}
}
```

- [ ] **Step 3: Napisz `tools/checks/35-loader.php`**

`contained_path()` jest jedyną logiką w `Loader`, którą da się przetestować bez
WordPressa — `realpath()`, `strpos()` i `is_readable()` to czysty PHP. Fixtures
to istniejące katalogi repo, więc check nie tworzy plików tymczasowych.

Numer `35` mieści się między `30-registry.php` i `40-variations.php`, więc nie
koliduje z plikiem z Task 7.

```php
<?php
/**
 * Checks for IsuDevLibrary\Loader::contained_path().
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/class-loader.php';

use IsuDevLibrary\Loader;

$plugin_root = dirname( __DIR__, 2 ) . '/';
$tools_dir   = $plugin_root . 'tools/';

Checks::is(
	'contained_path: a file inside the directory resolves to its real path',
	Loader::contained_path( $tools_dir, 'check.php' ),
	realpath( $tools_dir . 'check.php' )
);
Checks::is(
	'contained_path: a nested file inside the directory resolves',
	Loader::contained_path( $tools_dir, 'checks/10-array.php' ),
	realpath( $tools_dir . 'checks/10-array.php' )
);
Checks::is(
	'contained_path: a missing file returns empty string',
	Loader::contained_path( $tools_dir, 'does-not-exist.php' ),
	''
);

// The point of the function: a mistyped relative path must not silently load a
// file from somewhere else in the plugin.
Checks::is(
	'contained_path: parent traversal is refused even though the file exists',
	Loader::contained_path( $tools_dir, '../composer.json' ),
	''
);
Checks::is(
	'contained_path: traversal buried mid-path is refused',
	Loader::contained_path( $tools_dir, 'checks/../../composer.json' ),
	''
);
Checks::is(
	'contained_path: an absolute-looking path is still resolved under the root',
	Loader::contained_path( $tools_dir, '/check.php' ),
	realpath( $tools_dir . 'check.php' )
);
Checks::is(
	'contained_path: a nonexistent root returns empty string',
	Loader::contained_path( $plugin_root . 'no-such-dir/', 'check.php' ),
	''
);

/*
 * The separator in the prefix comparison is what stops a sibling directory whose
 * name merely starts with the root's name from passing. `site-header` and
 * `site-header-compact` are a plausible pair of block names in this library, so
 * this is worth pinning. Needs a fixture: no such pair exists in the repo, and
 * without it dropping DIRECTORY_SEPARATOR leaves every other check green.
 */
$fixture = \sys_get_temp_dir() . '/isudev-contained-path-check';
$inside  = $fixture . '/site-header';
$sibling = $fixture . '/site-header-evil';

@\mkdir( $inside, 0777, true );   // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Fixture setup; failure surfaces as a failed check below.
@\mkdir( $sibling, 0777, true );  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Fixture setup; failure surfaces as a failed check below.
\file_put_contents( $sibling . '/x.php', "<?php\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Local temp fixture, not a WP filesystem operation.

Checks::is(
	'contained_path: a sibling directory sharing the root name prefix is refused',
	Loader::contained_path( $inside . '/', '../site-header-evil/x.php' ),
	''
);

\unlink( $sibling . '/x.php' );
\rmdir( $sibling );
\rmdir( $inside );
\rmdir( $fixture );
```

- [ ] **Step 4: Zaktualizuj `isudev-library.php`**

Zamień blok `require_once` **dokładnie na to** — cztery pliki, nie pięć:

```php
require_once PATH . 'includes/utils/array.php';
require_once PATH . 'includes/config.php';
require_once PATH . 'includes/class-registry.php';
require_once PATH . 'includes/class-loader.php';

Loader::boot();
```

`includes/variations.php` **nie wchodzi tutaj** — powstaje w Task 7, który dopisze swój `require_once` w tej samej kolejności (przed `class-registry.php`). To bezpieczne: `Loader::register()` woła `Variations\attach()` tylko dla bloku z `'variations' => true`, a pierwszy blok pojawia się dopiero w Task 9 — długo po tym, jak Task 7 doda ten plik.

- [ ] **Step 5: Uruchom checki**

```bash
npm run test:php
```

Oczekiwane: `69 passed, 0 failed (4 check files)`. Adaptery nie wykonują się przy `require`.

- [ ] **Step 6: Zweryfikuj, że plugin się aktywuje bez błędów**

Bloków jeszcze nie ma, więc `Registry::descriptors()` zwróci `[]` i `register()` nic nie zrobi. Aktywuj plugin ręcznie w `http://isudev-library.local/wp-admin/plugins.php`, potem sprawdź, że strona główna wciąż zwraca 200 i nie zawiera notice'ów PHP:

```bash
curl -s -o /dev/null -w "home: %{http_code}\n" http://isudev-library.local/
curl -s http://isudev-library.local/ | grep -icE "(warning|fatal error|notice):" || echo "no PHP notices"
```

Oczekiwane: `home: 200` i `no PHP notices`.

- [ ] **Step 7: Lint i commit**

```bash
composer run lint:php
git add includes/class-registry.php includes/class-loader.php isudev-library.php tools/checks/35-loader.php
git commit -m "$(cat <<'EOF'
feat: add descriptor discovery and the single block registration point

Registry reads descriptors from src/blocks/*/block.php and decorates them with
resolved state; Loader is the only caller of register_block_type().

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 7: Wariacje rejestrowane w PHP

**Files:**
- Create: `includes/variations.php`
- Modify: `isudev-library.php` (dodaj `require_once`)
- Test: `tools/checks/40-variations.php`

**Interfaces:**
- Consumes: `IsuDevLibrary\Config\get_config()`.
- Produces, namespace `IsuDevLibrary\Variations`:
  - `build_variations( array $existing, array $block_config ): array` — czysta. Dokleja wariacje z `$block_config['variations']`, każdej wstrzykuje `attributes._namespace` i domyślne `isActive: ['_namespace']`, usuwa klucz `settings`. Pomija wpisy bez `name` i `title`.
  - `attach( string $block_name ): void` — wiesza filtr `get_block_type_variations` dla tego bloku.

- [ ] **Step 1: Napisz failing check**

`tools/checks/40-variations.php`:

```php
<?php
/**
 * Checks for IsuDevLibrary\Variations\build_variations().
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/variations.php';

use function IsuDevLibrary\Variations\build_variations;

$existing = array(
	array(
		'name'  => 'minimal',
		'title' => 'Minimal header',
	),
);

Checks::is( 'variations: no config leaves existing untouched', build_variations( $existing, array() ), $existing );
Checks::is( 'variations: non-array variations key is ignored', build_variations( $existing, array( 'variations' => 'nope' ) ), $existing );

Checks::is(
	'variations: injects _namespace and default isActive',
	build_variations(
		array(),
		array(
			'variations' => array(
				'compact' => array(
					'name'       => 'compact',
					'title'      => 'Compact header',
					'icon'       => 'menu',
					'attributes' => array( 'sticky' => false ),
				),
			),
		)
	),
	array(
		array(
			'name'       => 'compact',
			'title'      => 'Compact header',
			'icon'       => 'menu',
			'attributes' => array(
				'sticky'     => false,
				'_namespace' => 'compact',
			),
			'isActive'   => array( '_namespace' ),
		),
	)
);

Checks::is(
	'variations: creates attributes when absent',
	build_variations(
		array(),
		array(
			'variations' => array(
				'bare' => array(
					'name'  => 'bare',
					'title' => 'Bare',
				),
			),
		)
	),
	array(
		array(
			'name'       => 'bare',
			'title'      => 'Bare',
			'attributes' => array( '_namespace' => 'bare' ),
			'isActive'   => array( '_namespace' ),
		),
	)
);

Checks::is(
	'variations: respects an explicit isActive',
	build_variations(
		array(),
		array(
			'variations' => array(
				'custom' => array(
					'name'     => 'custom',
					'title'    => 'Custom',
					'isActive' => array( 'logoSource' ),
				),
			),
		)
	),
	array(
		array(
			'name'       => 'custom',
			'title'      => 'Custom',
			'isActive'   => array( 'logoSource' ),
			'attributes' => array( '_namespace' => 'custom' ),
		),
	)
);

Checks::is(
	'variations: strips the internal settings key',
	build_variations(
		array(),
		array(
			'variations' => array(
				'x' => array(
					'name'     => 'x',
					'title'    => 'X',
					'settings' => array( 'internal' => true ),
				),
			),
		)
	),
	array(
		array(
			'name'       => 'x',
			'title'      => 'X',
			'attributes' => array( '_namespace' => 'x' ),
			'isActive'   => array( '_namespace' ),
		),
	)
);

Checks::is(
	'variations: skips entries missing name or title',
	build_variations(
		array(),
		array(
			'variations' => array(
				'no-title' => array( 'name' => 'no-title' ),
				'no-name'  => array( 'title' => 'No name' ),
			),
		)
	),
	array()
);

Checks::is(
	'variations: appends to existing variations',
	\count(
		build_variations(
			$existing,
			array(
				'variations' => array(
					'compact' => array(
						'name'  => 'compact',
						'title' => 'Compact',
					),
				),
			)
		)
	),
	2
);
```

- [ ] **Step 2: Uruchom check — musi się wywalić**

```bash
npm run test:php
```

Oczekiwane: fatal error, brak `includes/variations.php`.

- [ ] **Step 3: Napisz `includes/variations.php`**

```php
<?php
/**
 * Block variations registered in PHP.
 *
 * Registering in PHP rather than JS keeps variations visible to PHP hooks, so
 * they can be filtered per post type and render.php can read `_namespace`.
 *
 * The build_variations() function is pure — no WP calls.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Variations;

use IsuDevLibrary\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Append configured variations to a block's variation list. Pure.
 *
 * @param array $existing     Variations already registered for the block.
 * @param array $block_config The block's subtree from the `library` config.
 * @return array Combined variation list.
 */
function build_variations( array $existing, array $block_config ): array {
	$configured = $block_config['variations'] ?? null;

	if ( ! \is_array( $configured ) ) {
		return $existing;
	}

	foreach ( $configured as $namespace => $variation ) {
		if ( ! \is_array( $variation ) ) {
			continue;
		}

		if ( ! isset( $variation['name'], $variation['title'] ) ) {
			continue;
		}

		// `settings` is internal to the config file, never exposed to the editor.
		unset( $variation['settings'] );

		if ( ! isset( $variation['attributes'] ) || ! \is_array( $variation['attributes'] ) ) {
			$variation['attributes'] = array();
		}

		$variation['attributes']['_namespace'] = (string) $namespace;

		if ( empty( $variation['isActive'] ) || ! \is_array( $variation['isActive'] ) ) {
			$variation['isActive'] = array( '_namespace' );
		}

		$existing[] = $variation;
	}

	return $existing;
}

/**
 * Wire the variations filter for a single block.
 *
 * @param string $block_name Full block name, e.g. `isudev/site-header`.
 * @return void
 */
function attach( string $block_name ): void {
	\add_filter(
		'get_block_type_variations',
		static function ( $variations, $block_type ) use ( $block_name ) {
			if ( ! \is_array( $variations ) || ! isset( $block_type->name ) || $block_name !== $block_type->name ) {
				return $variations;
			}

			$config = Config\get_config();

			return build_variations( $variations, $config[ $block_name ] ?? array() );
		},
		10,
		2
	);
}
```

- [ ] **Step 4: Dodaj `require_once` w `isudev-library.php`**

Wstaw `includes/variations.php` **przed** `class-registry.php`:

```php
require_once PATH . 'includes/utils/array.php';
require_once PATH . 'includes/config.php';
require_once PATH . 'includes/variations.php';
require_once PATH . 'includes/class-registry.php';
require_once PATH . 'includes/class-loader.php';

Loader::boot();
```

- [ ] **Step 5: Uruchom check — musi przejść**

```bash
npm run test:php
```

Oczekiwane: `77 passed, 0 failed (5 check files)`, exit 0.

- [ ] **Step 6: Lint i commit**

```bash
composer run lint:php
git add includes/variations.php isudev-library.php tools/checks/40-variations.php
git commit -m "$(cat <<'EOF'
feat: register block variations from isudev.json in PHP

Variations get an injected _namespace attribute and a default isActive, so
render.php can key styling off the active variation.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 8: Wspólny rejestr ikon

**Files:**
- Create: `includes/utils/icon.php`
- Modify: `isudev-library.php` (dodaj `require_once`)
- Test: `tools/checks/50-icon.php`
- Reference: `/Users/lukaszbiedron/Other Projects/isudev-header/includes/icon.php`

**Interfaces:**
- Consumes: nic.
- Produces, namespace `IsuDevLibrary\Utils`:
  - `default_icon_paths(): array` — czysta. `slug => SVG path markup` dla `chevronDown`, `burger`, `close`.
  - `build_svg( string $path_d, int $size, string $class_attr ): string` — czysta. Zwraca `''` gdy `$path_d` puste.
  - `icon( string $slug, int $size = 24, string $class_name = '' ): string` — adapter: filtr `isudev_library/icons` na rejestr, filtr `isudev_library/icon` na wynik, `esc_attr()` na klasie.

- [ ] **Step 1: Napisz failing check**

`tools/checks/50-icon.php`:

```php
<?php
/**
 * Checks for the pure parts of the icon registry.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/utils/icon.php';

use function IsuDevLibrary\Utils\build_svg;
use function IsuDevLibrary\Utils\default_icon_paths;

$paths = default_icon_paths();

Checks::is( 'icons: chevronDown is registered', isset( $paths['chevronDown'] ), true );
Checks::is( 'icons: burger is registered', isset( $paths['burger'] ), true );
Checks::is( 'icons: close is registered', isset( $paths['close'] ), true );
Checks::is( 'icons: exactly three defaults', \count( $paths ), 3 );

Checks::is( 'build_svg: empty path yields empty string', build_svg( '', 24, 'x' ), '' );

$svg = build_svg( '<path d="M0 0"/>', 32, 'isudev-header__close-icon' );

Checks::is( 'build_svg: uses currentColor', \strpos( $svg, 'fill="currentColor"' ) !== false, true );
Checks::is( 'build_svg: is aria-hidden', \strpos( $svg, 'aria-hidden="true"' ) !== false, true );
Checks::is( 'build_svg: is not focusable', \strpos( $svg, 'focusable="false"' ) !== false, true );
Checks::is( 'build_svg: applies size to width and height', \strpos( $svg, 'width="32" height="32"' ) !== false, true );
Checks::is( 'build_svg: applies the class attribute', \strpos( $svg, 'class="isudev-header__close-icon"' ) !== false, true );
Checks::is( 'build_svg: embeds the path', \strpos( $svg, '<path d="M0 0"/>' ) !== false, true );
```

- [ ] **Step 2: Uruchom check — musi się wywalić**

```bash
npm run test:php
```

Oczekiwane: fatal error, brak `includes/utils/icon.php`.

- [ ] **Step 3: Napisz `includes/utils/icon.php`**

Skopiuj trzy ciągi `d` **dosłownie** z `/Users/lukaszbiedron/Other Projects/isudev-header/includes/icon.php` (linie 33–35). Nie przepisuj ich z pamięci — to długie dane ścieżek. Poniżej struktura z placeholderami ścieżek oznaczonymi jako `PASTE_*`; podmień je na realne wartości z pliku źródłowego.

```php
<?php
/**
 * Inline SVG icon registry. No external icon dependency.
 *
 * The default_icon_paths() and build_svg() functions are pure — no WP calls.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Built-in icon path markup, keyed by slug. Pure.
 *
 * Paths are authored on a 0 0 600 600 viewBox.
 *
 * @return array slug => SVG child markup.
 */
function default_icon_paths(): array {
	return array(
		'chevronDown' => 'PASTE_CHEVRON_DOWN_PATH_FROM_isudev-header/includes/icon.php_LINE_33',
		'burger'      => 'PASTE_BURGER_PATH_FROM_isudev-header/includes/icon.php_LINE_34',
		'close'       => 'PASTE_CLOSE_PATH_FROM_isudev-header/includes/icon.php_LINE_35',
	);
}

/**
 * Wrap icon path markup in an SVG element. Pure.
 *
 * @param string $path_d     SVG child markup (already trusted, static).
 * @param int    $size       Pixel size for width and height.
 * @param string $class_attr Escaped value for the class attribute.
 * @return string SVG markup, or '' when there is nothing to draw.
 */
function build_svg( string $path_d, int $size, string $class_attr ): string {
	if ( '' === $path_d ) {
		return '';
	}

	return \sprintf(
		'<svg class="%1$s" width="%2$d" height="%2$d" viewBox="0 0 600 600" fill="currentColor" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">%3$s</svg>',
		$class_attr,
		$size,
		$path_d
	);
}

/*
 * WordPress adapters.
 */

/**
 * Return an inline SVG icon for the given slug.
 *
 * @param string $slug       Icon slug.
 * @param int    $size       Pixel size.
 * @param string $class_name Extra CSS class.
 * @return string Safe SVG markup ('' for unknown slugs).
 */
function icon( string $slug, int $size = 24, string $class_name = '' ): string {
	/**
	 * Filters the icon registry, so integrators can add or replace icons.
	 *
	 * @param array $paths slug => SVG child markup.
	 */
	$paths = (array) \apply_filters( 'isudev_library/icons', default_icon_paths() );

	$path_d = isset( $paths[ $slug ] ) && \is_string( $paths[ $slug ] ) ? $paths[ $slug ] : '';
	$svg    = build_svg( $path_d, $size, \esc_attr( $class_name ) );

	/**
	 * Filters the rendered icon markup.
	 *
	 * @param string $svg        The SVG markup ('' if unknown).
	 * @param string $slug       Icon slug.
	 * @param int    $size       Pixel size.
	 * @param string $class_name Extra CSS class.
	 */
	return (string) \apply_filters( 'isudev_library/icon', $svg, $slug, $size, $class_name );
}
```

- [ ] **Step 4: Dodaj `require_once` w `isudev-library.php`**

Po `includes/utils/array.php`:

```php
require_once PATH . 'includes/utils/array.php';
require_once PATH . 'includes/utils/icon.php';
require_once PATH . 'includes/config.php';
```

- [ ] **Step 5: Uruchom check — musi przejść**

```bash
npm run test:php
```

Oczekiwane: `88 passed, 0 failed (6 check files)`, exit 0. Jeśli `exactly three defaults` przechodzi, ale któryś `is registered` nie — nie podmieniłeś placeholderów.

- [ ] **Step 6: Potwierdź, że nie zostały placeholdery**

```bash
grep -c "PASTE_" includes/utils/icon.php || echo "clean"
```

Oczekiwane: `clean`.

- [ ] **Step 7: Lint i commit**

```bash
composer run lint:php
git add includes/utils/icon.php isudev-library.php tools/checks/50-icon.php
git commit -m "$(cat <<'EOF'
feat: add shared inline SVG icon registry

Promotes the site-header icon helper into a filterable, library-wide registry
that will back the future icon picker.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 9: Migracja `site-header` — PHP

Przenosi PHP bloku z `isudev-header` i stosuje mapę zmian nazw.

**Files:**
- Create: `src/blocks/site-header/block.php`
- Create: `src/blocks/site-header/render.php`
- Create: `src/blocks/site-header/inc/render-helpers.php`
- Create: `src/blocks/site-header/inc/class-nav-walker.php`
- Source: `/Users/lukaszbiedron/Other Projects/isudev-header/{src/render.php,includes/render-helpers.php,includes/class-nav-walker.php}`

**Interfaces:**
- Consumes: `IsuDevLibrary\Utils\icon()`, `Registry` (przez `Loader`).
- Produces:
  - deskryptor bloku `site-header` (slug `site-header`, name `isudev/site-header`, `variations => true`, `bootstrap => [ 'inc/render-helpers.php', 'inc/class-nav-walker.php' ]`)
  - `IsuDevLibrary\Blocks\SiteHeader\build_logo( array $attributes ): string`
  - `IsuDevLibrary\Blocks\SiteHeader\resolve_menu_ref( string $menu_ref ): array`
  - `IsuDevLibrary\Blocks\SiteHeader\build_menu( array $attributes ): string`
  - `IsuDevLibrary\Blocks\SiteHeader\Nav_Walker` (klasa)
  - hooki: `isudev_library/site_header/regions`, `isudev_library/site_header/output`, `isudev_library/site_header/menu_args`

- [ ] **Step 1: Skopiuj pliki źródłowe**

```bash
SRC="/Users/lukaszbiedron/Other Projects/isudev-header"
mkdir -p src/blocks/site-header/inc
cp "$SRC/src/render.php"                 src/blocks/site-header/render.php
cp "$SRC/includes/render-helpers.php"    src/blocks/site-header/inc/render-helpers.php
cp "$SRC/includes/class-nav-walker.php"  src/blocks/site-header/inc/class-nav-walker.php
```

- [ ] **Step 2: Zastosuj mapę zmian nazw — kolejność ma znaczenie**

Reguły muszą iść od najbardziej szczegółowej. `--idl-` przed `idl-`, `idl-site-header` przed `idl-`.

```bash
FILES="src/blocks/site-header/render.php src/blocks/site-header/inc/render-helpers.php src/blocks/site-header/inc/class-nav-walker.php"

# 1. Block name.
sed -i '' 's|idl/site-header|isudev/site-header|g' $FILES
# 2. Hook prefix (underscores).
sed -i '' 's|idl_site_header|isudev_library|g' $FILES
# 3. Text domain (hyphens) — must run before the generic idl- rule.
sed -i '' 's|idl-site-header|isudev-library|g' $FILES
# 4. CSS custom properties — must run before the generic idl- rule.
sed -i '' 's|--idl-|--isudev-|g' $FILES
# 5. Remaining CSS classes and DOM ids.
sed -i '' 's|idl-|isudev-|g' $FILES
# 6. PHP namespace.
sed -i '' 's|IsuDevLibrary\\SiteHeader|IsuDevLibrary\\Blocks\\SiteHeader|g' $FILES
```

- [ ] **Step 3: Sprawdź, że nic z `idl` nie zostało**

```bash
grep -rn "idl" src/blocks/site-header/ || echo "clean"
```

Oczekiwane: `clean`.

- [ ] **Step 4: Zmień hooki blokowe na przestrzeń nazw bloku**

Krok 2 zamienił `idl_site_header/regions` na `isudev_library/regions`, co jest zbyt ogólne — te trzy hooki należą do jednego bloku, nie do całej biblioteki. Hook `isudev_library/icon` **zostaje globalny** (należy do wspólnego rejestru z Task 8) i nie występuje już w tych plikach, bo `icon()` przeniósł się do utils.

```bash
FILES="src/blocks/site-header/render.php src/blocks/site-header/inc/render-helpers.php"
sed -i '' 's|isudev_library/regions|isudev_library/site_header/regions|g'   $FILES
sed -i '' 's|isudev_library/output|isudev_library/site_header/output|g'     $FILES
sed -i '' 's|isudev_library/menu_args|isudev_library/site_header/menu_args|g' $FILES
```

Zweryfikuj:

```bash
grep -rn "isudev_library/" src/blocks/site-header/
```

Oczekiwane: dokładnie trzy nazwy hooków, każda z segmentem `site_header/`.

- [ ] **Step 5: Podłącz `icon()` z wspólnych utils — w DWÓCH plikach**

`icon()` żyje teraz w `IsuDevLibrary\Utils` (Task 8), a oba pliki są w namespace
`IsuDevLibrary\Blocks\SiteHeader`. Bez importu wywołanie rozwiąże się na
nieistniejące `IsuDevLibrary\Blocks\SiteHeader\icon()` i wywali fatal.

Dodaj pod deklaracją `namespace` w **każdym** z tych plików:

```php
use function IsuDevLibrary\Utils\icon;
```

- `src/blocks/site-header/render.php` — woła `icon( 'close', 24, … )`
- `src/blocks/site-header/inc/class-nav-walker.php` — woła
  `icon( 'chevronDown', 20, … )` przy renderze rozwijanego podmenu

**Nie pomiń walkera.** Chevron pojawia się tylko w menu z dziećmi, więc brak
importu daje fatal wyłącznie na stronach z podmenu — czyli dokładnie w tych
przypadkach, które sprawdza suite a11y w Task 11. Potwierdź oba:

```bash
grep -n "use function IsuDevLibrary" src/blocks/site-header/render.php src/blocks/site-header/inc/class-nav-walker.php
```

Oczekiwane: dwie linie, jedna z każdego pliku.

- [ ] **Step 6: Napisz deskryptor `src/blocks/site-header/block.php`**

```php
<?php
/**
 * Descriptor for the isudev/site-header block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'site-header',
	'name'       => 'isudev/site-header',
	'requires'   => array(),
	'always_on'  => false,
	'variations' => true,
	'bootstrap'  => array(
		'inc/render-helpers.php',
		'inc/class-nav-walker.php',
	),
);
```

- [ ] **Step 7: Sprawdź składnię i lint**

```bash
for f in src/blocks/site-header/block.php src/blocks/site-header/render.php src/blocks/site-header/inc/*.php; do php -l "$f"; done
composer run lint:php
```

Oczekiwane: `No syntax errors detected` dla każdego pliku; phpcs bez błędów. Najbardziej prawdopodobny błąd phpcs to text domain — sprawdź, czy wszystkie wywołania i18n używają `isudev-library`.

- [ ] **Step 8: Uruchom checki**

```bash
npm run test:php
```

Oczekiwane: `88 passed, 0 failed (6 check files)`. Deskryptor nie jest jeszcze pokryty checkiem — pokrywa go Task 10 przez build i frontend.

- [ ] **Step 9: Commit**

```bash
git add src/blocks/site-header/
git commit -m "$(cat <<'EOF'
feat: migrate site-header PHP into the library

Renames idl/* to isudev/*, moves hooks under isudev_library/site_header/, and
replaces self-registration with a Loader descriptor.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 10: Migracja `site-header` — JS, SCSS, build

**Files:**
- Create: `src/blocks/site-header/{block.json,index.js,edit.js,save.js,view.js,variations.js,style.scss,editor.scss}`
- Source: `/Users/lukaszbiedron/Other Projects/isudev-header/src/*`

**Interfaces:**
- Consumes: deskryptor z Task 9, `Loader::block_build_path()`.
- Produces: skompilowany blok w `build/blocks/site-header/` oraz `build/blocks-manifest.php` z kluczem `site-header`.

- [ ] **Step 0: Napraw warunkowy entry w `webpack.config.js`**

Task 1 zapisał ten plik z **bezwarunkowym** entry `admin` wskazującym na
`src/admin/index.js`, a ten plik powstaje dopiero w Task 13. Task 10 jest
pierwszym buildem w całym planie, więc dopiero tutaj to wybucha: webpack
przerywa cały build i **nie emituje niczego**, także bloków.

Zamień blok `entry` w `webpack.config.js` na wersję warunkową:

```js
const fs = require('fs');
const path = require('path');

const adminEntry = path.resolve(__dirname, 'src/admin/index.js');

module.exports = {
	...defaultConfig,
	entry: {
		...(typeof defaultConfig.entry === 'function'
			? defaultConfig.entry()
			: defaultConfig.entry),
		// The admin panel lands in a later task than the first build, so this entry
		// is added only once its source exists. Without the guard, webpack fails
		// the whole build on an unresolved entry and emits nothing at all.
		//
		// The key must be 'admin/index', not 'admin'. wp-scripts writes
		// output.filename as '[name].js', so a bare key emits a flat build/admin.js
		// while includes/admin.php enqueues build/admin/index.js. The block entries
		// only nest because their names already contain slashes.
		...(fs.existsSync(adminEntry) ? { 'admin/index': adminEntry } : {}),
	},
};
```

**Nie twórz zaślepki `src/admin/index.js`.** Task 13 tworzy ten plik naprawdę
i wtedy entry włącza się samo; jego Step 7 (`ls build/admin/`) to weryfikuje.

Potwierdź, że build w ogóle startuje, zanim przejdziesz dalej:

```bash
node -e "console.log(Object.keys(require('./webpack.config.js').entry))"
```

Oczekiwane: tablica **bez** `admin/index` (bo `src/admin/` jeszcze nie istnieje).

- [ ] **Step 1: Skopiuj pliki źródłowe**

```bash
SRC="/Users/lukaszbiedron/Other Projects/isudev-header"
cp "$SRC/src/block.json"    src/blocks/site-header/block.json
cp "$SRC/src/index.js"      src/blocks/site-header/index.js
cp "$SRC/src/edit.js"       src/blocks/site-header/edit.js
cp "$SRC/src/save.js"       src/blocks/site-header/save.js
cp "$SRC/src/view.js"       src/blocks/site-header/view.js
cp "$SRC/src/variations.js" src/blocks/site-header/variations.js
cp "$SRC/src/style.scss"    src/blocks/site-header/style.scss
cp "$SRC/src/editor.scss"   src/blocks/site-header/editor.scss
```

- [ ] **Step 2: Zastosuj tę samą mapę zmian nazw**

```bash
FILES="src/blocks/site-header/block.json src/blocks/site-header/index.js src/blocks/site-header/edit.js src/blocks/site-header/save.js src/blocks/site-header/view.js src/blocks/site-header/variations.js src/blocks/site-header/style.scss src/blocks/site-header/editor.scss"

sed -i '' 's|idl/site-header|isudev/site-header|g' $FILES
sed -i '' 's|idl_site_header|isudev_library|g' $FILES
sed -i '' 's|idl-site-header|isudev-library|g' $FILES
sed -i '' 's|--idl-|--isudev-|g' $FILES
sed -i '' 's|idl-|isudev-|g' $FILES
```

- [ ] **Step 3: Sprawdź, że nic z `idl` nie zostało**

```bash
grep -rn "idl" src/blocks/site-header/ || echo "clean"
```

Oczekiwane: `clean`.

- [ ] **Step 4: NIE zmieniaj globali w `view.js`**

`view.js` używa `window.matchMedia` (linia ~10) i `document` (linie ~280–292). To jest poprawne: `viewScript` działa na frontendzie, który **nie jest** iframe'owany. Reguła `element.ownerDocument` dotyczy wyłącznie kodu edytora.

Potwierdź, że `edit.js` nie ma globali DOM:

```bash
grep -nE "\b(document|window)\b" src/blocks/site-header/edit.js || echo "edit.js clean — iframe safe"
```

Oczekiwane: `edit.js clean — iframe safe`.

- [ ] **Step 5: Zaktualizuj `block.json`**

Ustaw `version` na `1.0.0` i `textdomain` na `isudev-library` (krok 2 już podmienił text domain, sprawdź). Pola `render`, `style`, `editorStyle`, `editorScript`, `viewScript` zostają bez zmian — są względne do katalogu bloku, który się nie zmienił.

**Dodaj atrybut `_namespace`** jako pierwszy wpis w `attributes`:

```json
		"_namespace": {
			"type": "string",
			"default": "",
			"description": "Variation identifier injected by IsuDevLibrary\\Variations. Must be declared here or isActive matching never resolves."
		},
```

To **nie jest** kosmetyka. Deskryptor tego bloku ma `'variations' => true`, a
`Variations\build_variations()` wstrzykuje `attributes._namespace` i ustawia
`isActive: [ '_namespace' ]`. Jeśli `block.json` nie zadeklaruje tego atrybutu,
nie przeżyje on inicjalizacji atrybutów w edytorze, `isActive` nigdy się nie
dopasuje i **wariacja zarejestruje się, ale nigdy nie pokaże jako aktywna** —
bez żadnego błędu ani ostrzeżenia. `bento-card` w `kormas-isu` deklaruje ten
atrybut dokładnie z tego powodu; źródłowy `isudev-header` nie, bo nie miał
wariacji z `isudev.json`.

Sprawdź:

```bash
grep -E '"(name|textdomain|version|render|apiVersion|_namespace)"' src/blocks/site-header/block.json
```

Oczekiwane: `"apiVersion": 3`, `"name": "isudev/site-header"`, `"textdomain": "isudev-library"`, `"version": "1.0.0"`, `"render": "file:./render.php"`, `"_namespace"`.

Zweryfikuj też, że plik jest nadal poprawnym JSON-em:

```bash
node -e "JSON.parse(require('fs').readFileSync('src/blocks/site-header/block.json','utf8')); console.log('valid JSON')"
```

- [ ] **Step 6: Zbuduj**

```bash
nvm use
npm run build
```

Oczekiwane: build przechodzi i kończy się linią `Block metadata PHP file generated at: build/blocks-manifest.php`.

- [ ] **Step 7: Zweryfikuj strukturę buildu**

```bash
ls build/blocks/site-header/
grep -o "'site-header'" build/blocks-manifest.php | head -1
```

Oczekiwane w `build/blocks/site-header/`: `block.json`, `render.php`, `index.js`, `index.asset.php`, `index.css`, `style-index.css`, `view.js`, `view.asset.php`.

Oczekiwane z grepa: `'site-header'`. **To jest kluczowe** — `build-blocks-manifest.js` kluczuje manifest przez `basename( dirname( file ) )`, więc klucz to sama nazwa katalogu. Jeśli zobaczysz `'blocks/site-header'`, wersja wp-scripts się zmieniła i `Loader::register()` wymaga korekty ścieżki kolekcji.

- [ ] **Step 8: Zweryfikuj rejestrację i render na żywo**

Wstaw blok na stronę ręcznie: `http://isudev-library.local/wp-admin/` → nowa strona → wstaw blok „Site Header Block" → opublikuj. Zapisz URL. Potem:

```bash
curl -s "<URL_OPUBLIKOWANEJ_STRONY>" | grep -c "isudev-header" && echo "block rendered"
curl -s "<URL_OPUBLIKOWANEJ_STRONY>" | grep -icE "(warning|fatal error|notice):" || echo "no PHP notices"
```

Oczekiwane: liczba > 0 i `block rendered`, oraz `no PHP notices`.

Sprawdź warunkowe ładowanie assetów — na stronie głównej (bez bloku) nie powinno być jego CSS:

```bash
curl -s http://isudev-library.local/ | grep -c "site-header" || echo "assets not loaded without the block"
```

Oczekiwane: `assets not loaded without the block`.

- [ ] **Step 9: Lint i commit**

```bash
npm run lint:js
npm run lint:css
composer run lint:php
git add src/blocks/site-header/ build/
git commit -m "$(cat <<'EOF'
feat: migrate site-header editor and frontend assets

Ports block.json, editor, view script and styles with the isudev/* rename.
view.js keeps its DOM globals on purpose — view scripts are not iframed.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 11: Suite e2e dla bloku

**Files:**
- Create: `tools/mu-plugins/isudev-library-dev-fixture.php`
- Create: `playwright.config.js`
- Create: `e2e/utils.js`, `e2e/header.spec.js`, `e2e/header.a11y.spec.js`, `e2e/README.md`
- Source: `/Users/lukaszbiedron/Other Projects/isudev-header/{bin/dev-mu-loader.php,playwright.config.js,e2e/*}`

**Interfaces:**
- Consumes: zbudowany blok z Task 10.
- Produces: `e2e/utils.js` eksportuje `tabToFocus( page, locator, max = 30 )` i `outlineOf( locator )`.

**Dlaczego fikstura jest obowiązkowa, a nie wygodna.** Suite ma bramki
`test.skip` na „brak rodzica z podmenu w menu". Bez zasianego menu testy
dostępności **przechodzą zielono, nie testując niczego** — a to gorsze niż
awaria. Fikstura tworzy dokładnie trzy przypadki, które suite rozróżnia:

| Pozycja menu | URL | Oczekiwany markup |
| --- | --- | --- |
| rodzic bez linku | `#` | czysty `<button>` disclosure |
| rodzic z linkiem | `/solutions` | `<a>` + osobny `<button>` toggle |
| liść | `/pricing` | tylko `<a>` |

Plus strona z blokiem i wewnętrznym `core/buttons`, co ćwiczy
`InnerBlocks.Content` i `$content` w `render.php`.

- [ ] **Step 0: Napisz fiksturę dev i podmień symlink w mu-plugins**

Stan wyjściowy tej instalacji: `wp-content/mu-plugins/idl-dev-loader.php` jest
symlinkiem do `~/Other Projects/isudev-header/bin/dev-mu-loader.php`, który zasiał
stronę główną **starym** blokiem `idl/site-header`. Front page renderuje dziś
`class="idl-header … wp-block-idl-site-header"`. Suite szuka `.isudev-header`,
więc bez tego kroku nie znajdzie niczego.

Napisz `tools/mu-plugins/isudev-library-dev-fixture.php`:

```php
<?php
/**
 * Plugin Name: IsuDev Library — dev fixture (Local only)
 * Description: Force-activates isudev-library and seeds an e2e demo on this Local dev site. NOT for production.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/*
 * Only ever symlinked into the local dev site. Bail on a positively-different
 * HTTP host so it can never force-activate or reseed another install. An empty
 * host means CLI or cron on this install, which is allowed.
 */
$isudev_dev_host = 'isudev-library.local';
$isudev_req_host = strtolower( (string) strtok( (string) ( $_SERVER['HTTP_HOST'] ?? '' ), ':' ) );

/*
 * Exact match, not a substring test. `strpos()` would accept a Host header like
 * `isudev-library.local.attacker.tld`, which is not a guard at all. The port is
 * stripped first so `isudev-library.local:8080` still matches.
 */
if ( '' !== $isudev_req_host && $isudev_dev_host !== $isudev_req_host ) {
	return;
}

// Force-activate the plugin under test without writing to the DB.
add_filter(
	'option_active_plugins',
	static function ( $plugins ) {
		$slug = 'isudev-library/isudev-library.php';
		if ( is_array( $plugins ) && ! in_array( $slug, $plugins, true ) ) {
			$plugins[] = $slug;
		}
		return $plugins;
	}
);

/*
 * Seed the menu and front page once. Bump the seed version to force a reseed.
 * The three menu shapes below are what the accessibility suite distinguishes:
 * a label-only parent, a navigable parent, and plain leaves.
 */
add_action(
	'init',
	static function () {
		$seed_version = 1;
		if ( (int) get_option( 'isudev_library_dev_seed_version' ) === $seed_version ) {
			return;
		}

		$old = get_term_by( 'name', 'isudev-demo', 'nav_menu' );
		if ( $old ) {
			wp_delete_nav_menu( $old->term_id );
		}
		$menu_id = wp_create_nav_menu( 'isudev-demo' );

		// Label-only parent (URL '#') → pure disclosure button.
		$products = wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Products', 'menu-item-url' => '#', 'menu-item-status' => 'publish' ) );
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Product A', 'menu-item-url' => '/product-a', 'menu-item-parent-id' => $products, 'menu-item-status' => 'publish' ) );
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Product B', 'menu-item-url' => '/product-b', 'menu-item-parent-id' => $products, 'menu-item-status' => 'publish', 'menu-item-description' => 'Second product' ) );

		// Navigable parent (real URL) → link plus a split toggle.
		$solutions = wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Solutions', 'menu-item-url' => '/solutions', 'menu-item-status' => 'publish' ) );
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Solution X', 'menu-item-url' => '/solution-x', 'menu-item-parent-id' => $solutions, 'menu-item-status' => 'publish' ) );

		// Plain leaves.
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Pricing', 'menu-item-url' => '/pricing', 'menu-item-status' => 'publish' ) );
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'About', 'menu-item-url' => '/about', 'menu-item-status' => 'publish' ) );

		$content = '<!-- wp:isudev/site-header {"menuRef":"id:' . (int) $menu_id . '","logoSource":"site"} -->'
			. '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button -->'
			. '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact">Contact</a></div>'
			. '<!-- /wp:button --></div><!-- /wp:buttons -->'
			. '<!-- /wp:isudev/site-header -->';

		$existing = get_page_by_path( 'isudev-demo' );
		if ( $existing ) {
			wp_delete_post( $existing->ID, true );
		}
		$page_id = wp_insert_post(
			array(
				'post_title'   => 'IsuDev Demo',
				'post_name'    => 'isudev-demo',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $content,
			)
		);

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );
		update_option( 'isudev_library_dev_seed_version', $seed_version );
	},
	20
);
```

Podmień symlink — stary loader musi odejść, żeby dwa dema nie walczyły o front page:

```bash
MU="/Users/lukaszbiedron/Local Sites/isudev-library/app/public/wp-content/mu-plugins"
ls -l "$MU"
rm -f "$MU/idl-dev-loader.php"
ln -s "$(pwd)/tools/mu-plugins/isudev-library-dev-fixture.php" "$MU/isudev-library-dev-fixture.php"
ls -l "$MU"
```

Stary plugin `isudev-header` **zostaje zainstalowany** — Task 16 usuwa go
zgodnie z planem. Po tym kroku jego blok po prostu nie jest już nigdzie użyty.

Zweryfikuj, że front page renderuje **nowy** blok:

```bash
curl -s http://isudev-library.local/ | grep -oE 'class="[^"]*(idl|isudev)-header[^"]*"' | head -3
curl -s http://isudev-library.local/ | grep -c "wp-block-isudev-site-header"
curl -s http://isudev-library.local/ | grep -qiE "(warning|fatal error|notice):" && echo "PHP NOTICES PRESENT" || echo "no PHP notices"
```

Oczekiwane: klasy `isudev-header…`, licznik `wp-block-isudev-site-header` większy
od zera, brak notice'ów. **Jeśli wciąż widzisz `idl-header`, nie idź dalej** —
albo symlink nie został podmieniony, albo `isudev_library_dev_seed_version` już
istnieje z poprzedniego przebiegu i trzeba je skasować, żeby wymusić przesianie.

- [ ] **Step 1: Skopiuj konfigurację i suite**

`playwright.config.js` w `isudev-header` już domyślnie celuje w `http://isudev-library.local/`, więc nie wymaga zmian poza `testDir`.

```bash
SRC="/Users/lukaszbiedron/Other Projects/isudev-header"
cp "$SRC/playwright.config.js" playwright.config.js
mkdir -p e2e
cp "$SRC/e2e/utils.js"             e2e/utils.js
cp "$SRC/e2e/header.spec.js"       e2e/header.spec.js
cp "$SRC/e2e/header.a11y.spec.js"  e2e/header.a11y.spec.js
cp "$SRC/e2e/README.md"            e2e/README.md
```

- [ ] **Step 2: Zastosuj mapę zmian nazw do e2e**

```bash
FILES="e2e/utils.js e2e/header.spec.js e2e/header.a11y.spec.js e2e/README.md"
sed -i '' 's|idl/site-header|isudev/site-header|g' $FILES
sed -i '' 's|idl-site-header|isudev-library|g' $FILES
sed -i '' 's|--idl-|--isudev-|g' $FILES
sed -i '' 's|idl-|isudev-|g' $FILES
grep -rn "idl" e2e/ || echo "clean"
```

Oczekiwane: `clean`.

- [ ] **Step 3: Zainstaluj przeglądarkę**

```bash
nvm use
npm run test:e2e:install
```

- [ ] **Step 4: Uruchom suite**

```bash
npm run test:e2e
```

Oczekiwane: wszystkie testy zielone w projektach `desktop-chromium` i `mobile-chromium`.

Suite nawiguje do `./`, czyli front page — którą Step 0 zasiał blokiem
i menu, więc header i wszystkie trzy kształty pozycji menu są na miejscu.

**Policz pominięte testy i podaj liczbę w raporcie.** Bramki `test.skip`
w tym suite wyłączają się przy braku rodzica z podmenu, więc duża liczba
pominięć znaczy, że fikstura nie zadziałała i suite przechodzi, nie testując
nic. Pominięcia zależne od viewportu (`desktop only`, `mobile only`) są
normalne; pominięcia z komunikatem `no label-only parent in menu`,
`no navigable parent in menu` albo `need two submenu parents` **nie są** i
oznaczają, że trzeba wrócić do Step 0.

**To jest kryterium akceptacji spec §13 — nie idź dalej, dopóki suite nie jest
zielony.** Jeśli test pada z powodu zmienionej nazwy klasy, popraw selektor.
Jeśli pada z powodu regresji dostępności, **popraw blok, nie test** — kontrakt
a11y jest tym, czego ten suite pilnuje.

- [ ] **Step 5: Commit**

```bash
git add playwright.config.js e2e/ tools/mu-plugins/
git commit -m "$(cat <<'EOF'
test: port the site-header Playwright and axe suite plus its dev fixture

Accessibility contract from isudev-header must stay green after the migration
(spec §13). The fixture seeds the three menu shapes the suite distinguishes;
without it the skip guards make the suite pass while testing nothing.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 12: Opcje globalne, REST i strona admina

Bramka widoczności musi powstać razem z REST-em: `REST\permission_check()` woła
`Admin\show_admin()`, więc rozdzielenie tych plików na dwa zadania wymagałoby
tymczasowej implementacji, którą następne zadanie i tak by podmieniło.

**Files:**
- Create: `includes/settings.php`
- Create: `includes/admin.php`
- Create: `includes/rest.php`
- Modify: `isudev-library.php`

**Interfaces:**
- Consumes: `Registry::blocks()`, `Registry::flush()`, `Registry::OPTION`, `Config\config_sources()`.
- Produces:
  - `IsuDevLibrary\Settings\OPTION` = `'isudev_library_settings'`; `Settings\boot(): void`; `Settings\get( string $key, $fallback = null )`. Schemat opcji: `{ loadBaseTokens: bool }`, default `true`.
  - `IsuDevLibrary\Admin\MENU_SLUG` = `'isudev-library'`; `Admin\boot(): void`; `Admin\capability(): string` (filtr `isudev_library/settings/capability`, default `'manage_options'`); `Admin\show_admin(): bool` (filtr `isudev_library/settings/show_admin`, default `current_user_can( capability() )`); `Admin\enqueue( string $hook_suffix ): void`.
  - `IsuDevLibrary\REST\boot(): void`; `REST\permission_check(): bool` — zwraca `Admin\show_admin()`, więc ukrycie panelu zamyka też endpointy.
  - `GET /wp-json/isudev-library/v1/blocks` → `{ blocks: [...], diagnostics: {...} }`. Element `blocks[]`: `slug`, `name`, `title`, `description`, `icon`, `enabled`, `source`, `locked`, `requires`, `dependents`. `diagnostics`: `version`, `discovered` (int), `registered` (int), `configParent` (string), `configChild` (string).
  - `POST /wp-json/isudev-library/v1/blocks/<slug>` z `{ "enabled": bool }` → zaktualizowany element `blocks[]`. `404` na nieznany slug, `403` gdy `locked`.
  - Punkt montowania panelu: `<div id="isudev-library-admin">` na stronie menu.

- [ ] **Step 0: Rozszerz fiksturę dev o użytkownika e2e**

Steps 5, 7 i 8 wymagają zalogowanego administratora. Nikt pracujący nad tym
planem nie ma dostępu do wp-admin, a hasła nie wolno wpisywać do repo ani
przekazywać w promptach. Rozwiązanie: fikstura z Task 11 — już host-guarded do
`isudev-library.local` — provisionuje dedykowanego użytkownika i zapisuje losowe
hasło do pliku ignorowanego przez git.

Dopisz na końcu `tools/mu-plugins/isudev-library-dev-fixture.php`:

```php
/*
 * Provision a dedicated e2e user and write its credentials to a gitignored file.
 * Runs only on the dev host (guarded at the top of this file). The password is
 * random, local-only, and never enters the repository or a prompt.
 */
add_action(
	'init',
	static function () {
		$creds_file = __DIR__ . '/../.e2e-credentials.json';
		$login      = 'isudev-e2e';
		$user       = get_user_by( 'login', $login );

		if ( $user && is_readable( $creds_file ) ) {
			return;
		}

		/*
		 * Without a writable target the password would be set and immediately
		 * lost, and this block would regenerate it on every single request.
		 * Bail before touching the account.
		 */
		if ( ! is_writable( dirname( $creds_file ) ) ) {
			error_log( 'isudev-library dev fixture: cannot write ' . $creds_file . ' — e2e user not provisioned.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Dev-only fixture.
			return;
		}

		/*
		 * Alphanumeric only, and longer to compensate. wp_generate_password()'s
		 * special-character set includes `&` and `=`, which silently break any
		 * form-encoded login that interpolates the password into a query string —
		 * a failure that appears or disappears depending on what the generator drew.
		 * 32 alphanumeric characters is far more entropy than a local dev account needs.
		 */
		$password = wp_generate_password( 32, false, false );

		if ( $user ) {
			wp_set_password( $password, $user->ID );
		} else {
			$user_id = wp_insert_user(
				array(
					'user_login'   => $login,
					'user_pass'    => $password,
					'user_email'   => 'isudev-e2e@isudev-library.local',
					'display_name' => 'IsuDev E2E',
					'role'         => 'administrator',
				)
			);

			if ( is_wp_error( $user_id ) ) {
				return;
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Local dev credentials file, not a WP filesystem operation.
		file_put_contents( $creds_file, (string) wp_json_encode( array( 'user' => $login, 'pass' => $password ) ) );
		@chmod( $creds_file, 0600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort tightening; failure is not fatal.
	},
	21
);
```

Dopisz do `.gitignore`:

```gitignore
/tools/.e2e-credentials.json
```

i do `.distignore`:

```
/tools/.e2e-credentials.json
```

Wywołaj stronę raz, żeby fikstura się wykonała, i potwierdź:

```bash
curl -s -m 10 -o /dev/null http://isudev-library.local/
test -f tools/.e2e-credentials.json && echo "credentials file created" || echo "MISSING — fixture could not write to tools/"
node -e "const c=require('./tools/.e2e-credentials.json'); console.log('user:', c.user, '| password length:', c.pass.length)"
git status --porcelain tools/.e2e-credentials.json
```

Oczekiwane: plik istnieje, `user: isudev-e2e`, długość hasła 24, a `git status`
**nic nie wypisuje** — plik jest ignorowany. Jeśli `git status` go pokazuje,
`.gitignore` nie działa i **nie commituj**, dopóki tego nie naprawisz.

Jeśli plik się nie utworzył, PHP nie ma prawa zapisu do `tools/` — zgłoś to,
nie obchodź.

Dwa helpery, użyjesz ich w Steps 5, 7 i 8:

```bash
e2e_jar() {
	local jar
	jar=$(mktemp)
	local u p
	u=$(node -e "console.log(require('./tools/.e2e-credentials.json').user)")
	p=$(node -e "console.log(require('./tools/.e2e-credentials.json').pass)")
	# --data-urlencode per field, never one interpolated string: a password
	# containing `&` or `=` would otherwise split into extra form fields and the
	# login would fail for reasons that look random.
	curl -s -m 10 -c "$jar" -b "$jar" \
		--data-urlencode "log=$u" \
		--data-urlencode "pwd=$p" \
		--data-urlencode "wp-submit=Log In" \
		--data-urlencode "testcookie=1" \
		--data-urlencode "redirect_to=http://isudev-library.local/wp-admin/" \
		-o /dev/null "http://isudev-library.local/wp-login.php"
	echo "$jar"
}

# WordPress REST cookie auth ALSO requires an X-WP-Nonce header. A cookie jar
# alone gets you `rest_cookie_invalid_nonce` with status 403.
e2e_nonce() {
	local jar=$1
	# The nonce must come from a page that enqueues wp-api-fetch, which prints it
	# via createNonceMiddleware(). A plain /wp-admin/ page does NOT, and the other
	# `"nonce":"…"` values in admin HTML are different nonces that the REST API
	# rejects. The block editor always enqueues it; once Task 13 builds the panel,
	# admin.php?page=isudev-library works too.
	curl -s -m 20 -b "$jar" "http://isudev-library.local/wp-admin/post-new.php?post_type=page" \
		| grep -oE 'createNonceMiddleware\( *"[a-f0-9]+"' \
		| head -1 | grep -oE '"[a-f0-9]+"' | tr -d '"'
}
```

Sprawdź oba, zanim pójdziesz dalej:

```bash
JAR=$(e2e_jar)
curl -s -m 10 -b "$JAR" -o /dev/null -w "wp-admin as e2e user: %{http_code}\n" http://isudev-library.local/wp-admin/
NONCE=$(e2e_nonce "$JAR")
echo "rest nonce: ${NONCE:-NOT FOUND}"
```

Oczekiwane: `200` i niepusty nonce. Jeśli `302`, logowanie nie przeszło; jeśli
nonce jest pusty, wziąłeś go ze złej strony. W obu przypadkach nie zgaduj, zgłoś.

Każde uwierzytelnione wywołanie REST-a potrzebuje **obu**:

```bash
curl -s -m 10 -b "$JAR" -H "X-WP-Nonce: $NONCE" <url>
```

- [ ] **Step 1: Napisz `includes/settings.php`**

```php
<?php
/**
 * Global plugin options, exposed through the core settings REST endpoint.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Settings;

defined( 'ABSPATH' ) || exit;

const OPTION = 'isudev_library_settings';

/**
 * Default option value.
 *
 * @return array
 */
function defaults(): array {
	return array( 'loadBaseTokens' => true );
}

/**
 * Hook registration.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'init', __NAMESPACE__ . '\\register' );
}

/**
 * Register the option so @wordpress/core-data can read and write it.
 *
 * @return void
 */
function register(): void {
	\register_setting(
		'isudev_library',
		OPTION,
		array(
			'type'              => 'object',
			'default'           => defaults(),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize',
			'show_in_rest'      => array(
				'schema' => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'loadBaseTokens' => array( 'type' => 'boolean' ),
					),
				),
			),
		)
	);
}

/**
 * Sanitize the option value.
 *
 * @param mixed $value Incoming value.
 * @return array
 */
function sanitize( $value ): array {
	$value = \is_array( $value ) ? $value : array();

	return array(
		'loadBaseTokens' => ! isset( $value['loadBaseTokens'] ) || (bool) $value['loadBaseTokens'],
	);
}

/**
 * Read a single option key.
 *
 * @param string $key      Option key.
 * @param mixed  $fallback Value returned when the key is absent.
 * @return mixed
 */
function get( string $key, $fallback = null ) {
	$option = \get_option( OPTION, defaults() );
	$option = \is_array( $option ) ? $option : defaults();

	return $option[ $key ] ?? $fallback;
}
```

- [ ] **Step 2: Napisz `includes/admin.php`**

Pełna treść pliku znajduje się niżej, w kroku „Treść `includes/admin.php`" na
końcu tego zadania. Napisz go teraz, przed `rest.php`, bo `permission_check()`
zależy od `Admin\show_admin()`.

- [ ] **Step 3: Napisz `includes/rest.php`**

`use const` jest obowiązkowe przy imporcie stałej — `use IsuDevLibrary\VERSION;`
zaimportowałoby klasę o tej nazwie i `VERSION` w `diagnostics()` by się nie
rozwiązało.

```php
<?php
/**
 * REST controller for block toggles and diagnostics.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\REST;

use IsuDevLibrary\Admin;
use IsuDevLibrary\Config;
use IsuDevLibrary\Registry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

use const IsuDevLibrary\PATH;
use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const NAMESPACE_V1 = 'isudev-library/v1';

/**
 * Hook registration.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );
}

/**
 * Whether the current user may read or write the library configuration.
 *
 * Mirrors the admin panel gate: hiding the panel also closes the endpoints, so
 * `show_admin => false` is not merely cosmetic.
 *
 * @return bool
 */
function permission_check(): bool {
	return Admin\show_admin();
}

/**
 * Register the routes.
 *
 * @return void
 */
function register_routes(): void {
	\register_rest_route(
		NAMESPACE_V1,
		'/blocks',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => __NAMESPACE__ . '\\get_blocks',
			'permission_callback' => __NAMESPACE__ . '\\permission_check',
		)
	);

	\register_rest_route(
		NAMESPACE_V1,
		'/blocks/(?P<slug>[a-z0-9-]+)',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\update_block',
			'permission_callback' => __NAMESPACE__ . '\\permission_check',
			'args'                => array(
				'enabled' => array(
					'type'     => 'boolean',
					'required' => true,
				),
			),
		)
	);
}

/**
 * Metadata from the compiled manifest, keyed by block directory name.
 *
 * The block type registry only holds blocks that were registered, and a disabled
 * block is never registered, so this is the only place its real title, icon and
 * description can come from.
 *
 * @return array slug => decoded block.json contents.
 */
function manifest_metadata(): array {
	static $manifest = null;

	if ( null === $manifest ) {
		$file     = PATH . 'build/blocks-manifest.php';
		$manifest = \is_readable( $file ) ? (array) require $file : array();
	}

	return $manifest;
}

/**
 * Shape one block for the REST response.
 *
 * @param array $block Decorated descriptor from Registry::blocks().
 * @return array
 */
function prepare_block( array $block ): array {
	$type = \WP_Block_Type_Registry::get_instance()->get_registered( $block['name'] );
	$meta = manifest_metadata()[ $block['slug'] ] ?? array();

	/*
	 * Prefer the registered type, which reflects anything a filter changed at
	 * registration time. Fall back to the manifest, because a disabled block is
	 * never registered and would otherwise report its slug as its title — the
	 * panel would lose the name of every block the user just switched off.
	 */
	$title       = $type && $type->title ? $type->title : ( $meta['title'] ?? $block['slug'] );
	$description = $type && $type->description ? $type->description : ( $meta['description'] ?? '' );
	$icon        = $type && \is_string( $type->icon ) ? $type->icon : $meta['icon'] ?? 'block-default';

	return array(
		'slug'        => $block['slug'],
		'name'        => $block['name'],
		'title'       => (string) $title,
		'description' => (string) $description,
		'icon'        => \is_string( $icon ) ? $icon : 'block-default',
		'enabled'     => (bool) $block['enabled'],
		'source'      => (string) $block['source'],
		'locked'      => (bool) $block['locked'],
		'requires'    => \array_values( $block['requires'] ),
		'dependents'  => \array_values( $block['dependents'] ),
	);
}

/**
 * Diagnostics payload for the Settings tab.
 *
 * @return array
 */
function diagnostics(): array {
	$blocks  = Registry::blocks();
	$sources = Config\config_sources();

	$registered = 0;
	foreach ( $blocks as $block ) {
		if ( $block['enabled'] ) {
			++$registered;
		}
	}

	return array(
		'version'      => VERSION,
		'discovered'   => \count( $blocks ),
		'registered'   => $registered,
		'configParent' => $sources['parent'],
		'configChild'  => $sources['child'],
	);
}

/**
 * GET /blocks
 *
 * @return WP_REST_Response
 */
function get_blocks(): WP_REST_Response {
	$blocks = array();

	foreach ( Registry::blocks() as $block ) {
		$blocks[] = prepare_block( $block );
	}

	return new WP_REST_Response(
		array(
			'blocks'      => $blocks,
			'diagnostics' => diagnostics(),
		),
		200
	);
}

/**
 * POST /blocks/<slug>
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function update_block( WP_REST_Request $request ) {
	$slug   = (string) $request->get_param( 'slug' );
	$blocks = Registry::blocks();

	if ( ! isset( $blocks[ $slug ] ) ) {
		return new WP_Error(
			'isudev_library_unknown_block',
			\__( 'Unknown block.', 'isudev-library' ),
			array( 'status' => 404 )
		);
	}

	if ( $blocks[ $slug ]['locked'] ) {
		return new WP_Error(
			'isudev_library_block_locked',
			\__( 'This block is managed in code and cannot be toggled here.', 'isudev-library' ),
			array( 'status' => 403 )
		);
	}

	$option = \get_option( Registry::OPTION, array() );
	$option = \is_array( $option ) ? $option : array();

	$option[ $slug ] = (bool) $request->get_param( 'enabled' );

	\update_option( Registry::OPTION, $option );
	Registry::flush();

	$refreshed = Registry::blocks();

	return new WP_REST_Response( prepare_block( $refreshed[ $slug ] ), 200 );
}
```

- [ ] **Step 4: Zaktualizuj `isudev-library.php`**

To jest już finalna lista dla całego pluginu — nic więcej nie dojdzie.

```php
require_once PATH . 'includes/utils/array.php';
require_once PATH . 'includes/utils/icon.php';
require_once PATH . 'includes/config.php';
require_once PATH . 'includes/variations.php';
require_once PATH . 'includes/class-registry.php';
require_once PATH . 'includes/class-loader.php';
require_once PATH . 'includes/settings.php';
require_once PATH . 'includes/admin.php';
require_once PATH . 'includes/rest.php';

Loader::boot();
Settings\boot();
Admin\boot();
REST\boot();
```

- [ ] **Step 5: Zweryfikuj, że strona menu się renderuje**

Użyj cookie jara z Step 0 — żadnego klikania w przeglądarce.

```bash
JAR=$(e2e_jar)
curl -s -m 10 -b "$JAR" -o /dev/null -w "settings page: %{http_code}\n" \
	"http://isudev-library.local/wp-admin/admin.php?page=isudev-library"
curl -s -m 10 -b "$JAR" "http://isudev-library.local/wp-admin/admin.php?page=isudev-library" \
	| grep -c 'id="isudev-library-admin"'
curl -s -m 10 -b "$JAR" "http://isudev-library.local/wp-admin/" \
	| grep -c "page=isudev-library"
```

Oczekiwane: `200`, licznik punktu montowania `1` (mount point istnieje, panel
React dochodzi w Task 13), licznik linku w menu większy od zera.

- [ ] **Step 6: Zweryfikuj GET jako niezalogowany — musi odmówić**

```bash
curl -s -m 10 -o /dev/null -w "anon GET: %{http_code}\n" http://isudev-library.local/wp-json/isudev-library/v1/blocks
```

Oczekiwane: `401`. To jedyny krok, który nie potrzebuje uwierzytelnienia, i jest
najważniejszy z trzech — potwierdza, że endpoint jest domknięty.

- [ ] **Step 7: Zweryfikuj GET jako administrator**

```bash
JAR=$(e2e_jar)
NONCE=$(e2e_nonce "$JAR")
curl -s -m 10 -b "$JAR" -H "X-WP-Nonce: $NONCE" http://isudev-library.local/wp-json/isudev-library/v1/blocks | node -e "
const d = JSON.parse(require('fs').readFileSync(0, 'utf8'));
console.log('blocks:', d.blocks.length);
console.log(JSON.stringify(d.blocks[0], null, 1));
console.log('diagnostics:', JSON.stringify(d.diagnostics));
"
```

Oczekiwane: jeden blok o `slug: "site-header"`, `name: "isudev/site-header"`,
`enabled: true`, `locked: false`, `title: "Site Header Block"`,
`requires: []`, `dependents: []`; `source` to `default`, dopóki nic nie zapisało opcji, i `panel` po pierwszym POST-cie — oba są poprawne; oraz `diagnostics` z `discovered: 1`,
`registered: 1`, `version: "1.0.0"` i dwoma pustymi ścieżkami configu (ten theme
nie ma `isudev.json`).

- [ ] **Step 8: Zweryfikuj, że opcja globalna jest w REST**

```bash
JAR=$(e2e_jar)
NONCE=$(e2e_nonce "$JAR")
curl -s -m 10 -b "$JAR" -H "X-WP-Nonce: $NONCE" http://isudev-library.local/wp-json/wp/v2/settings | node -e "
const d = JSON.parse(require('fs').readFileSync(0, 'utf8'));
console.log('isudev_library_settings:', JSON.stringify(d.isudev_library_settings));
"
```

Oczekiwane: `{"loadBaseTokens":true}`. Jeśli klucz jest nieobecny,
`register_setting()` nie ma `show_in_rest`, a zakładka Settings w Task 13 nie
będzie mogła nic zapisać.

- [ ] **Step 9: Lint i commit**

```bash
composer run lint:php
npm run test:php
git add includes/settings.php includes/admin.php includes/rest.php isudev-library.php \
	tools/mu-plugins/isudev-library-dev-fixture.php .gitignore .distignore
git commit -m "$(cat <<'EOF'
feat: add admin screen, block toggle REST controller and global settings

GET /blocks returns computed state plus diagnostics; POST /blocks/<slug>
refuses locked blocks with 403. isudev_library/settings/show_admin gates the
menu and the endpoints together, so hiding the panel is not cosmetic. Global
options ride on register_setting so the panel can use @wordpress/core-data.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

#### Treść `includes/admin.php`

Napisz ten plik w Step 2.

```php
<?php
/**
 * Admin screen: menu registration, visibility gate and panel assets.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Admin;

use const IsuDevLibrary\PATH;
use const IsuDevLibrary\URL;
use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const MENU_SLUG = 'isudev-library';

/**
 * Hook registration.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'admin_menu', __NAMESPACE__ . '\\register_menu' );
	\add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue' );
}

/**
 * Capability required to view and change the library configuration.
 *
 * @return string
 */
function capability(): string {
	/**
	 * Filters the capability guarding the admin screen and the REST endpoints.
	 *
	 * @param string $capability Default 'manage_options'.
	 */
	$capability = \apply_filters( 'isudev_library/settings/capability', 'manage_options' );

	return \is_string( $capability ) && '' !== $capability ? $capability : 'manage_options';
}

/**
 * Whether the current user may see the admin panel.
 *
 * This also gates the REST endpoints, so returning false actually closes write
 * access rather than only hiding the menu.
 *
 * @return bool
 */
function show_admin(): bool {
	/**
	 * Filters whether the library admin panel is available to the current user.
	 *
	 * Mirrors the ACF `acf/settings/show_admin` pattern.
	 *
	 * @param bool $show Default current_user_can( capability() ).
	 */
	return (bool) \apply_filters( 'isudev_library/settings/show_admin', \current_user_can( capability() ) );
}

/**
 * Register the top-level menu page.
 *
 * @return void
 */
function register_menu(): void {
	if ( ! show_admin() ) {
		return;
	}

	\add_menu_page(
		\__( 'IsuDev Library', 'isudev-library' ),
		\__( 'IsuDev Library', 'isudev-library' ),
		capability(),
		MENU_SLUG,
		__NAMESPACE__ . '\\render_page',
		'dashicons-screenoptions',
		58
	);
}

/**
 * Render the mount point for the React panel.
 *
 * @return void
 */
function render_page(): void {
	echo '<div class="wrap"><div id="isudev-library-admin"></div></div>';
}

/**
 * Enqueue the panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 * @return void
 */
function enqueue( string $hook_suffix ): void {
	if ( 'toplevel_page_' . MENU_SLUG !== $hook_suffix || ! show_admin() ) {
		return;
	}

	$asset_file = PATH . 'build/admin/index.asset.php';

	if ( ! \is_readable( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	\wp_enqueue_script(
		'isudev-library-admin',
		URL . 'build/admin/index.js',
		$asset['dependencies'] ?? array(),
		$asset['version'] ?? VERSION,
		true
	);

	\wp_set_script_translations( 'isudev-library-admin', 'isudev-library', PATH . 'languages' );

	if ( \is_readable( PATH . 'build/admin/index.css' ) ) {
		\wp_enqueue_style(
			'isudev-library-admin',
			URL . 'build/admin/index.css',
			array( 'wp-components' ),
			$asset['version'] ?? VERSION
		);
	}
}
```

---

### Task 13: Panel React

**Files:**
- Create: `src/admin/index.js`, `src/admin/app.js`, `src/admin/admin.scss`
- Create: `src/admin/components/{blocks-tab.js,block-card.js,settings-tab.js}`

**Interfaces:**
- Consumes: `GET`/`POST /wp-json/isudev-library/v1/blocks`, opcja `isudev_library_settings` przez `@wordpress/core-data`.
- Produces: panel montowany w `#isudev-library-admin`.

- [ ] **Step 0: Popraw klucz entry w `webpack.config.js`**

Task 1 zapisał ten plik z kluczem entry `admin`. `wp-scripts` ustawia
`output.filename` na `[name].js`, więc goły klucz emituje **płaski**
`build/admin.js`, a `includes/admin.php` enqueue'uje `build/admin/index.js`.
Skutek: panel się buduje, skrypt nigdy nie trafia na stronę, a mount point
zostaje pusty — React w ogóle się nie montuje i nie ma żadnego błędu w konsoli,
bo nie ma czego uruchomić.

Bloki zagnieżdżają się poprawnie tylko dlatego, że ich nazwy entry **już**
zawierają ukośniki (`blocks/site-header/index`). Task 10 nigdy tego nie wykrył,
bo wtedy `src/admin/index.js` jeszcze nie istniał i entry było pomijane.

Zamień klucz na `'admin/index'`:

```js
		...(fs.existsSync(adminEntry) ? { 'admin/index': adminEntry } : {}),
```

Potwierdź, zanim cokolwiek zbudujesz:

```bash
node -e "console.log(Object.keys(require('./webpack.config.js').entry))"
```

Oczekiwane: lista zawiera `admin/index`, nie `admin`.

- [ ] **Step 1: Napisz `src/admin/index.js`**

```js
/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import App from './app';
import './admin.scss';

const mount = document.getElementById('isudev-library-admin');

if (mount) {
	createRoot(mount).render(<App />);
}
```

- [ ] **Step 2: Napisz `src/admin/app.js`**

```js
/**
 * WordPress dependencies
 */
import { TabPanel } from '@wordpress/components';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import BlocksTab from './components/blocks-tab';
import SettingsTab from './components/settings-tab';

export default function App() {
	const [blocks, setBlocks] = useState(null);
	const [diagnostics, setDiagnostics] = useState(null);
	const [error, setError] = useState('');

	const load = useCallback(() => {
		apiFetch({ path: '/isudev-library/v1/blocks' })
			.then((response) => {
				setBlocks(response.blocks);
				setDiagnostics(response.diagnostics);
				// Clear any earlier failure. Without this a transient error leaves a
				// permanently visible banner that outlives the problem it described.
				setError('');
			})
			.catch((err) => setError(err.message));
	}, []);

	useEffect(load, [load]);

	const tabs = [
		{ name: 'blocks', title: __('Blocks', 'isudev-library') },
		{ name: 'settings', title: __('Settings', 'isudev-library') },
	];

	return (
		<div className="isudev-admin">
			<h1 className="isudev-admin__title">
				{__('IsuDev Library', 'isudev-library')}
			</h1>

			{error && (
				<div className="notice notice-error">
					<p>{error}</p>
				</div>
			)}

			<TabPanel className="isudev-admin__tabs" tabs={tabs}>
				{(tab) =>
					tab.name === 'blocks' ? (
						<BlocksTab
							blocks={blocks}
							onChanged={load}
							onError={setError}
						/>
					) : (
						<SettingsTab diagnostics={diagnostics} />
					)
				}
			</TabPanel>
		</div>
	);
}
```

- [ ] **Step 3: Napisz `src/admin/components/block-card.js`**

```js
/**
 * WordPress dependencies
 */
import { Card, CardBody, Flex, FlexBlock, FlexItem, ToggleControl } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Human-readable explanation of where a block's state comes from.
 *
 * @param {Object} block Block payload from the REST endpoint.
 * @return {string} Explanation shown under the toggle.
 */
function stateNotice(block) {
	switch (block.source) {
		case 'code':
			return __('Managed in isudev.json — change it there.', 'isudev-library');
		case 'always_on':
			return __('Always enabled — this block cannot be turned off.', 'isudev-library');
		case 'dependency':
			return sprintf(
				/* translators: %s: comma-separated list of block slugs. */
				__('Requires: %s. Enable those first.', 'isudev-library'),
				block.requires.join(', ')
			);
		default:
			return __(
				'Disabling removes the block entirely — no editor or frontend assets load, and blocks already inserted in content render as nothing.',
				'isudev-library'
			);
	}
}

export default function BlockCard({ block, onToggle }) {
	const dependentsWarning =
		block.enabled && block.dependents.length > 0
			? sprintf(
					/* translators: %s: comma-separated list of block slugs. */
					__('Disabling this also disables: %s', 'isudev-library'),
					block.dependents.join(', ')
			  )
			: '';

	return (
		<Card className="isudev-admin__card" size="small">
			<CardBody>
				<Flex align="flex-start" gap={4}>
					<FlexBlock>
						<h2 className="isudev-admin__card-title">{block.title}</h2>
						<p className="isudev-admin__card-name">
							<code>{block.name}</code>
						</p>
						{block.description && <p>{block.description}</p>}
					</FlexBlock>
					<FlexItem>
						<ToggleControl
							__nextHasNoMarginBottom
							label={
								block.enabled
									? __('Enabled', 'isudev-library')
									: __('Disabled', 'isudev-library')
							}
							checked={block.enabled}
							disabled={block.locked}
							help={stateNotice(block)}
							onChange={(next) => onToggle(block, next)}
						/>
						{dependentsWarning && (
							<p className="isudev-admin__card-warning">{dependentsWarning}</p>
						)}
					</FlexItem>
				</Flex>
			</CardBody>
		</Card>
	);
}
```

- [ ] **Step 4: Napisz `src/admin/components/blocks-tab.js`**

```js
/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import BlockCard from './block-card';

export default function BlocksTab({ blocks, onChanged, onError }) {
	if (blocks === null) {
		return <Spinner />;
	}

	if (blocks.length === 0) {
		return <p>{__('No blocks found. Run npm run build.', 'isudev-library')}</p>;
	}

	const toggle = (block, enabled) => {
		if (
			!enabled &&
			block.dependents.length > 0 &&
			// eslint-disable-next-line no-alert
			!window.confirm(
				__(
					'This also disables the blocks that depend on it. Continue?',
					'isudev-library'
				)
			)
		) {
			return;
		}

		apiFetch({
			path: `/isudev-library/v1/blocks/${block.slug}`,
			method: 'POST',
			data: { enabled },
		})
			.then(onChanged)
			.catch((err) => onError(err.message));
	};

	return (
		<div className="isudev-admin__blocks">
			{blocks.map((block) => (
				<BlockCard key={block.slug} block={block} onToggle={toggle} />
			))}
		</div>
	);
}
```

`window.confirm` jest tu legalny — panel admina nie jest iframe'owanym canvasem edytora.

- [ ] **Step 5: Napisz `src/admin/components/settings-tab.js`**

```js
/**
 * WordPress dependencies
 */
import { Card, CardBody, Spinner, ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

export default function SettingsTab({ diagnostics }) {
	const [settings, setSettings] = useEntityProp(
		'root',
		'site',
		'isudev_library_settings'
	);

	const configPath = () => {
		if (!diagnostics) {
			return '';
		}
		if (diagnostics.configChild) {
			return diagnostics.configChild;
		}
		if (diagnostics.configParent) {
			return diagnostics.configParent;
		}
		return __('Not found — using block defaults.', 'isudev-library');
	};

	return (
		<div className="isudev-admin__settings">
			<Card size="small">
				<CardBody>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Load base --isudev-* tokens', 'isudev-library')}
						help={__(
							'Turn off when your theme provides the design tokens itself.',
							'isudev-library'
						)}
						checked={Boolean(settings?.loadBaseTokens)}
						onChange={(next) =>
							setSettings({ ...settings, loadBaseTokens: next })
						}
					/>
				</CardBody>
			</Card>

			<Card size="small">
				<CardBody>
					<h2>{__('Diagnostics', 'isudev-library')}</h2>
					{!diagnostics ? (
						<Spinner />
					) : (
						<ul className="isudev-admin__diagnostics">
							<li>
								{__('Version:', 'isudev-library')}{' '}
								<code>{diagnostics.version}</code>
							</li>
							<li>
								{__('Blocks discovered:', 'isudev-library')}{' '}
								{diagnostics.discovered}
							</li>
							<li>
								{__('Blocks registered:', 'isudev-library')}{' '}
								{diagnostics.registered}
							</li>
							<li>
								{__('isudev.json:', 'isudev-library')}{' '}
								<code>{configPath()}</code>
							</li>
						</ul>
					)}
				</CardBody>
			</Card>
		</div>
	);
}
```

- [ ] **Step 6: Napisz `src/admin/admin.scss`**

**Nazwa tego pliku ma znaczenie i nie może brzmieć `style.scss`.** `wp-scripts`
wymusza prefiks `style-` dla plików nazwanych `style.*`, więc `style.scss`
wyemitowałby `build/admin/style-index.css`, a `includes/admin.php` enqueue'uje
`build/admin/index.css`. Styl skompilowałby się poprawnie i po prostu nigdy nie
trafiłby na stronę. Widać to na bloku, który emituje oba pliki: `editor.scss` →
`index.css`, `style.scss` → `style-index.css`.

```scss
.isudev-admin {
	max-width: 60rem;

	&__title {
		margin-block: 1rem;
	}

	&__blocks,
	&__settings {
		display: grid;
		gap: 1rem;
		padding-block-start: 1rem;
	}

	&__card-title {
		margin: 0;
		font-size: 1rem;
	}

	&__card-name {
		margin-block: 0.25rem 0.5rem;
	}

	&__card-warning {
		margin-block-start: 0.5rem;
		color: #8a6d00;
	}

	&__diagnostics {
		margin: 0;
		list-style: none;

		li {
			margin-block: 0.25rem;
		}
	}
}
```

- [ ] **Step 7: Zbuduj i sprawdź, że entry `admin` istnieje**

```bash
nvm use
npm run build
ls build/admin/
```

Oczekiwane: `index.js`, `index.asset.php`, `index.css`. Jeśli katalogu nie ma — `webpack.config.js` nie domergował entry; sprawdź, czy `defaultConfig.entry` jest funkcją i czy ją wywołujesz.

- [ ] **Step 8: Zweryfikuj panel w przeglądarce**

Otwórz `http://isudev-library.local/wp-admin/admin.php?page=isudev-library`.

Oczekiwane:
- zakładki **Blocks** i **Settings**
- w Blocks jedna karta „Site Header Block" z `isudev/site-header`, toggle włączony, tekst pomocy o konsekwencji wyłączenia
- w Settings toggle „Load base --isudev-* tokens" oraz diagnostyka: wersja `1.0.0`, discovered `1`, registered `1`, `isudev.json: Not found — using block defaults.`

- [ ] **Step 9: Zweryfikuj round-trip toggle'a ręcznie**

Wyłącz „Site Header Block", odśwież stronę — toggle musi zostać wyłączony (`source: panel`). Otwórz edytor posta i potwierdź, że bloku nie ma w inserterze. Włącz z powrotem i potwierdź, że wraca.

- [ ] **Step 10: Lint i commit**

```bash
npm run lint:js
npm run lint:css
git add src/admin/ build/
git commit -m "$(cat <<'EOF'
feat: add React admin panel with Blocks and Settings tabs

Block cards surface where each state comes from (default/panel/code/always_on/
dependency) and warn before disabling a block others depend on.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 14: e2e panelu i bramki widoczności

**Files:**
- Create: `e2e/admin-auth.js`
- Create: `e2e/panel.spec.js`
- Create: `tools/mu-plugins/isudev-library-show-admin-false.php`

**Interfaces:**
- Consumes: panel z Task 13, REST i filtry widoczności z Task 12.
- Produces: `e2e/admin-auth.js` eksportuje `loginAsAdmin( page )` → `Promise<boolean>` (false, gdy brak zmiennych środowiskowych) oraz `hasAdminCredentials()` → `boolean`.

- [ ] **Step 1: Napisz `e2e/admin-auth.js`**

Bez zaszytych danych logowania. Zmienne środowiskowe: `WP_ADMIN_USER`, `WP_ADMIN_PASS`.

```js
/**
 * Admin login helper for panel specs.
 *
 * Credentials resolve from the environment first, then from the file the dev
 * fixture writes at tools/.e2e-credentials.json. That file is gitignored and
 * holds a random, local-only password, so nothing secret is ever committed:
 *   WP_ADMIN_USER=... WP_ADMIN_PASS=... npm run test:e2e   # explicit override
 */

/**
 * External dependencies
 */
const fs = require('fs');
const path = require('path');

/**
 * Resolve admin credentials.
 *
 * Prefers the environment, so CI can inject its own. Falls back to the file the
 * dev fixture writes, which lets the suite run locally with no setup and keeps
 * the password out of the repository and out of any prompt.
 *
 * @return {{user: string, pass: string}|null} Credentials, or null when none are available.
 */
function adminCredentials() {
	if (process.env.WP_ADMIN_USER && process.env.WP_ADMIN_PASS) {
		return {
			user: process.env.WP_ADMIN_USER,
			pass: process.env.WP_ADMIN_PASS,
		};
	}

	try {
		const file = path.join(__dirname, '..', 'tools', '.e2e-credentials.json');
		const parsed = JSON.parse(fs.readFileSync(file, 'utf8'));
		if (parsed && parsed.user && parsed.pass) {
			return { user: parsed.user, pass: parsed.pass };
		}
	} catch (error) {
		// Fixture has not run yet, or the file is unreadable. Fall through.
	}

	return null;
}

/**
 * Whether admin credentials are available at all.
 *
 * @return {boolean} True when credentials resolve.
 */
function hasAdminCredentials() {
	return adminCredentials() !== null;
}

/**
 * Log in to wp-admin.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<boolean>} Whether login succeeded.
 */
async function loginAsAdmin(page) {
	if (!hasAdminCredentials()) {
		return false;
	}

	const { user, pass } = adminCredentials();

	await page.goto('/wp-login.php');
	await page.fill('#user_login', user);
	await page.fill('#user_pass', pass);
	await page.click('#wp-submit');
	await page.waitForURL(/wp-admin/);

	return true;
}

module.exports = { adminCredentials, hasAdminCredentials, loginAsAdmin };
```

- [ ] **Step 2: Napisz `e2e/panel.spec.js`**

```js
/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');

/**
 * Internal dependencies
 */
const { hasAdminCredentials, loginAsAdmin } = require('./admin-auth');

const PANEL = '/wp-admin/admin.php?page=isudev-library';

test.describe('IsuDev Library admin panel', () => {
	test.skip(
		!hasAdminCredentials(),
		'No admin credentials: run the dev fixture, or set WP_ADMIN_USER and WP_ADMIN_PASS.'
	);

	test.beforeEach(async ({ page }) => {
		await loginAsAdmin(page);
	});

	test('lists site-header with an unlocked toggle', async ({ page }) => {
		await page.goto(PANEL);

		await expect(page.getByText('isudev/site-header')).toBeVisible();

		const toggle = page.getByRole('checkbox', { name: /Enabled|Disabled/ });
		await expect(toggle).toBeEnabled();
	});

	// Task 11's fixture writes block markup straight into post_content, so it
	// proves the server render but never touches the editor. This is the only
	// test that proves the block is actually registered and discoverable in the
	// inserter, and that edit.js loads in the editor canvas without throwing.
	// Task 13's Step 9 — disabling a block and watching it leave the inserter —
	// was never carried out, so this is the only proof that a toggle actually
	// deregisters the block rather than just flipping a database row.
	test('toggling a block off removes it from the inserter, and back on restores it', async ({
		page,
	}) => {
		const inserterHasSiteHeader = async () => {
			await page.goto('/wp-admin/post-new.php?post_type=page');
			await page
				.getByRole('button', { name: /Close|Zamknij/ })
				.click()
				.catch(() => {});
			await page
				.getByRole('button', {
					name: /Block Inserter|Toggle block inserter/,
				})
				.click();
			await page.getByRole('searchbox', { name: /Search/ }).fill('Site Header');
			return page
				.getByRole('option', { name: /Site Header/ })
				.isVisible()
				.catch(() => false);
		};

		const setEnabled = async (enabled) => {
			await page.goto(PANEL);
			const toggle = page.getByRole('checkbox', {
				name: /Enabled|Disabled/,
			});
			if (enabled) {
				await toggle.check();
			} else {
				await toggle.uncheck();
			}
			await page.waitForResponse(
				(response) =>
					response.url().includes('/isudev-library/v1/blocks/') &&
					response.request().method() === 'POST'
			);
		};

		expect(await inserterHasSiteHeader()).toBe(true);

		await setEnabled(false);
		expect(await inserterHasSiteHeader()).toBe(false);

		await setEnabled(true);
		expect(await inserterHasSiteHeader()).toBe(true);
	});

	test('site-header is discoverable in the block inserter', async ({ page }) => {
		const errors = [];
		page.on('pageerror', (error) => errors.push(error.message));

		await page.goto('/wp-admin/post-new.php?post_type=page');
		await page.getByRole('button', { name: /Close|Zamknij/ }).click().catch(() => {});

		await page
			.getByRole('button', { name: /Block Inserter|Toggle block inserter/ })
			.click();
		await page
			.getByRole('searchbox', { name: /Search/ })
			.fill('Site Header');

		await expect(
			page.getByRole('option', { name: /Site Header/ })
		).toBeVisible();

		expect(errors).toEqual([]);
	});

	test('shows both tabs and diagnostics', async ({ page }) => {
		await page.goto(PANEL);

		await expect(page.getByRole('tab', { name: 'Blocks' })).toBeVisible();
		await page.getByRole('tab', { name: 'Settings' }).click();

		await expect(page.getByText('Blocks discovered:')).toBeVisible();
		await expect(page.getByText('Load base --isudev-* tokens')).toBeVisible();
	});

	test('toggling off removes the block from the inserter, toggling on restores it', async ({
		page,
	}) => {
		await page.goto(PANEL);

		const toggle = page.getByRole('checkbox', { name: /Enabled|Disabled/ });
		await expect(toggle).toBeChecked();

		await toggle.uncheck();
		await page.reload();
		await expect(
			page.getByRole('checkbox', { name: /Enabled|Disabled/ })
		).not.toBeChecked();

		// The REST list is the registration source of truth.
		let payload = await page.evaluate(() =>
			window.wp.apiFetch({ path: '/isudev-library/v1/blocks' })
		);
		expect(payload.blocks[0].enabled).toBe(false);
		expect(payload.blocks[0].source).toBe('panel');
		expect(payload.diagnostics.registered).toBe(0);

		await page.getByRole('checkbox', { name: /Enabled|Disabled/ }).check();
		await page.reload();

		payload = await page.evaluate(() =>
			window.wp.apiFetch({ path: '/isudev-library/v1/blocks' })
		);
		expect(payload.blocks[0].enabled).toBe(true);
		expect(payload.diagnostics.registered).toBe(1);
	});
});

test('REST blocks endpoint refuses anonymous requests', async ({ request }) => {
	const response = await request.get('/wp-json/isudev-library/v1/blocks');
	expect(response.status()).toBe(401);
});
```

- [ ] **Step 3: Napisz mu-plugin do testu bramki**

```php
<?php
/**
 * Plugin Name: IsuDev Library — force show_admin false
 * Description: Dev-only. Drop into wp-content/mu-plugins/ to verify that hiding
 *              the panel also closes the REST endpoints. Delete afterwards.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

add_filter( 'isudev_library/settings/show_admin', '__return_false' );
```

- [ ] **Step 4: Uruchom pełny e2e bez mu-plugina**

```bash
nvm use
WP_ADMIN_USER="<twój login admina>" WP_ADMIN_PASS="<twoje hasło>" npm run test:e2e
```

Oczekiwane: wszystko zielone — suite headera z Task 11 plus cztery testy panelu z tego zadania. Jeśli zmienne nie są ustawione, testy panelu są pominięte (skipped), a `REST refuses anonymous` i tak przechodzi.

Nie wpisuj danych logowania do żadnego pliku w repo.

- [ ] **Step 5: Ręcznie zweryfikuj bramkę `show_admin`**

```bash
cp tools/mu-plugins/isudev-library-show-admin-false.php \
   "/Users/lukaszbiedron/Local Sites/isudev-library/app/public/wp-content/mu-plugins/"
```

Jako zalogowany administrator sprawdź:
- `http://isudev-library.local/wp-admin/` — pozycja „IsuDev Library" **nie** występuje w menu
- `http://isudev-library.local/wp-json/isudev-library/v1/blocks` — odpowiedź `403` z kodem `rest_forbidden`

Potem usuń plik:

```bash
rm "/Users/lukaszbiedron/Local Sites/isudev-library/app/public/wp-content/mu-plugins/isudev-library-show-admin-false.php"
```

Potwierdź, że menu wróciło.

- [ ] **Step 6: Commit**

```bash
git add e2e/admin-auth.js e2e/panel.spec.js tools/mu-plugins/
git commit -m "$(cat <<'EOF'
test: cover the admin panel round-trip and the visibility gate

Panel specs skip cleanly without WP_ADMIN_USER/WP_ADMIN_PASS; credentials are
never committed. Adds a dev-only mu-plugin for verifying show_admin => false.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 15: i18n, dokumentacja, przegląd kryteriów akceptacji

**Files:**
- Create: `languages/.gitkeep`
- Create: `README.md`, `CHANGELOG.md`, `AGENTS.md`, `CLAUDE.md`
- Modify: `docs/superpowers/plans/2026-07-29-isudev-library-v1.md` (odhacz kroki)

**Interfaces:**
- Consumes: wszystko powyżej.
- Produces: `languages/isudev-library.pot`.

- [ ] **Step 1: Wygeneruj plik `.pot`**

```bash
mkdir -p languages && touch languages/.gitkeep
nvm use
npm run i18n:make-pot
```

wp-cli wypisuje ostrzeżenie o deprecacji na PHP 8.4 — to szum, nie błąd. `make-pot` nie potrzebuje bazy danych.

Oczekiwane: powstaje `languages/isudev-library.pot`. Sprawdź, że zawiera stringi z panelu i bloku:

```bash
grep -c "msgid" languages/isudev-library.pot
grep -n "IsuDev Library" languages/isudev-library.pot | head -3
```

Oczekiwane: liczba `msgid` > 20, nazwa „IsuDev Library" obecna.

- [ ] **Step 2: Napisz `README.md`**

```markdown
# IsuDev Library

Reusable, server-rendered Gutenberg blocks and tools. One place instead of
copying blocks from project to project.

- **Self-contained.** No runtime dependency on a theme, on `t2`, or on any other
  plugin. `build/` ships with the repo, so no build step is needed to install it.
- **Always server-rendered.** Every block renders in PHP.
- **Toggleable.** Enable or disable blocks from the admin panel, or pin them in
  code via `isudev.json`.

## Requirements

WordPress 6.7+, PHP 7.4+. Node 22.22.2 for development only.

## Blocks

| Block | Description |
| --- | --- |
| `isudev/site-header` | Accessibility-first site header: logo, disclosure navigation, mobile drawer, actions slot. |

## Configuration

Everything works on sensible defaults with no configuration. To override
per-project, add an optional `isudev.json` to your theme (child overrides
parent). See `isudev.json.example`.

This plugin reads **only** the `library` key and never writes to the file, so the
same `isudev.json` is safe to share with other `isudev-*` plugins.

```json
{
  "library": {
    "isudev/site-header": { "enabled": true, "sticky": false }
  }
}
```

`enabled` set in `isudev.json` wins over the admin panel and locks the toggle.

### Variations

Register extra variations of a block from `isudev.json`. Each gets a
`_namespace` attribute, so `render.php` can vary markup and classes per
variation. See `isudev.json.example`.

## Filters

| Filter | Purpose |
| --- | --- |
| `isudev_library/settings/show_admin` | Whether the current user sees the panel. Also gates the REST endpoints. |
| `isudev_library/settings/capability` | Capability for the menu and REST. Default `manage_options`. |
| `isudev_library/config` | The `library` subtree after extraction. |
| `isudev_library/config/raw` | The whole decoded `isudev.json`. |
| `isudev_library/config/inherit_from_parent` | Whether to inherit from the parent theme. Default `true`. |
| `isudev_library/icons` | The inline SVG icon registry. |
| `isudev_library/icon` | Rendered icon markup. |
| `isudev_library/site_header/regions` | Header regions before assembly. |
| `isudev_library/site_header/output` | Final header markup. |
| `isudev_library/site_header/menu_args` | `wp_nav_menu` args for the header. |

Code-only mode — no panel, configuration lives entirely in `isudev.json`:

```php
add_filter( 'isudev_library/settings/show_admin', '__return_false' );
```

## Development

```bash
nvm use
npm install && composer install
npm run build
npm run test:php
npm run lint:js && npm run lint:css && composer run lint:php
WP_ADMIN_USER=... WP_ADMIN_PASS=... npm run test:e2e
```

Never run `npm start` in automated work — use the one-shot `npm run build`.

## Adding a block

1. Create `src/blocks/<slug>/` with `block.json` (`apiVersion: 3`,
   `"render": "file:./render.php"`), `index.js`, `render.php`.
2. Add `block.php` returning a descriptor — see
   `src/blocks/site-header/block.php`.
3. `npm run build`. The loader picks it up; the panel lists it automatically.

Block directory names must be globally unique: the generated
`build/blocks-manifest.php` is keyed by directory basename.

## License

GPL-2.0-or-later.
```

- [ ] **Step 3: Napisz `CHANGELOG.md`**

```markdown
# Changelog

## 1.0.0 — 2026-07-29

First release.

### Added

- Block registry with descriptor-based discovery and a single registration point.
- Enabled-state resolution: unmet dependencies, `always_on`, `isudev.json`,
  admin panel option, then enabled by default.
- Optional `isudev.json` theme config, reading only the `library` key. Child
  theme overrides parent.
- Block variations registered in PHP, with an injected `_namespace` attribute.
- Shared inline SVG icon registry.
- React admin panel: Blocks and Settings tabs, REST endpoints under
  `isudev-library/v1`, ACF-style `show_admin` / `capability` filters that gate
  the menu and the endpoints together.
- Block `isudev/site-header`, migrated from the standalone `isudev-header`
  plugin.

### Breaking changes from `isudev-header`

`isudev-header` is superseded by this plugin. There is no compatibility layer.

- Block renamed: `idl/site-header` → `isudev/site-header`.
- Text domain: `idl-site-header` → `isudev-library`.
- CSS custom properties: `--idl-*` → `--isudev-*`.
- CSS classes: `.idl-header` → `.isudev-header`, `.idl-nav` → `.isudev-nav`,
  `idl-scroll-locked` → `isudev-scroll-locked`.
- Filters: `idl_site_header/icon` → `isudev_library/icon`;
  `idl_site_header/regions` → `isudev_library/site_header/regions`;
  `idl_site_header/output` → `isudev_library/site_header/output`;
  `idl_site_header/menu_args` → `isudev_library/site_header/menu_args`.

Content containing `idl/site-header` will not render. Re-insert the block, or
rewrite `post_content` before upgrading.
```

- [ ] **Step 4: Napisz `AGENTS.md` i `CLAUDE.md`**

`AGENTS.md`:

```markdown
# AGENTS.md — IsuDev Library

Source of truth for agents working in this repo.

## What this is

A self-contained WordPress plugin: a library of reusable, always server-rendered
Gutenberg blocks plus shared PHP utils, with a React admin panel for enabling and
disabling blocks. No runtime dependency on a theme, on `t2`, or on any other
plugin.

## Golden rules

- **Blocks always render in PHP.** Leaf blocks `save: () => null`; containers
  save `<InnerBlocks.Content />` and receive it as `$content`.
- **`Loader` is the only caller of `register_block_type()`.** A `block.php`
  returns a descriptor and registers nothing.
- **Pure functions stay pure.** Anything above a "WordPress adapters" marker
  must not call WordPress — `tools/check.php` requires those files without WP.
- **No top-level hook registration in `includes/`.** Use `boot()`.
- **`isudev.json`: read the `library` key only, never write the file.** It is
  shared with other `isudev-*` plugins.
- **Accessibility is non-negotiable.** The site-header markup contract (disclosure
  nav, drawer, link-vs-button rule, state on `.isudev-header`,
  `isudev-scroll-locked` on `<html>`) must keep the Playwright + axe suite green.
- **`apiVersion: 3` everywhere.** In editor code never use global
  `document`/`window` — use `element.ownerDocument` via `useRefEffect`.
  `view.js` is frontend and may use globals.
- **WordPress Coding Standards.** `class-*.php`, `strict_types`, `ABSPATH` guard.
- **`build/` is committed.** Do not gitignore it.
- Block directory names must be globally unique — `blocks-manifest.php` is keyed
  by directory basename.

## Layout

- `isudev-library.php` — bootstrap: constants, textdomain, requires, `boot()`.
- `includes/` — registry, loader, config reader, variations, REST, admin, utils.
- `src/blocks/<slug>/` — one block: `block.json`, `block.php` (descriptor),
  editor JS, `view.js`, styles, `render.php`, `inc/` for block-only PHP.
- `src/admin/` — React panel.
- `tools/check.php` + `tools/checks/` — plain-PHP checks for pure functions.
- `e2e/` — Playwright + axe.

## Build & test

```bash
nvm use
npm run build
npm run test:php
npm run lint:js && npm run lint:css && composer run lint:php
WP_ADMIN_USER=... WP_ADMIN_PASS=... npm run test:e2e
```

Never run `npm run build` in a watch loop during automated work — run the
one-shot `build` and move on.

Note: wp-cli cannot reach this Local site's database. Do not write verification
steps that rely on `wp eval`, `wp option` or `wp plugin`. Use `tools/check.php`
and Playwright over HTTP against `http://isudev-library.local/`.

## Docs

- Design spec: `docs/superpowers/specs/2026-07-29-isudev-library-design.md`
- Implementation plan: `docs/superpowers/plans/2026-07-29-isudev-library-v1.md`
```

`CLAUDE.md`:

```markdown
use `./AGENTS.md` as the source of truth for agents working in this repo.
```

- [ ] **Step 5: Przejdź kryteria akceptacji spec §15 jedno po drugim**

Sprawdź każde i zapisz wynik. Wszystkie muszą być spełnione.

```bash
# 9. Lint i checki.
nvm use
npm run lint:js && npm run lint:css && composer run lint:php && npm run test:php

# 10. Manifest istnieje i jest kluczowany nazwą katalogu.
test -f build/blocks-manifest.php && grep -q "'site-header'" build/blocks-manifest.php && echo "10 OK"

# 1. Brak notice'ów PHP na froncie.
# grep -c wypisuje 0 i zwraca 1 przy braku trafień, więc bramkuj przez -q.
curl -s http://isudev-library.local/ | grep -qiE "(warning|fatal error|notice):" && echo "1 FAIL" || echo "1 OK"

# 3. Assety nie ładują się bez bloku (strona główna nie zawiera bloku).
curl -s http://isudev-library.local/ | grep -q "site-header" && echo "3 FAIL" || echo "3 OK"

# 4, 5. Suite e2e.
WP_ADMIN_USER="<login>" WP_ADMIN_PASS="<hasło>" npm run test:e2e
```

Kryteria 2, 6, 7, 8 wymagają weryfikacji ręcznej:

- **2** — blok renderuje się w pełni z PHP: potwierdź, że `src/blocks/site-header/save.js` zwraca `<InnerBlocks.Content />` (to blok kontenerowy ze slotem „end", więc taki `save` jest poprawny wg spec §10), a cały pozostały markup pochodzi z `render.php`.
- **6** — dodaj do `wp-content/themes/twentytwentyfive/isudev.json` treść `{"library":{"isudev/site-header":{"enabled":false}}}`. Potwierdź: blok znika z insertera, w panelu toggle jest zablokowany z kłódką i tekstem „Managed in isudev.json", a `GET /blocks` zwraca `source: "code"`, `locked: true` — nawet jeśli wcześniej włączyłeś blok z panelu. Potem **usuń ten plik**.
- **7** — dodaj do tego samego pliku wariację z `isudev.json.example`. Potwierdź, że „Compact header" jest w inserterze z ikoną `menu`, a po wstawieniu `render.php` widzi `_namespace`. Potem **usuń plik**.
- **8** — już zweryfikowane w Task 14 Step 5.

- [ ] **Step 6: Uruchom Plugin Check**

Zainstaluj i aktywuj plugin „Plugin Check" z `http://isudev-library.local/wp-admin/plugin-install.php`, potem przeskanuj `isudev-library` w `Narzędzia → Plugin Check`.

Oczekiwane: brak błędów o `apiVersion` poniżej 3. Ostrzeżenia o braku `readme.txt` i nagłówków wymaganych w wordpress.org są dopuszczalne — plugin nie jest dystrybuowany przez katalog.

- [ ] **Step 7: Odhacz wykonane kroki w tym planie**

Zamień `- [ ]` na `- [x]` dla wszystkich ukończonych kroków w
`docs/superpowers/plans/2026-07-29-isudev-library-v1.md`.

- [ ] **Step 8: Commit**

```bash
git add languages/ README.md CHANGELOG.md AGENTS.md CLAUDE.md docs/
git commit -m "$(cat <<'EOF'
docs: add README, changelog, agent instructions and translation template

Documents the isudev.json contract, every public filter, and the breaking
changes from isudev-header.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 16: Wygaszenie `isudev-header`

Ostatni krok migracji z spec §13. Wykonaj **tylko** gdy Task 15 przeszedł w całości.

**Files:**
- Modify: `/Users/lukaszbiedron/Other Projects/isudev-header/README.md`
- Delete: `wp-content/plugins/isudev-header` (symlink)

- [ ] **Step 1: Potwierdź, że nowy blok działa**

```bash
cd "/Users/lukaszbiedron/Local Sites/isudev-library/app/public/wp-content/plugins/isudev-library"
nvm use
npm run test:php && WP_ADMIN_USER="<login>" WP_ADMIN_PASS="<hasło>" npm run test:e2e
```

Oczekiwane: wszystko zielone. Jeśli nie — zatrzymaj się, nie usuwaj starego pluginu.

- [ ] **Step 2: Dezaktywuj `isudev-header`**

W `http://isudev-library.local/wp-admin/plugins.php` dezaktywuj „Site Header Block". Potwierdź, że strona z blokiem `isudev/site-header` nadal renderuje się poprawnie:

```bash
curl -s -o /dev/null -w "%{http_code}\n" "<URL_STRONY_Z_BLOKIEM>"
curl -s "<URL_STRONY_Z_BLOKIEM>" | grep -c "isudev-header" && echo "still rendering"
```

Oczekiwane: `200` i `still rendering`.

- [ ] **Step 3: Usuń symlink**

Sprawdź, że to naprawdę symlink, potem usuń:

```bash
ls -l "/Users/lukaszbiedron/Local Sites/isudev-library/app/public/wp-content/plugins/isudev-header"
rm "/Users/lukaszbiedron/Local Sites/isudev-library/app/public/wp-content/plugins/isudev-header"
```

Nie usuwaj katalogu `~/Other Projects/isudev-header` — to osobne repo z historią, zostaje zarchiwizowane, nie skasowane.

- [ ] **Step 4: Oznacz stare repo jako przeniesione**

Dopisz na początku `/Users/lukaszbiedron/Other Projects/isudev-header/README.md`:

```markdown
> **Archived.** Superseded by [isudev-library](https://github.com/isudev/isudev-library),
> where this block lives as `isudev/site-header`. See that repo's `CHANGELOG.md`
> for the rename map. No further development happens here.
```

- [ ] **Step 5: Commit w obu repo**

```bash
cd "/Users/lukaszbiedron/Other Projects/isudev-header"
git add README.md
git commit -m "$(cat <<'EOF'
docs: mark repo as superseded by isudev-library

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
)"
```

---

## Self-Review

**Spec coverage:**

| Sekcja spec | Zadanie |
| --- | --- |
| §3 Nazewnictwo | Global Constraints, Task 1 |
| §4 Struktura katalogów | Task 1, File Structure |
| §5 Build (entry, manifest, warunkowe assety, PHP z `src/`) | Task 1 Step 8, Task 10 Steps 6–8, Task 6 Step 2 |
| §6 Registry i deskryptory | Task 5, Task 6, Task 9 Step 6 |
| §7 Precedencja | Task 5 (pełna tabela jako checki) |
| §8 `isudev.json` | Task 3, Task 4 |
| §9 Wariacje w PHP | Task 7, Task 15 Step 5 (kryterium 7) |
| §10 Kontrakt SSR | Task 9, Task 10, Task 15 Step 5 (kryterium 2) |
| §11 Warstwa wspólna (`icon.php`, `array.php`) | Task 2, Task 8 |
| §11 `utils/attributes.php` | **Świadomie pominięte** — `site-header` nie ma atrybutów responsywnych, więc plik nie miałby konsumenta. Wejdzie razem z `bento-grid` (spec §17). Odnotowane jako odstępstwo. |
| §12 Panel, REST, filtry widoczności | Task 12, 13, 14 |
| §13 Migracja `site-header` | Task 9, 10, 11, 16 |
| §14 Tooling, lint, testy | Task 1, Task 11, Task 14, Task 15 Step 6 |
| §15 Kryteria akceptacji | Task 15 Step 5 |

**Placeholdery:** `PASTE_*` w Task 8 Step 3 są zamierzone i egzekwowane krokiem weryfikacyjnym (`grep -c "PASTE_"`) — dane ścieżek SVG mają setki znaków i muszą być skopiowane dosłownie z pliku źródłowego, a nie przepisane. Poza tym brak `TBD`/`TODO`.

**Spójność typów:** `Registry::OPTION`, `Settings\OPTION`, `Admin\MENU_SLUG`, `Loader::block_build_path()`, `Config\get_config()`, `Config\config_sources()`, `Variations\attach()`, `Variations\build_variations()`, `Utils\array_get()`, `Utils\icon()`, `Utils\build_svg()`, `Utils\default_icon_paths()` — użyte w Taskach 6, 7, 12, 13 dokładnie tak, jak zdefiniowane w Taskach 2–5 i 8. Kształt `[ 'enabled', 'source', 'locked' ]` z `resolve_states()` jest konsumowany bez zmian przez `Registry::blocks()` i `REST\prepare_block()`.

**Rewizja po pre-flight scanie (2026-07-29):** pierwotny plan miał 17 zadań i rozdzielał REST od strony admina, co wymuszało tymczasową implementację `REST\permission_check()` w Task 12, podmienianą w Task 13. Zadania zostały scalone w Task 12 — plan ma 16 zadań. Poprawione przy tej okazji: `use const IsuDevLibrary\VERSION;` w `rest.php` (import stałej wymaga `use const`), usunięty nieużywany `use IsuDevLibrary\Admin;` z `settings.php`, usunięty martwy `reset_cache()` z Task 4, oraz bramki `grep -c … || echo OK` w kryteriach akceptacji zamienione na `grep -q … && echo FAIL || echo OK` (`grep -c` wypisuje `0` i zwraca `1` przy braku trafień, więc poprzednia forma dawała mylący wynik).
