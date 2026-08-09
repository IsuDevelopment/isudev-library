/**
 * Playwright E2E config. Runs against the Local dev site by default.
 * Override with PLAYWRIGHT_BASE_URL.
 */
/**
 * External dependencies
 */
const { defineConfig, devices } = require('@playwright/test');

const baseURL =
	process.env.PLAYWRIGHT_BASE_URL || 'http://isudev-library.local/';

module.exports = defineConfig({
	testDir: './e2e',
	// The panel round-trip disables isudev/site-header for the duration of a
	// toggle, and the front-page fixture renders that block dynamically on
	// every request. If header.spec.js ran in a different worker at the same
	// moment, it would see the header render empty and fail for a reason that
	// has nothing to do with the header itself. Run everything in one worker,
	// strictly in file order, so no other spec can observe that window.
	fullyParallel: false,
	workers: 1,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI
		? [['line']]
		: [['list'], ['html', { open: 'never' }]],
	use: {
		baseURL,
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'desktop-chromium',
			use: {
				...devices['Desktop Chrome'],
				viewport: { width: 1280, height: 900 },
			},
		},
		{
			name: 'mobile-chromium',
			use: { ...devices['Pixel 7'] }, // ~412px wide → below the 62rem breakpoint.
		},
	],
});
