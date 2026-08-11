/**
 * View script for the social share network button block.
 *
 * Uses event delegation on the document to handle all share button
 * interactions. No inline onclick — CSP-friendly.
 */

/* global navigator */

const POPUP_NETWORKS = new Set(['facebook', 'x', 'linkedin']);
const POPUP_WIDTH = 550;
const POPUP_HEIGHT = 450;

/**
 * Get the closest share control from an event target.
 *
 * Matches both element types: the nine sharing networks render an <a>, the
 * three action networks (link, print, system) render a <button>.
 *
 * @param {EventTarget} target
 * @return {HTMLElement|null} The share control, or null when not found.
 */
function getShareControl(target) {
	return target.closest(
		'a.isudev-share__network[data-network], button.isudev-share__network[data-network]'
	);
}

/**
 * Open a URL in a centered popup window.
 *
 * @param {string} url
 */
function openPopup(url) {
	const left = Math.round(
		window.screenX + (window.outerWidth - POPUP_WIDTH) / 2
	);
	const top = Math.round(
		window.screenY + (window.outerHeight - POPUP_HEIGHT) / 2
	);
	const options = `width=${POPUP_WIDTH},height=${POPUP_HEIGHT},top=${top},left=${left},toolbar=no,location=no,resizable=yes,scrollbars=yes`;
	window.open(url, '_blank', options);
}

/**
 * Copy text to clipboard with fallback for older browsers.
 *
 * @param {string} text
 * @return {Promise<void>}
 */
async function copyToClipboard(text) {
	if (navigator.clipboard && navigator.clipboard.writeText) {
		return navigator.clipboard.writeText(text);
	}

	// Fallback for older browsers.
	const textArea = document.createElement('textarea');
	textArea.value = text;
	textArea.setAttribute('readonly', '');
	textArea.style.position = 'fixed';
	textArea.style.left = '-9999px';
	document.body.appendChild(textArea);
	textArea.select();

	try {
		document.execCommand('copy');
	} finally {
		document.body.removeChild(textArea);
	}
}

/**
 * Show a temporary status message near a button.
 *
 * `role="status"` makes this the announcement itself for assistive
 * technology, not merely a visual confirmation.
 *
 * @param {HTMLElement} button
 * @param {string}      message
 */
function showMessage(button, message) {
	const el = document.createElement('span');
	el.className = 'isudev-share__network-message';
	el.setAttribute('role', 'status');
	el.textContent = message;

	button.style.position = 'relative';
	button.appendChild(el);

	setTimeout(() => el.remove(), 2000);
}

/**
 * Main click handler — delegated on document.
 *
 * @param {MouseEvent} event
 */
function handleClick(event) {
	const control = getShareControl(event.target);
	if (!control) {
		return;
	}

	const network = control.dataset.network;

	// Popup networks: facebook, x, linkedin. These are anchors, so the
	// default navigation has to be stopped explicitly.
	if (POPUP_NETWORKS.has(network)) {
		event.preventDefault();
		openPopup(control.href);
		return;
	}

	// Copy link. A <button>, so there is no default action to prevent.
	if (network === 'link') {
		const text = control.dataset.copyText || '';
		const message = control.dataset.message;

		copyToClipboard(text).then(() => {
			if (message) {
				showMessage(control, message);
			}
		});
		return;
	}

	// System Share: Web Share API with silent clipboard fallback.
	if (network === 'system') {
		if (navigator.share) {
			navigator
				.share({
					title: document.title,
					url: window.location.href,
				})
				.catch(() => {});
		} else {
			copyToClipboard(window.location.href).then(() => {
				const message = control.dataset.message;
				if (message) {
					showMessage(control, message);
				}
			});
		}
		return;
	}

	// Print. A <button>, so there is no default action to prevent.
	if (network === 'print') {
		window.print();
	}

	// Email: default browser behaviour (mailto: link) — no JS needed.
}

document.addEventListener('click', handleClick);
