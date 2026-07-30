# E2E tests (Playwright)

End-to-end tests for the Site Header Block, run against a **Local by Flywheel**
dev site over HTTP (default `http://isudev-library.local/`).

## Prerequisites

- Node 22 (`.nvmrc` = 22.22.2 — `nvm use`).
- `npm install` and `npm run test:e2e:install` (Chromium) once.
- The Local site running, with the plugin linked + built:
  `npm run build && npm run env:link`. The dev mu-loader seeds the demo menu +
  front page on the first request (see `bin/dev-fixture.md`).

## Run

```bash
npm run test:e2e            # headless, desktop + mobile projects
npm run test:e2e:ui         # interactive UI mode
npm run test:e2e:report     # open the last HTML report
```

Override the target site:

```bash
PLAYWRIGHT_BASE_URL="http://another.local/" npm run test:e2e
```

## Projects

- `desktop-chromium` — 1280×900. Dropdown, keyboard, hover, focus-visible, scroll state.
- `mobile-chromium` — Pixel 7 (~412px, below the 62rem breakpoint). Burger, drawer,
  focus trap, scroll lock, accordion.

Each suite skips the viewport it doesn't apply to.
