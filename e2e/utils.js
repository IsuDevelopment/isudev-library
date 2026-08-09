/**
 * Shared E2E helpers.
 */

/**
 * Press Tab until `locator` is the active element (engages keyboard modality so
 * :focus-visible matches). Returns whether it was reached.
 *
 * @param {import('@playwright/test').Page}    page
 * @param {import('@playwright/test').Locator} locator
 * @param {number}                             max     Maximum Tab presses.
 * @return {Promise<boolean>} Whether the locator became the active element.
 */
async function tabToFocus(page, locator, max = 30) {
	for (let i = 0; i < max; i++) {
		const focused = await locator
			.evaluate((el) => el === el.ownerDocument.activeElement)
			.catch(() => false);
		if (focused) {
			return true;
		}
		await page.keyboard.press('Tab');
	}
	return locator
		.evaluate((el) => el === el.ownerDocument.activeElement)
		.catch(() => false);
}

/**
 * Computed outline of an element, as seen from JS.
 *
 * @param {import('@playwright/test').Locator} locator
 * @return {Promise<{ style: string, width: string }>} The computed outline.
 */
function outlineOf(locator) {
	return locator.evaluate((el) => {
		const s = el.ownerDocument.defaultView.getComputedStyle(el);
		return { style: s.outlineStyle, width: s.outlineWidth };
	});
}

module.exports = { tabToFocus, outlineOf };
