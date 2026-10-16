/**
 * Accordion links, front end (static classic script, no build).
 *
 * Core's `core/accordion` store is locked, so this talks to it through the DOM:
 * opening goes through the panel's `beforematch` event, which core already
 * handles with `actions.handleBeforeMatch` (opens, honours autoclose, never
 * toggles closed). Core's own `openPanelByHash` only reacts to targets inside a
 * panel, so a hash naming a toggle is ours alone and nothing runs twice.
 *
 * @param {Window}   window   Global window.
 * @param {Document} document Global document (front end, not iframed).
 */
(function (window, document) {
	const TOGGLE = '.wp-block-accordion-heading__toggle';
	const ACCORDION = '.wp-block-accordion';
	const RETRY_MS = 50;
	const RETRIES = 60; // Up to 3s for the Interactivity runtime to hydrate.

	const decode = (value) => {
		try {
			return decodeURIComponent(value);
		} catch (e) {
			return value;
		}
	};

	const currentId = () => decode(window.location.hash.slice(1));

	const isOpen = (button) => 'true' === button.getAttribute('aria-expanded');

	const panelOf = (button) => {
		const id = button.getAttribute('aria-controls');

		return id ? document.getElementById(id) : null;
	};

	const toggleFor = (id) => {
		const element = id ? document.getElementById(id) : null;

		return element && element.matches(TOGGLE) ? element : null;
	};

	const reducedMotion = () =>
		!!window.matchMedia &&
		window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	const setHash = (id) => {
		const url = id
			? `#${encodeURIComponent(id)}`
			: window.location.pathname + window.location.search;

		window.history.replaceState(window.history.state, '', url);
	};

	const focusItem = (button) => {
		button.scrollIntoView({
			behavior: reducedMotion() ? 'auto' : 'smooth',
			block: 'start',
		});
		button.focus({ preventScroll: true });
	};

	// Retries because a click or event before hydration has no listener yet.
	const reveal = (button, attempt = 0) => {
		if (isOpen(button)) {
			focusItem(button);
			return;
		}

		const panel = panelOf(button);

		if (!panel || attempt >= RETRIES) {
			return;
		}

		panel.dispatchEvent(new window.Event('beforematch'));
		window.setTimeout(() => reveal(button, attempt + 1), RETRY_MS);
	};

	const revealFromHash = () => {
		const button = toggleFor(currentId());

		if (button) {
			reveal(button);
		}
	};

	// Another open item of the same accordion (not of one nested in it).
	const otherOpen = (button) => {
		const accordion = button.closest(ACCORDION);

		if (!accordion) {
			return null;
		}

		return (
			Array.from(
				accordion.querySelectorAll(`${TOGGLE}[aria-expanded="true"]`)
			).find(
				(other) =>
					other !== button &&
					other.id &&
					other.closest(ACCORDION) === accordion
			) || null
		);
	};

	const onExpandedChange = (button) => {
		const current = currentId();

		if (isOpen(button)) {
			if (current === button.id) {
				return;
			}

			// Core opened it for a link to something inside the panel: keep that deeper link.
			const target = current ? document.getElementById(current) : null;
			const panel = panelOf(button);

			if (target && panel && panel.contains(target)) {
				return;
			}

			setHash(button.id);
			return;
		}

		if (current === button.id) {
			const other = otherOpen(button);

			setHash(other ? other.id : '');
		}
	};

	const observer = new window.MutationObserver((records) => {
		records.forEach((record) => {
			const button = record.target;

			// Hydration may re-set an unchanged value; only real flips count.
			if (
				!button.id ||
				!button.matches(TOGGLE) ||
				record.oldValue === button.getAttribute('aria-expanded')
			) {
				return;
			}

			onExpandedChange(button);
		});
	});

	const start = () => {
		observer.observe(document.body, {
			subtree: true,
			attributes: true,
			attributeFilter: ['aria-expanded'],
			attributeOldValue: true,
		});

		revealFromHash();
		window.addEventListener('hashchange', revealFromHash);
	};

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})(window, document);
