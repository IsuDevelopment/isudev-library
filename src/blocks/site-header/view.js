/**
 * Accessible site header — disclosure nav, mobile drawer, scroll state.
 *
 * All interaction state is kept on the block root (`.isudev-header`), except the
 * scroll lock which toggles `isudev-scroll-locked` on the document element.
 */

/* global IntersectionObserver, MutationObserver */

const DESKTOP_MQ = window.matchMedia('(min-width: 62rem)');

/**
 * Disclosure dropdowns for top-level items with a submenu panel.
 *
 * @param {HTMLElement} header The header root element.
 * @return {void}
 */
function initDisclosure(header) {
	const items = Array.from(
		header.querySelectorAll('.isudev-nav__item.has-submenu')
	);
	if (!items.length) {
		return;
	}

	const setOpen = (li, open) => {
		const toggle = li.querySelector('.isudev-nav__toggle');
		li.classList.toggle('is-open', open);
		if (toggle) {
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
	};

	const closeAll = (except) => {
		items.forEach((li) => {
			if (li !== except) {
				setOpen(li, false);
			}
		});
	};

	items.forEach((li) => {
		const toggle = li.querySelector('.isudev-nav__toggle');
		if (!toggle) {
			return;
		}

		toggle.addEventListener('click', () => {
			const isOpen = li.classList.contains('is-open');
			closeAll(li);
			setOpen(li, !isOpen);
		});

		li.addEventListener('mouseenter', () => {
			if (DESKTOP_MQ.matches) {
				closeAll(li);
				setOpen(li, true);
			}
		});
		li.addEventListener('mouseleave', () => {
			if (
				DESKTOP_MQ.matches &&
				!li.contains(li.ownerDocument.activeElement)
			) {
				setOpen(li, false);
			}
		});

		li.addEventListener('focusout', (event) => {
			if (DESKTOP_MQ.matches && !li.contains(event.relatedTarget)) {
				setOpen(li, false);
			}
		});

		li.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && li.classList.contains('is-open')) {
				setOpen(li, false);
				toggle.focus();
				// Innermost layer first: keep the drawer's Escape from also firing.
				event.preventDefault();
			}
		});
	});

	header.ownerDocument.addEventListener('click', (event) => {
		if (
			DESKTOP_MQ.matches &&
			!event.target.closest('.isudev-nav__item.has-submenu')
		) {
			closeAll(null);
		}
	});
}

/**
 * Mobile off-canvas drawer with focus trap, Escape, focus return, scroll lock.
 *
 * @param {HTMLElement} header The header root element.
 * @return {void}
 */
function initDrawer(header) {
	const burger = header.querySelector('.isudev-header__burger');
	const drawer = header.querySelector('#isudev-header-nav');
	if (!burger || !drawer) {
		return;
	}
	const doc = header.ownerDocument;
	const closeBtn = drawer.querySelector('.isudev-header__close');

	const isFocusable = (el) => {
		if (el === el.ownerDocument.activeElement) {
			return true;
		}
		// visibility:hidden (incl. inherited, e.g. closed accordion panels) is
		// never focusable. Use getClientRects() rather than offsetParent to
		// detect "not rendered" (display:none / detached), since offsetParent is
		// null for position:fixed elements even when they are visible.
		if (
			el.ownerDocument.defaultView.getComputedStyle(el).visibility ===
			'hidden'
		) {
			return false;
		}
		return el.getClientRects().length > 0;
	};

	const focusablesIn = (root) =>
		Array.from(
			root.querySelectorAll(
				'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
			)
		).filter(isFocusable);

	const openDrawer = () => {
		header.classList.add('is-drawer-open');
		doc.documentElement.classList.add('isudev-scroll-locked');
		burger.setAttribute('aria-expanded', 'true');
		const focusables = focusablesIn(drawer);
		(closeBtn || focusables[0] || drawer).focus();
	};

	const closeDrawer = () => {
		header.classList.remove('is-drawer-open');
		doc.documentElement.classList.remove('isudev-scroll-locked');
		burger.setAttribute('aria-expanded', 'false');
		burger.focus();
	};

	burger.addEventListener('click', () => {
		if (header.classList.contains('is-drawer-open')) {
			closeDrawer();
		} else {
			openDrawer();
		}
	});

	if (closeBtn) {
		closeBtn.addEventListener('click', closeDrawer);
	}

	doc.addEventListener('keydown', (event) => {
		if (!header.classList.contains('is-drawer-open')) {
			return;
		}
		if (event.key === 'Escape') {
			if (event.defaultPrevented) {
				return;
			}
			closeDrawer();
			return;
		}
		if (event.key === 'Tab') {
			const focusables = focusablesIn(drawer);
			if (!focusables.length) {
				return;
			}
			const first = focusables[0];
			const last = focusables[focusables.length - 1];
			if (event.shiftKey && doc.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && doc.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		}
	});

	DESKTOP_MQ.addEventListener('change', (event) => {
		if (event.matches && header.classList.contains('is-drawer-open')) {
			header.classList.remove('is-drawer-open');
			doc.documentElement.classList.remove('isudev-scroll-locked');
			burger.setAttribute('aria-expanded', 'false');
		}
	});
}

/**
 * Toggle `is-scrolled` on the header via a sentinel at 15dvh (no jump loop).
 *
 * @param {HTMLElement} header The header root element.
 * @return {void}
 */
function initScrollState(header) {
	const doc = header.ownerDocument;
	const win = doc.defaultView;

	const sentinel = doc.createElement('div');
	sentinel.className = 'isudev-header__scroll-sentinel';
	sentinel.setAttribute('aria-hidden', 'true');
	doc.body.prepend(sentinel);

	const apply = (scrolled) => {
		header.classList.toggle('is-scrolled', scrolled);
	};

	if ('IntersectionObserver' in win) {
		const io = new IntersectionObserver(
			(entries) => {
				apply(!entries[0].isIntersecting);
			},
			{ threshold: 0 }
		);
		io.observe(sentinel);
	} else {
		let ticking = false;
		const hi = win.innerHeight * 0.15;
		const lo = win.innerHeight * 0.08;
		const onScroll = () => {
			if (ticking) {
				return;
			}
			ticking = true;
			win.requestAnimationFrame(() => {
				const y = win.scrollY || win.pageYOffset;
				if (y > hi) {
					apply(true);
				} else if (y < lo) {
					apply(false);
				}
				ticking = false;
			});
		};
		win.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}
}

/**
 * Mirror an integrator's white-header body class onto `.isudev-header.is-white`.
 *
 * @param {HTMLElement} header The header root element.
 * @return {void}
 */
function initWhiteHeader(header) {
	const bodyClass = header.getAttribute('data-white-header-class');
	if (!bodyClass) {
		return;
	}
	const doc = header.ownerDocument;
	const sync = () => {
		header.classList.toggle(
			'is-white',
			doc.body.classList.contains(bodyClass)
		);
	};
	sync();
	if ('MutationObserver' in header.ownerDocument.defaultView) {
		const mo = new MutationObserver(sync);
		mo.observe(doc.body, { attributes: true, attributeFilter: ['class'] });
	}
}

/**
 * Bootstrap every header instance on the page.
 *
 * @return {void}
 */
function initHeaders() {
	const headers = Array.from(document.querySelectorAll('.isudev-header'));
	headers.forEach((header) => {
		initDisclosure(header);
		initDrawer(header);
		initScrollState(header);
		initWhiteHeader(header);
	});
}

if (document.readyState !== 'loading') {
	initHeaders();
} else {
	document.addEventListener('DOMContentLoaded', initHeaders);
}
