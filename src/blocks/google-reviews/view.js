/**
 * View script for the Google Reviews list/carousel block.
 *
 * Bundles Swiper itself rather than depending on a theme-provided `swiper`
 * script/style handle — this plugin has no runtime dependency on any theme
 * or other plugin. See guides/google-reviews.md.
 */

/**
 * External dependencies
 */
import Swiper from 'swiper';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';

const SLIDER_SELECTOR = '[data-google-reviews-slider="true"]';
const BLOCK_SELECTOR = '.isudev-google-reviews';
const reducedMotionQuery = window.matchMedia(
	'(prefers-reduced-motion: reduce)'
);

const viewportObserver =
	'IntersectionObserver' in window
		? new window.IntersectionObserver((entries) => {
				entries.forEach((entry) => {
					entry.target.dataset.sliderInViewport = String(
						entry.isIntersecting
					);

					const autoplay = entry.target.swiper?.autoplay;

					if (!autoplay) {
						return;
					}

					const hasExpandedReview = entry.target.querySelector(
						'[data-google-review-toggle][aria-expanded="true"]'
					);

					if (entry.isIntersecting && !hasExpandedReview) {
						autoplay.start();
					} else {
						autoplay.stop();
					}
				});
			})
		: null;

/**
 * Initialize one carousel instance.
 *
 * @param {HTMLElement} slider
 */
function initGoogleReviewSlider(slider) {
	if (!(slider instanceof window.HTMLElement) || slider.swiper) {
		return;
	}

	const mode =
		slider.dataset.sliderMode === 'classic' ? 'classic' : 'continuous';
	const isContinuous = mode === 'continuous' && !reducedMotionQuery.matches;
	const slides = slider.querySelectorAll('.swiper-slide');

	const settings = {
		slidesPerView: isContinuous ? 1.5 : 1,
		spaceBetween: 24,
		speed: isContinuous ? 10000 : 450,
		watchOverflow: true,
		loop: slides.length > 1,
		loopAdditionalSlides: 0,
		centeredSlides: isContinuous,
		a11y: true,
		autoplay: isContinuous
			? {
					delay: 0,
					disableOnInteraction: false,
					pauseOnMouseEnter: false,
				}
			: false,
		navigation: isContinuous
			? false
			: {
					nextEl: slider.querySelector('.swiper-button-next'),
					prevEl: slider.querySelector('.swiper-button-prev'),
				},
		pagination: isContinuous
			? false
			: {
					el: slider.querySelector('.swiper-pagination'),
					clickable: true,
				},
		breakpoints: {
			768: { slidesPerView: 2 },
			1024: { slidesPerView: 4 },
			1440: { slidesPerView: 5 },
		},
	};

	const swiper = new Swiper(slider, settings);

	if (isContinuous && viewportObserver) {
		swiper.autoplay?.stop();
		viewportObserver.observe(slider);
	}
}

const initializationObserver =
	'IntersectionObserver' in window
		? new window.IntersectionObserver(
				(entries) => {
					entries.forEach((entry) => {
						if (!entry.isIntersecting) {
							return;
						}

						initGoogleReviewSlider(entry.target);
						initializationObserver.unobserve(entry.target);
					});
				},
				{ rootMargin: '400px 0px' }
			)
		: null;

function initGoogleReviewSliders() {
	document.querySelectorAll(SLIDER_SELECTOR).forEach((slider) => {
		if (!(slider instanceof window.HTMLElement) || slider.swiper) {
			return;
		}

		if (initializationObserver) {
			initializationObserver.observe(slider);
		} else {
			initGoogleReviewSlider(slider);
		}
	});
}

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

			const slider = button.closest(SLIDER_SELECTOR);
			const autoplay = slider?.swiper?.autoplay;

			if (!autoplay) {
				return;
			}

			if (!isExpanded) {
				autoplay.stop();
				return;
			}

			const hasExpandedReview = slider.querySelector(
				'[data-google-review-toggle][aria-expanded="true"]'
			);

			if (
				!hasExpandedReview &&
				slider.dataset.sliderMode === 'continuous' &&
				slider.dataset.sliderInViewport === 'true' &&
				!reducedMotionQuery.matches
			) {
				autoplay.start();
			}
		});
	});
}

function initGoogleReviews() {
	initReviewExpanders();
	initGoogleReviewSliders();
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initGoogleReviews, {
		once: true,
	});
} else {
	initGoogleReviews();
}
