/**
 * Editor preview of cards, rendered by PHP through
 * `POST isudev-library/v1/post-cards/preview`. Requests made in the same tick
 * are sent as one batch, and results are cached by request content.
 */

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

const cache = new Map();
let queue = [];
let timer = null;

function flush() {
	const batch = queue;
	queue = [];
	timer = null;

	apiFetch({
		path: '/isudev-library/v1/post-cards/preview',
		method: 'POST',
		data: { items: batch.map(({ item }) => item) },
	})
		.then((response) => {
			batch.forEach(({ item, resolve }) =>
				resolve(response?.items?.[item.key] ?? { empty: true })
			);
		})
		.catch((error) => {
			batch.forEach(({ item, reject }) => {
				cache.delete(item.key);
				reject(error);
			});
		});
}

/**
 * Request one card preview.
 *
 * @param {Object} item `{ attributes, context?, wrapper?, withTemplate? }`.
 * @return {Promise<Object>} `{ empty, html, classes, postId, postType, template? }`.
 */
export function requestPreview(item) {
	const key = JSON.stringify(item);

	if (!cache.has(key)) {
		cache.set(
			key,
			new Promise((resolve, reject) => {
				queue.push({ item: { ...item, key }, resolve, reject });
				timer = timer ?? setTimeout(flush, 20);
			})
		);
	}

	return cache.get(key);
}

/**
 * Forget cached previews, e.g. after the theme's templates changed.
 */
export function clearPreviews() {
	cache.clear();
}

/**
 * @param {Object|null} item Preview request, or null for none.
 * @return {{status: string, data: Object|null}} `idle`, `loading`, `ready` or `error`.
 */
export function usePreview(item) {
	const key = item ? JSON.stringify(item) : '';
	const [state, setState] = useState({ status: 'idle', data: null, key: '' });

	useEffect(() => {
		if (!key) {
			setState({ status: 'idle', data: null, key });
			return undefined;
		}

		let active = true;
		setState((previous) => ({ ...previous, status: 'loading', key }));

		requestPreview(JSON.parse(key))
			.then((data) => active && setState({ status: 'ready', data, key }))
			.catch(
				() => active && setState({ status: 'error', data: null, key })
			);

		return () => {
			active = false;
		};
	}, [key]);

	return state;
}
