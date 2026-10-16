/**
 * Support widget: open and close the form, send it over REST.
 *
 * No build step; WordPress globals. Strings come from PHP in
 * `window.isudevDashboardSupport`.
 *
 * @param {Object} wp     WordPress globals.
 * @param {Object} config Path and strings from PHP.
 */
(function (wp, config) {
	if (!wp || !wp.apiFetch || !config) {
		return;
	}

	const root = document.getElementById('isudev-support');
	const form = root && root.querySelector('.isudev-dash__form');

	if (!form) {
		return;
	}

	const toggle = root.querySelector('.isudev-dash__toggle');
	const toggleLabel = toggle.querySelector('.isudev-dash__toggle-label');
	const toggleIcon = toggle.querySelector('.dashicons');
	const notice = root.querySelector('.isudev-dash__notice');
	const submit = form.querySelector('[type="submit"]');
	const { strings } = config;

	const showNotice = (type, text) => {
		notice.textContent = '';

		if (!text) {
			return;
		}

		const box = document.createElement('div');
		const paragraph = document.createElement('p');

		box.className = `notice notice-${type} inline`;
		paragraph.textContent = text;
		box.appendChild(paragraph);
		notice.appendChild(box);
	};

	const setOpen = (open) => {
		form.hidden = !open;
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		toggleLabel.textContent = open ? strings.hide : strings.show;
		toggleIcon.className = `dashicons ${
			open ? 'dashicons-arrow-up-alt2' : 'dashicons-edit'
		}`;

		if (open) {
			showNotice('', '');
			form.querySelector('input, select, textarea').focus();
		}
	};

	toggle.addEventListener('click', () => setOpen(form.hidden));
	form.querySelector('.isudev-dash__cancel').addEventListener('click', () => {
		setOpen(false);
		toggle.focus();
	});

	form.addEventListener('submit', (event) => {
		event.preventDefault();

		if (!form.checkValidity()) {
			showNotice('error', strings.required);
			form.reportValidity();
			return;
		}

		const data = Object.fromEntries(new window.FormData(form).entries());

		submit.disabled = true;
		submit.textContent = strings.sending;

		wp.apiFetch({ path: config.path, method: 'POST', data })
			.then((response) => {
				form.querySelector('[name="subject"]').value = '';
				form.querySelector('[name="description"]').value = '';
				setOpen(false);
				showNotice('success', response.message);
				toggle.focus();
			})
			.catch((error) => {
				showNotice('error', (error && error.message) || strings.failed);
			})
			.finally(() => {
				submit.disabled = false;
				submit.textContent = strings.send;
			});
	});
})(window.wp, window.isudevDashboardSupport);
