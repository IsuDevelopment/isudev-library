/**
 * Accessible header — behavior & accessibility E2E.
 */
/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');
/**
 * Internal dependencies
 */
const { tabToFocus, outlineOf } = require('./utils');

const DESKTOP = 'desktop-chromium';
const MOBILE = 'mobile-chromium';

test.describe('header — structure (all viewports)', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('./');
	});

	test('renders the header, logo and main nav', async ({ page }) => {
		await expect(page.locator('.isudev-header')).toBeVisible();
		await expect(
			page.locator('.isudev-header__logo').first()
		).toBeVisible();
		await expect(page.locator('.isudev-nav[aria-label]')).toHaveCount(1);
	});

	test('label-only parent is a pure disclosure button', async ({ page }) => {
		const toggle = page
			.locator(
				'.isudev-nav__item.has-submenu:not(.has-link) > .isudev-nav__toggle'
			)
			.first();
		test.skip((await toggle.count()) === 0, 'no label-only parent in menu');
		const controls = await toggle.getAttribute('aria-controls');
		expect(controls).toBeTruthy();
		await expect(page.locator(`#${controls}`)).toHaveCount(1);
	});

	test('navigable parent = real link + separate split toggle', async ({
		page,
	}) => {
		const li = page.locator('.isudev-nav__item.has-link').first();
		test.skip((await li.count()) === 0, 'no navigable parent in menu');

		const link = li.locator('> a.isudev-nav__link');
		const href = await link.getAttribute('href');
		expect(href).toBeTruthy();
		expect(href).not.toBe('#');

		const split = li.locator('button.isudev-nav__toggle--split');
		await expect(split).toHaveAttribute('aria-expanded', 'false');
		await expect(split).toHaveAttribute('aria-controls', /isudev-submenu-/);
	});
});

test.describe('header — desktop', () => {
	test.beforeEach(async ({ page }, testInfo) => {
		test.skip(testInfo.project.name !== DESKTOP, 'desktop only');
		await page.goto('./');
	});

	const firstToggle = (page) =>
		page
			.locator('.isudev-nav__item.has-submenu > .isudev-nav__toggle')
			.first();

	// Hover-free activation (dispatchEvent/focus+Enter): a real mouse .click()
	// hovers first (opens on hover) then toggles closed.

	test('activating the toggle opens the dropdown and syncs aria-expanded', async ({
		page,
	}) => {
		const li = page.locator('.isudev-nav__item.has-submenu').first();
		const toggle = li.locator('> .isudev-nav__toggle');
		const panel = li.locator('> .isudev-nav__panel');

		await expect(toggle).toHaveAttribute('aria-expanded', 'false');
		await expect(panel).toBeHidden();

		await toggle.dispatchEvent('click');
		await expect(toggle).toHaveAttribute('aria-expanded', 'true');
		await expect(li).toHaveClass(/is-open/);
		await expect(panel).toBeVisible();

		await toggle.dispatchEvent('click');
		await expect(li).not.toHaveClass(/is-open/);
	});

	test('Escape closes the dropdown and returns focus to the toggle', async ({
		page,
	}) => {
		const li = page.locator('.isudev-nav__item.has-submenu').first();
		const toggle = li.locator('> .isudev-nav__toggle');
		await toggle.focus();
		await page.keyboard.press('Enter');
		await expect(li).toHaveClass(/is-open/);

		await page.keyboard.press('Escape');
		await expect(li).not.toHaveClass(/is-open/);
		await expect(toggle).toBeFocused();
	});

	test('clicking outside closes an open dropdown', async ({ page }) => {
		const li = page.locator('.isudev-nav__item.has-submenu').first();
		await li.locator('> .isudev-nav__toggle').dispatchEvent('click');
		await expect(li).toHaveClass(/is-open/);

		await page.evaluate(() => document.body.click());
		await expect(li).not.toHaveClass(/is-open/);
	});

	test('only one dropdown is open at a time', async ({ page }) => {
		const toggles = page.locator(
			'.isudev-nav__item.has-submenu > .isudev-nav__toggle'
		);
		test.skip((await toggles.count()) < 2, 'need two submenu parents');
		await toggles.nth(0).dispatchEvent('click');
		await toggles.nth(1).dispatchEvent('click');
		await expect(
			page.locator('.isudev-nav__item.has-submenu.is-open')
		).toHaveCount(1);
	});

	test('Enter and Space open the dropdown from the keyboard', async ({
		page,
	}) => {
		const toggle = firstToggle(page);
		await toggle.focus();
		await page.keyboard.press('Enter');
		await expect(toggle).toHaveAttribute('aria-expanded', 'true');
		await page.keyboard.press('Escape');
		await expect(toggle).toHaveAttribute('aria-expanded', 'false');

		await toggle.focus();
		await page.keyboard.press('Space');
		await expect(toggle).toHaveAttribute('aria-expanded', 'true');
	});

	test('hover opens then closes the dropdown', async ({ page }) => {
		const li = page.locator('.isudev-nav__item.has-submenu').first();
		await li.hover();
		await expect(li).toHaveClass(/is-open/);
		await page.locator('.isudev-header__logo').first().hover();
		await expect(li).not.toHaveClass(/is-open/);
	});

	test('a nav toggle shows a visible focus outline on keyboard focus', async ({
		page,
	}) => {
		const toggle = firstToggle(page);
		const reached = await tabToFocus(page, toggle);
		expect(reached).toBe(true);
		const outline = await outlineOf(toggle);
		expect(outline.style).not.toBe('none');
		expect(outline.width).not.toBe('0px');
	});

	test('scrolling past the threshold toggles header.is-scrolled', async ({
		page,
	}) => {
		await page.evaluate(() => {
			const spacer = document.createElement('div');
			spacer.style.height = '300vh';
			spacer.setAttribute('data-test-spacer', '');
			document.body.appendChild(spacer);
		});

		await page.evaluate(() =>
			window.scrollTo(0, Math.round(window.innerHeight * 0.5))
		);
		await expect(page.locator('.isudev-header')).toHaveClass(/is-scrolled/);

		await page.evaluate(() => window.scrollTo(0, 0));
		await expect(page.locator('.isudev-header')).not.toHaveClass(
			/is-scrolled/
		);
	});
});

