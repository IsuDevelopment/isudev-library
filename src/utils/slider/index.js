/**
 * Shared slider wrapper — the only module allowed to import Embla.
 *
 * Initializes markup rendered by IsuDevLibrary\Utils\Slider\render()
 * (includes/utils/slider.php). Bundled into each consuming block's view
 * script; API, class contract and a11y rules: guides/slider.md.
 */

/**
 * External dependencies
 */
import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';
import AutoScroll from 'embla-carousel-auto-scroll';

/**
 * Internal dependencies
 */
import './slider.scss';

const ROOT_SELECTOR = '.isudev-slider';
const instances = new WeakMap();

const reducedMotion = () =>
	window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// One observer pauses every instance while it is off-screen.
const visibilityObserver =
	'IntersectionObserver' in window
		? new window.IntersectionObserver((entries) => {
				entries.forEach((entry) => {
					const controller = instances.get(entry.target);

					if (!controller) {
						return;
					}

					if (entry.isIntersecting) {
						controller.resume('offscreen');
					} else {
						controller.pause('offscreen');
					}
				});
			})
		: null;

/**
 * Read the JSON options PHP rendered on the root.
 *
 * @param {HTMLElement} root Slider root.
 * @return {Object} Options, empty when missing or malformed.
 */
function readOptions(root) {
	try {
		return JSON.parse(root.dataset.isudevSlider || '{}') || {};
	} catch {
		return {};
	}
}

/**
 * Initialize one slider now.
 *
 * @param {HTMLElement} root    `.isudev-slider` element.
 * @param {Object}      options Defaults; the root's data-isudev-slider wins.
 * @return {Object|null} Controller, or null when the markup is incomplete.
 */
