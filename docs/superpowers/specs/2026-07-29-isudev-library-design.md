# isudev-library — design

Data: 2026-07-29
Status: zaakceptowany do planowania

## 1. Cel

Jedno miejsce na bloki Gutenberga i narzędzia PHP, które dziś są kopiowane
z projektu do projektu. Plugin jest **self-contained**: własne repo, `build/`
commitowany, instalowalny jako zwykły folder lub zip, zero zależności runtime
od theme'a, od `t2` i od jakiegokolwiek innego pluginu.

Konfiguracja per-projekt odbywa się przez opcjonalny plik `isudev.json`
w theme'ie oraz przez panel w adminie. Bez żadnego z tych dwóch plugin działa
na sensownych defaultach zaraz po aktywacji.

## 2. Zakres

### v1 (ten spec)

- Fundament: bootstrap, `Registry`, `Loader`, czytnik konfiguracji, warstwa
  wariacji, wspólne utils PHP.
- Migracja bloku `site-header` z `~/Other Projects/isudev-header` jako
  `isudev/site-header`.
- Panel React w adminie: top-level menu, zakładki **Blocks** i **Settings**,
  REST, filtry widoczności.
- Tooling: build, lint (JS/CSS/PHP), e2e Playwright + axe.
- Inicjalizacja repo git w katalogu pluginu (`.gitignore`, `.distignore`,
  `AGENTS.md`, `CLAUDE.md`, `README.md`, `CHANGELOG.md`).

### Poza zakresem v1

- **`bento-grid` / `bento-card`** — źródło w `kormas-isu` ma alias webpacka
  `@helpers` do `themes/block-theme/src/helpers` i externals `@t2/*`. To wymaga
  przepisania, nie skopiowania. Kontrakt SSR dla bloków kontenerowych
  (sekcja 10) jest zaprojektowany tak, żeby bento weszło bez zmian
  w fundamencie.
- **Własna biblioteka komponentów React** — to osobny projekt, ciągnięty z NPM.
  Tutaj wyłącznie `@wordpress/*`.
- **Interfejs do edycji `isudev.json` z admina** — świadomie odłożone.
- Icon picker jako pełny komponent — v1 dostaje tylko rejestr ikon w PHP
  (`utils/icon.php`), który będzie jego backendem.

## 3. Nazewnictwo