test.describe('header — mobile drawer', () => {
	test.beforeEach(async ({ page }, testInfo) => {
		test.skip(testInfo.project.name !== MOBILE, 'mobile only');
		await page.goto('./');
	});

	test('burger opens the drawer, moves focus to Close and locks scroll', async ({
		page,
	}) => {
		const burger = page.locator('.isudev-header__burger');
		await expect(burger).toBeVisible();
		await expect(burger).toHaveAttribute('aria-expanded', 'false');

		await burger.click();
		await expect(page.locator('.isudev-header')).toHaveClass(
			/is-drawer-open/
		);
		await expect(burger).toHaveAttribute('aria-expanded', 'true');
		await expect(page.locator('.isudev-header__close')).toBeFocused();

		const overflow = await page.evaluate(
			() => window.getComputedStyle(document.documentElement).overflow
		);
		expect(overflow).toBe('hidden');
	});

	test('Escape closes the drawer and restores focus to the burger', async ({
		page,
	}) => {
		const burger = page.locator('.isudev-header__burger');
		await burger.click();
		await expect(page.locator('.isudev-header')).toHaveClass(
			/is-drawer-open/
		);

		await page.keyboard.press('Escape');
		await expect(page.locator('.isudev-header')).not.toHaveClass(
			/is-drawer-open/
		);
		await expect(burger).toHaveAttribute('aria-expanded', 'false');
		await expect(burger).toBeFocused();
	});

	test('focus is trapped inside the open drawer', async ({ page }) => {
		await page.locator('.isudev-header__burger').click();
		for (let i = 0; i < 15; i++) {
			await page.keyboard.press('Tab');
			const inside = await page.evaluate(() => {
				const drawer = document.getElementById('isudev-header-nav');
				return drawer.contains(drawer.ownerDocument.activeElement);
			});
			expect(inside).toBe(true);
		}
	});

	test('submenu toggles work as accordions inside the drawer', async ({
		page,
	}) => {
		await page.locator('.isudev-header__burger').click();
		const li = page
			.locator('#isudev-header-nav .isudev-nav__item.has-submenu')
			.first();
		await li.locator('> .isudev-nav__toggle').click();
		await expect(li).toHaveClass(/is-open/);
		await expect(li.locator('> .isudev-nav__panel')).toBeVisible();
	});

	test('the burger shows a visible focus outline on keyboard focus', async ({
		page,
	}) => {
		const burger = page.locator('.isudev-header__burger');
		const reached = await tabToFocus(page, burger);
		expect(reached).toBe(true);
		const outline = await outlineOf(burger);
		expect(outline.width).not.toBe('0px');
	});
});