export function createSlider(root, options = {}) {
	if (!(root instanceof window.HTMLElement)) {
		return null;
	}

	if (instances.has(root)) {
		return instances.get(root);
	}

	const viewport = root.querySelector('.isudev-slider__viewport');

	if (!viewport) {
		return null;
	}

	const settings = { ...options, ...readOptions(root) };
	const isReduced = reducedMotion();
	const isRtl = window.getComputedStyle(root).direction === 'rtl';
	const prevButton = root.querySelector('.isudev-slider__prev');
	const nextButton = root.querySelector('.isudev-slider__next');
	const pauseButton = root.querySelector('.isudev-slider__pause');
	const dotsNode = root.querySelector('.isudev-slider__dots');

	const plugins = [];
	let motion = null;

	// Every stop/start goes through pauseReasons, so plugins never restart on their own.
	const pluginDefaults = {
		playOnInit: false,
		stopOnInteraction: true,
		stopOnMouseEnter: false,
		stopOnFocusIn: false,
	};

	if (!isReduced && settings.autoScroll) {
		motion = AutoScroll({
			...pluginDefaults,
			speed: Number(settings.autoScroll) || 1,
			startDelay: 500,
		});
	} else if (!isReduced && settings.autoplay) {
		motion = Autoplay({
			...pluginDefaults,
			delay: Number(settings.autoplay) || 5000,
		});
	}

	if (motion) {
		plugins.push(motion);
	}

	const embla = EmblaCarousel(
		viewport,
		{
			loop: Boolean(settings.loop),
			align: settings.align || 'start',
			slidesToScroll: settings.slidesToScroll || 1,
			dragFree: Boolean(settings.dragFree),
			direction: isRtl ? 'rtl' : 'ltr',
			duration: isReduced ? 10 : 25,
		},
		plugins
	);

	const pauseReasons = new Set();
	const cleanups = [];

	const listen = (target, type, handler) => {
		target.addEventListener(type, handler);
		cleanups.push(() => target.removeEventListener(type, handler));
	};

	const canMove = () => embla.scrollSnapList().length > 1;

	function syncMotion() {
		if (!motion) {
			return;
		}

		if (pauseReasons.size === 0 && canMove()) {
			motion.play();
		} else {
			motion.stop();
		}

		const playing = motion.isPlaying();
		root.classList.toggle('is-playing', playing);
	}

	function syncPauseButton() {
		if (!pauseButton) {
			return;
		}

		const paused = pauseReasons.has('user');
		root.classList.toggle('is-paused', paused);
		pauseButton.setAttribute(
			'aria-label',
			paused
				? pauseButton.dataset.labelPlay
				: pauseButton.dataset.labelPause
		);
	}

	function pause(reason = 'api') {
		pauseReasons.add(reason);
		syncMotion();
		syncPauseButton();
	}

	function resume(reason = 'api') {
		pauseReasons.delete(reason);
		syncMotion();
		syncPauseButton();
	}

	function setDisabled(button, disabled) {
		if (!button) {
			return;
		}

		button.disabled = disabled;
		button.setAttribute('aria-disabled', String(disabled));
	}

	function syncControls() {
		setDisabled(prevButton, !embla.canScrollPrev());
		setDisabled(nextButton, !embla.canScrollNext());
		root.classList.toggle('is-static', !canMove());

		if (!dotsNode) {
			return;
		}

		const selected = embla.selectedScrollSnap();
		Array.from(dotsNode.children).forEach((dot, index) => {
			if (index === selected) {
				dot.setAttribute('aria-current', 'true');
			} else {
				dot.removeAttribute('aria-current');
			}
		});
	}

	function buildDots() {
		if (!dotsNode) {
			return;
		}

		const template = dotsNode.dataset.label || '%d';
		dotsNode.replaceChildren(
			...embla.scrollSnapList().map((snap, index) => {
				const dot = root.ownerDocument.createElement('button');
				dot.type = 'button';
				dot.className = 'isudev-slider__dot';
				dot.setAttribute(
					'aria-label',
					template.replace('%d', String(index + 1))
				);
				dot.addEventListener('click', () => controller.scrollTo(index));
				return dot;
			})
		);
	}

	const jump = () => isReduced;

	const controller = {
		root,
		scrollNext: () => embla.scrollNext(jump()),
		scrollPrev: () => embla.scrollPrev(jump()),
		scrollTo: (index) => embla.scrollTo(index, jump()),
		selectedIndex: () => embla.selectedScrollSnap(),
		snapCount: () => embla.scrollSnapList().length,
		slideCount: () => embla.slideNodes().length,
		hasMotion: () => Boolean(motion),
		isPlaying: () => Boolean(motion && motion.isPlaying()),
		pause,
		resume,
		/**
		 * Subscribe to 'select' or 'reInit'.
		 *
		 * @param {string}   event    Event name.
		 * @param {Function} callback Receives the controller.
		 * @return {Function} Unsubscribe.
		 */
		on(event, callback) {
			const handler = () => callback(controller);
			embla.on(event, handler);
			return () => embla.off(event, handler);
		},
		destroy() {
			visibilityObserver?.unobserve(root);
			cleanups.forEach((cleanup) => cleanup());
			embla.destroy();
			dotsNode?.replaceChildren();
			root.classList.remove(
				'is-ready',
				'is-static',
				'is-playing',
				'is-paused',
				'is-dragging',
				'has-autoplay',
				'has-auto-scroll'
			);
			instances.delete(root);
		},
	};

	if (prevButton) {
		listen(prevButton, 'click', controller.scrollPrev);
	}

	if (nextButton) {
		listen(nextButton, 'click', controller.scrollNext);
	}

	if (pauseButton) {
		listen(pauseButton, 'click', () => {
			if (pauseReasons.has('user')) {
				// An explicit Play outranks the hover and focus the click itself caused.
				pauseReasons.delete('hover');
				pauseReasons.delete('focus');
				resume('user');
			} else {
				pause('user');
			}
		});
	}

	listen(viewport, 'keydown', (event) => {
		if (
			event.target.closest?.('input, textarea, select, [contenteditable]')
		) {
			return;
		}

		const forward = isRtl ? 'ArrowLeft' : 'ArrowRight';
		const backward = isRtl ? 'ArrowRight' : 'ArrowLeft';

		if (event.key === forward) {
			event.preventDefault();
			controller.scrollNext();
		} else if (event.key === backward) {
			event.preventDefault();
			controller.scrollPrev();
		}
	});

	if (motion) {
		// Mouse only: a tap emits mouseenter with no mouseleave and would pause for good.
		listen(root, 'pointerenter', (event) => {
			if (event.pointerType === 'mouse') {
				pause('hover');
			}
		});
		listen(root, 'pointerleave', () => resume('hover'));
		// Keyboard focus only: a mouse drag focuses the viewport too.
		listen(root, 'focusin', (event) => {
			if (event.target.matches?.(':focus-visible')) {
				pause('focus');
			}
		});
		listen(root, 'focusout', (event) => {
			if (!root.contains(event.relatedTarget)) {
				resume('focus');
			}
		});
		embla.on('pointerDown', () => pause('drag'));
		// Auto-scroll takes over the scroll body, so let the drag momentum settle first.
		embla.on(settings.autoScroll ? 'settle' : 'pointerUp', () =>
			resume('drag')
		);

		const motionQuery = window.matchMedia(
			'(prefers-reduced-motion: reduce)'
		);
		const onMotionChange = () => {
			if (motionQuery.matches) {
				pause('reduced-motion');
			} else {
				resume('reduced-motion');
			}
		};
		motionQuery.addEventListener('change', onMotionChange);
		cleanups.push(() =>
			motionQuery.removeEventListener('change', onMotionChange)
		);
	}

	// Embla cancels mousemove, not the mousedown that starts a text selection.
	embla.on('pointerDown', () => {
		root.classList.add('is-dragging');
		root.ownerDocument.getSelection()?.removeAllRanges();
	});
	embla.on('pointerUp', () => root.classList.remove('is-dragging'));
	embla.on('select', syncControls);
	embla.on('reInit', () => {
		buildDots();
		syncControls();
		syncMotion();
	});

	instances.set(root, controller);
	root.classList.add('is-ready');

	if (motion) {
		root.classList.add(
			settings.autoScroll ? 'has-auto-scroll' : 'has-autoplay'
		);
	}

	buildDots();
	syncControls();
	syncPauseButton();

	if (motion && visibilityObserver) {
		// Held until the observer reports the root on screen.
		pauseReasons.add('offscreen');
		visibilityObserver.observe(root);
	} else {
		syncMotion();
	}

	return controller;
}

