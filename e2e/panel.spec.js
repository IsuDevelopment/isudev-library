/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');

/**
 * Internal dependencies
 */
const { hasAdminCredentials, loginAsAdmin } = require('./admin-auth');

const PANEL = '/wp-admin/admin.php?page=isudev-library';
const DESKTOP = 'desktop-chromium';

// Match on the option's block name rather than its visible text, so the
// round-trip proves *this* block's registration and not merely that something
// titled "Site Header" exists. Titles are not unique across plugins — the
// superseded `isudev-header` plugin used this exact title before it was
// removed, and any future block could collide again.
const SITE_HEADER_OPTION = '.editor-block-list-item-isudev-site-header';

/**
 * Open a fresh post in the block editor and get it into an interactive
 * state, dismissing any first-run dialog.
 *
 * WordPress 7.0 shows a "Choose a pattern" starter-patterns dialog for new
 * Pages that loads its options asynchronously and can pop up mid-test,
 * stealing focus from whatever the test just opened. A new Post has no
 * pattern picker, so use the Post editor for inserter checks — this suite
 * only needs *an* editor canvas, not specifically the Page one.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<void>}
 */
async function openPageEditor(page) {
	await page.goto('/wp-admin/post-new.php');

	const closeButton = page.getByRole('button', { name: /Close|Zamknij/ });
	if (await closeButton.isVisible({ timeout: 3000 }).catch(() => false)) {
		await closeButton.click();
	}
}

test.describe('IsuDev Library admin panel', () => {
	test.skip(
		!hasAdminCredentials(),
		'No admin credentials: run the dev fixture, or set WP_ADMIN_USER and WP_ADMIN_PASS.'
	);

	// Every test here logs in and several drive the full post editor, which is
	// heavier than the front-end pages the rest of the suite loads. Running
	// them one at a time avoids parallel logins/editor boots overwhelming the
	// local dev server, which otherwise manifests as wp-login timeouts that
	// have nothing to do with the panel itself. The admin panel and editor
	// inserter are not viewport-dependent, so (matching the desktop/mobile
	// split convention in header.spec.js) this suite runs once, desktop only.
	test.describe.configure({ mode: 'serial' });

	test.beforeEach(async ({ page }, testInfo) => {
		test.skip(testInfo.project.name !== DESKTOP, 'desktop only');
		await loginAsAdmin(page);
	});

	// Nothing else in this suite re-enables the block: if an assertion between
	// a toggle-off and its matching toggle-on throws — a flake, a timeout, or a
	// genuine regression — the run aborts with isudev/site-header disabled in
	// the real database. The describe runs serially, so that would poison this
	// file's own later tests, and it would leave the block disabled for
	// whatever runs after this suite. Restore it unconditionally after every
	// test, not just the round-trip, since any test could in principle be the
	// one that throws.
	test.afterEach(async ({ page }, testInfo) => {
		// Playwright still runs afterEach for a test that beforeEach skipped via
		// test.skip(), and this suite's beforeEach skips (without logging in) on
		// every project but desktop. Without this guard, the mobile project hits
		// an unauthenticated PANEL page, the checkbox locator never appears, and
		// the hook hangs to the 30s test timeout instead of a quick, harmless
		// no-op.
		if (testInfo.project.name !== DESKTOP) {
			return;
		}

		try {
			await page.goto(PANEL);

			const toggle = page.getByRole('checkbox', {
				name: /Enabled|Disabled/,
			});

			if (await toggle.isChecked()) {
				return;
			}

			await toggle.click();
			await page.waitForResponse(
				(response) =>
					response.url().includes('/isudev-library/v1/blocks/') &&
					response.request().method() === 'POST'
			);
		} catch (error) {
			// Panel gated, or the run is already failing. Leave the real error alone.
		}
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
			await openPageEditor(page);
			await page
				.getByRole('button', {
					name: /Block Inserter|Toggle block inserter/,
				})
				.click();
			await page
				.getByRole('searchbox', { name: /Search/ })
				.fill('Site Header');
			// The inserter search is debounced, so an instant isVisible() check
			// races it. Wait for the result to actually settle either way.
			return page
				.locator(SITE_HEADER_OPTION)
				.waitFor({ state: 'visible', timeout: 4000 })
				.then(() => true)
				.catch(() => false);
		};

		const setEnabled = async (enabled) => {
			await page.goto(PANEL);
			const toggle = page.getByRole('checkbox', {
				name: /Enabled|Disabled/,
			});
			// The panel is deliberately non-optimistic (Task 13: it POSTs, then
			// re-renders from the server response), so the checkbox's DOM state
			// only flips once that round trip resolves. check()/uncheck() assert
			// the flip happened immediately after the click, which races that
			// network round trip; click() plus an explicit wait for the POST
			// response does not.
			await toggle.click();
			await page.waitForResponse(
				(response) =>
					response.url().includes('/isudev-library/v1/blocks/') &&
					response.request().method() === 'POST'
			);
			await expect(toggle).toBeChecked({ checked: enabled });
		};

		expect(await inserterHasSiteHeader()).toBe(true);

		await setEnabled(false);
		expect(await inserterHasSiteHeader()).toBe(false);

		await setEnabled(true);
		expect(await inserterHasSiteHeader()).toBe(true);
	});

	test('site-header is discoverable in the block inserter', async ({
		page,
	}) => {
		const errors = [];
		page.on('pageerror', (error) => errors.push(error.message));

		await openPageEditor(page);

		await page
			.getByRole('button', {
				name: /Block Inserter|Toggle block inserter/,
			})
			.click();
		await page
			.getByRole('searchbox', { name: /Search/ })
			.fill('Site Header');

		await expect(page.locator(SITE_HEADER_OPTION)).toBeVisible();

		expect(errors).toEqual([]);
	});

	test('shows both tabs and diagnostics', async ({ page }) => {
		await page.goto(PANEL);

		await expect(page.getByRole('tab', { name: 'Blocks' })).toBeVisible();
		await page.getByRole('tab', { name: 'Settings' }).click();

		// Assert the reported numbers, not merely that the labels render: a
		// label being visible says nothing about the value beside it.
		const payload = await page.evaluate(() =>
			window.wp.apiFetch({ path: '/isudev-library/v1/blocks' })
		);
		await expect(
			page.getByText(
				`Blocks discovered: ${payload.diagnostics.discovered}`
			)
		).toBeVisible();
		await expect(
			page.getByText(
				`Blocks registered: ${payload.diagnostics.registered}`
			)
		).toBeVisible();
		await expect(page.getByText(payload.diagnostics.version)).toBeVisible();
	});

	test('toggling persists across a reload and updates diagnostics', async ({
		page,
	}) => {
		await page.goto(PANEL);

		const toggle = page.getByRole('checkbox', { name: /Enabled|Disabled/ });
		await expect(toggle).toBeChecked();

		// The panel POSTs and re-renders from the server response rather than
		// updating optimistically, so wait for that round trip (not just the
		// click) before reloading — otherwise the reload can race the write.
		await toggle.click();
		await page.waitForResponse(
			(response) =>
				response.url().includes('/isudev-library/v1/blocks/') &&
				response.request().method() === 'POST'
		);
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

		await page.getByRole('checkbox', { name: /Enabled|Disabled/ }).click();
		await page.waitForResponse(
			(response) =>
				response.url().includes('/isudev-library/v1/blocks/') &&
				response.request().method() === 'POST'
		);
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
