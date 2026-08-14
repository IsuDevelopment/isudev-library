/**
 * View script for the toggle content block.
 *
 * Frontend behaviour only: the button expands or collapses the content next
 * to it, animating height and dispatching `isudev/toggle:open` /
 * `isudev/toggle:close` events a theme can hook into.
 */

/* global getComputedStyle */

/**
 * Read the toggle content's full rendered height. Mutates the element.
 *
 * @param {HTMLElement} content
 * @return {number} Content height in pixels.
 */
function getContentHeight(content) {
	content.style.display = 'block';
	content.style.height = 'auto';
	return content.scrollHeight;
}

/**
 * Smooth-scroll so the content's top edge is in view.
 *
 * @param {HTMLElement} content
 */
function scrollToContentTop(content) {
	const targetTop = content.getBoundingClientRect().top + window.pageYOffset;
	window.scrollTo({ top: targetTop, behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', function () {
	const toggleBlocks = document.querySelectorAll('.isudev-toggle');

	toggleBlocks.forEach(function (toggleBlock) {
		const button = toggleBlock.querySelector('.isudev-toggle__button');
		const content = toggleBlock.querySelector('.isudev-toggle__content');

		if (!button || !content) {
			return;
		}

		const text = toggleBlock.querySelector('.isudev-toggle__text');
		const openText = button.getAttribute('data-open-text');
		const closeText = button.getAttribute('data-close-text');
		const shouldScrollToContent =
			toggleBlock.dataset.scrollToContent === 'true';
		const toggleSpeed =
			parseInt(
				getComputedStyle(toggleBlock).getPropertyValue(
					'--isudev-toggle-speed'
				),
				10
			) || 150;

		const eventDetail = () => ({
			block: toggleBlock,
			button,
			content,
			blockId: toggleBlock.dataset.toggleId || null,
		});

		button.addEventListener('click', function () {
			const isExpanded = button.getAttribute('aria-expanded') === 'true';

			if (isExpanded) {
				button.setAttribute('aria-expanded', 'false');
				if (text) {
					text.textContent = openText;
				}

				const currentHeight = content.scrollHeight;
				content.style.height = currentHeight + 'px';
				void content.offsetHeight;
				content.style.height = '0px';

				setTimeout(() => {
					content.setAttribute('hidden', '');
				}, toggleSpeed);

				document.dispatchEvent(
					new CustomEvent('isudev/toggle:close', {
						bubbles: true,
						detail: eventDetail(),
					})
				);
			} else {
				button.setAttribute('aria-expanded', 'true');
				content.removeAttribute('hidden');
				if (text) {
					text.textContent = closeText;
				}

				const contentHeight = getContentHeight(content);
				content.style.height = '0px';
				void content.offsetHeight;
				content.style.height = contentHeight + 'px';

				if (shouldScrollToContent) {
					setTimeout(() => {
						scrollToContentTop(content);
					}, toggleSpeed + 10);
				}

				setTimeout(() => {
					content.style.height = 'auto';
				}, toggleSpeed);

				document.dispatchEvent(
					new CustomEvent('isudev/toggle:open', {
						bubbles: true,
						detail: eventDetail(),
					})
				);
			}
		});

		button.addEventListener('keydown', function (event) {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault();
				button.click();
			}
		});
	});
});
