/**
 * View script for the selling points block.
 *
 * When "animate in" is on, reveals each point with a staggered fade/slide as
 * the wrapper scrolls into view.
 */

/* global IntersectionObserver */

const SELECTOR = '.isudev-selling-points.has-animate-in';
const ITEM_SELECTOR = '.isudev-selling-point';
const ITEM_STAGGER_MS = 80;
const REVEAL_ROOT_MARGIN = '0px 0px 150px 0px';

/**
 * Reveal a wrapper on the next two animation frames, so the initial
 * (pre-reveal) styles have a chance to paint before the transition starts.
 *
 * @param {HTMLElement} wrapper
 */
function scheduleReveal(wrapper) {
	window.requestAnimationFrame(() => {
		window.requestAnimationFrame(() => {
			wrapper.classList.add('is-visible');
		});
	});
}

/**
 * Reveal a wrapper, or do so immediately when animation frames aren't
 * available.
 *
 * @param {HTMLElement} wrapper
 */
function reveal(wrapper) {
	if (wrapper.classList.contains('is-visible')) {
		return;
	}

	if (!('requestAnimationFrame' in window)) {
		wrapper.classList.add('is-visible');
		return;
	}

	scheduleReveal(wrapper);
}

/**
 * Stagger each point's reveal delay by its position in the wrapper.
 *
 * @param {HTMLElement} wrapper
 */
function prepareWrapper(wrapper) {
	wrapper.querySelectorAll(ITEM_SELECTOR).forEach((item, index) => {
		item.style.setProperty('--isudev-selling-point-index', index);
		item.style.setProperty(
			'--isudev-selling-point-delay',
			`${index * ITEM_STAGGER_MS}ms`
		);
	});
}

function init() {
	const wrappers = document.querySelectorAll(SELECTOR);

	if (!wrappers.length) {
		return;
	}

	wrappers.forEach(prepareWrapper);

	if (!('IntersectionObserver' in window)) {
		wrappers.forEach(reveal);
		return;
	}

	const observer = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) {
					return;
				}

				reveal(entry.target);
				observer.unobserve(entry.target);
			});
		},
		{
			rootMargin: REVEAL_ROOT_MARGIN,
			threshold: 0,
		}
	);

	wrappers.forEach((wrapper) => {
		observer.observe(wrapper);
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', init, { once: true });
} else {
	init();
}