/**
 * The controller for a root, if it has been initialized.
 *
 * @param {HTMLElement} root Slider root.
 * @return {Object|undefined} Controller.
 */
export function getSlider(root) {
	return instances.get(root);
}

/**
 * Initialize every slider in a scope, lazily by default.
 *
 * @param {Document|Element} scope           Where to look. Default document.
 * @param {Object}           args            Optional.
 * @param {string}           args.selector   Root selector. Default '.isudev-slider'.
 * @param {Object}           args.options    Defaults passed to createSlider().
 * @param {boolean}          args.lazy       Wait until near the viewport. Default true.
 * @param {string}           args.rootMargin How near. Default '400px 0px'.
 * @param {Function}         args.onInit     Called with each new controller.
 */
export function initSliders(
	scope = document,
	{
		selector = ROOT_SELECTOR,
		options = {},
		lazy = true,
		rootMargin = '400px 0px',
		onInit,
	} = {}
) {
	const init = (root) => {
		const isNew = !instances.has(root);
		const controller = createSlider(root, options);

		if (controller && isNew && onInit) {
			onInit(controller);
		}
	};

	const roots = Array.from(scope.querySelectorAll(selector)).filter(
		(root) => !instances.has(root)
	);

	if (!lazy || !('IntersectionObserver' in window)) {
		roots.forEach(init);
		return;
	}

	const observer = new window.IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					observer.unobserve(entry.target);
					init(entry.target);
				}
			});
		},
		{ rootMargin }
	);

	roots.forEach((root) => observer.observe(root));
}
