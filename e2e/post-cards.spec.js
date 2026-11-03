/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');

/**
 * Internal dependencies
 */
const { hasAdminCredentials, loginAsAdmin } = require('./admin-auth');

const DESKTOP = 'desktop-chromium';
const PREFIX = 'E2E card';

// Content is created over REST in beforeAll and removed in afterAll, so the
// suite needs nothing seeded and leaves nothing behind. It uses the plugin's
// default card template only: no theme templates are assumed.
const fixture = { posts: [], pageId: 0, pageLink: '', termId: 0 };

/**
 * REST call as the logged-in admin.
 *
 * @param {import('@playwright/test').Page} page   Logged-in page.
 * @param {string}                          method HTTP method.
 * @param {string}                          route  Route below /wp-json/.
 * @param {Object}                          data   JSON body.
 * @return {Promise<Object>} Decoded response.
 */
async function rest(page, method, route, data) {
	const nonce = await page.evaluate(() =>
		fetch('/wp-admin/admin-ajax.php?action=rest-nonce').then((r) =>
			r.text()
		)
	);
	const response = await page.request.fetch(`/wp-json/${route}`, {
		method,
		headers: { 'X-WP-Nonce': nonce },
		data,
	});
	expect(response.ok(), `${method} ${route}`).toBeTruthy();
	return response.json();
}

/**
 * The test page: every way a card gets its post.
 *
 * @param {number[]} ids    Post IDs, newest first.
 * @param {number}   termId Category holding the posts.
 * @return {string} Block markup.
 */
function pageContent(ids, termId) {
	const query = JSON.stringify({
		postType: 'post',
		taxonomy: 'category',
		terms: [termId],
		orderBy: 'date',
		order: 'desc',
	});

	return [
		'<!-- wp:isudev/post-cards {"label":"Picked","className":"e2e-picked"} -->',
		`<!-- wp:isudev/post-card {"postId":${ids[0]}} /-->`,
		`<!-- wp:isudev/post-card {"postId":${ids[1]},"cardOptions":{"customTitle":"Custom title","hideExcerpt":true}} /-->`,
		`<!-- wp:isudev/post-card {"postId":${ids[2]}} /-->`,
		'<!-- /wp:isudev/post-cards -->',
		`<!-- wp:isudev/post-cards {"label":"Slots","className":"e2e-slots","uniqueOnPage":true,"query":${query}} -->`,
		'<!-- wp:isudev/post-card {"source":"query"} /-->',
		'<!-- wp:isudev/post-card {"source":"query"} /-->',
		'<!-- /wp:isudev/post-cards -->',
		`<!-- wp:isudev/post-cards {"label":"Carousel","className":"e2e-slider","layout":"slider","query":${query}} -->`,
		'<!-- wp:isudev/post-card {"source":"query"} /-->',
		'<!-- wp:isudev/post-card {"source":"query"} /-->',
		'<!-- wp:isudev/post-card {"source":"query"} /-->',
		'<!-- wp:isudev/post-card {"source":"query"} /-->',
		'<!-- /wp:isudev/post-cards -->',
		'<!-- wp:isudev/post-card {"className":"e2e-orphan"} /-->',
		`<!-- wp:query {"queryId":71,"className":"e2e-loop","query":{"perPage":2,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false,"taxQuery":{"category":[${termId}]}}} -->`,
		'<div class="wp-block-query e2e-loop"><!-- wp:post-template -->',
		'<!-- wp:isudev/post-card /-->',
		'<!-- /wp:post-template --></div>',
		'<!-- /wp:query -->',
	].join('\n');
}

