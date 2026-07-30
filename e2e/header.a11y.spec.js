/**
 * Accessible header — automated accessibility scan (axe-core), scoped to header.
 */
/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

const HEADER = '.isudev-header';
const DESKTOP = 'desktop-chromium';
const MOBILE = 'mobile-chromium';

const scanHeader = (page) => new AxeBuilder({ page }).include(HEADER).analyze();

test.describe('header — axe', () => {
	test('no violations in the default (closed) state', async ({ page }) => {
		await page.goto('./');
		const { violations } = await scanHeader(page);
		expect(violations).toEqual([]);
	});

	test('no violations with a desktop dropdown open', async ({
		page,
	}, testInfo) => {
		test.skip(testInfo.project.name !== DESKTOP, 'desktop only');
		await page.goto('./');
		await page
			.locator('.isudev-nav__item.has-submenu > .isudev-nav__toggle')
			.first()
			.click();
		const { violations } = await scanHeader(page);
		expect(violations).toEqual([]);
	});

	test('no violations with the mobile drawer open', async ({
		page,
	}, testInfo) => {
		test.skip(testInfo.project.name !== MOBILE, 'mobile only');
		await page.goto('./');
		await page.locator('.isudev-header__burger').click();
		const { violations } = await scanHeader(page);
		expect(violations).toEqual([]);
	});
});
