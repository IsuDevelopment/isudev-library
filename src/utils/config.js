/**
 * JS mirror of `IsuDevLibrary\Config\resolve_block_value()`
 * (includes/config.php). The editor has no PHP to call into, so this module
 * re-implements the same lookup order against the `isudevLibraryConfig`
 * global published once for the whole block editor by `Config\boot()`.
 *
 * This duplicates PHP logic and there is no JS test runner in this repo to
 * catch drift — the two must be changed together.
 */

/**
 * Read the merged `library` config published for the block editor.
 *
 * @return {Object} The `library` subtree, or `{}` when the global is unset.
 */
export function getLibraryConfig() {
	return window.isudevLibraryConfig ?? {};
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
		if (
			current === null ||
			typeof current !== 'object' ||
			!(segment in current)
		) {
			return undefined;
		}

		current = current[segment];
	}

	return current;
}