| Co | Wartość |
| --- | --- |
| Folder / slug pluginu | `isudev-library` |
| Plik główny | `isudev-library.php` |
| PHP namespace | `IsuDevLibrary\` |
| Nazwy bloków | `isudev/<slug>` (np. `isudev/site-header`) |
| Text domain | `isudev-library` (jeden `.pot` na cały plugin) |
| JS global | `window.isudevLibrary` |
| REST namespace | `isudev-library/v1` |
| Opcja DB (toggle) | `isudev_library_blocks` |
| Opcja DB (globalne) | `isudev_library_settings` |
| Prefix filtrów/akcji | `isudev_library/…` |
| CSS custom properties | `--isudev-*` |
| Plik konfiguracyjny theme'a | `isudev.json`, klucz `library` |

PHP namespace `IsuDevLibrary\` jest kontynuacją tego, co już jest
w `isudev-header` (`IsuDevLibrary\SiteHeader`) — nie wprowadzamy nowej
konwencji.

## 4. Struktura katalogów

```
isudev-library/
├── isudev-library.php            # bootstrap: nagłówek, stałe, textdomain, require, boot()
├── includes/
│   ├── class-registry.php        # discovery deskryptorów, stan enabled, requires/dependents
│   ├── class-loader.php          # JEDYNE miejsce wywołujące register_block_type()
│   ├── config.php                # czytnik isudev.json + get_block_config()
│   ├── variations.php            # helper na filtr get_block_type_variations
│   ├── rest.php                  # kontroler isudev-library/v1
│   ├── settings.php              # register_setting() dla opcji globalnych
│   ├── admin.php                 # menu page, enqueue panelu, filtry widoczności
│   └── utils/
│       ├── icon.php              # rejestr ikon: inline SVG + filtr
│       ├── attributes.php        # wrapper attrs, style vars, sanitizery
│       └── array.php             # bezpieczne pobieranie z zagnieżdżonych tablic
├── src/
│   ├── blocks/
│   │   └── site-header/
│   │       ├── block.json        # apiVersion 3, "render": "file:./render.php"
│   │       ├── block.php         # DESKRYPTOR — zwraca tablicę, nic nie rejestruje
│   │       ├── index.js  edit.js  view.js  variations.js
│   │       ├── style.scss  editor.scss
│   │       ├── render.php        # szablon SSR
│   │       └── inc/
│   │           ├── class-nav-walker.php
│   │           └── render-helpers.php
│   ├── admin/
│   │   ├── index.js              # createRoot
│   │   ├── app.js                # TabPanel: Blocks / Settings
│   │   ├── components/           # BlockCard, BlockList, SettingsPanel, Diagnostics
│   │   └── admin.scss            # NOT style.scss — wp-scripts would emit style-index.css
│   └── shared/                   # wspólne hooki JS (bez komponentów wizualnych)
├── build/                        # COMMITOWANY, + blocks-manifest.php
├── languages/
├── e2e/                          # Playwright + axe
├── docs/
├── webpack.config.js             # default config + entry `admin`
├── package.json  composer.json  phpcs.xml.dist  playwright.config.js
├── isudev.json.example           # dokumentacja formatu configu theme'a
├── .gitignore  .distignore
└── AGENTS.md  CLAUDE.md  README.md
```

`docs/` i `e2e/` trafiają do `.distignore`, żeby nie puchły w dystrybucji.

## 5. Build

Zweryfikowane w `@wordpress/scripts@31.8.0` (wersja z `isudev-header`), nie
założone:

- `getWebpackEntryPoints()` globuje `**/block.json` **rekurencyjnie** pod
  ścieżką źródłową, więc `src/blocks/<slug>/block.json` jest wykrywany
  automatycznie, bez konfiguracji.
- Gdy znajdzie choć jeden `block.json`, **zwraca wyłącznie te entry** —
  `src/admin/index.js` nie zostanie zbudowany sam z siebie. To jedyny powód
  istnienia `webpack.config.js`: rozszerza domyślną konfigurację o
  `entry: { admin: … }`. Żadnych aliasów do theme'a, żadnych externals —
  inaczej niż w `bento-grid`.
- Klucz entry to ścieżka względna do katalogu źródłowego bez rozszerzenia,
  więc **`src/blocks/<slug>/` mapuje się na `build/blocks/<slug>/`**. `Loader`
  buduje na tym ścieżkę metadanych: `PATH . 'build/blocks/' . $slug`.
- `wp-scripts build --blocks-manifest` generuje `build/blocks-manifest.php`
  (klasa `BlocksManifestPlugin` w configu wp-scripts). Plik konsumujemy przez
  `wp_register_block_metadata_collection( PATH . 'build', PATH . 'build/blocks-manifest.php' )`
  (WP 6.7+): jeden odczyt PHP zamiast N × `block.json`. Ta funkcja **tylko
  cache'uje metadane, nie rejestruje bloków**, więc nie koliduje z bramką
  z sekcji 7. Wywołanie jest opakowane w `function_exists()` — przy braku
  manifestu lub na starszym WP rejestracja i tak działa, tylko bez cache'u.

Skrypty npm: `build`, `start`, `format`, `lint:js`, `lint:css`, `test:e2e`,
`i18n:make-pot`, `i18n:make-json`, `i18n:make-mo` — układ przeniesiony
z `isudev-header`.

### Warunkowe ładowanie assetów

Dostajemy je **darmo** z `block.json` (`style`, `editorStyle`, `viewScript`) —
WP rejestruje handle przy `register_block_type()` i enqueue'uje je dopiero przy
renderze bloku. Zero własnego kodu.

Style edytora idą przez `block.json` (`editorStyle`), nie przez
`enqueue_block_editor_assets` — ten hook nie dosięga canvasu w iframe'owanym
edytorze.

### PHP bloku ładowany z `src/`, nie z `build/`

`Registry` globuje `src/blocks/*/block.php`. PHP nie wymaga kompilacji, więc nie
przechodzi przez build: edycja działa od razu, bez rebuildu i bez watchera, i nie
ma zdublowanych, nieświeżych kopii w `build/`. Do `build/` trafia wyłącznie
`render.php` (kopiowany automatycznie, bo `block.json` go referuje) oraz JS/CSS.

To odejście od konwencji `glob( 'build/*/block.php' )` z `bento-grid`
i `blocks-library` — świadome, na korzyść DX.

