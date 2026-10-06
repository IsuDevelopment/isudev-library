/**
 * View script for the Google Reviews list/carousel block.
 *
 * The carousel is the shared slider wrapper (src/utils/slider, Embla inside),
 * bundled here; markup comes from includes/utils/slider.php. See
 * guides/slider.md and guides/google-reviews.md.
 */

/**
 * Internal dependencies
 */
import { getSlider, initSliders } from '../../utils/slider';

const BLOCK_SELECTOR = '.isudev-google-reviews';
const SLIDER_SELECTOR = '.isudev-google-reviews__viewport.isudev-slider';
const EXPANDED_SELECTOR = '[data-google-review-toggle][aria-expanded="true"]';

function initReviewExpanders() {
	document.querySelectorAll(BLOCK_SELECTOR).forEach((block) => {
		if (
			!(block instanceof window.HTMLElement) ||
			block.dataset.reviewExpandersReady === 'true'
		) {
			return;
		}

		block.dataset.reviewExpandersReady = 'true';
		block.addEventListener('click', (event) => {
			if (!(event.target instanceof window.Element)) {
				return;
			}

			const button = event.target.closest('[data-google-review-toggle]');

			if (
				!(button instanceof window.HTMLButtonElement) ||
				!block.contains(button)
			) {
				return;
			}

			const remainderId = button.getAttribute('aria-controls');
			const remainder = remainderId
				? document.getElementById(remainderId)
				: null;
			const isExpanded = button.getAttribute('aria-expanded') === 'true';
			const ellipsis = remainder?.previousElementSibling;

			if (!(remainder instanceof window.HTMLElement)) {
				return;
			}

			button.setAttribute('aria-expanded', String(!isExpanded));
			button.textContent = isExpanded
				? button.dataset.labelExpand
				: button.dataset.labelCollapse;
			remainder.hidden = isExpanded;

			if (ellipsis instanceof window.HTMLElement) {
				ellipsis.hidden = !isExpanded;
			}

			// Hold motion while any review in this carousel is open for reading.
			const sliderRoot = button.closest(SLIDER_SELECTOR);
			const slider = sliderRoot ? getSlider(sliderRoot) : null;

			if (!slider) {
				return;
			}

			if (sliderRoot.querySelector(EXPANDED_SELECTOR)) {
				slider.pause('expanded');
			} else {
				slider.resume('expanded');
			}
		});
	});
}

function initGoogleReviews() {
	initReviewExpanders();
	initSliders(document, {
		selector: SLIDER_SELECTOR,
		onInit: (slider) => {
			if (slider.root.querySelector(EXPANDED_SELECTOR)) {
				slider.pause('expanded');
			}
		},
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initGoogleReviews, {
		once: true,
	});
} else {
	initGoogleReviews();
}
