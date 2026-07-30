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
	fullyParallel: true,
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