## 6. Registry i deskryptory

`src/blocks/<slug>/block.php` **nie rejestruje niczego** i nie wiesza hooków.
Zwraca deskryptor:

```php
<?php
declare( strict_types = 1 );
namespace IsuDevLibrary\Blocks\SiteHeader;
defined( 'ABSPATH' ) || exit;

return [
	'slug'       => 'site-header',
	'name'       => 'isudev/site-header',
	'requires'   => [],                            // slugi, które muszą być włączone
	'always_on'  => false,
	'variations' => true,                           // podłącz filtr get_block_type_variations
	'render'     => null,                           // null => render.php z block.json
	'bootstrap'  => [                               // PHP wymagany tylko gdy blok włączony
		'inc/render-helpers.php',
		'inc/class-nav-walker.php',
	],
];
```

`Registry`:

- globuje deskryptory, waliduje wymagane klucze (`slug`, `name`), odrzuca
  niepoprawne z `_doing_it_wrong()`;
- buduje mapę `dependents` (odwrotność `requires`), żeby panel mógł pokazać
  „wyłączenie tego zabierze też X";
- rozstrzyga stan włączenia (sekcja 7);
- cache'uje wynik w pamięci na request.

`Loader` jest **jedynym** miejscem w kodzie wywołującym `register_block_type()`.
Dla każdego włączonego bloku: `require_once` plików z `bootstrap`, potem
`register_block_type( <build path>, $args )`. Pliki `bootstrap` wyłączonego
bloku nie są w ogóle wczytywane.

## 7. Rozstrzyganie stanu włączenia

W tej kolejności, pierwszy pasujący wygrywa:

| # | Warunek | Wynik | `source` w REST |
| --- | --- | --- | --- |
| 1 | `requires` niespełnione | wyłączony twardo, toggle zablokowany | `dependency` |
| 2 | `always_on === true` | włączony, bez toggle'a | `always_on` |
| 3 | `isudev.json` → `library.<name>.enabled` istnieje | ta wartość, toggle zablokowany | `code` |
| 4 | `isudev_library_blocks[<slug>]` istnieje | ta wartość | `panel` |
| 5 | — | włączony | `default` |

`isudev.json` bije panel, bo stan w repo jest deterministyczny przy deployu;
panel obsługuje wszystko, czego kod nie zadeklarował.

**Konsekwencja wyboru „nie rejestruj wcale"**, świadomie zaakceptowana:
wszystkie bloki liściowe mają `save: () => null`, więc wyłączenie bloku sprawia,
że **już wstawione instancje renderują się jako nic** — nie jako popsuty markup,
po prostu pusto. To cena determinizmu i zera JS/CSS. Panel komunikuje to wprost
w opisie toggle'a.

## 8. `isudev.json`

Opcjonalny plik w theme'ie, obok `theme.json`. Współdzielony z pozostałymi
pluginami `isudev-*`, dlatego obowiązuje ścisły kontrakt:

> **isudev-library czyta wyłącznie klucz `library`, ignoruje resztę pliku
> i nigdy do niego nie pisze.** Inne pluginy `isudev-*` mają własne klucze
> i własne czytniki. Żaden plugin nie zależy od żadnego innego.

