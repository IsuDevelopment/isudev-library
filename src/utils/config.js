/**
 * JS mirror of `IsuDevLibrary\Config\resolve_block_value()`
 * (includes/config.php). The editor has no PHP to call into, so this module
 * re-implements the same lookup order against the `isudevLibraryConfig`
 * global published once for the whole block editor by `Config\boot()`.
 *
 * This duplicates PHP logic and there is no JS test runner in this repo to
 * catch drift — the two must be changed together.
 */

/* global globalThis */

/**
 * Read the merged `library` config published for the block editor.
 *
 * Reading a global here does not break the "never use global window/document in
 * editor code" rule: that rule is about the DOM, because the canvas is iframed
 * and has its own document. Editor JavaScript itself runs in the top frame, so
 * plain data globals belong to it. `globalThis` says that explicitly and is what
 * `@isudev/gutenberg`'s `getLocalizedIcons()` uses for `isudevIcons`.
 *
 * @return {Object} The `library` subtree, or `{}` when the global is unset.
 */
export function getLibraryConfig() {
	return globalThis.isudevLibraryConfig ?? {};
}

/**
 * Read a config value that must be an array.
 *
 * `isudev.json` is hand-written theme configuration, so any value can be the
 * wrong shape. PHP guards every read with `is_array()`; without the same guard
 * here a single mistyped key takes the whole block editor down with a
 * `TypeError` on `.filter` or `.includes`.
 *
 * @param {*}     value    Resolved config value.
 * @param {Array} fallback Value returned when `value` is not an array.
 * @return {Array} `value` when it is an array, otherwise `fallback`.
 */
export function asArray(value, fallback) {
	return Array.isArray(value) ? value : fallback;
}

/**
 * Read a config value that must be a positive integer.
 *
 * Mirrors the PHP path, where `(int)` plus `Utils\normalize_size()` turns
 * anything unusable into the default size.
 *
 * @param {*}      value    Resolved config value.
 * @param {number} fallback Value returned when `value` is not a positive number.
 * @return {number} A positive integer.
 */
export function asPositiveInt(value, fallback) {
	const parsed = Number.parseInt(value, 10);

	return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

/**
 * Resolve a config value for a block, honouring variation overrides.
 *
 * Lookup order: variation value, then block value, then `fallback`. A
 * resolved `undefined` counts as "not set" and falls through to the next
 * step, mirroring PHP's sentinel-based `resolve_block_value()`.
 *
 * @param {string}          blockName            Full block name, e.g. `isudev/social-share`.
 * @param {string|string[]} key                  Dot-notation key or an ordered key path.
 * @param {*}               [fallback]           Value returned when nothing resolves.
 * @param {string}          [variationNamespace] Variation namespace; '' to skip variation lookup.
 * @return {*} Resolved value.
 */
export function getBlockConfig(
	blockName,
	key,
	fallback = null,
	variationNamespace = ''
) {
	const keyPath = Array.isArray(key) ? key : String(key).split('.');
	const config = getLibraryConfig();

	if (variationNamespace !== '') {
		const variationValue = readPath(config, [
			blockName,
			'variations',
			variationNamespace,
			...keyPath,
		]);

		if (variationValue !== undefined) {
			return variationValue;
		}
	}

	const blockValue = readPath(config, [blockName, ...keyPath]);

	if (blockValue !== undefined) {
		return blockValue;
	}

	return fallback;
}

/**
 * Walk a nested object by an ordered key path.
 *
 * @param {Object}               data Source object.
 * @param {Array<string|number>} path Ordered list of keys to walk.
 * @return {*} Resolved value, or `undefined` when the path does not resolve.
 */
function readPath(data, path) {
	let current = data;

	for (const segment of path) {
		/*
		 * hasOwnProperty, not the `in` operator: `in` also finds inherited
		 * Object.prototype members, so a key path ending in `constructor` or
		 * `toString` would resolve to a function instead of falling through to
		 * the fallback. PHP's isset() does not do that, and this module claims
		 * to mirror it.
		 */
		if (
			current === null ||
			typeof current !== 'object' ||
			!Object.prototype.hasOwnProperty.call(current, segment)
		) {
			return undefined;
		}

		current = current[segment];
	}

	return current;
}