test.describe('Post cards', () => {
	test.skip(
		!hasAdminCredentials(),
		'No admin credentials: run the dev fixture, or set WP_ADMIN_USER and WP_ADMIN_PASS.'
	);

	test.describe.configure({ mode: 'serial' });

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage();
		await loginAsAdmin(page);

		const term = await rest(page, 'POST', 'wp/v2/categories', {
			name: `${PREFIX} ${Date.now()}`,
		});
		fixture.termId = term.id;

		// Five posts, a minute apart, so date order is deterministic.
		for (let i = 0; i < 5; i++) {
			const post = await rest(page, 'POST', 'wp/v2/posts', {
				title: `${PREFIX} ${i + 1}`,
				excerpt: `${PREFIX} excerpt ${i + 1}`,
				status: 'publish',
				date: new Date(Date.now() - (i + 1) * 60000).toISOString(),
				categories: [term.id],
			});
			fixture.posts.push(post);
		}

		const ids = fixture.posts.map((post) => post.id);
		const created = await rest(page, 'POST', 'wp/v2/pages', {
			title: `${PREFIX} page`,
			status: 'publish',
			content: pageContent(ids, term.id),
		});
		fixture.pageId = created.id;
		fixture.pageLink = created.link;

		await page.close();
	});

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage();
		await loginAsAdmin(page);

		for (const post of fixture.posts) {
			await rest(page, 'DELETE', `wp/v2/posts/${post.id}?force=true`);
		}
		if (fixture.pageId) {
			await rest(
				page,
				'DELETE',
				`wp/v2/pages/${fixture.pageId}?force=true`
			);
		}
		if (fixture.termId) {
			await rest(
				page,
				'DELETE',
				`wp/v2/categories/${fixture.termId}?force=true`
			);
		}

		await page.close();
	});

	test('hand-picked cards render in order with their options', async ({
		page,
	}) => {
		await page.goto(fixture.pageLink);

		const list = page.locator('.e2e-picked [role="list"]');
		await expect(list).toHaveAttribute('aria-label', 'Picked');

		const cards = list.locator('> .isudev-post-card');
		await expect(cards).toHaveCount(3);
		await expect(cards.nth(0)).toHaveAttribute('role', 'listitem');

		await expect(cards.locator('.isudev-card-title')).toHaveText([
			`${PREFIX} 1`,
			'Custom title',
			`${PREFIX} 3`,
		]);

		// hideExcerpt: class on the card, no excerpt inside it.
		await expect(cards.nth(1)).toHaveClass(/has-card-hide-excerpt/);
		await expect(cards.nth(1).locator('.isudev-card-excerpt')).toHaveCount(
			0
		);
		await expect(cards.nth(0).locator('.isudev-card-excerpt')).toHaveText(
			`${PREFIX} excerpt 1`
		);

		// One link per card: the title, pointing at the post.
		const links = cards.nth(0).locator('a');
		await expect(links).toHaveCount(1);
		await expect(links).toHaveAttribute('href', fixture.posts[0].link);
	});

	test('query slots skip posts already on the page', async ({ page }) => {
		await page.goto(fixture.pageLink);

		// Posts 1–3 are hand-picked above and the wrapper asks for unique
		// posts, so its two slots get posts 4 and 5.
		await expect(page.locator('.e2e-slots .isudev-card-title')).toHaveText([
			`${PREFIX} 4`,
			`${PREFIX} 5`,
		]);
	});

	test('a card in a Query Loop shows the loop post', async ({ page }) => {
		await page.goto(fixture.pageLink);

		await expect(page.locator('.e2e-loop .isudev-card-title')).toHaveText([
			`${PREFIX} 1`,
			`${PREFIX} 2`,
		]);
	});

	test('the carousel lays cards out in one row with controls', async ({
		page,
	}) => {
		await page.goto(fixture.pageLink);

		const slider = page.locator('.e2e-slider');
		await slider.scrollIntoViewIfNeeded();

		// The shared slider's CSS (viewStyle) is loaded: slides sit side by side.
		const cards = slider.locator('.isudev-post-card');
		await expect(cards).toHaveCount(4);
		const first = await cards.nth(0).boundingBox();
		const second = await cards.nth(1).boundingBox();
		expect(second.y).toBeCloseTo(first.y, 0);
		expect(second.x).toBeGreaterThan(first.x);

		await expect(slider.locator('.isudev-slider__next')).toBeVisible();
	});

	test('a card with no post renders nothing', async ({ page }) => {
		await page.goto(fixture.pageLink);

		// Outside a Query Loop the page's own postId must not leak into a card.
		await expect(page.locator('.e2e-orphan')).toHaveCount(0);
	});

	test('cards stack on mobile and sit side by side on desktop', async ({
		page,
	}, testInfo) => {
		await page.goto(fixture.pageLink);

		const cards = page.locator('.e2e-picked .isudev-post-card');
		const first = await cards.nth(0).boundingBox();
		const third = await cards.nth(2).boundingBox();

		if (testInfo.project.name === DESKTOP) {
			expect(third.y).toBeCloseTo(first.y, 0);
		} else {
			expect(third.y).toBeGreaterThan(first.y + first.height - 1);
		}
	});

	test('the editor previews the same cards as the front end', async ({
		page,
	}, testInfo) => {
		test.skip(testInfo.project.name !== DESKTOP, 'desktop only');

		await loginAsAdmin(page);
		await page.goto(
			`/wp-admin/post.php?post=${fixture.pageId}&action=edit`
		);

		const canvas = page.frameLocator('iframe[name="editor-canvas"]');

		await expect(
			canvas.locator('.e2e-picked .isudev-card-title')
		).toHaveText([`${PREFIX} 1`, 'Custom title', `${PREFIX} 3`], {
			timeout: 30000,
		});
		await expect(
			canvas.locator('.e2e-slots .isudev-card-title')
		).toHaveText([`${PREFIX} 4`, `${PREFIX} 5`]);
		// The editor keeps a hidden preview copy of the active loop item.
		await expect(
			canvas
				.locator('.e2e-loop .isudev-card-title')
				.filter({ visible: true })
		).toHaveText([`${PREFIX} 1`, `${PREFIX} 2`]);

		// The saved markup parses cleanly: no block recovery prompts.
		await expect(
			canvas.getByText(/unexpected or invalid content/)
		).toHaveCount(0);
	});
});
