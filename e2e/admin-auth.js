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
		const file = path.join(
			__dirname,
			'..',
			'tools',
			'.e2e-credentials.json'
		);
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
