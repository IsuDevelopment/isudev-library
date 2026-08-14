/**
 * View script for the agenda accordion block.
 *
 * Implements the WAI-ARIA accordion pattern: keyboard navigation between
 * triggers (arrow keys, Home/End), an initial item opened from a URL hash,
 * and a hash update on open so the current state can be bookmarked.
 */

/* global history */

let isudevAgendaAccordionKeyboardNavigation = false;

/**
 * Whether an element's id matches the current URL hash.
 *
 * @param {HTMLElement} el
 * @return {boolean} Whether the element's id matches the current hash.
 */
function matchesHash(el) {
	const hash = window.location.hash;
	return el.getAttribute('id') === hash.replace('#', '');
}

/**
 * Open or close a trigger's panel, honouring the accordion's behaviour flags.
 *
 * @param {HTMLElement} trigger
 */
function toggleTrigger(trigger) {
	const accordion = trigger.closest('.isudev-agenda-accordion');
	if (!accordion) {
		return;
	}

	// Allow more than one item to stay open at once.
	const allowMultiple = accordion.hasAttribute('data-allow-multiple');
	// Allow each trigger to both open and close individually.
	const allowToggle =
		allowMultiple || accordion.hasAttribute('data-allow-toggle');

	const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
	const active = accordion.querySelector('[aria-expanded="true"]');

	if (!allowMultiple && active && active !== trigger) {
		active.setAttribute('aria-expanded', 'false');
		const activePanel = document.getElementById(
			active.getAttribute('aria-controls')
		);
		if (activePanel) {
			activePanel.setAttribute('hidden', '');
		}

		if (!allowToggle) {
			active.removeAttribute('aria-disabled');
		}
	}

	if (!isExpanded) {
		history.replaceState({}, '', `#${trigger.getAttribute('id')}`);
		trigger.setAttribute('aria-expanded', 'true');
		const panel = document.getElementById(
			trigger.getAttribute('aria-controls')
		);
		if (panel) {
			panel.removeAttribute('hidden');
		}

		if (!allowToggle) {
			trigger.setAttribute('aria-disabled', 'true');
		}
	} else if (allowToggle) {
		history.replaceState(
			{},
			'',
			window.location.pathname + window.location.search
		);
		trigger.setAttribute('aria-expanded', 'false');
		const panel = document.getElementById(
			trigger.getAttribute('aria-controls')
		);
		if (panel) {
			panel.setAttribute('hidden', '');
		}
	}
}

/**
 * Delegated click handler on the accordion container.
 *
 * @param {MouseEvent} event
 */
function onClick(event) {
	const { target } = event;

	if (!target.classList.contains('isudev-agenda-accordion__trigger')) {
		return;
	}

	event.preventDefault();
	toggleTrigger(target);

	if (!isudevAgendaAccordionKeyboardNavigation) {
		target
			.closest('.isudev-agenda-accordion')
			?.querySelectorAll('.isudev-agenda-accordion-item.is-focused')
			.forEach((item) => item.classList.remove('is-focused'));
	}
}

/**
 * Delegated keydown handler for arrow / Home / End navigation between
 * triggers, per the WAI-ARIA accordion pattern.
 *
 * @param {KeyboardEvent} event
 */
function onKeyDown(event) {
	const { target } = event;

	const accordion = target.closest('.isudev-agenda-accordion');
	if (
		!accordion ||
		!target.classList.contains('isudev-agenda-accordion__trigger')
	) {
		return;
	}

	const triggers = Array.from(
		accordion.querySelectorAll('.isudev-agenda-accordion__trigger')
	);

	if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
		const index = triggers.indexOf(target);
		const direction = event.key === 'ArrowDown' ? 1 : -1;
		const newIndex =
			(index + triggers.length + direction) % triggers.length;

		triggers[newIndex].focus();
		event.preventDefault();
	} else if (event.key === 'Home') {
		triggers[0].focus();
		event.preventDefault();
	} else if (event.key === 'End') {
		triggers[triggers.length - 1].focus();
		event.preventDefault();
	}
}

/**
 * Wire up a single accordion instance.
 *
 * @param {HTMLElement} accordion
 */
function initAccordion(accordion) {
	const allowMultiple = accordion.hasAttribute('data-allow-multiple');
	const allowToggle =
		allowMultiple || accordion.hasAttribute('data-allow-toggle');

	const triggers = Array.from(
		accordion.querySelectorAll('.isudev-agenda-accordion__trigger')
	);

	const hashMatch = triggers.find(matchesHash);
	if (hashMatch) {
		toggleTrigger(hashMatch);
	}

	accordion.addEventListener('click', onClick);
	accordion.addEventListener('keydown', onKeyDown);

	accordion
		.querySelectorAll('.isudev-agenda-accordion-item')
		.forEach((item) => {
			item.querySelectorAll('.isudev-agenda-accordion__trigger').forEach(
				(trigger) => {
					trigger.addEventListener('focus', () => {
						if (isudevAgendaAccordionKeyboardNavigation) {
							item.classList.add('is-focused');
						}
					});

					trigger.addEventListener('blur', () => {
						item.classList.remove('is-focused');
					});
				}
			);
		});

	if (!allowToggle) {
		const expanded = accordion.querySelector('[aria-expanded="true"]');
		if (expanded) {
			expanded.setAttribute('aria-disabled', 'true');
		}
	}
}

document.addEventListener('DOMContentLoaded', function () {
	document
		.querySelectorAll('.isudev-agenda-accordion')
		.forEach(initAccordion);

	document.addEventListener('keydown', () => {
		isudevAgendaAccordionKeyboardNavigation = true;
	});

	document.addEventListener('mouseup', () => {
		isudevAgendaAccordionKeyboardNavigation = false;
	});
});