```jsonc
{
  "library": {                                  // ← tylko to czyta isudev-library
    "isudev/site-header": {
      "enabled": true,
      "sticky": false,
      "ariaLabel": "Główna",
      "variations": {
        "compact": {
          "name": "compact",
          "title": "Compact header",
          "icon": "menu",
          "description": "Header bez slotu akcji.",
          "attributes": { "sticky": false }
        }
      }
    }
  },
  "google-reviews": { }                         // ← ignorowane
}
```

Zachowanie czytnika (`includes/config.php`), port logiki z
`dekode-library/library/library-json/plugin.php`:

- szuka `isudev.json` w `get_template_directory()` (parent), potem
  `get_stylesheet_directory()` (child); child nadpisuje parent — tablice
  asocjacyjne scalane rekurencyjnie, **listy podmieniane w całości**;
- dziedziczenie z parenta wyłączalne filtrem
  `isudev_library/config/inherit_from_parent`;
- błąd JSON-a → log + puste `[]`, nigdy fatal;
- wynik cache'owany statycznie na request;
- filtry: `isudev_library/config/raw` (cały zdekodowany plik) oraz
  `isudev_library/config` (podrzewo `library`).

**Drugie odejście od dekode — scalanie list.** dekode scala przez
`array_replace_recursive()`, które łączy listy indeks po indeksie. Przy parent
`["core/paragraph", "core/image", "core/button"]` i child `["core/paragraph"]`
wynikiem są wszystkie trzy wpisy, więc child theme **nie może ograniczyć** listy
— a to jest główny zadeklarowany powód istnienia tego pliku (patrz wyżej:
„ograniczyć feature, np. `allowedBlocks`"). Sprawdzone empirycznie. Dlatego
`merge_configs()` scala rekurencyjnie tablice asocjacyjne, a listy podmienia
w całości. Rozróżnienie robi `is_list_array()` (własne, bo `array_is_list()`
wymaga PHP 8.1, a minimum to 7.4).

**Odejście od dekode:** dekode używa prywatnego `_wp_array_get()`. Piszemy własny
`IsuDevLibrary\Utils\array_get( array $data, array $path, $fallback )`, żeby nie
opierać się na prywatnym API rdzenia.

API dla bloków:

```php
get_block_config( string $block_name, string|array $key, $fallback = [], string $namespace = '' )
```

Rozwiązuje wartość z uwzględnieniem wariacji: najpierw
`library.<name>.variations.<namespace>.<key>`, potem `library.<name>.<key>`,
na końcu `$fallback`.

Konfiguracja jest wystawiana do edytora jako `window.isudevLibrary.config`
przez `wp_add_inline_script` na własnym handle enqueue'owanym w
`enqueue_block_editor_assets`.

## 9. Wariacje rejestrowane w PHP

Port `extended_block_variations()` z dekode: filtr `get_block_type_variations`
(WP 6.5+), podłączany tylko dla bloków z `'variations' => true` w deskryptorze.

Dla każdej wariacji z `isudev.json`:

- wstrzykiwany `attributes._namespace = <klucz wariacji>`;
- domyślne `isActive: [ '_namespace' ]`, jeśli wariacja nie definiuje własnego.

Rejestracja w PHP, nie w JS, bo tylko wtedy wariacja jest widoczna również
w hookach PHP — można ją odfiltrować per post type, a `render.php` zna
`_namespace` i może dokleić klasę zależną od wariacji. To realizuje wymóg
„niezależna kopia bloku z inną konfiguracją, ikoną i klasą" bez duplikowania
kodu bloku.

## 10. Kontrakt SSR

Renderowanie **zawsze** w PHP. Dwa warianty, zależnie od tego, czy blok ma
InnerBlocks:

| Typ bloku | `save` | `render.php` |
| --- | --- | --- |
| liść (`site-header`) | `() => null` | pełny markup |
| kontener (przyszły `bento-grid`) | `() => <InnerBlocks.Content />` | wrapper + `$content` |

Kontener **musi** zapisywać `<InnerBlocks.Content />` — inaczej treść dzieci
przepada. `render.php` dostaje ją jako `$content` i owija własnym wrapperem.
W obu wariantach wrapper powstaje w PHP przez `get_block_wrapper_attributes()`,
więc wymóg „SSR w PHP" jest spełniony.

Reguły dla `render.php`:

- `defined( 'ABSPATH' ) || exit;`
- escaping na każdym wyjściu, `absint()`/`sanitize_*` na każdym atrybucie;
- zero `echo` z niezaufanych danych; SVG z rejestru ikon są statyczne i zaufane;
- `$attributes`, `$content`, `$block` to jedyne wejścia.

## 11. Warstwa wspólna

Tylko PHP plus `@wordpress/*`. Żadnych własnych komponentów React w v1.

- **`utils/icon.php`** — rejestr ikon: inline SVG, `fill="currentColor"`,
  `aria-hidden`, filtr `isudev_library/icon` do podmiany zestawu. Baza to
  `includes/icon.php` z `isudev-header`, rozszerzona o rejestrację ikon przez
  filtr, żeby stała się backendem przyszłego icon pickera.
- **`utils/attributes.php`** — budowanie `--isudev-*` custom properties
  z atrybutów responsywnych, składanie klas, sanityzacja wartości spacing/color.
- **`utils/array.php`** — `array_get()` (zamiennik `_wp_array_get`).

Katalog `src/shared/` trzyma wspólne hooki JS (np. `useIframeDocument()` oparty
na `useRefEffect` z `@wordpress/compose`). Zero komponentów wizualnych — te
przyjdą z osobnej paczki NPM.

## 12. Panel admina

### Widoczność — wzorzec ACF

```php
// Kto widzi panel. Default: current_user_can( <capability> ).
apply_filters( 'isudev_library/settings/show_admin', bool $show );

// Capability dla menu ORAZ permission_callback REST-a. Default: 'manage_options'.
apply_filters( 'isudev_library/settings/capability', string $cap );
```

**`show_admin === false` zamyka też endpointy REST-a**, nie tylko ukrywa menu.
Oba filtry są per-user i wykonują się w tym samym kontekście requestu co
`permission_callback`, więc jest to spójne. Bez tego „ukrycie" byłoby
kosmetyczne — panel dałoby się obsłużyć `apiFetch`-em z konsoli.

`add_filter( 'isudev_library/settings/show_admin', '__return_false' )` wprowadza
bibliotekę w tryb **code-only**: jedynym źródłem prawdy zostaje `isudev.json`
plus defaulty deskryptorów.

### Struktura

Top-level menu „IsuDev Library", `TabPanel` z dwiema zakładkami.

**Blocks** — lista kart. Każda karta: ikona z `block.json`, tytuł, opis, toggle
oraz wskaźnik pochodzenia stanu:

| `source` | UI |
| --- | --- |
| `default` | toggle aktywny |
| `panel` | toggle aktywny |
| `code` | 🔒 kłódka, toggle zablokowany, „zarządzane w `isudev.json`" |
| `always_on` | toggle zablokowany, „zawsze włączony" |
| `dependency` | ⛓ toggle zablokowany, „wymaga: `<slug>`" |

Wszystkie trzy stany zablokowane (`code`, `always_on`, `dependency`) renderują
**wyszarzony toggle**, nie brak kontrolki. Wyszarzony przełącznik pokazuje
aktualny stan bloku, którego sama etykieta nie pokazuje, i wszystkie trzy
przypadki wyglądają wtedy jednakowo.

Toggle bloku, który ma `dependents`, pokazuje potwierdzenie z listą bloków, które
zostaną wyłączone razem z nim. Opis toggle'a zawiera ostrzeżenie z sekcji 7.

**Settings** v1 — dwie realne rzeczy, żeby zakładka nie była pusta:

- toggle „Load base `--isudev-*` tokens" — czy plugin dorzuca własne defaulty
  CSS, czy dostarcza je theme;
- diagnostyka read-only: czy i gdzie znaleziono `isudev.json` (child / parent /
  brak), wersja pluginu, liczba wykrytych i zarejestrowanych bloków.

### REST

Dwa mechanizmy, każdy tam gdzie jest idiomatyczny:

- **`isudev-library/v1/blocks`** — własny kontroler.
  `GET` zwraca listę z polami wyliczanymi: `slug`, `name`, `title`,
  `description`, `icon`, `enabled`, `source`, `locked`, `requires`,
  `dependents`. `POST /blocks/<slug>` z ciałem `{ enabled: bool }` zapisuje
  toggle; odpowiada `403`, gdy `source` to `code`, `always_on` lub `dependency`.
  Wyliczane pola nie mieszczą się sensownie w `register_setting`.
- **opcje globalne** — `register_setting( 'isudev_library', 'isudev_library_settings', [ 'show_in_rest' => [ 'schema' => … ] ] )`,
  więc zakładka Settings jedzie na `useEntityProp( 'root', 'site', … )`
  z `@wordpress/core-data`. Zero własnego kodu do zapisu.

Nonce leci automatycznie przez `@wordpress/api-fetch`. `permission_callback`
w obu przypadkach sprawdza capability z filtra **oraz** `show_admin`.

### Zgodność z WP 7.x

- `createRoot` z `@wordpress/element` (React 19 wszedł w WP 7.0).
- Wyłącznie `@wordpress/components`; **nie** używamy `DimensionControl`
  (usunięty w 7.0) ani nie opieramy się na `__next40pxDefaultSize`
  (no-op w 7.1). Stabilizowane nazwy zamiast `__experimental*`.
- Bloki `apiVersion: 3` — wymóg iframe'owanego edytora, który w WP 7.1 nie ma
  już fallbacku.
- W kodzie edytora nigdy globalny `document`/`window` — `element.ownerDocument`
  i `element.ownerDocument.defaultView`, referencja przez `useRefEffect`.

## 13. Migracja `site-header`

Źródło: `~/Other Projects/isudev-header`.

Zmiany przy przeniesieniu:

- `idl/site-header` → `isudev/site-header`;
- text domain `idl-site-header` → `isudev-library`;
- filtr `idl_site_header/icon` → `isudev_library/icon`;
- `includes/icon.php` → `includes/utils/icon.php` (wspólny rejestr);
- `includes/class-nav-walker.php`, `includes/render-helpers.php` →
  `src/blocks/site-header/inc/`;
- rejestracja przenoszona z `idl-site-header.php` do `Loader` + deskryptor;
- prefiksy CSS `--idl-*` → `--isudev-*`; klasy `.idl-header` → `.isudev-header`,
  `idl-scroll-locked` → `isudev-scroll-locked`.

**Bez warstwy zgodności dla starej nazwy bloku.** `isudev-header` jest w 0.1.0;
alias byłby martwym kodem od pierwszego dnia. Breaking change udokumentowany
w `README.md` i `CHANGELOG.md`.

Kontrakt dostępności headera (disclosure nav, drawer, reguła link-vs-button,
stan na `.isudev-header`, `isudev-scroll-locked` na `<html>`) jest **zachowany
bez zmian** — suite Playwright + axe z `isudev-header` musi przejść po migracji.
To jest kryterium akceptacji, nie życzenie.

Po zakończeniu migracji: usunięcie symlinku `isudev-header` z
`wp-content/plugins/` i archiwizacja repo `~/Other Projects/isudev-header`.

## 14. Tooling, lint, testy

- **PHP**: `strict_types = 1`, `defined( 'ABSPATH' ) || exit;`, WPCS,
  konwencja nazw `class-*.php`. `composer run lint:php` / `lint:php:fix`
  (phpcs + phpcbf, `phpcs.xml.dist` przeniesiony z `isudev-header`).
  `composer.json` deklaruje `"php": ">=7.4"` (minimum WP 7.0).
- **JS/CSS**: `wp-scripts lint-js`, `wp-scripts lint-style`,
  `@wordpress/prettier-config`.
- **Testy e2e**: Playwright + `@axe-core/playwright`.
  - suite a11y headera przeniesiony 1:1 z `isudev-header`;
  - jeden nowy test round-trip panelu: wyłącz blok → znika z insertera → włącz →
    wraca;
  - jeden test bramki widoczności: `show_admin => false` → brak menu **i** `403`
    na `POST /blocks/<slug>`.
- **Plugin Check** przed wydaniem — wykrywa bloki poniżej `apiVersion 3`.
- Build jednorazowy (`npm run build`), nigdy watcher w automatyzacji.

## 15. Kryteria akceptacji v1

1. Aktywacja pluginu na czystym WP z theme'em bez `isudev.json` rejestruje
   `isudev/site-header` i nie generuje żadnego notice'a ani warninga.
2. `isudev/site-header` renderuje się w pełni z PHP; `save` zwraca `null`.
3. JS i CSS bloku nie ładują się na stronie, na której bloku nie ma.
4. Suite Playwright + axe przeniesiony z `isudev-header` przechodzi.
5. Panel listuje bloki z poprawnym `source` i działającym toggle'em; wyłączenie
   bloku usuwa go z insertera i z frontendu.
6. `isudev.json` z `library."isudev/site-header".enabled = false` wyłącza blok
   i blokuje toggle w panelu, niezależnie od zawartości opcji DB.
7. Wariacja zdefiniowana w `isudev.json` pojawia się w inserterze, ma własną
   ikonę i tytuł, a `render.php` widzi jej `_namespace`.
8. `add_filter( 'isudev_library/settings/show_admin', '__return_false' )` ukrywa
   menu i zwraca `403` na zapis przez REST.
9. `npm run lint:js`, `npm run lint:css`, `composer run lint:php` — zielone.
10. `npm run build` produkuje `build/blocks-manifest.php`, który jest wczytywany
    przez `wp_register_block_metadata_collection()`.

## 16. Decyzje odrzucone

| Odrzucone | Dlaczego |
| --- | --- |
| Wyłączony blok zarejestrowany z `inserter: false` | Ładowałby JS/CSS edytora zawsze. Wybrano determinizm i zero kosztu. |
| `library.json` w theme'ie (nazwa dekode) | Generyczna nazwa obok `theme.json`. Wybrano `isudev.json` wspólny dla `isudev-*`. |
| `library.json` shipowany w pluginie z defaultami | Defaulty należą do `block.json` i deskryptorów. Config theme'a jest wyłącznie opcjonalnym override'em. |
| Self-registration w `block.php` + guard | Brama rozproszona po N plikach, brak miejsca na `requires`, panel nie ma skąd wziąć metadanych. |
| Czysty scan `build/*/block.json` bez deskryptorów | Nie ma gdzie trzymać `requires`/`always_on` — potrzebne od pierwszej pary `bento-grid`/`bento-card`. |
| Kopiowanie PHP do `build/` (`--webpack-copy-php`) | Wymaga rebuildu przy każdej zmianie PHP i dubluje pliki. |
| TypeScript w v1 | Wymagałby przepisania kodu headera na wejściu. Do rozważenia, gdy liczba bloków wzrośnie. |
| Alias `idl/site-header` → `isudev/site-header` | Header jest w 0.1.0; alias byłby martwym kodem. |
| Panel jako podstrona Ustawień | Słabsze pod rozbudowę (icon picker, konfigurator `isudev.json`). |
| Trzeci poziom configu w `uploads/` | Wchodzi w rolę panelu i rozmywa źródło prawdy. |
| `_wp_array_get()` | Prywatne API rdzenia. Własny `array_get()`. |

## 17. Otwarte na v2+

- Przepisanie `bento-grid` / `bento-card` bez `t2` i bez aliasu `@helpers`.
- Icon picker jako komponent edytora, oparty na rejestrze z `utils/icon.php`.
- Interfejs do edycji `isudev.json` z admina.
- Wciągnięcie pozostałych bloków z `kormas-isu`: `isudev-read-more`,
  `isudev-google-reviews`, `flexible-hero`, `extended-selling-points`.
- Decyzja o dystrybucji: zip z GitHub Releases vs. composer z repozytorium vcs.
- PHPUnit na `Registry`, czytnik configu i rozwiązywanie wariacji.
- Migracja na TypeScript, gdy liczba bloków to uzasadni.
