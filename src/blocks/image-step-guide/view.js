/**
 * View script for the image step guide block.
 *
 * Frontend behaviour only: when a guide has "enlarge on click" turned on,
 * clicking a step's image opens it enlarged in a simple overlay. Built with
 * no dependency on core/image's own lightbox implementation — see
 * guides/image-step-guide.md for why.
 */

/**
 * Open an image enlarged in a full-screen overlay.
 *
 * @param {string} src        Image URL.
 * @param {string} alt        Image alt text.
 * @param {string} closeLabel Accessible label for the close button.
 */
function openLightbox(src, alt, closeLabel) {
	const overlay = document.createElement('div');
	overlay.className = 'isudev-image-step-guide-lightbox';
	overlay.setAttribute('role', 'dialog');
	overlay.setAttribute('aria-modal', 'true');

	const image = document.createElement('img');
	image.src = src;
	image.alt = alt;
	image.className = 'isudev-image-step-guide-lightbox__image';

	const closeButton = document.createElement('button');
	closeButton.type = 'button';
	closeButton.className = 'isudev-image-step-guide-lightbox__close';
	closeButton.setAttribute('aria-label', closeLabel);
	closeButton.textContent = '×';

	const close = () => {
		overlay.remove();
		document.body.classList.remove('isudev-image-step-guide-lightbox-open');
		document.removeEventListener('keydown', onKeydown);
	};

	const onKeydown = (event) => {
		if (event.key === 'Escape') {
			close();
		}
	};

	overlay.addEventListener('click', (event) => {
		if (event.target === overlay || event.target === closeButton) {
			close();
		}
	});

	overlay.append(image, closeButton);
	document.body.appendChild(overlay);
	document.body.classList.add('isudev-image-step-guide-lightbox-open');
	document.addEventListener('keydown', onKeydown);
	closeButton.focus();
}

document.addEventListener('click', function (event) {
	const guide = event.target.closest(
		'.isudev-image-step-guide.has-lightbox-images'
	);

	if (!guide) {
		return;
	}

	const image = event.target.closest(
		'.isudev-image-step-guide-step__media img'
	);

	if (!image) {
		return;
	}

	event.preventDefault();
	openLightbox(
		image.currentSrc || image.src,
		image.alt,
		guide.dataset.lightboxCloseLabel || 'Close'
	);
});
