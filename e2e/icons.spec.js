/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');

/**
 * Internal dependencies
 */
const { hasAdminCredentials, loginAsAdmin } = require('./admin-auth');

const DESKTOP = 'desktop-chromium';

/**
 * Open a fresh post in the block editor and dismiss any first-run dialog.
 *
 * A Post avoids the starter-pattern dialog shown for new Pages, which can
 * appear asynchronously and steal focus from the test.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<void>}
 */
async function openPostEditor(page) {
	await page.goto('/wp-admin/post-new.php');

	const closeButton = page.getByRole('button', { name: /Close|Zamknij/ });
	if (await closeButton.isVisible({ timeout: 3000 }).catch(() => false)) {
		await closeButton.click();
	}
}

test.describe('icon registry editor injection', () => {
	test.skip(
		!hasAdminCredentials(),
		'No admin credentials: run the dev fixture, or set WP_ADMIN_USER and WP_ADMIN_PASS.'
	);

	test.describe.configure({ mode: 'serial' });

	test.beforeEach(async ({ page }, testInfo) => {
		test.skip(testInfo.project.name !== DESKTOP, 'desktop only');

		// isudevIcons is shared across isudev-* plugins. This plugin appends to
		// it, but the optional isudev-test-blocks fixture still *assigns* its
		// demo collection, so the final value can be someone else's. Record
		// every assignment so the test proves our registry arrived regardless of
		// who wrote last.
		await page.addInitScript(() => {
			const assignments = [];
			let current;

			Object.defineProperty(window, '__isudevIconAssignments', {
				value: assignments,
			});
			Object.defineProperty(window, 'isudevIcons', {
				configurable: true,
				get: () => current,
				set: (value) => {
					current = value;
					assignments.push(value);
				},
			});
		});

		await loginAsAdmin(page);
	});

	test('localizes icon definitions before editor scripts run', async ({
		page,
	}) => {
		await openPostEditor(page);

		// Block editor scripts execute in the top frame; only the canvas DOM is
		// iframed, so the localized global belongs to the page itself.
		const icons = await page.evaluate(() =>
			window.__isudevIconAssignments.find(
				(entries) =>
					Array.isArray(entries) &&
					entries.some((entry) => entry.name === 'arrowForward')
			)
		);

		expect(Array.isArray(icons)).toBe(true);
		expect(icons.length).toBeGreaterThanOrEqual(4);

		const names = icons.map((entry) => entry.name);
		expect(names).toContain('arrowForward');

		const arrow = icons.find((entry) => entry.name === 'arrowForward');
		expect(arrow.label).toBeTruthy();
		expect(arrow.icon.startsWith('<svg')).toBe(true);

		// The registry must be appended, never assigned: another isudev-* plugin
		// publishing into the same global has to survive our script.
		const html = await page.content();
		expect(html).toContain(
			'window.isudevIcons = ( window.isudevIcons || [] ).concat('
		);
	});
});
